<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DemoRoleSwitchController extends Controller
{
    /** Seeded demo accounts only (see DatabaseSeeder). */
    private const ACCOUNTS = [
        'admin' => 'admin@example.com',
        'staff' => 'staff@example.com',
        'patient' => 'patient@example.com',
    ];

    /**
     * Demo-only quick role switch. Disabled unless DEMO_ROLE_SWITCHER=true
     * and the app is not running in production.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(config('demo.role_switcher') && ! app()->isProduction(), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(self::ACCOUNTS))],
        ]);

        $user = User::where('email', self::ACCOUNTS[$validated['role']])->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        $target = $user->canManagePortal() ? route('admin.dashboard') : route('patient.dashboard');

        return redirect($target)->with(
            'success',
            "Demo mode: signed in as {$user->name} ({$user->role->value})."
        );
    }
}
