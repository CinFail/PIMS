<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\PatientProfile;
use App\Models\Role;
use App\Models\User;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /** Show the patient registration form. */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /** Create a new patient account. (OTP intentionally NOT implemented yet.) */
    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name'    => ['required', 'string', 'max:50'],
            'last_name'     => ['required', 'string', 'max:50'],
            'email'         => ['required', 'email', 'max:254', 'unique:users,email'],
            'mobile_number' => ['nullable', new MobileNumber],
            'date_of_birth' => ['required', 'date'],
            'password'      => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Use a transaction so the user + profile + role are all saved together.
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name'                  => $data['first_name'],
                'last_name'                   => $data['last_name'],
                'email'                       => $data['email'],
                'mobile_number'               => $data['mobile_number'] ?? null,
                'date_of_birth'               => $data['date_of_birth'],
                'password_hash'               => Hash::make($data['password']),
                'account_status'              => 'Active',
                'has_accepted_privacy_notice' => 1,
                'consented_privacy_at'        => now(),
                'is_otp_bypassed'             => 0,
            ]);

            PatientProfile::create(['user_id' => $user->user_id]);

            $patientRole = Role::where('name', 'patient')->first();
            if ($patientRole) {
                $user->roles()->attach($patientRole->role_id);
            }

            return $user;
        });

        AuditLogger::log('CREATE', 'Authentication', 'users', $user->user_id, 'New patient self-registered');

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'Welcome! Your account has been created.');
    }
}
