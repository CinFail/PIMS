<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabRequestItem extends Model
{
    protected $table = 'lab_request_items';
    protected $primaryKey = 'request_item_id';

    protected $fillable = [
        'lab_request_id', 'lab_test_id', 'status', 'specimen_type', 'remarks',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'lab_request_id', 'lab_request_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id', 'lab_test_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class, 'request_item_id', 'request_item_id');
    }
}
