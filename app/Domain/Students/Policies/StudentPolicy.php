<?php

namespace App\Domain\Students\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Students\Models\Student;

class StudentPolicy
{
    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('students:view') || $user->can('students:view-assigned');
    }

    /**
     * Determine whether the user can view the specific student.
     */
    public function view(User $user, Student $student): bool
    {
        // Enforce institution tenant boundary
        if ($user->institution_id !== $student->institution_id) {
            return false;
        }

        // Roles with general students:view permission (Owner, Admin, Academic Coordinator, Accountant, Receptionist)
        if ($user->can('students:view')) {
            return true;
        }

        // Roles with students:view-assigned (Teacher, Student, Guardian)
        if ($user->can('students:view-assigned')) {
            if ($user->isTeacher()) {
                // A Teacher can ONLY open their assigned students, never unassigned students!
                return $student->isAssignedToTeacher($user);
            }

            if ($user->hasRole('Student') && $student->user_id === $user->id) {
                return true;
            }

            if ($user->hasRole('Guardian')) {
                if ($student->guardian_id === $user->id) {
                    return true;
                }

                return $student->guardians()->where('guardians.user_id', $user->id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create students.
     */
    public function create(User $user): bool
    {
        return $user->can('students:create');
    }

    /**
     * Determine whether the user can update the student.
     */
    public function update(User $user, Student $student): bool
    {
        if ($user->institution_id !== $student->institution_id) {
            return false;
        }

        return $user->can('students:update');
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        if ($user->institution_id !== $student->institution_id) {
            return false;
        }

        return $user->can('students:delete');
    }
}
