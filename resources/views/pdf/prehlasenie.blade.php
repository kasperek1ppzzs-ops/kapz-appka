<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>ČESTNÉ PREHLÁSENIE</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; padding: 20px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 20px; color: #1e3a8a; }
        .header-box { width: 100%; border: 1px solid #9ca3af; padding: 10px; margin-bottom: 20px; }
        .body-text { line-height: 1.6; text-align: justify; margin-bottom: 30px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="title">{{ $statement->title }}</div>

    <div class="header-box">
        <strong>Meno a priezvisko KAPZ:</strong> {{ $statement->kapz->full_name }}<br>
        <strong>Osobné číslo:</strong> {{ $statement->kapz->personal_number }}<br>
        <strong>Pôsobnosť / Lokalita:</strong> {{ $statement->kapz->scope }}<br>
        <strong>Obdobie:</strong> {{ $statement->reportingPeriod->formatted_name }}
    </div>

    <div class="body-text">
        {{ $statement->body_content }}
    </div>

    <div style="margin-top: 50px;">
        V {{ $statement->kapz->scope }}, dňa {{ date('d.m.Y') }}
    </div>

    <table style="margin-top: 60px; width: 100%;">
        <tr>
            <td style="text-align: right; width: 100%;">
                ...............................................................<br>
                Podpis KAPZ (Vlastnoručný / Elektronický)
            </td>
        </tr>
    </table>
</body>
</html>
