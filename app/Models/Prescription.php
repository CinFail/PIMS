<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $table = 'prescriptions';
    protected $primaryKey = 'prescription_id';

    protected $fillable = [
        'consultation_id', 'prescribed_by', 'prescribed_at',
        'remarks', 'validity_days',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = ['prescribed_at' => 'datetime'];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id', 'consultation_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'prescribed_by', 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id', 'prescription_id');
    }
}
