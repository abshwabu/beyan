<?php

namespace App\Domain\Institution\Http\Controllers;

use App\Domain\Institution\Models\Institution;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformInstitutionController extends Controller
{
    /**
     * Display a listing of all institutions across the platform.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $institutions = Institution::withCount('users')
            ->latest()
            ->get();

        if ($request->wantsJson()) {
            return response()->json($institutions);
        }

        return Inertia::render('Platform/Institutions/Index', [
            'institutions' => $institutions,
        ]);
    }

    /**
     * Suspend an institution.
     */
    public function suspend(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institution = Institution::findOrFail($id);

        $oldStatus = $institution->status;
        $institution->update(['status' => 'suspended']);

        $institution->recordAudit('status_changed', ['status' => $oldStatus], ['status' => 'suspended']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Institution '{$institution->name}' suspended successfully.",
                'institution' => $institution,
            ]);
        }

        return back()->with('success', "Institution '{$institution->name}' suspended.");
    }

    /**
     * Activate an institution.
     */
    public function activate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institution = Institution::findOrFail($id);

        $oldStatus = $institution->status;
        $institution->update(['status' => 'active']);

        $institution->recordAudit('status_changed', ['status' => $oldStatus], ['status' => 'active']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Institution '{$institution->name}' activated successfully.",
                'institution' => $institution,
            ]);
        }

        return back()->with('success', "Institution '{$institution->name}' activated.");
    }

    /**
     * Edit subscription plan and duration.
     */
    public function updatePlan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'subscription_plan' => ['required', 'string', 'in:free,pilot,standard,premium,enterprise'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $institution = Institution::findOrFail($id);

        $oldPlan = [
            'subscription_plan' => $institution->subscription_plan,
            'subscription_ends_at' => $institution->subscription_ends_at?->toIso8601String(),
        ];

        $institution->update($validated);

        $institution->recordAudit('plan_updated', $oldPlan, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Subscription plan for '{$institution->name}' updated successfully.",
                'institution' => $institution,
            ]);
        }

        return back()->with('success', 'Subscription plan updated.');
    }
}
