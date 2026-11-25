<?php

namespace App\Domain\Academics\Http\Controllers;

use App\Domain\Academics\Exceptions\TimetableConflictException;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSchedule;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Academics\Models\Term;
use App\Domain\Academics\Services\SessionGeneratorService;
use App\Domain\Teachers\Models\Teacher;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClassGroupController extends Controller
{
    public function __construct(
        protected SessionGeneratorService $sessionGenerator
    ) {}

    /**
     * Display the institution's classes/groups and academic courses.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $institutionId = $request->user()->institution_id;

        $classes = ClassGroup::where('institution_id', $institutionId)
            ->with(['course', 'teachers', 'activeEnrollments', 'term', 'schedules'])
            ->latest()
            ->get()
            ->map(function (ClassGroup $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'type' => $c->type,
                    'capacity' => $c->capacity,
                    'room' => $c->room,
                    'meeting_link' => $c->meeting_link,
                    'status' => $c->status,
                    'course' => $c->course ? [
                        'id' => $c->course->id,
                        'name' => $c->course->name,
                        'code' => $c->course->code,
                        'level' => $c->course->level,
                    ] : null,
                    'primary_teacher' => $c->teachers->first() ? [
                        'id' => $c->teachers->first()->id,
                        'name' => $c->teachers->first()->full_name,
                    ] : null,
                    'teachers' => $c->teachers->map(fn ($t) => [
                        'id' => $t->id,
                        'name' => $t->full_name,
                    ]),
                    'schedules' => $c->schedules->sortBy('weekday')->values()->map(fn (ClassSchedule $s) => [
                        'id' => $s->id,
                        'weekday' => $s->weekday,
                        'start_time' => substr($s->start_time, 0, 5),
                        'end_time' => substr($s->end_time, 0, 5),
                    ]),
                    'enrolled_count' => $c->activeEnrollments->count(),
                    'term' => $c->term?->name,
                ];
            });

        $courses = Course::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'level']);

        $teachers = Teacher::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'specialization']);

        $terms = Term::where('institution_id', $institutionId)
            ->latest('start_date')
            ->get(['id', 'name', 'is_current']);

        if ($request->wantsJson()) {
            return response()->json([
                'classes' => $classes,
                'courses' => $courses,
                'teachers' => $teachers,
                'terms' => $terms,
            ]);
        }

        return Inertia::render('Academics/Classes/Index', [
            'classes' => $classes,
            'courses' => $courses,
            'teachers' => $teachers,
            'terms' => $terms,
        ]);
    }

    /**
     * Create a new class group and assign teacher(s) and optional schedule.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        $timezone = $user->institution?->timezone ?? 'Africa/Addis_Ababa';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'string', 'exists:courses,id'],
            'academic_term_id' => ['nullable', 'string'],
            'teacher_id' => ['nullable', 'string', 'exists:teachers,id'],
            'type' => ['required', 'string', 'in:physical,online,hybrid'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'room' => ['nullable', 'string', 'max:100'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['integer', 'min:0', 'max:6'],
            'start_time' => ['nullable', 'required_with:schedule_days', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_with:schedule_days', 'date_format:H:i', 'after:start_time'],
        ]);

        try {
            $classGroup = DB::transaction(function () use ($validated, $institutionId, $timezone) {
                $group = ClassGroup::create([
                    'institution_id' => $institutionId,
                    'course_id' => $validated['course_id'],
                    'academic_term_id' => $validated['academic_term_id'] ?? null,
                    'name' => $validated['name'],
                    'capacity' => $validated['capacity'],
                    'type' => $validated['type'],
                    'room' => $validated['room'] ?? null,
                    'meeting_link' => $validated['meeting_link'] ?? null,
                    'status' => 'active',
                ]);

                if (! empty($validated['teacher_id'])) {
                    $group->teachers()->attach($validated['teacher_id'], [
                        'institution_id' => $institutionId,
                        'is_primary' => true,
                    ]);
                }

                $this->syncClassSchedule($group, $validated, $institutionId, $timezone);

                return $group;
            });
        } catch (TimetableConflictException $e) {
            throw ValidationException::withMessages([
                'start_time' => [$e->getMessage()],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Class {$classGroup->name} created successfully.",
                'class' => $classGroup->load('schedules'),
            ], 201);
        }

        return redirect()->route('classes.index')
            ->with('success', "Class '{$classGroup->name}' created successfully.");
    }

    /**
     * Update an existing class group.
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        $timezone = $user->institution?->timezone ?? 'Africa/Addis_Ababa';
        $classGroup = ClassGroup::where('institution_id', $institutionId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'string', 'exists:courses,id'],
            'academic_term_id' => ['nullable', 'string'],
            'teacher_id' => ['nullable', 'string', 'exists:teachers,id'],
            'type' => ['required', 'string', 'in:physical,online,hybrid'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'room' => ['nullable', 'string', 'max:100'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['integer', 'min:0', 'max:6'],
            'start_time' => ['nullable', 'required_with:schedule_days', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_with:schedule_days', 'date_format:H:i', 'after:start_time'],
        ]);

        try {
            DB::transaction(function () use ($classGroup, $validated, $institutionId, $timezone, $request) {
                $classGroup->update([
                    'name' => $validated['name'],
                    'course_id' => $validated['course_id'],
                    'academic_term_id' => $validated['academic_term_id'] ?? null,
                    'type' => $validated['type'],
                    'capacity' => $validated['capacity'],
                    'room' => $validated['room'] ?? null,
                    'meeting_link' => $validated['meeting_link'] ?? null,
                    'status' => $validated['status'],
                ]);

                if (! empty($validated['teacher_id'])) {
                    $classGroup->teachers()->sync([
                        $validated['teacher_id'] => [
                            'institution_id' => $institutionId,
                            'is_primary' => true,
                        ],
                    ]);
                }

                if ($request->has('schedule_days')) {
                    $this->syncClassSchedule($classGroup, $validated, $institutionId, $timezone);
                }
            });
        } catch (TimetableConflictException $e) {
            throw ValidationException::withMessages([
                'start_time' => [$e->getMessage()],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Class {$classGroup->name} updated successfully.",
                'class' => $classGroup->load('schedules'),
            ]);
        }

        return redirect()->route('classes.index')
            ->with('success', "Class '{$classGroup->name}' updated successfully.");
    }

    /**
     * Sync recurring class schedule and regenerate upcoming sessions for the next 30 days.
     */
    protected function syncClassSchedule(
        ClassGroup $classGroup,
        array $validated,
        string $institutionId,
        string $timezone
    ): void {
        $days = array_values(array_unique(array_map('intval', $validated['schedule_days'] ?? [])));

        // Remove existing schedule templates and future unstarted sessions before regenerating
        ClassSchedule::where('class_group_id', $classGroup->id)->delete();
        ClassSession::where('class_group_id', $classGroup->id)
            ->where('scheduled_date', '>=', Carbon::now($timezone)->toDateString())
            ->where('status', 'scheduled')
            ->delete();

        if (empty($days) || empty($validated['start_time']) || empty($validated['end_time'])) {
            return;
        }

        $teacherId = $validated['teacher_id'] ?? $classGroup->teachers()->value('teachers.id');

        foreach ($days as $weekday) {
            ClassSchedule::create([
                'institution_id' => $institutionId,
                'class_group_id' => $classGroup->id,
                'teacher_id' => $teacherId ?: null,
                'weekday' => $weekday,
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'timezone' => $timezone,
            ]);
        }

        $startDate = Carbon::now($timezone)->startOfDay();
        $endDate = $startDate->copy()->addDays(30);
        $this->sessionGenerator->generateForClassGroup($classGroup, $startDate, $endDate);
    }

    /**
     * Delete or archive a class group.
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        $classGroup = ClassGroup::where('institution_id', $institutionId)->findOrFail($id);

        if ($classGroup->activeEnrollments()->count() > 0) {
            return back()->with('error', "Cannot delete class '{$classGroup->name}' because it has active enrolled students. Reassign or graduate students first.");
        }

        DB::transaction(function () use ($classGroup) {
            $classGroup->teachers()->detach();
            $classGroup->delete();
        });

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Class deleted successfully.']);
        }

        return redirect()->route('classes.index')
            ->with('success', "Class '{$classGroup->name}' deleted successfully.");
    }
}
