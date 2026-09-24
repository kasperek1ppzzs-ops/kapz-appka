<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>EVIDENCIA ODPRACOVANEJ DOBY APZ</title>
    <style>
        @page { margin: 10px 15px 10px 15px; }
        * { font-family: 'DejaVu Sans', sans-serif !important; }
        body { font-family: 'DejaVu Sans', sans-serif !important; font-size: 8px; color: #000000; margin: 0; padding: 0; }

        .header-logo-container { width: 100%; margin-bottom: 3px; text-align: center; }
        .header-logo-container img { width: 100%; max-height: 65px; object-fit: contain; }

        .doc-title { font-size: 11px; font-weight: bold; text-align: left; text-transform: uppercase; margin-top: 2px; margin-bottom: 3px; color: #000000; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 8px; }
        .meta-table td { padding: 1px 2px; vertical-align: middle; }

        .unified-table { width: 100%; border-collapse: collapse; border: 1.2px solid #000000; }
        .unified-table th, .unified-table td { border: 1px solid #000000; padding: 0.5px 2px; font-size: 7.2px; text-align: center; height: 10.5px; }
        .unified-table th { font-weight: bold; background-color: #ffffff; text-align: center; }
        .weekend-row { background-color: #ffedd5; font-weight: bold; }

        .label-cell { text-align: left; padding-left: 3px; }
        .val-cell { font-weight: bold; text-align: center; }
        .highlight-row { background-color: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>

    @php
        $img2Path = public_path('images/image2.jpeg');
        $img6Path = public_path('images/image6.jpg');
        $img5Path = public_path('images/image5.jpeg');
        $imgPath = file_exists($img2Path) ? $img2Path : (file_exists($img6Path) ? $img6Path : $img5Path);
        $bannerBase64 = file_exists($imgPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imgPath)) : null;
    @endphp

    <!-- Prominent High-Res Official Header Banner -->
    @if($bannerBase64)
        <div class="header-logo-container">
            <img src="{{ $bannerBase64 }}" alt="Official Logos Header" />
        </div>
    @endif

    <div class="doc-title">EVIDENCIA ODPRACOVANEJ DOBY APZ</div>

    <!-- Metadata Grid -->
    <table class="meta-table">
        <tr>
            <td style="width: 6%;"><strong>Firma:</strong></td>
            <td style="width: 25%;">Zdravé regióny</td>
            <td style="width: 7%;"><strong>Mesiac:</strong></td>
            <td style="width: 15%;">{{ $period->month }}</td>
            <td style="width: 47%;"></td>
        </tr>
        <tr>
            <td><strong>Meno :</strong></td>
            <td><strong>{{ $apz->full_name }}</strong></td>
            <td><strong>Os. č.:</strong></td>
            <td><strong>{{ $apz->personal_number }}</strong></td>
            <td style="width: 7%;"><strong>Oblasť:</strong></td>
            <td style="width: 20%;">{{ $apz->community_scope }}</td>
            <td style="width: 5%;"><strong>Rok:</strong></td>
            <td>{{ $period->year }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>KAPZ:</strong> {{ $kapz->full_name }} ({{ $kapz->personal_number }})</td>
            <td colspan="6"><strong>Úväzok APZ:</strong> {{ $apz->employment_ratio * 100 }}%</td>
        </tr>
    </table>

    @php
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year);
        $stdHours = ($apz->employment_ratio ?? 1.0) * 7.5;

        $summaryRows = [
            1 => ['label' => 'Počet pracovných dní', 'val' => $summary['total_working_days'], 'bold' => true],
            2 => ['label' => 'Z toho sviatky', 'val' => $summary['sviatky_days'] ?: '', 'bold' => false],
            3 => ['label' => 'Z toho dovolenka', 'val' => $summary['holiday_days'] ?: '', 'bold' => false],
            4 => ['label' => 'Práce neschopnosť (nemoc)', 'val' => $summary['pn_days'] ?: '', 'bold' => false],
            5 => ['label' => 'OČR', 'val' => $summary['ocr_days'] ?: '', 'bold' => false],
            6 => ['label' => 'MD, RD', 'val' => '', 'bold' => false],
            7 => ['label' => 'Osobné prekážky v práci – KZ', 'val' => '', 'bold' => false],
            8 => ['label' => 'Prekážky v práci - (platené) / lekár', 'val' => $summary['doctor_days'] ?: '', 'bold' => false],
            9 => ['label' => 'Prekážky v práci - (platené) / lekár - doprovod', 'val' => $summary['doctor_family_days'] ?: '', 'bold' => false],
            10 => ['label' => 'Prekážky v práci - (platené) / krv', 'val' => '', 'bold' => false],
            11 => ['label' => 'Prekážky v práci - (platené) / pohreb', 'val' => '', 'bold' => false],
            12 => ['label' => 'Prekážky v práci - (neplatené)', 'val' => '', 'bold' => false],
            13 => ['label' => 'Skutočne odpracované dni', 'val' => $summary['actual_work_days'], 'bold' => true],
            14 => ['label' => 'Finančný príspevok - dni', 'val' => $summary['financial_days'], 'bold' => true],
            15 => ['label' => 'Iné:', 'val' => '', 'bold' => false],
        ];
    @endphp

    <!-- Single Unified 8-Column Table -->
    <table class="unified-table">
        <thead>
            <tr>
                <th style="width: 4%;">Deň</th>
                <th style="width: 20%;">Pracovisko</th>
                <th style="width: 17%;">Odpracované</th>
                <th style="width: 9%;">Počet hodín</th>
                <th style="width: 16%;">Neodpracované</th>
                <th style="width: 7%;">Počet hodín</th>
                <th style="width: 22%;">Nevyplňovať</th>
                <th style="width: 5%;">dni / €</th>
            </tr>
        </thead>
        <tbody>
            @for($d = 1; $d <= 31; $d++)
                @php
                    $dateStr = sprintf('%04d-%02d-%02d', $period->year, $period->month, $d);
                    $entry = $summary['entries']->firstWhere('date', $dateStr);
                    $dayNum = date('N', strtotime($dateStr));
                    $isWeekend = in_array($dayNum, [6, 7]);
                    $isWithinMonth = $d <= $daysInMonth;
                    $status = $entry ? $entry->status : ($isWeekend ? 'weekend' : 'work');
                    $isWork = $status == 'work' && $isWithinMonth && !$isWeekend;
                @endphp
                <tr class="{{ $isWeekend ? 'weekend-row' : '' }}">
                    <td>{{ $d }}</td>
                    <td class="label-cell">{{ $isWork ? ($entry && $entry->workplace ? $entry->workplace : ($entry && $entry->activity_description ? $entry->activity_description : $apz->community_scope)) : '' }}</td>
                    <td></td>
                    <td>{{ $isWork ? number_format($entry ? $entry->hours_worked : $stdHours, 1, ',', '') : '' }}</td>
                    <td class="label-cell">
                        @if(!$isWork && $isWithinMonth && !$isWeekend)
                            @switch($status)
                                @case('holiday') Dovolenka @break
                                @case('pn') Práce neschopnosť (nemoc) @break
                                @case('ocr') OČR @break
                                @case('doctor') Lekár @break
                                @case('doctor_family') Lekár - doprovod @break
                                @case('public_holiday') Sviatok @break
                                @default {{ $status }}
                            @endswitch
                        @endif
                    </td>
                    <td>{{ (!$isWork && $isWithinMonth && !$isWeekend) ? number_format($entry ? $entry->hours_worked : $stdHours, 1, ',', '') : '' }}</td>

                    @if(isset($summaryRows[$d]))
                        <td class="label-cell {{ $summaryRows[$d]['bold'] ? 'highlight-row' : '' }}">
                            {{ $summaryRows[$d]['label'] }}
                        </td>
                        <td class="val-cell {{ $summaryRows[$d]['bold'] ? 'highlight-row' : '' }}">
                            {{ $summaryRows[$d]['val'] }}
                        </td>
                    @elseif($d == 16)
                        <td rowspan="16" style="vertical-align: top; padding: 4px; text-align: left; background-color: #ffffff;">
                            <strong>Podpis:</strong><br>
                            zamestnanca<br><br><br>
                            ...................................
                        </td>
                        <td rowspan="16" style="vertical-align: top; padding: 4px; text-align: left; background-color: #ffffff;">
                            <strong>Podpis:</strong><br>
                            nadriadeného (KAPZ)<br><br><br>
                            ...................................
                        </td>
                    @endif
                </tr>
            @endfor
            <tr style="font-weight: bold; background-color: #ffffff;">
                <td colspan="3" style="text-align: left; padding-left: 4px;">Spolu</td>
                <td>{{ number_format($summary['total_work_hours'], 2, ',', '') }}</td>
                <td></td>
                <td>{{ number_format($summary['total_absence_hours'], 2, ',', '') }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
