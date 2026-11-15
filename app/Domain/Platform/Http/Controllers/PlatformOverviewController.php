<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\DemoRequest;
use App\Domain\Institution\Models\Institution;
use App\Domain\Platform\Models\PlatformPayment;
use App\Domain\Platform\Models\Subscription;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PlatformOverviewController extends Controller
{
    /**
     * Display the Platform Super Admin Overview Dashboard.
     */
    public function index(Request $request): Response|JsonResponse
    {
        // 1. Cached heavy aggregate statistics (cached for 60 seconds)
        $stats = Cache::remember('platform_dashboard_stats', 60, function () {
            $now = Carbon::now();

            // Total institutions by status
            $statusCounts = Institution::groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();

            $statuses = [
                'trial' => Institution::where('subscription_plan', 'trial')->count(),
                'active' => $statusCounts['active'] ?? 0,
                'suspended' => $statusCounts['suspended'] ?? 0,
                'inactive' => $statusCounts['inactive'] ?? 0,
                'expired' => Subscription::where('status', 'expired')->count(),
            ];

            // New institutions this month
            $newThisMonth = Institution::where('created_at', '>=', $now->copy()->startOfMonth())->count();

            // Global platform aggregate counts (strictly anonymous totals)
            $totalStudents = Student::withoutInstitutionScope()->count();
            $totalTeachers = Teacher::withoutInstitutionScope()->count();

            // Monthly Recurring Revenue (MRR) estimate in ETB major units
            // Calculated from active subscriptions or institutions
            $activePlanCounts = Institution::where('status', 'active')
                ->whereNotNull('subscription_plan')
                ->groupBy('subscription_plan')
                ->selectRaw('subscription_plan, count(*) as count')
                ->pluck('count', 'subscription_plan')
                ->toArray();

            $plans = Plan::all()->keyBy('slug');
            $mrr = 0;

            foreach ($activePlanCounts as $slug => $count) {
                if (isset($plans[$slug])) {
                    $mrr += ($plans[$slug]->monthly_price_major * $count);
                }
            }

            // Trials ending in the next 7 days
            $trialsEndingSoon = Institution::where('subscription_plan', 'trial')
                ->whereBetween('subscription_ends_at', [$now, $now->copy()->addDays(7)])
                ->count();

            // Overdue subscriptions
            $overdueSubscriptions = Subscription::whereIn('status', ['grace_period', 'expired'])->count();

            return [
                'total_institutions' => Institution::count(),
                'statuses' => $statuses,
                'new_this_month' => $newThisMonth,
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'mrr_etb' => round($mrr, 2),
                'trials_ending_soon' => $trialsEndingSoon,
                'overdue_subscriptions' => $overdueSubscriptions,
            ];
        });

        // 2. Real-time recent items (lightweight queries)
        $recentDemoRequests = DemoRequest::latest()->take(5)->get();
        $recentAuditEvents = AuditLog::with('user')->latest()->take(8)->get();
        $recentPayments = PlatformPayment::with('institution')->latest()->take(5)->get();

        if ($request->wantsJson()) {
            return response()->json([
                'stats' => $stats,
                'recent_demo_requests' => $recentDemoRequests,
                'recent_audit_events' => $recentAuditEvents,
                'recent_payments' => $recentPayments,
            ]);
        }

        return Inertia::render('Platform/Overview', [
            'stats' => $stats,
            'recentDemoRequests' => $recentDemoRequests,
            'recentAuditEvents' => $recentAuditEvents,
            'recentPayments' => $recentPayments,
        ]);
    }
}
