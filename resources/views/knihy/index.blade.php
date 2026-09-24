@extends('layouts.app')

@section('title', 'KNIHA PRÍCHODOV A ODCHODOV')

@section('content')
<div class="space-y-6">
    <!-- Top Action & Info Bar -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="px-2.5 py-1 bg-blue-100 text-blue-800 rounded-lg text-xs font-black">ITMS: {{ $book->project_code ?? '401405DUQ8' }}</span>
                <span class="text-xs text-slate-400 font-bold uppercase">Oficiálna evidencia príchodov a odchodov</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 mt-1">KNIHA PRÍCHODOV A ODCHODOV</h1>
            <p class="text-xs text-slate-500">Zdravé regióny, Limbová 2, 831 01 Bratislava | IČO: 50626396</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Period Selector -->
            <form method="GET" action="{{ route('knihy.index') }}" class="flex items-center space-x-2">
                <input type="hidden" name="person_type" value="{{ $personType }}">
                <input type="hidden" name="person_id" value="{{ $personId }}">
                <select name="period_id" onchange="this.form.submit()"
                    class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $period->id == $p->id ? 'selected' : '' }}>
                            📅 {{ sprintf('%02d/%04d', $p->month, $p->year) }} ({{ $p->is_closed ? 'Uzamknuté' : 'Otvorené' }})
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Export Buttons -->
            <a href="{{ route('knihy.pdf', $book->id) }}"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center space-x-2">
                <span>📄</span>
                <span>Stiahnuť PDF ({{ $book->full_name }})</span>
            </a>

            <a href="{{ route('knihy.all_pdf', ['period_id' => $period->id, 'kapz_id' => $kapz->id]) }}"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center space-x-2">
                <span>📦</span>
                <span>Stiahnuť Všetky Knihy (Celý Tím)</span>
            </a>
        </div>
    </div>

    <!-- Person Selector Tabs -->
    <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-thin">
            <!-- KAPZ Tab -->
            <a href="{{ route('knihy.index', ['period_id' => $period->id, 'person_type' => 'KAPZ', 'person_id' => $kapz->id]) }}"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $personType === 'KAPZ' ? 'bg-slate-900 text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <span>👨‍💼</span>
                <span>KAPZ: {{ $kapz->full_name }}</span>
            </a>

            <div class="h-6 w-px bg-slate-200 mx-1"></div>

            <!-- APZ Tabs -->
            @foreach($apzList as $idx => $apz)
                <a href="{{ route('knihy.index', ['period_id' => $period->id, 'person_type' => 'APZ', 'person_id' => $apz->id]) }}"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center space-x-1.5 {{ $personType === 'APZ' && $personId == $apz->id ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200' }}">
                    <span>🤝</span>
                    <span>APZ {{ $idx + 1 }}: {{ $apz->full_name }} ({{ $apz->community_name ?? 'Lokalita' }})</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Active Person Details & Synchronization -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-5 rounded-2xl shadow-md flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-2xl font-bold border border-white/20">
                {{ $personType === 'KAPZ' ? '👨‍💼' : '🤝' }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-slate-300 uppercase font-bold">{{ $personType === 'KAPZ' ? 'Koordinátor asistentov podpory zdravia' : 'Asistent podpory zdravia' }}</span>
                    <span class="px-2 py-0.5 bg-blue-500/20 text-blue-300 rounded text-[10px] font-mono font-bold">{{ $book->personal_number }}</span>
                </div>
                <h2 class="text-lg font-black">{{ $book->full_name }}</h2>
                <div class="text-xs text-slate-300 flex items-center space-x-3 mt-0.5">
                    <span>📍 Lokalita: <strong>{{ $book->location }}</strong></span>
                    <span>•</span>
                    <span>✍️ Schvaľuje: <strong>{{ $book->approver_name }}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <form method="POST" action="{{ route('knihy.sync', $book->id) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold border border-white/20 transition flex items-center space-x-2"
                    title="Nanovo zosynchronizuje údaje z dochádzky">
                    <span>⚡</span>
                    <span>Znovu načítať z dochádzky</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Form & Editable Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form method="POST" action="{{ route('knihy.save', $book->id) }}">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 text-[11px] font-bold border-b border-slate-200 text-center">
                            <th rowspan="3" class="p-2 border-r border-slate-200 w-12">Dátum</th>
                            <th colspan="2" class="p-2 border-r border-slate-200 bg-blue-50/50">Príchod</th>
                            <th colspan="2" class="p-2 border-r border-slate-200 bg-emerald-50/50">Odchod</th>
                            <th colspan="4" class="p-2 border-r border-slate-200 bg-amber-50/50">Prerušenie pracovného času</th>
                            <th rowspan="3" class="p-2 border-r border-slate-200 min-w-[150px]">Dôvod odchodu</th>
                            <th rowspan="3" class="p-2 border-r border-slate-200 min-w-[160px]">Navštívené miesto</th>
                            <th rowspan="3" class="p-2 border-r border-slate-200 min-w-[140px]">Schválil</th>
                            <th rowspan="3" class="p-2 min-w-[140px]">Poznámka</th>
                        </tr>
                        <tr class="bg-slate-50 text-slate-600 text-[10px] font-bold border-b border-slate-200 text-center">
                            <th rowspan="2" class="p-1 border-r border-slate-200 w-12 bg-blue-50/30">hod.</th>
                            <th rowspan="2" class="p-1 border-r border-slate-200 w-12 bg-blue-50/30">min.</th>
                            <th rowspan="2" class="p-1 border-r border-slate-200 w-12 bg-emerald-50/30">hod.</th>
                            <th rowspan="2" class="p-1 border-r border-slate-200 w-12 bg-emerald-50/30">min.</th>
                            <th colspan="2" class="p-1 border-r border-slate-200 bg-amber-50/30">odchod</th>
                            <th colspan="2" class="p-1 border-r border-slate-200 bg-amber-50/30">príchod</th>
                        </tr>
                        <tr class="bg-slate-50 text-slate-600 text-[10px] font-bold border-b border-slate-200 text-center">
                            <th class="p-1 border-r border-slate-200 w-12 bg-amber-50/30">hod.</th>
                            <th class="p-1 border-r border-slate-200 w-12 bg-amber-50/30">min.</th>
                            <th class="p-1 border-r border-slate-200 w-12 bg-amber-50/30">hod.</th>
                            <th class="p-1 border-r border-slate-200 w-12 bg-amber-50/30">min.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($items as $item)
                            @php
                                $dt = \Carbon\Carbon::parse($item->record_date);
                                $isWeekend = $dt->isWeekend();
                                $isHoliday = $item->note === 'Sviatok';
                                $bgClass = $isWeekend ? 'bg-amber-50/40 text-amber-950 font-medium' : ($isHoliday ? 'bg-blue-50/40 text-blue-950 font-medium' : 'hover:bg-slate-50/80');
                            @endphp
                            <tr class="{{ $bgClass }} transition">
                                <!-- Day -->
                                <td class="p-1.5 text-center font-bold border-r border-slate-200">
                                    {{ $item->day_number }}
                                </td>

                                <!-- Príchod -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][arrival_hour]"
                                        value="{{ $item->arrival_hour }}"
                                        placeholder="07"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                                </td>
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][arrival_minute]"
                                        value="{{ $item->arrival_minute }}"
                                        placeholder="30"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Odchod -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][departure_hour]"
                                        value="{{ $item->departure_hour }}"
                                        placeholder="15"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                                </td>
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][departure_minute]"
                                        value="{{ $item->departure_minute }}"
                                        placeholder="30"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Prerušenie odchod -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][break_departure_hour]"
                                        value="{{ $item->break_departure_hour }}"
                                        placeholder="--"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-blue-500">
                                </td>
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][break_departure_minute]"
                                        value="{{ $item->break_departure_minute }}"
                                        placeholder="--"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Prerušenie príchod -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][break_arrival_hour]"
                                        value="{{ $item->break_arrival_hour }}"
                                        placeholder="--"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-blue-500">
                                </td>
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][break_arrival_minute]"
                                        value="{{ $item->break_arrival_minute }}"
                                        placeholder="--"
                                        maxlength="2"
                                        class="w-full text-center p-1 bg-white border border-slate-200 rounded text-xs font-mono focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Dôvod odchodu -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][break_reason]"
                                        value="{{ $item->break_reason }}"
                                        placeholder="Dôvod / Prekážka"
                                        class="w-full p-1 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Navštívené miesto -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][visited_location]"
                                        value="{{ $item->visited_location }}"
                                        placeholder="Obec / Komunita / Kancelária"
                                        class="w-full p-1 bg-white border border-slate-200 rounded text-xs font-semibold focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Schválil -->
                                <td class="p-1 border-r border-slate-200">
                                    <input type="text" name="items[{{ $item->id }}][approved_by]"
                                        value="{{ $item->approved_by }}"
                                        placeholder="Meno schvaľovateľa"
                                        class="w-full p-1 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-blue-500">
                                </td>

                                <!-- Poznámka -->
                                <td class="p-1">
                                    <input type="text" name="items[{{ $item->id }}][note]"
                                        value="{{ $item->note }}"
                                        placeholder="Poznámka / Sviatok / Víkend"
                                        class="w-full p-1 bg-white border border-slate-200 rounded text-xs focus:ring-1 focus:ring-blue-500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Bottom Action Save Bar -->
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-xs text-slate-500">
                    Evidencia za mesiac: <strong>{{ sprintf('%02d/%04d', $period->month, $period->year) }}</strong> | Počet dní: <strong>{{ count($items) }}</strong>
                </div>

                <div class="flex items-center space-x-3">
                    <button type="submit"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-500/20 transition flex items-center space-x-2">
                        <span>💾</span>
                        <span>Uložiť Zmeny v Knihe</span>
                    </button>

                    <a href="{{ route('knihy.pdf', $book->id) }}"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-black shadow-md transition flex items-center space-x-2">
                        <span>📄</span>
                        <span>Exportovať do PDF</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
