<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $table = 'lab_results';
    protected $primaryKey = 'result_id';

    protected $fillable = [
        'request_item_id', 'result_value', 'unit', 'reference_range',
        'abnormal_flag', 'remarks', 'workflow_status', 'result_file_path',
        'performed_by', 'checked_by', 'validated_by', 'released_by',
        'result_at', 'released_at',
        'is_identity_verified', 'verification_method', 'identity_verified_by',
        'is_voided', 'void_at', 'void_reason', 'void_approved_by',
    ];

    protected $casts = [
        'result_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(LabRequestItem::class, 'request_item_id', 'request_item_id');
    }
}
