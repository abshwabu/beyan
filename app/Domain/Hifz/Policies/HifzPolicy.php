<?php

namespace App\Domain\Hifz\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Students\Models\Student;

class HifzPolicy
{
    /**
     * Determine whether the user can view Hifz entries/overview.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('hifz:view') || $user->can('hifz:view-assigned');
    }

    /**
     * Determine whether the user can view a specific student's Qur'an progress profile.
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->institution_id !== $student->institution_id) {
            return false;
        }

        if ($user->can('hifz:view')) {
            return true;
        }

        if ($user->can('hifz:view-assigned')) {
            if ($user->isTeacher()) {
                return $student->isAssignedToTeacher($user);
            }

            if ($user->hasRole('Student') && $student->user_id === $user->id) {
                return true;
            }

            if ($user->hasRole('Guardian')) {
                return $student->guardians()->where('guardians.user_id', $user->id)->exists()
                    || $student->guardian_id === $user->id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can access entry creation.
     */
    public function create(User $user): bool
    {
        return $user->can('hifz:record');
    }

    /**
     * Determine whether the user can record a Hifz entry.
     */
    public function record(User $user, ?Student $student = null): bool
    {
        if (! $user->can('hifz:record')) {
            return false;
        }

        if ($student && $user->institution_id !== $student->institution_id) {
            return false;
        }

        if ($student && $user->isTeacher()) {
            return $student->isAssignedToTeacher($user);
        }

        return true;
    }

    /**
     * Determine whether the user can manage targets for a student.
     */
    public function manageTargets(User $user, ?Student $student = null): bool
    {
        if (! $user->can('hifz:manage-targets')) {
            return false;
        }

        if ($student && $user->institution_id !== $student->institution_id) {
            return false;
        }

        if ($student && $user->isTeacher()) {
            return $student->isAssignedToTeacher($user);
        }

        return true;
    }

    /**
     * Determine whether the user can issue certifications or Ijazahs.
     */
    public function certify(User $user, ?Student $student = null): bool
    {
        if (! $user->can('hifz:certify')) {
            return false;
        }

        if ($student && $user->institution_id !== $student->institution_id) {
            return false;
        }

        if ($student && $user->isTeacher()) {
            return $student->isAssignedToTeacher($user);
        }

        return true;
    }
}
