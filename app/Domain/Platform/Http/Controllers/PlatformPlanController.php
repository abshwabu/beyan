<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Finance\Models\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPlanController extends Controller
{
    /**
     * Display listing of all subscription plans.
     */
    public function index(): Response
    {
        $plans = Plan::orderBy('sort_order')->get();

        return Inertia::render('Platform/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show plan creation form.
     */
    public function create(): Response
    {
        return Inertia::render('Platform/Plans/Create');
    }

    /**
     * Store newly created plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plans,slug'],
            'monthly_price_major' => ['required', 'numeric', 'min:0'],
            'yearly_price_major' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'student_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'badge' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $plan = Plan::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'monthly_price' => (int) round($validated['monthly_price_major'] * 100),
            'yearly_price' => (int) round($validated['yearly_price_major'] * 100),
            'currency' => $validated['currency'],
            'student_limit' => $validated['student_limit'] ?? null,
            'user_limit' => $validated['user_limit'] ?? null,
            'features' => $validated['features'] ?? [],
            'badge' => $validated['badge'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('platform.plans.index')
            ->with('success', "Plan '{$plan->name}' created successfully.");
    }

    /**
     * Show plan edit form.
     */
    public function edit(string $id): Response
    {
        $plan = Plan::findOrFail($id);

        return Inertia::render('Platform/Plans/Edit', [
            'plan' => $plan,
        ]);
    }

    /**
     * Update plan details.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'monthly_price_major' => ['required', 'numeric', 'min:0'],
            'yearly_price_major' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'student_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'badge' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $plan->update([
            'name' => $validated['name'],
            'monthly_price' => (int) round($validated['monthly_price_major'] * 100),
            'yearly_price' => (int) round($validated['yearly_price_major'] * 100),
            'currency' => $validated['currency'],
            'student_limit' => $validated['student_limit'] ?? null,
            'user_limit' => $validated['user_limit'] ?? null,
            'features' => $validated['features'] ?? [],
            'badge' => $validated['badge'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('platform.plans.index')
            ->with('success', "Plan '{$plan->name}' updated.");
    }

    /**
     * Delete a plan if unused.
     */
    public function destroy(string $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return redirect()->route('platform.plans.index')
            ->with('success', "Plan '{$plan->name}' deleted.");
    }
}
