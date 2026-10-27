<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response; 

class KategoriController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403
        );
    }

    private function makeUniqueSlug(
        string $nama,
        ?int $ignoreId = null
    ): string {
        $baseSlug = Str::slug($nama);

        if ($baseSlug === '') {
            $baseSlug = 'kategori';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (
            Kategori::query()
                ->where('slug', $slug)
                ->when(
                    $ignoreId !== null,
                    fn ($query) => $query->where('id', '!=', $ignoreId)
                )
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->query('q', ''));

        $kategori = Kategori::query()
            ->withCount('produk')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/kategori/Index', [
            'kategori' => $kategori,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeAdmin($request);

        return Inertia::render('admin/kategori/Form', [
            'mode' => 'create',
            'kategori' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama'),
            ],
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['nama']);

        Kategori::create($data);

        return redirect()
            ->route('admin.kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(
        Request $request,
        Kategori $kategori
    ): Response {
        $this->authorizeAdmin($request);

        return Inertia::render('admin/kategori/Form', [
            'mode' => 'edit',
            'kategori' => $kategori->only([
                'id',
                'nama',
                'slug',
            ]),
        ]);
    }

    public function update(
        Request $request,
        Kategori $kategori
    ): RedirectResponse {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori', 'nama')
                    ->ignore($kategori->id),
            ],
        ]);

        $data['slug'] = $this->makeUniqueSlug(
            $data['nama'],
            $kategori->id
        );

        $kategori->update($data);

        return redirect()
            ->route('admin.kategori.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }
}
