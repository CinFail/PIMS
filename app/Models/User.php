<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    // The provided schema uses "users" with PK "user_id".
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    // The schema stores the password in "password_hash", not "password".
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'mobile_number',
        'password_hash',
        'date_of_birth',
        'account_status',
        'is_approved_by_admin',
        'is_otp_bypassed',
        'has_accepted_privacy_notice',
        'consented_privacy_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'consented_privacy_at' => 'datetime',
            'is_otp_bypassed' => 'boolean',
            'has_accepted_privacy_notice' => 'boolean',
        ];
    }

    /**
     * Tell Laravel's auth system which column holds the hashed password.
     * getAuthPassword()     → returns the hash VALUE  (used by Hash::check on login)
     * getAuthPasswordName() → returns the column NAME (used when rehashing / reset)
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // ---------------- Relationships ----------------

    public function roles(): BelongsToMany
    {
        // Junction table is "user_roles" (user_id, role_id).
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
    }

    public function patientProfile(): HasOne
    {
        return $this->hasOne(PatientProfile::class, 'user_id', 'user_id');
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class, 'user_id', 'user_id');
    }

    public function medTechProfile(): HasOne
    {
        return $this->hasOne(MedTechProfile::class, 'user_id', 'user_id');
    }

    public function receptionistProfile(): HasOne
    {
        return $this->hasOne(ReceptionistProfile::class, 'user_id', 'user_id');
    }

    // ---------------- Simple RBAC helpers ----------------

    /** Full name helper used across the views. */
    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Age in whole years, computed dynamically from date_of_birth.
     * Returns null when no date of birth is on file. Age is never
     * stored — it is always derived so it stays correct over time.
     */
    public function age(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /** Does the user have the given role name? e.g. hasRole('doctor') */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains(fn ($role) => $role->name === $roleName);
    }

    /** First role name, used to pick which dashboard to show. */
    public function primaryRole(): ?string
    {
        return optional($this->roles->first())->name;
    }

    /**
     * Does the user have the given permission?
     * Checks direct per-user permissions first, then permissions
     * granted through any of the user's roles.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super admin can do everything.
        if ($this->hasRole('super_admin')) {
            return true;
        }

        if ($this->directPermissions->contains(fn ($p) => $p->name === $permissionName)) {
            return true;
        }

        $roleIds = $this->roles->pluck('role_id')->all();

        if (empty($roleIds)) {
            return false;
        }

        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.permission_id', '=', 'role_has_permissions.permission_id')
            ->whereIn('role_has_permissions.role_id', $roleIds)
            ->where('permissions.name', $permissionName)
            ->exists();
    }
}
