<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice')->unique();
            $table->foreignId('kasir_id')->constrained('users')->restrictOnDelete();
            $table->string('nama_pelanggan')->nullable();
            $table->string('no_hp_pelanggan', 30)->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('total_diskon')->default(0);
            $table->unsignedInteger('total_bayar');
            $table->enum('metode_bayar', ['tunai', 'qris']);
            $table->unsignedInteger('uang_diterima')->nullable();
            $table->unsignedInteger('kembalian')->nullable();
            $table->enum('status', ['lunas', 'dibatalkan'])->default('lunas');
            $table->text('alasan_batal')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
