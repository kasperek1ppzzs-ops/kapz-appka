<?php

namespace Tests\Feature;

use App\Models\MonthlyTravelOrder;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\User;
use App\Services\TravelOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plán → Cestovný príkaz → Vyúčtovanie (hárky 15, 16, 17 matice).
 */
class MonthlyTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TravelPlan $plan;
    private ReportingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'novak@kapz.sk')->first();
        $this->period = ReportingPeriod::where('year', 2026)->where('month', 8)->first();
        $this->plan = TravelPlan::where('kapz_id', $this->user->kapzProfile->id)
            ->where('reporting_period_id', $this->period->id)->first();
        $this->plan->items()->delete();
        $this->plan->update(['status' => 'DRAFT']);

        // Deň z vyplnenej matice (Plán riadky 72–77): Snina → Ubľa → Stakčín → Snina
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", [
            'date' => '2026-08-03',
            'segments' => [
                ['from_place' => 'Snina', 'departure_time' => '8:30', 'to_place' => 'Ubľa', 'arrival_time' => '9:00', 'transport_mode' => 'AUV', 'km' => 27.1, 'purpose' => 'Kontrolná, podporná a hodnotiaca činnosť APZ'],
                ['from_place' => 'Ubľa', 'departure_time' => '11:00', 'to_place' => 'Stakčín', 'arrival_time' => '11:30', 'transport_mode' => 'AUV', 'km' => 20.5],
                ['from_place' => 'Stakčín', 'departure_time' => '13:30', 'to_place' => 'Snina', 'arrival_time' => '14:00', 'transport_mode' => 'AUV', 'km' => 7],
            ],
        ]);
        // Deň mimo mesiaca (31. 7. v 1. týždňovom bloku) sa do augustového CP nedostane
        $this->actingAs($this->user)->post("/travel/{$this->plan->id}/day", [
            'date' => '2026-07-31',
            'segments' => [['from_place' => 'Snina', 'to_place' => 'Kučín', 'km' => 40.5]],
        ]);
    }

    private function approveAndGenerate(): MonthlyTravelOrder
    {
        $this->plan->update(['status' => 'APPROVED']);
        $this->actingAs($this->user)->post('/travel/cp/generate', ['plan_id' => $this->plan->id])->assertRedirect();

        return MonthlyTravelOrder::where('kapz_id', $this->user->kapzProfile->id)->firstOrFail();
    }

    public function test_order_requires_approved_plan(): void
    {
        $this->actingAs($this->user)->post('/travel/cp/generate', ['plan_id' => $this->plan->id])->assertSessionHas('error');
        $this->assertSame(0, MonthlyTravelOrder::count());
    }

    public function test_order_is_generated_from_plan_segments_like_excel(): void
    {
        $order = $this->approveAndGenerate();

        $this->assertSame('08/2026/PN/ZK', $order->order_number);   // MM/RRRR/INICIÁLY/ZK
        $this->assertCount(3, $order->segments);                      // 31. 7. nepatrí do augusta
        $this->assertSame(['Snina', 'Ubľa', 'Stakčín'], $order->segments->pluck('from_place')->all());

        $calc = app(TravelOrderService::class)->calculate($order);
        $day = $calc['days']->first();                                // CP riadok 14
        $this->assertSame('Snina', $day['start_place']);
        $this->assertSame('8:30', $day['start_time']);
        $this->assertSame('Ubľa, Stakčín', $day['places']);
        $this->assertSame('Kontrolná, podporná a hodnotiaca činnosť APZ', $day['purpose']);
        $this->assertSame('Snina', $day['end_place']);
        $this->assertSame('14:00', $day['end_time']);
        $this->assertEquals(54.6, $calc['total_km']);                  // Vyúčtovanie VD!I92
        $this->assertEquals(54.6, $calc['auv_km']);                    // H466
    }

    public function test_settlement_formulas_match_excel(): void
    {
        $order = $this->approveAndGenerate();
        $ids = $order->segments->pluck('id')->all();
        $seg = fn ($id, $extra = []) => array_merge([
            'from_place' => $order->segments->firstWhere('id', $id)->from_place,
            'to_place' => $order->segments->firstWhere('id', $id)->to_place,
            'km' => $order->segments->firstWhere('id', $id)->km,
            'transport_mode' => 'AUV',
        ], $extra);

        $this->actingAs($this->user)->post("/travel/cp/{$order->id}", [
            'fuel_consumption' => 6.5,
            'advance_amount' => 10,
            'segments' => [
                $ids[0] => $seg($ids[0], ['departure_time' => '8:15', 'fuel_price' => 1.6, 'meals' => 5]),
                $ids[1] => $seg($ids[1], ['fuel_price' => 1.6]),
                $ids[2] => $seg($ids[2], ['other_costs' => 1.2]),
            ],
        ])->assertRedirect();

        $calc = app(TravelOrderService::class)->calculate($order->fresh());

        // Y = AI83 / 100 × W × AI
        $this->assertEquals(2.82, $calc['rows'][0]['fuel_cost']);     // 6,5/100 × 27,1 × 1,6 = 2,8184
        $this->assertEquals(2.13, $calc['rows'][1]['fuel_cost']);     // 6,5/100 × 20,5 × 1,6 = 2,132
        $this->assertEquals(0.0, $calc['rows'][2]['fuel_cost']);      // bez ceny PH
        // AD = Y + Z + AA + AB + AC
        $this->assertEquals(7.82, $calc['rows'][0]['total']);
        $this->assertEquals(11.15, $calc['totals']['total']);         // 7,82 + 2,13 + 1,20
        // AD271 = AD269 – AD270
        $this->assertEquals(1.15, $calc['balance']);
        // Skutočný čas z CP (líši sa od plánu) sa premietne do súhrnu dňa
        $this->assertSame('8:15', $calc['days']->first()['start_time']);
    }

    public function test_pdfs_and_page_render(): void
    {
        $order = $this->approveAndGenerate();

        $this->actingAs($this->user)->get('/travel/cp?period_id=' . $this->period->id)
            ->assertOk()->assertSee('08/2026/PN/ZK')->assertSee('Ubľa, Stakčín')->assertSee('Doplatok – Preplatok');
        $this->assertSame('application/pdf', $this->actingAs($this->user)->get("/travel/cp/{$order->id}/pdf")->headers->get('content-type'));
        $this->assertSame('application/pdf', $this->actingAs($this->user)->get("/travel/cp/{$order->id}/vyuctovanie-pdf")->headers->get('content-type'));
    }

    public function test_completed_order_is_locked_and_foreign_kapz_forbidden(): void
    {
        $order = $this->approveAndGenerate();
        $this->actingAs($this->user)->post("/travel/cp/{$order->id}/status")->assertRedirect();
        $this->assertSame('COMPLETED', $order->fresh()->status);

        $this->actingAs($this->user)->post("/travel/cp/{$order->id}", ['advance_amount' => 99])->assertSessionHas('error');
        $this->assertEquals(0, $order->fresh()->advance_amount);

        $other = User::where('email', 'horvathova@kapz.sk')->first();
        $this->actingAs($other)->get("/travel/cp/{$order->id}/pdf")->assertForbidden();
    }
}
