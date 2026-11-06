<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->enum('status', [
                'menunggu_pembayaran',
                'lunas',
                'dibatalkan',
                'kedaluwarsa',
                'gagal',
            ])->default('lunas')->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->enum('status', [
                'menunggu_pembayaran',
                'lunas',
                'dibatalkan',
                'kedaluwarsa',
            ])->default('lunas')->change();
        });
    }
};
