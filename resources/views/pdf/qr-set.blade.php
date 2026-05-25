<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Cetak QR {{ $qrSet->code }}</title>
    <style>
        @page {
            margin: 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            color: #0f172a;
        }

        .sheet {
            page-break-after: always;
            min-height: 250mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .badge {
            padding: 8px 12px;
            border: 1px solid #1f2937;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin-bottom: 14px;
        }

        .title {
            font-size: 34px;
            margin: 0;
            letter-spacing: 0.04em;
        }

        .code {
            margin-top: 10px;
            font-size: 13px;
            color: #334155;
        }

        .qr {
            margin-top: 18px;
            width: 360px;
            height: 360px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 10px;
            background: #ffffff;
        }

        .url {
            margin-top: 14px;
            font-size: 10px;
            word-break: break-all;
            color: #334155;
        }

        .token {
            margin-top: 10px;
            font-size: 11px;
            word-break: break-all;
            color: #0f172a;
        }

        .foot {
            margin-top: 14px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>

<body>
    @foreach ($points as $point)
        <section class="sheet">
            <div class="badge">{{ $point['label'] }}</div>
            <h1 class="title">QR {{ $point['label'] }}</h1>
            <div class="code">Set: {{ $qrSet->code }}</div>

            <img class="qr" src="{{ $point['image_url'] }}" alt="QR {{ $point['label'] }}">

            <div class="url">{{ $point['scan_url'] }}</div>
            <div class="token">Token: {{ $point['token'] }}</div>

            <div class="foot">
                Dicetak: {{ now()->format('d-m-Y H:i') }} | Digunakan untuk titik {{ $point['label'] }}
            </div>
        </section>
    @endforeach
</body>

</html>
