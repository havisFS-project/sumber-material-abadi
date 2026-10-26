<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('satuan', 20)->default('pcs');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('harga_jual');
            $table->enum('diskon_tipe', ['persen', 'nominal'])->nullable();
            $table->unsignedInteger('diskon_nilai')->default(0);
            $table->boolean('diskon_aktif')->default(false);
            $table->unsignedInteger('stok')->default(0);
            $table->boolean('punya_garansi')->default(false);
            $table->unsignedTinyInteger('masa_garansi_bulan')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
