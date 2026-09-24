<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ReportingPeriod;
use App\Models\ArrivalDepartureBook;
use App\Services\ArrivalDepartureBookService;
use App\Services\PdfGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArrivalDepartureBookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_kapz_can_view_arrival_departure_book_with_correct_hours_and_lunch_break(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $response = $this->get('/knihy');
        $response->assertStatus(200);
        $response->assertSee('KNIHA PRÍCHODOV A ODCHODOV');
        $response->assertSee('Novák');
        $response->assertSee('08');
        $response->assertSee('16');
        $response->assertSee('Obed');
    }

    public function test_kapz_can_view_apz_arrival_departure_book(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $apz = ApzProfile::first();
        $period = ReportingPeriod::first();

        $response = $this->get("/knihy?period_id={$period->id}&person_type=APZ&person_id={$apz->id}");
        $response->assertStatus(200);
        $response->assertSee($apz->full_name);
    }

    public function test_can_save_arrival_departure_book_items(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $book = ArrivalDepartureBook::first();
        $item = $book->items()->first();

        $response = $this->post("/knihy/{$book->id}/save", [
            'items' => [
                $item->id => [
                    'arrival_hour' => '08',
                    'arrival_minute' => '00',
                    'departure_hour' => '16',
                    'departure_minute' => '00',
                    'break_departure_hour' => '12',
                    'break_departure_minute' => '00',
                    'break_arrival_hour' => '12',
                    'break_arrival_minute' => '30',
                    'break_reason' => 'Obed',
                    'visited_location' => null,
                    'note' => 'Úprava záznamu',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('arrival_departure_book_items', [
            'id' => $item->id,
            'note' => 'Úprava záznamu',
        ]);
    }

    public function test_can_download_single_and_team_pdf(): void
    {
        $user = User::where('email', 'novak@kapz.sk')->first();
        $this->actingAs($user);

        $book = ArrivalDepartureBook::first();

        // 1. Single PDF
        $response = $this->get("/knihy/{$book->id}/pdf");
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        // 2. All-team PDF
        $responseTeam = $this->get("/knihy/all-pdf?period_id={$book->reporting_period_id}&kapz_id={$book->kapz_id}");
        $responseTeam->assertStatus(200);
        $this->assertEquals('application/pdf', $responseTeam->headers->get('content-type'));
    }
}
