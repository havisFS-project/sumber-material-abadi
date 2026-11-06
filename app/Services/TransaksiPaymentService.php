<?php

namespace App\Services;

use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransaksiPaymentService
{
    /**
     * Mengubah transaksi pending menjadi lunas.
     * Stok sudah dikurangi saat reservasi QRIS dibuat.
     */
    public function markAsPaid(string $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {

            $transaksi = Transaksi::query()
                ->where('no_invoice', $invoice)
                ->lockForUpdate()
                ->firstOrFail();

            // Mencegah pembayaran diproses dua kali.
            if ($transaksi->status !== 'menunggu_pembayaran') {
                return false;
            }

            $transaksi->update([
                'status' => 'lunas',
            ]);

            // Terbitkan garansi setelah pembayaran berhasil.
            app(GaransiTransaksiService::class)
                ->terbitkan($transaksi);

            return true;

        }, 3);
    }

    /**
     * Mengembalikan stok reservasi ketika pembayaran gagal
     * atau kedaluwarsa.
     */
    public function releaseReservation(
        string $invoice,
        string $targetStatus
    ): bool {
        if (! in_array($targetStatus, ['gagal', 'kedaluwarsa'], true)) {
            throw new RuntimeException(
                'Status tujuan pengembalian stok tidak valid.'
            );
        }

        return DB::transaction(function () use (
            $invoice,
            $targetStatus
        ) {

            $transaksi = Transaksi::query()
                ->where('no_invoice', $invoice)
                ->lockForUpdate()
                ->firstOrFail();

            // Reservasi hanya boleh dilepas sekali.
            if ($transaksi->status !== 'menunggu_pembayaran') {
                return false;
            }

            // Hitung jumlah setiap produk dalam transaksi.
            $jumlahProduk = $transaksi->detail()
                ->selectRaw('produk_id, SUM(jumlah) as total')
                ->groupBy('produk_id')
                ->orderBy('produk_id')
                ->get();

            if ($jumlahProduk->isEmpty()) {
                throw new RuntimeException(
                    'Detail transaksi tidak ditemukan. Stok tidak dapat dikembalikan.'
                );
            }

            // Kembalikan stok setiap produk.
            foreach ($jumlahProduk as $item) {

                $produk = Produk::query()
                    ->whereKey($item->produk_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $produk->increment(
                    'stok',
                    (int) $item->total
                );
            }

            // Tandai transaksi dengan status akhirnya.
            $transaksi->update([
                'status' => $targetStatus,
            ]);

            return true;

        }, 3);
    }
}
