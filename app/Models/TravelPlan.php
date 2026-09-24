<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelPlan extends Model
{
    protected $fillable = [
        'kapz_id',
        'reporting_period_id',
        'title',
        'status',
        'km_limit',
        'version',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_user_id',
        'admin_notes',
    ];

    /**
     * Travel order number matching official Excel format MM/YYYY/KAPZ_ID/ZK
     */
    public function getOrderNumberAttribute(): string
    {
        $month = str_pad($this->reportingPeriod->month, 2, '0', STR_PAD_LEFT);
        $year = $this->reportingPeriod->year;
        $id = $this->kapz->id ?? 1;
        return "{$month}/{$year}/{$id}/ZK";
    }

    /**
     * Calculate calendar week number for a given 1..5 week index of this reporting period
     */
    public function getCalendarWeek(int $weekNumber): int
    {
        $year = $this->reportingPeriod->year;
        $month = $this->reportingPeriod->month;
        
        // Find Monday of the first week of month
        $firstDayOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1);
        $firstMonday = $firstDayOfMonth->copy()->startOfWeek();
        
        $targetWeekStart = $firstMonday->copy()->addWeeks($weekNumber - 1);
        return (int) $targetWeekStart->isoWeek();
    }

    public function getTotalKmAttribute(): float
    {
        return (float) $this->items()->sum('estimated_km');
    }

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TravelPlanItem::class, 'travel_plan_id');
    }
}
