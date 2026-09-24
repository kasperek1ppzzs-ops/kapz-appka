@extends('layouts.app')

@section('title', 'Manažérsky Dashboard KAPZ')

@section('content')
<div class="space-y-6">
    <!-- Header with Welcome & Period Selector -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900">Manažérsky Prehľad Koordinátora</h1>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold uppercase tracking-wider">
                    {{ $kapz->scope }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Koordinátor: <strong>{{ $kapz->full_name }}</strong> ({{ $kapz->personal_number }}) | Sledované obdobie: <strong>{{ $period->formatted_name }}</strong></p>
        </div>

        <form action="{{ route('dashboard') }}" method="GET" class="flex items-center space-x-3">
            <label class="text-xs font-bold text-slate-500 uppercase">Obdobie:</label>
            <select name="month" onchange="this.form.submit()" class="p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-blue-900 shadow-sm focus:ring-2 focus:ring-blue-500">
                @foreach($periods as $p)
                    <option value="{{ $p->month }}" {{ $p->id == $period->id ? 'selected' : '' }}>
                        {{ $p->formatted_name }} {{ $p->is_closed ? '🔒' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Executive KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KM Card -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/15">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-200">Plánovaný nájazd</span>
                <span class="text-lg">🚗</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ number_format($plannedKm, 1) }} km</div>
            <div class="text-xs text-blue-100 mt-2 flex items-center justify-between border-t border-white/15 pt-2">
                <span>Skutočnosť: <strong>{{ number_format($actualKm, 1) }} km</strong></span>
                <span>Náklady: <strong>{{ number_format($totalCost, 2) }} €</strong></span>
            </div>
        </div>

        <!-- KAPZ Hours Card -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-5 text-white shadow-lg shadow-slate-900/15">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Odpracované KAPZ</span>
                <span class="text-lg">⏱️</span>
            </div>
            <div class="text-3xl font-black mt-2 text-emerald-400">{{ number_format($kapzSummary['total_hours'], 1) }} h</div>
            <div class="text-xs text-slate-300 mt-2 flex items-center justify-between border-t border-slate-700 pt-2">
                <span>Dni práce: <strong>{{ $kapzSummary['work_days'] }}d</strong></span>
                <span>Dovolenka: <strong>{{ $kapzSummary['holiday_days'] }}d</strong></span>
            </div>
        </div>

        <!-- APZ Group Hours Card -->
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/15">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-200">Skupina APZ (Celkom)</span>
                <span class="text-lg">🤝</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ number_format($groupWorkHours, 1) }} h</div>
            <div class="text-xs text-emerald-100 mt-2 flex items-center justify-between border-t border-white/15 pt-2">
                <span>Asistenti: <strong>{{ count($assignedApzs) }} osôb</strong></span>
                <span>Dni v teréne: <strong>{{ $groupWorkDays }}d</strong></span>
            </div>
        </div>

        <!-- Plan Status Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Stav Plánu Ciest</span>
                <span class="text-lg">📋</span>
            </div>
            <div class="mt-2">
                @if($travelPlan)
                    <span class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider inline-block
                        {{ $travelPlan->status == 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : '' }}
                        {{ $travelPlan->status == 'SUBMITTED' ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $travelPlan->status == 'DRAFT' ? 'bg-slate-100 text-slate-800' : '' }}
                        {{ $travelPlan->status == 'RETURNED' ? 'bg-rose-100 text-rose-800' : '' }}">
                        {{ $travelPlan->status }} (Verzia {{ $travelPlan->version }})
                    </span>
                @else
                    <span class="text-rose-500 font-bold text-sm">Nevytvorený</span>
                @endif
            </div>
            <div class="text-[11px] text-slate-500 mt-2 border-t border-slate-100 pt-2 flex items-center justify-between">
                <span>Počet výjazdov: <strong>{{ $travelPlan ? $travelPlan->items->count() : 0 }}</strong></span>
                <a href="{{ route('travel.index') }}" class="text-blue-600 hover:text-blue-800 font-bold">Otvoriť &rarr;</a>
            </div>
        </div>
    </div>

    <!-- FIELD VISITS & TRAVEL PURPOSES ANALYTICS (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- LEFT COLUMN: 📍 Frekvencia návštevností lokalít (Rebríček + Horizontálny graf) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span>📍</span>
                        <span>Frekvencia Návštevností Lokalít</span>
                    </h3>
                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 font-bold rounded-lg text-xs">
                        {{ count($locationStats) }} navštívených obcí
                    </span>
                </div>
                <p class="text-xs text-slate-500 mb-4">Počet realizovaných výjazdov a kontrol v jednotlivých komunitných lokalitách za {{ $period->formatted_name }}</p>

                <!-- Horizontal Bar Chart -->
                @if(count($locationStats) > 0)
                    <div class="h-64">
                        <canvas id="locationVisitsChart"></canvas>
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400 text-xs bg-slate-50 rounded-xl">
                        Žiadne plánované výjazdy v zvolenom období.
                    </div>
                @endif
            </div>
        </div>

        <!-- RIGHT COLUMN: 🎯 Účely pracovných ciest a ich počet -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                        <span>🎯</span>
                        <span>Účely Pracovných Ciest a Ich Počet</span>
                    </h3>
                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-lg text-xs">
                        {{ $travelPlan ? $travelPlan->items->count() : 0 }} výjazdov celkom
                    </span>
                </div>
                <p class="text-xs text-slate-500 mb-4">Rozdelenie činností koordinátora v teréne za obdobie {{ $period->formatted_name }}</p>

                @if(count($purposeStats) > 0)
                    <div class="space-y-3">
                        @foreach($purposeStats as $pName => $pData)
                            <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50 hover:bg-emerald-50/50 transition">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-slate-900 text-xs">{{ $pName }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                                        {{ $pData['trips_count'] }}x výjazd
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500">
                                    <span>Nájazd km pre tento účel:</span>
                                    <strong class="text-slate-800 font-mono">{{ number_format($pData['total_km'], 1) }} km</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400 text-xs bg-slate-50 rounded-xl">
                        Žiadne zaevidované účely ciest.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ATTENDANCE BREAKDOWN & TEAM OVERVIEW (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- APZ Group Attendance Doughnut (1 col) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Štruktúra Dochádzky Skupiny APZ</h3>
                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-lg text-xs">{{ count($assignedApzs) }} APZ</span>
                </div>
                <p class="text-xs text-slate-500 mb-4">Pomer odpracovaných dní voči absenciám</p>

                <div class="h-44 relative flex items-center justify-center">
                    <canvas id="apzAttendanceChart"></canvas>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs border-t border-slate-100 pt-3">
                <div class="bg-emerald-50/50 p-2 rounded-xl">
                    <div class="text-[10px] text-slate-400 font-bold uppercase">Práca</div>
                    <div class="font-black text-emerald-700 text-sm mt-0.5">{{ $groupWorkDays }} dní</div>
                </div>
                <div class="bg-blue-50/50 p-2 rounded-xl">
                    <div class="text-[10px] text-slate-400 font-bold uppercase">Dovolenka</div>
                    <div class="font-black text-blue-700 text-sm mt-0.5">{{ $groupHolidays }} dní</div>
                </div>
                <div class="bg-rose-50/50 p-2 rounded-xl">
                    <div class="text-[10px] text-slate-400 font-bold uppercase">PN/OČR</div>
                    <div class="font-black text-rose-700 text-sm mt-0.5">{{ $groupPn + $groupOcr + $groupDoctor }} dní</div>
                </div>
            </div>
        </div>

        <!-- Assigned APZ Team Table (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tím Asistentov Podpory Zdravia (APZ)</h3>
                        <p class="text-xs text-slate-500">Prehľad priradených asistentov v obvode {{ $kapz->scope }} za {{ $period->formatted_name }}</p>
                    </div>
                    <a href="{{ route('attendance.apz') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        Zadať Dochádzku APZ &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                                <th class="p-3">Meno a Priezvisko</th>
                                <th class="p-3">Komunita / Lokalita</th>
                                <th class="p-3 text-center">Odpracované</th>
                                <th class="p-3 text-center">Dovolenka</th>
                                <th class="p-3 text-center">PN / OČR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($apzSummaries as $item)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ $item['profile']->full_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $item['profile']->personal_number }}</div>
                                    </td>
                                    <td class="p-3 font-medium text-slate-700">
                                        📍 {{ $item['profile']->community_scope }}
                                    </td>
                                    <td class="p-3 text-center font-bold text-emerald-600">
                                        {{ $item['summary']['work_days'] }} dní ({{ $item['summary']['total_hours'] }}h)
                                    </td>
                                    <td class="p-3 text-center font-bold text-blue-600">
                                        {{ $item['summary']['holiday_days'] }} dní
                                    </td>
                                    <td class="p-3 text-center font-bold text-rose-600">
                                        {{ $item['summary']['pn_days'] + $item['summary']['ocr_days'] }} dní
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-slate-400 text-xs">
                                        Žiadni pridelení asistenti APZ pre zvolené obdobie.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Location Visits Horizontal Chart
        @if(count($locationStats) > 0)
            const locData = @json(array_values($locationStats));
            const locLabels = locData.map(d => d.location);
            const locCounts = locData.map(d => d.visits_count);

            const ctxLoc = document.getElementById('locationVisitsChart').getContext('2d');
            new Chart(ctxLoc, {
                type: 'bar',
                data: {
                    labels: locLabels,
                    datasets: [{
                        label: 'Počet návštev / kontrol',
                        data: locCounts,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderRadius: 6,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { weight: 'bold' } },
                            title: { display: true, text: 'Počet výjazdov / kontrol', font: { size: 10, weight: 'bold' } },
                            grid: { color: '#f1f5f9' }
                        },
                        y: {
                            ticks: { font: { weight: 'bold', size: 11 } },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const item = locData[context.dataIndex];
                                    return ` ${item.visits_count}x výjazd (${item.total_km} km)`;
                                }
                            }
                        }
                    }
                }
            });
        @endif

        // 2. APZ Group Attendance Doughnut Chart
        const ctxApz = document.getElementById('apzAttendanceChart').getContext('2d');
        new Chart(ctxApz, {
            type: 'doughnut',
            data: {
                labels: ['Práca v teréne', 'Dovolenka', 'PN / OČR / Lekár'],
                datasets: [{
                    data: [{{ $groupWorkDays }}, {{ $groupHolidays }}, {{ $groupPn + $groupOcr + $groupDoctor }}],
                    backgroundColor: ['#10b981', '#3b82f6', '#f43f5e'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } }
                }
            }
        });
    });
</script>
@endsection
