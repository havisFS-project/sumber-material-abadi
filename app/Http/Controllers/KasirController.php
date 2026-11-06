<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\DetailTransaksi;
use App\Models\Transaksi;
use App\Services\GaransiTransaksiService;
use App\Services\MidtransService;
use App\Services\TransaksiPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class KasirController extends Controller
{
    /**
     * Admin dan kasir boleh mengakses POS.
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
     * Menampilkan halaman POS.
     */
    public function index(Request $request): Response
    {
        $this->authorizeKasir($request);

        $search = trim((string) $request->query('q', ''));

        $produk = Produk::query()
            ->aktif()
            ->with('kategori')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama')
            ->limit(60)
            ->get()
            ->map(fn (Produk $item) => [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'kategori' => $item->kategori?->nama,
                'satuan' => $item->satuan,
                'harga_jual' => (int) $item->harga_jual,
                'harga_akhir' => (int) $item->harga_akhir,
                'potongan' => (int) $item->potongan,
                'diskon_aktif' => (bool) $item->diskon_aktif,
                'stok' => (int) $item->stok,
                'punya_garansi' => (bool) $item->punya_garansi,
                'masa_garansi_bulan' => $item->masa_garansi_bulan,
            ]);

        return Inertia::render('kasir/Index', [
            'produk' => $produk,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    
    /**
     * Menampilkan halaman pembayaran QRIS.
     */
    public function payment(
        Request $request,
        string $invoice
    ): Response {
        $this->authorizeKasir($request);

        $user = $request->user();

        $query = Transaksi::query()
            ->where('no_invoice', $invoice);

        // Kasir hanya boleh membuka pembayaran miliknya.
        // Admin dapat membuka seluruh transaksi.
        if ($user->role === 'kasir') {
            $query->where('kasir_id', $user->id);
        }

        $transaksi = $query->firstOrFail();

        abort_unless($transaksi->metode_bayar === 'qris', 404);

        // Kirim props secara langsung agar sesuai dengan Pembayaran.vue.
        return Inertia::render('kasir/Pembayaran', [
            'id' => $transaksi->id,
            'no_invoice' => $transaksi->no_invoice,
            'total_bayar' => (int) $transaksi->total_bayar,
            'status' => $transaksi->status,
            'qris_url' => $transaksi->qris_url,
            'payment_expires_at' => $transaksi->payment_expires_at
                ? $transaksi->payment_expires_at->toIso8601String()
                : null,
        ]);
    }
 
    /**
     * Mengambil status terbaru pembayaran QRIS.
     */
    public function paymentStatus(
        Request $request,
        string $invoice
    ): JsonResponse {
        $this->authorizeKasir($request);

        $user = $request->user();

        $query = Transaksi::query()
            ->where('no_invoice', $invoice)
            ->where('metode_bayar', 'qris');

        // Kasir hanya dapat memeriksa transaksi miliknya.
        // Admin dapat memeriksa seluruh transaksi.
        if ($user->role === 'kasir') {
            $query->where('kasir_id', $user->id);
        }

        $transaksi = $query->firstOrFail();

        return response()->json([
            'no_invoice' => $transaksi->no_invoice,
            'status' => $transaksi->status,
            'payment_expires_at' => $transaksi->payment_expires_at
                ? $transaksi->payment_expires_at->toIso8601String()
                : null,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Memproses checkout tunai dan QRIS.
     */
    public function store(
        Request $request,
        MidtransService $midtrans,
        TransaksiPaymentService $paymentService,
        GaransiTransaksiService $garansiService
    ): RedirectResponse {
        $this->authorizeKasir($request);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],

            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                'exists:produk,id',
            ],

            'items.*.jumlah' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            'metode_bayar' => [
                'required',
                'in:tunai,qris',
            ],

            'uang_diterima' => [
                'nullable',
                'required_if:metode_bayar,tunai',
                'integer',
                'min:0',
            ],

            'nama_pelanggan' => [
                'nullable',
                'string',
                'max:255',
            ],

            'no_hp_pelanggan' => [
                'nullable',
                'string',
                'max:30',
            ],
        ]);

        $kasirId = $request->user()->id;

        // Invoice unik untuk setiap percobaan transaksi.
        $invoice = 'SMA-'
            . now()->format('Ymd-His')
            . '-'
            . Str::upper(Str::random(6));

        /*
        |--------------------------------------------------------------------------
        | 1. Membuat transaksi dan reservasi stok
        |--------------------------------------------------------------------------
        |
        | Tunai:
        | - Status langsung lunas.
        | - Stok dikurangi.
        | - Garansi langsung diterbitkan.
        |
        | QRIS:
        | - Status menunggu_pembayaran.
        | - Stok direservasi dengan pengurangan stok.
        | - Garansi belum diterbitkan.
        |
        */

        $transaksi = DB::transaction(function () use (
            $validated,
            $invoice,
            $kasirId,
            $garansiService
        ): Transaksi {

            $items = $validated['items'];

            // Kunci produk selama proses checkout.
            $produkList = Produk::query()
                ->whereIn('id', array_column($items, 'id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($produkList->count() !== count($items)) {
                throw ValidationException::withMessages([
                    'items' => 'Sebagian produk tidak lagi tersedia.',
                ]);
            }

            $lines = [];
            $subtotal = 0;
            $totalDiskon = 0;
            $totalBayar = 0;
            $adaGaransi = false;

            foreach ($items as $input) {
                $produk = $produkList->get((int) $input['id']);

                $jumlah = (int) $input['jumlah'];

                if (! $produk || ! $produk->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada produk yang sudah tidak aktif.',
                    ]);
                }

                if ($jumlah > (int) $produk->stok) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$produk->nama} tidak mencukupi. "
                            . "Stok tersedia: {$produk->stok}.",
                    ]);
                }

                $hargaSatuan = (int) $produk->harga_jual;
                $diskonSatuan = (int) $produk->potongan;
                $hargaAkhir = (int) $produk->harga_akhir;

                $lineSubtotal = $hargaAkhir * $jumlah;

                $subtotal += $hargaSatuan * $jumlah;
                $totalDiskon += $diskonSatuan * $jumlah;
                $totalBayar += $lineSubtotal;

                if ($produk->punya_garansi) {
                    $adaGaransi = true;
                }

                $lines[] = [
                    'produk' => $produk,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $hargaSatuan,
                    'diskon_satuan' => $diskonSatuan,
                    'subtotal' => $lineSubtotal,
                ];
            }

            // Jika ada produk bergaransi, identitas pelanggan wajib.
            if ($adaGaransi) {
                if (
                    blank($validated['nama_pelanggan'] ?? null)
                    || blank($validated['no_hp_pelanggan'] ?? null)
                ) {
                    throw ValidationException::withMessages([
                        'nama_pelanggan' =>
                            'Nama dan nomor HP pelanggan wajib diisi karena terdapat produk bergaransi.',
                    ]);
                }
            }

            // Batas nominal unsignedInteger MySQL.
            $batasNominal = 4_294_967_295;

            if (
                $subtotal > $batasNominal
                || $totalDiskon > $batasNominal
                || $totalBayar > $batasNominal
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Total transaksi melebihi batas nominal yang didukung.',
                ]);
            }

            $metodeBayar = $validated['metode_bayar'];

            // QRIS tidak dapat memproses nominal nol.
            if ($metodeBayar === 'qris' && $totalBayar < 1) {
                throw ValidationException::withMessages([
                    'metode_bayar' => 'Total pembayaran QRIS harus lebih dari nol.',
                ]);
            }

            $uangDiterima = $metodeBayar === 'tunai'
                ? (int) $validated['uang_diterima']
                : null;

            if (
                $metodeBayar === 'tunai'
                && $uangDiterima < $totalBayar
            ) {
                throw ValidationException::withMessages([
                    'uang_diterima' => 'Uang diterima kurang dari total pembayaran.',
                ]);
            }

            // Tentukan status sesuai metode pembayaran.
            $status = $metodeBayar === 'tunai'
                ? 'lunas'
                : 'menunggu_pembayaran';

            $transaksi = Transaksi::create([
                'no_invoice' => $invoice,
                'kasir_id' => $kasirId,

                'nama_pelanggan' => $validated['nama_pelanggan'] ?? null,
                'no_hp_pelanggan' => $validated['no_hp_pelanggan'] ?? null,

                'subtotal' => $subtotal,
                'total_diskon' => $totalDiskon,
                'total_bayar' => $totalBayar,

                'metode_bayar' => $metodeBayar,

                'uang_diterima' => $uangDiterima,

                'kembalian' => $uangDiterima !== null
                    ? $uangDiterima - $totalBayar
                    : null,

                'status' => $status,
            ]);

            // Simpan detail transaksi dan reservasi stok.
            foreach ($lines as $line) {
                $produk = $line['produk'];

                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $produk->id,
                    'nama_produk' => $produk->nama,
                    'harga_satuan' => $line['harga_satuan'],
                    'diskon_satuan' => $line['diskon_satuan'],
                    'jumlah' => $line['jumlah'],
                    'subtotal' => $line['subtotal'],
                ]);

                // Stok dikurangi untuk transaksi tunai maupun QRIS.
                // Pada QRIS, ini merupakan reservasi stok.
                $produk->decrement('stok', $line['jumlah']);
            }

            // Tunai sudah dibayar, sehingga garansi boleh diterbitkan.
            if ($metodeBayar === 'tunai') {
                $garansiService->terbitkan($transaksi);
            }

            return $transaksi;

        }, 3);

        /*
        |--------------------------------------------------------------------------
        | 2. Jika pembayaran tunai, selesai
        |--------------------------------------------------------------------------
        */

        if ($transaksi->metode_bayar === 'tunai') {
            return redirect()
                ->route('kasir.index')
                ->with(
                    'success',
                    "Transaksi {$transaksi->no_invoice} berhasil disimpan."
                );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Jika QRIS, buat charge ke Midtrans
        |--------------------------------------------------------------------------
        */

        try {
            $qrisData = $midtrans->createQrisCharge($transaksi);

                } catch (Throwable $exception) {

            Log::error('Gagal membuat QRIS Midtrans.', [
                'order_id' => $transaksi->no_invoice,
                'error' => $exception->getMessage(),
            ]);

            // Periksa apakah Midtrans sebenarnya sudah membuat transaksi.
            $remoteStatus = null;

            try {
                $remoteStatus = $midtrans->getTransactionStatus(
                    $transaksi->no_invoice
                );
            } catch (Throwable $statusException) {
                Log::critical('Status transaksi QRIS belum dapat dipastikan.', [
                    'order_id' => $transaksi->no_invoice,
                    'error' => $statusException->getMessage(),
                ]);
            }

            $remoteState = strtolower(
                (string) ($remoteStatus['transaction_status'] ?? '')
            );

            if ($remoteState === 'settlement') {
                $paymentService->markAsPaid($transaksi->no_invoice);
            } elseif ($remoteState === 'expire') {
                $paymentService->releaseReservation(
                    $transaksi->no_invoice,
                    'kedaluwarsa'
                );
            } elseif (in_array($remoteState, ['deny', 'cancel'], true)) {
                $paymentService->releaseReservation(
                    $transaksi->no_invoice,
                    'gagal'
                );
            }

            // Jika status tidak diketahui atau masih pending,
            // jangan kembalikan stok secara otomatis.
            return redirect()
                ->route('kasir.index')
                ->withErrors([
                    'metode_bayar' =>
                        "Status QRIS invoice {$transaksi->no_invoice} belum dapat dipastikan. Jangan ulangi checkout untuk transaksi ini sebelum statusnya diperiksa.",
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Simpan data QRIS dari Midtrans
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $transaksi,
            $qrisData
        ) {

            $item = Transaksi::query()
                ->whereKey($transaksi->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan transaksi masih menunggu pembayaran.
            if ($item->status !== 'menunggu_pembayaran') {
                throw new \RuntimeException(
                    'Status transaksi berubah sebelum data QRIS disimpan.'
                );
            }

            if (
                $qrisData['order_id'] !== $item->no_invoice
                || blank($qrisData['transaction_id'])
                || blank($qrisData['qris_url'])
            ) {
                throw new \RuntimeException(
                    'Data QRIS dari Midtrans tidak sesuai dengan transaksi.'
                );
            }

            $item->update([
                'midtrans_transaction_id' => $qrisData['transaction_id'],
                'qris_url' => $qrisData['qris_url'],
                'payment_expires_at' => $qrisData['payment_expires_at'],
            ]);

        }, 3);

        /*
        |--------------------------------------------------------------------------
        | 5. Arahkan kasir ke halaman pembayaran QRIS
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('kasir.pembayaran', $transaksi->no_invoice)
            ->with(
                'success',
                'QRIS berhasil dibuat. Silakan tunggu pembayaran pelanggan.'
            );
    }
}
