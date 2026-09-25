<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\TravelSegmentService;
use App\Services\TravelOrderService;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\TravelOrder;
use App\Models\Statement;
use App\Models\ActivityReport;
use App\Models\PdfDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PdfGeneratorService
{
    /**
     * Generate PDF for KAPZ Attendance (EVIDENCIA_KAPZ)
     */
    public function generateKapzAttendancePdf(KapzProfile $kapz, ReportingPeriod $period, array $summary)
    {
        $pdf = Pdf::loadView('pdf.evidencia_kapz', [
            'kapz' => $kapz,
            'period' => $period,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Generate PDF for APZ Attendance (EVIDENCIA_APZ)
     */
    public function generateApzAttendancePdf(ApzProfile $apz, KapzProfile $kapz, ReportingPeriod $period, array $summary)
    {
        $pdf = Pdf::loadView('pdf.evidencia_apz', [
            'apz' => $apz,
            'kapz' => $kapz,
            'period' => $period,
            'summary' => $summary,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate Batch PDF for All APZ Attendances (EVIDENCIA_APZ_BATCH)
     */
    public function generateBatchApzAttendancePdf(array $batchData, KapzProfile $kapz, ReportingPeriod $period)
    {
        $pdf = Pdf::loadView('pdf.evidencia_apz_batch', [
            'batchData' => $batchData,
            'kapz' => $kapz,
            'period' => $period,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for Travel Plan (Plán pracovných ciest)
     */
    public function generateTravelPlanPdf(TravelPlan $plan, ?int $weekNumber = null)
    {
        $plan->load(['kapz', 'reportingPeriod', 'items.targetApz', 'items.segments', 'reviewedBy']);

        $pdf = Pdf::loadView('pdf.plan_pracovnych_ciest', [
            'plan' => $plan,
            'weeks' => app(TravelSegmentService::class)->weeksFor($plan),
            'selectedWeek' => $weekNumber,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for Travel Order & Settlement (Cestovný príkaz a Vyúčtovanie)
     */
    public function generateTravelOrderPdf(TravelOrder $order)
    {
        $pdf = Pdf::loadView('pdf.cestovny_prikaz', [
            'order' => $order->load(['kapz', 'report', 'expense']),
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for Statement (PREHLÁSENIE)
     */
    public function generateStatementPdf(Statement $statement)
    {
        $pdf = Pdf::loadView('pdf.prehlasenie', [
            'statement' => $statement->load(['kapz', 'reportingPeriod']),
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for Prehlásenie o činnosti mimo pracovného pomeru
     */
    public function generateOutsideActivityDeclarationPdf(\App\Models\OutsideActivityDeclaration $declaration)
    {
        $pdf = Pdf::loadView('pdf.prehlasenie_cinnosti', [
            'declaration' => $declaration->load(['kapz', 'reportingPeriod', 'items']),
        ])->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Generate PDF for Kniha príchodov a odchodov (single person)
     */
    public function generateKnihaPrichodovOdchodovPdf(\App\Models\ArrivalDepartureBook $book)
    {
        $pdf = Pdf::loadView('pdf.kniha_prichodov_odchodov', [
            'books' => [$book->load(['items', 'reportingPeriod', 'kapz'])],
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for all books (KAPZ + All APZs)
     */
    public function generateAllKnihyPdf(array $books)
    {
        $pdf = Pdf::loadView('pdf.kniha_prichodov_odchodov', [
            'books' => $books,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for Activity Report (Správa o pracovnej činnosti)
     */
    public function generateActivityReportPdf(ActivityReport $report)
    {
        $report->load(['kapz', 'reportingPeriod']);

        $pdf = Pdf::loadView('pdf.sprava_o_cinnosti', [
            'report' => $report,
            'limitUsage' => app(TravelOrderService::class)->limitUsage($report->kapz, $report->reportingPeriod),
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Generate PDF for Zoznam kontaktov
     */
    public function generateContactsPdf($contacts)
    {
        // Ensure grouped collection if not already grouped
        $grouped = ($contacts instanceof \Illuminate\Support\Collection && !is_string($contacts->keys()->first() ?? null))
            ? $contacts->groupBy('section')
            : $contacts;

        $pdf = Pdf::loadView('pdf.zoznam_kontaktov', [
            'grouped' => $grouped,
            'contacts' => $contacts,
        ])->setPaper('a4', 'portrait');

        return $pdf;
    }
}
