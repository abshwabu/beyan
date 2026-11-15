<?php

namespace App\Domain\Attendance\Http\Controllers;

use App\Domain\Academics\Models\ClassSession;
use App\Domain\Attendance\Actions\RecordSessionAttendanceAction;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Services\AttendanceAnalyticsService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected RecordSessionAttendanceAction $recordAction,
        protected AttendanceAnalyticsService $analyticsService
    ) {}

    /**
     * Display attendance records or session attendance marking view.
     */
    public function index(Request $request): Response|JsonResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        // If session_id is provided, prepare session marking data
        if ($request->filled('session_id')) {
            $session = ClassSession::with([
                'classGroup.course',
                'classGroup.activeEnrollments.student',
                'teacher',
            ])->findOrFail($request->input('session_id'));

            $records = AttendanceRecord::where('class_session_id', $session->id)
                ->get()
                ->keyBy('student_id');

            $enrolledStudents = $session->classGroup->activeEnrollments->map(function ($enrollment) use ($records) {
                $student = $enrollment->student;
                $record = $records->get($student->id);

                return [
                    'student_id' => $student->id,
                    'full_name' => $student->full_name ?: $student->name,
                    'student_code' => $student->student_code ?: $student->admission_number,
                    'gender' => $student->gender,
                    'status' => $record ? $record->status : null,
                    'note' => $record ? ($record->note ?: $record->remarks) : '',
                ];
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'session' => $session,
                    'students' => $enrolledStudents,
                    'teacher_check_in_status' => $session->teacher_attendance_status,
                    'teacher_check_in_notes' => $session->teacher_check_in_notes,
                ]);
            }

            return Inertia::render('Attendance/SessionMarking', [
                'session' => $session,
                'students' => $enrolledStudents,
                'teacherCheckInStatus' => $session->teacher_attendance_status,
                'teacherCheckInNotes' => $session->teacher_check_in_notes,
            ]);
        }

        $query = AttendanceRecord::with('student')->latest();

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        return response()->json($query->paginate(25));
    }

    /**
     * Display single attendance record.
     */
    public function show(string $id): JsonResponse
    {
        $attendance = AttendanceRecord::with('student')->findOrFail($id);

        Gate::authorize('view', $attendance);

        return response()->json($attendance);
    }

    /**
     * Record single attendance (backward-compatible).
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', AttendanceRecord::class);

        $validated = $request->validate([
            'student_id' => ['required', 'string'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,excused'],
            'remarks' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $record = AttendanceRecord::create([
            'institution_id' => auth()->user()->institution_id,
            'student_id' => $validated['student_id'],
            'date' => $validated['date'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? $validated['note'] ?? null,
            'note' => $validated['note'] ?? $validated['remarks'] ?? null,
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]);

        return response()->json($record, 201);
    }

    /**
     * Record whole class attendance for a session idempotently.
     */
    public function saveSessionAttendance(Request $request, string $sessionId): JsonResponse|RedirectResponse
    {
        $session = ClassSession::with('institution')->findOrFail($sessionId);

        $validated = $request->validate([
            'records' => ['required', 'array'],
            'records.*.student_id' => ['required', 'string'],
            'records.*.status' => ['required', 'in:present,absent,late,excused'],
            'records.*.note' => ['nullable', 'string', 'max:500'],
            'teacher_check_in_status' => ['nullable', 'in:present,absent,late,excused'],
            'teacher_check_in_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->recordAction->execute(
            $session,
            $validated['records'],
            auth()->user(),
            $validated['teacher_check_in_status'] ?? null,
            $validated['teacher_check_in_notes'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Attendance saved successfully.',
                'session_id' => $session->id,
            ]);
        }

        return back()->with('success', 'Attendance saved successfully.');
    }

    /**
     * Get computed attendance percentages using SQL aggregate queries.
     */
    public function analytics(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : null;

        if ($request->filled('student_id')) {
            $data = $this->analyticsService->computeStudentPercentage(
                $request->input('student_id'),
                $startDate,
                $endDate
            );

            return response()->json($data);
        }

        if ($request->filled('class_group_id')) {
            $data = $this->analyticsService->computeClassPercentage(
                $request->input('class_group_id'),
                $startDate,
                $endDate
            );

            return response()->json($data);
        }

        $institutionId = auth()->user()->institution_id;
        $periodStart = $startDate ?: Carbon::now()->startOfMonth();
        $periodEnd = $endDate ?: Carbon::now()->endOfMonth();

        $data = $this->analyticsService->computePeriodPercentage($institutionId, $periodStart, $periodEnd);

        return response()->json($data);
    }

    /**
     * Get repeated-absence alerts for dashboard.
     */
    public function flaggedAbsences(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        $threshold = (int) ($request->input('threshold', 3));
        $institutionId = auth()->user()->institution_id;

        $flagged = $this->analyticsService->detectRepeatedAbsences($institutionId, $threshold);

        return response()->json([
            'threshold' => $threshold,
            'flagged_students' => $flagged,
        ]);
    }
}
