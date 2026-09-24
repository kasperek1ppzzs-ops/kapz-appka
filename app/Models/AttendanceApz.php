<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceApz extends Model
{
    protected $table = 'attendance_apz';

    protected $fillable = [
        'apz_id',
        'kapz_id',
        'reporting_period_id',
        'date',
        'workplace',
        'status',
        'hours_worked',
        'activity_description',
        'notes',
    ];

    public function apz(): BelongsTo
    {
        return $this->belongsTo(ApzProfile::class, 'apz_id');
    }

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }
}
