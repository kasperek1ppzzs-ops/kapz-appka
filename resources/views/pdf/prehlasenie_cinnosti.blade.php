<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Prehlásenie o činnosti mimo pracovného pomeru</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #000000;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        /* Institutional Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 2px 4px;
        }

        .eu-box {
            display: inline-block;
            background-color: #003399;
            color: #ffcc00;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 2px;
            text-align: center;
        }
        .eu-text {
            font-size: 8pt;
            font-weight: bold;
            color: #1e293b;
            line-height: 1.1;
        }

        .prog-sk {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .mz-sr {
            font-size: 8pt;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            line-height: 1.1;
        }

        /* Document Title */
        .title-block {
            text-align: center;
            margin-bottom: 14px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            color: #000000;
        }
        .doc-subtitle {
            font-size: 9pt;
            margin-top: 3px;
            color: #333333;
        }
        .doc-period {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 4px;
            color: #000000;
        }

        /* Data Table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .main-table th, .main-table td {
            border: 0.75pt solid #000000;
            padding: 4.5pt 5pt;
            font-size: 8pt;
        }
        .main-table th {
            background-color: #ffffff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .main-table td {
            vertical-align: middle;
        }

        .col-os-cis { width: 6%; text-align: center; font-family: DejaVu Sans, monospace; }
        .col-name { width: 24%; text-align: left; }
        .col-gainful { width: 15%; text-align: center; }
        .col-esif { width: 14%; text-align: center; }
        .col-contract { width: 21%; text-align: left; }
        .col-date { width: 10%; text-align: center; }
        .col-sign { width: 10%; text-align: center; font-size: 7pt; color: #555555; }

        .footer {
            margin-top: 15px;
            font-size: 7pt;
            color: #666666;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Institutional Logos Table -->
    <table class="header-table">
        <tr>
            <td style="width: 33%; text-align: left;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="padding-right: 6px;">
                            <div class="eu-box">★ ★ ★<br>★ ★ ★</div>
                        </td>
                        <td>
                            <div class="eu-text">Spolufinancované<br>Európskou úniou</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 34%; text-align: center;">
                <div style="font-size: 7.5pt; color: #64748b; font-weight: bold;">PROGRAM</div>
                <div class="prog-sk">SLOVENSKO</div>
            </td>
            <td style="width: 33%; text-align: right;">
                <div class="mz-sr">
                    MINISTERSTVO ZDRAVOTNÍCTVA<br>SLOVENSKEJ REPUBLIKY
                </div>
            </td>
        </tr>
    </table>

    <!-- Title Block -->
    <div class="title-block">
        <h1 class="doc-title">Prehlásenie o činnosti mimo pracovného pomeru</h1>
        <div class="doc-subtitle">Zdravé regióny, Limbová 2, 837 52 Bratislava</div>
        <div class="doc-period">{{ sprintf('%02d/%04d', $declaration->reportingPeriod->month, $declaration->reportingPeriod->year) }}</div>
    </div>

    <!-- Main Data Table -->
    <table class="main-table">
        <thead>
            <tr>
                <th class="col-os-cis">Os. čís.</th>
                <th class="col-name">Meno zamestnanca</th>
                <th class="col-gainful">
                    Vykonával/a som zárobkovú činnosť v danom mesiaci (áno/nie)
                </th>
                <th class="col-esif">
                    Je daná práca financovaná z EŠIF? (áno/nie)
                </th>
                <th class="col-contract">
                    Typ pracovného úväzku<br>(výber zo zoznamu)
                </th>
                <th class="col-date">Dňa</th>
                <th class="col-sign">Podpis</th>
            </tr>
        </thead>
        <tbody>
            @foreach($declaration->items as $item)
                <tr>
                    <td class="col-os-cis">{{ $item->personal_number }}</td>
                    <td class="col-name">
                        <strong>{{ $item->full_name }}</strong>
                    </td>
                    <td class="col-gainful">{{ $item->has_gainful_activity }}</td>
                    <td class="col-esif">{{ $item->is_funded_by_esif }}</td>
                    <td class="col-contract">{{ $item->contract_type ?: '' }}</td>
                    <td class="col-date">
                        {{ $item->signature_date ? $item->signature_date->format('d.m.Y') : '' }}
                    </td>
                    <td class="col-sign" style="height: 20px;">
                        &nbsp;
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Vygenerované zo systému KAPZ/APZ dňa {{ date('d.m.Y H:i') }} | Zodpovedný koordinátor: {{ $declaration->kapz->full_name }}
    </div>

</body>
</html>
