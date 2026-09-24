<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Správa z pracovnej cesty – {{ $order->order_number }}</title>
    {{-- Predloha: hárok „Správa z pracovnej cesty“ – jeden blok (strana) na každý deň cesty z CP --}}
    <style>
        @page { margin: 15mm 15mm; size: a4 portrait; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #000; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 3px 4px; vertical-align: top; }
        .b { font-weight: bold; }
        .footer { margin-top: 40px; font-size: 8px; }
    </style>
</head>
<body>
@foreach($days as $d)
    <div class="page">
        <table><tr>
            <td class="b" style="font-size: 12px;">Správa z pracovnej cesty k cestovnému príkazu č.</td>
            <td class="b" style="font-size: 12px; text-align: right;">{{ $order->order_number }}</td>
        </tr></table>
        <table style="margin-top: 14px;">
            <tr><td style="width: 5%;">a)</td><td style="width: 30%;">Meno a priezvisko :</td><td class="b">{{ $order->kapz->full_name }}</td></tr>
            <tr><td>b)</td><td>Pracovné zaradenie:</td><td class="b">{{ config('kapz.kapz_position') }}</td></tr>
            <tr><td>c)</td><td>Miesto pracovnej cesty:</td><td class="b">{{ $d['places'] }}</td></tr>
            <tr><td>d)</td><td>Dátum pracovnej cesty:</td><td class="b">{{ $d['date']->format('d.m.Y') }} &nbsp; - &nbsp; {{ $d['date']->format('d.m.Y') }}</td></tr>
            <tr><td>e)</td><td>Účel cesty v CP</td><td class="b">{{ $d['purpose'] }}</td></tr>
        </table>

        <div class="b" style="margin-top: 16px;">Správa z pracovnej cesty:</div>
        <div style="margin-top: 6px; min-height: 180px; text-align: justify;">{!! nl2br(e($d['report_text'])) !!}</div>

        <div class="b" style="margin-top: 12px;">Použité motorové vozidlo:</div>
        <div style="margin-top: 4px;">
            Na pracovnú cestu bolo použité súkromné motorové vozidlo ......{{ $order->vehicle_model ?: '..........' }}.., EČ: ...{{ $order->vehicle_plate ?: '..........' }}...,
            priemerná spotreba podľa TP: ..{{ $order->fuel_consumption ? number_format($order->fuel_consumption, 1, ',', '') : '....' }} l/100km.
        </div>

        <table style="margin-top: 24px;">
            <tr><td style="width: 20%;">Spracoval:</td><td class="b">{{ $order->kapz->full_name }}</td></tr>
            <tr><td>V</td><td>{{ $order->residence_city ?: $order->kapz->scope }}, {{ $d['date']->format('d.m.Y') }}</td></tr>
        </table>

        <div class="footer">Zdravé regióny<br>Sídlo: Limbová 2, 837 52 Bratislava<br>Korešpondenčná adresa: Koceľova 9, 821 08 Bratislava<br>IČO: 50626396</div>
    </div>
@endforeach
</body>
</html>
