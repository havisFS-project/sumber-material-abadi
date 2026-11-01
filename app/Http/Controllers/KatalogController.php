<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'integer', 'exists:kategori,id'],
        ]);

        $search = trim($validated['q'] ?? '');
        $kategoriId = $validated['kategori'] ?? null;

        // Produk publik hanya yang aktif.
        $query = Produk::query()
            ->with('kategori')
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($kategoriId !== null) {
            $query->where('kategori_id', $kategoriId);
        }

        $produk = $query
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Produk $item) => [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'satuan' => $item->satuan,
                'deskripsi' => $item->deskripsi,
                'kategori' => $item->kategori?->nama,

                'harga_jual' => (int) $item->harga_jual,
                'harga_akhir' => (int) $item->harga_akhir,
                'potongan' => (int) $item->potongan,

                'diskon_aktif' => (bool) $item->diskon_aktif,
                'stok' => (int) $item->stok,

                'punya_garansi' => (bool) $item->punya_garansi,
                'masa_garansi_bulan' => $item->masa_garansi_bulan,

                'foto_url' => $item->foto
                    ? asset('storage/' . ltrim($item->foto, '/'))
                    : null,
            ]);

        // Produk promo untuk bagian hero.
        $promoProduk = Produk::query()
            ->where('is_active', true)
            ->where('diskon_aktif', true)
            ->where('diskon_nilai', '>', 0)
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->filter(fn (Produk $item) => $item->potongan > 0)
            ->values()
            ->map(fn (Produk $item) => [
                'nama' => $item->nama,
                'harga_akhir' => (int) $item->harga_akhir,
                'diskon_persen' => $item->harga_jual > 0
                    ? (int) round(
                        ($item->potongan / $item->harga_jual) * 100
                    )
                    : 0,
            ]);

        $kategoris = Kategori::query()
            ->whereHas('produk', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('nama')
            ->get(['id', 'nama']);

        // Informasi kontak dan lokasi toko.
        $alamat = (string) config('services.store.address', '');
        $mapsUrl = (string) config('services.store.maps_url', '');

        // Jika tautan Maps belum diberikan, gunakan pencarian alamat.
        if ($mapsUrl === '' && $alamat !== '') {
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query='
                . urlencode($alamat);
        }

        $whatsappToko = preg_replace(
            '/\D+/',
            '',
            (string) config('services.whatsapp.toko', '')
        );

        return Inertia::render('katalog/Index', [
            'produk' => $produk,
            'kategoris' => $kategoris,
            'promoProduk' => $promoProduk,

            'filters' => [
                'q' => $search,
                'kategori' => $kategoriId
                    ? (string) $kategoriId
                    : '',
            ],

            'totalProduk' => Produk::where(
                'is_active',
                true
            )->count(),

            'store' => [
                'nama' => config(
                    'app.name',
                    'Sumber Material Abadi'
                ),
                'alamat' => $alamat,
                'maps_url' => $mapsUrl,
                'whatsapp' => $whatsappToko,
                'weekday_hours' => config(
                    'services.store.weekday_hours'
                ),
                'sunday_hours' => config(
                    'services.store.sunday_hours'
                ),
            ],
        ]);
    }
}
