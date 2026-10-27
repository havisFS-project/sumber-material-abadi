<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProdukController extends Controller
{
    public function index(Request $request): Response
    {
        // Halaman ini hanya untuk admin.
        abort_unless(
            $request->user()?->role === 'admin',
            403
        );

        $search = trim((string) $request->query('q', ''));
        $kategoriId = $request->query('kategori');
        $status = (string) $request->query('status', 'semua');

        $query = Produk::query()->with('kategori');

        // Pencarian berdasarkan kode atau nama produk.
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan kategori.
        if (
            $kategoriId !== null
            && $kategoriId !== ''
            && is_numeric($kategoriId)
        ) {
            $query->where('kategori_id', $kategoriId);
        }

        // Filter berdasarkan status produk atau stok.
        if ($status === 'aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'nonaktif') {
            $query->where('is_active', false);
        } elseif ($status === 'stok_menipis') {
            $query->where('stok', '<=', 5);
        }

        $produk = $query
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Produk $item) => [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'satuan' => $item->satuan,
                'kategori' => $item->kategori?->nama,
                'harga_jual' => (int) $item->harga_jual,
                'harga_akhir' => (int) $item->harga_akhir,
                'potongan' => (int) $item->potongan,
                'diskon_aktif' => (bool) $item->diskon_aktif,
                'stok' => (int) $item->stok,
                'is_active' => (bool) $item->is_active,
                'punya_garansi' => (bool) $item->punya_garansi,
                'masa_garansi_bulan' => $item->masa_garansi_bulan,
            ]);

        return Inertia::render('admin/produk/Index', [
            'produk' => $produk,

            'kategoris' => Kategori::query()
                ->orderBy('nama')
                ->get(['id', 'nama']),

            'stats' => [
                'total' => Produk::count(),

                'aktif' => Produk::where(
                    'is_active',
                    true
                )->count(),

                'stok_menipis' => Produk::where(
                    'stok',
                    '<=',
                    5
                )->count(),

                'nonaktif' => Produk::where(
                    'is_active',
                    false
                )->count(),
            ],

            'filters' => [
                'q' => $search,
                'kategori' => $kategoriId,
                'status' => $status,
            ],
        ]);
    }

    private function authorizeAdmin(Request $request): void
{
    abort_unless(
        $request->user()?->role === 'admin',
        403
    );
}

private function produkRules(
    Request $request,
    ?Produk $produk = null
): array {
    $uniqueKode = Rule::unique('produk', 'kode');

    // Ketika edit, kode produk milik dirinya sendiri tetap boleh.
    if ($produk !== null) {
        $uniqueKode->ignore($produk->id);
    }

    $rules = [
        'kategori_id' => [
            'required',
            'integer',
            'exists:kategori,id',
        ],
        'kode' => [
            'required',
            'string',
            'max:50',
            $uniqueKode,
        ],
        'nama' => ['required', 'string', 'max:150'],
        'satuan' => ['required', 'string', 'max:30'],
        'deskripsi' => ['nullable', 'string', 'max:2000'],
        'harga_jual' => ['required', 'integer', 'min:0'],
        'stok' => ['required', 'integer', 'min:0'],
        'diskon_aktif' => ['required', 'boolean'],
        'punya_garansi' => ['required', 'boolean'],
        'is_active' => ['required', 'boolean'],
    ];

    if ($request->boolean('diskon_aktif')) {
        $rules['diskon_tipe'] = [
            'required',
            'in:persen,nominal',
        ];

        $rules['diskon_nilai'] = [
            'required',
            'numeric',
            'min:0',
        ];

        if ($request->input('diskon_tipe') === 'persen') {
            $rules['diskon_nilai'][] = 'max:100';
        }
    } else {
        $rules['diskon_tipe'] = ['nullable', 'in:persen,nominal'];
        $rules['diskon_nilai'] = ['nullable', 'numeric', 'min:0'];
    }

    if ($request->boolean('punya_garansi')) {
        $rules['masa_garansi_bulan'] = [
            'required',
            'integer',
            'min:1',
            'max:120',
        ];
    } else {
        $rules['masa_garansi_bulan'] = ['nullable', 'integer', 'min:1'];
    }

    return $rules;
}

public function create(Request $request): Response
{
    $this->authorizeAdmin($request);

    return Inertia::render('admin/produk/Form', [
        'mode' => 'create',
        'produk' => null,
        'kategoris' => Kategori::orderBy('nama')
            ->get(['id', 'nama']),
    ]);
}

public function store(Request $request): RedirectResponse
{
    $this->authorizeAdmin($request);

    $data = $request->validate(
        $this->produkRules($request)
    );

    // Normalisasi nilai checkbox.
    $data['diskon_aktif'] = $request->boolean('diskon_aktif');
    $data['punya_garansi'] = $request->boolean('punya_garansi');
    $data['is_active'] = $request->boolean('is_active');

    // Jika diskon tidak aktif, jangan terapkan diskon.
    if (! $data['diskon_aktif']) {
        $data['diskon_tipe'] = null;
        $data['diskon_nilai'] = 0;
    }

    // Jika tidak bergaransi, masa garansi menjadi kosong.
    if (! $data['punya_garansi']) {
        $data['masa_garansi_bulan'] = null;
    }

    Produk::create($data);

    return redirect()
        ->route('admin.produk.index')
        ->with('success', 'Produk berhasil ditambahkan.');
}

public function edit(
    Request $request,
    Produk $produk
): Response {
    $this->authorizeAdmin($request);

    return Inertia::render('admin/produk/Form', [
        'mode' => 'edit',
        'produk' => $produk->only([
            'id',
            'kategori_id',
            'kode',
            'nama',
            'satuan',
            'deskripsi',
            'harga_jual',
            'diskon_tipe',
            'diskon_nilai',
            'diskon_aktif',
            'stok',
            'punya_garansi',
            'masa_garansi_bulan',
            'is_active',
        ]),
        'kategoris' => Kategori::orderBy('nama')
            ->get(['id', 'nama']),
    ]);
}

public function update(
    Request $request,
    Produk $produk
): RedirectResponse {
    $this->authorizeAdmin($request);

    $data = $request->validate(
        $this->produkRules($request, $produk)
    );

    $data['diskon_aktif'] = $request->boolean('diskon_aktif');
    $data['punya_garansi'] = $request->boolean('punya_garansi');
    $data['is_active'] = $request->boolean('is_active');

    if (! $data['diskon_aktif']) {
        $data['diskon_tipe'] = null;
        $data['diskon_nilai'] = 0;
    }

    if (! $data['punya_garansi']) {
        $data['masa_garansi_bulan'] = null;
    }

    $produk->update($data);

    return redirect()
        ->route('admin.produk.index')
        ->with('success', 'Produk berhasil diperbarui.');
}

public function updateStatus(
    Request $request,
    Produk $produk
): RedirectResponse {
    // Pastikan hanya admin yang boleh mengubah status.
    $this->authorizeAdmin($request);

    // Validasi status yang dikirim dari frontend.
    $validated = $request->validate([
        'is_active' => ['required', 'boolean'],
    ]);

    // Simpan status baru ke database.
    $isActive = $request->boolean('is_active');

    $produk->update([
        'is_active' => $isActive,
    ]);

    // Kirim pesan sesuai hasil perubahan.
    return redirect()
        ->route('admin.produk.index')
        ->with(
            'success',
            $isActive
                ? 'Produk berhasil diaktifkan.'
                : 'Produk berhasil dinonaktifkan.'
        );
}
}