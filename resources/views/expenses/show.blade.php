@extends('layouts.app')
@section('title', 'Rincian Pembelian')
@section('content')
    <a href="{{ route('expenses.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Semua pengeluaran</a>

    <div class="mb-5 mt-2 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">{{ $expense->category }}</h1>
            <p class="text-sm text-slate-500">
                {{ $expense->spent_at->translatedFormat('d F Y, H:i') }}
                &middot; {{ $expense->supplier ?: 'Tanpa toko' }}
                &middot; dicatat oleh {{ $expense->user->name }}
            </p>
            @if ($expense->isCancelled())
                <p class="mt-2 inline-block rounded-lg bg-red-50 px-3 py-1 text-sm text-red-800">
                    Dibatalkan oleh {{ $expense->canceller?->name }} ({{ $expense->cancelled_at?->format('d/m/Y H:i') }}):
                    {{ $expense->cancel_reason }}
                </p>
            @endif
        </div>
        <div class="rounded-xl bg-white px-5 py-3 text-right shadow-sm">
            <div class="text-xs text-slate-500">Total pembelian</div>
            <div class="text-2xl font-bold {{ $expense->isCancelled() ? 'text-slate-400 line-through' : '' }}">
                @rupiah($expense->amount)</div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Barang</th>
                    <th class="p-3 text-right">Jumlah</th>
                    <th class="p-3">Satuan</th>
                    <th class="p-3 text-right">Harga per satuan</th>
                    <th class="p-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($expense->items as $i)
                    <tr>
                        <td class="p-3 font-medium">{{ $i->name }}</td>
                        <td class="p-3 text-right">{{ $i->qtyLabel() }}</td>
                        <td class="p-3">{{ $i->unit }}</td>
                        <td class="p-3 text-right">@rupiah($i->unit_price)</td>
                        <td class="p-3 text-right font-semibold">@rupiah($i->subtotal)</td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50 font-bold">
                    <td colspan="4" class="p-3 text-right">Total</td>
                    <td class="p-3 text-right">@rupiah($expense->amount)</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($expense->receipt_path)
        <div class="mt-5">
            <div class="mb-2 text-sm font-semibold">Foto nota</div>
            <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank">
                <img src="{{ asset('storage/' . $expense->receipt_path) }}" alt="Nota pembelian"
                    class="max-h-80 rounded-xl border border-slate-200">
            </a>
        </div>
    @endif
@endsection
