<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Cestovný príkaz č. {{ $order->order_number }}</title>
    {{-- Predloha: hárok „Cestovný príkaz“ – strana 1 = A1:N79, strana 2 = vyúčtovanie R80:AE281 --}}
    <style>
        @page { margin: 9mm 9mm; size: a4 portrait; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #000; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .box td, .box th { border: 0.6px solid #000; padding: 2px 3px; vertical-align: top; }
        .box th { background: #f2f2f2; font-weight: bold; text-align: center; font-size: 7px; }
        .plain td { padding: 2px 3px; vertical-align: top; }
        .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
        .title { font-size: 12px; font-weight: bold; }
        .dots { border-bottom: 0.6px dotted #000; }
        .page2 { page-break-before: always; }
        .small { font-size: 6.5px; }
    </style>
</head>
<body>
@php
    $money = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $km = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $yn = fn ($v) => $v === null ? 'áno – nie' : ($v ? 'áno' : 'nie');
    $kapz = $order->kapz;
@endphp

{{-- STRANA 1: CESTOVNÝ PRÍKAZ --}}
<table class="plain">
    <tr>
        <td style="width: 60%;">Zamestnávateľ &nbsp; {{ config('kapz.employer') }}</td>
        <td style="width: 15%;">Osobné číslo</td><td class="b">{{ $kapz->personal_number }}</td>
    </tr>
    <tr>
        <td class="title">Cestovný príkaz č. &nbsp; {{ $order->order_number }}</td>
        <td>Útvar</td><td class="b">{{ config('kapz.department') }}</td>
    </tr>
    <tr><td></td><td>Telefón, linka</td><td>{{ $kapz->phone }}</td></tr>
</table>
<table class="plain" style="margin-top: 6px;">
    <tr>
        <td style="width: 60%;">1. Priezvisko, meno, titul: <b>{{ $kapz->full_name }}</b></td>
        <td>Normálny pracovný čas... 8 hodín<br>od...........8:00.........do........16:00.........</td>
    </tr>
    <tr><td colspan="2">2. Bydlisko: <b>{{ $order->residence_address }}</b> &nbsp; <b>{{ $order->residence_city }}</b></td></tr>
</table>

<table class="box" style="margin-top: 6px;">
    <tr>
        <th colspan="3" style="width: 28%;">Začiatok cesty (miesto, dátum, hodina)</th>
        <th style="width: 22%;">Miesto konania</th>
        <th style="width: 22%;">Účel cesty</th>
        <th colspan="3" style="width: 28%;">Koniec cesty (miesto, dátum, hodina)</th>
    </tr>
    @foreach($calc['days'] as $d)
        <tr>
            <td>{{ $d['start_place'] }}</td><td class="c">{{ $d['date']->format('d.m.Y') }}</td><td class="c">{{ $d['start_time'] }}</td>
            <td>{{ $d['places'] }}</td>
            <td>{{ $d['purpose'] }}</td>
            <td>{{ $d['end_place'] }}</td><td class="c">{{ $d['date']->format('d.m.Y') }}</td><td class="c">{{ $d['end_time'] }}</td>
        </tr>
    @endforeach
</table>

<table class="plain" style="margin-top: 6px;">
    <tr><td>3. Spolucestujúci <span class="dots">{{ $order->companions }}</span></td></tr>
    <tr><td>4. Určený dopravný prostriedok: <b>{{ $order->vehicle }}</b> {{ $order->vehicle_plate ? '– ' . $order->vehicle_plate : '' }}</td></tr>
    <tr><td>5. Predpokladaná čiastka výdajov v EUR {{ $order->expected_costs !== null ? $money($order->expected_costs) : '.................' }}</td></tr>
    <tr><td>6. Povolená záloha v EUR {{ $money($order->advance_amount) }} &nbsp; vyplatená dňa………………… &nbsp; pokladničný doklad číslo……………………</td></tr>
</table>
<table class="plain" style="margin-top: 14px;">
    <tr><td style="width: 50%;">…………………………………………………..<br>Podpis pokladníka</td><td>………………………………………………………..<br>Dátum a podpis zodpovedného pracovníka</td></tr>
</table>

<div class="b" style="margin-top: 10px;">Vyúčtovanie pracovnej cesty</div>
<table class="plain">
    <tr><td>7. Správa o výsledku pracovnej cesty bola podaná dňa {{ $order->report_submitted_on?->format('d.m.Y') ?? '.............................' }}</td></tr>
    <tr><td>So spôsobom vykonania súhlasí.......................................................................... &nbsp; Dátum a podpis zodpovedného pracovníka</td></tr>
</table>
<table class="box" style="margin-top: 6px;">
    <tr>
        <td style="width: 55%;">8. VÝDAJOVÝ A PRÍJMOVÝ DOKLAD číslo</td>
        <th colspan="5">Účtovací predpis</th>
    </tr>
    <tr>
        <td rowspan="2">
            Účtovná náhrada bola preskúšaná a upravená v EUR............ <b>{{ $money($calc['balance']) }}</b><br>
            Vyplatená záloha………………………………….  EUR............ <b>{{ $money($calc['advance']) }}</b><br>
            Doplatok – Preplatok…………………..……....….  EUR............ <b>{{ $money($calc['balance']) }}</b><br>
            Slovom……………………………………………………………………….
        </td>
        <th>Má dať</th><th>Dal</th><th>Čiastka</th><th>Stredisko</th><th>Zákazka</th>
    </tr>
    <tr><td class="c">512100</td><td class="c">333100</td><td class="r">{{ $money($calc['balance']) }}</td><td></td><td></td></tr>
    <tr><td colspan="6">Poznámka o zaúčtovaní</td></tr>
</table>
<table class="plain" style="margin-top: 14px;">
    <tr class="small">
        <td>…………………………………………….<br>Dátum a podpis pracovníka, ktorý upravil vyúčtovanie</td>
        <td>..................<br>Dátum a podpis príjemcu (preukaz totožnosti)</td>
        <td>......................................................<br>Dátum a podpis pokladníka</td>
        <td>..........................................<br>Schválil (dátum a podpis)</td>
    </tr>
</table>
<div style="margin-top: 8px;">9. Poznámka: {{ $order->note }}</div>

{{-- STRANA 2: VYÚČTOVANIE PRACOVNEJ CESTY --}}
<div class="page2">
    <div class="title" style="text-align: center; margin-bottom: 4px;">VYÚČTOVANIE PRACOVNEJ CESTY</div>
    <table class="box">
        <thead>
            <tr>
                <th rowspan="2">Dátum</th>
                <th colspan="2" rowspan="2">Odchod - Príchod<br>Miesto rokovania</th>
                <th rowspan="2">o hod.</th>
                <th rowspan="2">Použitý dopr.<br>prostriedok 2)</th>
                <th rowspan="2">Vzdialenosť<br>v km 3)</th>
                <th rowspan="2">Začiatok a koniec pracovného výkonu (hodina)</th>
                <th>Cestovné výdavky a miestna preprava/ PHM</th>
                <th>Cestovné výdavky Amortizácia</th>
                <th>Stravné</th>
                <th>Nocľažné</th>
                <th>Nutné vedľajšie výdavky</th>
                <th>Spolu</th>
                <th>Upravené</th>
            </tr>
            <tr><th>EUR</th><th>EUR</th><th>EUR</th><th>EUR</th><th>EUR</th><th>EUR</th><th>EUR</th></tr>
            <tr class="small"><td class="c">1</td><td class="c" colspan="2">2</td><td></td><td class="c">3</td><td class="c">4</td><td class="c">5</td><td class="c">6</td><td class="c">7</td><td class="c">8</td><td class="c">9</td><td class="c">10</td><td class="c">11</td><td class="c">12</td></tr>
        </thead>
        <tbody>
        @foreach($calc['rows'] as $row)
            @php $s = $row['segment']; @endphp
            <tr>
                <td class="c" rowspan="2">{{ $s->trip_date->format('d.m.Y') }}</td>
                <td style="width: 5%;">Odchod</td><td>{{ $s->from_place }}</td><td class="c">{{ $s->departure_time }}</td>
                <td class="c" rowspan="2">{{ $s->transport_mode }}</td>
                <td class="r" rowspan="2">{{ $km($s->km) }}</td>
                <td class="c" rowspan="2">{{ $s->work_time }}</td>
                <td class="r" rowspan="2">{{ $money($row['fuel_cost']) }}</td>
                <td class="r" rowspan="2">{{ $money($s->amortization) }}</td>
                <td class="r" rowspan="2">{{ $money($s->meals) }}</td>
                <td class="r" rowspan="2">{{ $money($s->accommodation_cost) }}</td>
                <td class="r" rowspan="2">{{ $money($s->other_costs) }}</td>
                <td class="r" rowspan="2">{{ $money($row['total']) }}</td>
                <td class="r" rowspan="2">{{ $s->adjusted !== null ? $money($s->adjusted) : '' }}</td>
            </tr>
            <tr><td>Príchod</td><td>{{ $s->to_place }}</td><td class="c">{{ $s->arrival_time }}</td></tr>
        @endforeach
        </tbody>
        <tr>
            <td colspan="6" rowspan="3" class="small">
                Stravovanie bolo poskytnuté bezplatne: {{ $yn($order->meals_free) }}<br>
                Ubytovanie bolo poskytnuté bezplatne: {{ $yn($order->accommodation_free) }}<br>
                Voľný – zľavnený cestovný lístok: {{ $yn($order->discounted_ticket) }}
            </td>
            <td class="b">Celkom</td>
            <td class="r">{{ $money($calc['totals']['fuel_cost']) }}</td>
            <td class="r">{{ $money($calc['totals']['amortization']) }}</td>
            <td class="r">{{ $money($calc['totals']['meals']) }}</td>
            <td class="r">{{ $money($calc['totals']['accommodation_cost']) }}</td>
            <td class="r">{{ $money($calc['totals']['other_costs']) }}</td>
            <td class="r b">{{ $money($calc['totals']['total']) }}</td>
            <td class="r">{{ $money($calc['totals']['adjusted']) }}</td>
        </tr>
        <tr><td class="b" colspan="6">Preddavok</td><td class="r">{{ $money($calc['advance']) }}</td><td></td></tr>
        <tr><td class="b" colspan="6">Doplatok - Preplatok</td><td class="r b">{{ $money($calc['balance']) }}</td><td></td></tr>
    </table>
    <table class="plain small" style="margin-top: 6px;">
        <tr>
            <td style="width: 55%;">
                Poznámky:<br>
                1) Čas odchodu a príchodu vyplňajte podľa cestovného poriadku<br>
                2) Použitý dopravný prostriedok uvádzajte v skratke<br>
                O - Osobný vlak &nbsp; R - Rýchlik &nbsp; A - Autobus &nbsp; P - Pešo<br>
                AUS - auto služobné &nbsp; AUV - auto vlastné &nbsp; AUSS - auto služobné, ako spolucestujúci &nbsp; AUVS - auto vlastné, ako spolucestujúci<br>
                3) Počet km uvádzajte len pri použití iného ako verejného hromadného dopravného prostriedku<br>
                Pozn.: Stravné, nocľažné a ďalšie cestovné náhrady sa účtujú podľa platných smerníc
            </td>
            <td>Vyhlasujem, že som všetky údaje uviedol úplné a správne.<br><br><br>
                ............................................................<br>Dátum a podpis účtovateľa</td>
        </tr>
    </table>
</div>
</body>
</html>
