<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\DoctorProfile;
use App\Models\MedTechProfile;
use App\Models\PatientProfile;
use App\Models\ReceptionistProfile;
use App\Models\Role;
use App\Models\User;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /** List all users with their roles. */
    public function index(Request $request)
    {
        $search = $request->query('q');

        $users = User::with('roles')
            ->when($search, function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('user_id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    /** Form to create a new staff user. */
    public function create()
    {
        $roles = Role::orderBy('role_id')->get();

        return view('admin.users.create', compact('roles'));
    }

    /** Create a staff user and the matching profile for their role. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'     => ['required', 'string', 'max:50'],
            'last_name'      => ['required', 'string', 'max:50'],
            'email'          => ['required', 'email', 'max:254', 'unique:users,email'],
            'mobile_number'  => ['nullable', new MobileNumber],
            'date_of_birth'  => ['required', 'date'],
            'role'           => ['required', 'in:super_admin,doctor,receptionist,med_tech,patient'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'short_bio'      => ['nullable', 'string', 'max:500'],
            'password'       => ['required', 'string', 'min:6'],
        ]);

        // Doctors must have a license number (prescriptions need it).
        if ($data['role'] === 'doctor' && empty($data['license_number'])) {
            throw ValidationException::withMessages([
                'license_number' => 'A license number is required for doctors.',
            ]);
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name'                  => $data['first_name'],
                'last_name'                   => $data['last_name'],
                'email'                       => $data['email'],
                'mobile_number'               => $data['mobile_number'] ?? null,
                'date_of_birth'               => $data['date_of_birth'],
                'password_hash'               => Hash::make($data['password']),
                'account_status'              => 'Active',
                'is_approved_by_admin'        => 1,
                'has_accepted_privacy_notice' => 1,
                'consented_privacy_at'        => now(),
            ]);

            // Attach role.
            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->roles()->attach($role->role_id);
            }

            // Create the matching profile row.
            switch ($data['role']) {
                case 'doctor':
                    DoctorProfile::create([
                        'user_id'        => $user->user_id,
                        'license_number' => $data['license_number'],
                        'specialization' => $data['specialization'] ?? null,
                        'short_bio'      => $data['short_bio'] ?? null,
                        'is_active'      => 1,
                    ]);
                    break;
                case 'med_tech':
                    MedTechProfile::create([
                        'user_id'        => $user->user_id,
                        'license_number' => $data['license_number'] ?? null,
                    ]);
                    break;
                case 'receptionist':
                    ReceptionistProfile::create(['user_id' => $user->user_id]);
                    break;
                case 'super_admin':
                    AdminProfile::create(['user_id' => $user->user_id]);
                    break;
                case 'patient':
                    PatientProfile::create(['user_id' => $user->user_id]);
                    break;
            }

            return $user;
        });

        AuditLogger::log('CREATE', 'Access Control', 'users', $user->user_id, "Admin created a new '{$data['role']}' user");

        return redirect()->route('admin.users.index')->with('status', 'New user created successfully.');
    }

    /** Toggle a user between Active and Deactivated. Super Admin accounts are immutable. */
    public function toggleStatus(int $userId)
    {
        $user = User::with('roles')->findOrFail($userId);

        if ($user->hasRole('super_admin')) {
            return back()->withErrors([
                'guard' => 'Super Admin accounts cannot be deactivated or modified.',
            ]);
        }

        $new = $user->account_status === 'Active' ? 'Deactivated' : 'Active';

        $user->update(['account_status' => $new]);

        AuditLogger::log('UPDATE', 'Access Control', 'users', $user->user_id, "Admin set account status to {$new}");

        return back()->with('status', "User is now {$new}.");
    }
}
