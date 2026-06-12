<?php

namespace App\Http\Controllers\Patient;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\PatientMedicalHistory;
use App\Models\PatientProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /** Show the patient's editable profile. */
    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = PatientProfile::firstOrCreate(['user_id' => $user->user_id]);
        $medicalHistory = PatientMedicalHistory::firstOrCreate(['patient_id' => $profile->patient_id]);

        return view('patient.profile', compact('user', 'profile', 'medicalHistory'));
    }

    /** Save profile changes. Every update is audit-logged. */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = PatientProfile::firstOrCreate(['user_id' => $user->user_id]);

        $data = $request->validate([
            'first_name'               => ['required', 'string', 'max:50'],
            'last_name'                => ['required', 'string', 'max:50'],
            'mobile_number'            => ['nullable', 'string', 'max:20'],
            'sex'                      => ['nullable', 'in:Male,Female'],
            'contact_number'           => ['nullable', 'string', 'max:20'],
            'address'                  => ['nullable', 'string'],
            'blood_type'               => ['nullable', 'string', 'max:5'],
            'emergency_contact_name'   => ['nullable', 'string', 'max:100'],
            'emergency_contact_number' => ['nullable', 'string', 'max:20'],
            'allergies'                => ['nullable', 'string'],
            'chronic_conditions'       => ['nullable', 'string'],
            'past_surgeries'           => ['nullable', 'string'],
            'current_medications'      => ['nullable', 'string'],
            'family_history'           => ['nullable', 'string'],
        ]);

        $old = [
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
        ];

        $user->update([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'mobile_number' => $data['mobile_number'] ?? null,
        ]);

        $profile->update([
            'sex'                      => $data['sex'] ?? null,
            'contact_number'           => $data['contact_number'] ?? null,
            'address'                  => $data['address'] ?? null,
            'blood_type'               => $data['blood_type'] ?? null,
            'emergency_contact_name'   => $data['emergency_contact_name'] ?? null,
            'emergency_contact_number' => $data['emergency_contact_number'] ?? null,
        ]);

        $medicalHistory = PatientMedicalHistory::firstOrCreate(['patient_id' => $profile->patient_id]);
        $medicalHistory->update([
            'allergies'           => $data['allergies'] ?? null,
            'chronic_conditions'  => $data['chronic_conditions'] ?? null,
            'past_surgeries'      => $data['past_surgeries'] ?? null,
            'current_medications' => $data['current_medications'] ?? null,
            'family_history'      => $data['family_history'] ?? null,
        ]);

        AuditLogger::log(
            'UPDATE', 'Patient', 'patient_profiles', $profile->patient_id,
            'Patient updated their information', $old, ['first_name' => $data['first_name'], 'last_name' => $data['last_name']]
        );
        AuditLogger::log(
            'UPDATE', 'Patient', 'patient_medical_histories', $medicalHistory->medical_history_id,
            'Patient updated their medical history'
        );

        return back()->with('status', 'Your information has been updated.');
    }
}
