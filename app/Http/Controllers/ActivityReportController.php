<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\ReportingPeriod;
use App\Models\KapzProfile;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $periodId = $request->input('period_id', 1);
        $period = ReportingPeriod::findOrFail($periodId);
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        if ($user->isSupervisor()) {
            $visible = $this->visibleKapzIds();
            $reports = ActivityReport::with(['kapz', 'reportingPeriod'])
                ->where('reporting_period_id', $period->id)
                ->when($visible !== null, fn ($q) => $q->whereIn('kapz_id', $visible))
                ->get();
        } else {
            $kapz = $user->kapzProfile;
            $reports = ActivityReport::with(['kapz', 'reportingPeriod'])
                ->where('kapz_id', $kapz->id)
                ->where('reporting_period_id', $period->id)
                ->get();
        }

        return view('activity_reports.index', compact('reports', 'period', 'periods'));
    }

    public function downloadPdf(ActivityReport $report, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeKapzAccess($report->kapz_id);
        $pdf = $pdfGenerator->generateActivityReportPdf($report);
        return $pdf->download('SPRAVA_O_CINNOSTI_' . $report->kapz->personal_number . '.pdf');
    }
}
