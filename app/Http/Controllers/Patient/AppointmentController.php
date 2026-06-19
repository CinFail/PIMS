<?php

namespace App\Http\Controllers\Patient;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\DoctorDutySession;
use App\Models\LabAppointment;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTest;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    /** List the patient's own appointments. */
    public function index()
    {
        $patient = $this->patient();

        $appointments = Appointment::with(['doctor.user', 'status'])
            ->where('patient_id', $patient->patient_id)
            ->where('is_voided', 0)
            ->orderByDesc('appointment_at')
            ->get();

        $labAppointments = LabAppointment::with('labRequest.items.test')
            ->where('patient_id', $patient->patient_id)
            ->orderByDesc('scheduled_at')
            ->get();

        return view('patient.appointments', compact('appointments', 'labAppointments'));
    }

    /** Show the booking form: open slots and lab tests. */
    public function create()
    {
        $sessions = DoctorDutySession::with('doctor.user')
            ->where('is_voided', 0)
            ->where('status', 'Scheduled')
            ->whereDate('duty_date', '>=', now()->toDateString())
            ->whereHas('doctor', fn ($q) => $q->where('is_active', 1))
            ->whereDoesntHave('appointments', fn ($q) => $q->where('is_voided', 0))
            ->orderBy('duty_date')->orderBy('start_time')
            ->get();

        $labTests = LabTest::with('category')->where('is_active', 1)
            ->orderBy('test_name')->get();

        // Non-patient roles (e.g. receptionist) need a patient selector.
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $patients = collect();
        if (! $actor->hasRole('patient')) {
            $patients = PatientProfile::with('user')
                ->whereHas('user', fn ($q) => $q->where('account_status', 'Active'))
                ->orderBy('patient_id')
                ->get();
        }

        return view('patient.book', compact('sessions', 'labTests', 'patients'));
    }

    /** Book the chosen slot. */
    public function store(Request $request)
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        $isPatient = $actor->hasRole('patient');

        $rules = [
            'duty_session_id'  => ['required', 'integer', 'exists:doctor_duty_sessions,duty_session_id'],
            'preferred_time'   => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'reason_for_visit' => ['nullable', 'string'],
            'lab_tests'        => ['nullable', 'array'],
            'lab_tests.*'      => ['integer', 'exists:lab_tests,lab_test_id'],
        ];

        if (! $isPatient) {
            $rules['patient_id'] = ['required', 'integer', 'exists:patient_profiles,patient_id'];
        }

        $data = $request->validate($rules);

        $patient = $isPatient
            ? $this->patient()
            : PatientProfile::with('user')->findOrFail($data['patient_id']);

        $scheduledStatus = AppointmentStatus::where('status_name', 'Scheduled')->first();

        $appointment = DB::transaction(function () use ($data, $patient, $scheduledStatus) {
            $session = DoctorDutySession::with('doctor')
                ->lockForUpdate()
                ->findOrFail($data['duty_session_id']);

            if ($session->isTaken()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'duty_session_id' => 'Sorry, that schedule was just taken. Please pick another.',
                ]);
            }
            if (! empty($data['preferred_time'])) {
                $t = $data['preferred_time'];
                if ($t < '08:00' || $t > '18:00') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'preferred_time' => 'Preferred time must be between 8:00 AM and 6:00 PM.',
                    ]);
                }
                $appointmentAt = $session->duty_date->toDateString().' '.$t.':00';
            } else {
                $appointmentAt = $session->duty_date->toDateString().' '.$session->start_time;
            }

            $appointment = Appointment::create([
                'patient_id'       => $patient->patient_id,
                'doctor_id'        => $session->doctor_id,
                'duty_session_id'  => $session->duty_session_id,
                'appointment_at'   => $appointmentAt,
                'duration_minutes' => 30,
                'reason_for_visit' => $data['reason_for_visit'] ?? null,
                'appointment_type' => 'Scheduled',
                'status_id'        => $scheduledStatus->appointment_status_id,
            ]);

            if (! empty($data['lab_tests'])) {
                $labRequest = LabRequest::create([
                    'patient_id' => $patient->patient_id,
                    'doctor_id'  => $session->doctor_id,
                    'priority'   => 'Routine',
                    'status'     => 'Pending',
                ]);

                foreach (array_unique($data['lab_tests']) as $testId) {
                    LabRequestItem::create([
                        'lab_request_id' => $labRequest->lab_request_id,
                        'lab_test_id'    => $testId,
                        'status'         => 'Pending',
                    ]);
                }
            }

            return $appointment;
        });

        $msg = $isPatient
            ? $actor->fullName().' booked an appointment'
            : $actor->fullName().' booked appointment for '.$patient->user->fullName();

        AuditLogger::log('CREATE', 'Appointments', 'appointments', $appointment->appointment_id, $msg);

        $redirect = $isPatient
            ? redirect()->route('patient.appointments.index')
            : redirect()->route('receptionist.patients.index');

        return $redirect->with('status', 'Appointment has been booked successfully.');
    }

    private function patient(): PatientProfile
    {
        return PatientProfile::firstOrCreate(['user_id' => Auth::id()]);
    }
}
