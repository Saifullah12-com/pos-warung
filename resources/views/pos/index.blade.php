@extends('layouts.app')
@section('title', 'Kasir')
@section('content')
    <form id="posForm" method="POST" action="{{ route('pos.store') }}" class="grid gap-4 lg:grid-cols-3">
        @csrf
        {{-- Kiri: daftar menu --}}
        <section class="lg:col-span-2">
            <div id="chips" class="mb-3 flex flex-wrap gap-2"></div>
            <div id="grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4"></div>
        </section>

        {{-- Kanan: pesanan dan pembayaran --}}
        <aside class="space-y-3 rounded-xl bg-white p-4 shadow-sm lg:sticky lg:top-20 lg:self-start">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Pesanan</h2>
                <div class="text-right text-xs text-slate-500">Nomor antrian
                    <div class="text-2xl font-bold text-slate-900">{{ $nextQueue }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                @foreach (['dine_in' => 'Dine-in', 'takeaway' => 'Takeaway'] as $val => $label)
                    <label>
                        <input type="radio" name="order_type" value="{{ $val }}" class="peer sr-only"
                            @checked(old('order_type', 'dine_in') === $val)>
                        <span
                            class="block cursor-pointer rounded-lg border border-slate-300 px-3 py-2 text-center text-sm peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div id="cart" class="divide-y divide-slate-100 text-sm"></div>
            <div id="hiddenItems"></div>

            <div class="flex items-center justify-between border-t pt-3">
                <span>Total</span><span id="total" class="text-xl font-bold">Rp0</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                @foreach (['tunai' => 'Tunai', 'qris' => 'QRIS', 'hutang' => 'Hutang'] as $val => $label)
                    <label>
                        <input type="radio" name="method" value="{{ $val }}" class="peer sr-only"
                            @checked(old('method', 'tunai') === $val)>
                        <span
                            class="block cursor-pointer rounded-lg border border-slate-300 px-3 py-2 text-center text-sm peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div id="cashBox" class="space-y-2">
                <x-input name="amount_received" label="Uang diterima" type="number" min="0" step="1"
                    inputmode="numeric" id="received" />
                <div class="flex flex-wrap gap-1.5" id="quick">
                    <x-btn variant="secondary" class="!px-2 !py-1 !text-xs" data-cash="exact">Uang pas</x-btn>
                    @foreach ([20000, 50000, 100000] as $n)
                        <x-btn variant="secondary" class="!px-2 !py-1 !text-xs"
                            data-cash="{{ $n }}">{{ number_format($n, 0, ',', '.') }}</x-btn>
                    @endforeach
                </div>
                <div class="flex justify-between text-sm"><span>Kembalian</span><b id="change">-</b></div>
            </div>
            <div class="space-y-2 rounded-lg bg-slate-50 p-3">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-slate-700">Pelanggan <span id="custHint"
                            class="font-normal text-slate-500"></span></span>
                    <select name="customer_id" id="customer"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"></select>
                </label>
                <button type="button" id="newCustToggle" class="text-xs text-blue-600 hover:underline">+ Pelanggan
                    baru</button>
                <div id="newCust" class="hidden space-y-2">
                    <input id="ncName" placeholder="Nama"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                    <input id="ncPhone" placeholder="Telepon (opsional)"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                    <x-btn id="ncSave" variant="secondary" class="w-full !py-1.5 !text-xs">Simpan pelanggan</x-btn>
                </div>
                <div id="debtInfo" class="hidden space-y-0.5 text-sm"></div>
                <p class="text-xs text-slate-500">Bayar sebagian: pilih pelanggan, pilih Tunai, isi uang kurang dari total.
                    Sisanya jadi hutang.</p>
            </div>
            <div id="qrisBox" class="hidden space-y-2">
                <x-input name="reference_no" label="No. referensi QRIS (opsional)" />
                <p class="text-xs text-slate-500">Pastikan notifikasi pembayaran sudah masuk sebelum menekan Bayar.</p>
            </div>

            <x-btn type="submit" id="payBtn" class="w-full" disabled>Bayar</x-btn>
        </aside>
    </form>

    <script>
        (() => {
            const PRODUCTS = @json($products);
            const CATEGORIES = @json($categories);
            const OLD_ITEMS = @json(old('items', []));
            const CUSTOMERS = @json($customers);
            const OLD_CUSTOMER = @json(old('customer_id'));

            const $ = id => document.getElementById(id);
            const rp = n => 'Rp' + Number(n).toLocaleString('id-ID');
            const esc = s => String(s).replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
            const byId = Object.fromEntries(PRODUCTS.map(p => [p.id, p]));

            const cart = {}; // { idProduk: jumlah }
            let activeCat = 'all';

            // Pulihkan keranjang bila server menolak (misal uang kurang)
            Object.entries(OLD_ITEMS).forEach(([id, q]) => {
                if (byId[id] && byId[id].available) cart[id] = Math.min(99, parseInt(q) || 1);
            });

            const method = () => document.querySelector('input[name=method]:checked').value;
            const total = () => Object.entries(cart).reduce((s, [id, q]) => s + byId[id].price * q, 0);

            const currentCustomer = () => CUSTOMERS.find(c => String($('customer').value) === String(c.id));

            function renderCustomers(selectedId = '') {
                $('customer').innerHTML = '<option value="">- tanpa pelanggan -</option>' + CUSTOMERS.map(c =>
                    `<option value="${c.id}" ${String(c.id) === String(selectedId) ? 'selected' : ''}>${esc(c.name)}${c.phone ? ' (' + esc(c.phone) + ')' : ''}${c.balance > 0 ? ' - hutang ' + rp(c.balance) : ''}</option>`
                ).join('');
            }

            function renderChips() {
                const chips = [{
                    id: 'all',
                    name: 'Semua'
                }, ...CATEGORIES];
                $('chips').innerHTML = chips.map(c =>
                    `<button type="button" data-cat="${c.id}" class="rounded-full border px-4 py-1.5 text-sm ${String(c.id) === String(activeCat) ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white'}">${esc(c.name)}</button>`
                ).join('');
            }

            function renderGrid() {
                const list = PRODUCTS.filter(p => activeCat === 'all' || String(p.category_id) === String(activeCat));
                $('grid').innerHTML = list.map(p => `
            <button type="button" data-id="${p.id}" ${p.available ? '' : 'disabled'}
                class="rounded-xl border border-slate-200 bg-white p-2 text-left shadow-sm transition hover:border-blue-600 disabled:opacity-40">
                ${p.photo
                    ? `<img src="${esc(p.photo)}" alt="" class="mb-2 h-24 w-full rounded-lg object-cover">`
                    : `<div class="mb-2 flex h-24 items-center justify-center rounded-lg bg-slate-100 text-3xl font-bold text-slate-400">${esc(p.name.charAt(0).toUpperCase())}</div>`}
                <div class="text-sm font-semibold leading-tight">${esc(p.name)}</div>
                <div class="text-xs text-slate-500">${p.available ? rp(p.price) : 'Habis'}</div>
            </button>`).join('') || '<p class="text-sm text-slate-500">Belum ada menu.</p>';
            }

            function renderCart() {
                const entries = Object.entries(cart);
                $('cart').innerHTML = entries.length ? entries.map(([id, q]) => `
            <div class="flex items-center justify-between gap-2 py-2">
                <div><div class="font-medium">${esc(byId[id].name)}</div><div class="text-xs text-slate-500">${rp(byId[id].price)}</div></div>
                <div class="flex items-center gap-2">
                    <button type="button" data-act="dec" data-id="${id}" class="h-7 w-7 rounded border border-slate-300">-</button>
                    <span class="w-5 text-center">${q}</span>
                    <button type="button" data-act="inc" data-id="${id}" class="h-7 w-7 rounded border border-slate-300">+</button>
                </div>
            </div>`).join('') : '<p class="py-3 text-slate-500">Belum ada item. Pilih menu di sebelah kiri.</p>';

                // Input tersembunyi yang dikirim ke server: items[ID]=jumlah
                $('hiddenItems').innerHTML = entries.map(([id, q]) =>
                    `<input type="hidden" name="items[${id}]" value="${q}">`).join('');
                $('total').textContent = rp(total());
                refreshPayment();
            }

            function refreshPayment() {
                const t = total();
                const m = method();
                const received = parseInt($('received').value) || 0;
                const cust = currentCustomer();

                $('cashBox').classList.toggle('hidden', m !== 'tunai');
                $('qrisBox').classList.toggle('hidden', m !== 'qris');
                $('received').disabled = m !== 'tunai'; // input nonaktif tidak ikut terkirim ke server
                $('custHint').textContent = m === 'hutang' ? '(wajib)' : '(opsional)';

                // Bagian transaksi ini yang menjadi hutang
                let debtNow = 0;
                if (m === 'hutang') debtNow = t;
                else if (m === 'tunai' && cust && received > 0 && received < t) debtNow = t - received;

                $('change').textContent = (m === 'tunai' && t > 0 && received >= t) ? rp(received - t) : '-';

                const box = $('debtInfo');
                if (cust) {
                    box.classList.remove('hidden');
                    box.innerHTML =
                        `<div class="flex justify-between"><span>Hutang sebelumnya</span><b>${rp(cust.balance)}</b></div>` +
                        (debtNow > 0 ?
                            `<div class="flex justify-between"><span>Hutang transaksi ini</span><b>${rp(debtNow)}</b></div>
                   <div class="flex justify-between border-t pt-1 text-red-700"><span>Total hutang</span><b>${rp(cust.balance + debtNow)}</b></div>` :
                            '');
                } else {
                    box.classList.add('hidden');
                }

                let ok = t > 0;
                if (m === 'hutang') ok = ok && !!cust;
                else if (m === 'tunai') ok = ok && (received >= t || (!!cust && received > 0));
                $('payBtn').disabled = !ok;
            }

            $('chips').addEventListener('click', e => {
                const b = e.target.closest('button[data-cat]');
                if (!b) return;
                activeCat = b.dataset.cat;
                renderChips();
                renderGrid();
            });

            $('grid').addEventListener('click', e => {
                const b = e.target.closest('button[data-id]');
                if (!b) return;
                cart[b.dataset.id] = Math.min(99, (cart[b.dataset.id] || 0) + 1);
                renderCart();
            });

            $('cart').addEventListener('click', e => {
                const b = e.target.closest('button[data-act]');
                if (!b) return;
                const id = b.dataset.id;
                cart[id] += b.dataset.act === 'inc' ? 1 : -1;
                if (cart[id] <= 0) delete cart[id];
                if (cart[id] > 99) cart[id] = 99;
                renderCart();
            });

            $('quick').addEventListener('click', e => {
                const b = e.target.closest('button[data-cash]');
                if (!b) return;
                $('received').value = b.dataset.cash === 'exact' ? total() : b.dataset.cash;
                refreshPayment();
            });

            $('received').addEventListener('input', refreshPayment);
            $('customer').addEventListener('change', refreshPayment);
            $('newCustToggle').addEventListener('click', () => $('newCust').classList.toggle('hidden'));

            $('ncSave').addEventListener('click', async () => {
                const name = $('ncName').value.trim();
                if (!name) return;

                const res = await fetch(@json(route('customers.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('#posForm input[name=_token]').value,
                    },
                    body: JSON.stringify({
                        name,
                        phone: $('ncPhone').value.trim() || null
                    }),
                });

                if (!res.ok) {
                    alert('Gagal menyimpan pelanggan. Periksa nama dan telepon.');
                    return;
                }

                const c = await res.json();
                CUSTOMERS.push(c);
                renderCustomers(c.id);
                $('ncName').value = '';
                $('ncPhone').value = '';
                $('newCust').classList.add('hidden');
                refreshPayment();
            });
            document.querySelectorAll('input[name=method]').forEach(r => r.addEventListener('change', refreshPayment));
            $('posForm').addEventListener('submit', () => {
                $('payBtn').disabled = true;
            }); // cegah klik ganda

            renderCustomers(OLD_CUSTOMER);
            renderChips();
            renderGrid();
            renderCart();
        })();
    </script>
@endsection
