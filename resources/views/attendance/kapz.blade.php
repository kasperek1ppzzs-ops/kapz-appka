@extends('layouts.app')

@section('title', 'Evidencia Dochádzky KAPZ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-slate-900">Evidencia Dochádzky KAPZ (EVIDENCIA_KAPZ)</h1>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold uppercase">
                    {{ $kapz->full_name }} ({{ $kapz->personal_number }})
                </span>
                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">
                    📍 {{ $kapz->scope }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Interaktívna evidencia dní, odpracovaných hodín (norma 7,5h/deň) a pracovísk za {{ $period->formatted_name }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Period Selector -->
            <form action="{{ route('attendance.kapz') }}" method="GET" class="inline">
                <input type="hidden" name="kapz_id" value="{{ $kapz->id }}">
                <select name="period_id" onchange="this.form.submit()" class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 shadow-sm">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $p->id == $period->id ? 'selected' : '' }}>
                            {{ $p->formatted_name }} {{ $p->is_closed ? '🔒' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Download PDF -->
            <a href="{{ route('attendance.kapz.pdf', ['kapz_id' => $kapz->id, 'period_id' => $period->id]) }}"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5">
                <span>📄</span>
                <span>Stiahnuť Oficiálne PDF</span>
            </a>
        </div>
    </div>

    <!-- Monthly Summary Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-900 text-white p-4 rounded-2xl shadow-md">
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">Celkom Odpracované</div>
            <div class="text-xl font-black text-blue-400 mt-1">{{ number_format($summary['total_hours'], 1) }} h</div>
        </div>
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">Dni Práce</div>
            <div class="text-xl font-black text-white mt-1">{{ $summary['work_days'] }} dní</div>
        </div>
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">Dovolenka</div>
            <div class="text-xl font-black text-emerald-400 mt-1">{{ $summary['holiday_days'] }} dní</div>
        </div>
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">PN / OČR / Prekážky</div>
            <div class="text-xl font-black text-rose-400 mt-1">{{ $summary['pn_days'] + $summary['ocr_days'] + $summary['nv_days'] + $summary['md_rd_days'] + $summary['kz_days'] + $summary['other_days'] + ($summary['doctor_days'] ?? 0) + ($summary['doctor_family_days'] ?? 0) }} dní</div>
        </div>
    </div>

    <!-- Attendance Entry Grid Form -->
    <!-- Kontrola fondu pracovného času (Excel: EVIDENCIA_KAPZ!R39 vs. Kalendár) -->
    <div class="mb-4 p-3 rounded-xl border text-xs font-semibold {{ $summary['fund_ok'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
        Fond pracovného času: {{ number_format($summary['fund_hours'], 2, ',', '') }} h ({{ $summary['total_working_days'] }} prac. dní)
        · odpracované {{ number_format($summary['total_work_hours'], 2, ',', '') }} h
        + neodpracované {{ number_format($summary['total_absence_hours'], 2, ',', '') }} h
        @if($summary['fund_ok'])
            – ✅ fond sedí
        @else
            – ⚠️ rozdiel {{ number_format($summary['fund_difference'], 2, ',', '') }} h (doplňte chýbajúce dni)
        @endif
    </div>

    <form action="{{ route('attendance.kapz.save') }}" method="POST">
        @csrf
        <input type="hidden" name="kapz_id" value="{{ $kapz->id }}">
        <input type="hidden" name="period_id" value="{{ $period->id }}">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Rozpis dní za {{ $period->formatted_name }} (Úväzok: {{ $kapz->employment_ratio * 100 }}% | Norma: 7,5h / deň)</span>
                
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="quickFillKapzDays()"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition border border-slate-200 flex items-center space-x-1">
                        <span>⚡</span>
                        <span>Predvyplniť mesiac (7,5h / Pracovisko)</span>
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                        💾 Uložiť Zmeny Dochádzky
                    </button>
                </div>
            </div>

            @php
                $slovakDays = [
                    1 => 'Pondelok',
                    2 => 'Utorok',
                    3 => 'Streda',
                    4 => 'Štvrtok',
                    5 => 'Piatok',
                    6 => 'Sobota',
                    7 => 'Nedeľa',
                ];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                            <th class="p-3 w-40">Dátum a Deň</th>
                            <th class="p-3 w-56">Pracovisko</th>
                            <th class="p-3 w-64">Stav Dňa</th>
                            <th class="p-3 w-36">Odpracované (h)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($summary['entries'] as $entry)
                            @php
                                $dayNum = date('N', strtotime($entry->date));
                                $slovakDayName = $slovakDays[$dayNum] ?? '';
                                $isWeekend = in_array($dayNum, [6, 7]);
                                $isWork = \App\Enums\AttendanceStatus::fromCode($entry->status)->hasWorkplace();
                            @endphp
                            <tr class="{{ $isWeekend || $entry->status == 'weekend' ? 'bg-amber-100/90 text-amber-950 font-bold border-l-4 border-amber-500' : '' }} hover:bg-blue-50/50 transition">
                                <td class="p-3 font-bold {{ $isWeekend || $entry->status == 'weekend' ? 'text-amber-900' : 'text-slate-900' }}">
                                    {{ date('d.m.Y', strtotime($entry->date)) }}
                                    <span class="{{ $isWeekend || $entry->status == 'weekend' ? 'text-amber-700 font-extrabold' : 'text-slate-500 font-semibold' }} block text-[11px]">
                                        {{ $slovakDayName }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <input type="text"
                                        name="entries[{{ $entry->date }}][workplace]"
                                        id="workplace_{{ $entry->date }}"
                                        value="{{ $isWork ? ($entry->workplace ?: $kapz->scope) : '' }}"
                                        placeholder="{{ $isWork ? 'Napr. ' . $kapz->scope : '' }}"
                                        class="workplace-input w-full p-2 border border-slate-300 rounded-lg text-xs {{ !$isWork ? 'bg-slate-100/70 text-slate-400 cursor-not-allowed' : 'bg-white font-medium text-slate-800' }}">
                                </td>
                                <td class="p-3">
                                    <select name="entries[{{ $entry->date }}][status]"
                                        class="status-select w-full p-2 border border-slate-300 rounded-lg text-xs font-semibold"
                                        data-date="{{ $entry->date }}"
                                        data-weekend="{{ $isWeekend ? '1' : '0' }}"
                                        onchange="handleKapzStatusChange(this)">
                                        @include('attendance._status_options', ['current' => $entry->status])
                                    </select>
                                </td>
                                <td class="p-3">
                                    <input type="number" step="0.5" min="0" max="24"
                                        name="entries[{{ $entry->date }}][hours_worked]"
                                        id="hours_{{ $entry->date }}"
                                        value="{{ $entry->hours_worked }}"
                                        class="hours-input w-full p-2 border border-slate-300 rounded-lg text-xs font-bold text-center {{ $isWeekend ? 'bg-amber-50' : 'bg-white' }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    💾 Uložiť Zmeny Dochádzky
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Helper Script for KAPZ Workplace & Auto Hours (7.5h) -->
<script>
    const attendanceStatusMeta = @json(collect(\App\Enums\AttendanceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ['worked' => $s->workedHours(), 'workplace' => $s->hasWorkplace()]]));
    const defaultWorkplaceKapz = @json($kapz->scope ?: 'Banská Bystrica');
    const stdHoursKapz = {{ ($kapz->employment_ratio ?? 1.0) * 7.50 }};

    function handleKapzStatusChange(selectEl) {
        const date = selectEl.getAttribute('data-date');
        const isWeekend = selectEl.getAttribute('data-weekend') === '1';
        const hoursInput = document.getElementById('hours_' + date);
        const workplaceInput = document.getElementById('workplace_' + date);

        const meta = attendanceStatusMeta[selectEl.value] || {worked: 0, workplace: false};
        if (meta.workplace) {
            hoursInput.value = (selectEl.value === 'work' ? stdHoursKapz : meta.worked).toFixed(2);
            workplaceInput.value = defaultWorkplaceKapz;
            workplaceInput.classList.remove('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
            workplaceInput.classList.add('bg-white', 'font-medium', 'text-slate-800');
        } else {
            hoursInput.value = '0.0';
            workplaceInput.value = '';
            workplaceInput.classList.add('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
            workplaceInput.classList.remove('bg-white', 'font-medium', 'text-slate-800');
        }
    }

    function quickFillKapzDays() {
        if (!confirm('Prajete si predvyplniť celý mesiac fondom 7,5h s pracoviskom ' + defaultWorkplaceKapz + ' pre pracovné dni a prázdnym pracoviskom cez víkendy?')) {
            return;
        }

        const selects = document.querySelectorAll('.status-select');
        selects.forEach(selectEl => {
            const isWeekend = selectEl.getAttribute('data-weekend') === '1';
            const date = selectEl.getAttribute('data-date');
            const hoursInput = document.getElementById('hours_' + date);
            const workplaceInput = document.getElementById('workplace_' + date);

            if (isWeekend) {
                selectEl.value = 'weekend';
                hoursInput.value = '0.0';
                workplaceInput.value = '';
                workplaceInput.classList.add('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
                workplaceInput.classList.remove('bg-white', 'font-medium', 'text-slate-800');
            } else {
                selectEl.value = 'work';
                hoursInput.value = stdHoursKapz.toFixed(1);
                workplaceInput.value = defaultWorkplaceKapz;
                workplaceInput.classList.remove('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
                workplaceInput.classList.add('bg-white', 'font-medium', 'text-slate-800');
            }
        });
    }
</script>
@endsection
