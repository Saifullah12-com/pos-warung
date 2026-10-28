@extends('layouts.app')
@section('title', 'Produk')
@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-bold">Produk</h1>
        <x-btn :href="route('products.create')">+ Tambah produk</x-btn>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Foto</th>
                    <th class="p-3">Nama</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3 text-right">Harga</th>
                    <th class="p-3">Ketersediaan</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($products as $p)
                    <tr class="{{ $p->is_active ? '' : 'bg-slate-50 text-slate-400' }}">
                        <td class="p-3">
                            @if ($p->photo_path)
                                <img src="{{ asset('storage/' . $p->photo_path) }}" alt=""
                                    class="h-10 w-10 rounded object-cover">
                            @else
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded bg-slate-100 font-bold text-slate-400">
                                    {{ strtoupper(mb_substr($p->name, 0, 1)) }}</div>
                            @endif
                        </td>
                        <td class="p-3 font-medium">{{ $p->name }}</td>
                        <td class="p-3">{{ $p->category->name }}</td>
                        <td class="p-3 text-right">@rupiah($p->price)</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('products.availability', $p) }}">@csrf @method('PATCH')
                                <button
                                    class="rounded-full border px-3 py-1 text-xs {{ $p->is_available ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-300 bg-red-50 text-red-800' }}">{{ $p->is_available ? 'Tersedia' : 'Habis' }}</button>
                            </form>
                        </td>
                        <td class="p-3">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="space-x-3 whitespace-nowrap p-3 text-right">
                            <a href="{{ route('products.edit', $p) }}" class="text-blue-600 hover:underline">Ubah</a>
                            <form method="POST" action="{{ route('products.active', $p) }}" class="inline">@csrf
                                @method('PATCH')
                                <button
                                    class="text-slate-600 hover:underline">{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-500">Belum ada produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
