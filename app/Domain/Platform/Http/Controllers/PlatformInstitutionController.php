<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Domain\Platform\Models\Subscription;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformInstitutionController extends Controller
{
    /**
     * Searchable, filterable list of all institutions across the platform.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $query = Institution::query();

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($status = $request->query('status')) {
            if ($status === 'trial') {
                $query->where('subscription_plan', 'trial');
            } else {
                $query->where('status', $status);
            }
        }

        if ($plan = $request->query('plan')) {
            $query->where('subscription_plan', $plan);
        }

        if ($mode = $request->query('mode')) {
            $query->where('mode', $mode);
        }

        if ($country = $request->query('country')) {
            $query->where('country', $country);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $institutions = $query->with(['users' => function ($q) {
            $q->where('role', 'owner');
        }])
            ->withCount('users')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($request->has('page') ? $institutions : $institutions->items());
        }

        return Inertia::render('Platform/Institutions/Index', [
            'institutions' => $institutions,
            'filters' => $request->only(['search', 'status', 'plan', 'mode', 'country', 'date_from', 'date_to']),
            'plans' => $plans,
        ]);
    }

    /**
     * Show form to manually create an institution.
     */
    public function create(Request $request): Response
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Platform/Institutions/Create', [
            'plans' => $plans,
            'prefill' => $request->only(['name', 'phone', 'email', 'mode']),
        ]);
    }

    /**
     * Store a manually created institution with owner credentials.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:institutions,slug'],
            'mode' => ['required', 'in:physical,online,hybrid'],
            'timezone' => ['required', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'locale' => ['required', 'in:en,am,om,ar'],
            'country' => ['nullable', 'string', 'max:10'],
            'subscription_plan' => ['required', 'string'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $trialDays = (int) ($validated['trial_days'] ?? 14);

        $institution = DB::transaction(function () use ($validated, $trialDays) {
            $endsAt = Carbon::now()->addDays($trialDays);

            /** @var Institution $inst */
            $inst = Institution::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug']),
                'mode' => $validated['mode'],
                'timezone' => $validated['timezone'],
                'currency' => $validated['currency'],
                'locale' => $validated['locale'],
                'country' => $validated['country'] ?? 'ET',
                'status' => 'active',
                'subscription_plan' => $validated['subscription_plan'],
                'subscription_ends_at' => $endsAt,
            ]);

            // Owner User
            $owner = User::create([
                'institution_id' => $inst->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'phone' => $validated['owner_phone'] ?? null,
                'role' => 'owner',
                'password' => Hash::make($validated['owner_password']),
                'email_verified_at' => Carbon::now(),
            ]);

            // Initial subscription
            $plan = Plan::where('slug', $validated['subscription_plan'])->first();

            Subscription::create([
                'institution_id' => $inst->id,
                'plan_id' => $plan?->id,
                'billing_cycle' => 'monthly',
                'status' => ($validated['subscription_plan'] === 'trial') ? 'trial' : 'active',
                'starts_at' => Carbon::now(),
                'ends_at' => $endsAt,
                'trial_ends_at' => ($validated['subscription_plan'] === 'trial') ? $endsAt : null,
                'grace_days' => 7,
            ]);

            $inst->recordAudit('institution_created_manually', null, [
                'name' => $inst->name,
                'slug' => $inst->slug,
                'owner_email' => $owner->email,
                'plan' => $inst->subscription_plan,
            ]);

            return $inst;
        });

        return redirect()->route('platform.institutions.show', $institution->id)
            ->with('success', "Institution '{$institution->name}' created successfully.");
    }

    /**
     * Show institution detail page with usage metadata.
     * Note: Does NOT expose bulk student records, protecting tenant privacy.
     */
    public function show(Request $request, string $id): Response|JsonResponse
    {
        /** @var Institution $institution */
        $institution = Institution::with([
            'users' => fn ($q) => $q->where('role', 'owner'),
            'latestSubscription.plan',
            'platformPayments' => fn ($q) => $q->latest()->take(10),
        ])->findOrFail($id);

        $owner = $institution->users->first();

        // Anonymous usage counts
        $studentCount = Student::withoutInstitutionScope()
            ->where('institution_id', $institution->id)
            ->count();

        $teacherCount = Teacher::withoutInstitutionScope()
            ->where('institution_id', $institution->id)
            ->count();

        // Recent audit events for this institution
        $auditLogs = AuditLog::with('user')
            ->where('institution_id', $institution->id)
            ->latest()
            ->take(15)
            ->get();

        $data = [
            'institution' => $institution,
            'owner' => $owner,
            'usage' => [
                'student_count' => $studentCount,
                'teacher_count' => $teacherCount,
                'storage_used_bytes' => 1024 * 1024 * 2, // Estimated storage
            ],
            'auditLogs' => $auditLogs,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Platform/Institutions/Show', $data);
    }

    /**
     * Show institution edit page.
     */
    public function edit(string $id): Response
    {
        $institution = Institution::findOrFail($id);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Platform/Institutions/Edit', [
            'institution' => $institution,
            'plans' => $plans,
        ]);
    }

    /**
     * Update institution metadata.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $institution = Institution::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('institutions', 'slug')->ignore($institution->id)],
            'mode' => ['required', 'in:physical,online,hybrid'],
            'timezone' => ['required', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'locale' => ['required', 'in:en,am,om,ar'],
            'country' => ['nullable', 'string', 'max:10'],
        ]);

        $oldValues = $institution->only(array_keys($validated));

        $institution->update($validated);
        $institution->recordAudit('institution_updated', $oldValues, $validated);

        return redirect()->route('platform.institutions.show', $institution->id)
            ->with('success', "Institution '{$institution->name}' updated.");
    }

    /**
     * Suspend an institution. Suspended institutions' users are blocked at login.
     */
    public function suspend(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institution = Institution::findOrFail($id);
        $reason = $request->input('reason', 'Suspended by platform administrator');

        $oldStatus = $institution->status;

        DB::transaction(function () use ($institution, $oldStatus, $reason) {
            $institution->update(['status' => 'suspended']);
            $institution->recordAudit('status_changed', ['status' => $oldStatus], [
                'status' => 'suspended',
                'reason' => $reason,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Institution '{$institution->name}' suspended successfully.",
                'institution' => $institution,
            ]);
        }

        return back()->with('success', "Institution '{$institution->name}' has been suspended.");
    }

    /**
     * Activate a suspended or inactive institution.
     */
    public function activate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institution = Institution::findOrFail($id);
        $oldStatus = $institution->status;

        DB::transaction(function () use ($institution, $oldStatus) {
            $institution->update(['status' => 'active']);
            $institution->recordAudit('status_changed', ['status' => $oldStatus], ['status' => 'active']);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Institution '{$institution->name}' activated successfully.",
                'institution' => $institution,
            ]);
        }

        return back()->with('success', "Institution '{$institution->name}' has been activated.");
    }

    /**
     * Extend institution trial period.
     */
    public function extendTrial(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:180'],
        ]);

        $institution = Institution::findOrFail($id);
        $days = (int) $validated['days'];

        $baseDate = ($institution->subscription_ends_at && $institution->subscription_ends_at->isFuture())
            ? $institution->subscription_ends_at
            : Carbon::now();

        $newEndsAt = $baseDate->copy()->addDays($days);

        DB::transaction(function () use ($institution, $newEndsAt, $days) {
            $oldEnds = $institution->subscription_ends_at;

            $institution->update([
                'subscription_ends_at' => $newEndsAt,
                'status' => ($institution->status === 'suspended') ? 'active' : $institution->status,
            ]);

            if ($subscription = $institution->latestSubscription) {
                $subscription->update([
                    'ends_at' => $newEndsAt,
                    'status' => 'active',
                ]);
            }

            $institution->recordAudit('trial_extended', [
                'subscription_ends_at' => $oldEnds?->toIso8601String(),
            ], [
                'subscription_ends_at' => $newEndsAt->toIso8601String(),
                'days_added' => $days,
            ]);
        });

        return back()->with('success', "Trial extended by {$days} days for '{$institution->name}'.");
    }

    /**
     * Change institution subscription plan.
     */
    public function updatePlan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'subscription_plan' => ['required', 'string'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $institution = Institution::findOrFail($id);

        DB::transaction(function () use ($institution, $validated) {
            $oldPlan = [
                'subscription_plan' => $institution->subscription_plan,
                'subscription_ends_at' => $institution->subscription_ends_at?->toIso8601String(),
            ];

            $institution->update($validated);

            $plan = Plan::where('slug', $validated['subscription_plan'])->first();

            if ($subscription = $institution->latestSubscription) {
                $subscription->update([
                    'plan_id' => $plan?->id,
                    'ends_at' => ! empty($validated['subscription_ends_at']) ? Carbon::parse($validated['subscription_ends_at']) : $subscription->ends_at,
                ]);
            }

            $institution->recordAudit('plan_updated', $oldPlan, $validated);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Subscription plan for '{$institution->name}' updated successfully.",
                'institution' => $institution,
            ]);
        }

        return back()->with('success', "Subscription plan for '{$institution->name}' updated.");
    }

    /**
     * Soft delete an institution.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $institution = Institution::findOrFail($id);

        DB::transaction(function () use ($institution) {
            $institution->recordAudit('institution_deleted', ['name' => $institution->name], null);
            $institution->delete();
        });

        return redirect()->route('platform.institutions.index')
            ->with('success', "Institution '{$institution->name}' has been soft-deleted.");
    }
}
