<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Models\PlatformSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingsController extends Controller
{
    /**
     * Display current platform settings.
     */
    public function index(): Response
    {
        $settings = PlatformSetting::allWithDefaults();

        return Inertia::render('Platform/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update platform settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'default_grace_days' => ['required', 'integer', 'min:0', 'max:60'],
            'default_currency' => ['required', 'string', 'max:10'],
            'support_phone' => ['required', 'string', 'max:50'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_telegram' => ['nullable', 'string', 'max:100'],
            'maintenance_mode' => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        return back()->with('success', 'Platform settings updated successfully.');
    }
}
