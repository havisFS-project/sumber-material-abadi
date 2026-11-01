<?php

namespace App\Http\Controllers;

use App\Models\Garansi;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KasirTransaksiController extends Controller
{
    /**
     * Admin dan kasir boleh mengakses riwayat transaksi.
     */
    private function authorizeKasir(Request $request): void
    {
        abort_unless(
            in_array(
                $request->user()?->role,
                ['admin', 'kasir'],
                true
            ),
            403
        );
    }

    /**
     * Khusus admin untuk melakukan pembatalan transaksi.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403
        );
    }

    /**
     * Menampilkan riwayat transaksi.
     */
    public function index(Request $request): Response
    {
        $this->authorizeKasir($request);

        $search = trim((string) $request->query('q', ''));
        $user = $request->user();

        $query = Transaksi::query()
            ->with('kasir:id,name')
            ->withCount('detail')
            ->orderByDesc('created_at');

        // Kasir hanya melihat transaksinya sendiri.
        // Admin boleh melihat semua transaksi.
        if ($user->role === 'kasir') {
            $query->where('kasir_id', $user->id);
        }

        // Pencarian invoice atau nama pelanggan.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_invoice', 'like', "%{$search}%")
                    ->orWhere(
                        'nama_pelanggan',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $transaksi = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Transaksi $item) => [
                'id' => $item->id,
                'no_invoice' => $item->no_invoice,

                'tanggal' => $item->created_at?->format(
                    'd/m/Y H:i'
                ),

                'nama_pelanggan' => $item->nama_pelanggan,
                'nama_kasir' => $item->kasir?->name ?? '-',

                'jumlah_detail' => $item->detail_count,

                'subtotal' => (int) $item->subtotal,
                'total_diskon' => (int) $item->total_diskon,
                'total_bayar' => (int) $item->total_bayar,

                'metode_bayar' => $item->metode_bayar,
                'status' => $item->status,

                'alasan_batal' => $item->alasan_batal,

                // Hanya admin yang boleh membatalkan transaksi.
                'can_void' => $user->role === 'admin'
                    && $item->status === 'lunas',
            ]);

        return Inertia::render('kasir/Riwayat', [
            'transaksi' => $transaksi,

            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    /**
     * Menampilkan struk transaksi.
     */
    public function struk(
        Request $request,
        string $invoice
    ): Response {
        $this->authorizeKasir($request);

        $user = $request->user();

        $query = Transaksi::query()
            ->with([
                'kasir:id,name',
                'detail.garansi',
            ])
            ->where('no_invoice', $invoice);

        // Kasir tidak boleh melihat transaksi kasir lain.
        if ($user->role === 'kasir') {
            $query->where('kasir_id', $user->id);
        }

        $item = $query->firstOrFail();

        return Inertia::render('kasir/Struk', [
            'transaksi' => [
                'id' => $item->id,
                'no_invoice' => $item->no_invoice,

                'tanggal' => $item->created_at?->format(
                    'd/m/Y H:i'
                ),

                'nama_pelanggan' => $item->nama_pelanggan,
                'no_hp_pelanggan' => $item->no_hp_pelanggan,

                'nama_kasir' => $item->kasir?->name ?? '-',

                'subtotal' => (int) $item->subtotal,
                'total_diskon' => (int) $item->total_diskon,
                'total_bayar' => (int) $item->total_bayar,

                'metode_bayar' => $item->metode_bayar,

                'uang_diterima' => $item->uang_diterima === null
                    ? null
                    : (int) $item->uang_diterima,

                'kembalian' => $item->kembalian === null
                    ? null
                    : (int) $item->kembalian,

                'status' => $item->status,
                'alasan_batal' => $item->alasan_batal,

                'detail' => $item->detail->map(
                    fn ($detail) => [
                        'id' => $detail->id,
                        'nama_produk' => $detail->nama_produk,
                        'harga_satuan' => (int) $detail->harga_satuan,
                        'diskon_satuan' => (int) $detail->diskon_satuan,
                        'jumlah' => (int) $detail->jumlah,
                        'subtotal' => (int) $detail->subtotal,

                        'garansi' => $detail->garansi
                            ? [
                                'no_garansi' => $detail->garansi->no_garansi,

                                'tanggal_mulai' => $detail->garansi->tanggal_mulai
                                    ? Carbon::parse(
                                        $detail->garansi->tanggal_mulai
                                    )->format('d/m/Y')
                                    : null,

                                'tanggal_berakhir' => $detail->garansi->tanggal_berakhir
                                    ? Carbon::parse(
                                        $detail->garansi->tanggal_berakhir
                                    )->format('d/m/Y')
                                    : null,

                                'is_valid' => (bool) $detail->garansi->is_valid,
                            ]
                            : null,
                    ]
                )->values(),
            ],
        ]);
    }

    /**
     * Membatalkan transaksi oleh admin.
     *
     * Pengembalian stok, pembatalan garansi,
     * dan perubahan status dilakukan secara atomik.
     */
    public function void(
        Request $request,
        Transaksi $transaksi
    ): RedirectResponse {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'alasan_batal' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        $adminId = $request->user()->id;

        DB::transaction(function () use (
            $transaksi,
            $validated,
            $adminId
        ) {
            // Kunci transaksi agar tidak dibatalkan bersamaan.
            $item = Transaksi::query()
                ->whereKey($transaksi->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Hanya transaksi lunas yang dapat dibatalkan.
            if ($item->status !== 'lunas') {
                throw ValidationException::withMessages([
                    'alasan_batal' =>
                        'Transaksi ini sudah dibatalkan atau tidak dapat dibatalkan.',
                ]);
            }

            // Ambil seluruh detail transaksi.
            $details = $item->detail()->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'alasan_batal' =>
                        'Detail transaksi tidak ditemukan. Pembatalan tidak dapat diproses.',
                ]);
            }

            // Kunci seluruh data garansi yang terbit dari transaksi.
            $garansis = Garansi::query()
                ->whereIn(
                    'detail_transaksi_id',
                    $details->pluck('id')
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Jangan batalkan transaksi jika klaim sudah pernah diajukan.
            $memilikiKlaim = $garansis->contains(
                fn (Garansi $garansi) =>
                    $garansi->status_klaim !== 'belum_ada'
                    || ! $garansi->is_valid
            );

            if ($memilikiKlaim) {
                throw ValidationException::withMessages([
                    'alasan_batal' =>
                        'Transaksi tidak dapat dibatalkan karena salah satu garansinya sudah memiliki pengajuan klaim atau tidak lagi valid.',
                ]);
            }

            // Kelompokkan jumlah barang berdasarkan produk.
            // Hal ini menghindari pengembalian stok berulang yang tidak perlu.
            $jumlahProduk = $details
                ->groupBy('produk_id')
                ->map(
                    fn ($baris) => (int) $baris->sum('jumlah')
                );

            // Kunci produk lalu kembalikan stok.
            foreach ($jumlahProduk as $produkId => $jumlah) {
                $produk = Produk::query()
                    ->whereKey($produkId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $produk->increment('stok', $jumlah);
            }

            // Garansi tetap tersimpan, tetapi tidak berlaku lagi.
            foreach ($garansis as $garansi) {
                $garansi->update([
                    'is_valid' => false,
                ]);
            }

            // Simpan status pembatalan dan identitas admin.
            $item->update([
                'status' => 'dibatalkan',
                'alasan_batal' => $validated['alasan_batal'],
                'dibatalkan_oleh' => $adminId,
            ]);
        }, 3);

        return redirect()
            ->route('kasir.riwayat')
            ->with(
                'success',
                'Transaksi berhasil dibatalkan. Stok telah dikembalikan dan garansi terkait dinyatakan tidak berlaku.'
            );
    }
}
