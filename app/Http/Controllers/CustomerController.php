<?php

namespace App\Http\Controllers;

use App\Models\DebtEntry;
use App\Services\DebtService;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Tambah pelanggan cepat dari halaman kasir (mengembalikan JSON)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'id'      => $customer->id,
            'name'    => $customer->name,
            'phone'   => $customer->phone,
            'balance' => 0,
        ], 201);
    }
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $customers = Customer::withBalance()
            ->when($q !== '', fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->get();

        if ($request->boolean('hutang')) {
            $customers = $customers->filter(fn($c) => $c->balance > 0)->values();
        }

        // Total piutang dihitung dari SEMUA pelanggan, tidak terpengaruh pencarian
        $totalPiutang = Customer::withBalance()->get()->where('balance', '>', 0)->sum('balance');

        return view('customers.index', compact('customers', 'q', 'totalPiutang'));
    }

    public function show(Customer $customer)
    {
        // Urutkan dari yang terlama, hitung saldo berjalan, lalu balik agar yang terbaru di atas
        $running = 0;

        $ledger = $customer->debtEntries()->with(['order', 'user'])->orderBy('id')->get()
            ->map(function ($entry) use (&$running) {
                $running += $entry->type === DebtEntry::TYPE_HUTANG ? $entry->amount : -$entry->amount;
                $entry->running_balance = $running;

                return $entry;
            })
            ->reverse()
            ->values();

        return view('customers.show', ['customer' => $customer, 'balance' => $running, 'ledger' => $ledger]);
    }

    public function pay(Request $request, Customer $customer, DebtService $debts)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'in:tunai,qris'],
            'note'   => ['nullable', 'string', 'max:150'],
        ], [
            'amount.required' => 'Isi jumlah pembayaran.',
            'amount.integer'  => 'Jumlah pembayaran harus berupa angka.',
        ]);

        $debts->pay($customer, $request->user(), (int) $data['amount'], $data['method'], $data['note'] ?? null);

        return back()->with('success', 'Pembayaran hutang dicatat.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $customer->update($data);

        return back()->with('success', 'Data pelanggan diperbarui.');
    }

    public function toggle(Customer $customer)
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', 'Status pelanggan diperbarui.');
    }
}
