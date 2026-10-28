@php
    $setting = \App\Models\Setting::current();
    $brand = $setting?->business_name ?? config('app.name');
    $initial = mb_strtoupper(mb_substr($brand, 0, 1));
    $logo = file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : null;
    $bg = file_exists(public_path('images/warung.JPEG')) ? asset('images/warung.JPEG') : null;
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="text-slate-900 antialiased">
    <!--<div class="relative flex min-h-screen items-center justify-center bg-navy-900 bg-cover bg-center px-4 py-10 lg:justify-end lg:pr-[10%]"-->
    <div class="relative flex min-h-screen items-center justify-center bg-white bg-cover bg-center px-4 py-10"
        @if ($bg) style="background-image: url('{{ $bg }}')" @endif>

        {{-- Lapisan gelap: ubah angka /55 untuk mengatur seberapa redup fotonya --}}
        <div class="absolute inset-0 bg-navy-900/55"></div>

        <form method="POST" action="{{ route('login') }}"
            class="relative w-full max-w-sm space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
            @csrf

            <div class="flex items-center gap-3">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brand }}"
                        class="h-11 w-11 rounded-xl object-contain">
                @else
                    <div class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-lg font-bold text-white">
                        {{ $initial }}</div>
                @endif
                <div>
                    <h1 class="text-xl font-bold leading-tight text-navy-900">{{ $brand }}</h1>
                    <p class="text-sm text-slate-500">Masuk untuk memulai.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900">
                    {{ $errors->first() }}</div>
            @endif

            <x-input name="username" label="Username" autocomplete="username" autofocus required />
            <x-input name="password" label="Password" type="password" autocomplete="current-password" required />
            <x-btn type="submit" class="w-full">Masuk</x-btn>

            <a href="{{ route('home') }}" class="block text-center text-sm text-slate-500 hover:text-blue-600">&larr;
                Kembali ke beranda</a>
        </form>
    </div>

</body>

</html>
