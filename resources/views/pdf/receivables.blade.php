<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Daftar Piutang</title>
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

        .line {
            border-top: 2px solid #0b1b3d;
            margin: 10px 0 12px;
        }

        .list th {
            background: #0b1b3d;
            color: #fff;
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }

        .list td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .list tr.alt td {
            background: #f8fafc;
        }

        .list thead {
            display: table-header-group;
        }

        .list tr {
            page-break-inside: avoid;
        }

        .list .total td {
            border-top: 2px solid #0b1b3d;
            font-weight: bold;
            font-size: 11px;
        }

        .red {
            color: #b91c1c;
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
                <div style="font-size: 13px; font-weight: bold; color: #0b1b3d;">DAFTAR PIUTANG PELANGGAN</div>
                <div class="muted">Per {{ now()->format('d/m/Y H:i') }}<br>Dicetak oleh: {{ $printedBy }}</div>
            </td>
        </tr>
    </table>
    <div class="line"></div>

    <table class="list">
        <thead>
            <tr>
                <th style="width: 6%">No</th>
                <th style="width: 34%">Pelanggan</th>
                <th style="width: 20%">Telepon</th>
                <th style="width: 18%">Terakhir berhutang</th>
                <th style="width: 22%" class="right">Hutang</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $c)
                <tr class="{{ $loop->odd ? '' : 'alt' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td><b>{{ $c->name }}</b></td>
                    <td>{{ $c->phone ?: '-' }}</td>
                    <td>{{ $c->last_debt_at ? \Carbon\Carbon::parse($c->last_debt_at)->format('d/m/Y') : '-' }}</td>
                    <td class="right red">@rupiah($c->balance)</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 16px;" class="muted">Tidak ada piutang.</td>
                </tr>
            @endforelse
            @if ($customers->isNotEmpty())
                <tr class="total">
                    <td colspan="4" class="right">TOTAL PIUTANG</td>
                    <td class="right red">@rupiah($total)</td>
                </tr>
            @endif
        </tbody>
    </table>
    <p class="muted" style="margin-top: 14px; font-size: 8px;">Dokumen ini dicetak dari sistem kasir.</p>
</body>

</html>
