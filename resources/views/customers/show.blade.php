@extends('layouts.app')
@section('title', $customer->name)
@section('content')
    <a href="{{ route('customers.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Semua pelanggan</a>

    <div class="mb-5 mt-2 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">{{ $customer->name }}</h1>
            <p class="text-sm text-slate-500">{{ $customer->phone ?: 'Tanpa telepon' }} &middot;
                {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</p>
        </div>
        <div class="rounded-xl bg-white px-5 py-3 text-right shadow-sm">
            <div class="text-xs text-slate-500">
                {{ $balance > 0 ? 'Hutang saat ini' : ($balance < 0 ? 'Kelebihan bayar' : 'Saldo') }}</div>
            <div class="text-2xl font-bold {{ $balance > 0 ? 'text-red-700' : 'text-slate-800' }}">@rupiah(abs($balance))</div>
        </div>
    </div>
    <form method="GET" action="{{ route('customers.debt-pdf', $customer) }}" target="_blank"
        class="mb-6 flex max-w-xl flex-wrap items-end gap-2 rounded-xl bg-white p-4 shadow-sm">
        <x-input name="from" label="Dari tanggal (opsional)" type="date" />
        <x-input name="to" label="Sampai tanggal (opsional)" type="date" />
        <x-btn type="submit" variant="secondary">Cetak PDF</x-btn>
    </form>
    @if ($balance > 0)
        <form method="POST" action="{{ route('customers.pay', $customer) }}"
            class="mb-6 max-w-xl space-y-3 rounded-xl bg-white p-4 shadow-sm">
            @csrf
            <h2 class="font-semibold">Catat pembayaran hutang</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input name="amount" label="Jumlah dibayar (Rp)" type="number" min="1" :max="$balance"
                    step="1" inputmode="numeric" id="payAmount" required />
                <div class="flex items-end gap-2">
                    @foreach (['tunai' => 'Tunai', 'qris' => 'QRIS'] as $v => $l)
                        <label>
                            <input type="radio" name="method" value="{{ $v }}" class="peer sr-only"
                                @checked(old('method', 'tunai') === $v)>
                            <span
                                class="block cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <x-input name="note" label="Catatan (opsional)" />
            <div class="flex gap-2">
                <x-btn type="submit">Catat pembayaran</x-btn>
                <x-btn variant="secondary" onclick="document.getElementById('payAmount').value = {{ $balance }}">Isi
                    lunas</x-btn>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <h2 class="border-b p-3 font-semibold">Riwayat hutang</h2>
        @php $labels = ['hutang' => 'Hutang', 'bayar' => 'Pembayaran', 'batal' => 'Pembatalan']; @endphp
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Keterangan</th>
                    <th class="p-3 text-right">Jumlah</th>
                    <th class="p-3 text-right">Saldo</th>
                    <th class="p-3">Dicatat oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($ledger as $e)
                    <tr>
                        <td class="whitespace-nowrap p-3">{{ $e->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3">
                            {{ $labels[$e->type] }}@if ($e->method)
                                ({{ strtoupper($e->method) }})
                            @endif
                            @if ($e->order)
                                &middot; <a href="{{ route('receipt.show', $e->order) }}"
                                    class="text-blue-600 hover:underline">{{ $e->order->order_no }}</a>
                            @endif
                            @if ($e->note && !$e->order)
                                <div class="text-xs text-slate-500">{{ $e->note }}</div>
                            @endif
                        </td>
                        <td
                            class="p-3 text-right font-medium {{ $e->type === 'hutang' ? 'text-red-700' : 'text-blue-600' }}">
                            {{ $e->type === 'hutang' ? '+' : '-' }}@rupiah($e->amount)</td>
                        <td class="p-3 text-right">@rupiah($e->running_balance)</td>
                        <td class="p-3">{{ $e->user->name }}</td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-500">Belum ada catatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (auth()->user()->isAdmin())
            <div class="mt-6 max-w-xl space-y-3 rounded-xl bg-white p-4 shadow-sm">
                <h2 class="font-semibold">Ubah data pelanggan (Admin)</h2>
                <form method="POST" action="{{ route('customers.update', $customer) }}"
                    class="grid gap-3 sm:grid-cols-3 sm:items-end">
                    @csrf @method('PUT')
                    <x-input name="name" label="Nama" :value="$customer->name" required />
                    <x-input name="phone" label="Telepon" :value="$customer->phone" />
                    <x-btn type="submit" variant="secondary">Simpan</x-btn>
                </form>
                <form method="POST" action="{{ route('customers.toggle', $customer) }}">
                    @csrf @method('PATCH')
                    <x-btn type="submit"
                        variant="secondary">{{ $customer->is_active ? 'Nonaktifkan pelanggan' : 'Aktifkan pelanggan' }}</x-btn>
                </form>
            </div>
        @endif
    @endsection
