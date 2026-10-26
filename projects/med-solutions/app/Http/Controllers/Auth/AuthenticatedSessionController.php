<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the sign-in view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate login credentials with brute-force rate limiting & session regeneration.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many failed sign-in attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300);

            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = $request->user();
        $intended = $request->session()->pull('url.intended');
        $sameSite = str_starts_with((string) $intended, url('/'));
        $intendedPath = $sameSite ? (parse_url($intended, PHP_URL_PATH) ?? '/') : '/';

        // A patient who was bounced from /admin must not be sent back there after sign-in.
        $target = ($sameSite && $user->canAccessPath($intendedPath))
            ? $intended
            : route($user->panelRoute(), absolute: false);

        return redirect($target);
    }

    /**
     * Destroy an authenticated session securely.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out securely.');
    }
}
