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
    public function show(int $consultationId)
    {
        $consultation = Consultation::with([
            'patient.user',
            'diagnoses.doctor.user',
            'prescription.items',
            'prescription.doctor.user',
        ])->findOrFail($consultationId);

        $previousPrescriptions = Prescription::with(['items', 'doctor.user'])
            ->whereHas('consultation', fn ($q) => $q->where('patient_id', $consultation->patient_id))
            ->where('consultation_id', '!=', $consultationId)
            ->where('is_voided', 0)
            ->orderByDesc('prescribed_at')->get();

        $doctor = Auth::user()->doctorProfile;

        return view('doctor.consultation', compact('consultation', 'previousPrescriptions', 'doctor'));
    }

    public function update(Request $request, int $consultationId)
    {
        $consultation = Consultation::findOrFail($consultationId);
        $user         = Auth::user();
        $doctor       = $user->doctorProfile;
        $isAdmin      = $user->hasRole('super_admin');

        abort_unless(
            $isAdmin || ($doctor && $consultation->doctor_id === $doctor->doctor_id),
            403, 'You cannot modify this consultation.'
        );

        $data = $request->validate([
            'chief_complaint'  => ['nullable', 'string'],
            'weight_kg'        => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height_cm'        => ['nullable', 'numeric', 'min:0', 'max:300'],
            'temp_c'           => ['nullable', 'numeric', 'min:30', 'max:45'],
            'bp_systolic'      => ['nullable', 'integer', 'min:50', 'max:300'],
            'bp_diastolic'     => ['nullable', 'integer', 'min:30', 'max:200'],
            'heart_rate'       => ['nullable', 'integer', 'min:20', 'max:300'],
            'respiratory_rate' => ['nullable', 'integer', 'min:5', 'max:80'],
            'clinical_notes'   => ['nullable', 'string'],
            'follow_up_at'     => ['nullable', 'date'],
        ]);

        $old = $consultation->only([
            'chief_complaint', 'weight_kg', 'height_cm', 'temp_c',
            'bp_systolic', 'bp_diastolic', 'heart_rate', 'respiratory_rate',
            'clinical_notes', 'follow_up_at',
        ]);

        $consultation->update($data);

        AuditLogger::log('UPDATE', 'Consultations', 'consultations', $consultation->consultation_id,
            'Doctor updated consultation record', $old, $data);

        return back()->with('status', 'Consultation updated.');
    }

    public function storeDiagnosis(Request $request, int $consultationId)
    {
        $consultation = Consultation::findOrFail($consultationId);
        $doctor       = Auth::user()->doctorProfile;

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

        AuditLogger::log('CREATE', 'Diagnoses', 'diagnoses', $diagnosis->diagnosis_id, 'Doctor recorded a new diagnosis');

        return back()->with('status', 'Diagnosis added.');
    }

    public function storePrescription(Request $request, int $consultationId)
    {
        $consultation = Consultation::findOrFail($consultationId);
        $doctor       = Auth::user()->doctorProfile;

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

    public function updateDiagnosis(Request $request, int $diagnosisId)
    {
        $diagnosis = Diagnosis::findOrFail($diagnosisId);
        $user      = Auth::user();
        $doctor    = $user->doctorProfile;
        $isAdmin   = $user->hasRole('super_admin');

        abort_unless(
            $isAdmin || ($doctor && $diagnosis->diagnosed_by === $doctor->doctor_id),
            403, 'You cannot modify this diagnosis.'
        );

        $data = $request->validate([
            'icd_code'       => ['nullable', 'string', 'max:20'],
            'description'    => ['required', 'string'],
            'diagnosis_type' => ['required', 'in:Primary,Secondary,Differential'],
        ]);

        $old = $diagnosis->only(['icd_code', 'description', 'diagnosis_type']);
        $diagnosis->update($data);

        AuditLogger::log('UPDATE', 'Diagnoses', 'diagnoses', $diagnosis->diagnosis_id,
            'Updated diagnosis record', $old, $data);

        return back()->with('status', 'Diagnosis updated.');
    }

    public function updatePrescription(Request $request, int $prescriptionId)
    {
        $prescription = Prescription::findOrFail($prescriptionId);
        $user         = Auth::user();
        $doctor       = $user->doctorProfile;
        $isAdmin      = $user->hasRole('super_admin');

        abort_unless(
            $isAdmin || ($doctor && $prescription->prescribed_by === $doctor->doctor_id),
            403, 'You cannot modify this prescription.'
        );

        $data = $request->validate([
            'remarks'       => ['nullable', 'string'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $old = $prescription->only(['remarks', 'validity_days']);
        $prescription->update($data);

        AuditLogger::log('UPDATE', 'Prescriptions', 'prescriptions', $prescription->prescription_id,
            'Updated prescription record', $old, $data);

        return back()->with('status', 'Prescription updated.');
    }

    public function updatePrescriptionItem(Request $request, int $itemId)
    {
        $item         = PrescriptionItem::findOrFail($itemId);
        $user         = Auth::user();
        $doctor       = $user->doctorProfile;
        $isAdmin      = $user->hasRole('super_admin');
        $prescription = Prescription::findOrFail($item->prescription_id);

        abort_unless(
            $isAdmin || ($doctor && $prescription->prescribed_by === $doctor->doctor_id),
            403, 'You cannot modify this prescription.'
        );

        $data = $request->validate([
            'dosage'       => ['nullable', 'string', 'max:50'],
            'form'         => ['nullable', 'string', 'max:50'],
            'frequency'    => ['nullable', 'string', 'max:50'],
            'duration'     => ['nullable', 'string', 'max:50'],
            'quantity'     => ['nullable', 'integer', 'min:1', 'max:9999'],
            'instructions' => ['nullable', 'string'],
        ]);

        $old = $item->only(['dosage', 'form', 'frequency', 'duration', 'quantity', 'instructions']);
        $item->update($data);

        AuditLogger::log('UPDATE', 'Prescriptions', 'prescription_items', $item->prescription_item_id,
            'Updated prescription item', $old, $data);

        return back()->with('status', 'Prescription item updated.');
    }

    public function destroyItem(int $itemId)
    {
        $item   = PrescriptionItem::findOrFail($itemId);
        $doctor = Auth::user()->doctorProfile;

        abort_unless($doctor, 403, 'Your doctor profile is missing.');

        $prescription = Prescription::with('consultation')->findOrFail($item->prescription_id);

        abort_unless(
            $prescription->consultation->doctor_id === $doctor->doctor_id,
            403,
            'You cannot modify another doctor\'s prescription.'
        );

        $item->delete();

        AuditLogger::log('DELETE', 'Prescriptions', 'prescription_items', $itemId, 'Doctor removed a prescription item');

        return back()->with('status', 'Medicine removed from prescription.');
    }
}
