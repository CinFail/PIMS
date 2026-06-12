<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabRequest extends Model
{
    protected $table = 'lab_requests';
    protected $primaryKey = 'lab_request_id';

    protected $fillable = [
        'patient_id', 'doctor_id', 'request_at', 'priority',
        'clinical_notes', 'status',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = ['request_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id', 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class, 'lab_request_id', 'lab_request_id');
    }
}
