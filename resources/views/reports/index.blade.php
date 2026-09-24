@extends('layouts.app')

@section('title', 'Manažérske & Operatívne Reporty')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Manažérske & Operatívne Reporty</h1>
            <p class="text-xs text-slate-500 mt-1">Súhrnné štatistiky dochádzky, čerpania cestovných limitov a opodstatnenosti výdavkov</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('reports.export_csv', ['period_id' => $period->id]) }}"
                class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-sm transition">
                📊 Exportovať Limity do CSV / Excel
            </a>
        </div>
    </div>

    <!-- Aggregate Attendance Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase">KAPZ Hodiny Celkom</div>
            <div class="text-2xl font-black text-blue-600 mt-1">{{ number_format($attendanceSummary['kapz_total_hours'], 1) }} h</div>
            <div class="text-[11px] text-slate-500 mt-1">{{ $attendanceSummary['kapz_work_days'] }} odpracovaných dní</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase">APZ Hodiny Celkom</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($attendanceSummary['apz_total_hours'], 1) }} h</div>
            <div class="text-[11px] text-slate-500 mt-1">{{ $attendanceSummary['apz_work_days'] }} odpracovaných dní</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase">Absencie / PN KAPZ</div>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ $attendanceSummary['kapz_holiday_days'] + $attendanceSummary['kapz_pn_days'] }} dní</div>
            <div class="text-[11px] text-slate-500 mt-1">Dovolenky: {{ $attendanceSummary['kapz_holiday_days'] }}d, PN: {{ $attendanceSummary['kapz_pn_days'] }}d</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold text-slate-400 uppercase">Absencie / PN APZ</div>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ $attendanceSummary['apz_holiday_days'] + $attendanceSummary['apz_pn_days'] }} dní</div>
            <div class="text-[11px] text-slate-500 mt-1">Dovolenky: {{ $attendanceSummary['apz_holiday_days'] }}d, PN: {{ $attendanceSummary['apz_pn_days'] }}d</div>
        </div>
    </div>

    <!-- Travel Limits & Km Consumption Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-slate-50 font-bold text-xs text-slate-700 uppercase">
            Čerpanie cestovných limitov a kilometrov po jednotlivých KAPZ za {{ $period->formatted_name }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3">KAPZ Meno</th>
                        <th class="p-3">Pôsobnosť</th>
                        <th class="p-3">Stav Plánu</th>
                        <th class="p-3 text-right">Plánované KM</th>
                        <th class="p-3 text-right">Skutočné Odjazdené KM</th>
                        <th class="p-3 text-right">Celkové Náklady (€)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($travelKmSummary as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-bold text-slate-900">{{ $row['kapz']->full_name }} ({{ $row['kapz']->personal_number }})</td>
                            <td class="p-3 font-medium">{{ $row['kapz']->scope }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">{{ $row['status'] }}</span></td>
                            <td class="p-3 text-right font-bold text-blue-600">{{ number_format($row['planned_km'], 1) }} km</td>
                            <td class="p-3 text-right font-bold text-emerald-600">{{ number_format($row['actual_km'], 1) }} km</td>
                            <td class="p-3 text-right font-bold text-slate-900">{{ number_format($row['total_cost'], 2) }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
