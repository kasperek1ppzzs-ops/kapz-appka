<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jeden úsek cesty = dvojica riadkov Odchod/Príchod v hárku „Plán pracovných ciest“.
 */
class TravelPlanSegment extends Model
{
    protected $fillable = [
        'travel_plan_item_id',
        'position',
        'from_place',
        'departure_time',
        'to_place',
        'arrival_time',
        'transport_mode',
        'km',
        'purpose',
        'description',
        'accommodation',
        'companions',
    ];

    protected $casts = ['km' => 'float'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(TravelPlanItem::class, 'travel_plan_item_id');
    }
}
