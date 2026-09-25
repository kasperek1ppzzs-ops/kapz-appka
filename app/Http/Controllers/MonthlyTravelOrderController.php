<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTravelOrder;
use App\Models\ReportingPeriod;
use App\Models\TravelOrder;
use App\Models\TravelPlan;
use App\Services\TravelOrderService;
use App\Services\TravelSegmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Mesačný cestovný príkaz a vyúčtovanie (hárky „Cestovný príkaz“ a „Vyúčtovanie VD“).
 */
class MonthlyTravelOrderController extends Controller
{
    public function index(Request $request, TravelOrderService $service)
    {
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        $period = ReportingPeriod::findOrFail($request->input('period_id', $periods->first()?->id));
        $kapz = $this->resolveKapz($request);

        $plan = TravelPlan::where('kapz_id', $kapz->id)->where('reporting_period_id', $period->id)->first();
        $order = MonthlyTravelOrder::with('segments')
            ->where('kapz_id', $kapz->id)
            ->where('reporting_period_id', $period->id)
            ->first();
        $calc = $order ? $service->calculate($order) : null;

        $legacyOrders = TravelOrder::where('kapz_id', $kapz->id)->orderByDesc('id')->get();
        $transportModes = TravelSegmentService::TRANSPORT_MODES;

        return view('travel.cp', compact('periods', 'period', 'kapz', 'plan', 'order', 'calc', 'legacyOrders', 'transportModes'));
    }

    public function generate(Request $request, TravelOrderService $service)
    {
        $plan = TravelPlan::with('reportingPeriod')->findOrFail($request->input('plan_id'));
        $this->authorizeKapzAccess($plan->kapz_id);

        if ($plan->status !== 'APPROVED') {
            return back()->with('error', 'Cestovný príkaz sa vytvára zo schváleného plánu pracovných ciest.');
        }

        $existing = MonthlyTravelOrder::where('kapz_id', $plan->kapz_id)
            ->where('reporting_period_id', $plan->reporting_period_id)->first();
        if ($existing && $existing->status !== 'DRAFT') {
            return back()->with('error', 'Uzavretý cestovný príkaz nie je možné prepísať z plánu.');
        }

        $order = $service->generateFromPlan($plan);

        return redirect()
            ->route('travel.cp.index', ['period_id' => $plan->reporting_period_id, 'kapz_id' => $plan->kapz_id])
            ->with('success', "Cestovný príkaz č. {$order->order_number} bol pripravený zo schváleného plánu. Doplňte skutočné časy a výdavky.");
    }

    public function update(Request $request, MonthlyTravelOrder $order)
    {
        $this->authorizeKapzAccess($order->kapz_id);

        if ($order->status !== 'DRAFT') {
            return back()->with('error', 'Uzavretý cestovný príkaz nie je možné upravovať.');
        }

        $data = $request->validate([
            'residence_address' => ['nullable', 'string', 'max:255'],
            'residence_city' => ['nullable', 'string', 'max:255'],
            'companions' => ['nullable', 'string', 'max:255'],
            'vehicle' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'expected_costs' => ['nullable', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'fuel_consumption' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'report_submitted_on' => ['nullable', 'date'],
            'meals_free' => ['nullable', 'in:0,1'],
            'accommodation_free' => ['nullable', 'in:0,1'],
            'discounted_ticket' => ['nullable', 'in:0,1'],
            'note' => ['nullable', 'string', 'max:2000'],
            'segments' => ['nullable', 'array'],
            'segments.*.departure_time' => ['nullable', 'string', 'max:10'],
            'segments.*.arrival_time' => ['nullable', 'string', 'max:10'],
            'segments.*.from_place' => ['required_with:segments', 'string', 'max:255'],
            'segments.*.to_place' => ['required_with:segments', 'string', 'max:255'],
            'segments.*.transport_mode' => ['nullable', 'in:' . implode(',', array_keys(TravelSegmentService::TRANSPORT_MODES))],
            'segments.*.km' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'segments.*.work_time' => ['nullable', 'string', 'max:30'],
            'segments.*.fuel_price' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'segments.*.amortization' => ['nullable', 'numeric', 'min:0'],
            'segments.*.meals' => ['nullable', 'numeric', 'min:0'],
            'segments.*.accommodation_cost' => ['nullable', 'numeric', 'min:0'],
            'segments.*.other_costs' => ['nullable', 'numeric', 'min:0'],
            'segments.*.adjusted' => ['nullable', 'numeric'],
        ]);

        $header = collect($data)->except('segments')->all();
        foreach (['meals_free', 'accommodation_free', 'discounted_ticket'] as $flag) {
            $header[$flag] = isset($header[$flag]) && $header[$flag] !== '' ? (bool) $header[$flag] : null;
        }
        $header['advance_amount'] = (float) ($header['advance_amount'] ?? 0);
        $order->update($header);

        foreach ($data['segments'] ?? [] as $id => $row) {
            $segment = $order->segments()->whereKey($id)->first();
            if (!$segment) {
                continue;
            }
            $segment->update([
                'from_place' => $row['from_place'],
                'departure_time' => $row['departure_time'] ?? null,
                'to_place' => $row['to_place'],
                'arrival_time' => $row['arrival_time'] ?? null,
                'transport_mode' => $row['transport_mode'] ?? $segment->transport_mode,
                'km' => (float) ($row['km'] ?? 0),
                'work_time' => $row['work_time'] ?? null,
                'fuel_price' => ($row['fuel_price'] ?? '') === '' ? null : (float) $row['fuel_price'],
                'amortization' => (float) ($row['amortization'] ?? 0),
                'meals' => (float) ($row['meals'] ?? 0),
                'accommodation_cost' => (float) ($row['accommodation_cost'] ?? 0),
                'other_costs' => (float) ($row['other_costs'] ?? 0),
                'adjusted' => ($row['adjusted'] ?? '') === '' ? null : (float) $row['adjusted'],
            ]);
        }

        return back()->with('success', 'Cestovný príkaz bol uložený.');
    }

    public function toggleStatus(MonthlyTravelOrder $order)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->update(['status' => $order->status === 'DRAFT' ? 'COMPLETED' : 'DRAFT']);

        return back()->with('success', $order->status === 'COMPLETED'
            ? 'Cestovný príkaz bol uzavretý (vyúčtovaný).'
            : 'Cestovný príkaz bol znovu otvorený na úpravy.');
    }

    public function pdf(MonthlyTravelOrder $order, TravelOrderService $service)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->load('kapz', 'reportingPeriod', 'segments');

        return Pdf::loadView('pdf.cestovny_prikaz_mesacny', ['order' => $order, 'calc' => $service->calculate($order)])
            ->setPaper('a4', 'portrait')
            ->download('CESTOVNY_PRIKAZ_' . str_replace('/', '_', $order->order_number) . '.pdf');
    }

    public function settlementPdf(MonthlyTravelOrder $order, TravelOrderService $service)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->load('kapz', 'reportingPeriod', 'segments');

        return Pdf::loadView('pdf.vyuctovanie_vd', ['order' => $order, 'calc' => $service->calculate($order)])
            ->setPaper('a4', 'portrait')
            ->download('VYUCTOVANIE_VD_' . str_replace('/', '_', $order->order_number) . '.pdf');
    }

    /** Správy z pracovných ciest po dňoch (hárky „Správa z pracovnej cesty“ a GENERATOR). */
    public function reports(MonthlyTravelOrder $order, TravelOrderService $service)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->load('kapz', 'reportingPeriod');

        return view('travel.cp_reports', ['order' => $order, 'days' => $service->reportDays($order)]);
    }

    public function saveReports(Request $request, MonthlyTravelOrder $order)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        if ($order->status !== 'DRAFT') {
            return back()->with('error', 'Uzavretý cestovný príkaz nie je možné upravovať.');
        }

        $data = $request->validate([
            'days' => ['required', 'array'],
            'days.*.report_text' => ['nullable', 'string', 'max:5000'],
            'days.*.conclusion' => ['nullable', 'string', 'max:2000'],
        ]);

        $validDates = $order->segments()->pluck('trip_date')->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())->unique();
        foreach ($data['days'] as $date => $texts) {
            if (!$validDates->contains($date)) {
                continue;
            }
            $order->dayTexts()->updateOrCreate(
                ['trip_date' => $date],
                ['report_text' => $texts['report_text'] ?? null, 'conclusion' => $texts['conclusion'] ?? null]
            );
        }

        return back()->with('success', 'Správy z pracovných ciest boli uložené.');
    }

    public function reportsPdf(MonthlyTravelOrder $order, TravelOrderService $service)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->load('kapz', 'reportingPeriod');

        return Pdf::loadView('pdf.sprava_z_pracovnej_cesty', ['order' => $order, 'days' => $service->reportDays($order)])
            ->setPaper('a4', 'portrait')
            ->download('SPRAVY_Z_PRACOVNYCH_CIEST_' . str_replace('/', '_', $order->order_number) . '.pdf');
    }

    public function generatorPdf(MonthlyTravelOrder $order, TravelOrderService $service)
    {
        $this->authorizeKapzAccess($order->kapz_id);
        $order->load('kapz', 'reportingPeriod');

        return Pdf::loadView('pdf.generator_sprava', ['order' => $order, 'days' => $service->reportDays($order)])
            ->setPaper('a4', 'portrait')
            ->download('SPRAVA_ZO_SLUZOBNEJ_CESTY_' . str_replace('/', '_', $order->order_number) . '.pdf');
    }
}
