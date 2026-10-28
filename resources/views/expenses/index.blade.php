@extends('layouts.app')
@section('title', 'Pengeluaran')
@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold">Pengeluaran</h1>
            <p class="text-sm text-slate-500">{{ $from->translatedFormat('d M Y') }} - {{ $to->translatedFormat('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <x-input name="from" label="Dari" type="date" :value="$from->toDateString()" />
                <x-input name="to" label="Sampai" type="date" :value="$to->toDateString()" />
                <x-btn type="submit" variant="secondary">Tampilkan</x-btn>
            </form>
            <x-btn :href="route('expenses.create')">+ Catat pembelian</x-btn>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Total pengeluaran</div>
            <div class="text-xl font-bold text-red-700">@rupiah($total)</div>
            <div class="text-xs text-slate-400">tidak termasuk yang dibatalkan</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Jumlah pembelian</div>
            <div class="text-xl font-bold">{{ $count }}</div>
            <div class="text-xs text-slate-400">{{ $cancelled }} dibatalkan</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Rata-rata per pembelian</div>
            <div class="text-xl font-bold">@rupiah($count ? intdiv($total, $count) : 0)</div>
            <div class="text-xs text-slate-400">periode ini</div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Tanggal &amp; waktu</th>
                    <th class="p-3">Jenis</th>
                    <th class="p-3">Barang dibeli</th>
                    <th class="p-3">Toko</th>
                    <th class="p-3 text-right">Total</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($expenses as $e)
                    <tr class="{{ $e->isCancelled() ? 'bg-slate-50 text-slate-400' : '' }}">
                        <td class="whitespace-nowrap p-3">{{ $e->spent_at->format('d/m/Y') }}<div class="text-xs">
                                {{ $e->spent_at->format('H:i') }}</div>
                        </td>
                        <td class="p-3">{{ $e->category }}</td>
                        <td class="p-3">{{ $e->itemsSummary() }}</td>
                        <td class="p-3">{{ $e->supplier ?: '-' }}</td>
                        <td class="p-3 text-right font-semibold {{ $e->isCancelled() ? 'line-through' : '' }}">
                            @rupiah($e->amount)</td>
                        <td class="p-3">
                            @if ($e->isCancelled())
                                <span class="font-semibold text-red-700">Batal</span>
                                <div class="text-xs">{{ $e->cancel_reason }}</div>
                            @else
                                Aktif
                            @endif
                        </td>
                        <td class="space-x-3 whitespace-nowrap p-3 text-right">
                            <a href="{{ route('expenses.show', $e) }}" class="text-blue-600 hover:underline">Rincian</a>
                            @unless ($e->isCancelled())
                                <button type="button" class="text-red-700 hover:underline"
                                    data-cancel="{{ route('expenses.cancel', $e) }}"
                                    data-name="{{ $e->itemsSummary() }}">Batalkan</button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-500">Belum ada pembelian pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $expenses->links() }}</div>

    <dialog id="cancelDialog" class="m-auto w-full max-w-sm rounded-xl p-0 backdrop:bg-black/50">
        <form id="cancelForm" method="POST" class="space-y-3 p-5">
            @csrf
            <h2 class="font-bold">Batalkan pembelian?</h2>
            <p class="text-sm text-slate-600" id="cancelName"></p>
            <p class="text-sm text-slate-600">Catatan tetap tampil di daftar, tetapi tidak dihitung di laporan.</p>
            <x-input name="reason" label="Alasan pembatalan" required minlength="3" maxlength="255" />
            <div class="flex justify-end gap-2">
                <x-btn variant="secondary" onclick="document.getElementById('cancelDialog').close()">Kembali</x-btn>
                <x-btn type="submit" variant="danger">Batalkan</x-btn>
            </div>
        </form>
    </dialog>
    <script>
        document.querySelectorAll('[data-cancel]').forEach(b => b.addEventListener('click', () => {
            document.getElementById('cancelForm').action = b.dataset.cancel;
            document.getElementById('cancelName').textContent = b.dataset.name;
            document.getElementById('cancelDialog').showModal();
        }));
    </script>
@endsection
