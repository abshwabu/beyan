<?php

namespace App\Domain\Institution\Http\Controllers;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Services\DashboardMetricsService;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardMetricsService $metricsService
    ) {}

    public function __invoke(Request $request, TenantManager $tenantManager): Response|JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        // Platform staff operating outside a tenant context belong in the platform hub
        if ($user && $user->isPlatformStaff()) {
            return redirect()->route('platform.overview');
        }

        $institution = $tenantManager->getTenant();

        if (! $institution) {
            abort(403, 'No institution workspace found.');
        }

        // 1. Determine user dashboard role view
        $isTeacherOnly = $user->isTeacher() && ! $user->can('students:view');

        $adminMetrics = ! $isTeacherOnly ? $this->metricsService->getAdminDashboardMetrics($institution) : null;
        $teacherMetrics = ($user->isTeacher() || $isTeacherOnly) ? $this->metricsService->getTeacherDashboardMetrics($institution, $user) : null;

        $academicYears = AcademicYear::with('terms')->latest()->get();
        $currentYear = AcademicYear::where('is_current', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        $data = [
            'institution' => $institution,
            'is_teacher_only' => $isTeacherOnly,
            'admin_metrics' => $adminMetrics,
            'teacher_metrics' => $teacherMetrics,
            'academicYears' => $academicYears,
            'currentYear' => $currentYear,
            'currentTerm' => $currentTerm,
            'stats' => [
                'usersCount' => $adminMetrics['active_students_count'] ?? 0,
                'academicYearsCount' => $academicYears->count(),
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Dashboard', $data);
    }
}
