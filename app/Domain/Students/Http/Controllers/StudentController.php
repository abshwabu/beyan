<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Platform\Services\PlanLimitService;
use App\Domain\Students\Actions\RegisterStudentAction;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentDocument;
use App\Domain\Students\Search\StudentSearchInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function __construct(
        protected StudentSearchInterface $searchService,
        protected RegisterStudentAction $registerAction
    ) {}

    /**
     * Display a searchable, filterable list of students with pagination.
     */
    public function index(Request $request): Response|JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        $filters = $request->only(['search', 'status', 'class', 'teacher_id', 'per_page']);
        $students = $this->searchService->search($filters);

        if ($request->wantsJson()) {
            return response()->json($students);
        }

        return Inertia::render('Students/Index', [
            'students' => $students,
            'filters' => $filters,
        ]);
    }

    /**
     * Show mobile-first student registration form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Student::class);

        return Inertia::render('Students/Create');
    }

    /**
     * Register a new student with race-safe ID code.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Student::class);

        // Check subscription plan student limits
        if ($institution = $request->user()?->institution) {
            app(PlanLimitService::class)->ensureCanAddStudent($institution);
        }

        $validated = $request->validate([
            'full_name' => ['required_without:name', 'nullable', 'string', 'max:255'],
            'name' => ['required_without:full_name', 'nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'admission_date' => ['nullable', 'date'],
            'contact' => ['nullable', 'array'],
            'contact.phone' => ['nullable', 'string'],
            'contact.email' => ['nullable', 'email'],
            'contact.address' => ['nullable', 'string'],
            'initial_class' => ['nullable', 'string'],
            'guardians' => ['nullable', 'array'],
            'guardians.*.full_name' => ['required_with:guardians', 'string'],
            'guardians.*.phone' => ['nullable', 'string'],
            'guardians.*.email' => ['nullable', 'email'],
            'guardians.*.relationship_type' => ['nullable', 'string'],
            'guardians.*.is_primary' => ['nullable', 'boolean'],
            'guardians.*.create_account' => ['nullable', 'boolean'],
        ]);

        $student = $this->registerAction->execute($validated);

        if ($request->wantsJson()) {
            return response()->json($student, 201);
        }

        return redirect()->route('students.show', $student->id)
            ->with('success', "Student {$student->student_code} registered successfully.");
    }

    /**
     * Display student profile.
     */
    public function show(Request $request, string $id): Response|JsonResponse
    {
        $student = Student::with([
            'guardians',
            'currentEnrollment',
            'enrollments',
            'statusHistories.changedBy',
            'documents.uploadedBy',
            'teachers',
        ])->findOrFail($id);

        Gate::authorize('view', $student);

        if ($request->wantsJson()) {
            return response()->json($student);
        }

        return Inertia::render('Students/Show', [
            'student' => $student,
        ]);
    }

    /**
     * Show student edit form.
     */
    public function edit(string $id): Response
    {
        $student = Student::with(['guardians'])->findOrFail($id);

        Gate::authorize('update', $student);

        return Inertia::render('Students/Edit', [
            'student' => $student,
        ]);
    }

    /**
     * Update student profile.
     */
    public function update(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('update', $student);

        $validated = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'admission_date' => ['sometimes', 'nullable', 'date'],
            'contact' => ['sometimes', 'nullable', 'array'],
        ]);

        $student->update($validated);

        if ($request->wantsJson()) {
            return response()->json($student);
        }

        return redirect()->route('students.show', $student->id)
            ->with('success', 'Student details updated successfully.');
    }

    /**
     * Delete student.
     */
    public function delete(string $id): JsonResponse|RedirectResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('delete', $student);

        $student->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Student deleted successfully.']);
        }

        return redirect()->route('students.index')
            ->with('success', 'Student removed successfully.');
    }

    /**
     * Change student status with reason and effective date.
     */
    public function changeStatus(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('update', $student);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,suspended,withdrawn,graduated'],
            'reason' => ['required', 'string', 'max:1000'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $history = $student->changeStatus(
            $validated['status'],
            $validated['reason'],
            $validated['effective_date'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Status updated successfully.',
                'student' => $student->fresh(),
                'history' => $history,
            ]);
        }

        return back()->with('success', "Student status changed to {$validated['status']}.");
    }

    /**
     * Transfer student to a new class with effective-dated history retention.
     */
    public function transferClass(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('update', $student);

        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:255'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment = $student->transferClass(
            $validated['class_name'],
            $validated['transfer_date'] ?? null,
            $validated['notes'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Class transfer recorded successfully.',
                'enrollment' => $enrollment,
            ]);
        }

        return back()->with('success', "Student transferred to {$validated['class_name']}.");
    }

    /**
     * Upload a private student document stored in tenant-scoped directory.
     */
    public function uploadDocument(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $student = Student::findOrFail($id);

        Gate::authorize('update', $student);

        $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('document');
        $fileName = $file->getClientOriginalName();
        $title = $request->input('title') ?: pathinfo($fileName, PATHINFO_FILENAME);

        // Private storage on tenant-scoped path
        $path = $file->store("tenants/{$student->institution_id}/students/{$student->id}/documents", 'local');

        $doc = $student->documents()->create([
            'institution_id' => $student->institution_id,
            'uploaded_by_id' => auth()->id(),
            'title' => $title,
            'file_path' => $path,
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Document uploaded successfully.',
                'document' => $doc,
            ], 201);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Download private student document with authorization check.
     */
    public function downloadDocument(string $studentId, string $documentId): StreamedResponse
    {
        $student = Student::findOrFail($studentId);

        Gate::authorize('view', $student);

        $document = StudentDocument::where('student_id', $student->id)
            ->findOrFail($documentId);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    /**
     * Delete student document and its disk file.
     */
    public function deleteDocument(string $studentId, string $documentId): JsonResponse|RedirectResponse
    {
        $student = Student::findOrFail($studentId);

        Gate::authorize('update', $student);

        $document = StudentDocument::where('student_id', $student->id)
            ->findOrFail($documentId);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Document removed successfully.']);
        }

        return back()->with('success', 'Document removed successfully.');
    }
}
