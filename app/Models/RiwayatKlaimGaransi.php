<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKlaimGaransi extends Model
{
    protected $table = 'riwayat_klaim_garansi';

    protected $fillable = [
        'garansi_id',
        'jenis_aksi',
        'aktor',
        'user_id',
        'status_sebelumnya',
        'status_baru',
        'catatan',
        'jenis_penyelesaian',
        'detail_penyelesaian',
        'nominal_refund',
        'metode_refund',
        'referensi_refund',
    ];

    protected $casts = [
        'nominal_refund' => 'integer',
    ];

    public function garansi(): BelongsTo
    {
        return $this->belongsTo(Garansi::class, 'garansi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
