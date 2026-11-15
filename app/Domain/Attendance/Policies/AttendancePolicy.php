<?php

namespace App\Domain\Attendance\Policies;

use App\Domain\Academics\Models\ClassSession;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Auth\Models\User;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;

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

    /**
     * Determine whether the user can record/mark attendance for a specific class session.
     * Acceptance rule: A Teacher can ONLY mark sessions they are assigned to.
     */
    public function recordSession(User $user, ClassSession $session): bool
    {
        if ($user->institution_id !== $session->institution_id) {
            return false;
        }

        // Owner, Admin, Academic Coordinator with attendance:record can mark any session
        if ($user->can('attendance:record') && ($user->isOwner() || $user->hasRole('Admin') || $user->hasRole('Academic Coordinator'))) {
            return true;
        }

        // A Teacher can ONLY mark sessions they are assigned to
        if ($user->isTeacher() && $user->can('attendance:record')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (! $teacher) {
                return false;
            }

            if ($session->teacher_id === $teacher->id) {
                return true;
            }

            return $session->classGroup()
                ->whereHas('teachers', fn ($q) => $q->where('teachers.id', $teacher->id))
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can update the attendance record.
     * Acceptance rule: Editing past attendance requires 'attendance:edit-past' permission.
     */
    public function update(User $user, AttendanceRecord $attendance): bool
    {
        if ($user->institution_id !== $attendance->institution_id) {
            return false;
        }

        $tz = $user->institution->timezone ?? 'UTC';
        $today = Carbon::now($tz)->toDateString();
        $isPast = $attendance->date && $attendance->date->toDateString() < $today;

        // If editing past attendance, user MUST have 'attendance:edit-past' permission
        if ($isPast && ! $user->can('attendance:edit-past')) {
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
