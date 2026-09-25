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
        'initials',
        'scope',
        'region_expert',
        'phone',
        'email',
        'address',
        'residence_address',
        'residence_city',
        'vehicle_plate',
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

    /** Iba KAPZ, ktorých smie daný používateľ vidieť (pozri User::accessibleKapzIds). */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $ids = $user->accessibleKapzIds();

        return $ids === null ? $query : $query->whereIn('id', $ids);
    }

    public function expertAssignments(): HasMany
    {
        return $this->hasMany(ExpertKapzAssignment::class, 'kapz_id');
    }

    /**
     * Iniciály do čísla cestovného príkazu (HLASENIE!B42, napr. „LB“ pre „Bužová Lenka, Mgr.“).
     * Ak nie sú zadané, odvodia sa z mena: tituly sa vynechajú, pri tvare
     * „Priezvisko Meno, titul“ sa berie Meno + Priezvisko, inak prvé dve slová.
     */
    public function getInitialsCodeAttribute(): string
    {
        if ($this->initials) {
            return mb_strtoupper($this->initials);
        }

        $name = (string) $this->full_name;
        $isSurnameFirst = str_contains($name, ',');
        $words = array_values(array_filter(
            preg_split('/[\s,]+/u', $name),
            fn ($w) => $w !== '' && !str_contains($w, '.')
        ));

        if (count($words) < 2) {
            return mb_strtoupper(mb_substr($words[0] ?? 'X', 0, 2));
        }

        [$first, $second] = $isSurnameFirst ? [$words[1], $words[0]] : [$words[0], $words[1]];

        return mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($second, 0, 1));
    }
}
