<?php

namespace App\Http\Controllers;

use App\Models\ApzAssignment;
use App\Models\KapzProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * KAPZ, s ktorým používateľ pracuje: nadriadený si ho vyberá cez `kapz_id`
     * (expert iba z KAPZ, ktorých mu pridelil admin), KAPZ vždy dostane iba vlastný profil.
     */
    protected function resolveKapz(Request $request): KapzProfile
    {
        $user = Auth::user();

        if ($user->isSupervisor()) {
            if ($request->filled('kapz_id')) {
                $this->authorizeKapzAccess($request->input('kapz_id'));

                return KapzProfile::findOrFail($request->input('kapz_id'));
            }

            $kapz = KapzProfile::visibleTo($user)->orderBy('id')->first();
            abort_if(!$kapz, 403, 'Nemáte pridelených žiadnych KAPZ.');

            return $kapz;
        }

        $kapz = $user->kapzProfile;
        abort_if(!$kapz, 403, 'K účtu nie je priradený profil KAPZ.');

        if ($request->filled('kapz_id') && (int) $request->input('kapz_id') !== $kapz->id) {
            abort(403, 'Nemáte prístup k údajom iného KAPZ.');
        }

        return $kapz;
    }

    /**
     * Zabráni pracovať s dokumentmi KAPZ, ku ktorému používateľ nemá prístup (ochrana IDOR).
     */
    protected function authorizeKapzAccess(int|string|null $kapzId): void
    {
        abort_if(!Auth::user()->canAccessKapz($kapzId), 403, 'Nemáte prístup k údajom tohto KAPZ.');
    }

    /**
     * APZ musí byť (aktuálne alebo historicky) priradený danému KAPZ.
     * Admin a manažment môžu otvoriť ľubovoľného APZ.
     */
    protected function authorizeApzAccess(int|string|null $kapzId, int|string|null $apzId): void
    {
        $this->authorizeKapzAccess($kapzId);

        if (Auth::user()->accessibleKapzIds() === null) {
            return;
        }

        $assigned = ApzAssignment::where('kapz_id', $kapzId)->where('apz_id', $apzId)->exists();
        abort_if(!$assigned, 403, 'APZ nie je priradený k tomuto KAPZ.');
    }

    /** ID KAPZ viditeľných pre prihláseného používateľa; null = všetci. */
    protected function visibleKapzIds(): ?array
    {
        return Auth::user()->accessibleKapzIds();
    }
}
