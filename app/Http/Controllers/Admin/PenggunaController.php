<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    /**
     * Menampilkan daftar akun kasir.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $users = User::query()
            ->where('role', 'kasir')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/pengguna/Index', [
            'users' => $users,

            'filters' => [
                'search' => $search,
            ],

            'summary' => [
                'total' => User::where('role', 'kasir')->count(),
                'active' => User::where('role', 'kasir')
                    ->where('is_active', true)
                    ->count(),
                'inactive' => User::where('role', 'kasir')
                    ->where('is_active', false)
                    ->count(),
            ],
        ]);
    }

    /**
     * Membuat akun kasir baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'kasir',
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.pengguna.index')
            ->with('success', 'Akun kasir berhasil dibuat.');
    }

    /**
     * Mengaktifkan atau menonaktifkan akun kasir.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Pastikan yang diubah hanya akun kasir.
        abort_unless($user->role === 'kasir', 404);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $message = $user->is_active
            ? 'Akun kasir berhasil diaktifkan.'
            : 'Akun kasir berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }
}