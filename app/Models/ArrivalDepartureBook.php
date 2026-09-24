<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalDepartureBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'kapz_id',
        'reporting_period_id',
        'person_type',
        'person_id',
        'personal_number',
        'full_name',
        'location',
        'project_code',
        'approver_name',
        'status',
    ];

    public function kapz()
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function reportingPeriod()
    {
        return $this->belongsTo(ReportingPeriod::class, 'reporting_period_id');
    }

    public function items()
    {
        return $this->hasMany(ArrivalDepartureBookItem::class, 'book_id')->orderBy('day_number');
    }
}
