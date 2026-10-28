@extends('layouts.app')
@section('title', 'Pelanggan')
@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold">Pelanggan</h1>
            <p class="text-sm text-slate-500">Total piutang: <b class="text-red-700">@rupiah($totalPiutang)</b></p>
        </div>
        <form method="GET" class="flex items-end gap-2">
            <x-input name="q" label="Cari nama" :value="$q" />
            <label class="mb-2 flex items-center gap-1 text-sm">
                <input type="checkbox" name="hutang" value="1" @checked(request()->boolean('hutang'))> Hanya yang berhutang
            </label>
            <x-btn type="submit" variant="secondary">Cari</x-btn>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Nama</th>
                    <th class="p-3">Telepon</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Saldo hutang</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($customers as $c)
                    @php $b = (int) $c->balance; @endphp
                    <tr class="{{ $c->is_active ? '' : 'text-slate-400' }}">
                        <td class="p-3 font-medium">{{ $c->name }}</td>
                        <td class="p-3">{{ $c->phone ?: '-' }}</td>
                        <td class="p-3">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td
                            class="p-3 text-right font-semibold {{ $b > 0 ? 'text-red-700' : ($b < 0 ? 'text-blue-600' : 'text-slate-400') }}">
                            @if ($b < 0)
                                Lebih
                            @endif @rupiah(abs($b))
                        </td>
                        <td class="p-3 text-right"><a href="{{ route('customers.show', $c) }}"
                                class="text-blue-600 hover:underline">Detail</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-slate-500">Belum ada pelanggan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
