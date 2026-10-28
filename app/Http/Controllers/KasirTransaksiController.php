<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class KasirTransaksiController extends Controller
{
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

        // Pencarian berdasarkan invoice atau nama pelanggan.
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
            ]);

        return Inertia::render('kasir/Riwayat', [
            'transaksi' => $transaksi,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

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

        // Sembunyikan transaksi kasir lain dari pengguna kasir.
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
                                    ? Carbon::parse($detail->garansi->tanggal_mulai)->format('d/m/Y')
                                    : null,

                                'tanggal_berakhir' => $detail->garansi->tanggal_berakhir
                                    ? Carbon::parse($detail->garansi->tanggal_berakhir)->format('d/m/Y')
                                    : null,
                            ]
                            : null,
                    ]
                )->values(),
            ],
        ]);
    }
}
