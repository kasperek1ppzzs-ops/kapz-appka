<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>SPRÁVA O PRACOVNEJ ČINNOSTI</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; padding: 20px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 20px; color: #1e3a8a; }
        .header-box { width: 100%; border: 1px solid #9ca3af; padding: 10px; margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; margin-top: 15px; margin-bottom: 8px; color: #1e3a8a; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .body-text { line-height: 1.6; text-align: justify; margin-bottom: 20px; font-size: 11px; }
        .metrics-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .metrics-table td, .metrics-table th { border: 1px solid #94a3b8; padding: 6px; }
        .metrics-table th { background-color: #f1f5f9; }
    </style>
</head>
<body>
    <div class="title">SPRÁVA O PRACOVNEJ ČINNOSTI KAPZ</div>

    <div class="header-box">
        <strong>Meno a priezvisko KAPZ:</strong> {{ $report->kapz->full_name }} ({{ $report->kapz->personal_number }})<br>
        <strong>Pôsobnosť / Obvod:</strong> {{ $report->kapz->scope }}<br>
        <strong>Obdobie:</strong> {{ $report->reportingPeriod->formatted_name }}
    </div>

    @isset($limitUsage)
        {{-- Excel „Správa o pracovnej činnosti“ H11–H13: limit podľa pôsobnosti, najazdené km z CP (Vyúčtovanie VD!I92), zostatok --}}
        <div class="section-title">1. ČERPANIE MESAČNÉHO LIMITU</div>
        <table class="metrics-table">
            <tr><td>Mesačný limit kilometrov pracovných ciest podľa uvedenej pôsobnosti</td><td style="text-align: right; font-weight: bold;">{{ number_format($limitUsage['limit'], 1, ',', ' ') }} km</td></tr>
            <tr><td>Najazdené kilometre (cestovný príkaz{{ $limitUsage['order'] ? ' č. ' . $limitUsage['order']->order_number : '' }})</td><td style="text-align: right; font-weight: bold;">{{ number_format($limitUsage['driven'], 1, ',', ' ') }} km</td></tr>
            <tr><td>Zostatok</td><td style="text-align: right; font-weight: bold;">{{ number_format($limitUsage['remaining'], 1, ',', ' ') }} km</td></tr>
        </table>

        @if($limitUsage['days']->isNotEmpty())
            <div class="section-title">PRACOVNÉ CESTY V MESIACI</div>
            <table class="metrics-table">
                <thead><tr><th>Dátum</th><th>Miesto</th><th>Správa z pracovnej cesty</th></tr></thead>
                <tbody>
                    @foreach($limitUsage['days'] as $d)
                        <tr><td>{{ $d['date']->format('d.m.Y') }}</td><td>{{ $d['places'] }}</td><td>{{ \Illuminate\Support\Str::limit($d['report_text'], 160) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endisset

    <div class="section-title">{{ isset($limitUsage) ? '2' : '1' }}. STRUČNÝ PREHĽAD VYKONANEJ ČINNOSTI A AKTIVÍT</div>
    <div class="body-text">
        {{ $report->summary_text ?? 'Bez popisu činnosti.' }}
    </div>

    @if($report->metrics_json)
        <div class="section-title">{{ isset($limitUsage) ? '3' : '2' }}. KVANTITATÍVNE METRIKY A VÝKONNOSŤ</div>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Ukazovateľ / Metrika</th>
                    <th style="text-align: right;">Hodnota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->metrics_json as $key => $val)
                    <tr>
                        <td>{{ strtoupper(str_replace('_', ' ', $key)) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ is_array($val) ? json_encode($val) : $val }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table style="margin-top: 60px; width: 100%;">
        <tr>
            <td style="text-align: right; width: 100%;">
                ...............................................................<br>
                Podpis KAPZ (Spracovateľ)
            </td>
        </tr>
    </table>
</body>
</html>
