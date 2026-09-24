<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApzProfile extends Model
{
    protected $fillable = [
        'personal_number',
        'full_name',
        'scope',
        'position',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ApzAssignment::class, 'apz_id');
    }

    public function attendanceEntries(): HasMany
    {
        return $this->hasMany(AttendanceApz::class, 'apz_id');
    }
}
