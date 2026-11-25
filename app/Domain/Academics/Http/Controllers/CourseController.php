<?php

namespace App\Domain\Academics\Http\Controllers;

use App\Domain\Academics\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display the institution's academic courses.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $institutionId = $request->user()->institution_id;

        $courses = Course::where('institution_id', $institutionId)
            ->withCount('classGroups')
            ->latest()
            ->get()
            ->map(function (Course $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'level' => $c->level,
                    'duration' => $c->duration,
                    'learning_objectives' => $c->learning_objectives ?? [],
                    'description' => $c->description,
                    'status' => $c->status,
                    'class_groups_count' => $c->class_groups_count,
                    'created_at' => $c->created_at?->format('M d, Y'),
                ];
            });

        if ($request->wantsJson()) {
            return response()->json([
                'courses' => $courses,
            ]);
        }

        return Inertia::render('Academics/Courses/Index', [
            'courses' => $courses,
        ]);
    }

    /**
     * Store a newly created course for the institution.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $institutionId = $request->user()->institution_id;

        // Clean up learning objectives if sent as comma-separated or newline string
        if ($request->has('learning_objectives') && is_string($request->input('learning_objectives'))) {
            $objectives = array_filter(
                array_map('trim', preg_split('/[\r\n,]+/', $request->input('learning_objectives')))
            );
            $request->merge(['learning_objectives' => array_values($objectives)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses')->where(fn ($query) => $query->where('institution_id', $institutionId)),
            ],
            'level' => ['required', 'string', 'in:beginner,intermediate,advanced,all_levels'],
            'duration' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
        ]);

        $course = Course::create([
            'institution_id' => $institutionId,
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'level' => $validated['level'],
            'duration' => $validated['duration'] ?? null,
            'description' => $validated['description'] ?? null,
            'learning_objectives' => ! empty($validated['learning_objectives']) ? $validated['learning_objectives'] : null,
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Course '{$course->name}' created successfully.",
                'course' => $course,
            ], 201);
        }

        return redirect()->route('courses.index')
            ->with('success', "Course '{$course->name}' created successfully.");
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institutionId = $request->user()->institution_id;

        $course = Course::where('institution_id', $institutionId)
            ->where('id', $id)
            ->firstOrFail();

        if ($request->has('learning_objectives') && is_string($request->input('learning_objectives'))) {
            $objectives = array_filter(
                array_map('trim', preg_split('/[\r\n,]+/', $request->input('learning_objectives')))
            );
            $request->merge(['learning_objectives' => array_values($objectives)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses')
                    ->where(fn ($query) => $query->where('institution_id', $institutionId))
                    ->ignore($course->id),
            ],
            'level' => ['required', 'string', 'in:beginner,intermediate,advanced,all_levels'],
            'duration' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
        ]);

        $course->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'level' => $validated['level'],
            'duration' => $validated['duration'] ?? null,
            'description' => $validated['description'] ?? null,
            'learning_objectives' => ! empty($validated['learning_objectives']) ? $validated['learning_objectives'] : null,
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Course '{$course->name}' updated successfully.",
                'course' => $course,
            ]);
        }

        return redirect()->route('courses.index')
            ->with('success', "Course '{$course->name}' updated successfully.");
    }

    /**
     * Delete a course if it has no associated class groups.
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $institutionId = $request->user()->institution_id;

        $course = Course::where('institution_id', $institutionId)
            ->where('id', $id)
            ->withCount('classGroups')
            ->firstOrFail();

        if ($course->class_groups_count > 0) {
            $message = "Cannot delete '{$course->name}'. It has {$course->class_groups_count} linked class group(s). Remove or reassign them first.";

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $course->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Course '{$course->name}' deleted successfully.",
            ]);
        }

        return redirect()->route('courses.index')
            ->with('success', "Course '{$course->name}' deleted successfully.");
    }
}
