<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Semen', 'Besi', 'Cat', 'Pipa', 'Keramik', 'Toren Penguin', 'Perkakas', 'Material Dasar'] as $nama) {
            Kategori::updateOrCreate(['slug' => Str::slug($nama)], ['nama' => $nama]);
        }
    }
}
