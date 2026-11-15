<?php

namespace App\Domain\Academics\Services;

use App\Domain\Academics\Exceptions\TimetableConflictException;
use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimetableConflictService
{
    /**
     * Check if a teacher has an overlapping session inside an immediate transaction.
     * Overlap rule: (start_time_utc < $endTimeUtc) AND (end_time_utc > $startTimeUtc).
     *
     * @throws TimetableConflictException
     */
    public function assertNoTeacherConflict(
        string $institutionId,
        string $teacherId,
        Carbon $startTimeUtc,
        Carbon $endTimeUtc,
        ?string $excludeSessionId = null
    ): void {
        $teacher = Teacher::withoutInstitutionScope()->find($teacherId);
        $teacherName = $teacher ? $teacher->full_name : "Teacher #{$teacherId}";

        $query = ClassSession::withoutInstitutionScope()
            ->with('classGroup')
            ->where('institution_id', $institutionId)
            ->where('teacher_id', $teacherId)
            ->where('status', '!=', 'cancelled')
            ->where('start_time_utc', '<', $endTimeUtc)
            ->where('end_time_utc', '>', $startTimeUtc);

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        $conflict = $query->first();

        if ($conflict) {
            $groupName = $conflict->classGroup ? $conflict->classGroup->name : 'Unknown Class';
            $cStart = Carbon::parse($conflict->start_time_utc)->format('H:i');
            $cEnd = Carbon::parse($conflict->end_time_utc)->format('H:i');
            $date = Carbon::parse($conflict->scheduled_date)->format('Y-m-d');

            throw new TimetableConflictException(
                "Timetable conflict: {$teacherName} already has an overlapping session in '{$groupName}' from {$cStart} to {$cEnd} UTC on {$date}."
            );
        }
    }

    /**
     * Check if a student has an overlapping session inside an immediate transaction.
     *
     * @throws TimetableConflictException
     */
    public function assertNoStudentConflict(
        string $institutionId,
        string $studentId,
        Carbon $startTimeUtc,
        Carbon $endTimeUtc,
        ?string $excludeSessionId = null
    ): void {
        $student = Student::withoutInstitutionScope()->find($studentId);
        $studentName = $student ? ($student->full_name ?: $student->name) : "Student #{$studentId}";
        $scheduledDate = $startTimeUtc->toDateString();

        // Find active enrolled class groups for the student on this date
        $enrolledGroupIds = ClassEnrollment::withoutInstitutionScope()
            ->where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->where('status', 'enrolled')
            ->where('start_date', '<=', $scheduledDate)
            ->where(function ($q) use ($scheduledDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $scheduledDate);
            })
            ->pluck('class_group_id')
            ->toArray();

        if (empty($enrolledGroupIds)) {
            return;
        }

        $query = ClassSession::withoutInstitutionScope()
            ->with('classGroup')
            ->where('institution_id', $institutionId)
            ->whereIn('class_group_id', $enrolledGroupIds)
            ->where('status', '!=', 'cancelled')
            ->where('start_time_utc', '<', $endTimeUtc)
            ->where('end_time_utc', '>', $startTimeUtc);

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        $conflict = $query->first();

        if ($conflict) {
            $groupName = $conflict->classGroup ? $conflict->classGroup->name : 'Unknown Class';
            $cStart = Carbon::parse($conflict->start_time_utc)->format('H:i');
            $cEnd = Carbon::parse($conflict->end_time_utc)->format('H:i');
            $date = Carbon::parse($conflict->scheduled_date)->format('Y-m-d');

            throw new TimetableConflictException(
                "Timetable conflict: {$studentName} is already enrolled in an overlapping session in '{$groupName}' from {$cStart} to {$cEnd} UTC on {$date}."
            );
        }
    }

    /**
     * Assign a teacher to a session inside an IMMEDIATE transaction with conflict check.
     */
    public function assignTeacherToSession(ClassSession $session, Teacher $teacher): void
    {
        DB::transaction(function () use ($session, $teacher) {
            $this->assertNoTeacherConflict(
                $session->institution_id,
                $teacher->id,
                Carbon::parse($session->start_time_utc),
                Carbon::parse($session->end_time_utc),
                $session->id
            );

            $session->update(['teacher_id' => $teacher->id]);
        });
    }

    /**
     * Create a class session inside an IMMEDIATE transaction with conflict check.
     *
     * @param array{
     *     institution_id: string,
     *     class_group_id: string,
     *     teacher_id?: ?string,
     *     scheduled_date: string,
     *     start_time_utc: Carbon|string,
     *     end_time_utc: Carbon|string,
     *     type?: ?string,
     *     meeting_link?: ?string,
     *     room?: ?string,
     *     status?: ?string,
     *     notes?: ?string
     * } $data
     */
    public function createSession(array $data): ClassSession
    {
        return DB::transaction(function () use ($data) {
            $startUtc = $data['start_time_utc'] instanceof Carbon
                ? $data['start_time_utc']
                : Carbon::parse($data['start_time_utc'], 'UTC');

            $endUtc = $data['end_time_utc'] instanceof Carbon
                ? $data['end_time_utc']
                : Carbon::parse($data['end_time_utc'], 'UTC');

            if (! empty($data['teacher_id'])) {
                $this->assertNoTeacherConflict(
                    $data['institution_id'],
                    $data['teacher_id'],
                    $startUtc,
                    $endUtc
                );
            }

            return ClassSession::withoutInstitutionScope()->create([
                'institution_id' => $data['institution_id'],
                'class_group_id' => $data['class_group_id'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'scheduled_date' => $data['scheduled_date'],
                'start_time_utc' => $startUtc,
                'end_time_utc' => $endUtc,
                'type' => $data['type'] ?? 'physical',
                'meeting_link' => $data['meeting_link'] ?? null,
                'room' => $data['room'] ?? null,
                'status' => $data['status'] ?? 'scheduled',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Transfer a student to another class group with effective-dated history retention.
     */
    public function transferStudent(
        Student $student,
        ClassGroup $newClassGroup,
        ?string $transferDate = null,
        ?string $notes = null
    ): ClassEnrollment {
        return DB::transaction(function () use ($student, $newClassGroup, $transferDate, $notes) {
            $effectiveDate = $transferDate ? Carbon::parse($transferDate) : Carbon::today();

            // End any current active enrollments
            ClassEnrollment::withoutInstitutionScope()
                ->where('institution_id', $student->institution_id)
                ->where('student_id', $student->id)
                ->whereNull('end_date')
                ->where('status', 'enrolled')
                ->update([
                    'end_date' => $effectiveDate,
                    'status' => 'transferred',
                ]);

            // Create new effective-dated enrollment
            return ClassEnrollment::withoutInstitutionScope()->create([
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'class_group_id' => $newClassGroup->id,
                'start_date' => $effectiveDate,
                'end_date' => null,
                'status' => 'enrolled',
                'notes' => $notes,
            ]);
        });
    }
}
