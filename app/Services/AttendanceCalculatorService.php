<?php

namespace App\Services;

use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
use App\Models\ReportingPeriod;
use Illuminate\Support\Collection;

class AttendanceCalculatorService
{
    /**
     * Calculate monthly summary for KAPZ attendance according to official EVIDENCIA ODPRACOVANEJ DOBY schema.
     */
    public function calculateKapzMonthlySummary(int $kapzId, int $reportingPeriodId): array
    {
        $entries = AttendanceKapz::where('kapz_id', $kapzId)
            ->where('reporting_period_id', $reportingPeriodId)
            ->orderBy('date', 'asc')
            ->get();

        $workEntries = $entries->where('status', 'work');
        $actualWorkDays = $workEntries->count();
        $totalWorkHours = $workEntries->sum('hours_worked');

        $sviatkyDays = $entries->where('status', 'public_holiday')->count();
        $holidayDays = $entries->where('status', 'holiday')->count();
        $pnDays = $entries->where('status', 'pn')->count();
        $ocrDays = $entries->where('status', 'ocr')->count();
        $mdRdDays = $entries->where('status', 'md_rd')->count();
        $kzDays = $entries->where('status', 'paid_absence')->count();
        $doctorDays = $entries->where('status', 'doctor')->count();
        $doctorFamilyDays = $entries->where('status', 'doctor_family')->count();
        $bloodDays = $entries->where('status', 'blood_donation')->count();
        $funeralDays = $entries->where('status', 'funeral')->count();
        $unpaidDays = $entries->where('status', 'unpaid_absence')->count();
        $nvDays = $entries->where('status', 'nv')->count();

        // Absence entries are everything except work & weekend
        $absenceEntries = $entries->reject(fn($e) => in_array($e->status, ['work', 'weekend']));
        $totalAbsenceHours = $absenceEntries->sum('hours_worked');

        // Total working days in month (work days + paid holidays + absences)
        $totalWorkingDaysInMonth = $actualWorkDays + $sviatkyDays + $holidayDays + $pnDays + $ocrDays + $doctorDays + $doctorFamilyDays + $bloodDays + $funeralDays + $kzDays + $nvDays;

        return [
            'total_hours' => (float) $totalWorkHours,
            'total_work_hours' => (float) $totalWorkHours,
            'total_absence_hours' => (float) $totalAbsenceHours,
            'work_days' => $actualWorkDays,
            'actual_work_days' => $actualWorkDays,
            'financial_days' => $actualWorkDays,
            'total_working_days' => $totalWorkingDaysInMonth > 0 ? $totalWorkingDaysInMonth : 23,
            'sviatky_days' => $sviatkyDays,
            'holiday_days' => $holidayDays,
            'pn_days' => $pnDays,
            'ocr_days' => $ocrDays,
            'md_rd_days' => $mdRdDays,
            'kz_days' => $kzDays,
            'doctor_days' => $doctorDays,
            'doctor_family_days' => $doctorFamilyDays,
            'blood_days' => $bloodDays,
            'funeral_days' => $funeralDays,
            'unpaid_days' => $unpaidDays,
            'nv_days' => $nvDays,
            'entries' => $entries,
        ];
    }

    /**
     * Calculate monthly summary for APZ attendance.
     */
    public function calculateApzMonthlySummary(int $apzId, int $reportingPeriodId): array
    {
        $entries = AttendanceApz::where('apz_id', $apzId)
            ->where('reporting_period_id', $reportingPeriodId)
            ->orderBy('date', 'asc')
            ->get();

        $workEntries = $entries->where('status', 'work');
        $actualWorkDays = $workEntries->count();
        $totalWorkHours = $workEntries->sum('hours_worked');

        $sviatkyDays = $entries->where('status', 'public_holiday')->count();
        $holidayDays = $entries->where('status', 'holiday')->count();
        $pnDays = $entries->where('status', 'pn')->count();
        $ocrDays = $entries->where('status', 'ocr')->count();
        $doctorDays = $entries->where('status', 'doctor')->count();
        $doctorFamilyDays = $entries->where('status', 'doctor_family')->count();

        $absenceEntries = $entries->reject(fn($e) => in_array($e->status, ['work', 'weekend']));
        $totalAbsenceHours = $absenceEntries->sum('hours_worked');

        $totalWorkingDaysInMonth = $actualWorkDays + $sviatkyDays + $holidayDays + $pnDays + $ocrDays + $doctorDays + $doctorFamilyDays;

        return [
            'total_hours' => (float) $totalWorkHours,
            'total_work_hours' => (float) $totalWorkHours,
            'total_absence_hours' => (float) $totalAbsenceHours,
            'work_days' => $actualWorkDays,
            'actual_work_days' => $actualWorkDays,
            'financial_days' => $actualWorkDays,
            'total_working_days' => $totalWorkingDaysInMonth > 0 ? $totalWorkingDaysInMonth : 23,
            'sviatky_days' => $sviatkyDays,
            'holiday_days' => $holidayDays,
            'pn_days' => $pnDays,
            'ocr_days' => $ocrDays,
            'doctor_days' => $doctorDays,
            'doctor_family_days' => $doctorFamilyDays,
            'entries' => $entries,
        ];
    }
}
