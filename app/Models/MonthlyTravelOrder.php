<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mesačný cestovný príkaz (hárok „Cestovný príkaz“) – jeden na KAPZ a mesiac.
 */
class MonthlyTravelOrder extends Model
{
    protected $fillable = [
        'kapz_id', 'reporting_period_id', 'travel_plan_id', 'order_number', 'status',
        'residence_address', 'residence_city', 'companions', 'vehicle', 'vehicle_plate', 'vehicle_model',
        'expected_costs', 'advance_amount', 'fuel_consumption', 'report_submitted_on',
        'meals_free', 'accommodation_free', 'discounted_ticket', 'note',
    ];

    protected $casts = [
        'advance_amount' => 'float',
        'expected_costs' => 'float',
        'fuel_consumption' => 'float',
        'report_submitted_on' => 'date',
        'meals_free' => 'boolean',
        'accommodation_free' => 'boolean',
        'discounted_ticket' => 'boolean',
    ];

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }

    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class, 'travel_plan_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(MonthlyTravelOrderSegment::class)->orderBy('trip_date')->orderBy('position');
    }

    public function dayTexts(): HasMany
    {
        return $this->hasMany(MonthlyTravelOrderDay::class);
    }
}
