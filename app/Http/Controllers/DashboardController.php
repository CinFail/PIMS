<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\LabRequest;
use App\Models\LabResultRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $role = $user->primaryRole();

        return match ($role) {
            'super_admin'  => $this->adminDashboard(),
            'doctor'       => $this->doctorDashboard(),
            'med_tech'     => $this->medtechDashboard(),
            'receptionist' => $this->receptionistDashboard(),
            default        => $this->patientDashboard(),
        };
    }

    private function patientDashboard()
    {
        $patient = Auth::user()->patientProfile;
        $appointments = $patient
            ? Appointment::with(['doctor.user', 'status'])
                ->where('patient_id', $patient->patient_id)
                ->where('is_voided', 0)
                ->orderByDesc('appointment_at')
                ->limit(5)->get()
            : collect();

        return view('dashboard.patient', compact('appointments'));
    }

    private function doctorDashboard()
    {
        $doctor = Auth::user()->doctorProfile;
        $today = now()->toDateString();

        $todayCount = $doctor
            ? Appointment::where('doctor_id', $doctor->doctor_id)
                ->where('is_voided', 0)
                ->whereDate('appointment_at', $today)->count()
            : 0;

        $upcoming = $doctor
            ? Appointment::with(['patient.user', 'status'])
                ->where('doctor_id', $doctor->doctor_id)
                ->where('is_voided', 0)
                ->where('appointment_at', '>=', now()->startOfDay())
                ->orderBy('appointment_at')
                ->limit(5)->get()
            : collect();

        return view('dashboard.doctor', compact('todayCount', 'upcoming'));
    }

    private function medtechDashboard()
    {
        $pendingRequests = LabRequest::where('is_voided', 0)
            ->whereIn('status', ['Pending', 'Processing'])->count();
        $softCopyRequests = LabResultRequest::where('status', 'Pending')->count();

        return view('dashboard.medtech', compact('pendingRequests', 'softCopyRequests'));
    }

    private function receptionistDashboard()
    {
        $patientCount = User::whereHas('roles', fn ($q) => $q->where('name', 'patient'))->count();

        return view('dashboard.receptionist', compact('patientCount'));
    }

    private function adminDashboard()
    {
        $userCount = User::count();
        $recentLogs = AuditLog::with('user')->orderByDesc('logged_at')->limit(10)->get();

        return view('dashboard.admin', compact('userCount', 'recentLogs'));
    }
}
