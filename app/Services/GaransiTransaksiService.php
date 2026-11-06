<?php

namespace App\Services;

use App\Models\Garansi;
use App\Models\Transaksi;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class GaransiTransaksiService
{
    /**
     * Menerbitkan garansi untuk transaksi yang sudah lunas.
     */
    public function terbitkan(Transaksi $transaksi): void
    {
        // Garansi hanya boleh diterbitkan untuk transaksi lunas.
        if ($transaksi->status !== 'lunas') {
            throw ValidationException::withMessages([
                'payment' => 'Garansi hanya dapat diterbitkan setelah pembayaran berhasil.',
            ]);
        }

        $transaksi->loadMissing([
            'detail.produk',
            'detail.garansi',
        ]);

        foreach ($transaksi->detail as $detail) {

            // Lewati jika garansi sudah pernah dibuat.
            if ($detail->garansi) {
                continue;
            }

            $produk = $detail->produk;

            // Produk tanpa garansi tidak perlu dibuatkan data garansi.
            if (! $produk || ! $produk->punya_garansi) {
                continue;
            }

            $masaGaransi = (int) $produk->masa_garansi_bulan;

            if ($masaGaransi < 1 || $masaGaransi > 120) {
                throw ValidationException::withMessages([
                    'payment' => "Masa garansi produk {$detail->nama_produk} tidak valid.",
                ]);
            }

            // Tanggal garansi mengikuti tanggal transaksi.
            $tanggalMulai = $transaksi->created_at
                ? $transaksi->created_at->copy()->startOfDay()
                : now()->startOfDay();

            // Membuat nomor garansi unik.
            do {
                $nomorGaransi = 'GAR-'
                    . $tanggalMulai->format('Ymd')
                    . '-'
                    . Str::upper(Str::random(8));

            } while (
                Garansi::where('no_garansi', $nomorGaransi)->exists()
            );

            // Terbitkan garansi untuk detail transaksi ini.
            Garansi::create([
                'no_garansi' => $nomorGaransi,
                'detail_transaksi_id' => $detail->id,

                'tanggal_mulai' => $tanggalMulai->toDateString(),

                'tanggal_berakhir' => $tanggalMulai
                    ->copy()
                    ->addMonthsNoOverflow($masaGaransi)
                    ->toDateString(),

                'status_klaim' => 'belum_ada',
                'is_valid' => true,
            ]);
        }
    }
}
