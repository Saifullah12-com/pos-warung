@extends('layouts.app')
@section('title', 'Meja & QR')
@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold">Meja dan kode QR</h1>
            <p class="text-sm text-slate-500">Tempel satu QR di setiap meja. Pelanggan memindainya untuk melihat menu.</p>
        </div>
        <x-btn :href="route('tables.print')" target="_blank">Cetak semua QR</x-btn>
    </div>

    <div class="mb-6 grid max-w-3xl gap-4 sm:grid-cols-2">
        <form method="POST" action="{{ route('tables.store') }}"
            class="flex items-end gap-2 rounded-xl bg-white p-4 shadow-sm">
            @csrf
            <div class="flex-1"><x-input name="name" label="Tambah satu meja" placeholder="Contoh: Meja 1 atau Teras"
                    required /></div>
            <x-btn type="submit">Tambah</x-btn>
        </form>
        <form method="POST" action="{{ route('tables.bulk') }}"
            class="flex items-end gap-2 rounded-xl bg-white p-4 shadow-sm">
            @csrf
            <div class="flex-1"><x-input name="count" label="Buat otomatis (jumlah meja)" type="number" min="1"
                    max="50" required /></div>
            <x-btn type="submit" variant="secondary">Buat</x-btn>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($tables as $row)
            @php $t = $row['table']; @endphp
            <div class="rounded-xl bg-white p-4 text-center shadow-sm {{ $t->is_active ? '' : 'opacity-60' }}">
                <div class="font-semibold">{{ $t->name }}</div>
                <div class="text-xs {{ $t->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                    {{ $t->is_active ? 'Aktif' : 'Nonaktif' }}</div>
                {{-- SVG dibuat sendiri oleh server dari alamat kita, jadi aman ditampilkan apa adanya --}}
                <div class="mx-auto my-3 w-36 [&>svg]:h-auto [&>svg]:w-full">{!! $row['qr'] !!}</div>
                <a href="{{ $t->publicUrl() }}" target="_blank"
                    class="block truncate text-xs text-blue-600 hover:underline">{{ $t->publicUrl() }}</a>
                <div class="mt-3 flex justify-center gap-4 text-sm">
                    <form method="POST" action="{{ route('tables.toggle', $t) }}">@csrf @method('PATCH')
                        <button
                            class="text-slate-600 hover:underline">{{ $t->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </form>
                    <form method="POST" action="{{ route('tables.regenerate', $t) }}"
                        onsubmit="return confirm('Ganti kode QR {{ $t->name }}? QR yang sudah dicetak tidak akan berlaku lagi.')">
                        @csrf @method('PATCH')
                        <button class="text-blue-600 hover:underline">Ganti kode</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="col-span-full rounded-xl bg-white p-6 text-center text-slate-500">Belum ada meja. Tambahkan di atas.
            </p>
        @endforelse
    </div>
@endsection
