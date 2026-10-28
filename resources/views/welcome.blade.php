@php
    $brand = $setting?->business_name ?? config('app.name');
    $initial = mb_strtoupper(mb_substr($brand, 0, 1));
    $logo = file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : null;
    $photo = file_exists(public_path('images/warung.jpg')) ? asset('images/warung.jpg') : null;
    $perks = [
        [
            'produk',
            'Makan di tempat atau bungkus',
            'Pilih dine-in atau takeaway. Pesanan langsung dicatat dan mendapat nomor antrian.',
        ],
        [
            'kasir',
            'Bayar tunai atau QRIS',
            'Kembalian dihitung otomatis, dan pelanggan langganan bisa mencatat kasbon.',
        ],
        ['riwayat', 'Antrian yang jelas', 'Nomor antrian tercetak besar di struk, jadi pesanan mudah dipanggil.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} - Aplikasi Kasir POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-900 antialiased">
    {{-- Header menempel --}}
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-6">
            <a href="#beranda" class="flex items-center gap-3 font-bold text-navy-900">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brand }}" class="h-9 w-9 rounded-lg object-contain">
                @else
                    <div class="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 text-white">{{ $initial }}
                    </div>
                @endif
                <span class="text-lg">{{ $brand }}</span>
            </a>
            <nav class="ml-auto hidden gap-1 text-sm font-medium text-slate-600 md:flex">
                @foreach (['#tentang' => 'Tentang', '#menu' => 'Menu', '#lokasi' => 'Lokasi'] as $href => $label)
                    <a href="{{ $href }}"
                        class="rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-navy-900">{{ $label }}</a>
                @endforeach
            </nav>
            <a href="{{ route('login') }}"
                class="ml-auto rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 md:ml-3">Masuk</a>
        </div>
    </header>

    {{-- Hero --}}
    <section id="beranda" class="relative overflow-hidden bg-navy-900 text-white">
        <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl">
        </div>
        <div class="pointer-events-none absolute -bottom-40 -left-32 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl">
        </div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:py-24">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Aplikasi Kasir POS</p>
                <h1 class="mt-3 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                    Makan enak, <span class="text-blue-400">pesan mudah</span>, bayar cepat</h1>
                <p class="mt-5 max-w-lg text-lg text-slate-300">{{ $brand }} melayani makan di tempat maupun
                    bungkus,
                    dengan pembayaran tunai dan QRIS.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#menu"
                        class="rounded-xl bg-blue-600 px-7 py-3 font-semibold transition hover:bg-blue-500">Lihat
                        Menu</a>
                    <a href="{{ route('login') }}"
                        class="rounded-xl border border-white/30 px-7 py-3 font-semibold transition hover:bg-white/10">Masuk
                        ke aplikasi</a>
                </div>
                <div class="mt-8 flex flex-wrap gap-2 text-sm text-slate-200">
                    @foreach (['Dine-in dan takeaway', 'Tunai dan QRIS', 'Nomor antrian di struk'] as $tag)
                        <span class="rounded-full bg-white/10 px-4 py-1.5">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>

            @if ($featured->isNotEmpty())
                <div class="rounded-3xl bg-navy-800 p-6">
                    @foreach ($featured as $p)
                        <div
                            class="flex items-center gap-4 rounded-2xl bg-white/10 p-3 {{ $loop->first ? '' : 'mt-3' }}">
                            @if ($p->photo_path)
                                <img src="{{ asset('storage/' . $p->photo_path) }}" alt=""
                                    class="h-14 w-14 rounded-xl object-cover">
                            @else
                                <div class="grid h-14 w-14 place-items-center rounded-xl bg-blue-600 text-xl font-bold">
                                    {{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</div>
                            @endif
                            <div>
                                <div class="font-semibold">{{ $p->name }}</div>
                                @if ($showPrice)
                                    <div class="text-sm text-blue-300">@rupiah($p->price)</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Tentang --}}
    <section id="tentang" class="scroll-mt-16 py-20">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Tentang kami</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-navy-900 sm:text-4xl">Dapur sederhana, <span
                        class="text-blue-600">dilayani sepenuh hati</span></h2>
                <p class="mt-4 text-slate-600">
                    {{ $setting?->about ?: 'Kami melayani makan di tempat dan bungkus. Silakan lihat menu kami di bawah.' }}
                </p>
                <ul class="mt-6 space-y-3 text-slate-700">
                    @foreach (['Makan di tempat dan bungkus', 'Pembayaran tunai dan QRIS', 'Nomor antrian di setiap struk'] as $item)
                        <li class="flex items-center gap-3">
                            <span
                                class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">&#10003;</span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @if ($photo)
                <img src="{{ $photo }}" alt="{{ $brand }}" class="h-80 w-full rounded-3xl object-cover">
            @else
                <div class="grid h-80 place-items-center rounded-3xl bg-blue-50 text-8xl font-bold text-blue-200">
                    {{ $initial }}</div>
            @endif
        </div>
    </section>

    {{-- Keunggulan --}}
    <section class="bg-white py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Kenapa makan di sini</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-navy-900 sm:text-4xl">Layanan yang <span
                        class="text-blue-600">rapi dan cepat</span></h2>
                <p class="mx-auto mt-3 max-w-xl text-slate-600">Pesanan dicatat langsung di kasir, jadi tidak ada yang
                    terlewat.</p>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ($perks as [$icon, $title, $text])
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="grid h-12 w-12 place-items-center rounded-full bg-blue-100 text-blue-600">
                            <x-icon :name="$icon" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-navy-900">{{ $title }}</h3>
                        <p class="mt-2 text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Menu (otomatis dari data produk) --}}
    <section id="menu" class="scroll-mt-16 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Menu</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-navy-900 sm:text-4xl">Menu <span
                        class="text-blue-600">pilihan kami</span></h2>
            </div>

            @if ($categories->isEmpty())
                <p class="mt-10 text-center text-slate-500">Menu segera hadir.</p>
            @else
                @if ($categories->count() > 1)
                    <div class="mt-8 flex flex-wrap justify-center gap-2" id="chips">
                        <button type="button" data-chip="all"
                            class="rounded-full border border-blue-600 bg-blue-600 px-5 py-2 text-sm font-medium text-white">Semua</button>
                        @foreach ($categories as $c)
                            <button type="button" data-chip="{{ $c->id }}"
                                class="rounded-full border border-slate-300 bg-white px-5 py-2 text-sm font-medium text-slate-700">{{ $c->name }}</button>
                        @endforeach
                    </div>
                @endif

                @foreach ($categories as $c)
                    <div data-group="{{ $c->id }}" class="mt-10">
                        <h3 class="mb-4 text-xl font-semibold text-navy-900">{{ $c->name }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($c->products as $p)
                                <div
                                    class="rounded-2xl border border-slate-200 bg-white p-3 {{ $p->is_available ? '' : 'opacity-60' }}">
                                    @if ($p->photo_path)
                                        <img src="{{ asset('storage/' . $p->photo_path) }}" alt="{{ $p->name }}"
                                            class="h-32 w-full rounded-xl object-cover">
                                    @else
                                        <div
                                            class="grid h-32 place-items-center rounded-xl bg-blue-50 text-4xl font-bold text-blue-300">
                                            {{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</div>
                                    @endif
                                    <div class="mt-3 px-1">
                                        <div class="font-semibold text-navy-900">{{ $p->name }}</div>
                                        @if (!$p->is_available)
                                            <div class="text-sm font-semibold text-red-600">Habis</div>
                                        @elseif ($showPrice)
                                            <div class="font-semibold text-blue-600">@rupiah($p->price)</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- Lokasi --}}
    <section id="lokasi" class="scroll-mt-16 bg-white py-20">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Lokasi</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-navy-900 sm:text-4xl">Datang dan <span
                        class="text-blue-600">mampir</span></h2>
                <div class="mt-6 space-y-3">
                    @foreach ([['lokasi', 'Alamat', $setting?->address], ['telepon', 'Telepon', $setting?->phone], ['riwayat', 'Jam buka', $setting?->opening_hours]] as [$icon, $label, $value])
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 p-5">
                            <div
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-100 text-blue-600">
                                <x-icon :name="$icon" />
                            </div>
                            <div>
                                <div class="text-sm text-slate-500">{{ $label }}</div>
                                <div class="font-semibold text-navy-900">{{ $value ?: '-' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-3xl bg-navy-900 p-10 text-white">
                <h3 class="text-2xl font-bold">Mampir ke warung kami</h3>
                <p class="mt-3 text-slate-300">Makan di tempat atau bawa pulang, kami siap melayani.</p>
                @if ($setting?->address)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($setting->address) }}"
                        target="_blank" rel="noopener"
                        class="mt-6 inline-flex rounded-xl bg-blue-600 px-7 py-3 font-semibold transition hover:bg-blue-500">Buka
                        di Google Maps</a>
                @endif
            </div>
        </div>
    </section>

    <footer class="bg-navy-900 py-8 text-sm text-slate-300">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-6">
            <div>&copy; {{ date('Y') }} {{ $brand }}</div>
            <a href="{{ route('login') }}" class="text-white hover:underline">Masuk untuk kasir dan admin</a>
        </div>
    </footer>

    <script>
        // Filter kategori menu
        const chips = document.querySelectorAll('[data-chip]');
        const on = ['bg-blue-600', 'border-blue-600', 'text-white'];
        const off = ['bg-white', 'border-slate-300', 'text-slate-700'];

        chips.forEach(chip => chip.addEventListener('click', () => {
            const cat = chip.dataset.chip;

            chips.forEach(c => {
                const active = c === chip;
                c.classList.remove(...(active ? off : on));
                c.classList.add(...(active ? on : off));
            });

            document.querySelectorAll('[data-group]').forEach(g => {
                g.classList.toggle('hidden', cat !== 'all' && g.dataset.group !== cat);
            });
        }));
    </script>
</body>

</html>
