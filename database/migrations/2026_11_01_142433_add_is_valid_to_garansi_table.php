<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garansi', function (Blueprint $table) {
            $table->boolean('is_valid')
                ->default(true)
                ->after('tanggal_berakhir');
        });
    }

    public function down(): void
    {
        Schema::table('garansi', function (Blueprint $table) {
            $table->dropColumn('is_valid');
        });
    }
};