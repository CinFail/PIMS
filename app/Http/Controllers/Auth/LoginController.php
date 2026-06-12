<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
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

        // Auth::attempt works with our custom "password_hash" column because
        // the User model's getAuthPassword() points to it.
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

            AuditLogger::log('LOGIN', 'Authentication', 'users', $user->user_id, 'User logged in');

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The email or password is incorrect.',
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
