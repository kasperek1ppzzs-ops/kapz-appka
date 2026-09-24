<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalDepartureBookItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'day_number',
        'record_date',
        'arrival_hour',
        'arrival_minute',
        'departure_hour',
        'departure_minute',
        'break_departure_hour',
        'break_departure_minute',
        'break_arrival_hour',
        'break_arrival_minute',
        'break_reason',
        'visited_location',
        'approved_by',
        'note',
    ];

    protected $casts = [
        'record_date' => 'date',
        'day_number' => 'integer',
    ];

    public function book()
    {
        return $this->belongsTo(ArrivalDepartureBook::class, 'book_id');
    }
}
