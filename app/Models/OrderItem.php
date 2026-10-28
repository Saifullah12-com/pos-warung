<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false; // tabel order_items tidak punya created_at/updated_at

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'unit_price', 'qty', 'subtotal',
    ];

    public function order()   { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
