<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformImpersonationController extends Controller
{
    /**
     * Start a time-limited (30 min) owner impersonation session.
     * Strictly restricted to Platform Super Admin. Requires typed reason.
     */
    public function start(Request $request, string $institutionId): RedirectResponse
    {
        $admin = $request->user();

        if (! $admin || ! $admin->isPlatformSuperAdmin()) {
            abort(403, 'Unauthorized. Only Platform Super Admin can initiate support impersonation.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $institution = Institution::findOrFail($institutionId);

        // Find institution owner or first admin user
        $targetUser = $institution->users()->where('role', 'owner')->first()
            ?? $institution->users()->first();

        if (! $targetUser) {
            return back()->with('error', 'Cannot impersonate: Institution has no associated users.');
        }

        $expiresAt = Carbon::now()->addMinutes(30)->timestamp;

        // Record start in audit log before switching authentication context
        $institution->recordAudit('impersonation_started', null, [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'target_user_id' => $targetUser->id,
            'target_user_name' => $targetUser->name,
            'reason' => $validated['reason'],
            'expires_at' => Carbon::createFromTimestamp($expiresAt)->toIso8601String(),
        ]);

        // Set impersonation session markers
        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonator_name', $admin->name);
        $request->session()->put('impersonated_institution_id', $institution->id);
        $request->session()->put('impersonated_user_id', $targetUser->id);
        $request->session()->put('impersonation_reason', $validated['reason']);
        $request->session()->put('impersonation_expires_at', $expiresAt);

        // Log in as target user
        Auth::login($targetUser);

        return redirect()->route('dashboard')
            ->with('success', "Support session active for '{$institution->name}'. Auto-expires in 30 minutes.");
    }

    /**
     * Stop active impersonation session and return to platform dashboard.
     */
    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');
        $institutionId = $request->session()->get('impersonated_institution_id');

        if (! $impersonatorId) {
            return redirect()->route('platform.overview');
        }

        $originalAdmin = User::withoutInstitutionScope()->find($impersonatorId);
        $institution = $institutionId ? Institution::find($institutionId) : null;

        if ($institution) {
            $institution->recordAudit('impersonation_stopped', null, [
                'admin_id' => $impersonatorId,
                'admin_name' => $originalAdmin?->name,
                'stopped_at' => Carbon::now()->toIso8601String(),
            ]);
        }

        // Clear impersonation markers from session
        $request->session()->forget([
            'impersonator_id',
            'impersonator_name',
            'impersonated_institution_id',
            'impersonated_user_id',
            'impersonation_reason',
            'impersonation_expires_at',
        ]);

        if ($originalAdmin) {
            Auth::login($originalAdmin);
        }

        $redirectRoute = $institution
            ? route('platform.institutions.show', $institution->id)
            : route('platform.overview');

        return redirect($redirectRoute)->with('success', 'Support impersonation session ended.');
    }
}
