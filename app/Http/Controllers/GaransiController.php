<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaksi;
use App\Models\Garansi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class GaransiController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'no_garansi' => ['nullable', 'string', 'max:80'],
        ]);

        $noGaransi = strtoupper(
            trim((string) ($validated['no_garansi'] ?? ''))
        );

        // Halaman pertama kali dibuka, sebelum pencarian.
        if ($noGaransi === '') {
            return Inertia::render('garansi/Index', [
                'dicari' => false,
                'noGaransi' => '',
                'hasil' => null,
            ]);
        }

        // Cari nomor garansi secara tepat.
        $garansi = Garansi::query()
            ->where('no_garansi', $noGaransi)
            ->first();

        // Nomor tidak ditemukan: tampilkan hasil pencarian kosong.
        if (! $garansi) {
            return Inertia::render('garansi/Index', [
                'dicari' => true,
                'noGaransi' => $noGaransi,
                'hasil' => null,
            ]);
        }

        // Nama barang diambil dari snapshot detail transaksi.
        $detail = DetailTransaksi::query()
            ->find($garansi->detail_transaksi_id);

        $tanggalMulai = Carbon::parse($garansi->tanggal_mulai);
        $tanggalBerakhir = Carbon::parse($garansi->tanggal_berakhir);

        return Inertia::render('garansi/Index', [
            'dicari' => true,
            'noGaransi' => $noGaransi,

            'hasil' => [
                'no_garansi' => $garansi->no_garansi,
                'nama_produk' => $detail?->nama_produk
                    ?? 'Produk tidak tersedia',
                'tanggal_mulai' => $tanggalMulai->format('d/m/Y'),
                'tanggal_berakhir' => $tanggalBerakhir->format('d/m/Y'),

                // Status masa berlaku berbeda dari status klaim.
                'masa_berlaku' => $tanggalBerakhir->endOfDay()->isPast()
                    ? 'berakhir'
                    : 'aktif',

                'status_klaim' => $garansi->status_klaim,
            ],
        ]);
    }
}
