<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * A tiny helper to write rows into the audit_logs table.
 *
 * This is intentionally simple (a plain static method) so beginners can read
 * it top-to-bottom. Call it from any controller, for example:
 *
 *   AuditLogger::log('CREATE', 'Appointments', 'appointments', $id, 'Booked an appointment');
 */
class AuditLogger
{
    /**
     * @param  string  $action  One of: CREATE, UPDATE, DELETE, VIEW, LOGIN,
     *                           LOGOUT, VOID, APPROVE, UPLOAD, REQUEST, REJECT, RESTORE
     * @param  string  $module  Domain name, e.g. "Laboratory", "Appointments"
     */
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
