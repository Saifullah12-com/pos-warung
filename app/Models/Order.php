<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_BATAL   = 'batal';

    protected $fillable = [
        'order_no',
        'user_id',
        'customer_id',
        'order_type',
        'queue_date',
        'queue_no',
        'status',
        'total',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'total'        => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function debtEntry()
    {
        return $this->hasOne(DebtEntry::class);
    }
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Dipakai laporan: hanya transaksi yang tidak dibatalkan
    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_SELESAI);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_BATAL;
    }
}
