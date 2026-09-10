<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::record(
                action: 'failed_login_throttled',
                userId: null,
                targetType: 'user',
                targetReference: $request->input('email'),
                metadata: ['ip' => $request->ip(), 'seconds_remaining' => $seconds]
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists and is active
        if ($user && !$user->is_active) {
            RateLimiter::hit($throttleKey);

            AuditLog::record(
                action: 'failed_login_inactive',
                userId: $user->id,
                targetType: 'user',
                targetReference: $user->email,
                metadata: ['ip' => $request->ip(), 'reason' => 'account_inactive']
            );

            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();

            $authenticatedUser = Auth::user();

            AuditLog::record(
                action: 'login',
                userId: $authenticatedUser->id,
                targetType: 'user',
                targetReference: $authenticatedUser->email,
                metadata: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
            );

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey);

        AuditLog::record(
            action: 'failed_login',
            userId: $user?->id,
            targetType: 'user',
            targetReference: $request->input('email'),
            metadata: ['ip' => $request->ip(), 'reason' => 'invalid_credentials']
        );

        throw ValidationException::withMessages([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::record(
                action: 'logout',
                userId: $user->id,
                targetType: 'user',
                targetReference: $user->email,
                metadata: ['ip' => $request->ip()]
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
