<?php

namespace App\Services;

use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ReportingPeriod;
use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
use App\Models\TravelExpense;
use App\Models\TravelPlan;

class ReportingService
{
    /**
     * Get system-wide attendance summary for a given reporting period.
     */
    public function getAttendanceSummary(ReportingPeriod $period, ?array $kapzIds = null): array
    {
        $kapzEntries = AttendanceKapz::where('reporting_period_id', $period->id)
            ->when($kapzIds !== null, fn ($q) => $q->whereIn('kapz_id', $kapzIds))
            ->get();
        $apzEntries = AttendanceApz::where('reporting_period_id', $period->id)
            ->when($kapzIds !== null, fn ($q) => $q->whereIn('kapz_id', $kapzIds))
            ->get();

        return [
            'kapz_total_hours' => (float) $kapzEntries->sum('hours_worked'),
            'kapz_work_days' => $kapzEntries->where('status', 'work')->count(),
            'kapz_holiday_days' => $kapzEntries->where('status', 'holiday')->count(),
            'kapz_pn_days' => $kapzEntries->where('status', 'pn')->count(),
            'apz_total_hours' => (float) $apzEntries->sum('hours_worked'),
            'apz_work_days' => $apzEntries->where('status', 'work')->count(),
            'apz_holiday_days' => $apzEntries->where('status', 'holiday')->count(),
            'apz_pn_days' => $apzEntries->where('status', 'pn')->count(),
        ];
    }

    /**
     * Get travel km consumption summary by KAPZ.
     */
    public function getTravelKmSummary(ReportingPeriod $period, ?array $kapzIds = null): array
    {
        $plans = TravelPlan::with(['kapz', 'items'])
            ->where('reporting_period_id', $period->id)
            ->when($kapzIds !== null, fn ($q) => $q->whereIn('kapz_id', $kapzIds))
            ->get();

        $result = [];
        foreach ($plans as $plan) {
            $plannedKm = $plan->items->sum('estimated_km');
            $expenses = TravelExpense::where('kapz_id', $plan->kapz_id)->get();
            $actualKm = $expenses->sum('total_km');

            $result[] = [
                'kapz' => $plan->kapz,
                'status' => $plan->status,
                'planned_km' => (float) $plannedKm,
                'actual_km' => (float) $actualKm,
                'total_cost' => (float) $expenses->sum('final_balance'),
            ];
        }

        return $result;
    }
}
