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

    <div class="section-title">1. STRUČNÝ PREHĽAD VYKONANEJ ČINNOSTI A AKTIVÍT</div>
    <div class="body-text">
        {{ $report->summary_text ?? 'Bez popisu činnosti.' }}
    </div>

    @if($report->metrics_json)
        <div class="section-title">2. KVANITATÍVNE METRIKY A VÝKONNOSŤ INŠTITÚCIE</div>
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
