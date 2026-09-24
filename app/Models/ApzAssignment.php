<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApzAssignment extends Model
{
    protected $fillable = [
        'apz_id',
        'kapz_id',
        'valid_from',
        'valid_to',
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
}
