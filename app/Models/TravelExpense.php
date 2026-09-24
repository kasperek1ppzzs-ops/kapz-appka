<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelExpense extends Model
{
    protected $fillable = [
        'travel_order_id',
        'kapz_id',
        'total_km',
        'rate_per_km',
        'total_km_compensation',
        'diem_compensation',
        'accommodation_costs',
        'other_expenses',
        'advance_deducted',
        'final_balance',
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
