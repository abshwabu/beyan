<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformStaffController extends Controller
{
    /**
     * Display listing of Platform Staff users.
     */
    public function index(): Response
    {
        $staff = User::whereNull('institution_id')
            ->whereIn('role', ['platform_super_admin', 'platform_support'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Platform/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    /**
     * Store newly created platform staff member.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'in:platform_super_admin,platform_support'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'institution_id' => null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => Carbon::now(),
        ]);

        return redirect()->route('platform.staff.index')
            ->with('success', "Staff member '{$user->name}' added.");
    }

    /**
     * Update an existing platform staff user.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $user = User::whereNull('institution_id')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'in:platform_super_admin,platform_support'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('platform.staff.index')
            ->with('success', "Staff member '{$user->name}' updated.");
    }

    /**
     * Delete a platform staff member (prevents deleting oneself).
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        if ($request->user()->id === $id) {
            return back()->with('error', 'You cannot remove your own administrator account.');
        }

        $user = User::whereNull('institution_id')->findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()->route('platform.staff.index')
            ->with('success', "Staff member '{$name}' removed.");
    }
}
