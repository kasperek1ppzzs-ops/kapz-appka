{{--
    Týždenný blok podľa hárku „Plán pracovných ciest“:
    stĺpce 1 Dátum · 2 Odchod/Príchod – miesto rokovania · o hod. · 3 Použitý dopr. prostriedok ·
    4 Vzdialenosť v km · 5 Účel cesty · 6 Stručný opis · 7 Ubytovanie · 8 Spolucestujúca osoba.
    Úsek = dvojica riadkov Odchod / Príchod.
--}}
<div class="overflow-x-auto">
    <table class="w-full border-collapse text-[11px] plan-matrix">
        <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                <th class="p-2 text-left w-24">Dátum</th>
                <th class="p-2 text-left" colspan="2">Odchod – Príchod / Miesto rokovania</th>
                <th class="p-2 text-left w-16">o hod.</th>
                <th class="p-2 text-left w-24">Použitý dopr. prostriedok</th>
                <th class="p-2 text-right w-20">Vzdialenosť v km</th>
                <th class="p-2 text-left">Účel cesty</th>
                <th class="p-2 text-left">Stručný opis plánovanej pracovnej cesty</th>
                <th class="p-2 text-left w-20">Ubytovanie</th>
                <th class="p-2 text-left w-28">Spolucestujúca osoba</th>
                @if($isEditable)<th class="p-2 w-20"></th>@endif
            </tr>
            <tr class="bg-slate-50 text-slate-400 text-[10px] border-b border-slate-200 text-center">
                <td>1</td><td colspan="2">2</td><td></td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td><td>8</td>
                @if($isEditable)<td></td>@endif
            </tr>
        </thead>
        @foreach($wData['days'] as $day)
            @php
                $dateKey = $day['date']->toDateString();
                $formId = 'day-' . $dateKey;
                $segments = $day['segments'];
                $rows = max(1, $segments->count());
            @endphp
            <tbody class="border-b-2 border-slate-200 day-block {{ $day['in_month'] ? '' : 'opacity-60' }}" data-date="{{ $dateKey }}" data-form="{{ $formId }}">
                @if($segments->isEmpty())
                    <tr class="{{ $day['absence'] ? 'bg-amber-50/60' : '' }}">
                        <td class="p-2 font-bold align-top whitespace-nowrap">
                            {{ $day['date']->format('d.m.Y') }}
                            <span class="block text-[10px] font-normal text-slate-400">{{ $day['date']->locale('sk')->isoFormat('dddd') }}</span>
                        </td>
                        <td class="p-2 text-slate-400" colspan="5">
                            @if($isEditable)
                                <span class="italic">bez cesty</span>
                            @endif
                        </td>
                        <td class="p-2"></td>
                        <td class="p-2" colspan="3">
                            @if($isEditable)
                                <input form="{{ $formId }}" name="day_note" value="{{ $day['note'] ?: '' }}" placeholder="{{ $day['absence'] ?: 'napr. Administratíva' }}"
                                       class="w-full p-1.5 border border-slate-200 rounded text-[11px]">
                            @else
                                <span class="font-semibold text-slate-700">{{ $day['note'] ?: $day['absence'] }}</span>
                            @endif
                        </td>
                        @if($isEditable)
                            <td class="p-2"></td>
                        @endif
                    </tr>
                @endif

                @foreach($segments as $i => $seg)
                    <tr class="segment-row seg-odchod" data-index="{{ $i }}">
                        @if($i === 0)
                            <td class="p-2 font-bold align-top whitespace-nowrap" rowspan="{{ $rows * 2 }}">
                                {{ $day['date']->format('d.m.Y') }}
                                <span class="block text-[10px] font-normal text-slate-400">{{ $day['date']->locale('sk')->isoFormat('dddd') }}</span>
                                @if($day['absence'])
                                    <span class="block mt-1 text-[10px] text-amber-700 font-bold">{{ $day['absence'] }}</span>
                                @endif
                            </td>
                        @endif
                        <td class="p-1 pl-2 text-slate-500 w-14">Odchod</td>
                        @if($isEditable)
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][from_place]" value="{{ $seg->from_place }}" class="seg-from w-full p-1 border border-slate-200 rounded" list="planPlaces"></td>
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][departure_time]" value="{{ $seg->departure_time }}" placeholder="8:00" class="w-full p-1 border border-slate-200 rounded"></td>
                            <td class="p-1">
                                <select form="{{ $formId }}" name="segments[{{ $i }}][transport_mode]" class="w-full p-1 border border-slate-200 rounded">
                                    @foreach($transportModes as $code => $label)
                                        <option value="{{ $code }}" {{ $seg->transport_mode === $code ? 'selected' : '' }}>{{ $code }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 whitespace-nowrap">
                                <input form="{{ $formId }}" name="segments[{{ $i }}][km]" value="{{ $seg->km + 0 }}" type="number" step="0.1" min="0" class="seg-km w-16 p-1 border border-slate-200 rounded text-right">
                                <button type="button" title="Vypočítať km (vzdialenosti.sk)" class="text-blue-600" onclick="planCalcKm(this)">↻</button>
                            </td>
                            <td class="p-1">
                                <select form="{{ $formId }}" name="segments[{{ $i }}][purpose]" class="w-full p-1 border border-slate-200 rounded">
                                    <option value=""></option>
                                    @foreach(collect($officialPurposes)->when($seg->purpose && !in_array($seg->purpose, $officialPurposes), fn ($c) => $c->push($seg->purpose)) as $p)
                                        <option value="{{ $p }}" {{ $seg->purpose === $p ? 'selected' : '' }}>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][description]" value="{{ $seg->description }}" class="w-full p-1 border border-slate-200 rounded"></td>
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][accommodation]" value="{{ $seg->accommodation ?? 'Nie' }}" class="w-full p-1 border border-slate-200 rounded"></td>
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][companions]" value="{{ $seg->companions ?? 'Nie' }}" class="w-full p-1 border border-slate-200 rounded"></td>
                            <td class="p-1 text-right whitespace-nowrap" rowspan="2">
                                <button type="button" title="Odstrániť úsek" class="px-1.5 py-0.5 text-rose-600 hover:bg-rose-50 rounded" onclick="planRemoveSegment(this)">✕</button>
                            </td>
                        @else
                            <td class="p-1 font-semibold">{{ $seg->from_place }}</td>
                            <td class="p-1">{{ $seg->departure_time }}</td>
                            <td class="p-1">{{ $seg->transport_mode }}</td>
                            <td class="p-1 text-right font-bold">{{ number_format($seg->km, 1, ',', ' ') }}</td>
                            <td class="p-1">{{ $seg->purpose }}</td>
                            <td class="p-1">{{ $seg->description }}</td>
                            <td class="p-1">{{ $seg->accommodation }}</td>
                            <td class="p-1">{{ $seg->companions }}</td>
                        @endif
                    </tr>
                    <tr class="segment-row seg-prichod border-b border-slate-100" data-index="{{ $i }}">
                        <td class="p-1 pl-2 text-slate-500">Príchod</td>
                        @if($isEditable)
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][to_place]" value="{{ $seg->to_place }}" class="seg-to w-full p-1 border border-slate-200 rounded" list="planPlaces"></td>
                            <td class="p-1"><input form="{{ $formId }}" name="segments[{{ $i }}][arrival_time]" value="{{ $seg->arrival_time }}" placeholder="9:00" class="w-full p-1 border border-slate-200 rounded"></td>
                        @else
                            <td class="p-1 font-semibold">{{ $seg->to_place }}</td>
                            <td class="p-1">{{ $seg->arrival_time }}</td>
                        @endif
                        <td colspan="6"></td>
                    </tr>
                @endforeach

                @if($isEditable)
                    <tr class="day-actions bg-slate-50/60">
                        <td colspan="11" class="p-1.5 text-right">
                            @if($segments->isNotEmpty())
                                <input form="{{ $formId }}" name="day_note" value="{{ $day['note'] ?: '' }}" placeholder="Poznámka dňa (nepovinné)"
                                       class="p-1 border border-slate-200 rounded text-[11px] w-56 mr-2">
                            @endif
                            <button type="button" class="px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded font-bold" onclick="planAddSegment(this)">+ úsek</button>
                            <button type="submit" form="{{ $formId }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold">💾 Uložiť deň</button>
                        </td>
                    </tr>
                @endif
            </tbody>
        @endforeach
        <tfoot>
            <tr class="bg-slate-50 font-bold">
                <td colspan="5" class="p-2 text-right">Súčet km za týždeň ({{ $wData['calendar_week'] }}.):</td>
                <td class="p-2 text-right text-blue-700">{{ number_format($wData['total_km'], 1, ',', ' ') }}</td>
                <td colspan="{{ $isEditable ? 5 : 4 }}"></td>
            </tr>
        </tfoot>
    </table>
</div>

@if($isEditable)
    @foreach($wData['days'] as $day)
        <form id="day-{{ $day['date']->toDateString() }}" method="POST" action="{{ route('travel.save_day', $plan) }}" class="hidden">
            @csrf
            <input type="hidden" name="date" value="{{ $day['date']->toDateString() }}">
        </form>
    @endforeach
@endif
