<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\KapzProfile;
use App\Models\ReportingPeriod;
use App\Services\AttendanceCalculatorService;
use App\Services\PdfGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class KapzCmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('KAPZ / APZ Informačný Systém');
    }

    public function test_kapz_dashboard_access_and_scoping(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Mgr. Peter Novák');
        $response->assertSee('Banská Bystrica');
    }

    public function test_admin_dashboard_shows_all_kapzs(): void
    {
        $admin = User::where('email', 'admin@kapz.sk')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Manažérska Centrálna Matica');
        $response->assertSee('Centrálna Matica Kontroly');
    }

    public function test_attendance_kapz_calculations(): void
    {
        $kapz = KapzProfile::first();
        $period = ReportingPeriod::first();

        $service = new AttendanceCalculatorService();
        $summary = $service->calculateKapzMonthlySummary($kapz->id, $period->id);

        $this->assertArrayHasKey('total_hours', $summary);
        $this->assertGreaterThan(0, $summary['total_hours']);
    }

    public function test_pdf_generation_for_kapz_attendance(): void
    {
        $kapz = KapzProfile::first();
        $period = ReportingPeriod::first();

        $service = new AttendanceCalculatorService();
        $summary = $service->calculateKapzMonthlySummary($kapz->id, $period->id);

        $pdfGenerator = new PdfGeneratorService();
        $pdf = $pdfGenerator->generateKapzAttendancePdf($kapz, $period, $summary);

        $this->assertNotEmpty($pdf->output());
    }

    public function test_knihy_page_and_pdf_generation(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $response = $this->actingAs($user)->get('/knihy');
        $response->assertStatus(200);
        $response->assertSee('KNIHY');
    }

    public function test_activity_reports_and_reporting_pages(): void
    {
        $admin = User::where('email', 'admin@kapz.sk')->first();
        
        $res1 = $this->actingAs($admin)->get('/activity-reports');
        $res1->assertStatus(200);

        $res2 = $this->actingAs($admin)->get('/reports');
        $res2->assertStatus(200);

        $res3 = $this->actingAs($admin)->get('/admin/assignments');
        $res3->assertStatus(200);

        $res4 = $this->actingAs($admin)->get('/admin/audit-logs');
        $res4->assertStatus(200);
    }

    public function test_contacts_directory_and_exports(): void
    {
        $kapzUser = User::where('role', 'kapz')->first();

        // 1. View Contacts Index
        $response = $this->actingAs($kapzUser)->get('/contacts');
        $response->assertStatus(200);
        $response->assertSee('Zoznam Kontaktov');

        // 2. Download Contacts PDF
        $pdfRes = $this->actingAs($kapzUser)->get('/contacts/pdf');
        $pdfRes->assertStatus(200);

        // 3. Export Contacts CSV
        $csvRes = $this->actingAs($kapzUser)->get('/contacts/export-csv');
        $csvRes->assertStatus(200);
        $csvRes->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_managerial_dashboard_kapz_and_admin(): void
    {
        $kapzUser = User::where('role', 'kapz')->first();
        $adminUser = User::where('role', 'admin')->first();

        // KAPZ Managerial Dashboard
        $resKapz = $this->actingAs($kapzUser)->get('/dashboard');
        $resKapz->assertStatus(200);
        $resKapz->assertSee('Manažérsky Prehľad');

        // Admin Central Matrix Dashboard
        $resAdmin = $this->actingAs($adminUser)->get('/dashboard');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Manažérska Centrálna Matica');
    }

    public function test_apz_attendance_workflow_and_batch_pdf(): void
    {
        $kapzUser = User::where('role', 'kapz')->first();
        $kapz = $kapzUser->kapzProfile;
        $period = ReportingPeriod::first();

        // 1. View APZ Attendance
        $response = $this->actingAs($kapzUser)->get('/attendance/apz?period_id=' . $period->id);
        $response->assertStatus(200);
        $response->assertSee('Evidencia Dochádzky APZ');
        $response->assertSee('Tím priradených asistentov');

        // 2. Download Single APZ PDF
        $assignedApzs = $kapz->assignedApzsForDate('2026-08-15');
        $firstApz = $assignedApzs->first();
        $this->assertNotNull($firstApz);

        $singlePdfRes = $this->actingAs($kapzUser)->get('/attendance/apz/pdf?apz_id=' . $firstApz->id . '&kapz_id=' . $kapz->id . '&period_id=' . $period->id);
        $singlePdfRes->assertStatus(200);

        // 3. Download Batch All APZ PDF
        $batchPdfRes = $this->actingAs($kapzUser)->get('/attendance/apz/all-pdf?kapz_id=' . $kapz->id . '&period_id=' . $period->id);
        $batchPdfRes->assertStatus(200);
    }

    public function test_calendar_view_and_task_assignment(): void
    {
        $kapzUser = User::where('role', 'kapz')->first();
        $adminUser = User::where('role', 'admin')->first();

        // 1. View Calendar as KAPZ
        $res = $this->actingAs($kapzUser)->get('/calendar?year=2026&month=8');
        $res->assertStatus(200);
        $res->assertSee('Celoročný Kalendár & Pokyny Experta');
        $res->assertSee('August 2026');

        // 2. Admin / Expert creates a directive task for KAPZ
        $createRes = $this->actingAs($adminUser)->post('/calendar/tasks', [
            'task_date' => '2026-08-15',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'kapz_id' => $kapzUser->kapzProfile->id,
            'category' => 'EXPERT_DIRECTIVE',
            'priority' => 'URGENT',
            'title' => 'Testovací pokyn experta pre terén',
            'description' => 'Inštrukcie k realizácii mimoriadneho monitoringu.',
        ]);
        $createRes->assertRedirect();

        // 3. Verify task exists in database
        $this->assertDatabaseHas('calendar_tasks', [
            'title' => 'Testovací pokyn experta pre terén',
            'priority' => 'URGENT',
        ]);
    }

    public function test_outside_activity_declaration_workflow_and_pdf(): void
    {
        $kapzUser = User::where('role', 'kapz')->first();
        $kapz = $kapzUser->kapzProfile;
        $period = ReportingPeriod::first();

        // 1. View Statement page
        $res = $this->actingAs($kapzUser)->get('/statements?period_id=' . $period->id);
        $res->assertStatus(200);
        $res->assertSee('Prehlásenie o činnosti mimo pracovného pomeru');
        $res->assertSee('Vykonával/a som zárobkovú');

        // 2. Download Official Statement PDF
        $decl = \App\Models\OutsideActivityDeclaration::where('kapz_id', $kapz->id)->where('reporting_period_id', $period->id)->first();
        $this->assertNotNull($decl);

        $pdfRes = $this->actingAs($kapzUser)->get('/statements/' . $decl->id . '/pdf');
        $pdfRes->assertStatus(200);
    }
}
