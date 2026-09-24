@extends('layouts.app')

@section('title', 'Celoročný Kalendár & Pokyny Experta')

@section('content')
<div class="space-y-6">
    <!-- Header with Month / Year Navigation & Actions -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900">Celoročný Kalendár & Pokyny Experta</h1>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold uppercase tracking-wider">
                    {{ $year }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                <span>Evidencia denných úloh, metodických pokynov a termínov</span>
                @if($kapz)
                    <span class="text-slate-300">|</span>
                    <span>Koordinátor: <strong>{{ $kapz->full_name }}</strong> ({{ $kapz->scope }})</span>
                    @if($kapz->region_expert)
                        <span class="text-slate-300">|</span>
                        <span>Nadriadený Expert pre terén: <strong class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">🎯 {{ $kapz->region_expert }}</strong></span>
                    @endif
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter by KAPZ (for Admin / Expert) -->
            @if($isExpertOrAdmin && count($allKapzList) > 0)
                <form action="{{ route('calendar.index') }}" method="GET" class="inline">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <select name="kapz_id" onchange="this.form.submit()" class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 shadow-sm">
                        <option value="">-- Všetci koordinátori KAPZ --</option>
                        @foreach($allKapzList as $k)
                            <option value="{{ $k->id }}" {{ $selectedKapzId == $k->id ? 'selected' : '' }}>
                                👤 {{ $k->full_name }} ({{ $k->scope }})
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <!-- Month Navigation Buttons -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                @php
                    $prevM = $month - 1;
                    $prevY = $year;
                    if ($prevM < 1) { $prevM = 12; $prevY--; }

                    $nextM = $month + 1;
                    $nextY = $year;
                    if ($nextM > 12) { $nextM = 1; $nextY++; }
                @endphp
                <a href="{{ route('calendar.index', ['year' => $prevY, 'month' => $prevM, 'kapz_id' => $selectedKapzId]) }}"
                    class="px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-white rounded-lg transition" title="Predchádzajúci mesiac">
                    &larr;
                </a>
                
                <!-- Month Dropdown -->
                <form action="{{ route('calendar.index') }}" method="GET" class="inline mx-1">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="kapz_id" value="{{ $selectedKapzId }}">
                    <select name="month" onchange="this.form.submit()" class="bg-transparent text-xs font-black text-slate-900 border-0 focus:ring-0 cursor-pointer">
                        @foreach($slovakMonths as $num => $name)
                            <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }} {{ $year }}</option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('calendar.index', ['year' => $nextY, 'month' => $nextM, 'kapz_id' => $selectedKapzId]) }}"
                    class="px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-white rounded-lg transition" title="Nasledujúci mesiac">
                    &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Monthly KPI Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Úlohy v Mesiaci</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['total'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base">
                📅
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Čaká na splnenie</div>
                <div class="text-2xl font-black text-amber-600 mt-1">{{ $stats['pending'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base">
                ⏳
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">V riešení</div>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ $stats['in_progress'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base">
                ⚙️
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Splnené</div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['completed'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-base">
                ✅
            </div>
        </div>
    </div>

    <!-- MAIN CALENDAR GRID -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 bg-slate-900 text-white text-xs font-bold uppercase text-center border-b border-slate-800">
            <div class="py-3">Pondelok</div>
            <div class="py-3">Utorok</div>
            <div class="py-3">Streda</div>
            <div class="py-3">Štvrtok</div>
            <div class="py-3">Piatok</div>
            <div class="py-3 text-amber-400 bg-slate-950/40">Sobota</div>
            <div class="py-3 text-amber-400 bg-slate-950/40">Nedeľa</div>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100 text-xs">
            @foreach($calendarDays as $cell)
                @php
                    $isToday = ($cell['date'] === date('Y-m-d'));
                    $hasHoliday = !empty($cell['holiday']);
                @endphp
                <div class="min-h-[120px] p-2 flex flex-col justify-between transition relative group
                    {{ !$cell['is_current_month'] ? 'bg-slate-50/50 text-slate-300' : ($cell['is_weekend'] ? 'bg-amber-50/40' : 'bg-white') }}
                    {{ $isToday ? 'ring-2 ring-blue-500 ring-inset bg-blue-50/20' : '' }}">
                    
                    <!-- Day Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-1.5">
                            <span class="font-black text-sm {{ $isToday ? 'w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs shadow-sm' : ($cell['is_weekend'] ? 'text-amber-900 font-black' : ($cell['is_current_month'] ? 'text-slate-900' : 'text-slate-400')) }}">
                                {{ $cell['day'] }}
                            </span>
                            @if($isToday)
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Dnes</span>
                            @endif
                        </div>
                    </div>

                    <!-- Public Holiday Tag -->
                    @if($hasHoliday)
                        <div class="mt-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 font-extrabold text-[10px] truncate" title="{{ $cell['holiday'] }}">
                            🎉 {{ $cell['holiday'] }}
                        </div>
                    @endif

                    <!-- Tasks List in Day Cell -->
                    <div class="mt-1.5 space-y-1 flex-grow overflow-y-auto max-h-24">
                        @foreach($cell['tasks'] as $t)
                            @php
                                $badge = $t->priority_badge;
                                $isDone = ($t->status === 'COMPLETED');
                            @endphp
                            <div onclick="openTaskDetailModal({{ $t->toJson() }})"
                                class="p-1.5 rounded-lg border text-[11px] font-medium transition cursor-pointer flex flex-col space-y-0.5 shadow-sm
                                {{ $isDone
                                    ? 'bg-slate-50 text-slate-400 line-through border-slate-200'
                                    : ($t->priority === 'URGENT' ? 'bg-rose-50 border-rose-200 text-rose-900 font-bold hover:bg-rose-100' : 'bg-blue-50 border-blue-200 text-blue-900 hover:bg-blue-100') }}">
                                <div class="flex items-center justify-between">
                                    <span class="truncate font-bold">{{ $t->title }}</span>
                                    <span class="w-2 h-2 rounded-full {{ $badge['dot'] }} flex-shrink-0 ml-1"></span>
                                </div>
                                @if($t->start_time)
                                    <span class="text-[9px] text-slate-500 font-mono">{{ substr($t->start_time, 0, 5) }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- LIST OF UPCOMING DIRECTIVES & TASKS -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Zoznam Pokynov Experta pre terén a Úloh za {{ $slovakMonths[$month] }} {{ $year }}</h3>
                <p class="text-xs text-slate-500">Kompletný prehľad pridelených úloh, termínov a ich aktuálny stav</p>
            </div>
            <span class="px-3 py-1 bg-slate-200 text-slate-700 font-bold text-xs rounded-full">
                {{ $allTasks->count() }} záznamov
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs font-bold uppercase border-b border-slate-200">
                        <th class="p-3 w-28">Dátum</th>
                        <th class="p-3 w-48">Kategória</th>
                        <th class="p-3">Názov Úlohy a Popis</th>
                        <th class="p-3 w-36">Priradené</th>
                        <th class="p-3 w-32 text-center">Priorita</th>
                        <th class="p-3 w-36 text-center">Stav</th>
                        <th class="p-3 w-28 text-right">Akcia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($allTasks as $task)
                        @php
                            $badge = $task->priority_badge;
                            $isDone = ($task->status === 'COMPLETED');
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $isDone ? 'bg-slate-50/60' : '' }}">
                            <td class="p-3 font-bold font-mono text-slate-900">
                                📅 {{ $task->task_date->format('d.m.Y') }}
                                @if($task->start_time)
                                    <div class="text-[10px] text-slate-400 font-normal">{{ substr($task->start_time, 0, 5) }}</div>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-800">
                                    {{ $task->category_label }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 {{ $isDone ? 'line-through text-slate-400' : '' }}">{{ $task->title }}</div>
                                @if($task->description)
                                    <div class="text-slate-500 text-[11px] mt-0.5 line-clamp-2">{{ $task->description }}</div>
                                @endif
                                @if($task->completion_note)
                                    <div class="text-emerald-700 text-[11px] mt-1 font-semibold">
                                        💬 Poznámka k splneniu: {{ $task->completion_note }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-3 font-medium text-slate-700">
                                @if($task->kapz)
                                    👤 {{ $task->kapz->full_name }}
                                @elseif($task->target_scope)
                                    🌐 {{ $task->target_scope }}
                                @else
                                    🌐 Všetci KAPZ
                                @endif
                                <div class="text-[10px] text-slate-400">Od: {{ $task->assignedBy->name }}</div>
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black border {{ $badge['bg'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                @if($task->status === 'COMPLETED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        ✅ Splnené
                                    </span>
                                @elseif($task->status === 'IN_PROGRESS')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-100 text-blue-800">
                                        ⚙️ V riešení
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                        ⏳ Čaká
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <button type="button" onclick="openTaskDetailModal({{ $task->toJson() }})"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-blue-50 hover:text-blue-700 font-bold rounded-lg transition text-[11px]">
                                    Detail / Stav &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                                Žiadne zaevidované úlohy alebo pokyny pre zvolené obdobie.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: DETAIL ÚLOHY & ZMENA STAVU -->
<div id="taskDetailModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-150">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider" id="modalTaskCategory">
                Detail Pokynu
            </h3>
            <button type="button" onclick="closeTaskDetailModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <div class="p-5 space-y-4">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase">Názov úlohy:</div>
                <h2 class="text-base font-black text-slate-900 mt-0.5" id="modalTaskTitle"></h2>
            </div>

            <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Termín:</span>
                    <strong class="text-slate-900" id="modalTaskDate"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase">Priorita:</span>
                    <strong class="text-slate-900" id="modalTaskPriority"></strong>
                </div>
            </div>

            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase mb-1">Popis a inštrukcie:</div>
                <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-700 border border-slate-100 whitespace-pre-line" id="modalTaskDescription"></div>
            </div>

            <!-- Status Changer Form -->
            <form id="taskStatusForm" method="POST" class="pt-3 border-t border-slate-100 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Aktualizovať Stav Úlohy:</label>
                    <select name="status" id="modalTaskStatusSelect" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold">
                        <option value="PENDING">⏳ Čaká na splnenie</option>
                        <option value="IN_PROGRESS">⚙️ V riešení</option>
                        <option value="COMPLETED">✅ Splnené</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Poznámka koordinátora k splneniu:</label>
                    <textarea name="completion_note" id="modalTaskCompletionNote" rows="2" placeholder="Zadajte správu o výsledku alebo splnení úlohy..."
                        class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" onclick="closeTaskDetailModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Zavrieť
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                        💾 Uložiť Stav
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Modals & Interactive Actions -->
<script>
    function openTaskDetailModal(task) {
        document.getElementById('modalTaskCategory').innerText = task.category || 'Detail Pokynu';
        document.getElementById('modalTaskTitle').innerText = task.title;
        document.getElementById('modalTaskDate').innerText = task.task_date + (task.start_time ? ' (' + task.start_time.substring(0, 5) + ')' : '');
        document.getElementById('modalTaskPriority').innerText = task.priority || 'Štandardná';
        document.getElementById('modalTaskDescription').innerText = task.description || 'Bez dodatočného popisu.';
        document.getElementById('modalTaskStatusSelect').value = task.status;
        document.getElementById('modalTaskCompletionNote').value = task.completion_note || '';

        const form = document.getElementById('taskStatusForm');
        form.action = '/calendar/tasks/' + task.id + '/status';

        const modal = document.getElementById('taskDetailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeTaskDetailModal() {
        const modal = document.getElementById('taskDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
