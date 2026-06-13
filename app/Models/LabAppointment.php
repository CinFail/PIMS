<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabAppointment extends Model
{
    protected $table = 'lab_appointments';
    protected $primaryKey = 'lab_appointment_id';

    protected $fillable = [
        'patient_id', 'lab_request_id', 'scheduled_at', 'status', 'notes',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = ['scheduled_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class, 'patient_id', 'patient_id');
    }

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'lab_request_id', 'lab_request_id');
    }
}
