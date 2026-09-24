<?php

namespace App\Http\Controllers;

use App\Models\ApzAssignment;
use App\Models\KapzProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * KAPZ, s ktorým používateľ pracuje: nadriadený si ho vyberá cez `kapz_id`,
     * KAPZ vždy dostane iba vlastný profil.
     */
    protected function resolveKapz(Request $request): KapzProfile
    {
        $user = Auth::user();

        if ($user->isSupervisor()) {
            return $request->filled('kapz_id')
                ? KapzProfile::findOrFail($request->input('kapz_id'))
                : KapzProfile::orderBy('id')->firstOrFail();
        }

        $kapz = $user->kapzProfile;
        abort_if(!$kapz, 403, 'K účtu nie je priradený profil KAPZ.');

        if ($request->filled('kapz_id') && (int) $request->input('kapz_id') !== $kapz->id) {
            abort(403, 'Nemáte prístup k údajom iného KAPZ.');
        }

        return $kapz;
    }

    /**
     * Zabráni KAPZ pracovať s dokumentmi iného KAPZ (ochrana IDOR).
     */
    protected function authorizeKapzAccess(int|string|null $kapzId): void
    {
        $user = Auth::user();

        if ($user->isSupervisor()) {
            return;
        }

        abort_if(
            !$user->kapzProfile || (int) $kapzId !== $user->kapzProfile->id,
            403,
            'Nemáte prístup k údajom iného KAPZ.'
        );
    }

    /**
     * APZ musí byť (aktuálne alebo historicky) priradený danému KAPZ.
     */
    protected function authorizeApzAccess(int|string|null $kapzId, int|string|null $apzId): void
    {
        $this->authorizeKapzAccess($kapzId);

        if (Auth::user()->isSupervisor()) {
            return;
        }

        $assigned = ApzAssignment::where('kapz_id', $kapzId)->where('apz_id', $apzId)->exists();
        abort_if(!$assigned, 403, 'APZ nie je priradený k tomuto KAPZ.');
    }
}
