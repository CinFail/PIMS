<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    // $action: CREATE, UPDATE, DELETE, VIEW, LOGIN, LOGOUT, VOID, APPROVE, UPLOAD, REQUEST, REJECT, RESTORE
    // $module: domain label e.g. "Laboratory", "Appointments"
    public static function log(
        string $action,
        string $module,
        ?string $table = null,
        ?int $recordId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        AuditLog::create([
            'user_id'         => Auth::id(),
            'action'          => $action,
            'module_category' => $module,
            'table_name'      => $table,
            'record_id'       => $recordId,
            'description'     => $description,
            'old_values'      => $oldValues,
            'new_values'      => $newValues,
            'ip_address'      => Request::ip(),
            'user_agent'      => substr((string) Request::userAgent(), 0, 255),
            'logged_at'       => now(),
        ]);
    }
}
