<?php

namespace Tests\Feature;

use App\Models\AttendanceKapz;
use App\Models\KapzProfile;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Services\AttendanceCalculatorService;
use App\Services\WorkingTimeFundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Porovnanie s `vyplnena matica.xlsm` (08/2026, KAPZ v stĺpci HLASENIE!B):
 * V práci 3.–7., 10.–12., 14. 8.; Dovolenka 13. 8.
 * Excel: EVIDENCIA_KAPZ!E39 = 67,5; G39 = 7,5; HLASENIE!B38 (fond) = 157,5.
 */
class AttendanceExcelParityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private KapzProfile $kapz;
    private ReportingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'novak@kapz.sk')->first();
        $this->kapz = $this->user->kapzProfile;
        $this->period = ReportingPeriod::where('year', 2026)->where('month', 8)->first();
        AttendanceKapz::where('kapz_id', $this->kapz->id)->where('reporting_period_id', $this->period->id)->delete();
    }

    private function save(array $entries)
    {
        return $this->actingAs($this->user)->post('/attendance/kapz/save', [
            'kapz_id' => $this->kapz->id,
            'period_id' => $this->period->id,
            'entries' => $entries,
        ]);
    }

    private function excelAugustEntries(): array
    {
        $entries = [];
        foreach ([3, 4, 5, 6, 7, 10, 11, 12, 14] as $day) {
            $entries[sprintf('2026-08-%02d', $day)] = ['status' => 'work', 'workplace' => 'Snina'];
        }
        // Formulár pošle 7,5 aj pri absencii – server ho musí ignorovať.
        $entries['2026-08-13'] = ['status' => 'holiday', 'workplace' => 'Snina', 'hours_worked' => 7.5];

        return $entries;
    }

    public function test_fund_matches_excel_calendar(): void
    {
        $fund = new WorkingTimeFundService();

        // Kalendár!AF – 2026
        $expected = [1 => 165, 2 => 150, 3 => 165, 4 => 165, 5 => 157.5, 6 => 165,
            7 => 172.5, 8 => 157.5, 9 => 165, 10 => 165, 11 => 157.5, 12 => 172.5];
        foreach ($expected as $month => $hours) {
            $this->assertEquals($hours, $fund->fundHours(2026, $month), "mesiac $month");
        }
    }

    public function test_summary_matches_excel_evidencia(): void
    {
        $this->save($this->excelAugustEntries())->assertRedirect();

        $holiday = AttendanceKapz::where('kapz_id', $this->kapz->id)->where('date', '2026-08-13')->first();
        $this->assertEquals(0, (float) $holiday->hours_worked);
        $this->assertNull($holiday->workplace);

        $summary = (new AttendanceCalculatorService())->calculateKapzMonthlySummary($this->kapz->id, $this->period->id);

        $this->assertEquals(67.5, $summary['total_work_hours']);   // E39
        $this->assertEquals(7.5, $summary['total_absence_hours']); // G39
        $this->assertEquals(21, $summary['total_working_days']);
        $this->assertEquals(157.5, $summary['fund_hours']);
        $this->assertFalse($summary['fund_ok']);                   // mesiac nie je vyplnený celý
        $this->assertEquals(1, $summary['holiday_days']);
        $this->assertEquals(9, $summary['actual_work_days']);
    }

    public function test_half_day_counts_three_point_seventy_five_hours_each(): void
    {
        $this->save([
            '2026-08-03' => ['status' => 'half_holiday', 'workplace' => 'Snina'],
            '2026-08-04' => ['status' => 'half_doctor', 'workplace' => ''],
        ])->assertRedirect();

        $half = AttendanceKapz::where('kapz_id', $this->kapz->id)->where('date', '2026-08-03')->first();
        $this->assertEquals(3.75, (float) $half->hours_worked);
        $this->assertSame('Snina', $half->workplace);

        $summary = (new AttendanceCalculatorService())->calculateKapzMonthlySummary($this->kapz->id, $this->period->id);
        $this->assertEquals(7.5, $summary['total_work_hours']);
        $this->assertEquals(7.5, $summary['total_absence_hours']);
        $this->assertEquals(0.5, $summary['holiday_days']);
        $this->assertEquals(0.5, $summary['doctor_days']);
    }

    public function test_full_month_passes_fund_check(): void
    {
        $entries = [];
        for ($d = 1; $d <= 31; $d++) {
            $date = sprintf('2026-08-%02d', $d);
            if (date('N', strtotime($date)) >= 6) {
                $entries[$date] = ['status' => 'weekend'];
            } else {
                $entries[$date] = ['status' => $d === 20 ? 'pn' : 'work'];
            }
        }
        $this->save($entries)->assertRedirect();

        $summary = (new AttendanceCalculatorService())->calculateKapzMonthlySummary($this->kapz->id, $this->period->id);
        $this->assertTrue($summary['fund_ok']);
        $this->assertEquals(150.0, $summary['total_work_hours']);
        $this->assertEquals(7.5, $summary['total_absence_hours']);
        $this->assertEquals(1, $summary['pn_days']);
    }

    public function test_unknown_status_is_rejected(): void
    {
        $this->save(['2026-08-03' => ['status' => 'vymyslený']])->assertSessionHasErrors('entries.2026-08-03.status');
    }

    public function test_new_excel_statuses_are_selectable_and_rendered_in_pdf(): void
    {
        $this->save([
            '2026-08-03' => ['status' => 'no_communication'],
            '2026-08-04' => ['status' => 'md'],
            '2026-08-05' => ['status' => 'half_holiday', 'workplace' => 'Snina'],
        ])->assertRedirect();

        $page = $this->actingAs($this->user)->get('/attendance/kapz?period_id=' . $this->period->id);
        $page->assertOk()->assertSee('1/2 Dovolenka')->assertSee('lekár-tehotenstvo')->assertSee('Fond pracovného času');

        $summary = (new AttendanceCalculatorService())->calculateKapzMonthlySummary($this->kapz->id, $this->period->id);
        $html = view('pdf.evidencia_kapz', ['kapz' => $this->kapz, 'period' => $this->period, 'summary' => $summary])->render();

        $this->assertStringContainsString('Nekomunikuje', $html);
        $this->assertStringNotContainsString('no_communication', $html);
        $this->assertStringContainsString('3,75', $html);
    }
}
