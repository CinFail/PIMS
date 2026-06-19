<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Allow the request only if the logged-in user has ONE of the given roles.
     * Usage on a route: ->middleware('role:doctor') or 'role:doctor,super_admin'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // If the route also carries permission: middleware and the user holds
        // that permission, let them through regardless of role.
        foreach ($request->route()->middleware() as $m) {
            if (str_starts_with($m, 'permission:') &&
                $user->hasPermission(substr($m, strlen('permission:')))) {
                return $next($request);
            }
        }

        abort(403, 'You do not have access to this page.');
    }
}
