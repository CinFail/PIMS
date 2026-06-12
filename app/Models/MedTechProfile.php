<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedTechProfile extends Model
{
    protected $table = 'med_tech_profiles';
    protected $primaryKey = 'medtech_id';

    protected $fillable = ['user_id', 'license_number'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
