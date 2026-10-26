<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        // [kode, nama, kategori, satuan, harga_jual, stok, diskon_persen (atau null), masa_garansi_bulan (atau null)]
        $data = [
            ['SMN-001', 'Semen Tiga Roda 50 kg', 'Semen', 'sak', 68000, 120, null, null],
            ['SMN-002', 'Semen Gresik 40 kg', 'Semen', 'sak', 56000, 86, null, null],
            ['BSI-001', 'Besi Beton 10 mm (12 m)', 'Besi', 'batang', 89000, 64, null, null],
            ['BSI-002', 'Besi Hollow 4x4 cm', 'Besi', 'batang', 42000, 40, null, null],
            ['BSI-003', 'Paku 5 cm (1 kg)', 'Besi', 'kg', 24000, 75, null, null],
            ['BSI-004', 'Kawat Bendrat (1 kg)', 'Besi', 'kg', 21000, 58, null, null],
            ['CAT-001', 'Cat Tembok Dulux 5 kg', 'Cat', 'kaleng', 220000, 18, 15, null],
            ['CAT-002', 'Cat Kayu Avian 1 kg', 'Cat', 'kaleng', 62000, 27, null, null],
            ['CAT-003', 'Kuas Cat 3 inci', 'Cat', 'pcs', 12000, 90, null, null],
            ['PIP-001', 'Pipa PVC 3 inci Rucika', 'Pipa', 'batang', 105000, 32, 10, null],
            ['PIP-002', 'Lem Pipa PVC 100 g', 'Pipa', 'tube', 15000, 110, null, null],
            ['KRM-001', 'Keramik 40x40 (per dus)', 'Keramik', 'dus', 150000, 44, 10, null],
            ['TRN-500', 'Toren Penguin 500 L', 'Toren Penguin', 'unit', 1250000, 9, null, 24],
            ['TRN-1000', 'Toren Penguin 1000 L', 'Toren Penguin', 'unit', 2300000, 5, 5, 24],
            ['PKR-001', 'Pompa Air Shimizu PS-128', 'Perkakas', 'unit', 485000, 7, null, 12],
            ['PKR-002', 'Gerinda Tangan 4 inci', 'Perkakas', 'unit', 365000, 3, null, 6],
            ['PKR-003', 'Engsel Pintu 4 inci', 'Perkakas', 'pcs', 16000, 130, null, null],
            ['PKR-004', 'Cangkul Baja', 'Perkakas', 'pcs', 78000, 0, null, null],
            ['MTR-001', 'Pasir Cor (1 karung)', 'Material Dasar', 'karung', 18000, 200, null, null],
            ['MTR-002', 'Bata Merah (per biji)', 'Material Dasar', 'biji', 1100, 1200, null, null],
        ];

        foreach ($data as [$kode, $nama, $kategori, $satuan, $harga, $stok, $diskon, $garansi]) {
            Produk::updateOrCreate(['kode' => $kode], [
                'kategori_id' => Kategori::where('slug', Str::slug($kategori))->value('id'),
                'nama' => $nama,
                'satuan' => $satuan,
                'harga_jual' => $harga,
                'stok' => $stok,
                'diskon_tipe' => $diskon ? 'persen' : null,
                'diskon_nilai' => $diskon ?? 0,
                'diskon_aktif' => (bool) $diskon,
                'punya_garansi' => (bool) $garansi,
                'masa_garansi_bulan' => $garansi,
                'is_active' => true,
            ]);
        }
    }
}
