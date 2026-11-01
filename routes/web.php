<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KasirTransaksiController;
use App\Http\Controllers\GaransiController;
use App\Http\Controllers\KatalogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\KlaimGaransiController;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
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
        // Manajemen kategori.
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

        Route::get('/klaim-garansi', [
            KlaimGaransiController::class,
            'index',
        ])->name('klaim-garansi.index');

        Route::patch('/klaim-garansi/{garansi}/status', [
            KlaimGaransiController::class,
            'updateStatus',
        ])->name('klaim-garansi.status');

        Route::patch(
            '/transaksi/{transaksi}/void',
            [KasirTransaksiController::class, 'void']
        )->name('transaksi.void');
            });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::get('/katalog', [
    KatalogController::class,
    'index',
])->name('katalog.index');

Route::get('/kasir', [KasirController::class, 'index'])
    ->middleware('auth')
    ->name('kasir.index');

Route::post('/kasir/checkout', [KasirController::class, 'store'])
    ->middleware('auth')
    ->name('kasir.checkout');

Route::get(
    '/kasir/riwayat',
    [KasirTransaksiController::class, 'index']
)->middleware('auth')->name('kasir.riwayat');

Route::get(
    '/kasir/struk/{invoice}',
    [KasirTransaksiController::class, 'struk']
)->middleware('auth')->name('kasir.struk');

Route::get('/garansi', [GaransiController::class, 'index'])
    ->name('garansi.index');
    
Route::post('/garansi/klaim', [GaransiController::class, 'storeClaim'])
    ->middleware('throttle:5,1')
    ->name('garansi.klaim');
    
require __DIR__.'/settings.php';
