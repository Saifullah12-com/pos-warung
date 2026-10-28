<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtEntry extends Model
{
    public const TYPE_HUTANG = 'hutang'; // saldo bertambah
    public const TYPE_BAYAR  = 'bayar';  // pelanggan melunasi (saldo berkurang)
    public const TYPE_BATAL  = 'batal';  // transaksi hutang dibatalkan (saldo berkurang)

    protected $fillable = ['customer_id', 'order_id', 'type', 'amount', 'method', 'note', 'user_id'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function order()    { return $this->belongsTo(Order::class); }
    public function user()     { return $this->belongsTo(User::class); }
}