<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Vyúčtovanie cestovných nákladov vlastnou dopravou – {{ $order->order_number }}</title>
    {{-- Predloha: hárok „Vyúčtovanie VD“ (A2:I96): úseky z cestovného príkazu, I92 = súčet km --}}
    <style>
        @page { margin: 12mm 12mm; size: a4 portrait; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        .box td, .box th { border: 0.6px solid #000; padding: 2px 4px; }
        .box th { background: #f2f2f2; }
        .r { text-align: right; } .c { text-align: center; } .b { font-weight: bold; }
    </style>
</head>
<body>
    <div class="b" style="font-size: 12px; margin-bottom: 6px;">Vyúčtovanie cestovných nákladov vlastnou dopravou</div>
    <table class="box" style="margin-bottom: 6px;">
        <tr>
            <td class="b" style="width: 22%;">{{ $order->order_number }}</td>
            <td style="width: 38%;">{{ config('kapz.kapz_position') }}</td>
            <td class="b">{{ $order->kapz->full_name }}</td>
            <td style="width: 15%;">Číslo hárku: &nbsp; 1</td>
        </tr>
    </table>
    <table class="box">
        <tr><th style="width: 14%;">Dátum</th><th colspan="3">Cieľ cesty (z – do)</th><th style="width: 12%;">Počet km</th></tr>
        @foreach($order->segments as $s)
            <tr>
                <td class="c">{{ $s->trip_date->format('d.m.Y') }}</td>
                <td>{{ $s->from_place }}</td><td class="c" style="width: 3%;">&gt;</td><td>{{ $s->to_place }}</td>
                <td class="r">{{ rtrim(rtrim(number_format($s->km, 2, ',', ''), '0'), ',') }}</td>
            </tr>
        @endforeach
        <tr><td colspan="4" class="r b">Spolu</td><td class="r b">{{ rtrim(rtrim(number_format($calc['total_km'], 2, ',', ''), '0'), ',') }}</td></tr>
    </table>
    <div style="margin-top: 14px; font-size: 7.5px;">
        Zdravé regióny<br>Sídlo: Limbová 2, 83752 Bratislava<br>Korešpondenčná adresa: Koceľova 9, 821 08 Bratislava<br>IČO: 50626396
    </div>
</body>
</html>
