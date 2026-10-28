<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rincian Hutang {{ $customer->name }}</title>
    <style>
        @page {
            margin: 16mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            vertical-align: top;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 3px;
            color: #0b1b3d;
        }

        .muted {
            color: #64748b;
        }

        .right {
            text-align: right;
        }

        .title {
            font-size: 13px;
            font-weight: bold;
            color: #0b1b3d;
        }

        .line {
            border-top: 2px solid #0b1b3d;
            margin: 10px 0 12px;
        }

        .box {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }

        .ledger {
            margin-top: 12px;
        }

        .ledger th {
            background: #0b1b3d;
            color: #fff;
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }

        .ledger td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .ledger tr.alt td {
            background: #f8fafc;
        }

        .ledger thead {
            display: table-header-group;
        }

        .ledger tr {
            page-break-inside: avoid;
        }

        .ledger tr.total td {
            border-top: 1.5px solid #0b1b3d;
            border-bottom: 1.5px solid #0b1b3d;
            padding: 8px 6px;
            font-size: 11px;
            background: #ffffff;
        }

        .ledger tr.total td.tl {
            border-left: 1.5px solid #0b1b3d;
        }

        .ledger tr.total td.tr {
            border-right: 1.5px solid #0b1b3d;
        }

        .red {
            color: #b91c1c;
        }

        .green {
            color: #047857;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td style="width: 60%">
                <h1>{{ $setting?->business_name ?? config('app.name') }}</h1>
                <div class="muted">{{ $setting?->address }}<br>{{ $setting?->phone }}</div>
            </td>
            <td class="right">
                <div class="title">RINCIAN HUTANG PELANGGAN</div>
                <div class="muted">Dicetak: {{ now()->format('d/m/Y H:i') }}<br>Oleh: {{ $printedBy }}</div>
            </td>
        </tr>
    </table>
    <div class="line"></div>

    <table>
        <tr>
            <td style="width: 48%" class="box">
                <div class="muted">Pelanggan</div>
                <div style="font-size: 13px; font-weight: bold;">{{ $customer->name }}</div>
                <div>{{ $customer->phone ?: '-' }}</div>
                <div class="muted" style="margin-top: 6px;">Periode</div>
                <div>
                    @if ($from || $to)
                        {{ $from ? $from->format('d/m/Y') : 'Awal' }} s.d. {{ $to ? $to->format('d/m/Y') : 'Sekarang' }}
                    @else
                        Semua riwayat
                    @endif
                </div>
            </td>
            <td></td>
        </tr>
    </table>

    <p style="margin: 10px 0 0;">
        <b>Hutang saat ini (per {{ now()->format('d/m/Y') }}):</b>
        <span class="{{ $current > 0 ? 'red' : '' }}" style="font-size: 12px; font-weight: bold;">
            @rupiah(abs($current)){{ $current < 0 ? ' (kelebihan bayar)' : '' }}</span>
    </p>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width: 13%">Tanggal</th>
                <th style="width: 31%">Keterangan</th>
                <th style="width: 20%">Pesanan</th>
                <th style="width: 12%" class="right">Hutang (+)</th>
                <th style="width: 12%" class="right">Bayar / Batal (-)</th>
                <th style="width: 12%" class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="alt">
                <td colspan="5"><i>Saldo awal</i></td>
                <td class="right">@rupiah($opening)</td>
            </tr>
            @forelse ($rows as $r)
                <tr class="{{ $loop->odd ? '' : 'alt' }}">
                    <td>{{ $r['date']->format('d/m/Y') }}<br><span
                            class="muted">{{ $r['date']->format('H:i') }}</span></td>
                    <td>{{ $r['label'] }}</td>
                    <td>{{ $r['items'] }}</td>
                    <td class="right red">{{ $r['debit'] ? 'Rp' . number_format($r['debit'], 0, ',', '.') : '-' }}</td>
                    <td class="right green">{{ $r['credit'] ? 'Rp' . number_format($r['credit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="right">@rupiah($r['balance'])</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 14px;" class="muted">Tidak ada catatan pada
                        periode ini.</td>
                </tr>
            @endforelse
            <tr class="total">
                <td colspan="5" class="tl">Total</td>
                <td class="right tr">@rupiah(abs($closing)){{ $closing < 0 ? ' (lebih)' : '' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top: 14px; font-size: 8px;">Dokumen ini dicetak dari sistem kasir.</p>
</body>

</html>
