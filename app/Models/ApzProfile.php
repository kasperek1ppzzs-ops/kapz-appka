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

    /**
     * Lokalita APZ = pôsobnosť (HLASENIE riadok 2). Samostatný stĺpec neexistuje,
     * starší kód a šablóny ho však používajú.
     */
    public function getCommunityScopeAttribute(): ?string
    {
        return $this->scope;
    }

    public function getCommunityNameAttribute(): ?string
    {
        return $this->scope;
    }

    /** Excel matica počíta vždy s plným úväzkom 7,5 h/deň. */
    public function getEmploymentRatioAttribute(): float
    {
        return 1.0;
    }
}
