<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garansi', function (Blueprint $table) {
            $table->text('catatan_pelanggan')
                ->nullable()
                ->after('catatan_admin');
        });

        // Salin catatan lama agar informasi pelanggan tidak hilang.
        DB::table('garansi')
            ->whereNull('catatan_pelanggan')
            ->whereNotNull('catatan_admin')
            ->update([
                'catatan_pelanggan' => DB::raw('catatan_admin'),
            ]);
    }

    public function down(): void
    {
        Schema::table('garansi', function (Blueprint $table) {
            $table->dropColumn('catatan_pelanggan');
        });
    }
};