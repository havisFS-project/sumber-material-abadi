<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'no_invoice', 'kasir_id', 'nama_pelanggan', 'no_hp_pelanggan',
        'subtotal', 'total_diskon', 'total_bayar', 'metode_bayar',
        'uang_diterima', 'kembalian', 'status', 'alasan_batal', 'dibatalkan_oleh',
    ];

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class);
    }
}
