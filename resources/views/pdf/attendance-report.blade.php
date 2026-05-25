<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rekap Absensi {{ $satpam->name }} - {{ $monthLabel }}</title>
    <style>
        @page {
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            color: #0f172a;
            font-size: 11px;
        }

        .header {
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 2px solid #16a34a;
        }

        .title {
            margin: 0;
            font-size: 24px;
        }

        .subtitle {
            margin: 4px 0 0;
            color: #475569;
        }

        .meta {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .meta td {
            padding: 3px 0;
            vertical-align: top;
        }

        .cards {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px;
        }

        .cards td {
            width: 25%;
            padding: 8px;
            vertical-align: top;
        }

        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px;
            min-height: 76px;
        }

        .card-label {
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 9px;
            font-weight: 700;
        }

        .card-value {
            margin-top: 8px;
            font-size: 17px;
            font-weight: 700;
        }

        .card-desc {
            margin-top: 5px;
            color: #475569;
            font-size: 9px;
            line-height: 1.5;
        }

        .section-title {
            margin: 18px 0 8px;
            font-size: 14px;
            font-weight: 700;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table.report th,
        table.report td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }

        table.report th {
            background: #f8fafc;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #334155;
        }

        .time-cell {
            padding: 5px 7px;
            border-radius: 6px;
            display: inline-block;
            min-width: 58px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
        }

        .status-late {
            background: #fef3c7;
            color: #92400e;
        }

        .status-on-time {
            background: #dcfce7;
            color: #065f46;
        }

        .status-empty {
            background: #f1f5f9;
            color: #475569;
        }

        .status-missed {
            background: #e2e8f0;
            color: #334155;
        }

        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            margin-top: 10px;
            color: #64748b;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="title">Rekap Absensi Satpam</h1>
        <p class="subtitle">Statistik dan rekap bulanan untuk laporan internal admin</p>

        <table class="meta">
            <tr>
                <td style="width: 18%;"><strong>Satpam</strong></td>
                <td style="width: 2%;">:</td>
                <td>{{ $satpam->name }}</td>
                <td style="width: 18%;"><strong>Bulan</strong></td>
                <td style="width: 2%;">:</td>
                <td>{{ $monthLabel }}</td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td>:</td>
                <td>{{ $satpam->email }}</td>
                <td><strong>Dicetak</strong></td>
                <td>:</td>
                <td>{{ $reportGeneratedAt->format('d-m-Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="cards">
        <tr>
            @foreach ($summaryCards as $card)
                <td>
                    <div class="card">
                        <div class="card-label">{{ $card['label'] }}</div>
                        <div class="card-value">{{ $card['value'] }}</div>
                        <div class="card-desc">{{ $card['description'] }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>


    <div class="section-title">Rekap Harian</div>
    <table class="report">
        <thead>
            <tr>
                <th>Tanggal</th>
                @foreach ($dailyPointColumns as $column)
                    <th>{{ $column['label'] }} ({{ $column['schedule_time'] ?? '-' }})</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($dailyPointRows as $row)
                <tr>
                    <td>{{ $row['date']->translatedFormat('d M Y') }}</td>
                    @foreach ($row['points'] as $point)
                        <td>
                            @if ($point['status_category'] === 'missed')
                                <span class="time-cell status-missed">{{ $point['display_time'] }}</span>
                            @elseif ($point['status_category'] === 'expired')
                                <span class="time-cell status-expired">{{ $point['display_time'] }}</span>
                            @elseif (in_array($point['status_category'], ['late', 'on_time', 'empty'], true))
                                <span
                                    class="time-cell {{ $point['status_category'] === 'late' ? 'status-late' : '' }} {{ $point['status_category'] === 'on_time' ? 'status-on-time' : '' }} {{ $point['status_category'] === 'empty' ? 'status-empty' : '' }}">
                                    {{ $point['display_time'] }}
                                </span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak melalui sistem pada {{ $reportGeneratedAt->format('d-m-Y H:i') }}
    </div>
</body>

</html>
