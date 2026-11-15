<?php

namespace App\Domain\Students\Search;

use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LikeStudentSearch implements StudentSearchInterface
{
    /**
     * Perform indexed LIKE prefix search and filtration.
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Student::query()
            ->with(['guardians', 'currentEnrollment', 'teachers']);

        $user = auth()->user();

        // Enforce teacher assignment isolation if user is a teacher without universal students:view permission
        if ($user && $user->isTeacher() && ! $user->can('students:view')) {
            $query->whereHas('teachers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        // Filter by Status (uses composite index: institution_id, status)
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by Class Enrollment
        if (! empty($filters['class'])) {
            $className = $filters['class'];
            $query->whereHas('currentEnrollment', function ($q) use ($className) {
                $q->where('class_name', $className);
            });
        }

        // Filter by Teacher
        if (! empty($filters['teacher_id'])) {
            $teacherId = $filters['teacher_id'];
            $query->whereHas('teachers', function ($q) use ($teacherId) {
                $q->where('users.id', $teacherId);
            });
        }

        // Search by Name or ID Code (uses composite index: institution_id, full_name and unique student_code index)
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($sub) use ($term) {
                $sub->where('full_name', 'LIKE', "{$term}%")
                    ->orWhere('student_code', 'LIKE', "{$term}%")
                    ->orWhere('admission_number', 'LIKE', "{$term}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->orderBy('full_name', 'asc')->paginate($perPage);
    }
}
