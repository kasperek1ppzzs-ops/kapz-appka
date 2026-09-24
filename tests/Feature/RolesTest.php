<?php

namespace Tests\Feature;

use App\Models\ReportingPeriod;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_expert_gets_supervisor_dashboard_without_admin_controls(): void
    {
        $expert = User::where('role', 'expert')->first();
        $this->assertNotNull($expert, 'Seeder má vytvoriť používateľa s rolou expert.');

        $this->actingAs($expert)->get('/dashboard')
            ->assertOk()
            ->assertSee(route('reports.index'), false)
            ->assertDontSee(route('admin.audit_logs'), false)
            ->assertDontSee('Uzamknúť Mesiac');
    }

    public function test_expert_can_approve_plan_and_view_reports_but_not_admin(): void
    {
        $expert = User::where('role', 'expert')->first();
        $kapzUser = User::where('email', 'novak@kapz.sk')->first();
        $plan = TravelPlan::create([
            'kapz_id' => $kapzUser->kapzProfile->id,
            'reporting_period_id' => ReportingPeriod::first()->id,
            'title' => 'Plán na schválenie',
            'status' => 'SUBMITTED',
            'km_limit' => 1250,
            'version' => 1,
        ]);

        $this->actingAs($expert)->post('/travel/approve', ['plan_id' => $plan->id])->assertRedirect();
        $this->assertSame('APPROVED', $plan->fresh()->status);
        $this->assertSame($expert->id, $plan->fresh()->reviewed_by_user_id);

        $this->actingAs($expert)->get('/reports')->assertOk();
        $this->actingAs($expert)->get('/travel')->assertOk();
        $this->actingAs($expert)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_admin_dashboard_keeps_admin_controls(): void
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee(route('admin.audit_logs'), false);
    }

    public function test_kapz_without_profile_sees_explanation_instead_of_error(): void
    {
        $user = User::create([
            'name' => 'Nový KAPZ',
            'email' => 'novy@kapz.sk',
            'password' => Hash::make('password'),
            'role' => 'kapz',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('nie je priradený profil KAPZ');
    }
}
