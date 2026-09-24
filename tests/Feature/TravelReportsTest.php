<?php

namespace Tests\Feature;

use App\Models\ActivityReport;
use App\Models\MonthlyTravelOrder;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\User;
use App\Services\PdfGeneratorService;
use App\Services\TravelOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CP → GENERATOR / Správa z pracovnej cesty → Správa o pracovnej činnosti (čerpanie limitu). */
class TravelReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MonthlyTravelOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'novak@kapz.sk')->first();
        $period = ReportingPeriod::where('year', 2026)->where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $this->user->kapzProfile->id)->where('reporting_period_id', $period->id)->first();
        $plan->items()->delete();
        $plan->update(['status' => 'DRAFT']);

        $this->actingAs($this->user)->post("/travel/{$plan->id}/day", [
            'date' => '2026-08-03',
            'segments' => [
                ['from_place' => 'Snina', 'to_place' => 'Ubľa', 'km' => 27.1, 'purpose' => 'Kontrolná, podporná a hodnotiaca činnosť APZ'],
                ['from_place' => 'Ubľa', 'to_place' => 'Snina', 'km' => 27.1],
            ],
        ]);
        $this->actingAs($this->user)->post("/travel/{$plan->id}/day", [
            'date' => '2026-08-04',
            'segments' => [
                ['from_place' => 'Snina', 'to_place' => 'Humenné', 'km' => 22.4, 'purpose' => 'Osvetová činnosť'],
                ['from_place' => 'Humenné', 'to_place' => 'Snina', 'km' => 22.4],
            ],
        ]);
        $plan->update(['status' => 'APPROVED']);
        $this->order = app(TravelOrderService::class)->generateFromPlan($plan);
    }

    public function test_conclusion_is_looked_up_by_purpose_like_generator(): void
    {
        $days = app(TravelOrderService::class)->reportDays($this->order);

        $this->assertSame('Kontrolná, organizačná, podporná a hodnotiaca činnosť APZ v súvislosti s výkonom ich pracovnej činnosti.', $days[0]['conclusion']);
        $this->assertStringStartsWith('Priame vykonávanie osvetovej činnosti KAPZ', $days[1]['conclusion']);
        $this->assertSame($days[0]['conclusion'], $days[0]['report_text']); // predvolený text správy
    }

    public function test_kapz_can_write_own_report_text_and_download_pdfs(): void
    {
        $this->actingAs($this->user)->post("/travel/cp/{$this->order->id}/spravy", [
            'days' => [
                '2026-08-03' => ['report_text' => 'Dňa 3.8.2026 som vykonala kontrolnú činnosť APZ v lokalite Ubľa.', 'conclusion' => ''],
                '2099-01-01' => ['report_text' => 'cudzí deň sa ignoruje'],
            ],
        ])->assertRedirect();

        $days = app(TravelOrderService::class)->reportDays($this->order->fresh());
        $this->assertSame('Dňa 3.8.2026 som vykonala kontrolnú činnosť APZ v lokalite Ubľa.', $days[0]['report_text']);
        $this->assertStringStartsWith('Kontrolná, organizačná', $days[0]['conclusion']);
        $this->assertSame(1, $this->order->dayTexts()->count());

        $this->actingAs($this->user)->get("/travel/cp/{$this->order->id}/spravy")->assertOk()->assertSee('Humenné');
        foreach (['spravy-pdf', 'generator-pdf'] as $pdf) {
            $this->assertSame('application/pdf', $this->actingAs($this->user)->get("/travel/cp/{$this->order->id}/{$pdf}")->headers->get('content-type'));
        }
    }

    public function test_activity_report_uses_limit_and_driven_km_from_order(): void
    {
        $usage = app(TravelOrderService::class)->limitUsage($this->user->kapzProfile, $this->order->reportingPeriod);

        $this->assertEquals(1250, $usage['limit']);   // Banská Bystrica (seed)
        $this->assertEquals(99.0, $usage['driven']);  // 27,1 × 2 + 22,4 × 2 – z CP, nie z plánu
        $this->assertEquals(1151.0, $usage['remaining']);

        $report = ActivityReport::where('kapz_id', $this->user->kapzProfile->id)->first();
        $this->assertNotEmpty(app(PdfGeneratorService::class)->generateActivityReportPdf($report)->output());
    }
}
