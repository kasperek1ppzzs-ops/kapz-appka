<?php

namespace App\Services;

use App\Models\TravelPlan;
use App\Models\AuditLog;
use App\Models\KmLimit;
use App\Models\TravelPurpose;
use Illuminate\Support\Facades\Auth;

class TravelWorkflowService
{
    /**
     * Oficiálne účely ciest KAPZ (Excel GENERATOR!Q14:Q32) – krátky názov ako v Pláne (stĺpec H).
     *
     * @return array<int, string>
     */
    public function getOfficialPurposes(): array
    {
        return TravelPurpose::where('role', 'KAPZ')
            ->where('is_active', true)
            ->orderBy('code')
            ->pluck('title')
            ->all();
    }

    /**
     * Mesačný limit km podľa pôsobnosti (Excel: SUMIF nad hárkom „limity a prac. dni“).
     * Hľadá sa presná zhoda názvu pôsobnosti – nie podreťazec, aby „Košice“ nedostali
     * limit „Košice-okolie“. Neznáma pôsobnosť = 0 (limit nie je stanovený), rovnako ako v Exceli.
     */
    public function getLimitForScope(?string $scope): float
    {
        $key = self::normalizeScope($scope);
        if ($key === '') {
            return 0.0;
        }

        foreach (KmLimit::all() as $limit) {
            if (self::normalizeScope($limit->scope) === $key) {
                return (float) $limit->monthly_km;
            }
        }

        return 0.0;
    }

    /**
     * „Košice - okolie“ = „Košice-okolie“, „Velký Krtíš“ = „Veľký Krtíš“, bez ohľadu na veľkosť písmen.
     */
    public static function normalizeScope(?string $scope): string
    {
        $value = mb_strtolower(trim((string) $scope));
        $value = preg_replace('/\s*-\s*/u', '-', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return str_replace('velký', 'veľký', $value);
    }

    /**
     * Submit travel plan for approval.
     */
    public function submitPlan(TravelPlan $plan): bool
    {
        if (!in_array($plan->status, ['DRAFT', 'RETURNED'])) {
            return false;
        }

        $oldStatus = $plan->status;
        $plan->update([
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'SUBMIT_TRAVEL_PLAN',
            'entity_type' => TravelPlan::class,
            'entity_id' => $plan->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'SUBMITTED'],
        ]);

        return true;
    }

    /**
     * Approve travel plan.
     */
    public function approvePlan(TravelPlan $plan, ?string $adminNotes = null): bool
    {
        if ($plan->status !== 'SUBMITTED') {
            return false;
        }

        $plan->update([
            'status' => 'APPROVED',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => Auth::id(),
            'admin_notes' => $adminNotes,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'APPROVE_TRAVEL_PLAN',
            'entity_type' => TravelPlan::class,
            'entity_id' => $plan->id,
            'new_values' => ['status' => 'APPROVED', 'notes' => $adminNotes],
        ]);

        return true;
    }

    /**
     * Return travel plan for revisions.
     */
    public function returnPlan(TravelPlan $plan, string $adminNotes): bool
    {
        if ($plan->status !== 'SUBMITTED') {
            return false;
        }

        $plan->update([
            'status' => 'RETURNED',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => Auth::id(),
            'admin_notes' => $adminNotes,
            'version' => $plan->version + 1,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'RETURN_TRAVEL_PLAN',
            'entity_type' => TravelPlan::class,
            'entity_id' => $plan->id,
            'new_values' => ['status' => 'RETURNED', 'notes' => $adminNotes, 'version' => $plan->version],
        ]);

        return true;
    }
}
