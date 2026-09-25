<?php

namespace App\Services;

use App\Models\KapzProfile;
use App\Models\MonthlyTravelOrder;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mesačný cestovný príkaz a vyúčtovanie podľa hárkov „Cestovný príkaz“ a „Vyúčtovanie VD“.
 */
class TravelOrderService
{
    public function __construct(private TravelSegmentService $segments)
    {
    }

    public function orderNumber(KapzProfile $kapz, ReportingPeriod $period): string
    {
        // E3 = mesiac & "/" & rok & "/" & iniciály & "/ZK"
        return sprintf('%02d/%d/%s/ZK', $period->month, $period->year, $kapz->initials_code);
    }

    /**
     * Vytvorí (alebo prepíše) CP zo schváleného plánu: prevezme všetky úseky dní daného mesiaca.
     * Hlavičkové údaje sa predvyplnia z profilu KAPZ; už zadané hodnoty CP ostávajú.
     */
    public function generateFromPlan(TravelPlan $plan): MonthlyTravelOrder
    {
        $plan->loadMissing('kapz', 'reportingPeriod', 'items.segments');
        $kapz = $plan->kapz;
        $period = $plan->reportingPeriod;

        return DB::transaction(function () use ($plan, $kapz, $period) {
            $order = MonthlyTravelOrder::firstOrNew([
                'kapz_id' => $kapz->id,
                'reporting_period_id' => $period->id,
            ]);

            $order->fill([
                'travel_plan_id' => $plan->id,
                'order_number' => $this->orderNumber($kapz, $period),
                'status' => $order->status ?? 'DRAFT',
                'residence_address' => $order->residence_address ?? $kapz->residence_address,
                'residence_city' => $order->residence_city ?? $kapz->residence_city,
                'vehicle_plate' => $order->vehicle_plate ?? $kapz->vehicle_plate,
            ])->save();

            $order->segments()->delete();

            $items = $plan->items
                ->filter(fn ($i) => Carbon::parse($i->trip_date)->month === (int) $period->month)
                ->sortBy(fn ($i) => Carbon::parse($i->trip_date)->format('Ymd') . sprintf('%08d', $i->id));

            $position = [];
            foreach ($items as $item) {
                $date = Carbon::parse($item->trip_date)->toDateString();
                foreach ($this->segments->segmentsFor($item) as $seg) {
                    $position[$date] = ($position[$date] ?? 0) + 1;
                    $order->segments()->create([
                        'travel_plan_segment_id' => $seg->exists ? $seg->id : null,
                        'trip_date' => $date,
                        'position' => $position[$date],
                        'from_place' => $seg->from_place,
                        'departure_time' => $seg->departure_time,
                        'to_place' => $seg->to_place,
                        'arrival_time' => $seg->arrival_time,
                        'transport_mode' => $seg->transport_mode,
                        'km' => $seg->km,
                        'purpose' => $seg->purpose,
                    ]);
                }
            }

            if (!$order->vehicle) {
                $modes = $order->segments()->pluck('transport_mode')->unique()->values();
                $order->update(['vehicle' => $modes->implode(', ')]);
            }

            return $order->fresh('segments');
        });
    }

    /**
     * Výpočty ako v Exceli:
     *  - Y (PHM) = spotreba / 100 × km × cena PH (AI83 / 100 × W × AI),
     *  - AD (spolu) = Y + Z + AA + AB + AC; súčty stĺpcov (riadok 269),
     *  - AD271 doplatok/preplatok = spolu – preddavok,
     *  - H466 km vlastným autom, BG helper náhrada AUV = km × sadzba,
     *  - Vyúčtovanie VD!I92 = súčet km všetkých úsekov.
     */
    public function calculate(MonthlyTravelOrder $order): array
    {
        $order->loadMissing('segments');
        $consumption = (float) $order->fuel_consumption;
        $rate = (float) config('kapz.auv_rate_per_km');

        $rows = $order->segments->map(function ($s) use ($consumption) {
            $fuel = ($consumption > 0 && $s->fuel_price) ? round($consumption / 100 * $s->km * $s->fuel_price, 2) : 0.0;
            $total = round($fuel + $s->amortization + $s->meals + $s->accommodation_cost + $s->other_costs, 2);

            return ['segment' => $s, 'fuel_cost' => $fuel, 'total' => $total];
        });

        $sum = fn (string $key) => round($rows->sum($key), 2);
        $sumSeg = fn (string $attr) => round($order->segments->sum($attr), 2);

        $totals = [
            'fuel_cost' => $sum('fuel_cost'),
            'amortization' => $sumSeg('amortization'),
            'meals' => $sumSeg('meals'),
            'accommodation_cost' => $sumSeg('accommodation_cost'),
            'other_costs' => $sumSeg('other_costs'),
            'total' => $sum('total'),
            'adjusted' => $sumSeg('adjusted'),
        ];

        $auvKm = round($order->segments->where('transport_mode', 'AUV')->sum('km'), 2);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'advance' => (float) $order->advance_amount,
            'balance' => round($totals['total'] - (float) $order->advance_amount, 2),
            'total_km' => round($order->segments->sum('km'), 2),
            'auv_km' => $auvKm,
            'auv_rate' => $rate,
            'auv_compensation' => round($auvKm * $rate, 2),
            'days' => $this->days($order->segments),
        ];
    }

    /**
     * Súhrn ciest po dňoch (Cestovný príkaz riadky 14–46):
     * začiatok (miesto, dátum, hodina), miesto konania, účel, koniec (miesto, dátum, hodina).
     */
    public function days(Collection $segments): Collection
    {
        return $segments
            ->groupBy(fn ($s) => Carbon::parse($s->trip_date)->toDateString())
            ->map(function (Collection $day, string $date) {
                $day = $day->sortBy('position')->values();
                $first = $day->first();
                $last = $day->last();
                $places = $day->pluck('to_place')
                    ->reject(fn ($p) => mb_strtolower(trim($p)) === mb_strtolower(trim($first->from_place)))
                    ->unique()
                    ->values();

                return [
                    'date' => Carbon::parse($date),
                    'start_place' => $first->from_place,
                    'start_time' => $first->departure_time,
                    'places' => $places->isNotEmpty() ? $places->implode(', ') : $first->to_place,
                    'purpose' => $day->pluck('purpose')->filter()->first(),
                    'end_place' => $last->to_place,
                    'end_time' => $last->arrival_time,
                ];
            })
            ->values();
    }

    /**
     * Dni cestovného príkazu doplnené o texty správ:
     *  - conclusion = uložený záver, inak „Závery/Odporúčania“ podľa účelu (GENERATOR: IF(E13=Q14,R14,…)),
     *  - report_text = uložený text správy, inak predvolene záver.
     */
    public function reportDays(MonthlyTravelOrder $order): Collection
    {
        $order->loadMissing('segments', 'dayTexts');
        $texts = $order->dayTexts->keyBy(fn ($d) => $d->trip_date->toDateString());
        $conclusions = \App\Models\TravelPurpose::pluck('conclusion', 'title');

        return $this->days($order->segments)->map(function (array $day) use ($texts, $conclusions) {
            $saved = $texts->get($day['date']->toDateString());
            $default = $day['purpose'] ? ($conclusions[$day['purpose']] ?? null) : null;
            $day['default_conclusion'] = $default;
            $day['conclusion'] = $saved?->conclusion ?: $default;
            $day['report_text'] = $saved?->report_text ?: ($saved?->conclusion ?: $default);

            return $day;
        });
    }

    /** Čerpanie mesačného limitu (Správa o pracovnej činnosti H11–H13): limit, najazdené km z CP, zostatok. */
    public function limitUsage(\App\Models\KapzProfile $kapz, \App\Models\ReportingPeriod $period): array
    {
        $limit = app(TravelWorkflowService::class)->getLimitForScope($kapz->scope);
        $order = MonthlyTravelOrder::with('segments')
            ->where('kapz_id', $kapz->id)
            ->where('reporting_period_id', $period->id)
            ->first();
        $driven = $order ? round($order->segments->sum('km'), 2) : 0.0;

        return [
            'limit' => $limit,
            'driven' => $driven,
            'remaining' => round($limit - $driven, 2),
            'order' => $order,
            'days' => $order ? $this->reportDays($order) : collect(),
        ];
    }
}
