@extends('layouts.app')
@section('title', 'Riwayat')
@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-lg font-bold">Riwayat transaksi - {{ $date->translatedFormat('d F Y') }}</h1>
        @if (auth()->user()->isAdmin())
            <form method="GET" class="flex items-end gap-2">
                <x-input name="date" label="Tanggal" type="date" :value="$date->toDateString()" />
                <x-btn type="submit" variant="secondary">Tampilkan</x-btn>
            </form>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Antrian</th>
                    <th class="p-3">No. transaksi</th>
                    <th class="p-3">Jam</th>
                    <th class="p-3">Jenis</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3 text-right">Total</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $o)
                    <tr class="{{ $o->isCancelled() ? 'bg-slate-50 text-slate-400' : '' }}">
                        <td class="p-3 font-bold">{{ $o->queue_no }}</td>
                        <td class="p-3">{{ $o->order_no }}</td>
                        <td class="p-3">{{ $o->created_at->format('H:i') }}</td>
                        <td class="p-3">{{ $o->order_type === 'dine_in' ? 'Dine-in' : 'Takeaway' }}</td>
                        <td class="p-3">{{ strtoupper($o->payment?->method ?? '-') }}@if ($o->customer)
                                <div class="text-xs">{{ $o->customer->name }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-right {{ $o->isCancelled() ? 'line-through' : '' }}">@rupiah($o->total)</td>
                        <td class="p-3">
                            @if ($o->isCancelled())
                                <span class="font-semibold text-red-700">Batal</span>
                                <div class="text-xs">{{ $o->cancel_reason }}</div>
                            @else
                                Selesai
                            @endif
                        </td>
                        <td class="space-x-2 whitespace-nowrap p-3 text-right">
                            <a href="{{ route('receipt.show', $o) }}" class="text-blue-600 hover:underline">Struk</a>
                            @if (auth()->user()->isAdmin() && !$o->isCancelled())
                                <button type="button" class="text-red-700 hover:underline"
                                    data-cancel="{{ route('orders.cancel', $o) }}"
                                    data-no="{{ $o->order_no }}">Batalkan</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-500">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>

        @if (auth()->user()->isAdmin())
            <dialog id="cancelDialog" class="m-auto w-full max-w-sm rounded-xl p-0 backdrop:bg-black/50">
                <form id="cancelForm" method="POST" class="space-y-3 p-5">
                    @csrf
                    <h2 class="font-bold">Batalkan <span id="cancelNo"></span>?</h2>
                    <p class="text-sm text-slate-600">Transaksi tetap tampil di riwayat dan tidak dihitung di laporan.
                        Pengembalian uang dilakukan manual.</p>
                    <x-input name="reason" label="Alasan pembatalan" required minlength="3" maxlength="255" />
                    <div class="flex justify-end gap-2">
                        <x-btn variant="secondary" onclick="document.getElementById('cancelDialog').close()">Kembali</x-btn>
                        <x-btn type="submit" variant="danger">Batalkan transaksi</x-btn>
                    </div>
                </form>
            </dialog>
            <script>
                document.querySelectorAll('[data-cancel]').forEach(b => b.addEventListener('click', () => {
                    document.getElementById('cancelForm').action = b.dataset.cancel;
                    document.getElementById('cancelNo').textContent = b.dataset.no;
                    document.getElementById('cancelDialog').showModal();
                }));
            </script>
        @endif
    @endsection
