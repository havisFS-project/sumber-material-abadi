<?php

namespace App\Http\Controllers;

use App\Models\Garansi;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request
    ): Response|RedirectResponse {

        // Dashboard khusus admin.
        // Kasir diarahkan langsung ke halaman POS.
        if ($request->user()?->role !== 'admin') {
            return redirect()->route('kasir.index');
        }

        $now = now();

        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        // ==================================================
        // 1. OMZET DAN TRANSAKSI HARI INI
        // ==================================================

        // Omzet hanya menghitung transaksi lunas.
        $omzetHariIni = (int) Transaksi::query()
            ->where('status', 'lunas')
            ->whereDate('created_at', $today->toDateString())
            ->sum('total_bayar');

        $omzetKemarin = (int) Transaksi::query()
            ->where('status', 'lunas')
            ->whereDate('created_at', $yesterday->toDateString())
            ->sum('total_bayar');

        $persentaseOmzet = $omzetKemarin > 0
            ? round(
                (($omzetHariIni - $omzetKemarin) / $omzetKemarin) * 100,
                1
            )
            : null;

        // Jumlah seluruh transaksi hari ini.
        $transaksiHariIni = Transaksi::query()
            ->whereDate('created_at', $today->toDateString())
            ->count();

        // Jumlah transaksi yang dibatalkan hari ini.
        $transaksiDibatalkanHariIni = Transaksi::query()
            ->where('status', 'dibatalkan')
            ->whereDate('created_at', $today->toDateString())
            ->count();

        // ==================================================
        // 2. DATA PRODUK DAN STOK
        // ==================================================

        $produkAktif = Produk::query()
            ->where('is_active', true)
            ->count();

        // Ambang stok menipis: 5 unit atau kurang.
        $stokMenipis = Produk::query()
            ->where('is_active', true)
            ->where('stok', '<=', 5)
            ->count();

        // ==================================================
        // 3. KLAIM GARANSI YANG MENUNGGU TINDAKAN
        // ==================================================

        // Disetujui juga masih dihitung karena belum selesai.
        $statusKlaimMenunggu = [
            'diajukan',
            'diverifikasi',
            'disetujui',
        ];

        $jumlahKlaimMenunggu = Garansi::query()
            ->whereIn('status_klaim', $statusKlaimMenunggu)
            ->count();

        $klaimMenunggu = Garansi::query()
            ->with(['detail.transaksi'])
            ->whereIn('status_klaim', $statusKlaimMenunggu)
            ->latest('tanggal_klaim')
            ->take(3)
            ->get()
            ->map(fn (Garansi $item) => [
                'id' => $item->id,
                'no_garansi' => $item->no_garansi,

                'nama_produk' => $item->detail?->nama_produk
                    ?? 'Produk tidak tersedia',

                'nama_pelanggan' => $item->detail?->transaksi?->nama_pelanggan
                    ?? 'Pelanggan umum',

                'keluhan' => Str::limit(
                    $item->keluhan ?? 'Tidak ada keluhan.',
                    100
                ),

                'status' => $item->status_klaim,

                'tanggal' => $item->tanggal_klaim?->format(
                    'd/m/Y H:i'
                ),
            ]);

        // ==================================================
        // 4. GRAFIK OMZET TUJUH HARI TERAKHIR
        // ==================================================

        $tanggalMulaiGrafik = $today->copy()->subDays(6);

        $dataOmzet = Transaksi::query()
            ->selectRaw(
                'DATE(created_at) as tanggal, SUM(total_bayar) as omzet'
            )
            ->where('status', 'lunas')
            ->whereBetween('created_at', [
                $tanggalMulaiGrafik,
                $now,
            ])
            ->groupByRaw('DATE(created_at)')
            ->pluck('omzet', 'tanggal');

        // Isi semua tujuh hari, termasuk hari tanpa transaksi.
        $grafikOmzet = collect(range(6, 0))
            ->map(function (int $hari) use ($today, $dataOmzet) {
                $tanggal = $today->copy()->subDays($hari);

                return [
                    'label' => $tanggal
                        ->locale('id')
                        ->translatedFormat('D'),

                    'tanggal' => $tanggal->format('d/m'),

                    'omzet' => (int) $dataOmzet->get(
                        $tanggal->toDateString(),
                        0
                    ),
                ];
            })
            ->values();

        // ==================================================
        // 5. LIMA PRODUK TERLARIS TUJUH HARI TERAKHIR
        // ==================================================

        $produkTerlaris = DB::table('detail_transaksi as detail')
            ->join(
                'transaksi',
                'transaksi.id',
                '=',
                'detail.transaksi_id'
            )
            ->leftJoin(
                'produk',
                'produk.id',
                '=',
                'detail.produk_id'
            )
            ->where('transaksi.status', 'lunas')
            ->whereBetween('transaksi.created_at', [
                $tanggalMulaiGrafik,
                $now,
            ])
            ->select([
                'detail.produk_id',
                'detail.nama_produk as nama_snapshot',
                'produk.nama as nama_produk',
                'produk.satuan',
            ])
            ->selectRaw(
                'SUM(detail.jumlah) as jumlah_terjual'
            )
            ->groupBy(
                'detail.produk_id',
                'detail.nama_produk',
                'produk.nama',
                'produk.satuan'
            )
            ->orderByDesc('jumlah_terjual')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->produk_id,

                'nama' => $item->nama_produk
                    ?? $item->nama_snapshot,

                'jumlah' => (int) $item->jumlah_terjual,

                'satuan' => $item->satuan ?? 'unit',
            ]);

        // ==================================================
        // 6. DAFTAR STOK MENIPIS
        // ==================================================

        $daftarStokMenipis = Produk::query()
            ->with('kategori')
            ->where('is_active', true)
            ->where('stok', '<=', 5)
            ->orderBy('stok')
            ->orderBy('nama')
            ->take(5)
            ->get()
            ->map(fn (Produk $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'kategori' => $item->kategori?->nama ?? 'Umum',
                'stok' => (int) $item->stok,
                'satuan' => $item->satuan,
            ]);

        // ==================================================
        // 7. LIMA TRANSAKSI TERBARU
        // ==================================================

        $transaksiTerbaru = Transaksi::query()
            ->with('kasir:id,name')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (Transaksi $item) => [
                'id' => $item->id,
                'invoice' => $item->no_invoice,

                'kasir' => $item->kasir?->name ?? 'Tidak diketahui',

                'metode' => $item->metode_bayar,

                'total' => (int) $item->total_bayar,

                'status' => $item->status,

                'jam' => $item->created_at?->format('H:i'),
            ]);

        // ==================================================
        // 8. KIRIM DATA KE VUE
        // ==================================================

        return Inertia::render('Dashboard', [
            'tanggalHariIni' => $now
                ->copy()
                ->locale('id')
                ->translatedFormat('l, j F Y'),

            'stats' => [
                'omzet_hari_ini' => $omzetHariIni,
                'persentase_omzet' => $persentaseOmzet,

                'transaksi_hari_ini' => $transaksiHariIni,
                'transaksi_dibatalkan_hari_ini' => $transaksiDibatalkanHariIni,

                'produk_aktif' => $produkAktif,
                'stok_menipis' => $stokMenipis,

                'klaim_menunggu' => $jumlahKlaimMenunggu,
            ],

            'grafikOmzet' => $grafikOmzet,

            'produkTerlaris' => $produkTerlaris,

            'daftarStokMenipis' => $daftarStokMenipis,

            'transaksiTerbaru' => $transaksiTerbaru,

            'klaimMenunggu' => $klaimMenunggu,
        ]);
    }
}
