<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Plán pracovných ciest – {{ $plan->order_number }}</title>
    {{-- Predloha: hárok „Plán pracovných ciest“, oblasť tlače A1:L62 (1 strana = 1 kalendárny týždeň) --}}
    <style>
        @page { margin: 10mm 9mm; size: a4 portrait; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #000; margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .title { font-size: 12px; font-weight: bold; text-align: center; margin: 0 0 6px 0; }
        .head { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .head td { padding: 1.5px 2px; font-size: 8px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { border: 0.6px solid #000; padding: 1.5px 2px; vertical-align: middle; }
        .grid th { font-size: 7px; font-weight: bold; text-align: center; background: #f2f2f2; }
        .grid .num td { text-align: center; font-size: 6.5px; background: #f2f2f2; }
        .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
        .clause { margin-top: 6px; font-size: 8px; }
        .zfk { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .zfk td { border: 0.6px solid #000; padding: 2px 3px; font-size: 7.5px; vertical-align: top; }
    </style>
</head>
<body>
@php
    $weeks = $selectedWeek ? array_intersect_key($weeks, [$selectedWeek => true]) : $weeks;
    $position = config('kapz.kapz_position');
@endphp
@foreach($weeks as $w => $wData)
    <div class="page">
        <div class="title">Plán pracovných ciest &nbsp; {{ $wData['calendar_week'] }} &nbsp; kalendárny týždeň</div>

        <table class="head">
            <tr>
                <td style="width: 20%;">Meno a priezvisko:</td>
                <td class="b" style="width: 40%;">{{ $plan->kapz->full_name }}</td>
                <td style="width: 15%;">Mesiac/Rok</td>
                <td class="b">{{ $plan->order_number }}</td>
            </tr>
            <tr>
                <td>Pôsobnosť:</td>
                <td class="b">{{ $plan->kapz->scope }}</td>
                <td>Pracovná pozícia</td>
                <td class="b">{{ $position }}</td>
            </tr>
        </table>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 9%;">Dátum</th>
                    <th colspan="2" style="width: 21%;">Odchod - Príchod<br>Miesto rokovania</th>
                    <th style="width: 6%;">o hod.</th>
                    <th style="width: 7%;">Použitý dopr.<br>prostriedok</th>
                    <th style="width: 7%;">Vzdialenosť<br>v km</th>
                    <th style="width: 17%;">Účel cesty</th>
                    <th style="width: 18%;">Stručný opis plánovej pracovnej cesty</th>
                    <th style="width: 7%;">Ubytovanie</th>
                    <th style="width: 8%;">Spolucestujúca osoba</th>
                </tr>
                <tr class="num"><td>1</td><td colspan="2">2</td><td></td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td><td>8</td></tr>
            </thead>
            <tbody>
            @foreach($wData['days'] as $day)
                @if($day['segments']->isEmpty())
                    <tr>
                        <td class="c" rowspan="2">{{ $day['date']->format('d.m.Y') }}</td>
                        <td style="width: 6%;">Odchod</td><td></td><td></td><td></td><td></td><td></td>
                        <td rowspan="2">{{ $day['note'] ?: $day['absence'] }}</td><td></td><td></td>
                    </tr>
                    <tr><td>Príchod</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                @else
                    @foreach($day['segments'] as $i => $seg)
                        <tr>
                            <td class="c" rowspan="2">{{ $day['date']->format('d.m.Y') }}</td>
                            <td style="width: 6%;">Odchod</td>
                            <td>{{ $seg->from_place }}</td>
                            <td class="c">{{ $seg->departure_time }}</td>
                            <td class="c" rowspan="2">{{ $seg->transport_mode }}</td>
                            <td class="r" rowspan="2">{{ rtrim(rtrim(number_format($seg->km, 2, ',', ''), '0'), ',') }}</td>
                            <td rowspan="2">{{ $seg->purpose }}</td>
                            <td rowspan="2">{{ $seg->description ?: ($i === 0 ? ($day['note'] ?: '') : '') }}</td>
                            <td class="c" rowspan="2">{{ $seg->accommodation }}</td>
                            <td class="c" rowspan="2">{{ $seg->companions }}</td>
                        </tr>
                        <tr>
                            <td>Príchod</td>
                            <td>{{ $seg->to_place }}</td>
                            <td class="c">{{ $seg->arrival_time }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            </tbody>
        </table>

        <div class="clause">Harmonogram pracovných ciest je v súlade s náplňou opisu pracovnej činnosti práce <b>{{ $position }}</b></div>
        <table class="head" style="margin-top: 8px;">
            <tr>
                <td style="width: 15%;">Spracoval/a:</td>
                <td class="b" style="width: 45%;">{{ $plan->kapz->full_name }}</td>
                <td>podpis ..........................................</td>
            </tr>
        </table>

        <table class="zfk">
            <tr><td colspan="4" class="b">Základná finančná kontrola (ZFK) podľa § 7 zákona NR SR č. 357/2015 Z.z.</td></tr>
            <tr><td colspan="2">Popis fin.operácie (FO) alebo jej časti:</td><td colspan="2" class="b">Plán pracovných ciest</td></tr>
            <tr><td colspan="2">Zdôvodnenie:</td><td colspan="2">V súvislosti s realizáciou NP ZK, kód ITMS <b>{{ config('kapz.itms_code') }}</b></td></tr>
            <tr><td colspan="4">Overenie súladu finančnej operácie alebo jej časti so skutočnosťami podľa § 6 ods. 4 zákona NR SR č. 357/2015 Z.z. vykonali:</td></tr>
            <tr><td colspan="4">1. v súlade so všeobecne záväznými právnymi predpismi</td></tr>
            <tr>
                <td style="width: 30%; height: 22px;">Meno a priezvisko<br><b>{{ config('kapz.zfk_verifier') }}</b></td>
                <td style="width: 15%;">Dátum</td>
                <td style="width: 35%;">Fin. operácia alebo jej časť je možné - nie je možné pokračovať *</td>
                <td>Podpis</td>
            </tr>
            <tr><td colspan="4">2. Na základe vyjadrenia podľa bodu 1. fin. operáciu alebo jej časť je možné - nie je možné vykonať:*</td></tr>
            <tr>
                <td style="height: 22px;">Meno a priezvisko<br><b>{{ $plan->kapz->region_expert ?: ($plan->reviewedBy->name ?? '') }}</b></td>
                <td>Dátum<br>{{ $plan->status === 'APPROVED' && $plan->reviewed_at ? \Carbon\Carbon::parse($plan->reviewed_at)->format('d.m.Y') : '' }}</td>
                <td colspan="2">Podpis</td>
            </tr>
        </table>
        <div style="font-size: 7px; margin-top: 2px;">* nehodiace sa prečiarknite</div>
    </div>
@endforeach
</body>
</html>
