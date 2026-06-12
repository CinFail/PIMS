<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTestCategory extends Model
{
    protected $table = 'lab_test_categories';
    protected $primaryKey = 'lab_category_id';

    protected $fillable = ['category_name', 'description'];

    public function tests(): HasMany
    {
        return $this->hasMany(LabTest::class, 'lab_category_id', 'lab_category_id');
    }
}
