<?php

namespace App\Domain\Institution\Http\Controllers;

use App\Domain\Institution\Actions\RegisterInstitutionAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionRegistrationController extends Controller
{
    public function __construct(
        protected RegisterInstitutionAction $registerInstitutionAction
    ) {}

    /**
     * Show the registration form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/RegisterInstitution', [
            'modes' => ['online', 'physical', 'hybrid'],
            'locales' => [
                'en' => 'English',
                'am' => 'አማርኛ (Amharic)',
                'om' => 'Afaan Oromoo',
                'ar' => 'العربية (Arabic)',
            ],
            'timezones' => [
                'Africa/Addis_Ababa' => 'Africa/Addis Ababa (EAT)',
                'UTC' => 'UTC',
                'Asia/Riyadh' => 'Asia/Riyadh (AST)',
                'Europe/London' => 'Europe/London (GMT/BST)',
            ],
            'currencies' => ['ETB', 'USD', 'SAR', 'EUR'],
        ]);
    }

    /**
     * Handle the registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:institutions,slug'],
            'mode' => ['nullable', 'in:online,physical,hybrid'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'currency' => ['nullable', 'string', 'max:10'],
            'locale' => ['nullable', 'in:en,am,om,ar'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->registerInstitutionAction->execute($validated);

        Auth::login($result['user']);

        return redirect()->route('dashboard')->with('success', 'Institution workspace registered successfully!');
    }
}
