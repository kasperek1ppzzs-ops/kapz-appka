<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>KNIHA PRACOVNÝCH CIEST A VÝKAZOV (KNIHY)</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; margin: 0; padding: 10px; }
        .title { font-size: 14px; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 10px; color: #1e3a8a; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-table td { padding: 4px; }
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .grid-table th, .grid-table td { border: 1px solid #6b7280; padding: 4px; text-align: center; font-size: 9px; }
        .grid-table th { background-color: #e0e7ff; font-weight: bold; color: #1e3a8a; }
    </style>
</head>
<body>
    <div class="title">KNIHA EVIDENCIE CIEST A VÝKAZOV (KNIHY)</div>

    <table class="header-table">
        <tr>
            <td><strong>Meno KAPZ:</strong> {{ $kapz->full_name }} ({{ $kapz->personal_number }})</td>
            <td><strong>Obdobie:</strong> {{ $period->formatted_name }}</td>
        </tr>
        <tr>
            <td><strong>Pôsobnosť / Lokalita:</strong> {{ $kapz->scope }}</td>
            <td><strong>Počet priradených APZ:</strong> {{ count($data['apz_list']) }}</td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 12%;">Číslo Príkazu</th>
                <th style="width: 12%;">Dátum</th>
                <th style="width: 25%;">Trasa</th>
                <th>Účel Cesty</th>
                <th style="width: 12%;">Odjazdené km</th>
                <th style="width: 15%;">Náhrada celkom</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['orders'] as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ date('d.m.Y', strtotime($order->departure_datetime)) }}</td>
                    <td>{{ $order->departure_place }} &rarr; {{ $order->destination_place }}</td>
                    <td style="text-align: left;">{{ $order->purpose }}</td>
                    <td>{{ $order->expense ? number_format($order->expense->total_km, 1) . ' km' : '-' }}</td>
                    <td style="font-weight: bold;">{{ $order->expense ? number_format($order->expense->final_balance, 2) . ' €' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af;">Žiadne evidované jazdy</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table style="margin-top: 40px; width: 100%;">
        <tr>
            <td style="text-align: center; width: 50%;">...............................................................<br>Podpis vyhotoviteľa (KAPZ)</td>
            <td style="text-align: center; width: 50%;">...............................................................<br>Podpis a pečiatka schvaľovateľa</td>
        </tr>
    </table>
</body>
</html>
