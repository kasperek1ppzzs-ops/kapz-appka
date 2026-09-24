<?php

namespace App\Http\Controllers;

use App\Models\CalendarTask;
use App\Models\KapzProfile;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isExpertOrAdmin = $user->isAdmin() || in_array($user->role, ['expert', 'manager']);
        $kapz = $user->kapzProfile;

        // Year and Month
        $year = (int) $request->input('year', 2026);
        $month = (int) $request->input('month', 8);

        if ($month < 1) {
            $month = 12;
            $year--;
        } elseif ($month > 12) {
            $month = 1;
            $year++;
        }

        $allKapzList = KapzProfile::visibleTo($user)->with('user')->orderBy('full_name')->get();
        $selectedKapzId = $request->input('kapz_id', $kapz?->id);
        if ($selectedKapzId && !$user->canAccessKapz($selectedKapzId)) {
            abort(403, 'Nemáte prístup k údajom tohto KAPZ.');
        }
        $visibleKapzIds = $user->accessibleKapzIds();

        // Slovak Public Holidays Map (Day.Month => Title)
        $slovakHolidays = [
            '01-01' => 'Deň vzniku SR',
            '01-06' => 'Zjavenie Pána (Traja králi)',
            '05-01' => 'Sviatok práce',
            '05-08' => 'Deň víťazstva nad fašizmom',
            '07-05' => 'Sviatok sv. Cyrila a Metoda',
            '08-29' => 'Výročie SNP',
            '09-01' => 'Deň Ústavy SR',
            '09-15' => 'Sedembolestná Panna Mária',
            '11-01' => 'Sviatok všetkých svätých',
            '11-17' => 'Deň boja za slobodu a demokraciu',
            '12-24' => 'Štedrý deň',
            '12-25' => 'Prvý sviatok vianočný',
            '12-26' => 'Druhý sviatok vianočný',
        ];

        // Query tasks for the month
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $tasksQuery = CalendarTask::with(['assignedBy', 'kapz'])
            ->whereBetween('task_date', [$startDate, $endDate]);

        if (!$isExpertOrAdmin && $kapz) {
            $tasksQuery->where(function ($q) use ($kapz) {
                $q->where('kapz_id', $kapz->id)
                  ->orWhereNull('kapz_id')
                  ->orWhere('target_scope', $kapz->scope)
                  ->orWhere('target_scope', 'VŠETCI');
            });
        } elseif (!$selectedKapzId && $visibleKapzIds !== null) {
            // Expert bez výberu: úlohy jeho KAPZ, všeobecné úlohy a úlohy, ktoré sám zadal.
            $tasksQuery->where(function ($q) use ($visibleKapzIds, $user) {
                $q->whereIn('kapz_id', $visibleKapzIds)
                  ->orWhereNull('kapz_id')
                  ->orWhere('assigned_by_user_id', $user->id);
            });
        } elseif ($selectedKapzId) {
            $tasksQuery->where(function ($q) use ($selectedKapzId) {
                $q->where('kapz_id', $selectedKapzId)
                  ->orWhereNull('kapz_id')
                  ->orWhere('target_scope', 'VŠETCI');
            });
        }

        $allTasks = $tasksQuery->orderBy('start_time')->orderBy('priority', 'desc')->get();
        $tasksByDay = $allTasks->groupBy(fn($t) => $t->task_date->format('Y-m-d'));

        // Calendar grid structure (Monday = 1 to Sunday = 7)
        $firstDayTimestamp = strtotime("$year-$month-01");
        $firstDayOfWeek = (int) date('N', $firstDayTimestamp); // 1 = Mon, 7 = Sun

        $calendarDays = [];

        // Previous month leading days
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $prevMonthDaysCount = cal_days_in_month(CAL_GREGORIAN, $prevMonth, $prevYear);

        for ($i = $firstDayOfWeek - 1; $i > 0; $i--) {
            $dayNumber = $prevMonthDaysCount - $i + 1;
            $dateStr = sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $dayNumber);
            $calendarDays[] = [
                'day' => $dayNumber,
                'date' => $dateStr,
                'is_current_month' => false,
                'is_weekend' => (date('N', strtotime($dateStr)) >= 6),
                'holiday' => $slovakHolidays[date('m-d', strtotime($dateStr))] ?? null,
                'tasks' => collect([]),
            ];
        }

        // Current month days
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $calendarDays[] = [
                'day' => $d,
                'date' => $dateStr,
                'is_current_month' => true,
                'is_weekend' => (date('N', strtotime($dateStr)) >= 6),
                'holiday' => $slovakHolidays[sprintf('%02d-%02d', $month, $d)] ?? null,
                'tasks' => $tasksByDay->get($dateStr, collect([])),
            ];
        }

        // Trailing days for next month to complete the 7-col grid
        $trailingDaysCount = 7 - (count($calendarDays) % 7);
        if ($trailingDaysCount > 0 && $trailingDaysCount < 7) {
            $nextMonth = $month + 1;
            $nextYear = $year;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            for ($d = 1; $d <= $trailingDaysCount; $d++) {
                $dateStr = sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $d);
                $calendarDays[] = [
                    'day' => $d,
                    'date' => $dateStr,
                    'is_current_month' => false,
                    'is_weekend' => (date('N', strtotime($dateStr)) >= 6),
                    'holiday' => $slovakHolidays[date('m-d', strtotime($dateStr))] ?? null,
                    'tasks' => collect([]),
                ];
            }
        }

        $slovakMonths = [
            1 => 'Január', 2 => 'Február', 3 => 'Marec', 4 => 'Apríl',
            5 => 'Máj', 6 => 'Jún', 7 => 'Júl', 8 => 'August',
            9 => 'September', 10 => 'Október', 11 => 'November', 12 => 'December'
        ];

        // Summary stats
        $stats = [
            'total' => $allTasks->count(),
            'pending' => $allTasks->where('status', 'PENDING')->count(),
            'in_progress' => $allTasks->where('status', 'IN_PROGRESS')->count(),
            'completed' => $allTasks->where('status', 'COMPLETED')->count(),
            'urgent' => $allTasks->where('priority', 'URGENT')->count(),
        ];

        return view('calendar.index', compact(
            'year',
            'month',
            'calendarDays',
            'allTasks',
            'tasksByDay',
            'allKapzList',
            'selectedKapzId',
            'isExpertOrAdmin',
            'kapz',
            'slovakMonths',
            'stats'
        ));
    }

    public function storeTask(Request $request)
    {
        $request->validate([
            'task_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'kapz_id' => ['nullable'],
        ]);

        $user = Auth::user();
        $kapzId = $request->kapz_id;

        // If KAPZ is creating a personal task for himself
        if (!$user->isAdmin() && !in_array($user->role, ['expert', 'manager'])) {
            $kapzId = $user->kapzProfile?->id;
        } elseif ($user->isExpert()) {
            // Expert zadáva úlohy iba svojim prideleným KAPZ.
            abort_if(empty($kapzId) || $kapzId === 'ALL' || !$user->canAccessKapz($kapzId), 403, 'Úlohu môžete zadať iba prideleným KAPZ.');
        }

        CalendarTask::create([
            'assigned_by_user_id' => $user->id,
            'kapz_id' => ($kapzId === 'ALL' || empty($kapzId)) ? null : (int) $kapzId,
            'target_scope' => ($kapzId === 'ALL') ? 'VŠETCI' : null,
            'task_date' => $request->task_date,
            'start_time' => $request->start_time ?: null,
            'end_time' => $request->end_time ?: null,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'PENDING',
        ]);

        return back()->with('success', 'Úloha / pokyn bol úspešne zaevidovaný do kalendára.');
    }

    public function updateStatus(Request $request, CalendarTask $task)
    {
        $request->validate([
            'status' => ['required', 'in:PENDING,IN_PROGRESS,COMPLETED'],
            'completion_note' => ['nullable', 'string'],
        ]);

        $status = $request->status;
        $task->update([
            'status' => $status,
            'completion_note' => $request->completion_note,
            'completed_at' => ($status === 'COMPLETED') ? now() : null,
        ]);

        return back()->with('success', 'Stav úlohy bol aktualizovaný.');
    }

    public function deleteTask(CalendarTask $task)
    {
        $user = Auth::user();
        // Allow author or admin/expert to delete
        if ($task->assigned_by_user_id == $user->id || $user->isAdmin()) {
            $task->delete();
            return back()->with('success', 'Úloha bola odstránená z kalendára.');
        }

        return back()->with('error', 'Nemáte oprávnenie odstrániť túto úlohu.');
    }
}
