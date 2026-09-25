<?php

namespace App\Services;

use App\Models\ArrivalDepartureBook;
use App\Models\ArrivalDepartureBookItem;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ReportingPeriod;
use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;

class ArrivalDepartureBookService
{
    /**
     * Get or create a book for a given person, auto-syncing from attendance if not existing.
     */
    public function getOrCreateBook(KapzProfile $kapz, ReportingPeriod $period, string $personType, int $personId): ArrivalDepartureBook
    {
        $book = ArrivalDepartureBook::where('kapz_id', $kapz->id)
            ->where('reporting_period_id', $period->id)
            ->where('person_type', $personType)
            ->where('person_id', $personId)
            ->first();

        if (!$book) {
            $book = $this->createAndSyncBook($kapz, $period, $personType, $personId);
        }

        return $book;
    }

    /**
     * Create and populate a book for a person from attendance data.
     */
    public function createAndSyncBook(KapzProfile $kapz, ReportingPeriod $period, string $personType, int $personId): ArrivalDepartureBook
    {
        if ($personType === 'KAPZ') {
            $personName = $kapz->full_name;
            $personalNumber = $kapz->personal_number;
            $location = $kapz->scope;               // KNIHY!K5 = HLASENIE!B2
            $approverName = $kapz->region_expert;   // „Schválil“ – nadriadený podľa profilu
        } else {
            $apz = ApzProfile::findOrFail($personId);
            $personName = $apz->full_name;
            $personalNumber = $apz->personal_number;
            $location = $apz->scope;                // KNIHY!K47 = HLASENIE!C2
            $approverName = $kapz->full_name;
        }

        $book = ArrivalDepartureBook::updateOrCreate(
            [
                'kapz_id' => $kapz->id,
                'reporting_period_id' => $period->id,
                'person_type' => $personType,
                'person_id' => $personId,
            ],
            [
                'personal_number' => $personalNumber,
                'full_name' => $personName,
                'location' => $location,
                'project_code' => config('kapz.itms_code'),
                'approver_name' => $approverName,
                'status' => 'DRAFT',
            ]
        );

        $this->syncBookItems($book, $kapz, $period, $personType, $personId);

        return $book->fresh(['items']);
    }

    /**
     * Synchronize items (days 1 to 31) for the book from attendance records.
     */
    public function syncBookItems(ArrivalDepartureBook $book, KapzProfile $kapz, ReportingPeriod $period, string $personType, int $personId): void
    {
        $daysInMonth = Carbon::create($period->year, $period->month, 1)->daysInMonth;
        
        // Delete old items
        $book->items()->delete();

        // Get attendance map
        $attendanceMap = [];
        if ($personType === 'KAPZ') {
            $records = AttendanceKapz::where('kapz_id', $kapz->id)
                ->where('reporting_period_id', $period->id)
                ->get();
            foreach ($records as $r) {
                $dayNum = (int)Carbon::parse($r->date)->day;
                $attendanceMap[$dayNum] = $r;
            }
        } else {
            $records = AttendanceApz::where('apz_id', $personId)
                ->where('reporting_period_id', $period->id)
                ->get();
            foreach ($records as $r) {
                $dayNum = (int)Carbon::parse($r->date)->day;
                $attendanceMap[$dayNum] = $r;
            }
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($period->year, $period->month, $day);
            ArrivalDepartureBookItem::create(array_merge(
                $this->bookRowFromAttendance($attendanceMap[$day] ?? null),
                [
                    'book_id' => $book->id,
                    'day_number' => $day,
                    'record_date' => $date->format('Y-m-d'),
                    'visited_location' => null, // Excel KNIHY!K – vypĺňa sa ručne
                    'approved_by' => null,      // Excel KNIHY!L – vypĺňa sa ručne
                ]
            ));
        }
    }

    /**
     * Get all books for KAPZ and his assigned APZs for the period.
     */
    public function getAllTeamBooks(KapzProfile $kapz, ReportingPeriod $period): array
    {
        $books = [];
        // 1. KAPZ Book
        $books[] = $this->getOrCreateBook($kapz, $period, 'KAPZ', $kapz->id);

        // 2. APZ Books
        $refDate = sprintf('%04d-%02d-15', $period->year, $period->month);
        $assignedApzs = $kapz->assignedApzsForDate($refDate);
        foreach ($assignedApzs as $apz) {
            $books[] = $this->getOrCreateBook($kapz, $period, 'APZ', $apz->id);
        }

        return $books;
    }

    /**
     * Riadok knihy podľa vzorcov hárku KNIHY (riadok 9):
     *  - B–J: 8:00 / 16:00 / 12:00–12:30 „Obed“ iba ak odpracované hodiny ≥ 7,5
     *    (`=IF(N(EVIDENCIA_KAPZ!E8)>=7.5,8,"")`),
     *  - M (Poznámka): text neodpracovaného dňa z evidencie (`=EVIDENCIA_KAPZ!F8`),
     *  - víkend, polovičný deň bez času a deň bez záznamu zostávajú bez časov.
     */
    public function bookRowFromAttendance($attendance): array
    {
        $row = array_fill_keys([
            'arrival_hour', 'arrival_minute', 'departure_hour', 'departure_minute',
            'break_departure_hour', 'break_departure_minute', 'break_arrival_hour',
            'break_arrival_minute', 'break_reason', 'note',
        ], null);

        if (!$attendance) {
            return $row;
        }

        $status = AttendanceStatus::fromCode($attendance->status);
        $worked = $status === AttendanceStatus::Work
            ? (float) $attendance->hours_worked
            : $status->workedHours();

        if ($worked >= AttendanceStatus::FULL_DAY_HOURS) {
            $row = array_merge($row, [
                'arrival_hour' => '08', 'arrival_minute' => '00',
                'departure_hour' => '16', 'departure_minute' => '00',
                'break_departure_hour' => '12', 'break_departure_minute' => '00',
                'break_arrival_hour' => '12', 'break_arrival_minute' => '30',
                'break_reason' => 'Obed',
            ]);
        }

        $row['note'] = $status->unworkedText();

        return $row;
    }
}
