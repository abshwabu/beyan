<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Actions\InviteUserAction;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\UserInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class UserInvitationController extends Controller
{
    public function __construct(
        protected InviteUserAction $inviteUserAction
    ) {}

    /**
     * Invite a new user to the active institution.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string'],
        ]);

        $invitation = $this->inviteUserAction->execute([
            'email' => $validated['email'],
            'roles' => $validated['roles'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Invitation created and dispatched successfully.',
                'invitation' => $invitation,
                'accept_url' => $invitation->accept_url,
            ], 201);
        }

        return back()->with('success', 'Invitation dispatched successfully.')
            ->with('accept_url', $invitation->accept_url);
    }

    /**
     * Show the invitation acceptance form.
     */
    public function showAccept(string $token): Response
    {
        $invitation = UserInvitation::withoutInstitutionScope()
            ->with('institution')
            ->where('token', $token)
            ->firstOrFail();

        if (! $invitation->isPending()) {
            abort(410, 'This invitation has expired or has already been accepted.');
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'email' => $invitation->email,
            'institution' => $invitation->institution,
            'roles' => $invitation->roles,
        ]);
    }

    /**
     * Process invitation acceptance and create the user account with assigned roles.
     */
    public function accept(Request $request, string $token): RedirectResponse|JsonResponse
    {
        $invitation = UserInvitation::withoutInstitutionScope()
            ->where('token', $token)
            ->firstOrFail();

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => 'This invitation has expired or has already been accepted.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated) {
            $user = User::withoutInstitutionScope()->create([
                'institution_id' => $invitation->institution_id,
                'name' => $validated['name'],
                'email' => $invitation->email,
                'role' => $invitation->roles[0] ?? 'member',
                'password' => Hash::make($validated['password']),
                'email_verified_at' => Carbon::now(),
            ]);

            // Assign roles scoped to the institution
            app(PermissionRegistrar::class)->setPermissionsTeamId($invitation->institution_id);
            $user->assignRolesWithAudit(...$invitation->roles);

            $invitation->update(['status' => 'accepted']);

            return $user;
        });

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Account setup complete.',
                'user' => $user,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Welcome! Your account has been initialized.');
    }
}
