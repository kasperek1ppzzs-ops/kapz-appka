@extends('layouts.app')

@section('title', 'Prehlásenie o činnosti mimo pracovného pomeru')

@section('content')
<div class="space-y-6">
    <!-- Period & Action Controls -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-black text-slate-900">Prehlásenie o činnosti mimo pracovného pomeru</h1>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold font-mono">
                    {{ sprintf('%02d/%04d', $period->month, $period->year) }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Zdravé regióny, Limbová 2, 837 52 Bratislava | Koordinátor: <strong>{{ $kapz->full_name }}</strong> ({{ $kapz->scope }})
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Period Selector -->
            <form action="{{ route('statements.index') }}" method="GET" class="inline">
                @if($isExpertOrAdmin && $kapz)
                    <input type="hidden" name="kapz_id" value="{{ $kapz->id }}">
                @endif
                <select name="period_id" onchange="this.form.submit()" class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 shadow-sm">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $period->id == $p->id ? 'selected' : '' }}>
                            📅 {{ $p->formatted_name }} ({{ sprintf('%02d/%04d', $p->month, $p->year) }})
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Quick Fill 1-click button -->
            <form action="{{ route('statements.quick_fill_no', $declaration->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                    <span>⚡</span>
                    <span>Predvyplniť všetkým: Nie / Nie</span>
                </button>
            </form>

            <!-- Official PDF Download -->
            <a href="{{ route('statements.pdf', $declaration->id) }}"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                <span>📄</span>
                <span>Stiahnuť Oficiálne PDF</span>
            </a>
        </div>
    </div>

    <!-- MAIN STATEMENT FORM & TABLE -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Official Institutional Header Box matching Excel Template -->
        <div class="p-6 border-b border-slate-100 bg-slate-50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center text-center md:text-left border-b border-slate-200 pb-5 mb-5">
                <!-- Left Logo Badge: EU -->
                <div class="flex items-center justify-center md:justify-start space-x-3">
                    <div class="w-12 h-8 bg-blue-700 text-amber-300 flex items-center justify-center font-bold text-xs rounded shadow-sm border border-blue-900">
                        ★★★★
                    </div>
                    <div class="text-left">
                        <div class="text-[11px] font-black text-slate-800 uppercase tracking-tight leading-tight">Spolufinancované</div>
                        <div class="text-[11px] font-black text-slate-800 uppercase tracking-tight leading-tight">Európskou úniou</div>
                    </div>
                </div>

                <!-- Middle Logo Badge: Program Slovensko -->
                <div class="flex items-center justify-center space-x-2">
                    <div class="w-6 h-6 rounded bg-gradient-to-tr from-rose-500 via-indigo-600 to-blue-500 flex items-center justify-center text-white text-[10px] font-black shadow-sm">
                        ::
                    </div>
                    <div class="text-left">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">PROGRAM</div>
                        <div class="text-sm font-black text-slate-900 uppercase tracking-tight">SLOVENSKO</div>
                    </div>
                </div>

                <!-- Right Logo Badge: MZ SR -->
                <div class="flex items-center justify-center md:justify-end space-x-2.5 text-right">
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">MINISTERSTVO</div>
                        <div class="text-[11px] font-black text-slate-900 uppercase tracking-tight leading-tight">ZDRAVOTNÍCTVA</div>
                        <div class="text-[9px] font-semibold text-slate-500 uppercase tracking-tight leading-none">SLOVENSKEJ REPUBLIKY</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        ǂ
                    </div>
                </div>
            </div>

            <!-- Title & Subtitle in Box -->
            <div class="text-center">
                <h2 class="text-lg font-black text-slate-900 uppercase tracking-wide">
                    Prehlásenie o činnosti mimo pracovného pomeru
                </h2>
                <div class="text-xs text-slate-600 font-semibold mt-1">
                    Zdravé regióny, Limbová 2, 837 52 Bratislava
                </div>
                <div class="mt-2 inline-block px-4 py-1 bg-white border border-slate-300 rounded-lg text-xs font-black text-slate-900 shadow-sm">
                    Obdobie: {{ sprintf('%02d/%04d', $period->month, $period->year) }}
                </div>
            </div>
        </div>

        <!-- Editable Statement Table -->
        <form action="{{ route('statements.save', $declaration->id) }}" method="POST">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-800 text-[11px] font-black uppercase border-b border-slate-300 divide-x divide-slate-200 text-center">
                            <th class="p-3 w-20 text-center">Os. čís.</th>
                            <th class="p-3 w-64 text-left">Meno zamestnanca</th>
                            <th class="p-3 w-48">
                                <div>Vykonával/a som zárobkovú</div>
                                <div class="text-[10px] text-slate-500 font-bold lowercase">činnosť v danom mesiaci (áno/nie)</div>
                            </th>
                            <th class="p-3 w-48">
                                <div>Je daná práca</div>
                                <div class="text-[10px] text-slate-500 font-bold lowercase">financovaná z EŠIF? (áno/nie)</div>
                            </th>
                            <th class="p-3">
                                <div>Typ pracovného úväzku</div>
                                <div class="text-[10px] text-slate-500 font-bold lowercase">(výber zo zoznamu)</div>
                            </th>
                            <th class="p-3 w-32">Dňa</th>
                            <th class="p-3 w-32">Podpis</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs">
                        @foreach($items as $item)
                            <tr class="hover:bg-blue-50/40 transition {{ $item->person_type === 'KAPZ' ? 'bg-blue-50/30 font-semibold' : '' }}" id="row-{{ $item->id }}">
                                <!-- 1. Os. čís. -->
                                <td class="p-3 text-center font-bold font-mono text-slate-900 border-r border-slate-200">
                                    {{ $item->personal_number }}
                                </td>

                                <!-- 2. Meno zamestnanca -->
                                <td class="p-3 border-r border-slate-200">
                                    <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                        <span>{{ $item->full_name }}</span>
                                        @if($item->person_type === 'KAPZ')
                                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded text-[9px] font-black uppercase">KAPZ</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 3. Vykonával/a som zárobkovú činnosť -->
                                <td class="p-2 text-center border-r border-slate-200">
                                    <select name="items[{{ $item->id }}][has_gainful_activity]"
                                        id="gainful-{{ $item->id }}"
                                        onchange="toggleContractRow({{ $item->id }})"
                                        class="w-full p-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-center focus:ring-2 focus:ring-blue-500 shadow-sm">
                                        <option value="nie" {{ $item->has_gainful_activity === 'nie' ? 'selected' : '' }}>nie</option>
                                        <option value="áno" {{ $item->has_gainful_activity === 'áno' ? 'selected' : '' }}>áno</option>
                                    </select>
                                </td>

                                <!-- 4. Je daná práca financovaná z EŠIF -->
                                <td class="p-2 text-center border-r border-slate-200">
                                    <select name="items[{{ $item->id }}][is_funded_by_esif]"
                                        id="esif-{{ $item->id }}"
                                        class="w-full p-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-center focus:ring-2 focus:ring-blue-500 shadow-sm">
                                        <option value="nie" {{ $item->is_funded_by_esif === 'nie' ? 'selected' : '' }}>nie</option>
                                        <option value="áno" {{ $item->is_funded_by_esif === 'áno' ? 'selected' : '' }}>áno</option>
                                    </select>
                                </td>

                                <!-- 5. Typ pracovného úväzku -->
                                <td class="p-2 border-r border-slate-200">
                                    <select name="items[{{ $item->id }}][contract_type]"
                                        id="contract-{{ $item->id }}"
                                        class="w-full p-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500 shadow-sm {{ $item->has_gainful_activity === 'nie' ? 'bg-slate-100 text-slate-400' : '' }}"
                                        {{ $item->has_gainful_activity === 'nie' ? 'disabled' : '' }}>
                                        <option value="">-- Vyberte zo zoznamu --</option>
                                        @foreach($contractTypes as $cType)
                                            <option value="{{ $cType }}" {{ $item->contract_type == $cType ? 'selected' : '' }}>
                                                {{ $cType }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- 6. Dňa -->
                                <td class="p-2 text-center border-r border-slate-200">
                                    <input type="date" name="items[{{ $item->id }}][signature_date]"
                                        value="{{ $item->signature_date ? $item->signature_date->format('Y-m-d') : '' }}"
                                        class="w-full p-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold text-center">
                                </td>

                                <!-- 7. Podpis -->
                                <td class="p-2 text-center">
                                    <div class="h-7 border border-dashed border-slate-300 rounded-lg bg-slate-50/50 flex items-center justify-center text-[10px] text-slate-400 italic">
                                        (ručný podpis)
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Bottom Save Bar -->
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    Spolu záznamov: <strong>{{ count($items) }}</strong> (1 KAPZ + {{ count($items) - 1 }} APZ)
                </div>

                <div class="flex items-center space-x-3">
                    <button type="submit"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-500/20 transition flex items-center space-x-2">
                        <span>💾</span>
                        <span>Uložiť Prehlásenie</span>
                    </button>

                    <a href="{{ route('statements.pdf', $declaration->id) }}"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-black shadow-md transition flex items-center space-x-2">
                        <span>📄</span>
                        <span>Exportovať do PDF</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript to handle dynamic toggle of Contract Type -->
<script>
    function toggleContractRow(itemId) {
        const gainfulSelect = document.getElementById('gainful-' + itemId);
        const contractSelect = document.getElementById('contract-' + itemId);
        const esifSelect = document.getElementById('esif-' + itemId);

        if (gainfulSelect.value === 'nie') {
            contractSelect.value = '';
            contractSelect.disabled = true;
            contractSelect.classList.add('bg-slate-100', 'text-slate-400');
            esifSelect.value = 'nie';
        } else {
            contractSelect.disabled = false;
            contractSelect.classList.remove('bg-slate-100', 'text-slate-400');
        }
    }
</script>
@endsection
