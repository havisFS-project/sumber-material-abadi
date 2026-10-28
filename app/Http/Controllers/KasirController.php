<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\DetailTransaksi;
use App\Models\Transaksi;
use App\Models\Garansi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KasirController extends Controller
{
    public function index(Request $request): Response
    {
        // POS hanya boleh diakses admin dan kasir.
        abort_unless(
            in_array(
                $request->user()?->role,
                ['admin', 'kasir'],
                true
            ),
            403
        );

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

    
    public function store(Request $request): RedirectResponse
    {
        // Hanya admin dan kasir yang boleh melakukan checkout.
        abort_unless(
            in_array(
                $request->user()?->role,
                ['admin', 'kasir'],
                true
            ),
            403
        );

        // Validasi bentuk request sebelum memproses transaksi.
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
            'metode_bayar' => ['required', 'in:tunai,qris'],
            'uang_diterima' => [
                'nullable',
                'required_if:metode_bayar,tunai',
                'integer',
                'min:0',
            ],
            'nama_pelanggan' => ['nullable', 'string', 'max:255'],
            'no_hp_pelanggan' => ['nullable', 'string', 'max:30'],
        ]);

        $kasirId = $request->user()->id;

        // Invoice memiliki bagian acak untuk mengurangi risiko duplikasi.
        $invoice = 'SMA-'
            . now()->format('Ymd-His')
            . '-'
            . Str::upper(Str::random(6));

        $transaksi = DB::transaction(function () use (
            $validated,
            $invoice,
            $kasirId
        ): Transaksi {
            $items = $validated['items'];

            // Kunci baris produk selama checkout agar stok tidak
            // diubah bersamaan oleh transaksi lain.
            $produkList = Produk::query()
                ->whereIn('id', array_column($items, 'id'))
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

            // Seluruh harga dihitung ulang dari database,
            // bukan memakai total dari browser.
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

                $lines[] = [
                    'produk' => $produk,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $hargaSatuan,
                    'diskon_satuan' => $diskonSatuan,
                    'subtotal' => $lineSubtotal,
                ];
            }

            // Kolom nominal pada migration menggunakan unsignedInteger.
            // Batas maksimum integer unsigned MySQL adalah 4.294.967.295.
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

            // Simpan header transaksi.
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
                'status' => 'lunas',
            ]);

            // Simpan rincian barang dan kurangi stok.
            
            foreach ($lines as $line) {
                $produk = $line['produk'];

                // Simpan rincian barang yang dibeli.
                $detail = DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $produk->id,
                    'nama_produk' => $produk->nama,
                    'harga_satuan' => $line['harga_satuan'],
                    'diskon_satuan' => $line['diskon_satuan'],
                    'jumlah' => $line['jumlah'],
                    'subtotal' => $line['subtotal'],
                ]);

                // Buat garansi apabila produk memilikinya.
                if ($produk->punya_garansi) {
                    $masaGaransi = (int) $produk->masa_garansi_bulan;

                    // Produk bergaransi harus mempunyai masa berlaku.
                    if ($masaGaransi < 1 || $masaGaransi > 120) {
                        throw ValidationException::withMessages([
                            'items' => "Masa garansi produk {$produk->nama} belum valid.",
                        ]);
                    }

                    $tanggalMulai = now()->startOfDay();

                    // Buat nomor garansi unik.
                    do {
                        $nomorGaransi = 'GAR-'
                            . $tanggalMulai->format('Ymd')
                            . '-'
                            . Str::upper(Str::random(8));
                    } while (
                        Garansi::where('no_garansi', $nomorGaransi)->exists()
                    );

                    Garansi::create([
                        'no_garansi' => $nomorGaransi,
                        'detail_transaksi_id' => $detail->id,
                        'tanggal_mulai' => $tanggalMulai->toDateString(),
                        'tanggal_berakhir' => $tanggalMulai
                            ->copy()
                            ->addMonthsNoOverflow($masaGaransi)
                            ->toDateString(),
                        'status_klaim' => 'belum_ada',
                    ]);
                }

                // Kurangi stok dalam transaksi database yang sama.
                $produk->decrement('stok', $line['jumlah']);
            }


            return $transaksi;
        }, 3);

        return redirect()
            ->route('kasir.index')
            ->with(
                'success',
                "Transaksi {$transaksi->no_invoice} berhasil disimpan."
            );
    }

}
