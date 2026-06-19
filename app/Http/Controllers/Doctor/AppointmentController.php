<?php

namespace App\Http\Controllers\Doctor;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /** Scheduled check-ups: own queue for doctors, all appointments for admin. */
    public function index()
    {
        $doctor = Auth::user()->doctorProfile;

        if ($doctor) {
            $appointments = Appointment::with(['patient.user', 'status'])
                ->where('doctor_id', $doctor->doctor_id)
                ->where('is_voided', 0)
                ->where('appointment_at', '>=', now())
                ->orderBy('appointment_at')
                ->get();
            $byDoctor = null;
        } else {
            $appointments = collect();
            $byDoctor = Appointment::with(['patient.user', 'doctor.user', 'status'])
                ->where('is_voided', 0)
                ->whereDate('appointment_at', today())
                ->orderBy('appointment_at')
                ->get()
                ->groupBy('doctor_id');
        }

        $statuses = AppointmentStatus::orderBy('appointment_status_id')->get();

        return view('doctor.appointments', compact('appointments', 'doctor', 'byDoctor', 'statuses'));
    }

    /** Update the status of one appointment (doctor's own appointments only). */
    public function updateStatus(Request $request, int $appointmentId)
    {
        $data = $request->validate([
            'status_name' => ['required', 'string', 'in:Scheduled,Confirmed,In Progress,Completed,Cancelled,No Show'],
        ]);

        $appointment = Appointment::with('status')->findOrFail($appointmentId);
        $doctor      = Auth::user()->doctorProfile;

        abort_unless($doctor, 403, 'Your doctor profile is missing.');
        abort_unless($appointment->doctor_id === $doctor->doctor_id, 403, 'This is not your appointment.');

        $status = AppointmentStatus::where('status_name', $data['status_name'])->firstOrFail();
        $old    = $appointment->status?->status_name;

        $appointment->update(['status_id' => $status->appointment_status_id]);

        AuditLogger::log('UPDATE', 'Appointments', 'appointments', $appointment->appointment_id,
            "Doctor changed appointment status from {$old} to {$data['status_name']}");

        return back()->with('status', "Appointment marked as {$data['status_name']}.");
    }
}
