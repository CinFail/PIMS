<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // usage: ->middleware('role:doctor') or 'role:doctor,super_admin'
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

        // permission middleware on the same route can also satisfy access
        foreach ($request->route()->middleware() as $m) {
            if (str_starts_with($m, 'permission:') &&
                $user->hasPermission(substr($m, strlen('permission:')))) {
                return $next($request);
            }
        }

        abort(403, 'You do not have access to this page.');
    }
}
