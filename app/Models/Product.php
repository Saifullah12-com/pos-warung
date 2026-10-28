<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'price', 'photo_path', 'is_available', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'integer',
            'is_available' => 'boolean',
            'is_active'    => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Produk yang masih dipakai (tidak dinonaktifkan)
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    // Produk yang boleh dijual di kasir: aktif DAN tidak habis
    public function scopeSellable(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_available', true);
    }
}
