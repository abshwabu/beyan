<?php

namespace App\Domain\Students\Search;

use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Fts5StudentSearch implements StudentSearchInterface
{
    public function __construct(
        protected LikeStudentSearch $fallback
    ) {}

    /**
     * Perform FTS5 search when available, or delegate to indexed LIKE prefix search.
     */
    public function search(array $filters): LengthAwarePaginator
    {
        // If no text query is given, LikeStudentSearch handles pure filtering most efficiently
        if (empty($filters['search'])) {
            return $this->fallback->search($filters);
        }

        // Check if FTS5 virtual table exists
        if (! Schema::hasTable('students_fts')) {
            return $this->fallback->search($filters);
        }

        try {
            $term = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', trim($filters['search']));
            $term = trim($term);

            if (empty($term)) {
                return $this->fallback->search($filters);
            }

            // FTS5 prefix query: "term*"
            $matchQuery = "{$term}*";

            $matchingIds = DB::table('students_fts')
                ->whereRaw('students_fts MATCH ?', [$matchQuery])
                ->pluck('id')
                ->toArray();

            $query = Student::query()
                ->with(['guardians', 'currentEnrollment', 'teachers'])
                ->whereIn('id', $matchingIds);

            $user = auth()->user();
            if ($user && $user->isTeacher() && ! $user->can('students:view')) {
                $query->whereHas('teachers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['class'])) {
                $className = $filters['class'];
                $query->whereHas('currentEnrollment', function ($q) use ($className) {
                    $q->where('class_name', $className);
                });
            }

            if (! empty($filters['teacher_id'])) {
                $teacherId = $filters['teacher_id'];
                $query->whereHas('teachers', function ($q) use ($teacherId) {
                    $q->where('users.id', $teacherId);
                });
            }

            $perPage = (int) ($filters['per_page'] ?? 15);

            return $query->orderBy('full_name', 'asc')->paginate($perPage);
        } catch (\Throwable $e) {
            // Graceful fallback on syntax or SQLite driver errors
            return $this->fallback->search($filters);
        }
    }
}
