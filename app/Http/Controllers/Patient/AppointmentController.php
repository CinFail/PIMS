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
    /**
     * List the patient's own appointments. A single page, split into
     * Doctor Appointments and Laboratory Appointments.
     */
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
            ->where('is_voided', 0)
            ->orderByDesc('scheduled_at')
            ->get();

        return view('patient.appointments', compact('appointments', 'labAppointments'));
    }

    /** Show the booking form: active doctors, open slots, and lab tests. */
    public function create()
    {
        // Open duty slots: not voided, in the future, and not already taken.
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

        return view('patient.book', compact('sessions', 'labTests'));
    }

    /** Book the chosen slot. The slot is locked so no one else can take it. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'duty_session_id'  => ['required', 'integer', 'exists:doctor_duty_sessions,duty_session_id'],
            'reason_for_visit' => ['nullable', 'string'],
            'lab_tests'        => ['nullable', 'array'],
            'lab_tests.*'      => ['integer', 'exists:lab_tests,lab_test_id'],
        ]);

        $patient = $this->patient();
        $scheduledStatus = AppointmentStatus::where('status_name', 'Scheduled')->first();

        $appointment = DB::transaction(function () use ($data, $patient, $scheduledStatus) {
            // Lock the session row so concurrent requests queue up here instead of
            // both passing the isTaken() check and creating duplicate appointments.
            $session = DoctorDutySession::with('doctor')
                ->lockForUpdate()
                ->findOrFail($data['duty_session_id']);

            if ($session->isTaken()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'duty_session_id' => 'Sorry, that schedule was just taken. Please pick another.',
                ]);
            }
            $appointmentAt = $session->duty_date->toDateString().' '.$session->start_time;

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

            // If the patient selected lab tests, create a lab request for them.
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

        AuditLogger::log(
            'CREATE', 'Appointments', 'appointments', $appointment->appointment_id,
            'Patient booked an appointment'
        );

        return redirect()->route('patient.appointments.index')
            ->with('status', 'Your appointment has been booked.');
    }

    private function patient(): PatientProfile
    {
        return PatientProfile::firstOrCreate(['user_id' => Auth::id()]);
    }
}
