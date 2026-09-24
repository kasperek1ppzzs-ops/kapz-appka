<?php

namespace Tests\Feature;

use App\Models\ApzProfile;
use App\Models\ArrivalDepartureBook;
use App\Models\KapzProfile;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $novak;
    private User $horvathova;
    private ReportingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->novak = User::where('email', 'novak@kapz.sk')->first();
        $this->horvathova = User::where('email', 'horvathova@kapz.sk')->first();
        $this->period = ReportingPeriod::first();
    }

    public function test_kapz_cannot_download_attendance_pdf_of_another_kapz(): void
    {
        $other = $this->horvathova->kapzProfile;

        $this->actingAs($this->novak)
            ->get("/attendance/kapz/pdf?kapz_id={$other->id}&period_id={$this->period->id}")
            ->assertForbidden();
    }

    public function test_kapz_cannot_save_attendance_of_another_kapz(): void
    {
        $other = $this->horvathova->kapzProfile;

        $this->actingAs($this->novak)
            ->post('/attendance/kapz/save', [
                'kapz_id' => $other->id,
                'period_id' => $this->period->id,
                'entries' => ['2026-08-03' => ['status' => 'holiday']],
            ])
            ->assertForbidden();
    }

    public function test_kapz_cannot_access_unassigned_apz(): void
    {
        // Horváthová nemá pridelených APZ – APZ patria Novákovi.
        $kapz = $this->horvathova->kapzProfile;
        $apz = ApzProfile::first();

        $this->actingAs($this->horvathova)
            ->get("/attendance/apz/pdf?apz_id={$apz->id}&kapz_id={$kapz->id}&period_id={$this->period->id}")
            ->assertForbidden();
    }

    public function test_kapz_cannot_open_book_of_another_kapz(): void
    {
        $this->actingAs($this->novak)->get('/knihy')->assertOk();
        $book = ArrivalDepartureBook::where('kapz_id', $this->novak->kapzProfile->id)->first();

        $this->actingAs($this->horvathova)
            ->get("/knihy/{$book->id}/pdf")
            ->assertForbidden();
    }

    public function test_kapz_cannot_approve_own_travel_plan(): void
    {
        $plan = TravelPlan::create([
            'kapz_id' => $this->novak->kapzProfile->id,
            'reporting_period_id' => $this->period->id,
            'title' => 'Test',
            'status' => 'SUBMITTED',
            'km_limit' => 650,
            'version' => 1,
        ]);

        $this->actingAs($this->novak)
            ->post('/travel/approve', ['plan_id' => $plan->id])
            ->assertForbidden();

        $this->assertSame('SUBMITTED', $plan->fresh()->status);
    }

    public function test_kapz_cannot_modify_travel_plan_of_another_kapz(): void
    {
        $plan = TravelPlan::create([
            'kapz_id' => $this->horvathova->kapzProfile->id,
            'reporting_period_id' => $this->period->id,
            'title' => 'Cudzí plán',
            'status' => 'DRAFT',
            'km_limit' => 380,
            'version' => 1,
        ]);

        $this->actingAs($this->novak)
            ->post('/travel/add-item', [
                'travel_plan_id' => $plan->id,
                'week_number' => 1,
                'trip_date' => '2026-08-03',
                'departure_location' => 'Košice',
                'destination_location' => 'Moldava nad Bodvou',
                'purpose' => 'Test',
                'transport_mode' => 'AUV',
                'estimated_km' => 30,
            ])
            ->assertForbidden();

        $this->assertSame(0, $plan->items()->count());
    }

    public function test_kapz_cannot_access_admin_and_reporting_pages(): void
    {
        $this->actingAs($this->novak)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($this->novak)->get('/admin/assignments')->assertForbidden();
        $this->actingAs($this->novak)->get('/reports')->assertForbidden();
    }

    public function test_admin_keeps_access_to_any_kapz(): void
    {
        $admin = User::where('email', 'admin@kapz.sk')->first();
        $other = KapzProfile::where('id', $this->horvathova->kapzProfile->id)->first();

        $this->actingAs($admin)
            ->get("/attendance/kapz/pdf?kapz_id={$other->id}&period_id={$this->period->id}")
            ->assertOk();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
    }
}
