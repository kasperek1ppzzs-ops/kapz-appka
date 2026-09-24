<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportingPeriod;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPeriodController extends Controller
{
    public function togglePeriodLock(Request $request, ReportingPeriod $period)
    {
        $newState = !$period->is_closed;
        $period->update([
            'is_closed' => $newState,
            'closed_at' => $newState ? now() : null,
            'closed_by_user_id' => $newState ? Auth::id() : null,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $newState ? 'PERIOD_LOCKED' : 'PERIOD_UNLOCKED',
            'auditable_type' => ReportingPeriod::class,
            'auditable_id' => $period->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => json_encode(['period' => $period->formatted_name, 'is_closed' => $newState]),
        ]);

        $message = $newState ? 'Obdobie ' . $period->formatted_name . ' bolo úspešne uzamknuté.' : 'Obdobie ' . $period->formatted_name . ' bolo odomknuté na úpravy.';
        return back()->with('success', $message);
    }
}
