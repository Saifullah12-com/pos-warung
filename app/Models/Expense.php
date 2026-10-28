<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_BATAL = 'batal';

    public const CATEGORIES = ['Belanja bahan', 'Gaji', 'Listrik, air, gas', 'Sewa', 'Perawatan', 'Lain-lain'];

    protected $fillable = [
        'spent_at',
        'category',
        'supplier',
        'amount',
        'receipt_path',
        'user_id',
        'status',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['spent_at' => 'datetime', 'amount' => 'integer', 'cancelled_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    // Dipakai laporan: hanya pembelian yang tidak dibatalkan
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_AKTIF);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_BATAL;
    }

    // Ringkasan untuk kolom daftar: "Beras 25 kg, Telur 3 kg, +2 lainnya"
    public function itemsSummary(int $show = 2): string
    {
        $parts = $this->items->take($show)->map(fn(ExpenseItem $i) => $i->name . ' ' . $i->qtyLabel() . ' ' . $i->unit);
        $rest  = $this->items->count() - $show;

        return $parts->implode(', ') . ($rest > 0 ? ", +{$rest} lainnya" : '');
    }
}
