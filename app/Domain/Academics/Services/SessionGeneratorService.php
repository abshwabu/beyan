<?php

namespace App\Domain\Academics\Services;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionGeneratorService
{
    public function __construct(
        protected TimetableConflictService $conflictService
    ) {}

    /**
     * Generate class sessions from recurring schedules for a date range, stored in UTC.
     *
     * @return Collection<int, ClassSession>
     */
    public function generateForClassGroup(
        ClassGroup $classGroup,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $schedules = $classGroup->schedules()->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $period = CarbonPeriod::create($startDate->toDateString(), $endDate->toDateString());
        $createdSessions = collect();

        DB::transaction(function () use ($classGroup, $schedules, $period, &$createdSessions) {
            foreach ($period as $currentDate) {
                $weekday = $currentDate->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday

                $matchingSchedules = $schedules->where('weekday', $weekday);

                foreach ($matchingSchedules as $schedule) {
                    $tz = $schedule->timezone ?: ($classGroup->institution->timezone ?? 'UTC');

                    // Build local start and end datetimes
                    $startStr = "{$currentDate->format('Y-m-d')} {$schedule->start_time}";
                    $endStr = "{$currentDate->format('Y-m-d')} {$schedule->end_time}";

                    $localStart = Carbon::parse($startStr, $tz);
                    $localEnd = Carbon::parse($endStr, $tz);

                    // Convert to UTC for deterministic storage
                    $startUtc = $localStart->copy()->setTimezone('UTC');
                    $endUtc = $localEnd->copy()->setTimezone('UTC');

                    $teacherId = $schedule->teacher_id
                        ?: $classGroup->teachers()->wherePivot('is_primary', true)->value('teachers.id');

                    // Check conflict before creating
                    if ($teacherId) {
                        $this->conflictService->assertNoTeacherConflict(
                            $classGroup->institution_id,
                            $teacherId,
                            $startUtc,
                            $endUtc
                        );
                    }

                    $session = ClassSession::withoutInstitutionScope()->create([
                        'institution_id' => $classGroup->institution_id,
                        'class_group_id' => $classGroup->id,
                        'teacher_id' => $teacherId,
                        'scheduled_date' => $currentDate->format('Y-m-d'),
                        'start_time_utc' => $startUtc,
                        'end_time_utc' => $endUtc,
                        'type' => $classGroup->type,
                        'meeting_link' => $classGroup->meeting_link,
                        'room' => $classGroup->room,
                        'status' => 'scheduled',
                    ]);

                    $createdSessions->push($session);
                }
            }
        });

        return $createdSessions;
    }
}
