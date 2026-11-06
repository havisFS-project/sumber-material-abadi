<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {

            // Status tambahan untuk pembayaran QRIS.
            $table->enum('status', [
                'menunggu_pembayaran',
                'lunas',
                'dibatalkan',
                'kedaluwarsa',
            ])->default('lunas')->change();

            // ID transaksi yang diberikan oleh Midtrans.
            $table->string('midtrans_transaction_id')
                ->nullable()
                ->unique();

            // URL gambar QRIS dari Midtrans.
            $table->text('qris_url')->nullable();

            // Batas waktu pembayaran QRIS.
            $table->timestamp('payment_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {

            $table->dropUnique([
                'midtrans_transaction_id',
            ]);

            $table->dropColumn([
                'midtrans_transaction_id',
                'qris_url',
                'payment_expires_at',
            ]);

            // Kembalikan enum status ke struktur awal.
            $table->enum('status', [
                'lunas',
                'dibatalkan',
            ])->default('lunas')->change();
        });
    }
};
