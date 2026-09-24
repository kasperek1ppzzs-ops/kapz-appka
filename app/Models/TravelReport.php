<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelReport extends Model
{
    protected $fillable = [
        'travel_order_id',
        'kapz_id',
        'report_date',
        'summary_of_activities',
        'outcomes',
        'issues_encountered',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class, 'travel_order_id');
    }

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }
}
