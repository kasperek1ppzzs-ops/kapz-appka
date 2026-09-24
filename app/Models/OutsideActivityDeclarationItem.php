<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideActivityDeclarationItem extends Model
{
    protected $fillable = [
        'declaration_id',
        'order_num',
        'person_type',
        'person_id',
        'personal_number',
        'full_name',
        'has_gainful_activity',
        'is_funded_by_esif',
        'contract_type',
        'signature_date',
        'signature_status',
    ];

    protected $casts = [
        'signature_date' => 'date',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(OutsideActivityDeclaration::class, 'declaration_id');
    }
}
