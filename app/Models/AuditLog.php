<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'log_id';

    // This table only ever has logged_at, it is append-only (no updated_at).
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'module_category', 'table_name',
        'record_id', 'description', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'logged_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'logged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
