<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garansi', function (Blueprint $table) {
            $table->id();
            $table->string('no_garansi')->unique();
            $table->foreignId('detail_transaksi_id')->unique()->constrained('detail_transaksi')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->enum('status_klaim', ['belum_ada', 'diajukan', 'diverifikasi', 'disetujui', 'ditolak', 'selesai'])->default('belum_ada');
            $table->text('keluhan')->nullable();
            $table->string('foto_klaim')->nullable();
            $table->dateTime('tanggal_klaim')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garansi');
    }
};
