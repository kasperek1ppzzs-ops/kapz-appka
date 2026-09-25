<?php

namespace Tests\Feature;

use App\Models\AttendanceKapz;
use App\Models\KapzProfile;
use App\Models\ReportingPeriod;
use App\Services\ArrivalDepartureBookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * KNIHY v Exceli: časy 8:00–16:00 a prerušenie 12:00–12:30 „Obed“ iba ak odpracované ≥ 7,5 h,
 * poznámka (stĺpec M) = typ neodpracovaného dňa, víkend a deň bez stavu prázdne.
 */
class BookExcelParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_rows_follow_excel_knihy_formulas(): void
    {
        $this->seed();
        $kapz = KapzProfile::where('personal_number', '!=', '')->orderBy('id')->first();
        $period = ReportingPeriod::where('year', 2026)->where('month', 8)->first();
        AttendanceKapz::where('kapz_id', $kapz->id)->where('reporting_period_id', $period->id)->delete();

        $rows = [
            3 => ['work', 7.5],
            4 => ['holiday', 0],
            5 => ['doctor', 0],
            6 => ['public_holiday', 0],
            7 => ['holiday', 7.5],     // chybne uložené hodiny nesmú vytvoriť prítomnosť
            10 => ['half_holiday', 3.75],
            11 => ['no_communication', 0],
        ];
        foreach ($rows as $day => [$status, $hours]) {
            AttendanceKapz::create([
                'kapz_id' => $kapz->id,
                'reporting_period_id' => $period->id,
                'date' => sprintf('2026-08-%02d', $day),
                'status' => $status,
                'hours_worked' => $hours,
            ]);
        }

        $book = app(ArrivalDepartureBookService::class)->createAndSyncBook($kapz, $period, 'KAPZ', $kapz->id);
        $items = $book->items->keyBy('day_number');

        $this->assertSame($kapz->scope, $book->location);

        // Pracovný deň
        $this->assertSame(['08', '00', '16', '00', '12', '00', '12', '30', 'Obed'], [
            $items[3]->arrival_hour, $items[3]->arrival_minute, $items[3]->departure_hour, $items[3]->departure_minute,
            $items[3]->break_departure_hour, $items[3]->break_departure_minute,
            $items[3]->break_arrival_hour, $items[3]->break_arrival_minute, $items[3]->break_reason,
        ]);
        $this->assertNull($items[3]->note);

        // Absencie: bez časov, text v poznámke
        $expectedNotes = [4 => 'Dovolenka', 5 => 'Lekár', 6 => 'Sviatok', 7 => 'Dovolenka', 10 => 'Dovolenka', 11 => 'Nekomunikuje'];
        foreach ($expectedNotes as $day => $note) {
            $this->assertNull($items[$day]->arrival_hour, "deň $day nemá mať príchod");
            $this->assertNull($items[$day]->break_reason, "deň $day nemá mať dôvod prerušenia");
            $this->assertSame($note, $items[$day]->note, "poznámka dňa $day");
        }

        // Víkend (1. 8. 2026 je sobota) a pracovný deň bez záznamu (12. 8.) sú prázdne
        foreach ([1, 12] as $day) {
            $this->assertNull($items[$day]->arrival_hour);
            $this->assertNull($items[$day]->note);
        }
    }
}
