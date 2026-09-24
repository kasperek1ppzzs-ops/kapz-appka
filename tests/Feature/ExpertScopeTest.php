<?php

namespace Tests\Feature;

use App\Models\ExpertKapzAssignment;
use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Expert pre terén vidí iba KAPZ, ktorých mu pridelil admin. */
class ExpertScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $expert;
    private User $novak;       // pridelený expertovi v seederi
    private User $horvathova;  // nepridelená
    private ReportingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->expert = User::where('role', 'expert')->first();
        $this->novak = User::where('email', 'novak@kapz.sk')->first();
        $this->horvathova = User::where('email', 'horvathova@kapz.sk')->first();
        $this->period = ReportingPeriod::where('year', 2026)->where('month', 8)->first();
    }

    private function plan(User $kapzUser): TravelPlan
    {
        return TravelPlan::create([
            'kapz_id' => $kapzUser->kapzProfile->id,
            'reporting_period_id' => $this->period->id,
            'title' => 'Plán',
            'status' => 'SUBMITTED',
            'km_limit' => 500,
            'version' => 1,
        ]);
    }

    public function test_expert_sees_only_assigned_kapz_documents(): void
    {
        $assigned = $this->novak->kapzProfile->id;
        $other = $this->horvathova->kapzProfile->id;

        $this->actingAs($this->expert)
            ->get("/attendance/kapz/pdf?kapz_id={$assigned}&period_id={$this->period->id}")->assertOk();
        $this->actingAs($this->expert)
            ->get("/attendance/kapz/pdf?kapz_id={$other}&period_id={$this->period->id}")->assertForbidden();
        $this->actingAs($this->expert)
            ->get("/attendance/kapz?kapz_id={$other}&period_id={$this->period->id}")->assertForbidden();
    }

    public function test_expert_can_approve_only_assigned_kapz_plans(): void
    {
        $own = $this->plan($this->novak);
        $foreign = $this->plan($this->horvathova);

        $this->actingAs($this->expert)->post('/travel/approve', ['plan_id' => $own->id])->assertRedirect();
        $this->actingAs($this->expert)->post('/travel/approve', ['plan_id' => $foreign->id])->assertForbidden();

        $this->assertSame('APPROVED', $own->fresh()->status);
        $this->assertSame('SUBMITTED', $foreign->fresh()->status);
    }

    public function test_expert_lists_and_dashboard_hide_unassigned_kapz(): void
    {
        $this->plan($this->horvathova);
        $foreignName = $this->horvathova->kapzProfile->full_name;

        $this->actingAs($this->expert)->get('/travel?period_id=' . $this->period->id)
            ->assertOk()->assertDontSee($foreignName);
        $this->actingAs($this->expert)->get('/dashboard')
            ->assertOk()->assertSee($this->novak->kapzProfile->full_name)->assertDontSee($foreignName);
        $this->actingAs($this->expert)->get('/reports?period_id=' . $this->period->id)
            ->assertOk()->assertDontSee($foreignName);
    }

    public function test_admin_assignment_grants_and_ended_assignment_revokes_access(): void
    {
        $admin = User::where('role', 'admin')->first();
        $other = $this->horvathova->kapzProfile;

        $this->actingAs($admin)->post('/admin/expert-assignments', [
            'expert_user_id' => $this->expert->id,
            'kapz_id' => $other->id,
            'valid_from' => now()->subMonth()->toDateString(),
        ])->assertRedirect();

        $this->assertTrue($this->expert->fresh()->canAccessKapz($other->id));
        $this->assertSame($this->expert->name, $other->fresh()->region_expert);

        ExpertKapzAssignment::where('kapz_id', $other->id)->update(['valid_to' => now()->subDay()->toDateString()]);
        $this->assertFalse($this->expert->fresh()->canAccessKapz($other->id));
    }

    public function test_only_admin_can_assign_and_only_to_expert_role(): void
    {
        $payload = [
            'expert_user_id' => $this->expert->id,
            'kapz_id' => $this->horvathova->kapzProfile->id,
            'valid_from' => '2026-09-01',
        ];
        $this->actingAs($this->expert)->post('/admin/expert-assignments', $payload)->assertForbidden();

        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->post('/admin/expert-assignments', ['expert_user_id' => $this->novak->id] + $payload)
            ->assertSessionHasErrors('expert_user_id');
    }
}
