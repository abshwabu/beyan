<?php

namespace Tests\Feature;

use App\Domain\Academics\Exceptions\TimetableConflictException;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSchedule;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Academics\Services\SessionGeneratorService;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSessionGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Teacher $teacher;

    protected Course $course;

    protected ClassGroup $classGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et'],
            'mode' => 'online',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);

        $this->teacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Ustaz Khalid',
            'specialization' => 'Hifz',
        ]);

        $this->course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Qaida Nooraniyah',
            'code' => 'QAI-101',
            'level' => 'beginner',
        ]);

        $this->classGroup = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Qaida Beginners Online',
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/qaida-live',
        ]);

        $this->classGroup->teachers()->attach($this->teacher->id, [
            'institution_id' => $this->institution->id,
            'is_primary' => true,
        ]);
    }

    public function test_can_generate_class_sessions_from_recurring_schedules_stored_in_utc(): void
    {
        // Add schedule: Monday (1) and Wednesday (3) at 09:00 - 10:30 in Africa/Addis_Ababa (UTC+3)
        ClassSchedule::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $this->teacher->id,
            'weekday' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '10:30',
            'timezone' => 'Africa/Addis_Ababa',
        ]);

        ClassSchedule::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $this->teacher->id,
            'weekday' => 3, // Wednesday
            'start_time' => '09:00',
            'end_time' => '10:30',
            'timezone' => 'Africa/Addis_Ababa',
        ]);

        $generator = app(SessionGeneratorService::class);

        // Date range covering 2 full weeks: Monday 2026-11-16 to Sunday 2026-11-29
        $startDate = Carbon::parse('2026-11-16'); // Monday
        $endDate = Carbon::parse('2026-11-29');   // Sunday

        $sessions = $generator->generateForClassGroup($this->classGroup, $startDate, $endDate);

        // 2 Mondays + 2 Wednesdays = 4 sessions
        $this->assertCount(4, $sessions);

        foreach ($sessions as $session) {
            // Verify stored in UTC: 09:00 Addis = 06:00 UTC
            $startUtc = Carbon::parse($session->start_time_utc, 'UTC');
            $endUtc = Carbon::parse($session->end_time_utc, 'UTC');

            $this->assertEquals('06:00', $startUtc->format('H:i'));
            $this->assertEquals('07:30', $endUtc->format('H:i'));

            // Verify timezone conversion back to local
            $this->assertEquals('09:00', $session->startTimeForTimezone('Africa/Addis_Ababa')->format('H:i'));
            $this->assertEquals('10:30', $session->endTimeForTimezone('Africa/Addis_Ababa')->format('H:i'));

            $this->assertEquals('online', $session->type);
            $this->assertEquals('https://meet.google.com/qaida-live', $session->meeting_link);
            $this->assertEquals($this->teacher->id, $session->teacher_id);
        }

        $this->assertEquals(4, ClassSession::count());
    }

    public function test_generation_stops_and_rolls_back_if_teacher_has_conflict(): void
    {
        // Schedule: Monday at 09:00 - 10:30 Addis (= 06:00 - 07:30 UTC)
        ClassSchedule::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $this->teacher->id,
            'weekday' => 1,
            'start_time' => '09:00',
            'end_time' => '10:30',
            'timezone' => 'Africa/Addis_Ababa',
        ]);

        // Pre-existing session on 2026-11-23 for Ustaz Khalid at 06:30 - 07:30 UTC (conflict)
        ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-23',
            'start_time_utc' => Carbon::parse('2026-11-23 06:30:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-23 07:30:00', 'UTC'),
            'status' => 'scheduled',
        ]);

        $generator = app(SessionGeneratorService::class);

        $this->expectException(TimetableConflictException::class);

        $generator->generateForClassGroup(
            $this->classGroup,
            Carbon::parse('2026-11-16'),
            Carbon::parse('2026-11-29')
        );

        // Entire generation rolled back
        $this->assertEquals(1, ClassSession::count()); // Only the pre-existing session
    }
}
