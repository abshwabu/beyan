<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Finance\Models\Plan;
use App\Domain\Platform\Models\Subscription;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSubscriptionController extends Controller
{
    /**
     * Display list of all subscriptions with filtering.
     */
    public function index(Request $request): Response
    {
        $query = Subscription::with(['institution', 'plan']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($planId = $request->query('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($request->boolean('overdue')) {
            $query->whereIn('status', ['grace_period', 'expired']);
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString();
        $plans = Plan::all();

        return Inertia::render('Platform/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['status', 'plan_id', 'overdue']),
            'plans' => $plans,
        ]);
    }

    /**
     * Extend subscription by X days.
     */
    public function extend(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $subscription = Subscription::with('institution')->findOrFail($id);
        $days = (int) $validated['days'];

        $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture())
            ? $subscription->ends_at
            : Carbon::now();

        $newEndsAt = $baseDate->copy()->addDays($days);

        $subscription->update([
            'ends_at' => $newEndsAt,
            'status' => 'active',
        ]);

        if ($institution = $subscription->institution) {
            $institution->update([
                'subscription_ends_at' => $newEndsAt,
                'status' => ($institution->status === 'suspended') ? 'active' : $institution->status,
            ]);
            $institution->recordAudit('subscription_extended', null, [
                'days' => $days,
                'ends_at' => $newEndsAt->toIso8601String(),
            ]);
        }

        return back()->with('success', "Subscription extended by {$days} days.");
    }
}
