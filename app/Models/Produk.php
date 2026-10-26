<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kategori_id', 'kode', 'nama', 'satuan', 'deskripsi', 'harga_jual',
        'diskon_tipe', 'diskon_nilai', 'diskon_aktif', 'stok',
        'punya_garansi', 'masa_garansi_bulan', 'foto', 'is_active',
    ];

    protected $casts = [
        'diskon_aktif' => 'boolean',
        'punya_garansi' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Ikut terkirim ke Vue lewat Inertia
    protected $appends = ['harga_akhir', 'potongan'];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** BR-01: harga akhir per unit, dibulatkan ke rupiah penuh, tidak pernah negatif. */
    protected function hargaAkhir(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->diskon_aktif || ! $this->diskon_tipe) {
                return (int) $this->harga_jual;
            }

            $akhir = $this->diskon_tipe === 'persen'
                ? (int) round($this->harga_jual * (100 - $this->diskon_nilai) / 100)
                : $this->harga_jual - $this->diskon_nilai;

            return max(0, (int) $akhir);
        });
    }

    protected function potongan(): Attribute
    {
        return Attribute::get(fn () => (int) $this->harga_jual - $this->harga_akhir);
    }
}
