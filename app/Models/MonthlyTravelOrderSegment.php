<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Úsek v tabuľke vyúčtovania cestovného príkazu (dvojica riadkov Odchod/Príchod, R85:AE268).
 */
class MonthlyTravelOrderSegment extends Model
{
    protected $fillable = [
        'monthly_travel_order_id', 'travel_plan_segment_id', 'trip_date', 'position',
        'from_place', 'departure_time', 'to_place', 'arrival_time', 'transport_mode', 'km',
        'work_time', 'purpose', 'fuel_price', 'amortization', 'meals', 'accommodation_cost',
        'other_costs', 'adjusted',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'km' => 'float',
        'fuel_price' => 'float',
        'amortization' => 'float',
        'meals' => 'float',
        'accommodation_cost' => 'float',
        'other_costs' => 'float',
        'adjusted' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MonthlyTravelOrder::class, 'monthly_travel_order_id');
    }
}
