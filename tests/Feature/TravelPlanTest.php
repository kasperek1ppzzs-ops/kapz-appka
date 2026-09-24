<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_kapz_can_view_travel_plan_with_5_weeks_and_assigned_village_chips(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $period = ReportingPeriod::where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $user->kapzProfile->id)
            ->where('reporting_period_id', $period->id)
            ->first();
        $plan->update(['status' => 'DRAFT']);

        $response = $this->get("/travel?period_id={$period->id}");
        $response->assertStatus(200);
        $response->assertSee('Týždenný Plán Pracovných Ciest KAPZ');
        $response->assertSee('1. TÝŽDEŇ');
        $response->assertSee('5. TÝŽDEŇ');
        $response->assertSee('Mesačný limit km');
        $response->assertSee('⚡ Rýchly výber obcí vášho tímu na 1 klik:');
        $response->assertSee('Podlavice');
        $response->assertSee('Šalková');
    }

    public function test_kapz_can_add_trip_to_assigned_village_and_custom_destination(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $period = ReportingPeriod::where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $user->kapzProfile->id)
            ->where('reporting_period_id', $period->id)
            ->first();

        $plan->update(['status' => 'DRAFT']);

        // 1. Add trip with all 4 times to assigned village
        $response1 = $this->post('/travel/add-item', [
            'travel_plan_id' => $plan->id,
            'week_number' => 1,
            'trip_date' => "{$period->year}-" . str_pad($period->month, 2, '0', STR_PAD_LEFT) . "-05",
            'departure_time' => '08:00',
            'arrival_at_dest_time' => '09:00',
            'departure_from_dest_time' => '14:00',
            'arrival_time' => '15:00',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Šalková',
            'purpose' => 'Odborná príprava a koordinácia APZ',
            'transport_mode' => 'AUV',
            'estimated_km' => 25.5,
        ]);
        $response1->assertRedirect();
        $this->assertDatabaseHas('travel_plan_items', [
            'destination_location' => 'Šalková',
            'departure_time' => '08:00',
            'arrival_at_dest_time' => '09:00',
            'departure_from_dest_time' => '14:00',
            'arrival_time' => '15:00',
            'estimated_km' => 25.5,
        ]);

        // 2. Add trip to external/custom locality (nie len do priradených)
        $response2 = $this->post('/travel/add-item', [
            'travel_plan_id' => $plan->id,
            'week_number' => 2,
            'trip_date' => "{$period->year}-" . str_pad($period->month, 2, '0', STR_PAD_LEFT) . "-12",
            'departure_time' => '07:30',
            'arrival_time' => '17:00',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Levoča - Regionálne pracovné stretnutie',
            'purpose' => 'Priama účasť na pracovnom stretnutí',
            'transport_mode' => 'AUV',
            'estimated_km' => 140.0,
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('travel_plan_items', [
            'destination_location' => 'Levoča - Regionálne pracovné stretnutie',
            'estimated_km' => 140.0,
        ]);
    }

    public function test_kapz_can_submit_plan_for_approval(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $period = ReportingPeriod::where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $user->kapzProfile->id)
            ->where('reporting_period_id', $period->id)
            ->first();

        $plan->update(['status' => 'DRAFT']);

        // Add a trip so plan is not empty
        TravelPlanItem::create([
            'travel_plan_id' => $plan->id,
            'week_number' => 1,
            'trip_date' => "{$period->year}-" . str_pad($period->month, 2, '0', STR_PAD_LEFT) . "-03",
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Podlavice',
            'purpose' => 'Kontrola v teréne',
            'transport_mode' => 'AUV',
            'estimated_km' => 15.0,
        ]);

        $response = $this->post('/travel/submit', [
            'plan_id' => $plan->id,
        ]);
        $response->assertRedirect();
        $this->assertEquals('SUBMITTED', $plan->fresh()->status);
    }

    public function test_expert_can_view_and_approve_plan_with_zfk(): void
    {
        $admin = User::where('email', 'admin@kapz.sk')->first();
        $kapzUser = User::where('email', 'novak@kapz.sk')->first();

        $period = ReportingPeriod::where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $kapzUser->kapzProfile->id)
            ->where('reporting_period_id', $period->id)
            ->first();

        $plan->update(['status' => 'SUBMITTED', 'submitted_at' => now()]);

        // 1. Admin views /travel for August
        $this->actingAs($admin);
        $response = $this->get("/travel?period_id={$period->id}");
        $response->assertStatus(200);
        $response->assertSee('Schvaľovanie Týždenných Plánov Ciest KAPZ');
        $response->assertSee($kapzUser->kapzProfile->full_name);

        // 2. Admin approves plan
        $approveResponse = $this->post('/travel/approve', [
            'plan_id' => $plan->id,
            'admin_notes' => 'Plán ciest je v poriadku, ZFK overená.',
        ]);
        $approveResponse->assertRedirect();
        $this->assertEquals('APPROVED', $plan->fresh()->status);
        $this->assertNotNull($plan->fresh()->reviewed_at);
        $this->assertEquals($admin->id, $plan->fresh()->reviewed_by_user_id);
    }

    public function test_expert_can_return_plan_with_mandatory_notes(): void
    {
        $admin = User::where('email', 'admin@kapz.sk')->first();
        $kapzUser = User::where('email', 'novak@kapz.sk')->first();

        $period = ReportingPeriod::where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $kapzUser->kapzProfile->id)
            ->where('reporting_period_id', $period->id)
            ->first();

        $plan->update(['status' => 'SUBMITTED', 'submitted_at' => now()]);

        $this->actingAs($admin);
        $returnResponse = $this->post('/travel/return', [
            'plan_id' => $plan->id,
            'admin_notes' => 'Prosím upravte termín cesty v 2. týždni kvôli celoslovenskej porade.',
        ]);
        $returnResponse->assertRedirect();

        $refreshed = $plan->fresh();
        $this->assertEquals('RETURNED', $refreshed->status);
        $this->assertStringContainsString('celoslovenskej porade', $refreshed->admin_notes);
    }

    public function test_can_download_official_travel_plan_pdf(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $period = ReportingPeriod::where('month', 8)->first();
        $plan = TravelPlan::where('kapz_id', $user->kapzProfile->id)
            ->where('reporting_period_id', $period->id)
            ->first();

        // Add trips in weeks 1 and 3
        TravelPlanItem::create([
            'travel_plan_id' => $plan->id,
            'week_number' => 1,
            'trip_date' => "{$period->year}-" . str_pad($period->month, 2, '0', STR_PAD_LEFT) . "-03",
            'departure_time' => '08:00',
            'arrival_time' => '16:00',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Šalková',
            'purpose' => 'Odborná príprava APZ',
            'transport_mode' => 'AUV',
            'estimated_km' => 30.0,
        ]);

        // 1. Download complete plan (all weeks)
        $response = $this->get("/travel/pdf/{$plan->id}");
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('PLAN_PRACOVNYCH_CIEST', $response->headers->get('content-disposition'));

        // 2. Download individual week plan (e.g. week 1)
        $responseWeek1 = $this->get("/travel/pdf/{$plan->id}?week=1");
        $responseWeek1->assertStatus(200);
        $this->assertEquals('application/pdf', $responseWeek1->headers->get('content-type'));
        $this->assertStringContainsString('1_TYZDEN', $responseWeek1->headers->get('content-disposition'));

        // 3. Verify compliance and ZFK text is present in the web view
        $viewResponse = $this->get("/travel?period_id={$period->id}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Harmonogram pracovných ciest je v súlade s náplňou opisu pracovnej činnosti práce Koordinátor asistentov podpory zdravia.');
        $viewResponse->assertSee('Spracoval/a:');
        $viewResponse->assertSee('Mgr. Peter Novák');
        $viewResponse->assertSee('Základná finančná kontrola (ZFK) podľa § 7 zákona NR SR č. 357/2015 Z.z.');
        $viewResponse->assertSee('Pracovné stretnutie a porada v zdravotníckych a sociálnych zariadeniach');
        $viewResponse->assertSee('V súvislosti s realizáciou NP ZK, kód ITMS 401405DUQ8');
        $viewResponse->assertSee('Overenie súladu finančnej operácie so skutočnosťami podľa § 6 ods. 4 zákona č. 357/2015 Z.z. vykonáva príslušný');
    }

    public function test_distance_calculation_endpoint(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        // 1. Same location should return 0 km
        $response1 = $this->getJson('/travel/calculate-distance?from=Banská Bystrica&to=Banská Bystrica');
        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'distance_km' => 0.0,
            'source' => 'same_location',
        ]);

        // 2. Local suburb fallback (Podlavice)
        $response2 = $this->getJson('/travel/calculate-distance?from=Banská Bystrica&to=Podlavice');
        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'distance_km' => 6.0,
            'round_trip_km' => 12.0,
            'source' => 'local_matrix',
        ]);

        // 3. Real municipality routing (Banská Bystrica -> Brezno)
        $response3 = $this->getJson('/travel/calculate-distance?from=Banská Bystrica&to=Brezno');
        $response3->assertStatus(200);
        $data = $response3->json();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(35.0, $data['distance_km']);
        $this->assertLessThan(55.0, $data['distance_km']);
        $this->assertEquals(round($data['distance_km'] * 2, 1), $data['round_trip_km']);

        // 4. Multi-stop route circuit (Banská Bystrica -> Šalková, Podlavice)
        $response4 = $this->getJson('/travel/calculate-distance?from=Banská Bystrica&to=Šalková, Podlavice');
        $response4->assertStatus(200);
        $data4 = $response4->json();
        $this->assertTrue($data4['success']);
        $this->assertTrue($data4['is_multi_stop']);
        $this->assertEquals(2, $data4['stop_count']);
        $this->assertGreaterThan(15.0, $data4['round_trip_km']);
    }
}
