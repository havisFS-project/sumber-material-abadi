
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garansi', function (Blueprint $table) {
            // Jenis solusi yang dipilih admin.
            $table->string('jenis_penyelesaian', 30)->nullable();

            // Penjelasan tindakan penyelesaian.
            $table->text('detail_penyelesaian')->nullable();

            // Data tambahan khusus refund.
            $table->unsignedInteger('nominal_refund')->nullable();
            $table->string('metode_refund', 30)->nullable();
            $table->string('referensi_refund', 100)->nullable();

            // Waktu klaim benar-benar selesai.
            $table->timestamp('tanggal_selesai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('garansi', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_penyelesaian',
                'detail_penyelesaian',
                'nominal_refund',
                'metode_refund',
                'referensi_refund',
                'tanggal_selesai',
            ]);
        });
    }
};
