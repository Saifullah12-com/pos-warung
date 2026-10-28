@extends('layouts.app')
@section('title', 'Struk ' . $order->order_no)
@push('head')
    <style>
        :root {
            --struk-w: 48mm;
        }

        /* lebar area cetak printer 58mm. Ubah di sini bila perlu */

        .struk {
            background: #fff;
            color: #000;
            padding: 20px;
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: 13px;
            line-height: 1.5;
        }

        .struk .c {
            text-align: center;
        }

        .struk .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }

        .struk .row span:first-child {
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .struk .row span:last-child {
            white-space: nowrap;
        }

        .struk hr {
            border: 0;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .struk .q {
            font-size: 44px;
            font-weight: 700;
            line-height: 1.1;
        }

        @media print {
            @page {
                margin: 0;
            }

            html,
            body {
                width: 58mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            main {
                padding: 0 !important;
            }

            .wrap {
                max-width: none !important;
                margin: 0 !important;
            }

            .struk {
                width: var(--struk-w);
                margin: 0 auto;
                padding: 2mm 0 6mm;
                font-size: 10px;
                line-height: 1.3;
                box-shadow: none;
            }

            .struk .q {
                font-size: 30px;
            }

            .noprint {
                display: none !important;
            }

            .struk {
                color: #000 !important;
                font-weight: 600;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .struk hr {
                border-top: 1px solid #000;
            }
        }
    </style>
@endpush
@section('content')
    <div class="wrap mx-auto max-w-sm">
        <div class="struk">
            <div class="c">
                <b>{{ strtoupper($setting?->business_name ?? config('app.name')) }}</b>
                @if ($setting?->address)
                    <div>{{ $setting->address }}</div>
                @endif
                @if ($setting?->phone)
                    <div>{{ $setting->phone }}</div>
                @endif
            </div>
            <hr>
            <div class="c">Nomor antrian
                <div class="q">{{ $order->queue_no }}</div>
            </div>
            <hr>
            <div>No : {{ $order->order_no }}</div>
            <div>Tgl: {{ $order->created_at->format('d/m/Y H:i') }}</div>
            <div>Kasir: {{ $order->user->name }}</div>
            <div>Jenis: {{ $order->order_type === 'dine_in' ? 'Dine-in' : 'Takeaway' }}</div>
            @if ($order->customer)
                <div>Pelanggan: {{ $order->customer->name }}</div>
            @endif
            <hr>
            @foreach ($order->items as $item)
                <div class="row">
                    <span>{{ $item->qty }}x {{ $item->product_name }}</span>
                    <span>@rupiah($item->subtotal)</span>
                </div>
            @endforeach
            <hr>
            <div class="row"><b>TOTAL</b><b>@rupiah($order->total)</b></div>
            @if ($order->payment)
                @php $m = $order->payment->method; @endphp
                <div class="row">
                    <span>{{ $m === 'tunai' ? 'Tunai' : ($m === 'qris' ? 'QRIS' : 'Hutang') }}</span><span>@rupiah($order->payment->amount_received)</span>
                </div>
                @if ($m !== 'hutang')
                    <div class="row"><span>Kembali</span><span>@rupiah($order->payment->change_amount)</span></div>
                @endif
                @if ($order->payment->debt_amount > 0)
                    <div class="row"><b>HUTANG</b><b>@rupiah($order->payment->debt_amount)</b></div>
                    <div class="row"><span>Total hutang saat ini</span><span>@rupiah($balance)</span></div>
                @endif
            @endif
            @if ($order->isCancelled())
                <div class="c" style="margin-top: 6px;"><b>*** TRANSAKSI DIBATALKAN ***</b></div>
            @endif
            @if ($setting?->receipt_footer)
                <hr>
                <div class="c">{{ $setting->receipt_footer }}</div>
            @endif
        </div>

        <div class="noprint mt-4 flex gap-2">
            <x-btn onclick="window.print()">Cetak struk</x-btn>
            <x-btn variant="secondary" :href="route('pos.index')">Pesanan baru</x-btn>
        </div>
    </div>
@endsection
