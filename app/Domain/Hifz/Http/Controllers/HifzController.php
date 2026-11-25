<?php

namespace App\Domain\Hifz\Http\Controllers;

use App\Domain\Auth\Models\User;
use App\Domain\Hifz\Actions\RecordHifzEntryAction;
use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\QuranSurah;
use App\Domain\Hifz\Services\HifzProgressService;
use App\Domain\Hifz\Services\HifzTargetService;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HifzController extends Controller
{
    public function __construct(
        protected RecordHifzEntryAction $recordAction,
        protected HifzProgressService $progressService,
        protected HifzTargetService $targetService
    ) {}

    /**
     * Display recent Hifz records and progress overview.
     */
    public function index(Request $request): Response|JsonResponse
    {
        Gate::authorize('viewAny', HifzEntry::class);

        /** @var User $user */
        $user = $request->user();

        $query = HifzEntry::query()
            ->with(['student', 'teacher.user', 'surah'])
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        // Scope to assigned students if teacher
        if ($user->isTeacher() && ! $user->can('hifz:view')) {
            $assignedStudentIds = $user->assignedStudents()->pluck('students.id');
            $query->whereIn('student_id', $assignedStudentIds);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $entries = $query->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($entries);
        }

        return Inertia::render('Hifz/Index', [
            'entries' => $entries,
            'filters' => $request->only(['type', 'student_id', 'date']),
        ]);
    }

    /**
     * Fast entry screen for teachers: select student, surah and ayah range, rating, save (< 20s entry).
     */
    public function fastEntry(Request $request): Response|JsonResponse
    {
        Gate::authorize('create', HifzEntry::class);

        /** @var User $user */
        $user = $request->user();

        // 1. Available students for this teacher / user
        if ($user->isTeacher() && ! $user->can('students:view')) {
            $students = $user->assignedStudents()
                ->where('students.status', 'active')
                ->select(['students.id', 'students.full_name', 'students.student_code'])
                ->get();
        } else {
            $students = Student::query()
                ->where('status', 'active')
                ->select(['id', 'full_name', 'student_code'])
                ->orderBy('full_name')
                ->get();
        }

        // 2. Reference list of all 114 Surahs
        $surahs = QuranSurah::query()
            ->orderBy('number')
            ->get(['number', 'name', 'english_name', 'arabic_name', 'total_ayahs']);

        // 3. Pre-fill latest sabaq if student_id is provided
        $lastSabaq = null;
        $selectedStudentId = $request->input('student_id');

        if ($selectedStudentId) {
            $lastSabaqEntry = HifzEntry::query()
                ->where('student_id', $selectedStudentId)
                ->where('type', 'sabaq')
                ->with('surah')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            if ($lastSabaqEntry) {
                $lastSabaq = [
                    'surah_number' => $lastSabaqEntry->surah_number,
                    'surah_name' => $lastSabaqEntry->surah?->english_name,
                    'end_ayah' => $lastSabaqEntry->end_ayah,
                    'suggested_start_ayah' => $lastSabaqEntry->end_ayah + 1,
                    'date' => $lastSabaqEntry->date->toDateString(),
                ];
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'students' => $students,
                'surahs' => $surahs,
                'last_sabaq' => $lastSabaq,
                'selected_student_id' => $selectedStudentId,
            ]);
        }

        return Inertia::render('Hifz/FastEntry', [
            'students' => $students,
            'surahs' => $surahs,
            'last_sabaq' => $lastSabaq,
            'selected_student_id' => $selectedStudentId,
        ]);
    }

    /**
     * Store a new Hifz entry.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string'],
            'teacher_id' => ['nullable', 'string'],
            'class_session_id' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'type' => ['required', 'string', 'in:sabaq,sabqi,manzil,reading'],
            'surah_number' => ['required', 'integer', 'min:1', 'max:114'],
            'start_ayah' => ['required', 'integer', 'min:1'],
            'end_ayah' => ['required', 'integer', 'min:1'],
            'quality_rating' => ['nullable', 'string', 'in:excellent,very_good,good,acceptable,poor'],
            'tajweed_rating' => ['nullable', 'string', 'in:excellent,good,needs_work'],
            'mistakes_count' => ['nullable', 'integer', 'min:0'],
            'teacher_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();

        // Resolve teacher id if not provided (fallback to user teacher profile, or auto-link for admin/owner)
        if (empty($validated['teacher_id'])) {
            $teacher = $user->teacherProfile
                ?? Teacher::where('user_id', $user->id)->first()
                ?? Teacher::firstOrCreate(
                    [
                        'institution_id' => $user->institution_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'full_name' => $user->name,
                        'specialization' => 'Qur\'an Instructor',
                        'status' => 'active',
                    ]
                );
            $validated['teacher_id'] = $teacher->id;
        }

        // Check policy on student
        $student = Student::findOrFail($validated['student_id']);
        Gate::authorize('record', [HifzEntry::class, $student]);

        $entry = $this->recordAction->execute($validated);
        $entry->load(['surah', 'teacher.user']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Hifz entry recorded successfully.',
                'entry' => $this->progressService->formatEntry($entry),
            ], 201);
        }

        return redirect()->route('hifz.index')->with('success', 'Hifz entry recorded successfully.');
    }

    /**
     * Display a student's Qur'an progress profile.
     * Shows Sabaq, Sabqi, and Manzil distinctly.
     */
    public function studentProfile(Request $request, Student $student): Response|JsonResponse
    {
        Gate::authorize('view', [HifzEntry::class, $student]);

        $profile = $this->progressService->getStudentProfile($student);

        if ($request->wantsJson()) {
            return response()->json($profile);
        }

        return Inertia::render('Hifz/StudentProfile', [
            'profile' => $profile,
        ]);
    }

    /**
     * Set or update a target for a student.
     */
    public function storeTarget(Request $request, Student $student): RedirectResponse|JsonResponse
    {
        Gate::authorize('manageTargets', [HifzEntry::class, $student]);

        $validated = $request->validate([
            'period' => ['required', 'string', 'in:daily,weekly'],
            'target_type' => ['required', 'string', 'in:sabaq_ayahs,sabaq_pages,sabqi_pages,sabqi_ayahs,manzil_pages,manzil_ayahs,reading_ayahs'],
            'target_value' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $target = $this->targetService->setTarget($student, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Hifz target updated successfully.',
                'target' => $target,
            ]);
        }

        return redirect()->back()->with('success', 'Hifz target updated successfully.');
    }

    /**
     * Record a Hifz completion or Ijazah certification.
     */
    public function storeCertification(Request $request, Student $student): RedirectResponse|JsonResponse
    {
        Gate::authorize('certify', [HifzEntry::class, $student]);

        $validated = $request->validate([
            'teacher_id' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:hifz_completion,juz_completion,ijazah,tajweed_certificate'],
            'title' => ['required', 'string', 'max:255'],
            'riwayah' => ['nullable', 'string', 'max:100'],
            'sanad_chain' => ['nullable', 'string'],
            'completion_date' => ['required', 'date'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        if (empty($validated['teacher_id'])) {
            $teacher = $user->teacherProfile ?? Teacher::where('user_id', $user->id)->first();
            $validated['teacher_id'] = $teacher?->id;
        }

        $certification = $student->hifzCertifications()->create([
            'institution_id' => $student->institution_id,
            'teacher_id' => $validated['teacher_id'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'riwayah' => $validated['riwayah'] ?? 'Hafs an Asim',
            'sanad_chain' => $validated['sanad_chain'] ?? null,
            'completion_date' => $validated['completion_date'],
            'certificate_number' => $validated['certificate_number'] ?? 'CERT-'.strtoupper(uniqid()),
            'grade' => $validated['grade'] ?? 'Mumtaz',
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Certification recorded successfully.',
                'certification' => $certification,
            ], 201);
        }

        return redirect()->back()->with('success', 'Certification recorded successfully.');
    }

    /**
     * Compare progress across two distinct periods.
     */
    public function compareProgress(Request $request, Student $student): JsonResponse
    {
        Gate::authorize('view', [HifzEntry::class, $student]);

        $validated = $request->validate([
            'start_date_a' => ['required', 'date'],
            'end_date_a' => ['required', 'date', 'after_or_equal:start_date_a'],
            'start_date_b' => ['required', 'date'],
            'end_date_b' => ['required', 'date', 'after_or_equal:start_date_b'],
        ]);

        $comparison = $this->progressService->compareProgress(
            $student,
            $validated['start_date_a'],
            $validated['end_date_a'],
            $validated['start_date_b'],
            $validated['end_date_b']
        );

        return response()->json($comparison);
    }
}
