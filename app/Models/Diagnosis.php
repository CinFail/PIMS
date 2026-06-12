<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    protected $table = 'diagnoses';
    protected $primaryKey = 'diagnosis_id';

    protected $fillable = [
        'consultation_id', 'diagnosed_by', 'icd_code', 'description',
        'diagnosis_type', 'diagnosed_at',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = ['diagnosed_at' => 'datetime'];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id', 'consultation_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'diagnosed_by', 'doctor_id');
    }
}
