<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorDutySession extends Model
{
    protected $table = 'doctor_duty_sessions';
    protected $primaryKey = 'duty_session_id';

    protected $fillable = [
        'doctor_id', 'duty_date', 'start_time', 'end_time',
        'status', 'assigned_by',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = [
        'duty_date' => 'date',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id', 'doctor_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'duty_session_id', 'duty_session_id');
    }

    public function isTaken(): bool
    {
        return $this->appointments()
            ->where('is_voided', 0)
            ->whereNotIn('status_id', [4, 5, 6, 7])
            ->exists();
    }
}
