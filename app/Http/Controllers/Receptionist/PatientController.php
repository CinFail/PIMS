<?php

namespace App\Http\Controllers\Receptionist;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\PatientMedicalHistory;
use App\Models\PatientProfile;
use App\Models\Role;
use App\Models\User;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    /** Patient information list (searchable). */
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

        return view('receptionist.patients', compact('patients', 'search'));
    }

    /** View a single patient's basic information. */
    public function show(int $patientId)
    {
        $patient = PatientProfile::with('user')->findOrFail($patientId);

        AuditLogger::log('VIEW', 'Receptionist', 'patient_profiles', $patientId, 'Receptionist viewed patient information');

        return view('receptionist.show', compact('patient'));
    }

    /** Form to add a new walk-in patient. */
    public function create()
    {
        return view('receptionist.create');
    }

    /**
     * Create a new patient on behalf of a walk-in.
     * OTP is bypassed, but an email OR a contact number is required.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'          => ['required', 'string', 'max:50'],
            'middle_name'         => ['nullable', 'string', 'max:50'],
            'last_name'           => ['required', 'string', 'max:50'],
            'email'               => ['nullable', 'email', 'max:254', 'unique:users,email'],
            'mobile_number'       => ['nullable', new MobileNumber],
            'date_of_birth'       => ['required', 'date', 'before_or_equal:today'],
            'sex'                 => ['nullable', 'in:Male,Female'],
            'address'             => ['nullable', 'string'],
            'password'            => ['required', 'string', 'min:6'],
            'allergies'           => ['nullable', 'string'],
            'chronic_conditions'  => ['nullable', 'string'],
            'past_surgeries'      => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'family_history'      => ['nullable', 'string'],
        ]);

        // Business rule: at least one contact channel must be provided.
        if (empty($data['email']) && empty($data['mobile_number'])) {
            throw ValidationException::withMessages([
                'email' => 'Please provide either an email OR a mobile number.',
            ]);
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name'                  => $data['first_name'],
                'middle_name'                 => $data['middle_name'] ?? null,
                'last_name'                   => $data['last_name'],
                'email'                       => $data['email'] ?? ('walkin_'.uniqid().'@clinic.local'),
                'mobile_number'               => $data['mobile_number'] ?? null,
                'date_of_birth'               => $data['date_of_birth'],
                'password_hash'               => Hash::make($data['password']),
                'account_status'              => 'Active',
                'is_otp_bypassed'             => 1,
                'has_accepted_privacy_notice' => 1,
                'consented_privacy_at'        => now(),
            ]);

            $profile = PatientProfile::create([
                'user_id' => $user->user_id,
                'sex'     => $data['sex'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            PatientMedicalHistory::create([
                'patient_id'          => $profile->patient_id,
                'allergies'           => $data['allergies'] ?? null,
                'chronic_conditions'  => $data['chronic_conditions'] ?? null,
                'past_surgeries'      => $data['past_surgeries'] ?? null,
                'current_medications' => $data['current_medications'] ?? null,
                'family_history'      => $data['family_history'] ?? null,
            ]);

            $role = Role::where('name', 'patient')->first();
            if ($role) {
                $user->roles()->attach($role->role_id);
            }

            return $user;
        });

        AuditLogger::log('CREATE', 'Receptionist', 'users', $user->user_id, 'Receptionist added a new patient (OTP bypassed)');

        return redirect()->route('receptionist.patients.index')
            ->with('status', 'New patient added successfully.');
    }
}
