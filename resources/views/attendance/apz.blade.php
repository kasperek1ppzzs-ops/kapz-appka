@extends('layouts.app')

@section('title', 'Evidencia Dochádzky APZ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-slate-900">Evidencia Dochádzky APZ (EVIDENCIA_APZ)</h1>
                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold uppercase">
                    {{ $apz->full_name }} ({{ $apz->personal_number }})
                </span>
                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">
                    📍 {{ $apz->community_scope }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Priradený KAPZ: <strong>{{ $kapz->full_name }}</strong> ({{ $kapz->personal_number }}) | Sledované obdobie: <strong>{{ $period->formatted_name }}</strong> (Norma 7,5h/deň)</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Period Selector -->
            <form action="{{ route('attendance.apz') }}" method="GET" class="inline">
                <input type="hidden" name="apz_id" value="{{ $apz->id }}">
                <input type="hidden" name="kapz_id" value="{{ $kapz->id }}">
                <select name="period_id" onchange="this.form.submit()" class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 shadow-sm">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $p->id == $period->id ? 'selected' : '' }}>
                            {{ $p->formatted_name }} {{ $p->is_closed ? '🔒' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Global Action: Bulk Pre-fill ALL APZs at once -->
            @if(isset($assignedApzs) && $assignedApzs->count() > 0)
                <form action="{{ route('attendance.apz.quick_fill_all') }}" method="POST" class="inline" onsubmit="return confirm('Prajete si hromadne predvyplniť fond 7,5h a pracoviská pre VŠETKÝCH {{ $assignedApzs->count() }} priradených asistentov APZ za {{ $period->formatted_name }}?');">
                    @csrf
                    <input type="hidden" name="kapz_id" value="{{ $kapz->id }}">
                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5"
                        title="Hromadne predvyplní štandardný fond 7,5h a priradené pracoviská pre všetkých asistentov v tíme">
                        <span>⚡</span>
                        <span>Predvyplniť VŠETKÝCH APZ (7,5h)</span>
                    </button>
                </form>
            @endif

            <!-- Single APZ PDF -->
            <a href="{{ route('attendance.apz.pdf', ['apz_id' => $apz->id, 'kapz_id' => $kapz->id, 'period_id' => $period->id]) }}"
                class="px-4 py-2 bg-emerald-800 hover:bg-emerald-900 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5">
                <span>📄</span>
                <span>PDF tohto APZ</span>
            </a>

            <!-- Batch All APZ PDF -->
            @if(isset($assignedApzs) && $assignedApzs->count() > 1)
                <a href="{{ route('attendance.apz.all_pdf', ['kapz_id' => $kapz->id, 'period_id' => $period->id]) }}"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5"
                    title="Vygenerovať a stiahnuť spoločný viacstránkový balík dochádzok pre všetkých priradených asistentov">
                    <span>📑</span>
                    <span>Stiahnuť VŠETKY PDF ({{ $assignedApzs->count() }} APZ)</span>
                </a>
            @endif
        </div>
    </div>

    <!-- APZ Team Selector Grid (Optimized for 15+ Assistants) -->
    @if(isset($assignedApzs) && $assignedApzs->count() > 0)
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Tím priradených asistentov ({{ $assignedApzs->count() }} APZ):</span>
                    <span class="text-xs text-slate-400">Kliknutím vyberte asistenta pre úpravu dochádzky</span>
                </div>

                <!-- Quick Filter for Many APZs -->
                @if($assignedApzs->count() > 5)
                    <div class="relative w-full sm:w-64">
                        <input type="text" id="apzFilterInput" placeholder="Filtrovať asistenta..."
                            class="w-full pl-7 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:ring-1 focus:ring-emerald-500">
                        <span class="absolute left-2.5 top-1.5 text-slate-400 text-xs">🔍</span>
                    </div>
                @endif
            </div>

            <!-- APZ Selection Cards Grid -->
            <div id="apzGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 max-h-60 overflow-y-auto p-1">
                @foreach($assignedApzs as $itemApz)
                    @php
                        $isActive = ($itemApz->id == $apz->id);
                        $stats = $apzStats[$itemApz->id] ?? null;
                    @endphp
                    <a href="{{ route('attendance.apz', ['apz_id' => $itemApz->id, 'kapz_id' => $kapz->id, 'period_id' => $period->id]) }}"
                        data-name="{{ strtolower($itemApz->full_name . ' ' . $itemApz->community_scope) }}"
                        class="p-3 rounded-xl text-xs font-bold transition border flex flex-col justify-between space-y-1.5
                        {{ $isActive
                            ? 'bg-emerald-600 text-white border-emerald-700 shadow-md shadow-emerald-500/20 ring-2 ring-emerald-500/50'
                            : 'bg-slate-50 hover:bg-emerald-50/60 text-slate-700 border-slate-200 hover:border-emerald-300' }}">
                        <div class="flex items-center justify-between">
                            <span class="truncate {{ $isActive ? 'text-white font-extrabold' : 'text-slate-900' }}">
                                👤 {{ $itemApz->full_name }}
                            </span>
                            @if($isActive)
                                <span class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded font-black">Aktívny</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-[11px] {{ $isActive ? 'text-emerald-100' : 'text-slate-500' }}">
                            <span>📍 {{ $itemApz->community_scope }}</span>
                            @if($stats)
                                <span class="font-mono font-black {{ $isActive ? 'text-white' : ($stats['work_days'] > 0 ? 'text-emerald-700' : 'text-amber-600') }}">
                                    {{ $stats['work_days'] }}d / {{ number_format($stats['total_hours'], 1) }}h
                                </span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Monthly Summary Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-900 text-white p-4 rounded-2xl shadow-md">
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">Celkom Odpracované</div>
            <div class="text-xl font-black text-emerald-400 mt-1">{{ number_format($summary['total_hours'], 1) }} h</div>
        </div>
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">Dni Práce</div>
            <div class="text-xl font-black text-white mt-1">{{ $summary['work_days'] }} dní</div>
        </div>
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">Dovolenka</div>
            <div class="text-xl font-black text-blue-400 mt-1">{{ $summary['holiday_days'] }} dní</div>
        </div>
        <div class="p-3 bg-slate-800/80 rounded-xl">
            <div class="text-xs text-slate-400 font-bold uppercase">PN / OČR / Prekážky</div>
            <div class="text-xl font-black text-rose-400 mt-1">{{ $summary['pn_days'] + $summary['ocr_days'] + ($summary['doctor_days'] ?? 0) + ($summary['doctor_family_days'] ?? 0) }} dní</div>
        </div>
    </div>

    <!-- Attendance Grid Form (Identical to KAPZ Form) -->
    <form action="{{ route('attendance.apz.save') }}" method="POST">
        @csrf
        <input type="hidden" name="apz_id" value="{{ $apz->id }}">
        <input type="hidden" name="kapz_id" value="{{ $kapz->id }}">
        <input type="hidden" name="period_id" value="{{ $period->id }}">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50">
                <div>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Rozpis dní za {{ $period->formatted_name }} pre {{ $apz->full_name }}
                    </span>
                    <span class="text-xs text-slate-500 block sm:inline sm:ml-2">
                        (Úväzok: {{ $apz->employment_ratio * 100 }}% | Norma: 7,5h / deň)
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" onclick="quickFillWorkDays()"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition border border-slate-200 flex items-center space-x-1">
                        <span>⚡</span>
                        <span>Predvyplniť tohto APZ (7,5h / Pracovisko)</span>
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                        💾 Uložiť Dochádzku APZ
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
                                $isWork = ($entry->status == 'work');
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
                                        value="{{ $isWork ? ($entry->workplace ?: $apz->community_scope) : '' }}"
                                        placeholder="{{ $isWork ? 'Napr. ' . $apz->community_scope : '' }}"
                                        class="workplace-input w-full p-2 border border-slate-300 rounded-lg text-xs {{ !$isWork ? 'bg-slate-100/70 text-slate-400 cursor-not-allowed' : 'bg-white font-medium text-slate-800' }}">
                                </td>
                                <td class="p-3">
                                    <select name="entries[{{ $entry->date }}][status]"
                                        class="status-select w-full p-2 border border-slate-300 rounded-lg text-xs font-semibold"
                                        data-date="{{ $entry->date }}"
                                        data-weekend="{{ $isWeekend ? '1' : '0' }}"
                                        onchange="handleStatusChange(this)">
                                        <option value="work" {{ $entry->status == 'work' ? 'selected' : '' }}>Práca</option>
                                        <option value="holiday" {{ $entry->status == 'holiday' ? 'selected' : '' }}>Dovolenka</option>
                                        <option value="pn" {{ $entry->status == 'pn' ? 'selected' : '' }}>PN (Prácaneschopnosť)</option>
                                        <option value="ocr" {{ $entry->status == 'ocr' ? 'selected' : '' }}>OČR (Ošetrovanie člena rodiny)</option>
                                        <option value="doctor" {{ $entry->status == 'doctor' ? 'selected' : '' }}>Lekár</option>
                                        <option value="doctor_family" {{ $entry->status == 'doctor_family' ? 'selected' : '' }}>Lekár - doprovod</option>
                                        <option value="substitute_leave" {{ $entry->status == 'substitute_leave' ? 'selected' : '' }}>Náhradné voľno</option>
                                        <option value="paid_absence" {{ $entry->status == 'paid_absence' ? 'selected' : '' }}>Prekážky v práci - platené</option>
                                        <option value="unpaid_absence" {{ $entry->status == 'unpaid_absence' ? 'selected' : '' }}>Prekážky v práci - neplatené</option>
                                        <option value="funeral" {{ $entry->status == 'funeral' ? 'selected' : '' }}>Pohreb</option>
                                        <option value="blood_donation" {{ $entry->status == 'blood_donation' ? 'selected' : '' }}>Darovanie krvi</option>
                                        <option value="public_holiday" {{ $entry->status == 'public_holiday' ? 'selected' : '' }}>Sviatok</option>
                                        <option value="no_communication" {{ $entry->status == 'no_communication' ? 'selected' : '' }}>Nekomunikuje</option>
                                        <option value="weekend" {{ $entry->status == 'weekend' ? 'selected' : '' }}>Víkend</option>
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

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">Uloženie dochádzky pre asistenta <strong>{{ $apz->full_name }}</strong></span>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    💾 Uložiť Dochádzku APZ
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Helper Script for APZ Workplace & Auto Hours (7.5h) -->
<script>
    const defaultWorkplaceApz = @json($apz->community_scope ?: $apz->scope);
    const stdHours = {{ ($apz->employment_ratio ?? 1.0) * 7.50 }};

    function handleStatusChange(selectEl) {
        const date = selectEl.getAttribute('data-date');
        const isWeekend = selectEl.getAttribute('data-weekend') === '1';
        const hoursInput = document.getElementById('hours_' + date);
        const workplaceInput = document.getElementById('workplace_' + date);

        if (selectEl.value === 'work') {
            hoursInput.value = stdHours.toFixed(1);
            workplaceInput.value = defaultWorkplaceApz;
            workplaceInput.classList.remove('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
            workplaceInput.classList.add('bg-white', 'font-medium', 'text-slate-800');
        } else {
            hoursInput.value = '0.0';
            workplaceInput.value = '';
            workplaceInput.classList.add('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
            workplaceInput.classList.remove('bg-white', 'font-medium', 'text-slate-800');
        }
    }

    function quickFillWorkDays() {
        if (!confirm('Prajete si predvyplniť celý mesiac fondom 7,5h s pracoviskom ' + defaultWorkplaceApz + ' pre pracovné dni a prázdnym pracoviskom cez víkendy?')) {
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
                hoursInput.value = stdHours.toFixed(1);
                workplaceInput.value = defaultWorkplaceApz;
                workplaceInput.classList.remove('bg-slate-100/70', 'text-slate-400', 'cursor-not-allowed');
                workplaceInput.classList.add('bg-white', 'font-medium', 'text-slate-800');
            }
        });
    }

    // Live search filter for APZ switcher grid
    const filterInput = document.getElementById('apzFilterInput');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#apzGrid > a');
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
</script>
@endsection
