<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaksi;
use App\Models\Garansi;
use App\Models\RiwayatKlaimGaransi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

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
                'whatsappToko' => config('services.whatsapp.toko'),
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
                'whatsappToko' => config('services.whatsapp.toko'),
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

                // Informasi keputusan dan penyelesaian klaim.
                'catatan_pelanggan' => $garansi->catatan_pelanggan,

                'jenis_penyelesaian' => $garansi->jenis_penyelesaian,

                'detail_penyelesaian' => $garansi->detail_penyelesaian,

                'nominal_refund' => $garansi->nominal_refund === null
                    ? null
                    : (int) $garansi->nominal_refund,

                'metode_refund' => $garansi->metode_refund,

                'tanggal_selesai' => $garansi->tanggal_selesai?->format(
                    'd/m/Y H:i'
                ),
            ],
            'whatsappToko' => config('services.whatsapp.toko'),
        ]);
    }

    
    public function storeClaim(Request $request): RedirectResponse
    {
        // Validasi data yang dikirim pelanggan.
        $validated = $request->validate([
            'no_garansi' => ['required', 'string', 'max:80'],
            'keluhan' => ['required', 'string', 'min:10', 'max:2000'],
            'foto_klaim' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $noGaransi = Str::upper(
            trim($validated['no_garansi'])
        );

        $fotoPath = null;

        try {
            DB::transaction(function () use (
                $validated,
                $request,
                $noGaransi,
                &$fotoPath
            ): void {
                // Kunci record garansi selama proses pengajuan.
                $garansi = Garansi::query()
                    ->where('no_garansi', $noGaransi)
                    ->lockForUpdate()
                    ->first();

                if (! $garansi) {
                    throw ValidationException::withMessages([
                        'no_garansi' => 'Nomor garansi tidak ditemukan.',
                    ]);
                }

                // Periksa masa berlaku.
                if ($garansi->kedaluwarsa) {
                    throw ValidationException::withMessages([
                        'no_garansi' => 'Masa garansi produk sudah berakhir.',
                    ]);
                }

                // Satu record garansi hanya boleh diajukan sekali.
                if ($garansi->status_klaim !== 'belum_ada') {
                    throw ValidationException::withMessages([
                        'no_garansi' => 'Garansi ini sudah memiliki pengajuan klaim.',
                    ]);
                }

                // Simpan foto ke storage/app/public/klaim-garansi.
                $storedPath = $request->file('foto_klaim')
                    ->store('klaim-garansi', 'public');

                if (! $storedPath) {
                    throw new RuntimeException(
                        'Foto klaim gagal disimpan.'
                    );
                }

                $fotoPath = $storedPath;

                // Perbarui status dan data klaim.
                $garansi->update([
                    'status_klaim' => 'diajukan',
                    'keluhan' => $validated['keluhan'],
                    'foto_klaim' => $fotoPath,
                    'tanggal_klaim' => now(),
                ]);
                RiwayatKlaimGaransi::create([
                    'garansi_id' => $garansi->id,
                    'jenis_aksi' => 'pengajuan',
                    'aktor' => 'pelanggan',
                    'user_id' => null,
                    'status_sebelumnya' => 'belum_ada',
                    'status_baru' => 'diajukan',
                    'catatan' => $validated['keluhan'],
                ]);
            });
        } catch (Throwable $exception) {
            // Hapus foto jika proses database gagal.
            if ($fotoPath !== null) {
                Storage::disk('public')->delete($fotoPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('garansi.index', [
                'no_garansi' => $noGaransi,
            ])
            ->with(
                'success',
                'Pengajuan klaim berhasil dikirim. Silakan pantau status garansi Anda.'
            );
    }

}
