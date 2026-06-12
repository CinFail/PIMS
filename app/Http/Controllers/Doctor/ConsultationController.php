<?php

namespace App\Http\Controllers\Doctor;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    /** Show one consultation with its diagnoses and prescription. */
    public function show(int $consultationId)
    {
        $consultation = Consultation::with([
            'patient.user',
            'diagnoses.doctor.user',
            'prescription.items',
            'prescription.doctor.user',
        ])->findOrFail($consultationId);

        // Previous prescriptions for this patient (medication history).
        $previousPrescriptions = Prescription::with(['items', 'doctor.user'])
            ->whereHas('consultation', fn ($q) => $q->where('patient_id', $consultation->patient_id))
            ->where('consultation_id', '!=', $consultationId)
            ->where('is_voided', 0)
            ->orderByDesc('prescribed_at')->get();

        $doctor = Auth::user()->doctorProfile;

        return view('doctor.consultation', compact('consultation', 'previousPrescriptions', 'doctor'));
    }

    /** Add a structured diagnosis (shows in the patient's diagnosis tab). */
    public function storeDiagnosis(Request $request, int $consultationId)
    {
        $consultation = Consultation::findOrFail($consultationId);
        $doctor = Auth::user()->doctorProfile;
        abort_unless($doctor, 403, 'Your doctor profile is missing.');
        abort_unless($consultation->doctor_id === $doctor->doctor_id, 403, 'You cannot modify another doctor\'s consultation.');

        $data = $request->validate([
            'icd_code'       => ['nullable', 'string', 'max:20'],
            'description'    => ['required', 'string'],
            'diagnosis_type' => ['required', 'in:Primary,Secondary,Differential'],
        ]);

        $diagnosis = Diagnosis::create([
            'consultation_id' => $consultation->consultation_id,
            'diagnosed_by'    => $doctor->doctor_id,
            'icd_code'        => $data['icd_code'] ?? null,
            'description'     => $data['description'],
            'diagnosis_type'  => $data['diagnosis_type'],
            'diagnosed_at'    => now(),
        ]);

        AuditLogger::log('CREATE', 'Diagnoses', 'diagnoses', $diagnosis->diagnosis_id, 'Doctor recorded a new diagnosis (new findings)');

        return back()->with('status', 'Diagnosis added.');
    }

    /** Create / add to the prescription for this consultation. */
    public function storePrescription(Request $request, int $consultationId)
    {
        $consultation = Consultation::findOrFail($consultationId);
        $doctor = Auth::user()->doctorProfile;
        abort_unless($doctor, 403, 'Your doctor profile is missing.');
        abort_unless($consultation->doctor_id === $doctor->doctor_id, 403, 'You cannot modify another doctor\'s consultation.');

        $data = $request->validate([
            'medicine_name' => ['required', 'string', 'max:150'],
            'dosage'        => ['nullable', 'string', 'max:50'],
            'form'          => ['nullable', 'string', 'max:50'],
            'frequency'     => ['nullable', 'string', 'max:50'],
            'duration'      => ['nullable', 'string', 'max:50'],
            'quantity'      => ['nullable', 'integer', 'min:1'],
            'instructions'  => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $consultation, $doctor) {
            // One prescription per consultation; create it if it does not exist yet.
            $prescription = Prescription::firstOrCreate(
                ['consultation_id' => $consultation->consultation_id],
                ['prescribed_by' => $doctor->doctor_id, 'prescribed_at' => now()]
            );

            PrescriptionItem::create([
                'prescription_id' => $prescription->prescription_id,
                'medicine_name'   => $data['medicine_name'],
                'dosage'          => $data['dosage'] ?? null,
                'form'            => $data['form'] ?? null,
                'frequency'       => $data['frequency'] ?? null,
                'duration'        => $data['duration'] ?? null,
                'quantity'        => $data['quantity'] ?? null,
                'instructions'    => $data['instructions'] ?? null,
            ]);

            AuditLogger::log('CREATE', 'Prescriptions', 'prescriptions', $prescription->prescription_id, 'Doctor added a new prescription');
        });

        return back()->with('status', 'Medicine added to the prescription.');
    }
}
