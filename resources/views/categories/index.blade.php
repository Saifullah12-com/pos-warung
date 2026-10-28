@extends('layouts.app')
@section('title', 'Kategori')
@section('content')
    <h1 class="mb-4 text-lg font-bold">Kategori</h1>

    <form method="POST" action="{{ route('categories.store') }}" class="mb-5 flex max-w-md items-end gap-2">
        @csrf
        <div class="flex-1"><x-input name="name" label="Kategori baru" required /></div>
        <x-btn type="submit">Tambah</x-btn>
    </form>

    <div class="max-w-2xl divide-y rounded-xl bg-white shadow-sm">
        @foreach ($categories as $c)
            <div class="flex flex-wrap items-center gap-3 p-3 {{ $c->is_active ? '' : 'text-slate-400' }}">
                <form method="POST" action="{{ route('categories.update', $c) }}" class="flex flex-1 items-center gap-2">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $c->name }}" required maxlength="50"
                        class="w-full max-w-xs rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900">
                    <button class="text-sm text-blue-600 hover:underline">Simpan nama</button>
                </form>
                <span class="text-xs">{{ $c->products_count }} produk</span>
                <form method="POST" action="{{ route('categories.toggle', $c) }}">@csrf @method('PATCH')
                    <button
                        class="text-sm text-slate-600 hover:underline">{{ $c->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
            </div>
        @endforeach
    </div>
    <p class="mt-3 max-w-2xl text-xs text-slate-500">Kategori nonaktif menyembunyikan semua produk di dalamnya dari halaman
        kasir.</p>
@endsection
