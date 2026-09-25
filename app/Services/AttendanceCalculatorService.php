<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceApz;
use App\Models\AttendanceKapz;
use App\Models\ReportingPeriod;
use Illuminate\Support\Collection;

/**
 * Výpočty EVIDENCIA ODPRACOVANEJ DOBY podľa hárkov EVIDENCIA_KAPZ / EVIDENCIA_APZ:
 *  - E39 = súčet odpracovaných hodín, G39 = súčet neodpracovaných hodín,
 *  - pravý panel (K8–K34) = počty dní podľa typu, polovičný deň = 0,5 dňa,
 *  - kontrola fondu: E39 + G39 = fond z hárku Kalendár.
 */
class AttendanceCalculatorService
{
    private const BUCKETS = [
        'sviatky_days', 'holiday_days', 'pn_days', 'ocr_days', 'md_rd_days', 'kz_days',
        'doctor_days', 'doctor_family_days', 'blood_days', 'funeral_days', 'unpaid_days',
        'nv_days', 'other_days',
    ];

    public function __construct(private ?WorkingTimeFundService $fund = null)
    {
        $this->fund ??= new WorkingTimeFundService();
    }

    public function calculateKapzMonthlySummary(int $kapzId, int $reportingPeriodId): array
    {
        $entries = AttendanceKapz::where('kapz_id', $kapzId)
            ->where('reporting_period_id', $reportingPeriodId)
            ->orderBy('date', 'asc')
            ->get();

        return $this->summarize($entries, $reportingPeriodId);
    }

    public function calculateApzMonthlySummary(int $apzId, int $reportingPeriodId): array
    {
        $entries = AttendanceApz::where('apz_id', $apzId)
            ->where('reporting_period_id', $reportingPeriodId)
            ->orderBy('date', 'asc')
            ->get();

        return $this->summarize($entries, $reportingPeriodId);
    }

    public function summarize(Collection $entries, int $reportingPeriodId): array
    {
        $buckets = array_fill_keys(self::BUCKETS, 0.0);
        $workHours = 0.0;
        $absenceHours = 0.0;
        $fullWorkDays = 0;

        foreach ($entries as $entry) {
            $status = AttendanceStatus::fromCode($entry->status);

            if ($status->workedHours() > 0) {
                // Pri práci platí hodnota zo záznamu (predvolene 7,5 h), pri polovičnom dni 3,75 h.
                $workHours += $status === AttendanceStatus::Work
                    ? (float) $entry->hours_worked
                    : $status->workedHours();
            }
            if ($status === AttendanceStatus::Work) {
                $fullWorkDays++;
            }

            $absenceHours += $status->unworkedHours();

            if ($bucket = $status->summaryBucket()) {
                $buckets[$bucket] += $status->dayWeight();
            }
        }

        $period = ReportingPeriod::find($reportingPeriodId);
        $workingDays = $period ? $this->fund->workingDays($period->year, $period->month) : 0;
        $fundHours = $workingDays * AttendanceStatus::FULL_DAY_HOURS;

        // K32 / K34 (skutočne odpracované dni, finančný príspevok) = odpracované hodiny / 7,5
        // (rovnako ako EVIDENCIA_APZ!F41 = E41/7,5).
        $actualWorkDays = $this->number($workHours / AttendanceStatus::FULL_DAY_HOURS);

        $result = [
            'total_hours' => $workHours,
            'total_work_hours' => $workHours,
            'total_absence_hours' => $absenceHours,
            'work_days' => $fullWorkDays,
            'actual_work_days' => $actualWorkDays,
            'financial_days' => $actualWorkDays,
            'total_working_days' => $workingDays,
            'fund_hours' => $fundHours,
            'fund_difference' => round($fundHours - $workHours - $absenceHours, 2),
            'fund_ok' => abs($fundHours - $workHours - $absenceHours) < 0.001,
            'entries' => $entries,
        ];

        foreach ($buckets as $key => $value) {
            $result[$key] = $this->number($value);
        }

        return $result;
    }

    /** 3.0 → 3, 2.5 → 2.5 (aby PDF neukazovalo „3.0“ pri celých dňoch). */
    private function number(float $value): int|float
    {
        return floor($value) == $value ? (int) $value : round($value, 2);
    }
}
