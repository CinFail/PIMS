<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    protected $table = 'consultations';
    protected $primaryKey = 'consultation_id';

    protected $fillable = [
        'appointment_id', 'doctor_id', 'patient_id', 'consultation_at',
        'chief_complaint', 'weight_kg', 'height_cm', 'temp_c',
        'bp_systolic', 'bp_diastolic', 'heart_rate', 'respiratory_rate',
        'clinical_notes', 'follow_up_at',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = [
        'consultation_at' => 'datetime',
        'follow_up_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id', 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class, 'patient_id', 'patient_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'consultation_id', 'consultation_id');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class, 'consultation_id', 'consultation_id');
    }
}
