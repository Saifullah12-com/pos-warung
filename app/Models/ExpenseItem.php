<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['expense_id', 'name', 'qty', 'unit', 'unit_price', 'subtotal'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:2', 'unit_price' => 'integer', 'subtotal' => 'integer'];
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    // 25.00 menjadi "25", 1.50 menjadi "1,5"
    public function qtyLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->qty, 2, ',', '.'), '0'), ',');
    }
}
