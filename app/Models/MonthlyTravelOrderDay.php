<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Texty správ k jednému dňu cestovného príkazu (Správa z pracovnej cesty, GENERATOR). */
class MonthlyTravelOrderDay extends Model
{
    protected $fillable = ['monthly_travel_order_id', 'trip_date', 'report_text', 'conclusion'];

    protected $casts = ['trip_date' => 'date'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MonthlyTravelOrder::class, 'monthly_travel_order_id');
    }
}
