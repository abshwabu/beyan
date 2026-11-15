<?php

namespace App\Domain\Attendance\Http\Controllers;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        return response()->json(AttendanceRecord::latest()->get());
    }

    public function show(string $id): JsonResponse
    {
        $attendance = AttendanceRecord::with('student')->findOrFail($id);

        Gate::authorize('view', $attendance);

        return response()->json($attendance);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', AttendanceRecord::class);

        $validated = $request->validate([
            'student_id' => ['required', 'string'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late,excused'],
            'remarks' => ['nullable', 'string'],
        ]);

        $record = AttendanceRecord::create([
            ...$validated,
            'recorded_by' => auth()->id(),
        ]);

        return response()->json($record, 201);
    }
}
