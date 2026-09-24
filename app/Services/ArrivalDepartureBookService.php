<?php

namespace App\Services;

use App\Models\ArrivalDepartureBook;
use App\Models\ArrivalDepartureBookItem;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ReportingPeriod;
use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
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
            $location = $kapz->base_municipality ?? 'Banská Bystrica';
            $approverName = $kapz->region_expert ?? 'Mgr. Ľudmila Grešková';
        } else {
            $apz = ApzProfile::findOrFail($personId);
            $personName = $apz->full_name;
            $personalNumber = $apz->personal_number;
            $location = $apz->community_name ?? 'Lokalita APZ';
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
                'project_code' => '401405DUQ8',
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

        // Slovak Public Holidays
        $holidays = [
            '01-01', '01-06', '05-01', '05-08', '07-05', '08-29', '09-01', '09-15', '11-01', '11-17', '12-24', '12-25', '12-26'
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($period->year, $period->month, $day);
            $isWeekend = $date->isWeekend();
            $monthDay = $date->format('m-d');
            $isOfficialHoliday = in_array($monthDay, $holidays);

            $att = $attendanceMap[$day] ?? null;

            $arrivalHour = null;
            $arrivalMin = null;
            $departureHour = null;
            $departureMin = null;
            $breakDepHour = null;
            $breakDepMin = null;
            $breakArrHour = null;
            $breakArrMin = null;
            $breakReason = null;
            $visitedLocation = null; // As requested, visited locations are kept empty
            $approvedBy = null;
            $note = null;

            if ($att) {
                $status = strtoupper($att->status ?? '');
                $hoursWorked = (float)($att->hours_worked ?? 0);

                if ($status === 'SVIATOK' || $isOfficialHoliday) {
                    $note = 'Sviatok';
                } elseif ($status === 'DOVOLENKA') {
                    $breakReason = 'Dovolenka';
                    $note = 'Dovolenka';
                } elseif ($status === 'PN') {
                    $breakReason = 'PN';
                    $note = 'PN';
                } elseif ($status === 'OCR' || $status === 'OČR') {
                    $breakReason = 'OČR';
                    $note = 'OČR';
                } elseif ($status === 'LEKAR' || $status === 'LEKÁR') {
                    $breakReason = 'Lekár';
                    $note = 'Lekár';
                } elseif ($hoursWorked > 0 || $status === 'WORK' || $status === 'PRÁCA') {
                    // Standard workday: 8:00 - 16:00, lunch break 12:00 - 12:30 (net 7.5h)
                    $arrivalHour = '08';
                    $arrivalMin = '00';
                    $departureHour = '16';
                    $departureMin = '00';
                    $breakDepHour = '12';
                    $breakDepMin = '00';
                    $breakArrHour = '12';
                    $breakArrMin = '30';
                    $breakReason = 'Obed';
                } elseif ($isWeekend) {
                    $note = 'Víkend';
                }
            } else {
                if ($isOfficialHoliday) {
                    $note = 'Sviatok';
                } elseif ($isWeekend) {
                    $note = 'Víkend';
                } else {
                    // Default weekday if no explicit attendance record yet
                    $arrivalHour = '08';
                    $arrivalMin = '00';
                    $departureHour = '16';
                    $departureMin = '00';
                    $breakDepHour = '12';
                    $breakDepMin = '00';
                    $breakArrHour = '12';
                    $breakArrMin = '30';
                    $breakReason = 'Obed';
                }
            }

            ArrivalDepartureBookItem::create([
                'book_id' => $book->id,
                'day_number' => $day,
                'record_date' => $date->format('Y-m-d'),
                'arrival_hour' => $arrivalHour,
                'arrival_minute' => $arrivalMin,
                'departure_hour' => $departureHour,
                'departure_minute' => $departureMin,
                'break_departure_hour' => $breakDepHour,
                'break_departure_minute' => $breakDepMin,
                'break_arrival_hour' => $breakArrHour,
                'break_arrival_minute' => $breakArrMin,
                'break_reason' => $breakReason,
                'visited_location' => $visitedLocation,
                'approved_by' => $approvedBy,
                'note' => $note,
            ]);
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
}
