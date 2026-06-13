<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientProfile extends Model
{
    protected $table = 'patient_profiles';
    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'user_id', 'sex', 'address',
        'emergency_contact_name', 'emergency_contact_number', 'blood_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(PatientMedicalHistory::class, 'patient_id', 'patient_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'patient_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'patient_id', 'patient_id');
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class, 'patient_id', 'patient_id');
    }

    public function labAppointments(): HasMany
    {
        return $this->hasMany(LabAppointment::class, 'patient_id', 'patient_id');
    }
}
