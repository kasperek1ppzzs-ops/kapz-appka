<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReport extends Model
{
    protected $fillable = [
        'kapz_id',
        'reporting_period_id',
        'summary_text',
        'metrics_json',
    ];

    protected $casts = [
        'metrics_json' => 'array',
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
