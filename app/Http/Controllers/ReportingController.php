<?php

namespace App\Http\Controllers;

use App\Models\ReportingPeriod;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingController extends Controller
{
    public function index(Request $request, ReportingService $reportingService)
    {
        $periodId = $request->input('period_id', 1);
        $period = ReportingPeriod::findOrFail($periodId);
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        $attendanceSummary = $reportingService->getAttendanceSummary($period, $this->visibleKapzIds());
        $travelKmSummary = $reportingService->getTravelKmSummary($period, $this->visibleKapzIds());

        return view('reports.index', compact('period', 'periods', 'attendanceSummary', 'travelKmSummary'));
    }

    public function exportCsv(Request $request, ReportingService $reportingService)
    {
        $period = ReportingPeriod::findOrFail($request->input('period_id', 1));
        $travelKmSummary = $reportingService->getTravelKmSummary($period, $this->visibleKapzIds());

        $response = new StreamedResponse(function () use ($travelKmSummary) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['KAPZ Meno', 'Osobne Cislo', 'Posobnost', 'Stav Planu', 'Planovane KM', 'Odjazdene KM', 'Naklady EUR']);

            foreach ($travelKmSummary as $row) {
                fputcsv($handle, [
                    $row['kapz']->full_name,
                    $row['kapz']->personal_number,
                    $row['kapz']->scope,
                    $row['status'],
                    $row['planned_km'],
                    $row['actual_km'],
                    $row['total_cost'],
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="REPORT_CESTOVNE_LIMITY_' . $period->year . '_' . sprintf('%02d', $period->month) . '.csv"');

        return $response;
    }
}
