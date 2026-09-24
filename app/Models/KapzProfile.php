<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KapzProfile extends Model
{
    protected $fillable = [
        'user_id',
        'personal_number',
        'full_name',
        'scope',
        'region_expert',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ApzAssignment::class, 'kapz_id');
    }

    /**
     * Get active or historic APZs assigned to this KAPZ for a given date range or date.
     */
    public function assignedApzsForDate(string $date)
    {
        return ApzProfile::whereHas('assignments', function ($query) use ($date) {
            $query->where('kapz_id', $this->id)
                ->where('valid_from', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
                });
        })->get();
    }

    public function attendanceEntries(): HasMany
    {
        return $this->hasMany(AttendanceKapz::class, 'kapz_id');
    }

    public function travelPlans(): HasMany
    {
        return $this->hasMany(TravelPlan::class, 'kapz_id');
    }

    /** Východisková obec KAPZ = pôsobnosť (HLASENIE!B2). */
    public function getBaseMunicipalityAttribute(): ?string
    {
        return $this->scope;
    }

    /** Excel matica počíta vždy s plným úväzkom 7,5 h/deň. */
    public function getEmploymentRatioAttribute(): float
    {
        return 1.0;
    }
}
