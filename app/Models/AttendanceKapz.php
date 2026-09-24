<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceKapz extends Model
{
    protected $table = 'attendance_kapz';

    protected $fillable = [
        'kapz_id',
        'reporting_period_id',
        'date',
        'workplace',
        'status',
        'hours_worked',
        'overtime_hours',
        'activity_description',
        'notes',
    ];

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }
}
