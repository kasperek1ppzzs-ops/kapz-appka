<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>CESTOVNÝ PRÍKAZ A VYÚČTOVANIE</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; margin: 0; padding: 15px; }
        .title { font-size: 14px; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 12px; color: #1e3a8a; }
        .section-title { font-size: 11px; font-weight: bold; margin-top: 15px; margin-bottom: 5px; padding: 3px; background-color: #e0e7ff; color: #1e3a8a; }
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grid-table td { border: 1px solid #9ca3af; padding: 4px; }
    </style>
</head>
<body>
    <div class="title">CESTOVNÝ PRÍKAZ Č. {{ $order->order_number }}</div>

    <div class="section-title">1. CESTOVNÝ PRÍKAZ</div>
    <table class="grid-table">
        <tr>
            <td style="width: 25%;"><strong>Zamestnanec / KAPZ:</strong></td>
            <td>{{ $order->kapz->full_name }} ({{ $order->kapz->personal_number }})</td>
        </tr>
        <tr>
            <td><strong>Pôsobnosť:</strong></td>
            <td>{{ $order->kapz->scope }}</td>
        </tr>
        <tr>
            <td><strong>Počiatok cesty:</strong></td>
            <td>{{ date('d.m.Y H:i', strtotime($order->departure_datetime)) }} - {{ $order->departure_place }}</td>
        </tr>
        <tr>
            <td><strong>Koniec cesty:</strong></td>
            <td>{{ $order->arrival_datetime ? date('d.m.Y H:i', strtotime($order->arrival_datetime)) : '-' }} - {{ $order->destination_place }}</td>
        </tr>
        <tr>
            <td><strong>Účel cesty:</strong></td>
            <td>{{ $order->purpose }}</td>
        </tr>
        <tr>
            <td><strong>Dopravný prostriedok:</strong></td>
            <td>{{ $order->transport_means }}</td>
        </tr>
    </table>

    @if($order->report)
        <div class="section-title">2. SPRÁVA Z PRACOVNEJ CIESTY</div>
        <table class="grid-table">
            <tr>
                <td style="width: 25%;"><strong>Stručné zhrnutie:</strong></td>
                <td>{{ $order->report->summary_of_activities }}</td>
            </tr>
            <tr>
                <td><strong>Výsledky a prínos:</strong></td>
                <td>{{ $order->report->outcomes ?? '-' }}</td>
            </tr>
        </table>
    @endif

    @if($order->expense)
        <div class="section-title">3. VYÚČTOVANIE PRACOVNEJ CIESTY</div>
        <table class="grid-table">
            <tr>
                <td style="width: 40%;"><strong>Počet odjazdených km:</strong></td>
                <td style="text-align: right;">{{ number_format($order->expense->total_km, 2) }} km</td>
            </tr>
            <tr>
                <td><strong>Sadzba za 1 km:</strong></td>
                <td style="text-align: right;">{{ number_format($order->expense->rate_per_km, 3) }} €</td>
            </tr>
            <tr>
                <td><strong>Náhrada za km celkom:</strong></td>
                <td style="text-align: right;">{{ number_format($order->expense->total_km_compensation, 2) }} €</td>
            </tr>
            <tr>
                <td><strong>Stravné / Diéty:</strong></td>
                <td style="text-align: right;">{{ number_format($order->expense->diem_compensation, 2) }} €</td>
            </tr>
            <tr>
                <td style="background-color: #f3f4f6;"><strong>CELKOVÉ NÁHRADY:</strong></td>
                <td style="text-align: right; background-color: #f3f4f6; font-weight: bold;">
                    {{ number_format($order->expense->final_balance, 2) }} €
                </td>
            </tr>
        </table>
    @endif

    <table style="margin-top: 40px; width: 100%;">
        <tr>
            <td style="text-align: center; width: 50%;">...............................................................<br>Podpis zamestnanca (KAPZ)</td>
            <td style="text-align: center; width: 50%;">...............................................................<br>Podpis a schválenie vyúčtovania</td>
        </tr>
    </table>
</body>
</html>
