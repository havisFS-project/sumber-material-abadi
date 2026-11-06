<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\KlaimGaransiController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KasirTransaksiController;
use App\Http\Controllers\GaransiController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\MidtransWebhookController;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Utama
|--------------------------------------------------------------------------
*/

Route::get('/', function (Request $request) {
    $user = $request->user();

    if ($user instanceof User) {
        if ($user->role === 'admin') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('kasir.index');
    }

    return Inertia::render('Welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Manajemen Produk.
        Route::get('/produk', [ProdukController::class, 'index'])
            ->name('produk.index');

        Route::get('/produk/create', [ProdukController::class, 'create'])
            ->name('produk.create');

        Route::post('/produk', [ProdukController::class, 'store'])
            ->name('produk.store');

        Route::get('/produk/{produk}/edit', [ProdukController::class, 'edit'])
            ->name('produk.edit');

        Route::put('/produk/{produk}', [ProdukController::class, 'update'])
            ->name('produk.update');

        Route::patch('/produk/{produk}/status', [ProdukController::class, 'updateStatus'])
            ->name('produk.status');


        // Manajemen Kategori.
        Route::get('/kategori', [KategoriController::class, 'index'])
            ->name('kategori.index');

        Route::get('/kategori/create', [KategoriController::class, 'create'])
            ->name('kategori.create');

        Route::post('/kategori', [KategoriController::class, 'store'])
            ->name('kategori.store');

        Route::get('/kategori/{kategori}/edit', [KategoriController::class, 'edit'])
            ->name('kategori.edit');

        Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])
            ->name('kategori.update');


        // Manajemen Klaim Garansi.
        Route::get('/klaim-garansi', [KlaimGaransiController::class, 'index'])
            ->name('klaim-garansi.index');

        Route::patch('/klaim-garansi/{garansi}/status', [KlaimGaransiController::class, 'updateStatus'])
            ->name('klaim-garansi.status');


        // Pembatalan Transaksi.
        Route::patch('/transaksi/{transaksi}/void', [KasirTransaksiController::class, 'void'])
            ->name('transaksi.void');


        // Manajemen Pengguna Kasir.
        Route::get('/pengguna', [PenggunaController::class, 'index'])
            ->name('pengguna.index');

        Route::post('/pengguna', [PenggunaController::class, 'store'])
            ->name('pengguna.store');

        Route::patch('/pengguna/{user}/status', [PenggunaController::class, 'toggleStatus'])
            ->name('pengguna.status');
    });


/*
|--------------------------------------------------------------------------
| Team Invitations
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {

    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])
        ->name('invitations.accept');

    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])
        ->name('invitations.decline');
});


/*
|--------------------------------------------------------------------------
| Katalog Publik
|--------------------------------------------------------------------------
*/

Route::get('/katalog', [KatalogController::class, 'index'])
    ->name('katalog.index');


/*
|--------------------------------------------------------------------------
| Kasir Routes
|--------------------------------------------------------------------------
*/

Route::get('/kasir', [KasirController::class, 'index'])
    ->middleware(['auth', 'active'])
    ->name('kasir.index');

Route::post('/kasir/checkout', [KasirController::class, 'store'])
    ->middleware(['auth', 'active'])
    ->name('kasir.checkout');

Route::get(
        '/kasir/pembayaran/{invoice}',
        [KasirController::class, 'payment']
    )->middleware(['auth', 'active'])
    ->name('kasir.pembayaran');

Route::get(
        '/kasir/pembayaran/{invoice}/status',
        [KasirController::class, 'paymentStatus']
    )->middleware(['auth', 'active'])
    ->name('kasir.pembayaran.status');

Route::get('/kasir/riwayat', [KasirTransaksiController::class, 'index'])
    ->middleware(['auth', 'active'])
    ->name('kasir.riwayat');

Route::get('/kasir/struk/{invoice}', [KasirTransaksiController::class, 'struk'])
    ->middleware(['auth', 'active'])
    ->name('kasir.struk');


/*
|--------------------------------------------------------------------------
| Garansi Publik
|--------------------------------------------------------------------------
*/

Route::get('/garansi', [GaransiController::class, 'index'])
    ->name('garansi.index');

Route::post('/garansi/klaim', [GaransiController::class, 'storeClaim'])
    ->middleware('throttle:5,1')
    ->name('garansi.klaim');

/*
|--------------------------------------------------------------------------
| Midtrans Webhook
|--------------------------------------------------------------------------
*/

Route::post('/midtrans/notification', [
    MidtransWebhookController::class,
    'handle',
])->name('midtrans.notification');

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';
