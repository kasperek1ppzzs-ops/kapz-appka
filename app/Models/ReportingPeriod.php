<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportingPeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'is_closed',
        'closed_at',
        'closed_by_user_id',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function attendanceKapz(): HasMany
    {
        return $this->hasMany(AttendanceKapz::class, 'reporting_period_id');
    }

    public function attendanceApz(): HasMany
    {
        return $this->hasMany(AttendanceApz::class, 'reporting_period_id');
    }

    public function getFormattedNameAttribute(): string
    {
        $months = [
            1 => 'Január', 2 => 'Február', 3 => 'Marec', 4 => 'Apríl',
            5 => 'Máj', 6 => 'Jún', 7 => 'Júl', 8 => 'August',
            9 => 'September', 10 => 'Október', 11 => 'November', 12 => 'December'
        ];
        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}
