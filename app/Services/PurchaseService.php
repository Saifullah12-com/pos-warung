<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function create(User $admin, array $data, ?UploadedFile $receipt = null): Purchase
    {
        // Subtotal dan total SELALU dihitung di server. Angka dari browser tidak dipercaya.
        $lines = [];
        $total = 0;

        foreach ($data['items'] as $item) {
            $qty      = round((float) $item['qty'], 3);
            $price    = (int) $item['unit_price'];
            $subtotal = (int) round($qty * $price);
            $total   += $subtotal;

            $lines[] = [
                'name'       => $this->clean($item['name']),
                'qty'        => $qty,
                'unit'       => mb_strtolower(trim($item['unit'])),
                'unit_price' => $price,
                'subtotal'   => $subtotal,
            ];
        }

        $path = $receipt?->store('purchases', 'public');

        return DB::transaction(function () use ($admin, $data, $lines, $total, $path) {
            $purchase = Purchase::create([
                'type'         => $data['type'],
                'purchased_at' => ! empty($data['purchased_at']) ? Carbon::parse($data['purchased_at']) : now(),
                'supplier'     => ! empty($data['supplier']) ? $this->clean($data['supplier']) : null,
                'receipt_path' => $path,
                'total'        => $total,
                'user_id'      => $admin->id,
                'status'       => Purchase::STATUS_AKTIF,
            ]);

            $purchase->items()->createMany($lines);

            return $purchase;
        });
    }

    // Dibatalkan, bukan dihapus. Pemeriksaan peran dilakukan di route.
    public function cancel(Purchase $purchase, User $admin, string $reason): Purchase
    {
        return DB::transaction(function () use ($purchase, $admin, $reason) {
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw ValidationException::withMessages(['reason' => 'Pembelian ini sudah dibatalkan.']);
            }

            $locked->update([
                'status'        => Purchase::STATUS_BATAL,
                'cancel_reason' => $reason,
                'cancelled_by'  => $admin->id,
                'cancelled_at'  => now(),
            ]);

            return $locked;
        });
    }

    // Rapikan spasi ganda agar "Beras" dan "Beras  " tidak dianggap barang berbeda
    private function clean(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
