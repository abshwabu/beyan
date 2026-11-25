<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Reports\Services\ReportExportService;
use App\Domain\Reports\Services\ReportGeneratorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportGeneratorService $generatorService,
        protected ReportExportService $exportService
    ) {}

    /**
     * Display report index dashboard.
     */
    public function index(): Response|JsonResponse
    {
        $this->authorizeReportsAccess();

        $availableReports = [
            [
                'id' => 'students',
                'title' => 'Student Directory',
                'description' => 'Complete student roster, admission numbers, gender, guardian contacts, and status.',
                'icon' => '👨‍🎓',
            ],
            [
                'id' => 'teachers',
                'title' => 'Teacher Roster',
                'description' => 'List of teachers, qualifications, specialization, and assigned class counts.',
                'icon' => '👨‍🏫',
            ],
            [
                'id' => 'attendance',
                'title' => 'Attendance Performance',
                'description' => 'Aggregate attendance rates, present/absent/late counts by student and session.',
                'icon' => '📅',
            ],
            [
                'id' => 'hifz',
                'title' => 'Qur\'an & Hifz Progress',
                'description' => 'Memorization progress, Sabaq positions, revision volumes, and mistake tracking.',
                'icon' => '📖',
            ],
            [
                'id' => 'payments',
                'title' => 'Tuition Payments Ledger',
                'description' => 'Recorded payments, receipt numbers, payment methods, and cash collection totals.',
                'icon' => '💳',
            ],
            [
                'id' => 'classes',
                'title' => 'Class Groups & Timetable',
                'description' => 'Course assignments, assigned teachers, enrollment counts, and room capacity.',
                'icon' => '🏫',
            ],
        ];

        return Inertia::render('Reports/Index', [
            'reports' => $availableReports,
        ]);
    }

    /**
     * Generate and display a specific report with filters.
     */
    public function show(Request $request, string $type): Response|JsonResponse
    {
        $this->authorizeReportsAccess();

        $institution = $this->resolveInstitution($request);

        $report = $this->buildReportData($institution, $type, $request->all());

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        return Inertia::render('Reports/Show', [
            'type' => $type,
            'report' => $report,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Export report data to Excel/CSV or Printable PDF view.
     */
    public function export(Request $request, string $type): StreamedResponse|HttpResponse|JsonResponse
    {
        $this->authorizeReportsAccess();

        $institution = $this->resolveInstitution($request);

        $report = $this->buildReportData($institution, $type, $request->all());
        $format = $request->input('format', 'csv');

        // Printable HTML / PDF view
        if (in_array($format, ['pdf', 'print'], true)) {
            return response()->view('reports.printable', [
                'institution' => $institution,
                'report' => $report,
            ]);
        }

        // CSV / Excel export
        return $this->exportService->exportCsv(
            $type.'_report',
            $report['headers'],
            $report['rows']
        );
    }

    protected function resolveInstitution(Request $request): Institution
    {
        $institution = app(TenantManager::class)->getTenant() ?? $request->user()?->institution;

        if (! $institution) {
            abort(403, 'No active institution context found.');
        }

        return $institution;
    }

    /**
     * Authorize access to reports.
     */
    protected function authorizeReportsAccess(): void
    {
        $user = request()->user();

        // Owner, Admin, Academic Coordinator, Accountant can view reports
        if ($user && ($user->can('finance:view-reports') || $user->can('students:view'))) {
            return;
        }

        abort(403, 'Unauthorized to view institution reports.');
    }

    /**
     * Helper to route report generation to correct service method.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildReportData(Institution $institution, string $type, array $filters): array
    {
        return match ($type) {
            'students' => $this->generatorService->generateStudentListReport($institution, $filters),
            'teachers' => $this->generatorService->generateTeacherListReport($institution, $filters),
            'attendance' => $this->generatorService->generateAttendanceReport($institution, $filters),
            'hifz' => $this->generatorService->generateHifzProgressReport($institution, $filters),
            'payments' => $this->generatorService->generatePaymentsReport($institution, $filters),
            'classes' => $this->generatorService->generateClassReport($institution, $filters),
            default => abort(404, "Report type '{$type}' not supported."),
        };
    }
}
