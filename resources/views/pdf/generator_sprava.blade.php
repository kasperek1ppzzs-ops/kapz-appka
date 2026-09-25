<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Správa zo služobnej cesty – {{ $order->order_number }}</title>
    {{-- Predloha: hárok GENERATOR – blok na každý deň z CP; Závery/Odporúčania podľa účelu (IF(E13=Q14,R14,…)) --}}
    <style>
        @page { margin: 14mm 14mm; size: a4 portrait; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 2px 4px; vertical-align: top; }
        .b { font-weight: bold; }
        .day { margin-top: 10px; border-top: 0.6px solid #000; padding-top: 4px; page-break-inside: avoid; }
        .small { font-size: 7.5px; color: #333; }
    </style>
</head>
<body>
    <div class="b" style="font-size: 13px; text-align: center;">Správa zo služobnej cesty</div>
    <table style="margin-top: 10px;">
        <tr><td style="width: 30%;">Odbor/Oddelenie:</td><td class="b">{{ config('kapz.department') }}</td></tr>
        <tr><td>Meno a priezvisko pracovníka:</td><td class="b">{{ $order->kapz->full_name }}</td></tr>
        <tr><td>Pozícia:</td><td class="b">{{ config('kapz.kapz_position') }}</td></tr>
    </table>
    @foreach($days as $d)
        <div class="day">
            <table>
                <tr><td style="width: 30%;">Deň:<br><span class="small">(presný dátum)</span></td><td class="b">{{ $d['date']->format('d.m.Y') }} &nbsp; - &nbsp; {{ $d['date']->format('d.m.Y') }}</td></tr>
                <tr><td>Miesto konania:</td><td class="b">{{ $d['places'] }}</td></tr>
                <tr><td>Účel/predmet cesty:</td><td class="b">{{ $d['purpose'] }}</td></tr>
                <tr><td>Závery/Odporúčania:</td><td>{{ $d['conclusion'] }}</td></tr>
            </table>
        </div>
    @endforeach
</body>
</html>
