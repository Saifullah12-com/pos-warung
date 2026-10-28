@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <x-input name="from" label="Dari tanggal" type="date" :value="$from->toDateString()" />
        <x-input name="to" label="Sampai tanggal" type="date" :value="$to->toDateString()" />
        <x-btn type="submit">Tampilkan</x-btn>
    </form>

    {{-- 1. Kartu ringkasan --}}
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Total penjualan</div>
            <div class="text-xl font-bold">@rupiah($summary->sales)</div>
            <div class="text-xs text-slate-400">{{ (int) $summary->trx }} transaksi</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Uang masuk</div>
            <div class="text-xl font-bold text-emerald-700">@rupiah($totalIn)</div>
            <div class="text-xs text-slate-400">termasuk pelunasan</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Hutang baru</div>
            <div class="text-xl font-bold text-red-700">@rupiah($newDebt)</div>
            <div class="text-xs text-slate-400">dari penjualan periode ini</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Transaksi batal</div>
            <div class="text-xl font-bold">{{ $cancelled }}</div>
            <div class="text-xs text-slate-400">tidak dihitung</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Piutang saat ini</div>
            <div class="text-xl font-bold text-red-700">@rupiah($totalReceivable)</div>
            <div class="text-xs text-slate-400">semua pelanggan</div>
        </div>
    </div>

    {{-- 2. Pengeluaran (kiri) dan Kas bersih (kanan) --}}
    <div class="mb-5 grid items-start gap-5 lg:grid-cols-2">
        <section class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <h2 class="border-b p-3 font-semibold">Pengeluaran per kategori</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    @forelse ($expenseByCategory as $x)
                        <tr>
                            <td class="p-3">{{ $x->category }} <span
                                    class="text-xs text-slate-400">({{ $x->n }}x)</span></td>
                            <td class="p-3 text-right">@rupiah($x->total)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-4 text-center text-slate-500">Tidak ada pengeluaran.</td>
                        </tr>
                    @endforelse
                    <tr class="font-bold">
                        <td class="p-3">Total pengeluaran</td>
                        <td class="p-3 text-right text-red-700">@rupiah($totalExpense)</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <div class="text-xs text-slate-500">Kas bersih periode</div>
            <div class="text-3xl font-bold {{ $netCash < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                {{ $netCash < 0 ? '-' : '' }}@rupiah(abs($netCash))</div>
            <p class="mt-2 text-sm text-slate-500">Uang masuk (@rupiah($totalIn)) dikurangi pengeluaran (@rupiah($totalExpense)).
                Ini bukan laba: modal bahan per menu dan hutang yang belum tertagih tidak termasuk.</p>
        </section>
    </div>

    {{-- 3. Rincian uang masuk (kiri) dan Daftar piutang (kanan) --}}
    <div class="mb-5 grid items-start gap-5 lg:grid-cols-2">
        <section class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <h2 class="border-b p-3 font-semibold">Rincian uang masuk</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    <tr>
                        <td class="p-3">Penjualan tunai (diterima dikurangi kembalian)</td>
                        <td class="p-3 text-right">@rupiah($moneyIn['tunai']['sales'])</td>
                    </tr>
                    <tr>
                        <td class="p-3">Penjualan QRIS</td>
                        <td class="p-3 text-right">@rupiah($moneyIn['qris']['sales'])</td>
                    </tr>
                    <tr>
                        <td class="p-3">Pelunasan hutang tunai</td>
                        <td class="p-3 text-right">@rupiah($moneyIn['tunai']['repay'])</td>
                    </tr>
                    <tr>
                        <td class="p-3">Pelunasan hutang QRIS</td>
                        <td class="p-3 text-right">@rupiah($moneyIn['qris']['repay'])</td>
                    </tr>
                    <tr class="font-bold">
                        <td class="p-3">Total uang masuk</td>
                        <td class="p-3 text-right">@rupiah($totalIn)</td>
                    </tr>
                </tbody>
            </table>
            <p class="border-t p-3 text-xs text-slate-500">Total penjualan = penjualan tunai + penjualan QRIS + hutang baru.
                Transaksi yang dibatalkan tidak ikut dihitung, termasuk uangnya (pengembalian dilakukan manual).</p>
        </section>

        <section class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <h2 class="border-b p-3 font-semibold">Daftar piutang pelanggan</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="p-3">Pelanggan</th>
                        <th class="p-3">Telepon</th>
                        <th class="p-3 text-right">Hutang</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($receivables as $c)
                        <tr>
                            <td class="p-3 font-medium">{{ $c->name }}</td>
                            <td class="p-3">{{ $c->phone ?: '-' }}</td>
                            <td class="p-3 text-right font-semibold text-red-700">@rupiah($c->balance)</td>
                            <td class="p-3 text-right"><a href="{{ route('customers.show', $c) }}"
                                    class="text-blue-600 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-slate-500">Tidak ada piutang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    {{-- 4. Penjualan per hari (kiri) dan Produk terlaris (kanan) --}}
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <h2 class="border-b p-3 font-semibold">Penjualan per hari</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3 text-right">Transaksi</th>
                        <th class="p-3 text-right">Penjualan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($daily as $d)
                        <tr>
                            <td class="p-3">{{ \Carbon\Carbon::parse($d->day)->translatedFormat('d M Y') }}</td>
                            <td class="p-3 text-right">{{ $d->trx }}</td>
                            <td class="p-3 text-right">@rupiah($d->sales)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-4 text-center text-slate-500">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <h2 class="border-b p-3 font-semibold">Produk terlaris</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="p-3">Produk</th>
                        <th class="p-3 text-right">Terjual</th>
                        <th class="p-3 text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($topProducts as $p)
                        <tr>
                            <td class="p-3">{{ $p->product_name }}</td>
                            <td class="p-3 text-right">{{ $p->qty }}</td>
                            <td class="p-3 text-right">@rupiah($p->sales)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-4 text-center text-slate-500">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
