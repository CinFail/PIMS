<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient_id', 'doctor_id', 'duty_session_id', 'appointment_at',
        'duration_minutes', 'rescheduled_from_id', 'reason_for_visit',
        'appointment_type', 'status_id',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = ['appointment_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id', 'doctor_id');
    }

    public function dutySession(): BelongsTo
    {
        return $this->belongsTo(DoctorDutySession::class, 'duty_session_id', 'duty_session_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AppointmentStatus::class, 'status_id', 'appointment_status_id');
    }
}
