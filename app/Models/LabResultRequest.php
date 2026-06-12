<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResultRequest extends Model
{
    protected $table = 'lab_result_requests';
    protected $primaryKey = 'result_request_id';

    protected $fillable = [
        'result_id', 'patient_id', 'doctor_id', 'requested_at',
        'status', 'fulfilled_by', 'fulfilled_at',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(LabResult::class, 'result_id', 'result_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientProfile::class, 'patient_id', 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id', 'doctor_id');
    }
}
