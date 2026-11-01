<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garansi extends Model
{
    protected $table = 'garansi';

    protected $fillable = [
        'no_garansi', 'detail_transaksi_id', 'tanggal_mulai', 'tanggal_berakhir',
        'status_klaim', 'keluhan', 'foto_klaim', 'tanggal_klaim', 'catatan_admin', 'catatan_pelanggan', 'jenis_penyelesaian', 
        'detail_penyelesaian', 'nominal_refund', 'metode_refund', 'referensi_refund', 'tanggal_selesai', 'is_valid',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
        'tanggal_klaim' => 'datetime',
        'nominal_refund' => 'integer',
        'tanggal_selesai' => 'datetime',
        'is_valid' => 'boolean',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(DetailTransaksi::class, 'detail_transaksi_id');
    }

    public function getKedaluwarsaAttribute(): bool
    {
        return $this->tanggal_berakhir->endOfDay()->isPast();
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(
            RiwayatKlaimGaransi::class,
            'garansi_id'
        );
    }
}
