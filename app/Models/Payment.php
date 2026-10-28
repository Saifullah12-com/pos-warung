<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'method',
        'amount_received',
        'change_amount',
        'debt_amount',
        'reference_no',
        'verified_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
