<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Academics\Models\ClassSession;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Policies\AttendancePolicy;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordSessionAttendanceAction
{
    /**
     * Record or update whole class attendance for a session idempotently in a single short transaction.
     *
     * @param  array<int, array{
     *     student_id: string,
     *     status: string,
     *     note?: ?string
     * }>  $records
     *
     * @throws AuthorizationException
     */
    public function execute(
        ClassSession $session,
        array $records,
        User $recorder,
        ?string $teacherCheckInStatus = null,
        ?string $teacherCheckInNotes = null
    ): void {
        $policy = app(AttendancePolicy::class);

        // 1. Authorize session marking
        if (! $policy->recordSession($recorder, $session)) {
            throw new AuthorizationException('A Teacher can only mark sessions they are assigned to.');
        }

        // 2. Authorize past attendance editing
        $tz = $session->institution->timezone ?? 'UTC';
        $today = Carbon::now($tz)->toDateString();
        $isPastSession = Carbon::parse($session->scheduled_date)->toDateString() < $today;

        $hasExistingRecords = AttendanceRecord::withoutInstitutionScope()
            ->where('class_session_id', $session->id)
            ->exists();

        if ($isPastSession && $hasExistingRecords && ! $recorder->can('attendance:edit-past')) {
            throw new AuthorizationException("Editing past attendance requires the 'attendance:edit-past' permission.");
        }

        // 3. Save whole class in one short database transaction
        DB::transaction(function () use ($session, $records, $recorder, $teacherCheckInStatus, $teacherCheckInNotes) {
            // Teacher check-in status
            if ($teacherCheckInStatus) {
                $session->update([
                    'teacher_attendance_status' => $teacherCheckInStatus,
                    'teacher_checked_in_at' => now(),
                    'teacher_check_in_notes' => $teacherCheckInNotes,
                ]);
            }

            if (empty($records)) {
                return;
            }

            $now = now();
            $upsertRows = [];

            foreach ($records as $item) {
                if (empty($item['student_id']) || empty($item['status'])) {
                    continue;
                }

                $note = $item['note'] ?? null;

                $upsertRows[] = [
                    'id' => (string) Str::ulid(),
                    'institution_id' => $session->institution_id,
                    'class_session_id' => $session->id,
                    'session_id' => $session->id,
                    'student_id' => $item['student_id'],
                    'date' => Carbon::parse($session->scheduled_date)->toDateString(),
                    'status' => $item['status'],
                    'note' => $note,
                    'remarks' => $note,
                    'recorded_by' => $recorder->id,
                    'recorded_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($upsertRows)) {
                AttendanceRecord::withoutInstitutionScope()->upsert(
                    $upsertRows,
                    ['class_session_id', 'student_id'],
                    ['status', 'note', 'remarks', 'recorded_by', 'recorded_at', 'updated_at']
                );
            }
        });
    }
}
