<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(User $admin, array $data, ?UploadedFile $receipt = null): Expense
    {
        // 1. Hitung subtotal tiap barang dan total di SERVER
        $lines = [];
        $total = 0;

        foreach ($data['items'] as $row) {
            $qty      = round((float) $row['qty'], 2);
            $price    = (int) $row['unit_price'];
            $subtotal = (int) round($qty * $price);
            $total   += $subtotal;

            $lines[] = [
                'name'       => $this->tidyName($row['name']),
                'qty'        => $qty,
                'unit'       => mb_strtolower(trim($row['unit'])),
                'unit_price' => $price,
                'subtotal'   => $subtotal,
            ];
        }

        if ($total > 2_000_000_000) {
            throw ValidationException::withMessages(['items' => 'Total pembelian terlalu besar. Periksa lagi jumlah dan harga.']);
        }

        // 2. Simpan semuanya dalam satu transaction: pembelian dan barangnya tersimpan bersama atau tidak sama sekali
        return DB::transaction(function () use ($admin, $data, $receipt, $lines, $total) {
            $expense = Expense::create([
                'spent_at'     => Carbon::parse($data['spent_at']),
                'category'     => $data['category'],
                'supplier'     => filled($data['supplier'] ?? null) ? trim($data['supplier']) : null,
                'amount'       => $total,
                'receipt_path' => $receipt?->store('expenses', 'public'),
                'user_id'      => $admin->id,
                'status'       => Expense::STATUS_AKTIF,
            ]);

            $expense->items()->createMany($lines);

            return $expense;
        });
    }

    // Dibatalkan, bukan dihapus. Pemeriksaan peran dilakukan di route.
    public function cancel(Expense $expense, User $admin, string $reason): Expense
    {
        return DB::transaction(function () use ($expense, $admin, $reason) {
            $locked = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw ValidationException::withMessages(['reason' => 'Pembelian ini sudah dibatalkan.']);
            }

            $locked->update([
                'status'        => Expense::STATUS_BATAL,
                'cancel_reason' => $reason,
                'cancelled_by'  => $admin->id,
                'cancelled_at'  => now(),
            ]);

            return $locked;
        });
    }

    // " beras  " menjadi "Beras", supaya satu barang tidak tercatat dengan dua ejaan
    private function tidyName(string $name): string
    {
        $name = Str::squish($name);

        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }
}
