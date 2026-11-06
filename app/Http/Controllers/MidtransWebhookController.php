<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Services\MidtransService;
use App\Services\TransaksiPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransWebhookController extends Controller
{
    public function handle(
        Request $request,
        MidtransService $midtrans,
        TransaksiPaymentService $paymentService
    ): JsonResponse {

        // Ambil data notifikasi dari Midtrans.
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');

        // Pastikan data utama tersedia.
        if (
            ! is_string($orderId)
            || ! is_string($statusCode)
            || ! is_string($grossAmount)
            || ! is_string($signatureKey)
        ) {
            return response()->json([
                'message' => 'Payload notifikasi tidak valid.',
            ], 400);
        }

        $serverKey = config('midtrans.server_key');

        if (blank($serverKey)) {
            Log::critical('Midtrans Server Key belum dikonfigurasi.');

            return response()->json([
                'message' => 'Konfigurasi pembayaran tidak tersedia.',
            ], 500);
        }

        // Verifikasi signature menggunakan SHA512.
        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $serverKey
        );

        $signatureValid = hash_equals($expectedSignature, $signatureKey);

        if (! $signatureValid) {
            Log::warning('Signature webhook tidak cocok. Melanjutkan verifikasi melalui Get Status API.', [
                'order_id' => $orderId,
            ]);
        }

        // Cari transaksi lokal berdasarkan invoice.
        $transaksi = Transaksi::query()
            ->where('no_invoice', $orderId)
            ->where('metode_bayar', 'qris')
            ->first();

        if (! $transaksi) {
            Log::warning('Transaksi webhook tidak ditemukan.', [
                'order_id' => $orderId,
            ]);

            return response()->json([
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        try {
            // Jangan langsung percaya status dari request webhook.
            // Ambil status terbaru langsung dari Midtrans.
            $status = $midtrans->getTransactionStatus($orderId);

            // Pastikan respons merupakan transaksi yang sama.
            if (
                ($status['order_id'] ?? null) !== $transaksi->no_invoice
                || ($status['payment_type'] ?? null) !== 'qris'
                || ($status['currency'] ?? null) !== 'IDR'
            ) {
                return response()->json([
                    'message' => 'Data transaksi Midtrans tidak sesuai.',
                ], 422);
            }

            // Cocokkan ID transaksi Midtrans.
            if (
                empty($transaksi->midtrans_transaction_id)
                || ($status['transaction_id'] ?? null)
                    !== $transaksi->midtrans_transaction_id
            ) {
                return response()->json([
                    'message' => 'ID transaksi Midtrans tidak sesuai.',
                ], 422);
            }

            // Cocokkan nominal pembayaran dengan database.
            $nominalMidtrans = number_format(
                (float) ($status['gross_amount'] ?? -1),
                2,
                '.',
                ''
            );

            $nominalLokal = number_format(
                (int) $transaksi->total_bayar,
                2,
                '.',
                ''
            );

            if ($nominalMidtrans !== $nominalLokal) {
                Log::warning('Nominal webhook Midtrans tidak sesuai.', [
                    'order_id' => $orderId,
                ]);

                return response()->json([
                    'message' => 'Nominal pembayaran tidak sesuai.',
                ], 422);
            }

            // Gunakan status terbaru yang diverifikasi dari Midtrans.
            $transactionStatus = $status['transaction_status'] ?? null;

            switch ($transactionStatus) {

                case 'settlement':

                    // Pembayaran sukses harus memiliki status code 200.
                    if (($status['status_code'] ?? null) !== '200') {
                        return response()->json([
                            'message' => 'Status pembayaran belum valid.',
                        ], 422);
                    }

                    // Jika fraud_status tersedia, nilainya harus ACCEPT.
                    if (
                        isset($status['fraud_status'])
                        && strtolower($status['fraud_status']) !== 'accept'
                    ) {
                        return response()->json([
                            'message' => 'Status verifikasi pembayaran belum diterima.',
                        ], 422);
                    }

                    $paymentService->markAsPaid($orderId);

                    break;

                case 'expire':

                    $paymentService->releaseReservation(
                        $orderId,
                        'kedaluwarsa'
                    );

                    break;

                case 'deny':
                case 'cancel':

                    $paymentService->releaseReservation(
                        $orderId,
                        'gagal'
                    );

                    break;

                case 'pending':

                    // Belum perlu mengubah transaksi.
                    break;

                default:

                    Log::info('Status Midtrans belum dipetakan.', [
                        'order_id' => $orderId,
                        'status' => $transactionStatus,
                    ]);

                    break;
            }

            return response()->json([
                'message' => 'Notifikasi berhasil diproses.',
            ]);

        } catch (Throwable $exception) {

            Log::error('Gagal memproses webhook Midtrans.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);

            // HTTP 500 memberi kesempatan Midtrans mengirim ulang.
            return response()->json([
                'message' => 'Gagal memproses pembayaran.',
            ], 500);
        }
    }
}
