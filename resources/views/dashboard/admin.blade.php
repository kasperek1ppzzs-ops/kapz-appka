@extends('layouts.app')

@section('title', 'Manažérska Centrálna Matica - Administrácia')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900">Manažérska Centrálna Matica a Dashboard</h1>
                <span class="px-3 py-1 bg-amber-100 text-amber-900 rounded-full text-xs font-bold uppercase tracking-wider">
                    Administrátor Ústredia
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Celosystémový prehľad koordinátorov KAPZ, terénnych asistentov APZ a vyúčtovaní ciest za obdobie <strong>{{ $period->formatted_name }}</strong></p>
        </div>

        <!-- Period Selector and Period Lock Form -->
        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center space-x-2">
                <select name="month" onchange="this.form.submit()" class="p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 shadow-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($periods as $p)
                        <option value="{{ $p->month }}" {{ $p->id == $period->id ? 'selected' : '' }}>
                            {{ $p->formatted_name }} {{ $p->is_closed ? '🔒 (Uzavreté)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <form action="{{ route('admin.period.toggle_lock', $period->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5
                    {{ $period->is_closed ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white' }}">
                    <span>{{ $period->is_closed ? '🔓 Odomknúť Mesiac' : '🔒 Uzamknúť Mesiac' }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Executive KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- System Hours -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/15">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-200">Odpracované v systéme</span>
                <span class="text-lg">⏱️</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ number_format($totalSystemHours, 1) }} h</div>
            <div class="text-xs text-blue-100 mt-2 flex items-center justify-between border-t border-white/15 pt-2">
                <span>KAPZ: <strong>{{ number_format($totalKapzHours, 0) }}h</strong></span>
                <span>APZ: <strong>{{ number_format($totalApzHours, 0) }}h</strong></span>
            </div>
        </div>

        <!-- System KM -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-5 text-white shadow-lg shadow-slate-900/15">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Nájazd Kilometrov</span>
                <span class="text-lg">🚗</span>
            </div>
            <div class="text-3xl font-black mt-2 text-emerald-400">{{ number_format($totalPlannedKm, 1) }} km</div>
            <div class="text-xs text-slate-300 mt-2 flex items-center justify-between border-t border-slate-700 pt-2">
                <span>Skutočnosť: <strong>{{ number_format($totalActualKm, 1) }} km</strong></span>
                <span>Náklady: <strong>{{ number_format($totalTravelCosts, 2) }} €</strong></span>
            </div>
        </div>

        <!-- Field Presence & APZ Team -->
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/15">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-200">Terénne Pokrytie</span>
                <span class="text-lg">🗺️</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $totalApzCount }} APZ / {{ $totalKapzCount }} KAPZ</div>
            <div class="text-xs text-emerald-100 mt-2 flex items-center justify-between border-t border-white/15 pt-2">
                <span>Dni v teréne: <strong>{{ $totalWorkDays }}d</strong></span>
                <span>Absencie: <strong>{{ $totalHolidays + $totalPn + $totalOcr }}d</strong></span>
            </div>
        </div>

        <!-- Approvals Status -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Čaká na Schválenie</span>
                <span class="text-lg">📋</span>
            </div>
            <div class="text-3xl font-black mt-2 text-amber-600">{{ count($pendingPlans) }} plánov</div>
            <div class="text-[11px] text-slate-500 mt-2 border-t border-slate-100 pt-2 flex items-center justify-between">
                <span>Schválených: <strong>{{ $approvedPlansCount }}</strong></span>
                <a href="#pending-plans-section" class="text-blue-600 hover:text-blue-800 font-bold">Zobraziť &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Regional KM Distribution & System Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Regional KM Chart -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-1">Nájazd KM podľa Regiónov</h3>
                <p class="text-xs text-slate-500 mb-4">Rozdelenie plánovaných kilometrov za {{ $period->formatted_name }}</p>
                <div class="h-48 relative flex items-center justify-center">
                    <canvas id="regionKmChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Operational Status & Quick Actions -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Rýchly Prehľad Systému a Správa</h3>
                    <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold">{{ $period->formatted_name }}</span>
                </div>
                <p class="text-xs text-slate-500 mb-4">Priamy prístup k centrálnym reportom, správe personálu a auditným záznamom</p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <a href="{{ route('reports.index') }}" class="p-3 bg-slate-50 hover:bg-indigo-50 hover:text-indigo-700 rounded-xl text-xs font-bold text-center transition border border-slate-200 flex flex-col items-center justify-center space-y-1">
                        <span class="text-lg">📊</span>
                        <span>Manažérske Reporty</span>
                    </a>
                    <a href="{{ route('admin.assignments.index') }}" class="p-3 bg-slate-50 hover:bg-amber-50 hover:text-amber-700 rounded-xl text-xs font-bold text-center transition border border-slate-200 flex flex-col items-center justify-center space-y-1">
                        <span class="text-lg">⚙️</span>
                        <span>Priradenia APZ</span>
                    </a>
                    <a href="{{ route('admin.audit_logs') }}" class="p-3 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-bold text-center transition border border-slate-200 flex flex-col items-center justify-center space-y-1">
                        <span class="text-lg">🛡️</span>
                        <span>Auditné Logy</span>
                    </a>
                    <a href="{{ route('contacts.index') }}" class="p-3 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 rounded-xl text-xs font-bold text-center transition border border-slate-200 flex flex-col items-center justify-center space-y-1">
                        <span class="text-lg">👥</span>
                        <span>Zoznam Kontaktov</span>
                    </a>
                </div>
            </div>

            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <span>Stav mesiaca: <strong class="{{ $period->is_closed ? 'text-rose-600' : 'text-emerald-600' }}">{{ $period->is_closed ? '🔒 Uzamknuté (Zákaz úprav)' : '🔓 Odomknuté (Prebieha zber)' }}</strong></span>
                <span class="text-slate-400">Posledná aktualizácia: {{ date('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Pending Plans Alert / Approval Box -->
    @if(count($pendingPlans) > 0)
        <div id="pending-plans-section" class="bg-amber-50 rounded-2xl border border-amber-200 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-black text-amber-950 uppercase tracking-wider flex items-center space-x-2">
                        <span>⏳</span>
                        <span>Plány pracovných ciest čakajúce na schválenie ({{ count($pendingPlans) }})</span>
                    </h2>
                    <p class="text-xs text-amber-800 mt-1">Skontrolujte plánované cesty koordinátorov a schváľte alebo vráťte na dopracovanie.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($pendingPlans as $plan)
                    <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-sm flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-sm">{{ $plan->kapz->full_name }}</span>
                                <span class="text-xs text-slate-400 font-mono">{{ $plan->kapz->scope }}</span>
                            </div>
                            <div class="text-xs text-slate-600 mt-1">
                                Plánovaný nájazd: <strong>{{ number_format($plan->items->sum('estimated_km'), 1) }} km</strong> ({{ $plan->items->count() }} ciest)
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 pt-2 border-t border-slate-100">
                            <a href="{{ route('travel.pdf', $plan->id) }}" target="_blank"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">
                                📄 Zobraziť PDF
                            </a>
                            <form action="{{ route('travel.approve') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                    ✓ Schváliť Plán
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Central Control Matrix Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Centrálna Matica Kontroly Výkazov a Dochádzky</h3>
                <p class="text-xs text-slate-500">Stav plnenia povinností za obdobie {{ $period->formatted_name }} pre všetkých koordinátorov</p>
            </div>
            <a href="{{ route('reports.index') }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                📊 Podrobné Reporty &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3">KAPZ Koordinátor</th>
                        <th class="p-3">Obvod Pôsobnosti</th>
                        <th class="p-3 text-center">Počet APZ</th>
                        <th class="p-3 text-center">Stav Dochádzky</th>
                        <th class="p-3 text-center">Plán Ciest</th>
                        <th class="p-3 text-right">Plánované KM</th>
                        <th class="p-3 text-right">Rýchle Akcie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($kapzStatusMatrix as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-slate-900">
                                {{ $row['kapz']->full_name }}
                                <span class="block text-[10px] text-slate-400 font-mono">{{ $row['kapz']->personal_number }}</span>
                            </td>
                            <td class="p-3 font-medium text-slate-700">
                                📍 {{ $row['kapz']->scope }}
                            </td>
                            <td class="p-3 text-center font-bold text-blue-600">
                                {{ $row['assigned_apz_count'] }} APZ
                            </td>
                            <td class="p-3 text-center">
                                @if($row['attendance_complete'])
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">✓ Vyplnená</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">⚠️ Rozpracovaná</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase
                                    {{ $row['plan_status'] == 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $row['plan_status'] == 'SUBMITTED' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $row['plan_status'] == 'DRAFT' ? 'bg-slate-100 text-slate-800' : '' }}
                                    {{ $row['plan_status'] == 'NEVYTVORENÝ' ? 'bg-rose-100 text-rose-800' : '' }}">
                                    {{ $row['plan_status'] }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-bold text-slate-900">
                                {{ number_format($row['planned_km'], 1) }} km
                            </td>
                            <td class="p-3 text-right space-x-1">
                                <a href="{{ route('attendance.kapz', ['kapz_id' => $row['kapz']->id, 'period_id' => $period->id]) }}"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition text-[11px]">
                                    Dochádzka
                                </a>
                                <a href="{{ route('attendance.apz', ['kapz_id' => $row['kapz']->id, 'period_id' => $period->id]) }}"
                                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg transition text-[11px]">
                                    APZ Tím
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Regional KM Distribution Doughnut Chart
        const regionStats = @json($regionKmStats);
        const regionLabels = Object.keys(regionStats);
        const regionData = Object.values(regionStats);

        const ctxRegion = document.getElementById('regionKmChart').getContext('2d');
        new Chart(ctxRegion, {
            type: 'doughnut',
            data: {
                labels: regionLabels.length > 0 ? regionLabels : ['Banská Bystrica', 'Poprad', 'Michalovce', 'Košice'],
                datasets: [{
                    data: regionData.length > 0 ? regionData : [42.5, 38.0, 55.0, 24.0],
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } }
                }
            }
        });
    });
</script>
@endsection
