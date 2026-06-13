<?php

namespace App\Http\Controllers\Patient;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\LabAppointment;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTest;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Lets a patient request laboratory tests WITHOUT booking a doctor
 * consultation, and schedule a laboratory appointment for the visit.
 *
 * This is separate from the doctor appointment flow
 * (Patient\AppointmentController) and from the soft-copy result flow
 * (Patient\LabResultRequestController).
 */
class LabTestRequestController extends Controller
{
    /** Show the "Request a Lab Test" form: available tests + preferred schedule. */
    public function create()
    {
        $labTests = LabTest::with('category')
            ->where('is_active', 1)
            ->orderBy('test_name')
            ->get();

        return view('patient.lab_request_create', compact('labTests'));
    }

    /**
     * Create a self-requested lab request (no referring doctor) together with
     * a laboratory appointment for the chosen date and time.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'lab_tests'       => ['required', 'array', 'min:1'],
            'lab_tests.*'     => ['integer', 'exists:lab_tests,lab_test_id'],
            'scheduled_date'  => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time'  => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'clinical_notes'  => ['nullable', 'string'],
        ], [
            'lab_tests.required'           => 'Please select at least one laboratory test.',
            'lab_tests.min'                => 'Please select at least one laboratory test.',
            'scheduled_date.required'      => 'Please select a preferred date.',
            'scheduled_date.after_or_equal' => 'Please choose a future date.',
            'scheduled_time.required'      => 'Please enter a preferred time.',
            'scheduled_time.regex'         => 'Invalid time format.',
        ]);

        // Validate day of week: Saturday (6) is not available
        $date = \Illuminate\Support\Carbon::parse($data['scheduled_date']);
        if ($date->dayOfWeek === 6) {
            return back()->withInput()->withErrors([
                'scheduled_date' => 'Saturdays are not available. Please choose Sunday through Friday.',
            ]);
        }

        // Validate business hours — open: Mon–Thu 7:30 AM–4:30 PM, Sun & Fri 7:30 AM–4:00 PM
        [$h, $m] = array_map('intval', explode(':', $data['scheduled_time']));
        $totalMinutes = $h * 60 + $m;
        $minMinutes   = 7 * 60 + 30; // 7:30 AM
        $isLongDay    = $date->dayOfWeek >= 1 && $date->dayOfWeek <= 4; // Mon–Thu
        $maxMinutes   = $isLongDay ? 16 * 60 + 30 : 16 * 60;           // 4:30 PM or 4:00 PM
        $maxLabel     = $isLongDay ? '4:30 PM' : '4:00 PM';
        if ($totalMinutes < $minMinutes || $totalMinutes > $maxMinutes) {
            return back()->withInput()->withErrors([
                'scheduled_time' => "Please choose a time between 7:30 AM and {$maxLabel}.",
            ]);
        }

        $data['scheduled_at'] = $data['scheduled_date'].' '.$data['scheduled_time'].':00';

        $patient = $this->patient();

        $labAppointment = DB::transaction(function () use ($data, $patient) {
            // doctor_id stays NULL: this is a patient self-request with no referring doctor.
            $labRequest = LabRequest::create([
                'patient_id'     => $patient->patient_id,
                'doctor_id'      => null,
                'priority'       => 'Routine',
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'status'         => 'Pending',
            ]);

            foreach (array_unique($data['lab_tests']) as $testId) {
                LabRequestItem::create([
                    'lab_request_id' => $labRequest->lab_request_id,
                    'lab_test_id'    => $testId,
                    'status'         => 'Pending',
                ]);
            }

            return LabAppointment::create([
                'patient_id'     => $patient->patient_id,
                'lab_request_id' => $labRequest->lab_request_id,
                'scheduled_at'   => $data['scheduled_at'],
                'status'         => 'Scheduled',
                'notes'          => $data['clinical_notes'] ?? null,
            ]);
        });

        AuditLogger::log(
            'CREATE', 'Laboratory', 'lab_appointments', $labAppointment->lab_appointment_id,
            'Patient requested laboratory tests and booked a laboratory appointment'
        );

        return redirect()->route('patient.appointments.index')
            ->with('status', 'Your laboratory test request and appointment have been submitted.');
    }

    private function patient(): PatientProfile
    {
        return PatientProfile::firstOrCreate(['user_id' => Auth::id()]);
    }
}
