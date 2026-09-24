<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ApzAssignment;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminManagementController extends Controller
{
    public function listKapz()
    {
        $kapzs = KapzProfile::with('user')->get();
        return view('admin.kapz_index', compact('kapzs'));
    }

    public function listApz()
    {
        $apzs = ApzProfile::with('assignments.kapz')->get();
        return view('admin.apz_index', compact('apzs'));
    }

    public function listAssignments()
    {
        $assignments = ApzAssignment::with(['apz', 'kapz'])->orderBy('valid_from', 'desc')->get();
        $kapzs = KapzProfile::all();
        $apzs = ApzProfile::all();
        return view('admin.assignments', compact('assignments', 'kapzs', 'apzs'));
    }

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'apz_id' => ['required', 'exists:apz_profiles,id'],
            'kapz_id' => ['required', 'exists:kapz_profiles,id'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        // Close any currently active assignment for this APZ if valid_to is set
        ApzAssignment::where('apz_id', $request->apz_id)
            ->whereNull('valid_to')
            ->update(['valid_to' => date('Y-m-d', strtotime($request->valid_from . ' -1 day'))]);

        ApzAssignment::create([
            'apz_id' => $request->apz_id,
            'kapz_id' => $request->kapz_id,
            'valid_from' => $request->valid_from,
            'valid_to' => $request->valid_to,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Priradenie APZ ku KAPZ bolo úspešne uložené.');
    }

    public function listAuditLogs()
    {
        $logs = AuditLog::with('user')->orderBy('id', 'desc')->paginate(50);
        return view('admin.audit_logs', compact('logs'));
    }
}
