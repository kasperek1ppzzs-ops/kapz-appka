<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TravelOrder extends Model
{
    protected $fillable = [
        'kapz_id',
        'travel_plan_item_id',
        'order_number',
        'departure_datetime',
        'arrival_datetime',
        'departure_place',
        'destination_place',
        'purpose',
        'transport_means',
        'advance_amount',
        'status',
    ];

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function travelPlanItem(): BelongsTo
    {
        return $this->belongsTo(TravelPlanItem::class, 'travel_plan_item_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(TravelReport::class, 'travel_order_id');
    }

    public function expense(): HasOne
    {
        return $this->hasOne(TravelExpense::class, 'travel_order_id');
    }
}
