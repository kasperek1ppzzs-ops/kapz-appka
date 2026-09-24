<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TravelPlanItem extends Model
{
    protected $fillable = [
        'travel_plan_id',
        'week_number',
        'trip_date',
        'departure_time',
        'arrival_at_dest_time',
        'departure_from_dest_time',
        'arrival_time',
        'departure_location',
        'destination_location',
        'purpose',
        'transport_mode',
        'target_apz_id',
        'estimated_km',
        'notes',
    ];

    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class, 'travel_plan_id');
    }

    public function targetApz(): BelongsTo
    {
        return $this->belongsTo(ApzProfile::class, 'target_apz_id');
    }

    public function travelOrder(): HasOne
    {
        return $this->hasOne(TravelOrder::class, 'travel_plan_item_id');
    }
}
