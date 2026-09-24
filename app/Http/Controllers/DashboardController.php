<?php

namespace App\Http\Controllers;

use App\Models\ReportingPeriod;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use App\Models\TravelExpense;
use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
use App\Services\AttendanceCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(AttendanceCalculatorService $attendanceService, Request $request)
    {
        $user = Auth::user();

        // Selected period (default August 2026)
        $year = $request->input('year', 2026);
        $month = $request->input('month', 8);

        $period = ReportingPeriod::firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['is_closed' => false]
        );

        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        if ($user->isSupervisor()) {
            return $this->adminDashboard($period, $periods, $attendanceService, $user->accessibleKapzIds());
        }

        return $this->kapzDashboard($user, $period, $periods, $attendanceService);
    }

    private function kapzDashboard($user, $period, $periods, AttendanceCalculatorService $attendanceService)
    {
        $kapz = $user->kapzProfile;

        if (!$kapz) {
            return view('dashboard.no_profile');
        }

        // Assigned APZs for selected period date
        $refDate = sprintf('%04d-%02d-15', $period->year, $period->month);
        $assignedApzs = $kapz->assignedApzsForDate($refDate);

        // KAPZ Monthly Attendance metrics
        $kapzSummary = $attendanceService->calculateKapzMonthlySummary($kapz->id, $period->id);

        // APZ Monthly metrics for assigned group
        $apzSummaries = [];
        $groupWorkDays = 0;
        $groupWorkHours = 0;
        $groupHolidays = 0;
        $groupPn = 0;
        $groupOcr = 0;
        $groupDoctor = 0;

        foreach ($assignedApzs as $apz) {
            $sum = $attendanceService->calculateApzMonthlySummary($apz->id, $period->id);
            $apzSummaries[$apz->id] = [
                'profile' => $apz,
                'summary' => $sum,
            ];
            $groupWorkDays += $sum['work_days'];
            $groupWorkHours += $sum['total_hours'];
            $groupHolidays += $sum['holiday_days'];
            $groupPn += $sum['pn_days'];
            $groupOcr += $sum['ocr_days'];
            $groupDoctor += ($sum['doctor_days'] ?? 0) + ($sum['doctor_family_days'] ?? 0);
        }

        // Travel plan for this period
        $travelPlan = TravelPlan::with(['items.targetApz'])->where('kapz_id', $kapz->id)
            ->where('reporting_period_id', $period->id)
            ->first();

        $plannedKm = $travelPlan ? $travelPlan->items->sum('estimated_km') : 0;
        $expenses = TravelExpense::where('kapz_id', $kapz->id)->get();
        $actualKm = $expenses->sum('total_km');
        $totalCost = $expenses->sum('final_balance');

        // 1. Locality Visit Frequency (Frekvencia návštevností lokalít)
        $locationStats = [];
        if ($travelPlan && $travelPlan->items->count() > 0) {
            $groupedByLocation = $travelPlan->items->groupBy('destination_location');
            foreach ($groupedByLocation as $location => $items) {
                $locationStats[$location] = [
                    'location' => $location,
                    'visits_count' => $items->count(),
                    'total_km' => $items->sum('estimated_km'),
                    'purposes' => $items->pluck('purpose')->unique()->values()->all(),
                ];
            }
            // Sort by visits count descending, then total km descending
            uasort($locationStats, function ($a, $b) {
                if ($a['visits_count'] === $b['visits_count']) {
                    return $b['total_km'] <=> $a['total_km'];
                }
                return $b['visits_count'] <=> $a['visits_count'];
            });
        }

        // 2. Purposes Breakdown (Účely ciest a ich počet)
        $purposeStats = [];
        if ($travelPlan && $travelPlan->items->count() > 0) {
            $groupedByPurpose = $travelPlan->items->groupBy(function ($item) {
                $p = trim($item->purpose);
                if (stripos($p, 'Kontrola') !== false || stripos($p, 'výkonu') !== false) {
                    return 'Kontrola a podpora výkonu APZ v teréne';
                } elseif (stripos($p, 'Koordinačn') !== false || stripos($p, 'samospráv') !== false || stripos($p, 'lekár') !== false) {
                    return 'Koordinačné stretnutie (samospráva, lekári)';
                } elseif (stripos($p, 'Distribúc') !== false || stripos($p, 'materiál') !== false) {
                    return 'Distribúcia zdravotníckeho a osvetového materiálu';
                } elseif (stripos($p, 'Metodic') !== false || stripos($p, 'usmernen') !== false) {
                    return 'Metodické usmernenie a riešenie potrieb';
                } elseif (stripos($p, 'osvet') !== false || stripos($p, 'prednáš') !== false) {
                    return 'Osvetová a komunitná činnosť';
                }
                return $p;
            });

            foreach ($groupedByPurpose as $purposeName => $items) {
                $purposeStats[$purposeName] = [
                    'purpose' => $purposeName,
                    'trips_count' => $items->count(),
                    'total_km' => $items->sum('estimated_km'),
                ];
            }
            uasort($purposeStats, fn($a, $b) => $b['trips_count'] <=> $a['trips_count']);
        }

        // 3. Transport modes distribution for chart
        $transportStats = [
            'AAuto' => $travelPlan ? $travelPlan->items->where('transport_mode', 'AAuto')->sum('estimated_km') : 0,
            'VHD' => $travelPlan ? $travelPlan->items->where('transport_mode', 'VHD')->sum('estimated_km') : 0,
            'PAuto' => $travelPlan ? $travelPlan->items->where('transport_mode', 'PAuto')->sum('estimated_km') : 0,
        ];

        return view('dashboard.kapz', compact(
            'kapz',
            'period',
            'periods',
            'assignedApzs',
            'kapzSummary',
            'apzSummaries',
            'travelPlan',
            'plannedKm',
            'actualKm',
            'totalCost',
            'groupWorkDays',
            'groupWorkHours',
            'groupHolidays',
            'groupPn',
            'groupOcr',
            'groupDoctor',
            'locationStats',
            'purposeStats',
            'transportStats'
        ));
    }

    /**
     * Prehľad pre nadriadených. $visibleKapzIds = null → všetci KAPZ (admin, manažment),
     * inak iba KAPZ pridelení expertovi.
     */
    private function adminDashboard($period, $periods, AttendanceCalculatorService $attendanceService, ?array $visibleKapzIds = null)
    {
        $scoped = fn ($query, string $column = 'kapz_id') => $visibleKapzIds === null ? $query : $query->whereIn($column, $visibleKapzIds);

        $allKapz = $scoped(KapzProfile::with('user'), 'id')->get();
        $allApz = $visibleKapzIds === null
            ? ApzProfile::all()
            : ApzProfile::whereHas('assignments', fn ($q) => $q->whereIn('kapz_id', $visibleKapzIds))->get();
        $pendingPlans = $scoped(TravelPlan::with('kapz'))->where('status', 'SUBMITTED')->get();
        $approvedPlansCount = $scoped(TravelPlan::query())->where('status', 'APPROVED')->count();
        $totalApzCount = $allApz->count();
        $totalKapzCount = $allKapz->count();

        // System-wide attendance stats for this period
        $allKapzAttendance = $scoped(AttendanceKapz::query())->where('reporting_period_id', $period->id)->get();
        $allApzAttendance = $scoped(AttendanceApz::query())->where('reporting_period_id', $period->id)->get();

        $totalKapzHours = $allKapzAttendance->sum('hours_worked');
        $totalApzHours = $allApzAttendance->sum('hours_worked');
        $totalSystemHours = $totalKapzHours + $totalApzHours;

        $totalWorkDays = $allApzAttendance->where('status', 'work')->count();
        $totalHolidays = $allApzAttendance->where('status', 'holiday')->count();
        $totalPn = $allApzAttendance->where('status', 'pn')->count();
        $totalOcr = $allApzAttendance->where('status', 'ocr')->count();
        $totalOtherAbsences = $allApzAttendance->whereNotIn('status', ['work', 'holiday', 'pn', 'ocr', 'weekend'])->count();

        // Travel stats
        $allPlans = $scoped(TravelPlan::with('items'))->where('reporting_period_id', $period->id)->get();
        $totalPlannedKm = $allPlans->sum(fn($p) => $p->items->sum('estimated_km'));
        $allExpenses = $scoped(TravelExpense::query())->get();
        $totalActualKm = $allExpenses->sum('total_km');
        $totalTravelCosts = $allExpenses->sum('final_balance');

        // Status matrix across all KAPZs
        $kapzStatusMatrix = [];
        $regionKmStats = [];
        $allLocations = [];
        $allPurposes = [];

        foreach ($allPlans as $plan) {
            foreach ($plan->items as $item) {
                $loc = $item->destination_location;
                if ($loc) {
                    $allLocations[$loc] = ($allLocations[$loc] ?? 0) + 1;
                }
                $purp = trim($item->purpose);
                if ($purp) {
                    $allPurposes[$purp] = ($allPurposes[$purp] ?? 0) + 1;
                }
            }
        }
        arsort($allLocations);
        arsort($allPurposes);

        foreach ($allKapz as $kapz) {
            $plan = TravelPlan::where('kapz_id', $kapz->id)
                ->where('reporting_period_id', $period->id)
                ->first();

            $kapzAttendanceCount = AttendanceKapz::where('kapz_id', $kapz->id)
                ->where('reporting_period_id', $period->id)
                ->count();

            $km = $plan ? $plan->items->sum('estimated_km') : 0;
            $regionKmStats[$kapz->scope] = ($regionKmStats[$kapz->scope] ?? 0) + $km;

            $kapzStatusMatrix[] = [
                'kapz' => $kapz,
                'plan_status' => $plan ? $plan->status : 'NEVYTVORENÝ',
                'planned_km' => $km,
                'attendance_complete' => ($kapzAttendanceCount >= 20),
                'assigned_apz_count' => $kapz->assignedApzsForDate(sprintf('%04d-%02d-15', $period->year, $period->month))->count(),
            ];
        }

        return view('dashboard.admin', compact(
            'period',
            'periods',
            'allKapz',
            'pendingPlans',
            'approvedPlansCount',
            'totalApzCount',
            'totalKapzCount',
            'totalSystemHours',
            'totalKapzHours',
            'totalApzHours',
            'totalWorkDays',
            'totalHolidays',
            'totalPn',
            'totalOcr',
            'totalOtherAbsences',
            'totalPlannedKm',
            'totalActualKm',
            'totalTravelCosts',
            'kapzStatusMatrix',
            'regionKmStats',
            'allLocations',
            'allPurposes'
        ));
    }
}
