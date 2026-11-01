<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_klaim_garansi', function (Blueprint $table) {
            $table->id();

            // Garansi yang memiliki aktivitas ini.
            $table->foreignId('garansi_id')
                ->constrained('garansi')
                ->cascadeOnDelete();

            // Jenis aktivitas: pengajuan, verifikasi,
            // keputusan, atau penyelesaian.
            $table->string('jenis_aksi', 30);

            // Pihak yang melakukan aktivitas.
            $table->string('aktor', 20);

            // ID user kosong jika aktivitas berasal dari pelanggan publik.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Perubahan status.
            $table->string('status_sebelumnya', 30)->nullable();
            $table->string('status_baru', 30);

            // Catatan aktivitas.
            $table->text('catatan')->nullable();

            // Snapshot keputusan penyelesaian.
            $table->string('jenis_penyelesaian', 30)->nullable();
            $table->text('detail_penyelesaian')->nullable();

            // Informasi refund jika aktivitas terkait refund.
            $table->unsignedInteger('nominal_refund')->nullable();
            $table->string('metode_refund', 30)->nullable();
            $table->string('referensi_refund', 100)->nullable();

            $table->timestamps();

            // Mempermudah pengambilan riwayat berdasarkan garansi
            // dan urutan waktu.
            $table->index(['garansi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_klaim_garansi');
    }
};
