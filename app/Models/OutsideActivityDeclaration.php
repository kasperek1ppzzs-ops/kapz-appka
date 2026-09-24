<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutsideActivityDeclaration extends Model
{
    protected $fillable = [
        'kapz_id',
        'reporting_period_id',
        'status',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutsideActivityDeclarationItem::class, 'declaration_id')->orderBy('order_num');
    }
}
