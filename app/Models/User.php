<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'personal_number',
        'scope',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Admin, Expert pre terén alebo manažment – vidia a schvaľujú dokumentáciu všetkých KAPZ.
     */
    public function isSupervisor(): bool
    {
        return in_array($this->role, ['admin', 'expert', 'manager'], true);
    }

    public function isExpert(): bool
    {
        return $this->role === 'expert';
    }

    /**
     * ID KAPZ, ktorých údaje smie používateľ vidieť; null = všetci (admin, manažment).
     * Expert vidí iba KAPZ, ktorých mu admin pridelil (platné k dnešnému dňu),
     * KAPZ iba seba.
     *
     * @return array<int, int>|null
     */
    public function accessibleKapzIds(): ?array
    {
        if (in_array($this->role, ['admin', 'manager'], true)) {
            return null;
        }

        if ($this->isExpert()) {
            return $this->expertAssignments()
                ->activeOn(now()->toDateString())
                ->pluck('kapz_id')
                ->unique()
                ->values()
                ->all();
        }

        $own = $this->kapzProfile?->id;

        return $own ? [$own] : [];
    }

    public function canAccessKapz(int|string|null $kapzId): bool
    {
        $ids = $this->accessibleKapzIds();

        return $ids === null || in_array((int) $kapzId, $ids, true);
    }

    public function expertAssignments(): HasMany
    {
        return $this->hasMany(ExpertKapzAssignment::class, 'expert_user_id');
    }

    public function isKapz(): bool
    {
        return $this->role === 'kapz';
    }

    public function kapzProfile(): HasOne
    {
        return $this->hasOne(KapzProfile::class, 'user_id');
    }
}
