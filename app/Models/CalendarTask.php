<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarTask extends Model
{
    protected $fillable = [
        'assigned_by_user_id',
        'kapz_id',
        'target_scope',
        'task_date',
        'start_time',
        'end_time',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'completed_at',
        'completion_note',
    ];

    protected $casts = [
        'task_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function kapz(): BelongsTo
    {
        return $this->belongsTo(KapzProfile::class, 'kapz_id');
    }

    public function getPriorityBadgeAttribute(): array
    {
        return match($this->priority) {
            'URGENT' => ['label' => 'Kritická / Urgentná', 'bg' => 'bg-rose-100 text-rose-800 border-rose-200', 'dot' => 'bg-rose-500'],
            'HIGH' => ['label' => 'Vysoká priorita', 'bg' => 'bg-amber-100 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
            'LOW' => ['label' => 'Nízka priorita', 'bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400'],
            default => ['label' => 'Štandardná', 'bg' => 'bg-blue-100 text-blue-800 border-blue-200', 'dot' => 'bg-blue-500'],
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'EXPERT_DIRECTIVE' => '🎯 Pokyn Experta pre terén',
            'CONTROL' => '🔍 Kontrola a monitoring APZ',
            'DEADLINE' => '⏰ Termín odovzdania / Uzávierka',
            'TRAINING' => '🎓 Školenie a porada',
            default => '📝 Úloha / Poznámka',
        };
    }
}
