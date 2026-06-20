<?php

namespace App\Http\Controllers\Doctor;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\DoctorDutySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    // doctor sees own queue; admin sees everything
    public function index()
    {
        $user   = Auth::user();
        $doctor = $user->doctorProfile;

        if ($doctor) {
            $appointments = Appointment::with(['patient.user', 'status'])
                ->where('doctor_id', $doctor->doctor_id)
                ->where('is_voided', 0)
                ->where('appointment_at', '>=', now())
                ->orderByRaw('CASE WHEN status_id IN (1, 7) THEN 0 ELSE 1 END')
                ->orderBy('appointment_at')
                ->get();
        } else {
            $appointments = Appointment::with(['patient.user', 'doctor.user', 'status'])
                ->where('is_voided', 0)
                ->orderByRaw('CASE WHEN status_id IN (1, 7) THEN 0 ELSE 1 END')
                ->orderBy('appointment_at')
                ->paginate(25);
        }

        $availableSessions = DoctorDutySession::with('doctor.user')
            ->where('is_voided', 0)
            ->where('status', 'Scheduled')
            ->whereDate('duty_date', '>=', now()->toDateString())
            ->whereHas('doctor', fn ($q) => $q->where('is_active', 1))
            ->whereDoesntHave('appointments', fn ($q) => $q->where('is_voided', 0)->whereNotIn('status_id', [4, 5, 6, 7]))
            ->orderBy('duty_date')->orderBy('start_time')
            ->get();

        return view('doctor.appointments', compact('appointments', 'doctor', 'availableSessions'));
    }

    // status 7 (rescheduled) updates appointment_at in-place, no new row
    // terminal statuses 4-7 free the slot because isTaken() excludes them
    public function updateStatus(Request $request, int $appointmentId)
    {
        $rules = [
            'status_id' => ['required', 'integer', 'in:4,5,6,7'],
        ];

        if ($request->input('status_id') == 7) {
            $rules['duty_session_id'] = ['required', 'integer', 'exists:doctor_duty_sessions,duty_session_id'];
        }

        $data = $request->validate($rules);

        $appointment = Appointment::with('status')->findOrFail($appointmentId);
        $user        = Auth::user();
        $doctor      = $user->doctorProfile;
        $isAdmin     = $user->hasRole('super_admin');

        abort_unless(
            $isAdmin || ($doctor && $appointment->doctor_id === $doctor->doctor_id),
            403, 'This is not your appointment.'
        );

        if ((int) $data['status_id'] === 7) {
            DB::transaction(function () use ($data, $appointment) {
                $session = DoctorDutySession::with('doctor')
                    ->lockForUpdate()
                    ->findOrFail($data['duty_session_id']);

                if ($session->isTaken()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'duty_session_id' => 'That duty session was just taken. Please select another.',
                    ]);
                }

                $appointment->update([
                    'duty_session_id' => $session->duty_session_id,
                    'doctor_id'       => $session->doctor_id,
                    'appointment_at'  => $session->duty_date->toDateString().' '.$session->start_time,
                    'status_id'       => 7,
                ]);
            });

            AuditLogger::log('UPDATE', 'Appointments', 'appointments', $appointment->appointment_id,
                "Appointment rescheduled to duty session #{$data['duty_session_id']}");

            return back()->with('status', 'Appointment rescheduled successfully.');
        }

        $status    = AppointmentStatus::findOrFail($data['status_id']);
        $oldStatus = $appointment->status?->status_name;
        $appointment->update(['status_id' => $data['status_id']]);

        AuditLogger::log('UPDATE', 'Appointments', 'appointments', $appointment->appointment_id,
            "Changed appointment status from {$oldStatus} to {$status->status_name}");

        return back()->with('status', "Appointment marked as {$status->status_name}.");
    }
}
