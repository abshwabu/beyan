<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        // Find user globally across all tenants or platform
        $user = User::withoutInstitutionScope()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Block login for users of suspended or inactive institutions
        if (! empty($user->institution_id)) {
            $institution = $user->institution;

            if ($institution && $institution->status === 'suspended') {
                throw ValidationException::withMessages([
                    'email' => 'Your institution account has been suspended. Please contact platform administration.',
                ]);
            }

            if ($institution && $institution->status === 'inactive') {
                throw ValidationException::withMessages([
                    'email' => 'Your institution account is inactive.',
                ]);
            }
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        if ($user->isPlatformStaff()) {
            $intended = $request->session()->pull('url.intended');
            if ($intended && str_contains($intended, '/platform')) {
                return redirect()->to($intended);
            }

            return redirect()->route('platform.overview');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
