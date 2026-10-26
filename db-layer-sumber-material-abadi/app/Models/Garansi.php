<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Garansi extends Model
{
    protected $table = 'garansi';

    protected $fillable = [
        'no_garansi', 'detail_transaksi_id', 'tanggal_mulai', 'tanggal_berakhir',
        'status_klaim', 'keluhan', 'foto_klaim', 'tanggal_klaim', 'catatan_admin',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
        'tanggal_klaim' => 'datetime',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(DetailTransaksi::class, 'detail_transaksi_id');
    }

    public function getKedaluwarsaAttribute(): bool
    {
        return $this->tanggal_berakhir->endOfDay()->isPast();
    }
}
