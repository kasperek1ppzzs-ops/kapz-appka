<?php

namespace App\Http\Controllers;

use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use App\Models\ReportingPeriod;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Services\TravelWorkflowService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TravelPlanController extends Controller
{
    public function index(Request $request, TravelWorkflowService $workflowService)
    {
        $user = Auth::user();
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        $periodId = $request->input('period_id', $periods->first()->id ?? 1);
        $period = ReportingPeriod::findOrFail($periodId);

        // 1. Role: Admin / Expert pre terén
        if ($user->isSupervisor()) {
            $plans = TravelPlan::with(['kapz.user', 'items', 'reportingPeriod', 'reviewedBy'])
                ->where('reporting_period_id', $period->id)
                ->get();
            return view('travel.admin_index', compact('plans', 'period', 'periods'));
        }

        // 2. Role: KAPZ Koordinátor
        $kapz = $user->kapzProfile;
        abort_if(!$kapz, 403, 'K účtu nie je priradený profil KAPZ.');

        $limit = $workflowService->getLimitForScope($kapz->scope);

        // Find or create plan for this period
        $plan = TravelPlan::firstOrCreate(
            [
                'kapz_id' => $kapz->id,
                'reporting_period_id' => $period->id,
            ],
            [
                'title' => "Mesačný plán pracovných ciest - {$period->formatted_name}",
                'status' => 'DRAFT',
                'km_limit' => $limit,
                'version' => 1,
            ]
        );

        if ($plan->km_limit <= 0) {
            $plan->update(['km_limit' => $limit]);
        }

        $plan->load('items.targetApz');

        // Retrieve assigned APZs & communities for 1-click selection
        $firstDayOfMonth = "{$period->year}-" . str_pad($period->month, 2, '0', STR_PAD_LEFT) . "-01";
        $assignedApzs = $kapz->assignedApzsForDate($firstDayOfMonth);
        if ($assignedApzs->isEmpty()) {
            $assignedApzs = ApzProfile::whereIn('id', $kapz->assignments()->pluck('apz_id'))->get();
        }
        if ($assignedApzs->isEmpty()) {
            $assignedApzs = ApzProfile::take(10)->get();
        }

        $assignedVillages = $assignedApzs->pluck('scope')->filter()->unique()->values()->all();

        // Common destinations datalist
        $commonDestinations = collect([
            $kapz->scope,
            'Banská Bystrica',
            'Zvolen',
            'Brezno',
            'Levoča',
            'Prešov',
            'Poprad',
            'Košice',
            'Bratislava - Ústredie ZR',
            'Regionálny úrad verejného zdravotníctva (RÚVZ)',
            'Nemocnica s poliklinikou',
            'Úrad práce, sociálnych vecí a rodiny (ÚPSVaR)',
        ])->merge($assignedVillages)->filter()->unique()->values()->all();

        // Weeks breakdown (1 to 5)
        $weeksData = [];
        for ($w = 1; $w <= 5; $w++) {
            $calWeek = $plan->getCalendarWeek($w);
            $items = $plan->items->where('week_number', $w)->sortBy('trip_date');
            $weeksData[$w] = [
                'week_number' => $w,
                'calendar_week' => $calWeek,
                'items' => $items,
                'total_km' => $items->sum('estimated_km'),
            ];
        }

        $totalKm = $plan->total_km;
        $kmLimit = (float) $plan->km_limit;
        $kmRemaining = max(0, $kmLimit - $totalKm);
        $kmPercentage = $kmLimit > 0 ? min(100, round(($totalKm / $kmLimit) * 100)) : 0;
        $isOverLimit = $totalKm > $kmLimit;

        $officialPurposes = $workflowService->getOfficialPurposes();
        $transportModes = [
            'AUV' => 'AUV (vlastné motorové vozidlo)',
            'AAuto' => 'AAuto (služobné motorové vozidlo)',
            'VHD' => 'VHD (verejná hromadná doprava)',
        ];

        return view('travel.index', compact(
            'plan',
            'kapz',
            'period',
            'periods',
            'assignedApzs',
            'assignedVillages',
            'commonDestinations',
            'weeksData',
            'totalKm',
            'kmLimit',
            'kmRemaining',
            'kmPercentage',
            'isOverLimit',
            'officialPurposes',
            'transportModes'
        ));
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'travel_plan_id' => ['required', 'exists:travel_plans,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:5'],
            'trip_date' => ['required', 'date'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'arrival_at_dest_time' => ['nullable', 'string', 'max:10'],
            'departure_from_dest_time' => ['nullable', 'string', 'max:10'],
            'arrival_time' => ['nullable', 'string', 'max:10'],
            'departure_location' => ['required', 'string', 'max:255'],
            'destination_location' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'transport_mode' => ['required', 'string', 'max:50'],
            'estimated_km' => ['required', 'numeric', 'min:0'],
            'target_apz_id' => ['nullable', 'exists:apz_profiles,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = TravelPlan::findOrFail($request->travel_plan_id);
        $this->authorizeKapzAccess($plan->kapz_id);

        if (in_array($plan->status, ['SUBMITTED', 'APPROVED'])) {
            return back()->with('error', 'Schválený alebo odoslaný plán nie je možné upravovať.');
        }

        TravelPlanItem::create([
            'travel_plan_id' => $plan->id,
            'week_number' => $request->week_number,
            'trip_date' => $request->trip_date,
            'departure_time' => $request->departure_time ?: '08:00',
            'arrival_at_dest_time' => $request->arrival_at_dest_time ?: null,
            'departure_from_dest_time' => $request->departure_from_dest_time ?: null,
            'arrival_time' => $request->arrival_time ?: '16:00',
            'departure_location' => $request->departure_location,
            'destination_location' => $request->destination_location,
            'purpose' => $request->purpose,
            'transport_mode' => $request->transport_mode,
            'target_apz_id' => $request->target_apz_id ?: null,
            'estimated_km' => $request->estimated_km,
            'notes' => $request->notes,
        ]);

        return back()->with('success', "Pracovná cesta do {$request->destination_location} bola pridaná do {$request->week_number}. týždňa.");
    }

    public function updateItem(Request $request, TravelPlanItem $item)
    {
        $plan = $item->travelPlan;
        $this->authorizeKapzAccess($plan->kapz_id);
        if (in_array($plan->status, ['SUBMITTED', 'APPROVED'])) {
            return back()->with('error', 'Schválený alebo odoslaný plán nie je možné upravovať.');
        }

        $request->validate([
            'week_number' => ['required', 'integer', 'min:1', 'max:5'],
            'trip_date' => ['required', 'date'],
            'departure_time' => ['nullable', 'string', 'max:10'],
            'arrival_at_dest_time' => ['nullable', 'string', 'max:10'],
            'departure_from_dest_time' => ['nullable', 'string', 'max:10'],
            'arrival_time' => ['nullable', 'string', 'max:10'],
            'departure_location' => ['required', 'string', 'max:255'],
            'destination_location' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'transport_mode' => ['required', 'string', 'max:50'],
            'estimated_km' => ['required', 'numeric', 'min:0'],
            'target_apz_id' => ['nullable', 'exists:apz_profiles,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $item->update([
            'week_number' => $request->week_number,
            'trip_date' => $request->trip_date,
            'departure_time' => $request->departure_time ?: '08:00',
            'arrival_at_dest_time' => $request->arrival_at_dest_time ?: null,
            'departure_from_dest_time' => $request->departure_from_dest_time ?: null,
            'arrival_time' => $request->arrival_time ?: '16:00',
            'departure_location' => $request->departure_location,
            'destination_location' => $request->destination_location,
            'purpose' => $request->purpose,
            'transport_mode' => $request->transport_mode,
            'target_apz_id' => $request->target_apz_id ?: null,
            'estimated_km' => $request->estimated_km,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Pracovná cesta bola úspešne upravená.');
    }

    public function deleteItem(TravelPlanItem $item)
    {
        $plan = $item->travelPlan;
        $this->authorizeKapzAccess($plan->kapz_id);
        if (in_array($plan->status, ['SUBMITTED', 'APPROVED'])) {
            return back()->with('error', 'Schválený alebo odoslaný plán nie je možné upravovať.');
        }

        $dest = $item->destination_location;
        $item->delete();

        return back()->with('success', "Pracovná cesta do {$dest} bola odstránená z plánu.");
    }

    public function submit(Request $request, TravelWorkflowService $workflowService)
    {
        $plan = TravelPlan::findOrFail($request->plan_id);
        $this->authorizeKapzAccess($plan->kapz_id);

        if ($plan->items()->count() === 0) {
            return back()->with('error', 'Pred odoslaním na schválenie musíte naplánovať aspoň jednu pracovnú cestu.');
        }

        if ($workflowService->submitPlan($plan)) {
            return back()->with('success', 'Plán pracovných ciest bol úspešne odoslaný na schválenie Expertovi pre terén.');
        }

        return back()->with('error', 'Plán nie je v stave vhodnom na odoslanie.');
    }

    public function approve(Request $request, TravelWorkflowService $workflowService)
    {
        $plan = TravelPlan::findOrFail($request->plan_id);
        if ($workflowService->approvePlan($plan, $request->admin_notes)) {
            return back()->with('success', "Plán pracovných ciest pre {$plan->kapz->full_name} bol schválený v rámci ZFK.");
        }
        return back()->with('error', 'Schválenie plánu zlyhalo.');
    }

    public function returnPlan(Request $request, TravelWorkflowService $workflowService)
    {
        $request->validate([
            'admin_notes' => ['required', 'string', 'min:3'],
        ], [
            'admin_notes.required' => 'Pri vrátení plánu je povinné uviesť dôvod / pokyny na prepracovanie.',
        ]);

        $plan = TravelPlan::findOrFail($request->plan_id);
        if ($workflowService->returnPlan($plan, $request->admin_notes)) {
            return back()->with('success', "Plán bol vrátený koordinátorovi {$plan->kapz->full_name} na doplnenie.");
        }
        return back()->with('error', 'Vrátenie plánu zlyhalo.');
    }

    public function downloadPdf(Request $request, TravelPlan $plan, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeKapzAccess($plan->kapz_id);
        $week = $request->query('week') ? (int) $request->query('week') : null;
        $pdf = $pdfGenerator->generateTravelPlanPdf($plan, $week);
        $orderNumber = str_replace('/', '_', $plan->order_number);
        $suffix = $week ? "_{$week}_TYZDEN" : "_KOMPLET";
        return $pdf->download("PLAN_PRACOVNYCH_CIEST_{$orderNumber}{$suffix}.pdf");
    }

    public function calculateDistance(Request $request, \App\Services\DistanceCalculationService $distanceService)
    {
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $data = $distanceService->getDistance($from, $to);
        return response()->json($data);
    }
}
