<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;

/**
 * Mesačný fond pracovného času podľa hárku Kalendár (stĺpce AF/AG):
 * počet dní pondelok–piatok × 7,5 h. Sviatky sa z fondu neodpočítavajú –
 * v evidencii sa vykazujú ako neodpracovaný deň „Sviatok“ (7,5 h).
 */
class WorkingTimeFundService
{
    public function workingDays(int $year, int $month): int
    {
        $day = Carbon::create($year, $month, 1);
        $count = 0;

        while ($day->month === $month) {
            if (!$day->isWeekend()) {
                $count++;
            }
            $day->addDay();
        }

        return $count;
    }

    public function fundHours(int $year, int $month): float
    {
        return $this->workingDays($year, $month) * AttendanceStatus::FULL_DAY_HOURS;
    }
}
