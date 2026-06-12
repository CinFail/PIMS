<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    /** Show all roles. The admin picks one to edit its permissions. */
    public function index()
    {
        $roles = Role::withCount('permissions')->orderBy('role_id')->get();

        return view('admin.roles.index', compact('roles'));
    }

    /** Show the checkbox grid of permissions for one role. */
    public function edit(int $roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $permissions = Permission::orderBy('name')->get();
        $assigned = $role->permissions->pluck('permission_id')->all();

        return view('admin.roles.edit', compact('role', 'permissions', 'assigned'));
    }

    /** Save the chosen permissions for the role. Change is audit-logged. */
    public function update(Request $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);

        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,permission_id'],
        ]);

        $old = $role->permissions()->pluck('permissions.permission_id')->all();
        $new = $data['permissions'] ?? [];

        // sync() updates the role_has_permissions junction in one call.
        $role->permissions()->sync($new);

        AuditLogger::log(
            'UPDATE', 'Access Control', 'role_has_permissions', $role->role_id,
            "Updated permissions for role '{$role->name}'",
            ['permission_ids' => $old],
            ['permission_ids' => $new]
        );

        return redirect()->route('admin.roles.index')
            ->with('status', "Permissions for '{$role->display_name}' have been saved.");
    }
}
