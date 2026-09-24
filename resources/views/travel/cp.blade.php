@extends('layouts.app')

@section('title', 'Cestovný príkaz a vyúčtovanie')

@section('content')
@php
    $editable = $order && $order->status === 'DRAFT';
    $money = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $visibleKapz = \App\Models\KapzProfile::visibleTo(Auth::user())->orderBy('full_name')->get();
@endphp
<div class="space-y-5">
    <!-- Hlavička a akcie -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Cestovný príkaz {{ $order ? 'č. ' . $order->order_number : '' }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $kapz->full_name }} · {{ $kapz->scope }} · {{ $period->formatted_name }}
                @if($order) · <strong>{{ $order->status === 'DRAFT' ? 'rozpracovaný' : 'uzavretý' }}</strong>@endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('travel.cp.index') }}" class="flex items-center gap-2">
                @if(Auth::user()->isSupervisor())
                    <select name="kapz_id" onchange="this.form.submit()" class="p-2 border border-slate-300 rounded-xl text-xs font-semibold">
                        @foreach($visibleKapz as $k)
                            <option value="{{ $k->id }}" {{ $k->id === $kapz->id ? 'selected' : '' }}>{{ $k->full_name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="period_id" onchange="this.form.submit()" class="p-2 border border-slate-300 rounded-xl text-xs font-semibold">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $p->id === $period->id ? 'selected' : '' }}>{{ $p->formatted_name }}</option>
                    @endforeach
                </select>
            </form>
            @if($plan && $plan->status === 'APPROVED' && (!$order || $editable))
                <form method="POST" action="{{ route('travel.cp.generate') }}"
                      onsubmit="return {{ $order ? "confirm('Prepísať úseky cestovného príkazu podľa schváleného plánu? Zadané výdavky pri úsekoch sa stratia.')" : 'true' }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold">
                        {{ $order ? '↻ Načítať znova z plánu' : '➕ Vytvoriť CP zo schváleného plánu' }}
                    </button>
                </form>
            @endif
            @if($order)
                <a href="{{ route('travel.cp.reports', $order) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold">📝 Správy z pracovných ciest</a>
                <a href="{{ route('travel.cp.pdf', $order) }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold">📄 PDF cestovný príkaz</a>
                <a href="{{ route('travel.cp.settlement_pdf', $order) }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold">📄 PDF vyúčtovanie VD</a>
                <form method="POST" action="{{ route('travel.cp.status', $order) }}">
                    @csrf
                    <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-xl text-xs font-bold">
                        {{ $order->status === 'DRAFT' ? '🔒 Uzavrieť (vyúčtované)' : '🔓 Otvoriť na úpravy' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(!$order)
        <div class="bg-white p-6 rounded-2xl border border-slate-200 text-sm text-slate-600">
            @if(!$plan)
                Pre toto obdobie neexistuje plán pracovných ciest.
            @elseif($plan->status !== 'APPROVED')
                Cestovný príkaz sa vytvára zo <strong>schváleného</strong> plánu pracovných ciest (aktuálny stav plánu: {{ $plan->status }}).
            @else
                Plán je schválený – vytvorte cestovný príkaz tlačidlom vyššie. Prevezmú sa všetky úseky (Odchod/Príchod) z plánu.
            @endif
        </div>
    @else
    <form method="POST" action="{{ route('travel.cp.update', $order) }}" class="space-y-5">
        @csrf
        <fieldset {{ $editable ? '' : 'disabled' }} class="space-y-5">

        <!-- 1.–2. Hlavička cestovného príkazu -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-xs">
            <div><span class="text-slate-500">Zamestnávateľ</span> <strong>{{ config('kapz.employer') }}</strong></div>
            <div><span class="text-slate-500">Osobné číslo</span> <strong>{{ $kapz->personal_number }}</strong> · <span class="text-slate-500">Útvar</span> <strong>{{ config('kapz.department') }}</strong></div>
            <div><span class="text-slate-500">1. Priezvisko, meno, titul:</span> <strong>{{ $kapz->full_name }}</strong></div>
            <div><span class="text-slate-500">Normálny pracovný čas</span> 8 hodín, od 8:00 do 16:00</div>
            <div class="flex items-center gap-2 md:col-span-2">
                <span class="text-slate-500 whitespace-nowrap">2. Bydlisko:</span>
                <input name="residence_address" value="{{ $order->residence_address }}" placeholder="ulica, PSČ" class="flex-1 p-1.5 border border-slate-300 rounded">
                <input name="residence_city" value="{{ $order->residence_city }}" placeholder="obec" class="w-48 p-1.5 border border-slate-300 rounded">
            </div>
        </div>

        <!-- Súhrn ciest po dňoch (riadky 14–46) -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold">
                        <th class="p-2 text-left" colspan="3">Začiatok cesty (miesto, dátum, hodina)</th>
                        <th class="p-2 text-left">Miesto konania</th>
                        <th class="p-2 text-left">Účel cesty</th>
                        <th class="p-2 text-left" colspan="3">Koniec cesty (miesto, dátum, hodina)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($calc['days'] as $d)
                        <tr>
                            <td class="p-2">{{ $d['start_place'] }}</td><td class="p-2">{{ $d['date']->format('d.m.Y') }}</td><td class="p-2">{{ $d['start_time'] }}</td>
                            <td class="p-2 font-semibold">{{ $d['places'] }}</td>
                            <td class="p-2">{{ $d['purpose'] }}</td>
                            <td class="p-2">{{ $d['end_place'] }}</td><td class="p-2">{{ $d['date']->format('d.m.Y') }}</td><td class="p-2">{{ $d['end_time'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-3 text-slate-500">Cestovný príkaz nemá žiadne úseky.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3.–7. -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
            <label class="flex items-center gap-2">3. Spolucestujúci <input name="companions" value="{{ $order->companions }}" class="flex-1 p-1.5 border border-slate-300 rounded"></label>
            <label class="flex items-center gap-2">4. Určený dopravný prostriedok
                <input name="vehicle" value="{{ $order->vehicle }}" class="w-28 p-1.5 border border-slate-300 rounded">
                <input name="vehicle_plate" value="{{ $order->vehicle_plate }}" placeholder="ŠPZ" class="w-28 p-1.5 border border-slate-300 rounded">
                <input name="vehicle_model" value="{{ $order->vehicle_model }}" placeholder="typ vozidla" class="w-36 p-1.5 border border-slate-300 rounded">
            </label>
            <label class="flex items-center gap-2">5. Predpokladaná čiastka výdajov v EUR <input name="expected_costs" type="number" step="0.01" value="{{ $order->expected_costs }}" class="w-28 p-1.5 border border-slate-300 rounded"></label>
            <label class="flex items-center gap-2">6. Povolená záloha (preddavok) v EUR <input name="advance_amount" type="number" step="0.01" value="{{ $order->advance_amount }}" class="w-28 p-1.5 border border-slate-300 rounded"></label>
            <label class="flex items-center gap-2">7. Správa o výsledku pracovnej cesty bola podaná dňa <input name="report_submitted_on" type="date" value="{{ $order->report_submitted_on?->format('Y-m-d') }}" class="p-1.5 border border-slate-300 rounded"></label>
            <label class="flex items-center gap-2">Spotreba AUV (l/100 km) <input name="fuel_consumption" type="number" step="0.01" value="{{ $order->fuel_consumption }}" class="w-24 p-1.5 border border-slate-300 rounded"></label>
        </div>

        <!-- Vyúčtovanie pracovnej cesty (R82:AE271) -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <div class="p-3 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Vyúčtovanie pracovnej cesty</div>
            <table class="w-full text-[11px] border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold text-center">
                        <th class="p-1.5">Dátum</th>
                        <th class="p-1.5" colspan="2">Odchod – Príchod / Miesto rokovania</th>
                        <th class="p-1.5">o hod.</th>
                        <th class="p-1.5">Použitý dopr. prostriedok</th>
                        <th class="p-1.5">Vzdialenosť v km</th>
                        <th class="p-1.5">Začiatok a koniec prac. výkonu</th>
                        <th class="p-1.5">PHM €</th>
                        <th class="p-1.5">Amortizácia €</th>
                        <th class="p-1.5">Stravné €</th>
                        <th class="p-1.5">Nocľažné €</th>
                        <th class="p-1.5">Nutné vedľajšie výdavky €</th>
                        <th class="p-1.5">Spolu €</th>
                        <th class="p-1.5">Upravené €</th>
                        <th class="p-1.5">Cena PH €/l</th>
                    </tr>
                    <tr class="bg-slate-50 text-slate-400 text-[10px] text-center"><td>1</td><td colspan="2">2</td><td></td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td><td>8</td><td>9</td><td>10</td><td>11</td><td>12</td><td></td></tr>
                </thead>
                <tbody>
                @foreach($calc['rows'] as $row)
                    @php $s = $row['segment']; $n = "segments[{$s->id}]"; @endphp
                    <tr class="border-t border-slate-200">
                        <td class="p-1 font-bold whitespace-nowrap" rowspan="2">{{ $s->trip_date->format('d.m.Y') }}</td>
                        <td class="p-1 text-slate-500">Odchod</td>
                        <td class="p-1"><input name="{{ $n }}[from_place]" value="{{ $s->from_place }}" class="w-full p-1 border border-slate-200 rounded"></td>
                        <td class="p-1"><input name="{{ $n }}[departure_time]" value="{{ $s->departure_time }}" class="w-16 p-1 border border-slate-200 rounded"></td>
                        <td class="p-1" rowspan="2">
                            <select name="{{ $n }}[transport_mode]" class="p-1 border border-slate-200 rounded">
                                @foreach($transportModes as $code => $label)
                                    <option value="{{ $code }}" {{ $s->transport_mode === $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-1" rowspan="2"><input name="{{ $n }}[km]" type="number" step="0.1" value="{{ $num($s->km) }}" class="w-16 p-1 border border-slate-200 rounded text-right"></td>
                        <td class="p-1" rowspan="2"><input name="{{ $n }}[work_time]" value="{{ $s->work_time }}" class="w-24 p-1 border border-slate-200 rounded"></td>
                        <td class="p-1 text-right font-semibold" rowspan="2">{{ $money($row['fuel_cost']) }}</td>
                        @foreach(['amortization', 'meals', 'accommodation_cost', 'other_costs'] as $f)
                            <td class="p-1" rowspan="2"><input name="{{ $n }}[{{ $f }}]" type="number" step="0.01" min="0" value="{{ $num($s->$f) }}" class="w-20 p-1 border border-slate-200 rounded text-right"></td>
                        @endforeach
                        <td class="p-1 text-right font-bold" rowspan="2">{{ $money($row['total']) }}</td>
                        <td class="p-1" rowspan="2"><input name="{{ $n }}[adjusted]" type="number" step="0.01" value="{{ $num($s->adjusted) }}" class="w-20 p-1 border border-slate-200 rounded text-right"></td>
                        <td class="p-1" rowspan="2"><input name="{{ $n }}[fuel_price]" type="number" step="0.001" min="0" value="{{ $num($s->fuel_price) }}" class="w-20 p-1 border border-slate-200 rounded text-right"></td>
                    </tr>
                    <tr>
                        <td class="p-1 text-slate-500">Príchod</td>
                        <td class="p-1"><input name="{{ $n }}[to_place]" value="{{ $s->to_place }}" class="w-full p-1 border border-slate-200 rounded"></td>
                        <td class="p-1"><input name="{{ $n }}[arrival_time]" value="{{ $s->arrival_time }}" class="w-16 p-1 border border-slate-200 rounded"></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="font-bold bg-slate-50">
                    <tr class="border-t-2 border-slate-300">
                        <td colspan="5" class="p-2 text-right">Celkom</td>
                        <td class="p-2 text-right">{{ $num($calc['total_km']) }} km</td>
                        <td></td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['fuel_cost']) }}</td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['amortization']) }}</td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['meals']) }}</td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['accommodation_cost']) }}</td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['other_costs']) }}</td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['total']) }}</td>
                        <td class="p-2 text-right">{{ $money($calc['totals']['adjusted']) }}</td>
                        <td></td>
                    </tr>
                    <tr><td colspan="12" class="p-2 text-right">Preddavok</td><td class="p-2 text-right">{{ $money($calc['advance']) }}</td><td colspan="2"></td></tr>
                    <tr><td colspan="12" class="p-2 text-right">Doplatok – Preplatok</td><td class="p-2 text-right text-blue-700">{{ $money($calc['balance']) }}</td><td colspan="2"></td></tr>
                </tfoot>
            </table>
            <div class="p-3 border-t border-slate-100 text-[11px] text-slate-600 grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach(['meals_free' => 'Stravovanie bolo poskytnuté bezplatne', 'accommodation_free' => 'Ubytovanie bolo poskytnuté bezplatne', 'discounted_ticket' => 'Voľný – zľavnený cestovný lístok'] as $f => $label)
                    <label class="flex items-center gap-2">{{ $label }}:
                        <select name="{{ $f }}" class="p-1 border border-slate-300 rounded">
                            <option value="" {{ $order->$f === null ? 'selected' : '' }}>áno – nie</option>
                            <option value="1" {{ $order->$f === true ? 'selected' : '' }}>áno</option>
                            <option value="0" {{ $order->$f === false ? 'selected' : '' }}>nie</option>
                        </select>
                    </label>
                @endforeach
                <div class="md:col-span-3 text-slate-500">
                    Km vlastným autom (AUV): <strong>{{ $num($calc['auv_km']) }} km</strong> ·
                    orientačná náhrada {{ number_format($calc['auv_rate'], 3, ',', '') }} €/km: <strong>{{ $money($calc['auv_compensation']) }} €</strong>
                    (pomocný výpočet matice BG85, do amortizácie sa nezapisuje automaticky).
                    PHM = spotreba / 100 × km × cena PH.
                </div>
            </div>
        </div>

        <!-- 8.–9. -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 text-xs space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>8. Účtovná náhrada bola preskúšaná a upravená v EUR: <strong>{{ $money($calc['balance']) }}</strong></div>
                <div>Vyplatená záloha: <strong>{{ $money($calc['advance']) }}</strong></div>
                <div>Doplatok – Preplatok: <strong>{{ $money($calc['balance']) }}</strong></div>
            </div>
            <label class="block">9. Poznámka
                <textarea name="note" rows="2" class="w-full mt-1 p-2 border border-slate-300 rounded">{{ $order->note }}</textarea>
            </label>
        </div>

        @if($editable)
            <div class="flex justify-end">
                <button class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow">💾 Uložiť cestovný príkaz</button>
            </div>
        @endif
        </fieldset>
    </form>
    @endif

    @if($legacyOrders->isNotEmpty())
        <div class="bg-white p-4 rounded-2xl border border-slate-200 text-xs">
            <div class="font-bold text-slate-700 mb-2">Staršie cestovné príkazy (pôvodná evidencia)</div>
            @foreach($legacyOrders as $lo)
                <a href="{{ route('travel.orders.show', $lo) }}" class="text-blue-700 hover:underline mr-3">{{ $lo->order_number }}</a>
            @endforeach
        </div>
    @endif
</div>
@endsection
