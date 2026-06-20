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

class LabTestRequestController extends Controller
{
    public function create()
    {
        $labTests = LabTest::with('category')
            ->where('is_active', 1)
            ->orderBy('test_name')
            ->get();

        return view('patient.lab_request_create', compact('labTests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lab_tests'       => ['required', 'array', 'min:1'],
            'lab_tests.*'     => ['integer', 'exists:lab_tests,lab_test_id'],
            'scheduled_date'  => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time'  => ['required', 'date_format:H:i'],
            'clinical_notes'  => ['nullable', 'string'],
        ], [
            'lab_tests.required'            => 'Please select at least one laboratory test.',
            'lab_tests.min'                 => 'Please select at least one laboratory test.',
            'scheduled_date.required'       => 'Please select a preferred date.',
            'scheduled_date.after_or_equal' => 'Please choose a future date.',
            'scheduled_time.required'       => 'Please select a preferred time.',
            'scheduled_time.date_format'    => 'Please enter a valid time.',
        ]);

        // saturday = 6, not a clinic day
        $date = \Illuminate\Support\Carbon::parse($data['scheduled_date']);
        if ($date->dayOfWeek === 6) {
            return back()->withInput()->withErrors([
                'scheduled_date' => 'Saturdays are not available. Please choose Sunday through Friday.',
            ]);
        }

        $data['scheduled_at'] = $data['scheduled_date'].' '.$data['scheduled_time'].':00';

        $patient = $this->patient();

        $labAppointment = DB::transaction(function () use ($data, $patient) {
            // doctor_id is null — patient-initiated, no referral
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
