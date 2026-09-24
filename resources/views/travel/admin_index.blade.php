@extends('layouts.app')

@section('title', 'Schvaľovanie Plánov Ciest - Expert pre terén')

@section('content')
<div class="space-y-6">

    <!-- Horná lišta -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="p-2.5 bg-indigo-100 text-indigo-700 rounded-xl font-black text-lg">🛡️</span>
                <div>
                    <h1 class="text-xl font-black text-slate-900">Schvaľovanie Týždenných Plánov Ciest KAPZ</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pohľad Experta pre terén / Nadriadeného | Základná finančná kontrola (ZFK) podľa § 7 zákona č. 357/2015 Z.z.
                    </p>
                </div>
            </div>
        </div>

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
    </div>

    <!-- Súhrnné štatistické karty -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Celkovo Plánov</span>
            <span class="text-2xl font-black text-slate-800">{{ $plans->count() }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/20 shadow-xs">
            <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">⏳ Čaká na Schválenie</span>
            <span class="text-2xl font-black text-amber-600">{{ $plans->where('status', 'SUBMITTED')->count() }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-emerald-200 bg-emerald-50/20 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">✅ Schválené ZFK</span>
            <span class="text-2xl font-black text-emerald-600">{{ $plans->where('status', 'APPROVED')->count() }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-rose-200 bg-rose-50/20 shadow-xs">
            <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider block">↩️ Vrátené na Úpravu</span>
            <span class="text-2xl font-black text-rose-600">{{ $plans->where('status', 'RETURNED')->count() }}</span>
        </div>
    </div>

    <!-- Zoznam plánov jednotlivých KAPZ -->
    <div class="space-y-6">
        @forelse($plans as $planItem)
            @php
                $kapzProfile = $planItem->kapz;
                $totKm = $planItem->items->sum('estimated_km');
                $limKm = (float) $planItem->km_limit;
                $isOver = $limKm > 0 && $totKm > $limKm;
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Hlavička karty plánu -->
                <div class="p-5 bg-slate-50 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <div class="flex items-center space-x-2.5">
                            <h3 class="font-black text-slate-900 text-base">{{ $kapzProfile->full_name }}</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider
                                {{ $planItem->status == 'APPROVED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}
                                {{ $planItem->status == 'SUBMITTED' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '' }}
                                {{ $planItem->status == 'DRAFT' ? 'bg-slate-100 text-slate-700 border border-slate-300' : '' }}
                                {{ $planItem->status == 'RETURNED' ? 'bg-rose-100 text-rose-800 border border-rose-300' : '' }}">
                                {{ $planItem->status }} (Verzia {{ $planItem->version }})
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Pôsobnosť: <strong>{{ $kapzProfile->scope }}</strong> | 
                            Číslo CP: <span class="font-mono font-bold text-blue-700">{{ $planItem->order_number }}</span> | 
                            Osobné číslo: {{ $kapzProfile->personal_number }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <!-- KM Merač -->
                        <div class="text-right">
                            <span class="text-[11px] text-slate-400 font-bold uppercase block">Nájazd voči limitu</span>
                            <span class="font-black text-sm {{ $isOver ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ number_format($totKm, 1) }} / {{ number_format($limKm, 1) }} km
                            </span>
                        </div>

                        <!-- Tlačidlá schvaľovania -->
                        <div class="flex items-center space-x-2">
                            @if($planItem->status == 'SUBMITTED')
                                <!-- Schváliť ZFK -->
                                <form action="{{ route('travel.approve') }}" method="POST" onsubmit="return confirm('Schváliť tento plán pracovných ciest a potvrdiť ZFK?')">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $planItem->id }}">
                                    <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                                        <span>✅ Schváliť ZFK</span>
                                    </button>
                                </form>

                                <!-- Vrátiť s poznámkou -->
                                <button type="button" onclick="openReturnModal('{{ $planItem->id }}', '{{ $kapzProfile->full_name }}')" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                                    <span>↩️ Vrátiť</span>
                                </button>
                            @endif

                            <a href="{{ route('travel.pdf', $planItem->id) }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1" title="Stiahnuť kompletný mesačný plán ciest">
                                <span>📦 PDF (Komplet)</span>
                            </a>
                        </div>
                    </div>
                </div>

                @if($planItem->admin_notes)
                    <div class="px-5 py-2.5 bg-amber-50/70 border-b border-amber-200 text-xs text-amber-900">
                        <strong>Poznámka / Dôvod vrátenia:</strong> {{ $planItem->admin_notes }}
                    </div>
                @endif

                <!-- Prehľad trás po 5 týždňoch -->
                <div class="p-5 space-y-4">
                    @if($planItem->items->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            @for($w = 1; $w <= 5; $w++)
                                @php
                                    $wItems = $planItem->items->where('week_number', $w);
                                    $wKm = $wItems->sum('estimated_km');
                                @endphp
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between border-b border-slate-200 pb-1.5 mb-2">
                                            <div>
                                                <span class="text-xs font-black text-slate-800">{{ $w }}. TÝŽDEŇ</span>
                                                <span class="text-[11px] font-bold text-blue-700 block">{{ number_format($wKm, 0) }} km</span>
                                            </div>
                                            <a href="{{ route('travel.pdf', ['plan' => $planItem->id, 'week' => $w]) }}" 
                                               class="px-2 py-0.5 bg-white border border-slate-300 hover:border-slate-500 rounded text-[10px] font-bold text-slate-700 transition"
                                               title="Stiahnuť PDF {{ $w }}. týždňa">
                                                📄 PDF
                                            </a>
                                        </div>
                                        <div class="space-y-1.5 text-[11px]">
                                            @forelse($wItems as $it)
                                                <div class="bg-white p-1.5 rounded border border-slate-200">
                                                    <div class="font-bold text-slate-800">{{ date('d.m.', strtotime($it->trip_date)) }}: 📍 {{ $it->destination_location }}</div>
                                                    <div class="text-[10px] text-slate-500 truncate" title="{{ $it->purpose }}">{{ $it->purpose }}</div>
                                                    <div class="text-[9px] font-mono text-slate-500 mt-0.5">
                                                        {{ $it->departure_time ?: '08:00' }} &rarr; {{ $it->arrival_at_dest_time ?: '09:00' }} | {{ $it->departure_from_dest_time ?: '14:00' }} &rarr; {{ $it->arrival_time ?: '15:00' }}
                                                    </div>
                                                    <div class="text-[10px] font-mono text-blue-600 font-bold text-right">{{ number_format($it->estimated_km, 0) }} km</div>
                                                </div>
                                            @empty
                                                <span class="text-slate-400 text-[10px]">Žiadne cesty</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    @else
                        <p class="text-xs text-slate-400 text-center py-2">Tento koordinátor zatiaľ nezadal žiadne plánované cesty.</p>
                    @endif

                    <!-- Päta karty plánu: Klauzula a ZFK -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                        <div class="border-l-4 border-blue-600 pl-3">
                            <p class="text-xs font-bold text-slate-800 italic">
                                „Harmonogram pracovných ciest je v súlade s náplňou opisu pracovnej činnosti práce Koordinátor asistentov podpory zdravia.“
                            </p>
                            <p class="text-xs text-slate-700 mt-1">
                                Spracoval/a: <strong class="text-slate-900">{{ $kapzProfile->full_name }}</strong>
                            </p>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-slate-200 text-xs space-y-1.5">
                            <div class="font-black text-slate-900 uppercase tracking-wide text-[11px] border-b border-slate-100 pb-1 flex items-center justify-between">
                                <span>Základná finančná kontrola (ZFK) podľa § 7 zákona NR SR č. 357/2015 Z.z.</span>
                                <span class="text-[10px] text-slate-400 font-semibold">NP ZK, ITMS 401405DUQ8</span>
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
                            <div class="border-t border-slate-100 pt-1.5 text-[11px] text-slate-600 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                <div>
                                    Overenie súladu finančnej operácie so skutočnosťami podľa § 6 ods. 4 zákona č. 357/2015 Z.z. vykonáva príslušný <strong>Expert pre terén / Nadriadený</strong>.
                                </div>
                                <div class="text-[10px] font-bold">
                                    @if($planItem->status == 'APPROVED')
                                        <span class="text-emerald-700">✓ Overil/a: {{ $planItem->reviewedBy->name ?? 'Expert pre terén' }} ({{ date('d.m.Y', strtotime($planItem->reviewed_at)) }})</span>
                                    @else
                                        <span class="text-amber-600 font-semibold">Čaká na schválenie ZFK</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 text-slate-400">
                Pre vybrané obdobie {{ $period->formatted_name }} zatiaľ neboli zaevidované žiadne plány pracovných ciest.
            </div>
        @endforelse
    </div>

</div>

<!-- Modálne okno pre vrátenie plánu s odôvodnením -->
<div id="returnModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-black text-slate-900 text-sm uppercase tracking-wider flex items-center space-x-2">
                <span>↩️ Vrátiť Plán na Doplnenie</span>
            </h3>
            <button type="button" onclick="closeReturnModal()" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
        </div>

        <p class="text-xs text-slate-600">
            Zadajte koordinátorovi <strong id="modalKapzName" class="text-slate-900"></strong> konkrétne pripomienky a pokyny na úpravu plánu pracovných ciest:
        </p>

        <form action="{{ route('travel.return') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="plan_id" id="modalPlanId" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Dôvod vrátenia / Pokyny na úpravu *</label>
                <textarea name="admin_notes" rows="4" required class="w-full p-2.5 border border-slate-300 rounded-xl text-xs font-medium focus:ring-2 focus:ring-rose-500" placeholder="napr. Prosím upravte plán v 3. týždni, prekrýva sa so školením..."></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold">
                    Zrušiť
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md">
                    Potvrdiť Vrátenie Plánu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReturnModal(planId, kapzName) {
        document.getElementById('modalPlanId').value = planId;
        document.getElementById('modalKapzName').innerText = kapzName;
        document.getElementById('returnModal').classList.remove('hidden');
    }

    function closeReturnModal() {
        document.getElementById('returnModal').classList.add('hidden');
    }
</script>
@endsection
