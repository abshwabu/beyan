<?php

namespace App\Domain\Attendance\Policies;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Auth\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance:view') || $user->can('attendance:view-assigned');
    }

    public function view(User $user, AttendanceRecord $attendance): bool
    {
        if ($user->institution_id !== $attendance->institution_id) {
            return false;
        }

        if ($user->can('attendance:view')) {
            return true;
        }

        if ($user->can('attendance:view-assigned')) {
            $student = $attendance->student;

            if ($user->isTeacher() && $student) {
                return $student->isAssignedToTeacher($user);
            }

            if ($user->hasRole('Student') && $student && $student->user_id === $user->id) {
                return true;
            }

            if ($user->hasRole('Guardian') && $student && $student->guardian_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('attendance:record');
    }

    public function update(User $user, AttendanceRecord $attendance): bool
    {
        if ($user->institution_id !== $attendance->institution_id) {
            return false;
        }

        if ($user->can('attendance:update')) {
            return true;
        }

        if ($user->isTeacher() && $user->can('attendance:record')) {
            return $attendance->student && $attendance->student->isAssignedToTeacher($user);
        }

        return false;
    }

    public function delete(User $user, AttendanceRecord $attendance): bool
    {
        if ($user->institution_id !== $attendance->institution_id) {
            return false;
        }

        return $user->can('attendance:update') && $user->isOwner();
    }
}
