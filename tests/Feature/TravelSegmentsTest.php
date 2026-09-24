<?php

namespace Tests\Feature;

use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use App\Models\User;
use App\Services\TravelSegmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Úseky ciest ako dvojice Odchod/Príchod v hárku „Plán pracovných ciest“ (08/2026, riadky 72–77).
 */
class TravelSegmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TravelPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'novak@kapz.sk')->first();
        $period = ReportingPeriod::where('year', 2026)->where('month', 8)->first();
        $this->plan = TravelPlan::where('kapz_id', $this->user->kapzProfile->id)
            ->where('reporting_period_id', $period->id)->first();
        $this->plan->update(['status' => 'DRAFT']);
    }

    private function excelDay(): array
    {
        return [
            'date' => '2026-08-03',
            'segments' => [
                ['from_place' => 'Snina', 'departure_time' => '8:30', 'to_place' => 'Ubľa', 'arrival_time' => '9:00',
                 'transport_mode' => 'AUV', 'km' => 27.1, 'purpose' => 'Kontrolná, podporná a hodnotiaca činnosť APZ',
                 'description' => 'Ubľa - prvá bežná kontrolná, podporná a hodnotiaca činnosť APZ', 'accommodation' => 'Nie', 'companions' => 'Nie'],
                ['from_place' => 'Ubľa', 'departure_time' => '11:00', 'to_place' => 'Stakčín', 'arrival_time' => '11:30',
                 'transport_mode' => 'AUV', 'km' => 20.5, 'purpose' => 'Kontrolná, podporná a hodnotiaca činnosť APZ'],
                ['from_place' => 'Stakčín', 'departure_time' => '13.30', 'to_place' => 'Snina', 'arrival_time' => '14:00',
                 'transport_mode' => 'AUV', 'km' => 7],
            ],
        ];
    }

    public function test_day_is_saved_as_chain_of_segments_like_excel(): void
    {
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", $this->excelDay())->assertRedirect();

        $items = $this->plan->items()->whereDate('trip_date', '2026-08-03')->get();
        $this->assertCount(1, $items);
        $item = $items->first();
        $segments = $item->segments;

        $this->assertCount(3, $segments);
        $this->assertSame(['Snina', 'Ubľa', 'Stakčín'], $segments->pluck('from_place')->all());
        $this->assertSame(['Ubľa', 'Stakčín', 'Snina'], $segments->pluck('to_place')->all());
        $this->assertSame('13:30', $segments[2]->departure_time); // 13.30 → 13:30

        // Súhrn položky pre prehľady (CP riadok 14: Snina / Ubľa, Stakčín / Snina)
        $this->assertSame('Snina', $item->departure_location);
        $this->assertSame('Ubľa, Stakčín', $item->destination_location);
        $this->assertSame('8:30', $item->departure_time);
        $this->assertSame('14:00', $item->arrival_time);
        $this->assertEquals(54.6, (float) $item->estimated_km);
        $this->assertSame(2, (int) $item->week_number); // 3. 8. 2026 = 2. blok plánu (32. kal. týždeň), 1. blok = 27. 7.–2. 8.
    }

    public function test_saving_day_again_replaces_previous_trips_of_that_day(): void
    {
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", $this->excelDay());
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", [
            'date' => '2026-08-03',
            'segments' => [],
            'day_note' => 'Administratíva',
        ])->assertRedirect();

        $items = $this->plan->items()->whereDate('trip_date', '2026-08-03')->get();
        $this->assertCount(1, $items);
        $this->assertSame('Administratíva', $items->first()->notes);
        $this->assertCount(0, $items->first()->segments);
    }

    public function test_legacy_item_with_four_times_gets_there_and_back_segments(): void
    {
        $item = TravelPlanItem::create([
            'travel_plan_id' => $this->plan->id,
            'week_number' => 1,
            'trip_date' => '2026-08-05',
            'departure_time' => '8:00',
            'arrival_at_dest_time' => '8:45',
            'departure_from_dest_time' => '14:05',
            'arrival_time' => '15:00',
            'departure_location' => 'Snina',
            'destination_location' => 'Kučín',
            'purpose' => 'Odborné riadenie koordinačných stretnutí APZ',
            'transport_mode' => 'AAuto',
            'estimated_km' => 81,
        ]);

        $segments = (new TravelSegmentService())->segmentsFor($item);
        $this->assertCount(2, $segments);
        $this->assertSame(['Snina', 'Kučín'], $segments->pluck('from_place')->all());
        $this->assertSame(['8:00', '14:05'], $segments->pluck('departure_time')->all());
        $this->assertSame(['8:45', '15:00'], $segments->pluck('arrival_time')->all());
        $this->assertEquals([40.5, 40.5], $segments->pluck('km')->all());
        $this->assertSame('AUS', $segments[0]->transport_mode); // AAuto → AUS
    }

    public function test_incomplete_segment_and_locked_or_foreign_plan_are_rejected(): void
    {
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", [
            'date' => '2026-08-03',
            'segments' => [['from_place' => 'Snina', 'to_place' => '']],
        ])->assertSessionHas('error');
        $this->assertSame(0, $this->plan->items()->whereDate('trip_date', '2026-08-03')->count());

        $this->plan->update(['status' => 'APPROVED']);
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", $this->excelDay())->assertSessionHas('error');

        $other = User::where('email', 'horvathova@kapz.sk')->first();
        $this->actingAs($other)->post("/travel/{$this->plan->id}/day", $this->excelDay())->assertForbidden();
    }
}
