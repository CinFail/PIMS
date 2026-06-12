<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /** Full audit trail with optional filters. */
    public function index(Request $request)
    {
        $module = $request->query('module');
        $action = $request->query('action');

        $logs = AuditLog::with('user')
            ->when($module, fn ($q) => $q->where('module_category', $module))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderByDesc('logged_at')
            ->paginate(25)
            ->withQueryString();

        $modules = AuditLog::select('module_category')->distinct()->orderBy('module_category')->pluck('module_category');

        return view('admin.audit.index', compact('logs', 'modules', 'module', 'action'));
    }

    /**
     * Role-specific dashboards (Patient / MedTech / Doctor / Receptionist).
     * Each one simply filters the audit trail by the matching module(s).
     */
    public function dashboard(string $role)
    {
        // Map each dashboard to the audit module names it should show.
        $map = [
            'patient'      => ['Patient', 'Appointments', 'Laboratory'],
            'medtech'      => ['Laboratory'],
            'doctor'       => ['Doctor', 'Consultations', 'Diagnoses', 'Prescriptions'],
            'receptionist' => ['Receptionist'],
        ];

        abort_unless(isset($map[$role]), 404);

        $logs = AuditLog::with('user')
            ->whereIn('module_category', $map[$role])
            ->orderByDesc('logged_at')
            ->paginate(25);

        $title = ucfirst($role).' Dashboard';

        return view('admin.audit.dashboard', compact('logs', 'title', 'role'));
    }
}
