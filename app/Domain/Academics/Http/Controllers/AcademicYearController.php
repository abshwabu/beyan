<?php

namespace App\Domain\Academics\Http\Controllers;

use App\Domain\Academics\Models\AcademicYear;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index(): JsonResponse
    {
        $years = AcademicYear::with('terms')->latest()->get();

        return response()->json($years);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'institution_id' => ['nullable', 'string'],
        ]);

        $academicYear = AcademicYear::create($validated);

        return response()->json($academicYear, 201);
    }

    public function show(string $id): JsonResponse
    {
        $year = AcademicYear::with('terms')->findOrFail($id);

        return response()->json($year);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'is_current' => ['sometimes', 'boolean'],
        ]);

        $year->update($validated);

        return response()->json($year);
    }

    public function destroy(string $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);
        $year->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
