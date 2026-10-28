<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function debtEntries()
    {
        return $this->hasMany(DebtEntry::class);
    }

    // Saldo hutang SATU pelanggan (dipakai di halaman kasir dan layar pelunasan)
    public function balance(): int
    {
        return (int) $this->debtEntries()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'hutang' THEN amount ELSE -amount END), 0) as bal")
            ->value('bal');
    }

    // Saldo untuk BANYAK pelanggan sekaligus (dipakai di daftar). Hasilnya ada di kolom ->balance
    public function scopeWithBalance(Builder $query): Builder
    {
        return $query->select('customers.*')->addSelect([
            'balance' => DebtEntry::selectRaw("COALESCE(SUM(CASE WHEN type = 'hutang' THEN amount ELSE -amount END), 0)")
                ->whereColumn('customer_id', 'customers.id'),
        ]);
    }
}