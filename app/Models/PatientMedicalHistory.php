<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMedicalHistory extends Model
{
    protected $table = 'patient_medical_histories';
    protected $primaryKey = 'medical_history_id';

    protected $fillable = [
        'patient_id', 'allergies', 'chronic_conditions', 'past_surgeries',
        'current_medications', 'family_history', 'notes',
    ];
}
