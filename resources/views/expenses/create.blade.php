@extends('layouts.app')
@section('title', 'Catat Pembelian')
@section('content')
    @php
        $inp =
            'w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600';
    @endphp
    <h1 class="text-lg font-bold">
        Catat pembelian</h1>
    <p class="mb-4 text-sm text-slate-500">Setiap pembelian yang disimpan otomatis masuk ke daftar pengeluaran dan laporan.
    </p>

    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" id="expForm"
        class="rounded-xl bg-white p-5 shadow-sm">
        @csrf

        <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="block text-sm">
                <span class="mb-1 block font-medium text-slate-700">Jenis</span>
                <select name="category" required class="{{ $inp }}">
                    @foreach ($categories as $c)
                        <option @selected(old('category') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </label>
            <x-input name="spent_at" label="Tanggal & waktu pembelian" type="datetime-local" :value="now()->format('Y-m-d\TH:i')"
                max="{{ now()->format('Y-m-d\TH:i') }}" required />
            <x-input name="supplier" label="Toko atau pemasok (opsional)" maxlength="100" />
            <div class="text-sm">
                <span class="mb-1 block font-medium text-slate-700">Dicatat oleh</span>
                <div class="flex min-h-11 items-center">{{ auth()->user()->name }} ({{ auth()->user()->role }})</div>
            </div>
        </div>

        <h2 class="mb-2 font-semibold">Barang yang dibeli</h2>

        {{-- Judul kolom (layar lebar) --}}
        <div
            class="mb-1 hidden grid-cols-[2.2fr_.8fr_.8fr_1.2fr_1.2fr_44px] gap-2 text-xs font-semibold text-slate-600 lg:grid">
            <span>Nama barang</span><span>Jumlah</span><span>Satuan</span><span>Harga per satuan
                (Rp)</span><span>Subtotal</span><span></span>
        </div>
        <div id="rows"></div>

        <button type="button" id="addRow" class="mb-5 mt-1 text-sm font-semibold text-blue-600 hover:underline">+ Tambah
            barang</button>

        <div class="flex flex-wrap items-end justify-between gap-4 border-t border-slate-200 pt-4">
            <label class="block text-sm">
                <span class="mb-1 block font-medium text-slate-700">Foto nota (opsional)</span>
                <input type="file" name="receipt" accept="image/*" class="block text-sm">
            </label>
            <div class="text-right">
                <div class="text-sm text-slate-500">Total pembelian</div>
                <div id="total" class="text-3xl font-bold">Rp0</div>
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <x-btn type="submit" id="saveBtn">Simpan pembelian</x-btn>
            <x-btn variant="secondary" :href="route('expenses.index')">Batal</x-btn>
        </div>
    </form>

    {{-- Saran otomatis dari pembelian sebelumnya --}}
    <datalist id="namaList">
        @foreach ($names as $n)
            <option value="{{ $n }}">
        @endforeach
    </datalist>
    <datalist id="satuanList">
        @foreach ($units as $u)
            <option value="{{ $u }}">
        @endforeach
    </datalist>

    {{-- Cetakan satu baris barang. __i__ diganti nomor urut oleh JavaScript --}}
    <template id="rowTpl">
        <div data-row
            class="mb-3 grid grid-cols-2 items-end gap-2 rounded-lg border border-slate-200 p-3 lg:mb-2 lg:grid-cols-[2.2fr_.8fr_.8fr_1.2fr_1.2fr_44px] lg:border-0 lg:p-0">
            <input name="items[__i__][name]" list="namaList" placeholder="Nama barang" aria-label="Nama barang"
                maxlength="100" required class="col-span-2 lg:col-span-1 {{ $inp }}">
            <input name="items[__i__][qty]" type="number" min="0.01" step="0.01" inputmode="decimal"
                placeholder="Jumlah" aria-label="Jumlah" required class="{{ $inp }}">
            <input name="items[__i__][unit]" list="satuanList" placeholder="Satuan" aria-label="Satuan" maxlength="20"
                required class="{{ $inp }}">
            <input name="items[__i__][unit_price]" type="number" min="1" step="1" inputmode="numeric"
                placeholder="Harga per satuan" aria-label="Harga per satuan" required class="{{ $inp }}">
            <div data-sub class="flex min-h-11 items-center text-sm font-semibold">Rp0</div>
            <button type="button" data-remove aria-label="Hapus baris"
                class="min-h-11 rounded-lg border border-slate-300 text-lg text-red-700 hover:bg-red-50">&times;</button>
        </div>
    </template>

    <script>
        (() => {
            const OLD = @json(old('items', [])); // isian sebelumnya bila server menolak
            const rows = document.getElementById('rows');
            const tpl = document.getElementById('rowTpl');
            const rp = v => 'Rp' + Math.round(v).toLocaleString('id-ID');
            let n = 0; // nomor urut baris, dipakai di nama input

            // Hitung subtotal tiap baris dan total. Ini hanya tampilan; server menghitung ulang.
            function recalc() {
                let total = 0;
                rows.querySelectorAll('[data-row]').forEach(r => {
                    const qty = parseFloat(r.querySelector('[name$="[qty]"]').value) || 0;
                    const price = parseFloat(r.querySelector('[name$="[unit_price]"]').value) || 0;
                    const sub = Math.round(qty * price);
                    total += sub;
                    r.querySelector('[data-sub]').textContent = rp(sub);
                });
                document.getElementById('total').textContent = rp(total);
            }

            function addRow(v = {}) {
                const el = tpl.content.firstElementChild.cloneNode(true);
                el.innerHTML = el.innerHTML.replaceAll('__i__', n++);
                el.querySelector('[name$="[name]"]').value = v.name ?? '';
                el.querySelector('[name$="[qty]"]').value = v.qty ?? '';
                el.querySelector('[name$="[unit]"]').value = v.unit ?? '';
                el.querySelector('[name$="[unit_price]"]').value = v.unit_price ?? '';
                rows.appendChild(el);
                return el;
            }

            document.getElementById('addRow').addEventListener('click', () => {
                addRow().querySelector('[name$="[name]"]').focus();
            });

            rows.addEventListener('input', recalc);

            rows.addEventListener('click', e => {
                const btn = e.target.closest('[data-remove]');
                if (!btn) return;
                const row = btn.closest('[data-row]');
                if (rows.children.length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input').forEach(i => i.value =
                    ''); // baris terakhir hanya dikosongkan
                }
                recalc();
            });

            document.getElementById('expForm').addEventListener('submit', () => {
                document.getElementById('saveBtn').disabled = true; // cegah klik ganda
            });

            const initial = Object.values(OLD);
            (initial.length ? initial : [{}]).forEach(addRow);
            recalc();
        })();
    </script>
@endsection
