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
            'lab_tests'      => ['required', 'array', 'min:1'],
            'lab_tests.*'    => ['integer', 'exists:lab_tests,lab_test_id'],
            'scheduled_at'   => ['required', 'date', 'after_or_equal:today'],
            'clinical_notes' => ['nullable', 'string'],
        ], [
            'lab_tests.required' => 'Please select at least one laboratory test.',
            'lab_tests.min'      => 'Please select at least one laboratory test.',
        ]);

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
