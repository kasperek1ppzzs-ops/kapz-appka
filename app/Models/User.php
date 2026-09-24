<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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

    public function isKapz(): bool
    {
        return $this->role === 'kapz';
    }

    public function kapzProfile(): HasOne
    {
        return $this->hasOne(KapzProfile::class, 'user_id');
    }
}
