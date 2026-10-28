<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak QR Meja</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            color: #0f172a;
            background: #f1f5f9;
        }

        .bar {
            padding: 12px 16px;
            background: #fff;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .bar button {
            padding: 8px 18px;
            border-radius: 8px;
            border: 0;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .sheet {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8mm;
            padding: 10mm;
        }

        .card {
            background: #fff;
            border: 2px dashed #94a3b8;
            border-radius: 6mm;
            padding: 8mm;
            text-align: center;
            break-inside: avoid;
        }

        .brand {
            font-weight: 700;
            font-size: 16pt;
        }

        .meja {
            font-size: 28pt;
            font-weight: 800;
            margin: 2mm 0;
        }

        .qr svg {
            width: 60mm;
            height: 60mm;
        }

        .hint {
            font-size: 11pt;
            color: #475569;
            margin-top: 2mm;
        }

        @media print {
            .bar {
                display: none;
            }

            body {
                background: #fff;
            }

            .sheet {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="bar">
        <button onclick="window.print()">Cetak</button>
        <span>Gunting setiap kartu di garis putus-putus, lalu tempel di mejanya.</span>
    </div>
    <div class="sheet">
        @foreach ($cards as $c)
            <div class="card">
                <div class="brand">{{ $brand }}</div>
                <div class="meja">{{ strtoupper($c['name']) }}</div>
                <div class="qr">{!! $c['qr'] !!}</div>
                <div class="hint">Pindai untuk melihat menu</div>
            </div>
        @endforeach
    </div>
</body>

</html>
