<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Kasir') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @php
        $me = auth()->user();
        $brand = \App\Models\Setting::current()?->business_name ?? config('app.name');
        $logo = file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : null;
        $nav = [
            ['Kasir', 'pos.index', 'pos.*', false, 'kasir'],
            ['Riwayat', 'orders.index', 'orders.*', false, 'riwayat'],
            ['Pelanggan', 'customers.index', 'customers.*', false, 'pelanggan'],
            ['Produk', 'products.index', 'products.*', true, 'produk'],
            ['Kategori', 'categories.index', 'categories.*', true, 'kategori'],
            ['Meja & QR', 'tables.index', 'tables.*', true, 'meja'],
            ['Laporan', 'reports.index', 'reports.*', true, 'laporan'],
            ['Pengeluaran', 'expenses.index', 'expenses.*', true, 'pengeluaran'],
            ['Akun', 'users.index', 'users.*', true, 'akun'],
        ];
    @endphp

    {{-- Sidebar --}}
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy-900 text-white transition-transform lg:translate-x-0 print:hidden">
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $brand }}"
                    class="h-9 w-9 rounded-lg bg-white object-contain p-1">
            @else
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 text-lg font-bold">
                    {{ mb_strtoupper(mb_substr($brand, 0, 1)) }}</div>
            @endif
            <div class="leading-tight">
                <div class="font-bold">{{ $brand }}</div>
                <div class="text-xs text-slate-300">Aplikasi Kasir POS</div>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3 text-sm">
            @foreach ($nav as [$label, $route, $pattern, $adminOnly, $icon])
                @if (!$adminOnly || $me->isAdmin())
                    <a href="{{ route($route) }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs($pattern) ? 'bg-blue-600 font-semibold text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <x-icon :name="$icon" />{{ $label }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="space-y-1 border-t border-white/10 p-3 text-sm">
            <a href="{{ route('password.edit') }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-slate-300 hover:bg-white/10 hover:text-white">
                <x-icon name="akun" />Ganti password
            </a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-slate-300 hover:bg-white/10 hover:text-white">
                    <x-icon name="keluar" />Keluar
                </button>
            </form>
        </div>
    </aside>
    <div id="backdrop" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden print:hidden"></div>

    <div class="lg:pl-64 print:!pl-0">
        {{-- Bar atas --}}
        <header
            class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 print:hidden">
            <button id="menuBtn" type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                aria-label="Buka menu">
                <x-icon name="menu" class="h-6 w-6" />
            </button>
            <div class="text-sm text-slate-500">Selamat datang, <b class="text-slate-900">{{ $me->name }}</b></div>
            <div class="ml-auto flex items-center gap-2 text-sm">
                <span
                    class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold capitalize text-blue-700">{{ $me->role }}</span>
                <div class="grid h-9 w-9 place-items-center rounded-full bg-slate-200 font-bold text-slate-600">
                    {{ mb_strtoupper(mb_substr($me->name, 0, 1)) }}</div>
            </div>
        </header>

        <main class="p-4 lg:p-6">
            @if (session('success'))
                <div
                    class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 print:hidden">
                    {{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div
                    class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 print:hidden">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');
        const toggleMenu = open => {
            sidebar.classList.toggle('-translate-x-full', !open);
            backdrop.classList.toggle('hidden', !open);
        };
        document.getElementById('menuBtn').addEventListener('click', () => toggleMenu(true));
        backdrop.addEventListener('click', () => toggleMenu(false));
    </script>
</body>

</html>
