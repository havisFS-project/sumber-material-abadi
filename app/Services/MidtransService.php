<?php

namespace App\Services;

use App\Models\Transaksi;
use Illuminate\Support\Carbon;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Transaction;
use RuntimeException;

class MidtransService
{
    /**
     * Mengatur konfigurasi SDK Midtrans.
     */
    public function configure(): void
    {
        $serverKey = config('midtrans.server_key');

        if (blank($serverKey)) {
            throw new RuntimeException(
                'MIDTRANS_SERVER_KEY belum dikonfigurasi di file .env.'
            );
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Membuat pembayaran QRIS melalui Midtrans Core API.
     */
    public function createQrisCharge(Transaksi $transaksi): array
    {
        $this->configure();

        if ($transaksi->metode_bayar !== 'qris') {
            throw new RuntimeException(
                'Transaksi ini bukan menggunakan metode QRIS.'
            );
        }

        $transaksi->loadMissing('detail');

        $items = $transaksi->detail->map(function ($detail) {
            return [
                'id' => (string) $detail->produk_id,
                'price' => (int) $detail->harga_satuan
                    - (int) $detail->diskon_satuan,
                'quantity' => (int) $detail->jumlah,
                'name' => $detail->nama_produk,
            ];
        })->values()->all();

        $params = [
            'payment_type' => 'qris',

            'transaction_details' => [
                'order_id' => $transaksi->no_invoice,
                'gross_amount' => (int) $transaksi->total_bayar,
            ],

            'item_details' => $items,

            'qris' => [
                'acquirer' => config('midtrans.qris_acquirer', 'gopay'),
            ],

            'custom_expiry' => [
                'order_time' => now('Asia/Jakarta')
                    ->format('Y-m-d H:i:s O'),
                'expiry_duration' => 15,
                'unit' => 'minute',
            ],
        ];

        if ($transaksi->nama_pelanggan || $transaksi->no_hp_pelanggan) {
            $params['customer_details'] = array_filter([
                'first_name' => $transaksi->nama_pelanggan,
                'phone' => $transaksi->no_hp_pelanggan,
            ]);
        }

        $response = CoreApi::charge($params);

        $data = json_decode(json_encode($response), true);

        if (! is_array($data)) {
            throw new RuntimeException(
                'Respons pembayaran Midtrans tidak valid.'
            );
        }

        $qrisUrl = null;

        foreach ($data['actions'] ?? [] as $action) {
            if (($action['name'] ?? '') === 'generate-qr-code-v2') {
                $qrisUrl = $action['url'] ?? null;
                break;
            }
        }

        if (! $qrisUrl) {
            foreach ($data['actions'] ?? [] as $action) {
                if (($action['name'] ?? '') === 'generate-qr-code') {
                    $qrisUrl = $action['url'] ?? null;
                    break;
                }
            }
        }

        if (
            ($data['transaction_status'] ?? '') !== 'pending'
            || empty($data['transaction_id'])
            || empty($qrisUrl)
        ) {
            throw new RuntimeException(
                'Midtrans tidak berhasil membuat QRIS pembayaran.'
            );
        }

        return [
            'transaction_id' => $data['transaction_id'],
            'order_id' => $data['order_id'],
            'qris_url' => $qrisUrl,

            'payment_expires_at' => isset($data['expiry_time'])
                ? Carbon::parse($data['expiry_time'], 'Asia/Jakarta')
                : now('Asia/Jakarta')->addMinutes(15),
        ];
    }

    /**
     * Mengambil status transaksi langsung dari Midtrans.
     *
     * Metode ini digunakan untuk memverifikasi webhook.
     */
    public function getTransactionStatus(string $orderId): array
    {
        $this->configure();

        $response = Transaction::status($orderId);

        $data = json_decode(json_encode($response), true);

        if (! is_array($data)) {
            throw new RuntimeException(
                'Gagal membaca status transaksi dari Midtrans.'
            );
        }

        return $data;
    }
}
