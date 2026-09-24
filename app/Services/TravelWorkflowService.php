<?php

namespace App\Services;

use App\Models\TravelPlan;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class TravelWorkflowService
{
    public const OFFICIAL_PURPOSES = [
        'Odborná príprava, vedenie a konzultácia APZ pri plnení cieľov aktivít NP Zdravé komunity.',
        'Pravidelné stretnutia KAPZ s APZ v rámci celej spádovej oblasti a riešenie a výmena informácií pri realizácii aktivít projektu v jednotlivých lokalitách.',
        'Priama účasť KAPZ pri príprave a samotnej realizácii programov podpory zdravia v marginalizovaných rómskych komunitách.',
        'Priama účasť KAPZ pri riešení krízových situácií v teréne.',
        'Priama účasť Koordinátora asistentov podpory zdravia na pracovných stretnutiach týkajúcich sa prezentácie činnosti projektu.',
        'Účasť KAPZ na školení alebo zabezpečenie účasti APZ na vzdelávacích aktivitách projektu (školenie, prednáška).',
        'Vzájomná spolupráca s iným koordinátorom (koordinátormi) asistentov podpory zdravia pri zabezpečení cieľov a aktivít NP ZK.',
    ];

    public const REGION_KM_LIMITS = [
        'Banská Bystrica' => 1250,
        'Zvolen' => 1250,
        'Michalovce' => 535,
        'Veľké Kapušany' => 300,
        'Nitra' => 1365,
        'Veľký Krtíš' => 1035,
        'Snina' => 650,
        'Bardejov' => 420,
        'Stará Ľubovňa' => 255,
        'Humenné' => 385,
        'Vranov nad Topľou' => 240,
        'Rimavská Sobota' => 970,
        'Fiľakovo' => 340,
        'Trebišov' => 930,
        'Gelnica' => 760,
        'Prešov' => 520,
        'Poprad' => 400,
        'Poprad - okolie' => 745,
        'Spišská Nová Ves' => 525,
        'Levoča' => 525,
        'Sabinov' => 245,
        'Kežmarok' => 365,
        'Košice-okolie' => 530,
        'Rožňava' => 455,
        'Revúca' => 620,
        'Košice' => 380,
        'Svidník' => 695,
        'Malacky' => 950,
        'Senica' => 340,
        'Veľký Šariš' => 340,
        'Trhovište' => 440,
        'Nové Zámky' => 770,
        'Ľubotín' => 275,
        'Moldava nad Bodvou' => 390,
    ];

    public function getOfficialPurposes(): array
    {
        return self::OFFICIAL_PURPOSES;
    }

    public function getLimitForScope(?string $scope): float
    {
        if (!$scope) {
            return 500.0;
        }

        foreach (self::REGION_KM_LIMITS as $region => $limit) {
            if (mb_stripos($scope, $region) !== false || mb_stripos($region, $scope) !== false) {
                return (float) $limit;
            }
        }

        return 500.0;
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
