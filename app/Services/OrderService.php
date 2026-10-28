<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DebtEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Semua aturan bisnis transaksi ada di sini (bukan di controller):
 * hitung total, nomor antrian, simpan pesanan + item + pembayaran, dan pembatalan.
 */
class OrderService
{
    /** Nomor antrian berikutnya untuk hari ini (hanya untuk tampilan). */
    public function nextQueueNo(): int
    {
        return (int) Order::where('queue_date', now()->toDateString())->max('queue_no') + 1;
    }

    /**
     * @param array{order_type:string, items:array<int,int>, method:string, amount_received?:int|null, reference_no?:string|null} $data
     */
    public function create(User $cashier, array $data): Order
    {
        // Jika dua kasir menyimpan bersamaan dan mendapat nomor antrian sama,
        // database menolak (unique). Kita ulang beberapa kali dengan nomor baru.
        $maxAttempts = 3;

        for ($attempt = 1;; $attempt++) {
            try {
                return DB::transaction(fn() => $this->persist($cashier, $data));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }

    private function persist(User $cashier, array $data): Order
    {
        // 1. Produk dari DATABASE. Harga dari browser tidak pernah dipercaya.
        $qtyById  = collect($data['items'])->mapWithKeys(fn($qty, $id) => [(int) $id => (int) $qty]);
        $products = Product::sellable()->whereIn('id', $qtyById->keys())->get()->keyBy('id');

        if ($products->count() !== $qtyById->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada produk yang habis atau sudah tidak tersedia. Periksa kembali pesanan.',
            ]);
        }

        // 2. Hitung baris pesanan dan total di server.
        $lines = [];
        $total = 0;

        foreach ($qtyById as $id => $qty) {
            $product  = $products[$id];
            $subtotal = $product->price * $qty;
            $total   += $subtotal;

            $lines[] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'unit_price'   => $product->price,
                'qty'          => $qty,
                'subtotal'     => $subtotal,
            ];
        }

        // 3. Pelanggan (wajib bila ada hutang)
        $customer = null;

        if (! empty($data['customer_id'])) {
            $customer = Customer::where('is_active', true)->find($data['customer_id']);

            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan tidak ditemukan atau sudah nonaktif.',
                ]);
            }
        }

        if ($data['method'] === 'hutang' && ! $customer) {
            throw ValidationException::withMessages(['customer_id' => 'Pilih pelanggan untuk transaksi hutang.']);
        }

        // 4. Pembayaran: hitung uang masuk, sisa hutang, dan kembalian
        $received = match ($data['method']) {
            'hutang' => 0,
            'tunai'  => (int) ($data['amount_received'] ?? 0),
            default  => min($total, (int) ($data['amount_received'] ?? $total)), // QRIS: dianggap pas
        };

        $debt   = max(0, $total - $received);   // bagian yang belum dibayar
        $change = max(0, $received - $total);   // kembalian

        // Kurang bayar hanya boleh jika ada pelanggan yang menanggung hutangnya
        if ($debt > 0 && ! $customer) {
            throw ValidationException::withMessages([
                'amount_received' => 'Uang yang diterima kurang dari total pesanan.',
            ]);
        }

        // 5. Nomor antrian dan nomor transaksi
        $now     = now();
        $queueNo = (int) Order::where('queue_date', $now->toDateString())->max('queue_no') + 1;

        $order = Order::create([
            'order_no'    => sprintf('TRX-%s-%03d', $now->format('Ymd'), $queueNo),
            'user_id'     => $cashier->id,
            'customer_id' => $customer?->id,
            'order_type'  => $data['order_type'],
            'queue_date'  => $now->toDateString(),
            'queue_no'    => $queueNo,
            'status'      => Order::STATUS_SELESAI,
            'total'       => $total,
        ]);

        $order->items()->createMany($lines);

        $order->payment()->create([
            'method'          => $data['method'],
            'amount_received' => $received,
            'change_amount'   => $change,
            'debt_amount'     => $debt,
            'reference_no'    => $data['method'] === 'qris' ? ($data['reference_no'] ?? null) : null,
            'verified_by'     => $cashier->id,
            'paid_at'         => $now,
        ]);

        // 6. Catat hutang di buku hutang pelanggan
        if ($debt > 0) {
            DebtEntry::create([
                'customer_id' => $customer->id,
                'order_id'    => $order->id,
                'type'        => DebtEntry::TYPE_HUTANG,
                'amount'      => $debt,
                'note'        => 'Transaksi ' . $order->order_no,
                'user_id'     => $cashier->id,
            ]);
        }

        return $order;
    }

    /** Pembatalan: status diubah, data TIDAK dihapus. Pemeriksaan peran dilakukan di route. */
    public function cancel(Order $order, User $admin, string $reason): Order
    {
        return DB::transaction(function () use ($order, $admin, $reason) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw ValidationException::withMessages(['reason' => 'Transaksi ini sudah dibatalkan.']);
            }

            $locked->update([
                'status'        => Order::STATUS_BATAL,
                'cancel_reason' => $reason,
                'cancelled_by'  => $admin->id,
                'cancelled_at'  => now(),
            ]);

            // Jika transaksi ini menimbulkan hutang, hutangnya ikut dihapus dari saldo
            $debt = (int) $locked->payment?->debt_amount;

            if ($debt > 0 && $locked->customer_id) {
                DebtEntry::create([
                    'customer_id' => $locked->customer_id,
                    'order_id'    => $locked->id,
                    'type'        => DebtEntry::TYPE_BATAL,
                    'amount'      => $debt,
                    'note'        => 'Pembatalan ' . $locked->order_no,
                    'user_id'     => $admin->id,
                ]);
            }

            return $locked;
        });
    }
}
