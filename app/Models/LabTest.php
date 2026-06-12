<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTest extends Model
{
    protected $table = 'lab_tests';
    protected $primaryKey = 'lab_test_id';

    protected $fillable = [
        'lab_category_id', 'test_name', 'default_unit',
        'default_reference_range', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LabTestCategory::class, 'lab_category_id', 'lab_category_id');
    }
}
