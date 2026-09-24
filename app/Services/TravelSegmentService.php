<?php

namespace App\Services;

use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use App\Models\TravelPlanSegment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Úseky ciest (dvojice Odchod/Príchod) podľa hárkov „Plán pracovných ciest“ a „Cestovný príkaz“.
 */
class TravelSegmentService
{
    /** Legenda dopravných prostriedkov (Cestovný príkaz R276–U279). */
    public const TRANSPORT_MODES = [
        'AUV' => 'AUV – auto vlastné',
        'AUS' => 'AUS – auto služobné',
        'AUVS' => 'AUVS – auto vlastné, ako spolucestujúci',
        'AUSS' => 'AUSS – auto služobné, ako spolucestujúci',
        'O' => 'O – osobný vlak',
        'R' => 'R – rýchlik',
        'A' => 'A – autobus',
        'P' => 'P – pešo',
    ];

    /** Staršie kódy aplikácie → kódy matice. */
    private const LEGACY_TRANSPORT = ['AAuto' => 'AUS', 'VHD' => 'A'];

    public static function normalizeTransport(?string $code): string
    {
        $code = self::LEGACY_TRANSPORT[$code] ?? $code;

        return array_key_exists((string) $code, self::TRANSPORT_MODES) ? $code : 'AUV';
    }

    /**
     * Úseky položky: uložené, alebo (pri starších položkách bez úsekov) odvodené
     * zo súhrnných polí – tam, prípadne okruh cez viac zastávok, a späť.
     */
    public function segmentsFor(TravelPlanItem $item): Collection
    {
        $stored = $item->relationLoaded('segments') ? $item->segments : $item->segments()->get();

        return $stored->isNotEmpty() ? $stored->sortBy('position')->values() : $this->deriveSegments($item);
    }

    public function deriveSegments(TravelPlanItem $item): Collection
    {
        $stops = array_values(array_filter(array_map('trim', preg_split('/[,;+]+/', (string) $item->destination_location))));
        if (!$stops || !$item->departure_location) {
            return collect();
        }

        $base = $item->departure_location;
        $chain = array_merge([$base], $stops, [$base]);
        $count = count($chain) - 1;
        $totalKm = (float) $item->estimated_km;
        $firstKm = $count === 2 ? round($totalKm / 2, 2) : $totalKm;

        $segments = collect();
        for ($i = 0; $i < $count; $i++) {
            $isFirst = $i === 0;
            $isLast = $i === $count - 1;
            $segments->push(new TravelPlanSegment([
                'position' => $i + 1,
                'from_place' => $chain[$i],
                'departure_time' => $isFirst ? $item->departure_time : ($isLast ? $item->departure_from_dest_time : null),
                'to_place' => $chain[$i + 1],
                'arrival_time' => $isFirst ? $item->arrival_at_dest_time : ($isLast ? $item->arrival_time : null),
                'transport_mode' => self::normalizeTransport($item->transport_mode),
                'km' => $isFirst ? $firstKm : ($count === 2 ? round($totalKm - $firstKm, 2) : 0.0),
                'purpose' => $isFirst ? $item->purpose : null,
                'accommodation' => 'Nie',
            ]));
        }

        return $segments;
    }

    /** Uloží odvodené úseky k položke (po pridaní/úprave cez starší formulár so 4 časmi). */
    public function rebuildSegments(TravelPlanItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->segments()->delete();
            foreach ($this->deriveSegments($item) as $segment) {
                $item->segments()->save($segment);
            }
        });
    }

    /**
     * Uloží celý deň z editora v tvare matice: úseky nahradia všetky cesty daného dňa.
     * Deň bez úsekov s poznámkou (napr. „Administratíva“) sa uloží ako položka bez úsekov.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function saveDay(TravelPlan $plan, string $date, array $rows, ?string $dayNote = null): ?TravelPlanItem
    {
        $rows = array_values(array_filter($rows, fn ($r) => trim((string) ($r['from_place'] ?? '')) !== '' || trim((string) ($r['to_place'] ?? '')) !== ''));
        $dayNote = trim((string) $dayNote) ?: null;

        return DB::transaction(function () use ($plan, $date, $rows, $dayNote) {
            $plan->items()->whereDate('trip_date', $date)->get()->each->delete();

            if (!$rows && !$dayNote) {
                return null;
            }

            $item = $plan->items()->create([
                'week_number' => $this->weekIndex($plan, $date),
                'trip_date' => $date,
                'departure_location' => $rows[0]['from_place'] ?? '',
                'destination_location' => '',
                'purpose' => '',
                'transport_mode' => 'AUV',
                'estimated_km' => 0,
                'notes' => $dayNote,
            ]);

            foreach ($rows as $i => $row) {
                $item->segments()->create([
                    'position' => $i + 1,
                    'from_place' => trim((string) $row['from_place']),
                    'departure_time' => $this->time($row['departure_time'] ?? null),
                    'to_place' => trim((string) ($row['to_place'] ?? '')),
                    'arrival_time' => $this->time($row['arrival_time'] ?? null),
                    'transport_mode' => self::normalizeTransport($row['transport_mode'] ?? 'AUV'),
                    'km' => (float) str_replace(',', '.', (string) ($row['km'] ?? 0)),
                    'purpose' => trim((string) ($row['purpose'] ?? '')) ?: null,
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                    'accommodation' => trim((string) ($row['accommodation'] ?? '')) ?: 'Nie',
                    'companions' => trim((string) ($row['companions'] ?? '')) ?: 'Nie',
                ]);
            }

            $this->syncItemFromSegments($item);

            return $item;
        });
    }

    /**
     * Súhrnné polia položky (používané prehľadmi, dashboardom a starším formulárom)
     * sa odvodia z úsekov: východisko, zastávky, 4 časy, účel, doprava, km.
     */
    public function syncItemFromSegments(TravelPlanItem $item): void
    {
        $segments = $item->segments()->orderBy('position')->get();
        if ($segments->isEmpty()) {
            return;
        }

        $first = $segments->first();
        $last = $segments->last();
        $stops = $segments->pluck('to_place')->filter(fn ($p) => $p !== $first->from_place)->unique()->values();

        $item->update([
            'departure_location' => $first->from_place,
            'destination_location' => $stops->implode(', ') ?: $first->to_place,
            'departure_time' => $first->departure_time ?: '',
            'arrival_at_dest_time' => $first->arrival_time,
            'departure_from_dest_time' => $segments->count() > 1 ? $last->departure_time : null,
            'arrival_time' => $last->arrival_time ?: '',
            'purpose' => (string) ($segments->pluck('purpose')->filter()->first() ?? ''),
            'transport_mode' => $first->transport_mode,
            'estimated_km' => round($segments->sum('km'), 2),
        ]);
    }

    /** Poradie týždňa (1–5) v mesiaci plánu podľa kalendárneho týždňa dátumu. */
    public function weekIndex(TravelPlan $plan, string $date): int
    {
        $period = $plan->reportingPeriod;
        $firstMonday = Carbon::create($period->year, $period->month, 1)->startOfWeek();
        $index = intdiv($firstMonday->diffInDays(Carbon::parse($date)->startOfDay(), false), 7) + 1;

        return max(1, min(5, $index));
    }

    /** Dni (po–pi, prípadne aj víkend s cestou) kalendárneho týždňa v rámci plánu. */
    public function weekDates(TravelPlan $plan, int $week): array
    {
        $period = $plan->reportingPeriod;
        $start = Carbon::create($period->year, $period->month, 1)->startOfWeek()->addWeeks($week - 1);

        return collect(range(0, 6))->map(fn ($d) => $start->copy()->addDays($d))->all();
    }

    /**
     * Dni po–pi (a víkend, ak je naň cesta) pre každý z 5 týždňových blokov plánu.
     * Deň nesie úseky zo všetkých položiek toho dňa, poznámku dňa a absenciu z dochádzky.
     */
    public function weeksFor(TravelPlan $plan): array
    {
        $plan->loadMissing('items.segments', 'reportingPeriod');
        $period = $plan->reportingPeriod;
        $itemsByDate = $plan->items->groupBy(fn ($i) => \Carbon\Carbon::parse($i->trip_date)->toDateString());

        $rangeStart = \Carbon\Carbon::create($period->year, $period->month, 1)->startOfWeek();
        $absences = \App\Models\AttendanceKapz::where('kapz_id', $plan->kapz_id)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeStart->copy()->addWeeks(5)->toDateString()])
            ->get()
            ->mapWithKeys(fn ($a) => [\Carbon\Carbon::parse($a->date)->toDateString() => \App\Enums\AttendanceStatus::fromCode($a->status)->unworkedText()]);

        $weeks = [];
        for ($w = 1; $w <= 5; $w++) {
            $days = [];
            foreach ($this->weekDates($plan, $w) as $date) {
                $key = $date->toDateString();
                $items = $itemsByDate->get($key, collect())->sortBy('id');
                if ($date->isWeekend() && $items->isEmpty()) {
                    continue;
                }
                $days[] = [
                    'date' => $date,
                    'in_month' => $date->month === (int) $period->month,
                    'segments' => $items->flatMap(fn ($i) => $this->segmentsFor($i))->values(),
                    'note' => $items->pluck('notes')->filter()->implode('; '),
                    'absence' => $absences[$key] ?? null,
                ];
            }

            $weeks[$w] = [
                'week_number' => $w,
                'calendar_week' => $plan->getCalendarWeek($w),
                'days' => $days,
                'total_km' => collect($days)->sum(fn ($d) => $d['segments']->sum('km')),
            ];
        }

        return $weeks;
    }

    private function time(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2})[:.,](\d{2})$/', $value, $m)) {
            return sprintf('%d:%02d', $m[1], $m[2]);
        }
        if (preg_match('/^\d{1,2}$/', $value)) {
            return sprintf('%d:00', $value);
        }

        return $value;
    }
}
