<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StudentController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        $query = Student::query();

        // If teacher, only return their assigned students
        if (auth()->user()->isTeacher()) {
            $query->whereHas('teachers', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        return response()->json($query->get());
    }

    public function show(string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('view', $student);

        return response()->json($student);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'admission_number' => ['required', 'string'],
            'gender' => ['nullable', 'string'],
        ]);

        $student = Student::create($validated);

        return response()->json($student, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('update', $student);

        $validated = $request->validate([
            'name' => ['sometimes', 'string'],
        ]);

        $student->update($validated);

        return response()->json($student);
    }

    public function delete(string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('delete', $student);

        $student->delete();

        return response()->json(['message' => 'Student deleted']);
    }
}
