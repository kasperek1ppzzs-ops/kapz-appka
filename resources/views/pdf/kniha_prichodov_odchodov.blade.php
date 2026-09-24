<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>KNIHA PRÍCHODOV A ODCHODOV</title>
    <style>
        @page {
            margin: 10mm 10mm 10mm 10mm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7pt;
            color: #1e293b;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        .page-container {
            page-break-after: always;
            position: relative;
        }
        .page-container:last-child {
            page-break-after: avoid;
        }

        /* Top Header Organization */
        .org-header {
            font-size: 7pt;
            color: #475569;
            text-align: center;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .doc-title-box {
            text-align: center;
            margin-bottom: 6px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .doc-itms {
            font-size: 7.5pt;
            color: #64748b;
            font-weight: bold;
            margin-top: 2px;
        }

        /* Meta Box */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
        }
        .meta-table td {
            padding: 3px 6px;
            font-size: 7.5pt;
        }
        .meta-label {
            font-weight: bold;
            color: #334155;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
        }

        /* Main Table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }
        .main-table th, .main-table td {
            border: 0.5px solid #94a3b8;
            padding: 1.8px 2px;
            text-align: center;
            font-size: 6.5pt;
            vertical-align: middle;
            overflow: hidden;
            word-wrap: break-word;
        }
        .main-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
        }
        .main-table tr.weekend {
            background-color: #fff7ed;
            color: #9a3412;
        }
        .main-table tr.holiday {
            background-color: #eff6ff;
            color: #1e40af;
        }

        .col-day { width: 4.5%; }
        .col-hour { width: 5.5%; }
        .col-min { width: 5.5%; }
        .col-reason { width: 14%; text-align: left; }
        .col-loc { width: 16%; text-align: left; }
        .col-appr { width: 13%; text-align: left; }
        .col-note { width: 15%; text-align: left; }

        /* Sign-off Section */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .sign-table td {
            font-size: 7.5pt;
            padding: 4px 6px;
            vertical-align: top;
        }
        .sign-box {
            border-bottom: 1px dotted #64748b;
            height: 26px;
            margin-top: 4px;
        }
        .sign-label {
            font-size: 6.5pt;
            color: #64748b;
            text-align: right;
            font-style: italic;
        }

        .footer-note {
            font-size: 6pt;
            color: #94a3b8;
            text-align: center;
            margin-top: 6px;
        }
    </style>
</head>
<body>

@foreach($books as $book)
    @php
        $period = $book->reportingPeriod;
        $periodStr = sprintf('%02d/%04d', $period->month, $period->year);
        $lastDay = \Carbon\Carbon::create($period->year, $period->month, 1)->endOfMonth()->format('d.m.Y');
    @endphp

    <div class="page-container">
        <!-- Header -->
        <div class="org-header">
            Zdravé regióny, Limbová 2 831 01 Bratislava (Korešpondenčná adresa: Koceľova 9, 82108 Bratislava) IČO: 50626396
        </div>

        <div class="doc-title-box">
            <h1 class="doc-title">KNIHA PRÍCHODOV A ODCHODOV</h1>
            <div class="doc-itms">ITMS kód projektu: {{ $book->project_code ?? '401405DUQ8' }}</div>
        </div>

        <!-- Meta info -->
        <table class="meta-table">
            <tr>
                <td style="width: 35%;">
                    <span class="meta-label">Meno:</span>
                    <span class="meta-val">{{ $book->full_name }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="meta-label">Osobné číslo:</span>
                    <span class="meta-val">{{ $book->personal_number }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="meta-label">Lokalita:</span>
                    <span class="meta-val">{{ $book->location }}</span>
                </td>
                <td style="width: 15%; text-align: right;">
                    <span class="meta-label">Mesiac:</span>
                    <span class="meta-val">{{ $periodStr }}</span>
                </td>
            </tr>
        </table>

        <!-- Main Data Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="3" class="col-day">Dátum</th>
                    <th colspan="2">Príchod</th>
                    <th colspan="2">Odchod</th>
                    <th colspan="4">Prerušenie pracovného času</th>
                    <th rowspan="3" class="col-reason">Dôvod odchodu počas pracovného času</th>
                    <th rowspan="3" class="col-loc">Navštívené miesto</th>
                    <th rowspan="3" class="col-appr">Schválil</th>
                    <th rowspan="3" class="col-note">Poznámka</th>
                </tr>
                <tr>
                    <th rowspan="2" class="col-hour">hod.</th>
                    <th rowspan="2" class="col-min">min.</th>
                    <th rowspan="2" class="col-hour">hod.</th>
                    <th rowspan="2" class="col-min">min.</th>
                    <th colspan="2">odchod</th>
                    <th colspan="2">príchod</th>
                </tr>
                <tr>
                    <th class="col-hour">hod.</th>
                    <th class="col-min">min.</th>
                    <th class="col-hour">hod.</th>
                    <th class="col-min">min.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($book->items as $item)
                    @php
                        $dt = \Carbon\Carbon::parse($item->record_date);
                        $isWeekend = $dt->isWeekend();
                        $isHoliday = $item->note === 'Sviatok';
                        $rowClass = $isWeekend ? 'weekend' : ($isHoliday ? 'holiday' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td style="font-weight: bold;">{{ $item->day_number }}</td>
                        <td>{{ $item->arrival_hour }}</td>
                        <td>{{ $item->arrival_minute }}</td>
                        <td>{{ $item->departure_hour }}</td>
                        <td>{{ $item->departure_minute }}</td>
                        <td>{{ $item->break_departure_hour }}</td>
                        <td>{{ $item->break_departure_minute }}</td>
                        <td>{{ $item->break_arrival_hour }}</td>
                        <td>{{ $item->break_arrival_minute }}</td>
                        <td style="text-align: left; padding-left: 3px;">{{ $item->break_reason }}</td>
                        <td style="text-align: left; padding-left: 3px;">{{ $item->visited_location }}</td>
                        <td style="text-align: left; padding-left: 3px;">{{ $item->approved_by }}</td>
                        <td style="text-align: left; padding-left: 3px;">{{ $item->note }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Sign-off Block -->
        <table class="sign-table">
            <tr>
                <td style="width: 30%;">
                    <strong>Dátum :</strong> {{ $lastDay }}
                </td>
                <td style="width: 35%;">
                    <strong>Vyhotovil :</strong> {{ $book->full_name }}
                    <div class="sign-box"></div>
                    <div class="sign-label">podpis</div>
                </td>
                <td style="width: 35%;">
                    <strong>Schválil :</strong> {{ $book->approver_name }}
                    <div class="sign-box"></div>
                    <div class="sign-label">podpis</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Generované zo systému KAPZ/APZ dňa {{ date('d.m.Y H:i') }} | Tento projekt je spolufinancovaný Európskou úniou v rámci Programu Slovensko.
        </div>
    </div>
@endforeach

</body>
</html>
