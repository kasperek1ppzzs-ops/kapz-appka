<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ZOZNAM KONTAKTOV</title>
    <style>
        @page { margin: 20px 25px 20px 25px; }
        * { font-family: 'DejaVu Sans', sans-serif !important; }
        body { font-family: 'DejaVu Sans', sans-serif !important; font-size: 7.5px; color: #000000; margin: 0; padding: 0; }

        .section-block { width: 100%; margin-bottom: 12px; }
        .page-break { page-break-after: always; }

        .section-header-box {
            width: 100%;
            background-color: #b4c6e7;
            border: 1px solid #000000;
            border-bottom: none;
            padding: 3px 6px;
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            box-sizing: border-box;
        }

        .director-header-box {
            background-color: #ffd966 !important;
        }

        .contacts-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 6px;
        }

        .contacts-table th, .contacts-table td {
            border: 1px solid #000000;
            padding: 1.5px 3px;
            font-size: 6.8px;
            vertical-align: middle;
        }

        .contacts-table th {
            font-weight: bold;
            background-color: #ffffff;
            text-align: center;
            font-size: 7px;
        }

        .center-cell { text-align: center; }
        .num-col { width: 4%; text-align: center; }
        .name-col { width: 20%; font-weight: bold; }
        .email-col { width: 23%; }
        .phone-col { width: 13%; text-align: center; }
        .scope-col { width: 14%; text-align: center; }
        .pos-col { width: 26%; }
        .expert-col { width: 20%; }
    </style>
</head>
<body>

    @php
        $page1Sections = ['Koordinátori asistentov podpory zdravia'];
        $page2Sections = [
            'Experti pre terén',
            'Regionálni manažéri podpory zdravia',
            'Manažéri asistentov podpory zdravia v prostredí nemocníc',
            'Ekonomické oddelenie',
            'Riaditeľka príspevkovej organizácie a hlavná expertka národného projektu'
        ];
        $page3Sections = [
            'Oddelenie pre metodiku',
            'Oddelenie vzdelávania a rozvoja ľudských zdrojov',
            'Oddelenie pre terén',
            'Oddelenie pre monitoring'
        ];
        $page4Sections = [
            'Projektové oddelenie',
            'IT oddelenie',
            'PR oddelenie'
        ];
    @endphp

    @foreach($grouped as $sectionTitle => $contacts)
        @php
            $isKapz = ($sectionTitle === 'Koordinátori asistentov podpory zdravia');
            $isDirector = stripos($sectionTitle, 'Riaditeľka') !== false;
        @endphp

        <div class="section-block">
            <!-- Section Header -->
            <div class="section-header-box {{ $isDirector ? 'director-header-box' : '' }}">
                {{ $sectionTitle }}
            </div>

            <!-- Table -->
            <table class="contacts-table">
                <thead>
                    <tr>
                        <th class="num-col">P. č.</th>
                        <th class="name-col">Priezvisko a meno</th>
                        <th class="email-col">E-mail</th>
                        <th class="phone-col">Tel. číslo</th>
                        <th class="scope-col">Pôsobnosť/lokalita</th>
                        <th class="{{ $isKapz ? 'pos-col' : '' }}" style="{{ !$isKapz ? 'width: 30%;' : '' }}">Pracovná pozícia</th>
                        @if($isKapz)
                            <th class="expert-col">Príslušnosť k Expertovi pre terén</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $c)
                        <tr>
                            <td class="center-cell">{{ $c->order_num ?: $loop->iteration }}</td>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->email }}</td>
                            <td class="center-cell">{{ $c->phone }}</td>
                            <td class="center-cell">{{ $c->scope }}</td>
                            <td>{{ $c->position }}</td>
                            @if($isKapz)
                                <td>{{ $c->region_expert }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($isKapz || $sectionTitle === 'Riaditeľka príspevkovej organizácie a hlavná expertka národného projektu' || $sectionTitle === 'Oddelenie pre monitoring')
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
