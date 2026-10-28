@extends('layouts.app')
@section('title', $product->exists ? 'Ubah Produk' : 'Tambah Produk')
@section('content')
    <form method="POST" enctype="multipart/form-data"
        action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
        class="mx-auto max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif
        <h1 class="text-lg font-bold">{{ $product->exists ? 'Ubah produk' : 'Tambah produk' }}</h1>

        <x-input name="name" label="Nama produk" :value="$product->name" required />

        <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Kategori</span>
            <select name="category_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                <option value="">- pilih -</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((int) old('category_id', $product->category_id) === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </label>

        <x-input name="price" label="Harga (Rp)" type="number" min="0" step="1" inputmode="numeric"
            :value="$product->price" required />

        <div>
            <span class="mb-1 block text-sm font-medium text-slate-700">Foto (opsional, maks. 2 MB)</span>
            @if ($product->photo_path)
                <img src="{{ asset('storage/' . $product->photo_path) }}" alt=""
                    class="mb-2 h-24 w-24 rounded-lg object-cover">
            @endif
            <input type="file" name="photo" accept="image/*" class="block w-full text-sm">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_available" value="0">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $product->is_available))>
            Tersedia (hilangkan centang bila stok habis)
        </label>

        <div class="flex gap-2">
            <x-btn type="submit">Simpan</x-btn>
            <x-btn variant="secondary" :href="route('products.index')">Batal</x-btn>
        </div>
    </form>
@endsection
