<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /** Show the login form. */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /** Handle a login attempt. */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('email', $credentials['email'])->first();

        // Unknown email — generic message to avoid enumeration.
        if (! $user) {
            return back()->withErrors([
                'email' => 'The email or password is incorrect.',
            ])->onlyInput('email');
        }

        // Account is temporarily locked.
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $minutes = (int) ceil(now()->diffInSeconds($user->locked_until) / 60);
            return back()->withErrors([
                'email' => "This account is temporarily locked. Try again in {$minutes} minute(s).",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->account_status !== 'Active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'This account is not active. Please contact the clinic.',
                ])->onlyInput('email');
            }

            // Clear lock on successful login.
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until'          => null,
            ]);

            AuditLogger::log('LOGIN', 'Authentication', 'users', $user->user_id, 'User logged in');

            return redirect()->intended(route('dashboard'));
        }

        // Increment failed attempts; lock at 5.
        $attempts = ($user->failed_login_attempts ?? 0) + 1;

        if ($attempts >= 5) {
            $user->update([
                'failed_login_attempts' => $attempts,
                'locked_until'          => now()->addMinutes(30),
            ]);

            AuditLogger::log('LOGIN', 'Authentication', 'users', $user->user_id,
                'Account locked after 5 failed login attempts');

            return back()->withErrors([
                'email' => 'Too many failed attempts. This account has been locked for 30 minutes.',
            ])->onlyInput('email');
        }

        $user->update(['failed_login_attempts' => $attempts]);

        $remaining = 5 - $attempts;
        return back()->withErrors([
            'email' => "The email or password is incorrect. {$remaining} attempt(s) remaining before lockout.",
        ])->onlyInput('email');
    }

    /** Log the user out. */
    public function logout(Request $request)
    {
        $userId = Auth::id();

        AuditLogger::log('LOGOUT', 'Authentication', 'users', $userId, 'User logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
