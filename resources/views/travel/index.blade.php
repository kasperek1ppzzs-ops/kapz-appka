@extends('layouts.app')

@section('title', 'Týždenný Plán Pracovných Ciest - KAPZ')

@section('content')
<div class="space-y-6">

    <!-- Horná lišta / Hlavička -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="p-2.5 bg-blue-100 text-blue-700 rounded-xl font-black text-lg">🗺️</span>
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-xl font-black text-slate-900">Týždenný Plán Pracovných Ciest KAPZ</h1>
                        <span class="px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-wider
                            {{ $plan->status == 'APPROVED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}
                            {{ $plan->status == 'SUBMITTED' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '' }}
                            {{ $plan->status == 'DRAFT' ? 'bg-slate-100 text-slate-800 border border-slate-300' : '' }}
                            {{ $plan->status == 'RETURNED' ? 'bg-rose-100 text-rose-800 border border-rose-300' : '' }}">
                            ● {{ $plan->status == 'APPROVED' ? 'Schválené ZFK' : ($plan->status == 'SUBMITTED' ? 'Odoslané na schválenie' : ($plan->status == 'RETURNED' ? 'Vrátené na doplnenie' : 'Koncept')) }} (Verzia {{ $plan->version }})
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Koordinátor: <strong class="text-slate-800">{{ $kapz->full_name }}</strong> | 
                        Pôsobnosť: <strong class="text-slate-800">{{ $kapz->scope }}</strong> | 
                        Číslo CP: <span class="font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $plan->order_number }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Filter Obdobia -->
            <form action="{{ route('travel.index') }}" method="GET" class="flex items-center space-x-2">
                <label class="text-xs font-bold text-slate-500">Obdobie:</label>
                <select name="period_id" onchange="this.form.submit()" class="p-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-slate-50">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $p->id == $period->id ? 'selected' : '' }}>
                            {{ $p->formatted_name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if(in_array($plan->status, ['DRAFT', 'RETURNED']))
                <form action="{{ route('travel.submit') }}" method="POST" onsubmit="return confirm('Naozaj chcete odoslať tento mesačný plán pracovných ciest na schválenie Expertovi pre terén?')">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center space-x-1.5">
                        <span>🚀 Odoslať na Schválenie</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('travel.pdf', $plan->id) }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5" title="Stiahnuť kompletný 5-týždňový plán ciest">
                <span>📦 Stiahnuť Všetky Týždne (Kompletné PDF)</span>
            </a>
        </div>
    </div>

    <!-- Alert pri vrátení plánu -->
    @if($plan->status == 'RETURNED' && $plan->admin_notes)
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl shadow-sm">
            <div class="flex items-start space-x-3">
                <span class="text-rose-600 text-lg">⚠️</span>
                <div>
                    <h4 class="text-xs font-black uppercase text-rose-800 tracking-wider">Plán bol vrátený Expertom pre terén na doplnenie / opravu:</h4>
                    <p class="text-xs text-rose-700 mt-1 font-medium">{{ $plan->admin_notes }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Alert pri schválení plánu -->
    @if($plan->status == 'APPROVED')
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-emerald-600 text-lg">✅</span>
                <div class="text-xs text-emerald-900">
                    <strong>Plán je riadne schválený v rámci Základnej finančnej kontroly (ZFK) podľa § 7 zákona č. 357/2015 Z.z.</strong><br>
                    <span class="text-emerald-700">Schválil: {{ $plan->reviewedBy->name ?? 'Expert pre terén' }} dňa {{ $plan->reviewed_at ? date('d.m.Y H:i', strtotime($plan->reviewed_at)) : '-' }}</span>
                </div>
            </div>
            <span class="text-xs font-black px-2.5 py-1 bg-emerald-200 text-emerald-900 rounded-lg">ZFK OVERENÁ</span>
        </div>
    @endif

    <!-- Mesačný Limit KM & Štatistika (Hárok limity a prac. dni) -->
    <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Mesačný limit km pre región {{ $kapz->scope }}</span>
                <div class="flex items-baseline space-x-2 mt-0.5">
                    <span class="text-2xl font-black text-blue-400">{{ number_format($totalKm, 1) }}</span>
                    <span class="text-sm font-bold text-slate-400">/ {{ number_format($kmLimit, 1) }} km</span>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-xs font-bold mb-1.5">
                    <span class="text-slate-300">Čerpanie limitu</span>
                    <span class="{{ $isOverLimit ? 'text-rose-400' : 'text-blue-300' }}">{{ $kmPercentage }} %</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden border border-slate-700">
                    <div class="h-3 rounded-full transition-all duration-500 {{ $isOverLimit ? 'bg-rose-500' : ($kmPercentage > 85 ? 'bg-amber-400' : 'bg-blue-500') }}"
                         style="width: {{ min(100, $kmPercentage) }}%"></div>
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Zostávajúca rezerva</span>
                <span class="text-xl font-black {{ $isOverLimit ? 'text-rose-400' : 'text-emerald-400' }}">
                    {{ $isOverLimit ? '-' . number_format($totalKm - $kmLimit, 1) . ' km (prekročené!)' : number_format($kmRemaining, 1) . ' km' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Formulár: Pridať novú jazdu do plánu (iba ak je DRAFT alebo RETURNED) -->
    @if(in_array($plan->status, ['DRAFT', 'RETURNED']))
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span>Naplánovať Pracovnú Cestu do Týždňa</span>
                </h3>
                <span class="text-[11px] text-slate-400">Zadajte údaje o trase, účele a odhadovaných kilometroch</span>
            </div>

            <form action="{{ route('travel.add_item') }}" method="POST" class="space-y-4" id="travelItemForm">
                @csrf
                <input type="hidden" name="travel_plan_id" value="{{ $plan->id }}">

                <!-- 1. Riadok: Týždeň, Dátum cesty a Trasa -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Týždeň -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">1. Týždeň v mesiaci *</label>
                        <select name="week_number" id="week_number" required class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-bold text-blue-800 bg-blue-50 focus:ring-2 focus:ring-blue-500">
                            @for($w = 1; $w <= 5; $w++)
                                <option value="{{ $w }}">
                                    {{ $w }}. Týždeň ({{ $weeksData[$w]['calendar_week'] }}. kalendárny týždeň)
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- Dátum cesty -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">2. Dátum cesty *</label>
                        <input type="date" name="trip_date" id="trip_date" required 
                               min="{{ $period->year }}-{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}-01"
                               max="{{ date('Y-m-t', strtotime($period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT) . '-01')) }}"
                               value="{{ $period->year }}-{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}-03"
                               class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Východisková lokalita -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Miesto Odchodu (Východisková) *</label>
                        <input type="text" name="departure_location" id="departure_location" value="{{ $kapz->scope }}" required 
                               onchange="autoCalculateDistance()"
                               class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500" placeholder="napr. Banská Bystrica">
                    </div>

                    <!-- Cieľová lokalita -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Miesto Určenia (Cieľová Lokalita) *
                        </label>
                        <input type="text" name="destination_location" id="destination_location" list="destinationsList" required 
                               onchange="autoCalculateDistance()" oninput="debounceCalculateDistance()"
                               class="w-full p-2.5 border border-blue-400 bg-blue-50/30 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-blue-500" 
                               placeholder="Zadajte obec alebo kliknite nižšie...">
                        <datalist id="destinationsList">
                            @foreach($commonDestinations as $dest)
                                <option value="{{ $dest }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <!-- 2. Riadok: Časový harmonogram celej cesty (4 časy podľa matice) -->
                <div class="p-3.5 bg-gradient-to-r from-blue-50/70 via-indigo-50/50 to-slate-50 border border-blue-200 rounded-xl">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-black uppercase text-blue-900 tracking-wider flex items-center space-x-1.5">
                            <span>⏱️ Časový harmonogram celej cesty (zaznamenané všetky 4 časy podľa pôvodného plánu):</span>
                        </span>
                        <span class="text-[10px] text-blue-600 font-semibold">Odchod z bazy &rarr; Príchod do cieľa &rarr; Odchod z cieľa &rarr; Príchod späť</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white p-2.5 rounded-lg border border-blue-100 shadow-2xs">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                🚗 1. Odchod z východiskovej *
                            </label>
                            <input type="time" name="departure_time" value="08:00" required 
                                   class="w-full p-2 border border-slate-300 rounded-lg text-xs font-bold text-blue-900 focus:ring-2 focus:ring-blue-500" 
                                   title="Čas odchodu z bydliska / kancelárie">
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-blue-100 shadow-2xs">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                🏁 2. Príchod do cieľovej
                            </label>
                            <input type="time" name="arrival_at_dest_time" value="09:00" 
                                   class="w-full p-2 border border-slate-300 rounded-lg text-xs font-bold text-blue-900 focus:ring-2 focus:ring-blue-500" 
                                   title="Čas príchodu na miesto rokovania">
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-indigo-100 shadow-2xs">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                🔄 3. Odchod z cieľovej
                            </label>
                            <input type="time" name="departure_from_dest_time" value="14:00" 
                                   class="w-full p-2 border border-slate-300 rounded-lg text-xs font-bold text-indigo-900 focus:ring-2 focus:ring-indigo-500" 
                                   title="Čas odchodu z miesta rokovania po ukončení výkonu">
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-indigo-100 shadow-2xs">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                🏠 4. Príchod späť *
                            </label>
                            <input type="time" name="arrival_time" value="15:00" required 
                                   class="w-full p-2 border border-slate-300 rounded-lg text-xs font-bold text-indigo-900 focus:ring-2 focus:ring-indigo-500" 
                                   title="Čas návratu do východiskovej lokality">
                        </div>
                    </div>
                </div>

                <!-- 1-KLIK VÝBER PRIRADENÝCH OBCÍ APZ TÍMU (S PODPOROU VIACERÝCH LOKALÍT V 1 DEŇ) -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase text-slate-700 tracking-wider flex items-center space-x-1.5">
                            <span>⚡ Rýchly výber obcí vášho tímu na 1 klik: <span class="text-blue-700 font-bold lowercase">(kliknutím pridáte aj viacero lokalít do 1 dňa)</span></span>
                        </span>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] text-slate-400 hidden sm:inline">Kliknutím na obec pridáte/odoberiete zastávku</span>
                            <button type="button" onclick="clearDestinations()" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded text-[10px] font-bold transition">
                                ✕ Vyčistiť výber
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($assignedApzs as $apzItem)
                            @if($apzItem->scope)
                                <button type="button" 
                                        data-village="{{ $apzItem->scope }}"
                                        data-apz-id="{{ $apzItem->id }}"
                                        onclick="selectVillage('{{ $apzItem->scope }}', '{{ $apzItem->id }}')" 
                                        class="village-chip px-2.5 py-1 bg-white hover:bg-blue-600 hover:text-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 transition shadow-2xs flex items-center space-x-1">
                                    <span class="chip-icon">📍</span>
                                    <span>{{ $apzItem->scope }}</span>
                                    <span class="text-[10px] opacity-75">({{ $apzItem->full_name }})</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                    <!-- Náhľad poskladanej trasy cez viacero lokalít -->
                    <div id="routePreviewSummary" class="text-xs p-2 bg-blue-50/80 border border-blue-200 rounded-lg hidden"></div>
                    <input type="hidden" name="target_apz_id" id="target_apz_id" value="">
                </div>

                <!-- Účel cesty a Dopravný prostriedok -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Stručný opis plánovanej pracovnej cesty (Účel) *
                        </label>
                        <select id="purposeSelect" onchange="applyOfficialPurpose(this.value)" class="w-full p-2 border border-slate-300 rounded-xl text-xs mb-2 text-slate-700 bg-slate-50">
                            <option value="">-- Vyberte oficiálny účel z číselníka (alebo vpíšte vlastný nižšie) --</option>
                            @foreach($officialPurposes as $pText)
                                <option value="{{ $pText }}">{{ Str::limit($pText, 95) }}</option>
                            @endforeach
                        </select>
                        <textarea name="purpose" id="purposeText" rows="2" required 
                                  class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-500" 
                                  placeholder="Vpíšte účel pracovnej cesty alebo zvoľte z číselníka vyššie..."></textarea>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Dopravný Prostriedok *</label>
                            <select name="transport_mode" required class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-white">
                                @foreach($transportModes as $code => $label)
                                    <option value="{{ $code }}" {{ $code == 'AUV' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-700">Predpokladané km *</label>
                                <a id="vzdialenostiLink" href="https://www.vzdialenosti.sk" target="_blank" rel="noopener noreferrer" 
                                   class="text-[10px] font-bold text-blue-600 hover:text-blue-800 hover:underline flex items-center space-x-1" 
                                   title="Overiť trasu priamo na webe vzdialenosti.sk">
                                    <span>🌐 Overiť na vzdialenosti.sk</span>
                                </a>
                            </div>

                            <div class="flex items-center space-x-2">
                                <input type="number" step="0.5" min="1" name="estimated_km" id="estimated_km" value="30" required 
                                       class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-black text-blue-700 focus:ring-2 focus:ring-blue-500">
                                <div class="flex space-x-1">
                                    <button type="button" onclick="setKm(15)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-xs font-bold rounded">15</button>
                                    <button type="button" onclick="setKm(30)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-xs font-bold rounded">30</button>
                                    <button type="button" onclick="setKm(60)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-xs font-bold rounded">60</button>
                                </div>
                            </div>

                            <!-- Indikátor stavu zo vzdialenosti.sk & Prepínač Tam a späť -->
                            <div class="flex items-center justify-between mt-2 pt-1 border-t border-slate-100">
                                <label class="inline-flex items-center space-x-1.5 cursor-pointer text-[11px] font-bold text-slate-700 select-none">
                                    <input type="checkbox" id="isRoundTrip" onchange="toggleRoundTrip()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Tam aj späť (2× km)</span>
                                </label>
                                <div id="distanceStatus" class="text-[10px] font-semibold text-slate-500"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center space-x-2">
                        <span>➕ Pridať Pracovnú Cestu do Plánu</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Rozdelenie na 5 Kalendárnych Týždňov (Karty 1. až 5. Týždeň) -->
    <div class="space-y-6">
        @for($w = 1; $w <= 5; $w++)
            @php
                $wData = $weeksData[$w];
                $items = $wData['items'];
                $weekKm = $wData['total_km'];
                $calWeek = $wData['calendar_week'];
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Hlavička týždňa -->
                <div class="p-4 bg-slate-100 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-600 text-white font-black text-sm flex items-center justify-center shadow-xs">
                            {{ $w }}
                        </span>
                        <div>
                            <h3 class="font-black text-slate-900 text-sm uppercase tracking-wider">
                                {{ $w }}. TÝŽDEŇ <span class="text-blue-700 font-bold">({{ $calWeek }}. kalendárny týždeň)</span>
                            </h3>
                            <span class="text-[11px] text-slate-500">Číslo CP: {{ $plan->order_number }}</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-bold px-3 py-1 bg-blue-50 text-blue-800 border border-blue-200 rounded-full">
                            Súčet za týždeň: <strong>{{ number_format($weekKm, 1) }} km</strong>
                        </span>
                        <a href="{{ route('travel.pdf', ['plan' => $plan->id, 'week' => $w]) }}" 
                           class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1"
                           title="Stiahnuť PDF plánu pre {{ $w }}. týždeň">
                            <span>📄 PDF {{ $w }}. týždeň</span>
                        </a>
                    </div>
                </div>

                <!-- Tabuľka jázd v danom týždni -->
                @if($items->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-[11px] font-bold uppercase border-b border-slate-200">
                                    <th class="p-3 w-28">1. Dátum</th>
                                    <th class="p-3">2. Miesto rokovania (Trasa cesty)</th>
                                    <th class="p-3 w-40 text-center">Časový harmonogram</th>
                                    <th class="p-3">6. Stručný opis plánovanej cesty (Účel)</th>
                                    <th class="p-3 w-24 text-center">7. Doprava</th>
                                    <th class="p-3 w-24 text-right">8. Odhad km</th>
                                    @if(in_array($plan->status, ['DRAFT', 'RETURNED']))
                                        <th class="p-3 w-16 text-center">Akcie</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                @php
                                    $itemsByDate = $items->groupBy('trip_date');
                                @endphp
                                @foreach($items as $item)
                                    @php
                                        $dests = array_filter(array_map('trim', preg_split('/[,;+]+/', $item->destination_location)));
                                        $isMultiLoc = count($dests) > 1;
                                        $sameDayCount = $itemsByDate[$item->trip_date]->count();
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="p-3 font-bold text-slate-900 whitespace-nowrap">
                                            {{ date('d.m.Y', strtotime($item->trip_date)) }}<br>
                                            <span class="text-[10px] text-slate-400 font-normal">{{ \Carbon\Carbon::parse($item->trip_date)->translatedFormat('l') }}</span>
                                            @if($sameDayCount > 1)
                                                <span class="block mt-1 px-1.5 py-0.5 bg-amber-100 text-amber-900 border border-amber-200 rounded text-[9px] font-bold w-fit">
                                                    ⚡ Cesta v daný deň
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @if($isMultiLoc)
                                                <div class="space-y-1">
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="text-[9px] font-black px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded">
                                                            🛣️ OKRUŽNÁ TRASA ({{ count($dests) }} LOKALITY)
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-slate-800 font-medium flex flex-wrap items-center gap-1">
                                                        <span class="text-slate-600">{{ $item->departure_location }}</span>
                                                        @foreach($dests as $d)
                                                            <span class="text-blue-500 font-bold">&rarr;</span>
                                                            <span class="font-bold text-blue-900 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">📍 {{ $d }}</span>
                                                        @endforeach
                                                        <span class="text-blue-500 font-bold">&rarr;</span>
                                                        <span class="text-slate-600 font-medium">{{ $item->departure_location }}</span>
                                                    </div>
                                                    @if($item->targetApz)
                                                        <div class="text-[10px] text-slate-500 font-normal">APZ tím: {{ $item->targetApz->full_name }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="space-y-1">
                                                    <div class="flex items-center space-x-1.5 text-xs">
                                                        <span class="text-[9px] font-black px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded">TAM</span>
                                                        <span class="text-slate-600">{{ $item->departure_location }}</span>
                                                        <span class="text-slate-400">&rarr;</span>
                                                        <span class="font-bold text-slate-900">📍 {{ $item->destination_location }}</span>
                                                    </div>
                                                    <div class="flex items-center space-x-1.5 text-xs">
                                                        <span class="text-[9px] font-black px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded">SPÄŤ</span>
                                                        <span class="text-slate-600">📍 {{ $item->destination_location }}</span>
                                                        <span class="text-slate-400">&rarr;</span>
                                                        <span class="text-slate-700">{{ $item->departure_location }}</span>
                                                    </div>
                                                    @if($item->targetApz)
                                                        <div class="text-[10px] text-slate-500 font-normal">APZ: {{ $item->targetApz->full_name }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3 text-center whitespace-nowrap">
                                            <div class="space-y-1 font-mono text-[11px]">
                                                <div class="text-blue-900 font-semibold bg-blue-50/70 px-2 py-0.5 rounded border border-blue-100" title="Odchod z východiskovej → Príchod do cieľovej">
                                                    <span class="text-[9px] text-blue-600 font-bold font-sans uppercase">Tam:</span>
                                                    {{ $item->departure_time ?: '08:00' }} &rarr; {{ $item->arrival_at_dest_time ?: '09:00' }}
                                                </div>
                                                <div class="text-indigo-900 bg-indigo-50/70 px-2 py-0.5 rounded border border-indigo-100" title="Odchod z cieľovej → Príchod do východiskovej späť">
                                                    <span class="text-[9px] text-indigo-600 font-bold font-sans uppercase">Späť:</span>
                                                    {{ $item->departure_from_dest_time ?: '14:00' }} &rarr; {{ $item->arrival_time ?: '15:00' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-slate-700 max-w-md">
                                            {{ $item->purpose }}
                                        </td>
                                        <td class="p-3 text-center font-bold text-slate-700">
                                            <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[11px]">
                                                {{ $item->transport_mode }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right font-black text-blue-700 whitespace-nowrap">
                                            {{ number_format($item->estimated_km, 1) }} km
                                        </td>
                                        @if(in_array($plan->status, ['DRAFT', 'RETURNED']))
                                            <td class="p-3 text-center whitespace-nowrap">
                                                <form action="{{ route('travel.delete_item', $item->id) }}" method="POST" onsubmit="return confirm('Naozaj chcete vymazať túto cestu?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Odstrániť jazdu">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-6 text-center text-slate-400 text-xs">
                        V {{ $w }}. týždni ({{ $calWeek }}. kalendárny týždeň) zatiaľ nie sú naplánované žiadne pracovné cesty.
                    </div>
                @endif

                <!-- Päta týždenného plánu: Klauzula, Spracoval/a, ZFK & Samostatný Export do PDF -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200/80 pb-2.5">
                        <div class="border-l-4 border-blue-600 pl-3">
                            <p class="text-xs font-bold text-slate-800 italic">
                                „Harmonogram pracovných ciest je v súlade s náplňou opisu pracovnej činnosti práce Koordinátor asistentov podpory zdravia.“
                            </p>
                            <p class="text-xs text-slate-700 mt-1">
                                Spracoval/a: <strong class="text-slate-900">{{ $kapz->full_name }}</strong>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <a href="{{ route('travel.pdf', ['plan' => $plan->id, 'week' => $w]) }}" 
                               class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5"
                               title="Exportovať plán na {{ $w }}. týždeň do samostatného PDF">
                                <span>📄 Stiahnuť PDF ({{ $w }}. týždeň)</span>
                            </a>
                        </div>
                    </div>

                    <!-- Blok Základnej finančnej kontroly (ZFK) -->
                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-xs space-y-2">
                        <div class="font-black text-slate-900 uppercase tracking-wide text-[11px] flex items-center justify-between border-b border-slate-100 pb-1.5">
                            <span>Základná finančná kontrola (ZFK) podľa § 7 zákona NR SR č. 357/2015 Z.z.</span>
                            <span class="text-[10px] font-semibold text-slate-400">NP ZK, ITMS 401405DUQ8</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                            <div>
                                <span class="text-slate-400 block text-[10px]">Popis finančnej operácie (FO):</span>
                                <strong class="text-slate-800">Pracovné stretnutie a porada v zdravotníckych a sociálnych zariadeniach</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Zdôvodnenie:</span>
                                <strong class="text-slate-800">V súvislosti s realizáciou NP ZK, kód ITMS 401405DUQ8</strong>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 pt-2 text-[11px] text-slate-600 flex flex-col md:flex-row md:items-center md:justify-between gap-1">
                            <div>
                                Overenie súladu finančnej operácie so skutočnosťami podľa § 6 ods. 4 zákona č. 357/2015 Z.z. vykonáva príslušný <strong>Expert pre terén / Nadriadený</strong>.
                            </div>
                            <div class="text-[10px] font-bold">
                                @if($plan->status == 'APPROVED')
                                    <span class="text-emerald-700">✓ Overil/a: {{ $plan->reviewedBy->name ?? ($plan->kapz->region_expert ?? 'Expert pre terén') }} ({{ date('d.m.Y', strtotime($plan->reviewed_at)) }})</span>
                                @else
                                    <span class="text-amber-600 font-semibold">Čaká na schválenie ZFK</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

</div>

<!-- Pomocné JavaScripty pre výber viacerých obcí, okružné trasy a výpočet km -->
<script>
    let baseOneWayKm = 30;
    let baseRoundTripKm = 60;
    let isMultiStopRoute = false;
    let debounceTimer = null;

    function selectVillage(villageName, apzId) {
        const destInput = document.getElementById('destination_location');
        const apzInput = document.getElementById('target_apz_id');
        if (!destInput) return;

        let currentVal = destInput.value.trim();
        let villages = currentVal ? currentVal.split(/[,+;]+/).map(s => s.trim()).filter(Boolean) : [];

        const index = villages.findIndex(v => v.toLowerCase() === villageName.toLowerCase());
        if (index > -1) {
            // Already present, remove it (toggle off)
            villages.splice(index, 1);
        } else {
            // Add new village to route
            villages.push(villageName);
        }

        destInput.value = villages.join(', ');
        if (apzInput && villages.length === 1) {
            apzInput.value = apzId;
        } else if (apzInput && villages.length === 0) {
            apzInput.value = '';
        }

        updateVillageButtonsState();
        autoCalculateDistance();
    }

    function clearDestinations() {
        const destInput = document.getElementById('destination_location');
        const apzInput = document.getElementById('target_apz_id');
        if (destInput) {
            destInput.value = '';
        }
        if (apzInput) {
            apzInput.value = '';
        }
        updateVillageButtonsState();
        autoCalculateDistance();
    }

    function updateVillageButtonsState() {
        const destInput = document.getElementById('destination_location');
        const currentVal = destInput ? destInput.value.trim().toLowerCase() : '';
        const villages = currentVal ? currentVal.split(/[,+;]+/).map(s => s.trim()).filter(Boolean) : [];
        
        document.querySelectorAll('.village-chip').forEach(btn => {
            const vName = (btn.getAttribute('data-village') || '').toLowerCase();
            const icon = btn.querySelector('.chip-icon');
            if (villages.includes(vName)) {
                btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
                if (icon) icon.textContent = '✓';
            } else {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
                if (icon) icon.textContent = '📍';
            }
        });

        const summaryElem = document.getElementById('routePreviewSummary');
        if (summaryElem) {
            const fromInput = document.getElementById('departure_location');
            const fromVal = fromInput ? fromInput.value.trim() : 'Východisko';
            if (villages.length > 1) {
                summaryElem.innerHTML = `
                    <div class="flex flex-wrap items-center gap-1.5 font-medium text-slate-800">
                        <span class="px-1.5 py-0.5 bg-blue-600 text-white rounded text-[10px] font-black uppercase tracking-wider">Okružná trasa (${villages.length} lokality)</span>
                        <span class="text-slate-600">${fromVal}</span>
                        ${villages.map(v => `<span class="text-blue-600 font-bold">&rarr;</span> <span class="font-bold text-blue-900 bg-white px-1.5 py-0.5 rounded border border-blue-200">📍 ${v}</span>`).join(' ')}
                        <span class="text-blue-600 font-bold">&rarr;</span>
                        <span class="text-slate-600 font-medium">${fromVal}</span>
                    </div>`;
                summaryElem.classList.remove('hidden');
            } else if (villages.length === 1) {
                summaryElem.innerHTML = `
                    <div class="flex items-center space-x-1.5 text-slate-700">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Trasa:</span>
                        <span>${fromVal}</span>
                        <span class="text-blue-600">&rarr;</span>
                        <strong class="text-blue-900">📍 ${villages[0]}</strong>
                        <span class="text-blue-600">&rarr;</span>
                        <span>${fromVal}</span>
                    </div>`;
                summaryElem.classList.remove('hidden');
            } else {
                summaryElem.classList.add('hidden');
            }
        }
    }

    function debounceCalculateDistance() {
        updateVillageButtonsState();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            autoCalculateDistance();
        }, 600);
    }

    function autoCalculateDistance() {
        const fromInput = document.getElementById('departure_location');
        const toInput = document.getElementById('destination_location');
        const statusDiv = document.getElementById('distanceStatus');
        const kmInput = document.getElementById('estimated_km');
        const isRoundTrip = document.getElementById('isRoundTrip');
        const linkElem = document.getElementById('vzdialenostiLink');

        if (!fromInput || !toInput || !toInput.value.trim()) {
            if (statusDiv) statusDiv.innerHTML = '';
            return;
        }

        const fromVal = fromInput.value.trim();
        const toVal = toInput.value.trim();

        if (statusDiv) {
            statusDiv.innerHTML = '<span class="text-blue-600 animate-pulse font-bold">⚡ Prepočítavam trasu...</span>';
        }

        fetch(`{{ route('travel.calculate_distance') }}?from=${encodeURIComponent(fromVal)}&to=${encodeURIComponent(toVal)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.distance_km > 0) {
                    baseOneWayKm = data.distance_km;
                    baseRoundTripKm = data.round_trip_km;
                    isMultiStopRoute = Boolean(data.is_multi_stop);

                    const finalKm = (isRoundTrip && isRoundTrip.checked) ? data.round_trip_km : data.distance_km;
                    if (kmInput) {
                        kmInput.value = finalKm;
                    }
                    if (statusDiv) {
                        if (data.is_multi_stop) {
                            statusDiv.innerHTML = `<span class="text-emerald-700 font-bold">✓ Okružná trasa cez ${data.stop_count} lokality: ${finalKm} km</span>`;
                        } else {
                            const badgeText = data.source === 'vzdialenosti.sk' ? 'vzdialenosti.sk' : 'cestná sieť SR';
                            statusDiv.innerHTML = `<span class="text-emerald-700 font-bold">✓ ${badgeText}: ${finalKm} km</span>`;
                        }
                    }
                    if (linkElem) {
                        linkElem.href = `https://www.vzdialenosti.sk/`;
                    }
                } else if (data.success && data.distance_km === 0) {
                    if (statusDiv) {
                        statusDiv.innerHTML = `<span class="text-slate-500 font-semibold">Rovnaké miesto (0 km)</span>`;
                    }
                } else {
                    if (statusDiv) {
                        statusDiv.innerHTML = `<span class="text-amber-600 font-semibold">Overte na vzdialenosti.sk</span>`;
                    }
                }
            })
            .catch(err => {
                console.warn('Distance calculation error:', err);
                if (statusDiv) {
                    statusDiv.innerHTML = '';
                }
            });
    }

    function toggleRoundTrip() {
        const isRoundTrip = document.getElementById('isRoundTrip');
        const kmInput = document.getElementById('estimated_km');
        if (!kmInput) return;

        if (isRoundTrip && isRoundTrip.checked) {
            kmInput.value = baseRoundTripKm.toFixed(1);
        } else {
            kmInput.value = baseOneWayKm.toFixed(1);
        }
    }

    // Inicializácia pri načítaní stránky
    document.addEventListener('DOMContentLoaded', () => {
        updateVillageButtonsState();
    });

    function applyOfficialPurpose(purposeText) {
        if (!purposeText) return;
        const textarea = document.getElementById('purposeText');
        if (textarea) {
            textarea.value = purposeText;
        }
    }

    function setKm(val) {
        baseOneWayKm = val;
        const isRoundTrip = document.getElementById('isRoundTrip');
        const kmInput = document.getElementById('estimated_km');
        if (kmInput) {
            kmInput.value = (isRoundTrip && isRoundTrip.checked) ? (val * 2) : val;
        }
    }
</script>
@endsection
