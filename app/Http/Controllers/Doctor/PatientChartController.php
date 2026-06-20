<?php

namespace App\Http\Controllers\Doctor;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\VoidRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientChartController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $patients = PatientProfile::with('user')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('patient_id')
            ->paginate(15)
            ->withQueryString();

        return view('doctor.patients', compact('patients', 'search'));
    }

    public function show(int $patientId)
    {
        $patient = PatientProfile::with(['user', 'medicalHistory'])->findOrFail($patientId);

        $consultations = Consultation::with('doctor.user')
            ->where('patient_id', $patientId)
            ->where('is_voided', 0)
            ->orderByDesc('consultation_at')
            ->get();

        $diagnoses = Diagnosis::with('doctor.user')
            ->whereHas('consultation', fn ($q) => $q->where('patient_id', $patientId))
            ->where('is_voided', 0)
            ->orderByDesc('diagnosed_at')->get();

        $prescriptions = Prescription::with(['items', 'doctor.user'])
            ->whereHas('consultation', fn ($q) => $q->where('patient_id', $patientId))
            ->where('is_voided', 0)
            ->orderByDesc('prescribed_at')->get();

        $results = LabResult::whereHas('requestItem.request', fn ($q) => $q->where('patient_id', $patientId))
            ->with('requestItem.test')->orderByDesc('created_at')->get();

        // Keyed by "table:id" for O(1) lookup in the view — only Pending requests matter here.
        $pendingVoids = VoidRequest::where('status', 'Pending')
            ->whereIn('table_name', ['consultations', 'diagnoses', 'prescriptions'])
            ->get()
            ->keyBy(fn ($vr) => $vr->table_name . ':' . $vr->record_id);

        AuditLogger::log('VIEW', 'Doctor', 'patient_profiles', $patientId, "Doctor viewed patient chart for {$patient->user->fullName()}");

        return view('doctor.chart', compact('patient', 'consultations', 'diagnoses', 'prescriptions', 'results', 'pendingVoids'));
    }

    public function startConsultation(int $patientId)
    {
        $doctor = Auth::user()->doctorProfile;
        abort_unless($doctor, 403, 'Your doctor profile is missing.');

        $patient = PatientProfile::findOrFail($patientId);

        $consultation = Consultation::create([
            'doctor_id'  => $doctor->doctor_id,
            'patient_id' => $patient->patient_id,
        ]);

        AuditLogger::log('CREATE', 'Consultations', 'consultations', $consultation->consultation_id, 'Doctor started a consultation');

        return redirect()->route('doctor.consultation.show', $consultation->consultation_id);
    }
}
