<?php

namespace Tests\Feature;

use App\Models\TravelOrder;
use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use App\Models\User;
use App\Services\PdfGeneratorService;
use App\Services\TravelWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelExcelParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_km_limits_match_excel_sheet_exactly(): void
    {
        $service = new TravelWorkflowService();

        // Hárok „limity a prac. dni“ – predtým Košice = 530, Poprad - okolie = 400, Levoča = 525.
        $expected = [
            'Košice' => 380, 'Košice-okolie' => 530, 'Košice - okolie' => 530,
            'Poprad' => 400, 'Poprad - okolie' => 745, 'Levoča' => 400,
            'Snina' => 650, 'Velký Krtíš' => 1035, 'Veľký Krtíš' => 1035,
            'Žilina a okolie' => 0, '' => 0,
        ];
        foreach ($expected as $scope => $km) {
            $this->assertEquals($km, $service->getLimitForScope($scope), "pôsobnosť „{$scope}“");
        }
    }

    public function test_official_purposes_come_from_generator_sheet(): void
    {
        $purposes = (new TravelWorkflowService())->getOfficialPurposes();

        $this->assertCount(19, $purposes);
        $this->assertSame('Prezentácia NP zdravé komunity', $purposes[0]);
        $this->assertContains('Kontrolná, podporná a hodnotiaca činnosť APZ', $purposes);
        $this->assertContains('Odborné riadenie koordinačných stretnutí APZ', $purposes);
    }

    public function test_draft_plan_picks_up_corrected_limit(): void
    {
        $user = User::where('email', 'horvathova@kapz.sk')->first(); // pôsobnosť Košice
        $this->actingAs($user)->get('/travel')->assertOk();

        $plan = TravelPlan::where('kapz_id', $user->kapzProfile->id)->first();
        $this->assertEquals(380, (float) $plan->km_limit);
    }

    public function test_plan_pdf_does_not_invent_missing_times(): void
    {
        $plan = TravelPlan::first();
        $plan->items()->delete();
        TravelPlanItem::create([
            'travel_plan_id' => $plan->id,
            'week_number' => 1,
            'trip_date' => '2026-08-03',
            'departure_time' => '07:10',
            'arrival_time' => '15:50',
            'departure_location' => 'Snina',
            'destination_location' => 'Ubľa',
            'purpose' => 'Kontrolná, podporná a hodnotiaca činnosť APZ',
            'transport_mode' => 'AUV',
            'estimated_km' => 27.1,
        ]);

        $fresh = $plan->fresh()->load(['kapz', 'reportingPeriod', 'items.targetApz', 'items.segments', 'reviewedBy']);
        $html = view('pdf.plan_pracovnych_ciest', [
            'plan' => $fresh,
            'weeks' => app(\App\Services\TravelSegmentService::class)->weeksFor($fresh),
            'selectedWeek' => null,
        ])->render();

        $this->assertStringContainsString('07:10', $html);
        $this->assertStringNotContainsString('09:00', $html);
        $this->assertStringNotContainsString('14:00', $html);
    }

    public function test_travel_order_detail_page_renders(): void
    {
        $order = TravelOrder::first();
        $user = $order->kapz->user;

        $this->actingAs($user)->get("/travel/orders/{$order->id}")
            ->assertOk()
            ->assertSee($order->order_number);
    }
}
