<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DebtEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebtService
{
    public function pay(Customer $customer, User $receiver, int $amount, string $method, ?string $note = null): DebtEntry
    {
        return DB::transaction(function () use ($customer, $receiver, $amount, $method, $note) {
            // Kunci baris pelanggan agar dua pelunasan bersamaan tidak saling menimpa
            $locked  = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $balance = $locked->balance();

            if ($amount < 1) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran minimal Rp1.']);
            }

            if ($balance <= 0) {
                throw ValidationException::withMessages(['amount' => 'Pelanggan ini tidak memiliki hutang.']);
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah melebihi hutang saat ini (Rp' . number_format($balance, 0, ',', '.') . ').',
                ]);
            }

            return $locked->debtEntries()->create([
                'type'    => DebtEntry::TYPE_BAYAR,
                'amount'  => $amount,
                'method'  => $method,
                'note'    => $note,
                'user_id' => $receiver->id,
            ]);
        });
    }
}
