<?php

namespace App\Domain\Institution\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\UserInvitation;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Teachers\Models\Teacher;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends Controller
{
    /**
     * Display the institution's staff members and pending invitations.
     */
    public function index(Request $request): Response|JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        $institutionId = $currentUser->institution_id;

        $staffUsers = User::where('institution_id', $institutionId)
            ->with(['roles', 'teacherProfile'])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'role' => $u->role,
                    'roles' => $u->roles->pluck('name')->toArray(),
                    'is_teacher' => $u->teacherProfile !== null,
                    'specialization' => $u->teacherProfile?->specialization,
                    'created_at' => $u->created_at?->format('M d, Y'),
                ];
            });

        $pendingInvitations = UserInvitation::where('institution_id', $institutionId)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('created_at')
            ->get()
            ->map(function (UserInvitation $inv) {
                return [
                    'id' => $inv->id,
                    'email' => $inv->email,
                    'roles' => $inv->roles,
                    'accept_url' => $inv->accept_url,
                    'expires_at' => $inv->expires_at->diffForHumans(),
                    'created_at' => $inv->created_at?->format('M d, Y'),
                ];
            });

        $assignableRoles = [
            'Admin',
            'Academic Coordinator',
            'Teacher',
            'Accountant',
            'Receptionist',
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'staff' => $staffUsers,
                'pending_invitations' => $pendingInvitations,
                'roles' => $assignableRoles,
            ]);
        }

        return Inertia::render('Staff/Index', [
            'staff' => $staffUsers,
            'pendingInvitations' => $pendingInvitations,
            'assignableRoles' => $assignableRoles,
        ]);
    }

    /**
     * Directly create a staff member account with assigned role.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        $institutionId = $currentUser->institution_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:Admin,Academic Coordinator,Teacher,Accountant,Receptionist'],
            'specialization' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated, $institutionId) {
            $user = User::withoutInstitutionScope()->create([
                'institution_id' => $institutionId,
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
                'role' => strtolower(str_replace(' ', '_', $validated['role'])),
                'password' => Hash::make($validated['password']),
                'email_verified_at' => Carbon::now(),
            ]);

            // Assign Spatie Role scoped to institution
            app(PermissionRegistrar::class)->setPermissionsTeamId($institutionId);
            $user->assignRolesWithAudit($validated['role']);

            // If the role is Teacher, create corresponding Teacher profile
            if ($validated['role'] === 'Teacher') {
                Teacher::withoutInstitutionScope()->create([
                    'institution_id' => $institutionId,
                    'user_id' => $user->id,
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'specialization' => $validated['specialization'] ?? 'Hifz & Tajweed',
                    'qualifications' => ['Certified Instructor'],
                    'status' => 'active',
                ]);
            }

            return $user;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Staff member {$user->name} created successfully.",
                'user' => $user,
            ], 201);
        }

        return redirect()->route('staff.index')
            ->with('success', "Staff member {$user->name} ({$validated['role']}) added successfully.");
    }

    /**
     * Remove a staff user from the institution.
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        if ($currentUser->id === $id) {
            return back()->with('error', 'You cannot remove your own account.');
        }

        $user = User::where('institution_id', $currentUser->institution_id)->findOrFail($id);

        if ($user->role === 'owner') {
            return back()->with('error', 'The institution Owner cannot be removed.');
        }

        DB::transaction(function () use ($user) {
            // Delete associated teacher profile if exists
            Teacher::where('user_id', $user->id)->delete();
            $user->delete();
        });

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Staff member removed successfully.']);
        }

        return redirect()->route('staff.index')
            ->with('success', 'Staff member removed successfully.');
    }
}
