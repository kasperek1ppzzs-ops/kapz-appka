<?php

namespace App\Http\Controllers;

use App\Models\ReportingPeriod;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
use App\Services\AttendanceCalculatorService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function showKapzAttendance(Request $request, AttendanceCalculatorService $calculator)
    {
        $kapz = $this->resolveKapz($request);

        $periodId = $request->input('period_id', 1);
        $period = ReportingPeriod::findOrFail($periodId);
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        $summary = $calculator->calculateKapzMonthlySummary($kapz->id, $period->id);

        return view('attendance.kapz', compact('kapz', 'period', 'periods', 'summary'));
    }

    public function saveKapzAttendance(Request $request)
    {
        $request->validate([
            'kapz_id' => ['required', 'exists:kapz_profiles,id'],
            'period_id' => ['required', 'exists:reporting_periods,id'],
            'entries' => ['required', 'array'],
        ]);

        $this->authorizeKapzAccess($request->kapz_id);

        foreach ($request->entries as $date => $data) {
            $status = $data['status'] ?? 'work';
            $isWork = ($status === 'work');
            $workplace = $isWork ? (trim($data['workplace'] ?? '') ?: null) : null;
            $hours = $isWork ? ($data['hours_worked'] ?? 7.50) : ($data['hours_worked'] ?? 0.00);

            AttendanceKapz::updateOrCreate(
                [
                    'kapz_id' => $request->kapz_id,
                    'reporting_period_id' => $request->period_id,
                    'date' => $date,
                ],
                [
                    'workplace' => $workplace,
                    'status' => $status,
                    'hours_worked' => $hours,
                    'overtime_hours' => 0.00,
                    'activity_description' => $data['activity_description'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Evidencia dochádzky KAPZ bola úspešne uložená.');
    }

    public function quickFillKapzMonth(Request $request)
    {
        $request->validate([
            'kapz_id' => ['required', 'exists:kapz_profiles,id'],
            'period_id' => ['required', 'exists:reporting_periods,id'],
        ]);

        $this->authorizeKapzAccess($request->kapz_id);

        $period = ReportingPeriod::findOrFail($request->period_id);
        $kapz = KapzProfile::findOrFail($request->kapz_id);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year);
        $stdHours = ($kapz->employment_ratio ?? 1.0) * 7.50;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $period->year, $period->month, $d);
            $dayNum = date('N', strtotime($dateStr));
            $isWeekend = in_array($dayNum, [6, 7]);

            AttendanceKapz::updateOrCreate(
                [
                    'kapz_id' => $kapz->id,
                    'reporting_period_id' => $period->id,
                    'date' => $dateStr,
                ],
                [
                    'workplace' => $isWeekend ? null : ($kapz->scope ?: 'Banská Bystrica'),
                    'status' => $isWeekend ? 'weekend' : 'work',
                    'hours_worked' => $isWeekend ? 0.00 : $stdHours,
                    'overtime_hours' => 0.00,
                    'activity_description' => $isWeekend ? null : 'Koordinácia a kontrola v teréne',
                ]
            );
        }

        return back()->with('success', 'Mesiac bol úspešne predvyplnený štandardným pracovným fondom (7,5h) pre KAPZ ' . $kapz->full_name . '.');
    }

    public function downloadKapzPdf(Request $request, AttendanceCalculatorService $calculator, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeKapzAccess($request->kapz_id);
        $kapz = KapzProfile::findOrFail($request->kapz_id);
        $period = ReportingPeriod::findOrFail($request->period_id);
        $summary = $calculator->calculateKapzMonthlySummary($kapz->id, $period->id);

        $pdf = $pdfGenerator->generateKapzAttendancePdf($kapz, $period, $summary);
        return $pdf->download('EVIDENCIA_KAPZ_' . $kapz->personal_number . '_' . $period->year . '_' . sprintf('%02d', $period->month) . '.pdf');
    }

    public function showApzAttendance(Request $request, AttendanceCalculatorService $calculator)
    {
        $kapz = $this->resolveKapz($request);

        $periodId = $request->input('period_id', 1);
        $period = ReportingPeriod::findOrFail($periodId);
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        // Get assigned APZs for KAPZ for this period
        $refDate = sprintf('%04d-%02d-15', $period->year, $period->month);
        $assignedApzs = $kapz->assignedApzsForDate($refDate);

        if ($assignedApzs->isEmpty()) {
            if (!Auth::user()->isSupervisor()) {
                return redirect()->route('dashboard')->with('error', 'V tomto období nemáte pridelených žiadnych APZ.');
            }
            $assignedApzs = ApzProfile::all();
        }

        $apzId = $request->input('apz_id', $assignedApzs->first()?->id);
        $this->authorizeApzAccess($kapz->id, $apzId);
        $apz = ApzProfile::findOrFail($apzId);

        $summary = $calculator->calculateApzMonthlySummary($apz->id, $period->id);

        // Precompute summary for each assigned APZ so badges show real-time stats
        $apzStats = [];
        foreach ($assignedApzs as $item) {
            $sum = $calculator->calculateApzMonthlySummary($item->id, $period->id);
            $apzStats[$item->id] = [
                'work_days' => $sum['work_days'],
                'total_hours' => $sum['total_hours'],
                'holiday_days' => $sum['holiday_days'],
                'pn_days' => $sum['pn_days'],
                'is_complete' => ($sum['work_days'] + $sum['holiday_days'] + $sum['pn_days'] + $sum['ocr_days']) >= 20,
            ];
        }

        return view('attendance.apz', compact('apz', 'kapz', 'period', 'periods', 'assignedApzs', 'summary', 'apzStats'));
    }

    public function saveApzAttendance(Request $request)
    {
        $request->validate([
            'apz_id' => ['required', 'exists:apz_profiles,id'],
            'kapz_id' => ['required', 'exists:kapz_profiles,id'],
            'period_id' => ['required', 'exists:reporting_periods,id'],
            'entries' => ['required', 'array'],
        ]);

        $this->authorizeApzAccess($request->kapz_id, $request->apz_id);

        foreach ($request->entries as $date => $data) {
            $status = $data['status'] ?? 'work';
            $isWork = ($status === 'work');
            $workplace = $isWork ? (trim($data['workplace'] ?? '') ?: null) : null;
            $hours = $isWork ? ($data['hours_worked'] ?? 7.50) : ($data['hours_worked'] ?? 0.00);

            AttendanceApz::updateOrCreate(
                [
                    'apz_id' => $request->apz_id,
                    'reporting_period_id' => $request->period_id,
                    'date' => $date,
                ],
                [
                    'kapz_id' => $request->kapz_id,
                    'workplace' => $workplace,
                    'status' => $status,
                    'hours_worked' => $hours,
                    'activity_description' => $data['activity_description'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Evidencia dochádzky APZ bola úspešne uložená.');
    }

    public function quickFillApzMonth(Request $request)
    {
        $request->validate([
            'apz_id' => ['required', 'exists:apz_profiles,id'],
            'kapz_id' => ['required', 'exists:kapz_profiles,id'],
            'period_id' => ['required', 'exists:reporting_periods,id'],
        ]);

        $this->authorizeApzAccess($request->kapz_id, $request->apz_id);

        $period = ReportingPeriod::findOrFail($request->period_id);
        $apz = ApzProfile::findOrFail($request->apz_id);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year);
        $stdHours = ($apz->employment_ratio ?? 1.0) * 7.50;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $period->year, $period->month, $d);
            $dayNum = date('N', strtotime($dateStr));
            $isWeekend = in_array($dayNum, [6, 7]);

            AttendanceApz::updateOrCreate(
                [
                    'apz_id' => $apz->id,
                    'reporting_period_id' => $period->id,
                    'date' => $dateStr,
                ],
                [
                    'kapz_id' => $request->kapz_id,
                    'workplace' => $isWeekend ? null : ($apz->community_scope ?: $apz->scope),
                    'status' => $isWeekend ? 'weekend' : 'work',
                    'hours_worked' => $isWeekend ? 0.00 : $stdHours,
                    'activity_description' => $isWeekend ? null : 'Zdravotná mediácia a osveta v komunite ' . $apz->community_scope,
                ]
            );
        }

        return back()->with('success', 'Mesiac bol úspešne predvyplnený štandardným fondom (7,5h) pre APZ ' . $apz->full_name . '.');
    }

    public function quickFillAllApzMonth(Request $request)
    {
        $request->validate([
            'kapz_id' => ['required', 'exists:kapz_profiles,id'],
            'period_id' => ['required', 'exists:reporting_periods,id'],
        ]);

        $this->authorizeKapzAccess($request->kapz_id);

        $period = ReportingPeriod::findOrFail($request->period_id);
        $kapz = KapzProfile::findOrFail($request->kapz_id);
        $refDate = sprintf('%04d-%02d-15', $period->year, $period->month);
        $assignedApzs = $kapz->assignedApzsForDate($refDate);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year);

        foreach ($assignedApzs as $itemApz) {
            $stdHours = ($itemApz->employment_ratio ?? 1.0) * 7.50;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = sprintf('%04d-%02d-%02d', $period->year, $period->month, $d);
                $dayNum = date('N', strtotime($dateStr));
                $isWeekend = in_array($dayNum, [6, 7]);

                AttendanceApz::updateOrCreate(
                    [
                        'apz_id' => $itemApz->id,
                        'reporting_period_id' => $period->id,
                        'date' => $dateStr,
                    ],
                    [
                        'kapz_id' => $kapz->id,
                        'workplace' => $isWeekend ? null : ($itemApz->community_scope ?: $itemApz->scope),
                        'status' => $isWeekend ? 'weekend' : 'work',
                        'hours_worked' => $isWeekend ? 0.00 : $stdHours,
                        'activity_description' => $isWeekend ? null : 'Zdravotná mediácia a osveta v komunite ' . $itemApz->scope,
                    ]
                );
            }
        }

        return back()->with('success', 'Všetci priradení asistenti APZ (' . $assignedApzs->count() . ' osôb) boli úspešne predvyplnení štandardným fondom 7,5h.');
    }

    public function downloadApzPdf(Request $request, AttendanceCalculatorService $calculator, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeApzAccess($request->kapz_id, $request->apz_id);
        $apz = ApzProfile::findOrFail($request->apz_id);
        $kapz = KapzProfile::findOrFail($request->kapz_id);
        $period = ReportingPeriod::findOrFail($request->period_id);
        $summary = $calculator->calculateApzMonthlySummary($apz->id, $period->id);

        $pdf = $pdfGenerator->generateApzAttendancePdf($apz, $kapz, $period, $summary);
        return $pdf->download('EVIDENCIA_APZ_' . $apz->personal_number . '_' . $period->year . '_' . sprintf('%02d', $period->month) . '.pdf');
    }

    public function downloadAllApzPdf(Request $request, AttendanceCalculatorService $calculator, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeKapzAccess($request->kapz_id);
        $kapz = KapzProfile::findOrFail($request->kapz_id);
        $period = ReportingPeriod::findOrFail($request->period_id);
        $refDate = sprintf('%04d-%02d-15', $period->year, $period->month);
        $assignedApzs = $kapz->assignedApzsForDate($refDate);

        if ($assignedApzs->isEmpty() && Auth::user()->isSupervisor()) {
            $assignedApzs = ApzProfile::all();
        }

        $batchData = [];
        foreach ($assignedApzs as $itemApz) {
            $batchData[] = [
                'apz' => $itemApz,
                'summary' => $calculator->calculateApzMonthlySummary($itemApz->id, $period->id),
            ];
        }

        $pdf = $pdfGenerator->generateBatchApzAttendancePdf($batchData, $kapz, $period);
        return $pdf->download('EVIDENCIA_APZ_VSETCI_' . $kapz->personal_number . '_' . $period->year . '_' . sprintf('%02d', $period->month) . '.pdf');
    }
}
