<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>PLÁN PRACOVNÝCH CIEST - {{ $plan->order_number }}</title>
    <style>
        @page {
            margin: 8mm 10mm 8mm 10mm;
            size: a4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #111827;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }
        .week-page {
            page-break-inside: avoid;
        }
        .header-box {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }
        .org-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 11.5px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 1px 0 2px 0;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
        }
        .info-table td {
            padding: 2px 5px;
            font-size: 7.5px;
        }
        .week-header {
            background-color: #f1f5f9;
            border-left: 3px solid #2563eb;
            padding: 2.5px 5px;
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
            margin-bottom: 2px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .grid-table th {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 2.5px 3.5px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            text-align: center;
        }
        .grid-table td {
            border: 1px solid #cbd5e1;
            padding: 2.5px 3.5px;
            font-size: 7px;
            vertical-align: top;
        }
        .subtotal-row td {
            background-color: #f8fafc;
            font-weight: bold;
            text-align: right;
            border-top: 1.5px solid #94a3b8;
            font-size: 7.5px;
        }
        .clause-box {
            margin-top: 5px;
            padding: 3px 5px;
            background-color: #f8fafc;
            border-left: 3px solid #0284c7;
            font-size: 7.5px;
            font-style: italic;
            color: #0f172a;
        }
        .creator-line {
            font-size: 7.5px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
            margin-bottom: 4px;
        }
        .zfk-box {
            margin-top: 4px;
            border: 1px solid #94a3b8;
            padding: 4px 6px;
            background-color: #ffffff;
        }
        .zfk-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            font-size: 7px;
            padding: 0 10px;
        }
    </style>
</head>
<body>

    @php
        $weeks = $selectedWeek ? [$selectedWeek] : [1, 2, 3, 4, 5];
        $totalMonthlyKm = $plan->items->sum('estimated_km');
    @endphp

    @foreach($weeks as $w)
        @php
            $weekItems = $plan->items->where('week_number', $w)->sortBy('trip_date');
            $weekKm = $weekItems->sum('estimated_km');
            $calWeek = $plan->getCalendarWeek($w);
            $orderSuffix = "-T{$w}";
        @endphp

        <div class="week-page" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
            <!-- Hlavička dokumentu -->
            <div class="header-box">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 60%; vertical-align: top;">
                            <div class="org-title">Zdravé regióny</div>
                            <div style="font-size: 6.5px; color: #475569;">
                                Limbová 2, 831 01 Bratislava | IČO: 50626396<br>
                                NP Zdravé komunity | Kód ITMS: 401405DUQ8
                            </div>
                        </td>
                        <td style="width: 40%; text-align: right; vertical-align: top;">
                            <div style="font-size: 8px; font-weight: bold; color: #1e3a8a;">
                                Číslo CP: {{ $plan->order_number }}{{ $orderSuffix }}
                            </div>
                            <div style="font-size: 7px; color: #64748b;">
                                Obdobie: {{ $plan->reportingPeriod->formatted_name }} | {{ $w }}. týždeň ({{ $calWeek }}. KT)
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="doc-title">TÝŽDENNÝ PLÁN PRACOVNÝCH CIEST</div>
            <div style="text-align: center; font-size: 8.5px; font-weight: bold; color: #1e3a8a; margin-bottom: 4px;">
                {{ $w }}. TÝŽDEŇ &mdash; {{ $calWeek }}. KALENDÁRNY TÝŽDEŇ
            </div>

            <!-- Identifikačná tabuľka -->
            <table class="info-table">
                <tr>
                    <td style="width: 50%;"><strong>Meno a priezvisko:</strong> {{ $plan->kapz->full_name }}</td>
                    <td style="width: 50%;"><strong>Pracovná pozícia:</strong> Koordinátor asistentov podpory zdravia</td>
                </tr>
                <tr>
                    <td><strong>Pôsobnosť (Región):</strong> {{ $plan->kapz->scope }}</td>
                    <td><strong>Osobné číslo:</strong> {{ $plan->kapz->personal_number }}</td>
                </tr>
                <tr>
                    <td><strong>Mesačný limit km:</strong> {{ number_format($plan->km_limit, 0) }} km (čerpanie: {{ number_format($totalMonthlyKm, 1) }} km)</td>
                    <td><strong>Stav plánu:</strong> {{ $plan->status == 'APPROVED' ? 'SCHVÁLENÉ (ZFK overená)' : ($plan->status == 'SUBMITTED' ? 'ODOSLANÉ NA SCHVÁLENIE' : 'KONCEPT') }} (Verzia {{ $plan->version }})</td>
                </tr>
            </table>

            <!-- Tabuľka trás daného týždňa -->
            <div class="week-header">
                Harmonogram ciest na {{ $w }}. týždeň &mdash; Nájazd: {{ number_format($weekKm, 1) }} km
            </div>

            <table class="grid-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">1. Dátum</th>
                        <th style="width: 25%;">2. Miesto rokovania (Trasa)</th>
                        <th style="width: 14%;">Časový harmonogram</th>
                        <th style="width: 35%;">6. Stručný opis plánovanej cesty (Účel)</th>
                        <th style="width: 9%;">7. Doprava</th>
                        <th style="width: 9%;">8. Km</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weekItems as $item)
                        <tr>
                            <td style="text-align: center;">{{ date('d.m.Y', strtotime($item->trip_date)) }}</td>
                            <td>
                                @php
                                    $dests = array_filter(array_map('trim', preg_split('/[,;+]+/', $item->destination_location)));
                                @endphp
                                @if(count($dests) > 1)
                                    <div style="font-size: 6px; font-weight: bold; color: #1e3a8a;">Okruh ({{ count($dests) }} lokality):</div>
                                    <div style="font-size: 6.5px; line-height: 1.2;">{{ $item->departure_location }} &rarr; <strong>{{ implode(' &rarr; ', $dests) }}</strong> &rarr; {{ $item->departure_location }}</div>
                                @else
                                    <div style="font-size: 6.5px;"><span style="color: #0284c7; font-weight: bold;">Tam:</span> {{ $item->departure_location }} &rarr; <strong>{{ $item->destination_location }}</strong></div>
                                    <div style="font-size: 6.5px; color: #475569;"><span style="font-weight: bold;">Späť:</span> {{ $item->destination_location }} &rarr; {{ $item->departure_location }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; font-family: monospace; font-size: 6px; line-height: 1.3;">
                                <div style="color: #0369a1;"><strong>Tam:</strong> {{ $item->departure_time }} &rarr; {{ $item->arrival_at_dest_time }}</div>
                                <div style="color: #334155;"><strong>Späť:</strong> {{ $item->departure_from_dest_time }} &rarr; {{ $item->arrival_time }}</div>
                            </td>
                            <td>
                                {{ $item->purpose }}
                                @if($item->targetApz)
                                    <div style="font-size: 6px; color: #64748b;">(APZ: {{ $item->targetApz->full_name }})</div>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $item->transport_mode }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format($item->estimated_km, 1) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 5px;">
                                V {{ $w }}. týždni ({{ $calWeek }}. kalendárny týždeň) nie sú naplánované žiadne pracovné cesty.
                            </td>
                        </tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td colspan="5" style="text-align: right;">Súčet km za {{ $w }}. týždeň:</td>
                        <td style="text-align: right; font-weight: bold; color: #1e3a8a;">{{ number_format($weekKm, 1) }} km</td>
                    </tr>
                </tbody>
            </table>

            <!-- Zákonná klauzula súladu -->
            <div class="clause-box">
                „Harmonogram pracovných ciest je v súlade s náplňou opisu pracovnej činnosti práce Koordinátor asistentov podpory zdravia.“
            </div>

            <div class="creator-line">
                Spracoval/a: {{ $plan->kapz->full_name }}
            </div>

            <!-- Blok Základnej finančnej kontroly (ZFK) -->
            <div class="zfk-box">
                <div class="zfk-title">
                    Základná finančná kontrola (ZFK) podľa § 7 zákona NR SR č. 357/2015 Z.z.
                </div>
                <table style="width: 100%; font-size: 7px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <strong>Popis finančnej operácie (FO):</strong><br>
                            Pracovné stretnutie a porada v zdravotníckych a sociálnych zariadeniach
                        </td>
                        <td style="width: 50%; vertical-align: top;">
                            <strong>Zdôvodnenie:</strong><br>
                            V súvislosti s realizáciou NP ZK, kód ITMS 401405DUQ8
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 3px; border-top: 1px dashed #cbd5e1; margin-top: 3px;">
                            Overenie súladu finančnej operácie so skutočnosťami podľa § 6 ods. 4 zákona č. 357/2015 Z.z. vykonáva príslušný <strong>Expert pre terén / Nadriadený</strong>.
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top: 2px;">
                            Overenie vykonal/a:
                            <strong>{{ $plan->reviewedBy->name ?? ($plan->kapz->region_expert ?? 'Koky Richard, Mgr. PhD., MHA, MPH') }}</strong>
                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            Dátum: <strong>{{ $plan->reviewed_at ? date('d.m.Y', strtotime($plan->reviewed_at)) : '................................' }}</strong>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Podpisy -->
            <table class="signatures-table">
                <tr>
                    <td>
                        ................................................................................<br>
                        <strong>Spracoval/a:</strong> {{ $plan->kapz->full_name }}<br>
                        <span style="font-size: 6px; color: #64748b;">(Koordinátor asistentov podpory zdravia)</span>
                    </td>
                    <td>
                        ................................................................................<br>
                        <strong>Schválil/a:</strong> {{ $plan->reviewedBy->name ?? ($plan->kapz->region_expert ?? 'Koky Richard, Mgr. PhD., MHA, MPH') }}<br>
                        <span style="font-size: 6px; color: #64748b;">(Expert pre terén / Nadriadený)</span>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

</body>
</html>
