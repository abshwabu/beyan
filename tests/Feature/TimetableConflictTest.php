<?php

namespace Tests\Feature;

use App\Domain\Academics\Exceptions\TimetableConflictException;
use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Academics\Services\TimetableConflictService;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableConflictTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Course $course;

    protected Teacher $teacher;

    protected ClassGroup $classGroupA;

    protected ClassGroup $classGroupB;

    protected TimetableConflictService $conflictService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et'],
            'mode' => 'hybrid',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);

        $this->course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tajweed Essentials',
            'code' => 'TAJ-101',
            'level' => 'intermediate',
        ]);

        $this->teacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Ustaz Bilal Mahmoud',
            'email' => 'bilal@beyan.edu.et',
            'specialization' => 'Tajweed',
        ]);

        $this->classGroupA = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Tajweed Morning A',
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/tajweed-a',
        ]);

        $this->classGroupB = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Tajweed Morning B',
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/tajweed-b',
        ]);

        $this->conflictService = app(TimetableConflictService::class);
    }

    public function test_assigning_teacher_to_overlapping_session_is_rejected_with_clear_message(): void
    {
        // Session 1: 08:00 UTC to 09:30 UTC assigned to Ustaz Bilal
        $session1 = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupA->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-16',
            'start_time_utc' => Carbon::parse('2026-11-16 08:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-16 09:30:00', 'UTC'),
            'type' => 'online',
            'status' => 'scheduled',
        ]);

        // Session 2: 09:00 UTC to 10:30 UTC in Group B (overlaps from 09:00 to 09:30)
        $session2 = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupB->id,
            'teacher_id' => null, // Unassigned
            'scheduled_date' => '2026-11-16',
            'start_time_utc' => Carbon::parse('2026-11-16 09:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-16 10:30:00', 'UTC'),
            'type' => 'online',
            'status' => 'scheduled',
        ]);

        $this->expectException(TimetableConflictException::class);
        $this->expectExceptionMessage("Timetable conflict: Ustaz Bilal Mahmoud already has an overlapping session in 'Tajweed Morning A' from 08:00 to 09:30 UTC on 2026-11-16.");

        // Attempting to assign Ustaz Bilal to Session 2 MUST throw exception with clear message
        $this->conflictService->assignTeacherToSession($session2, $this->teacher);
    }

    public function test_creating_session_with_overlapping_teacher_is_rejected(): void
    {
        // Existing session: 10:00 - 11:30 UTC
        ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupA->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-17',
            'start_time_utc' => Carbon::parse('2026-11-17 10:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-17 11:30:00', 'UTC'),
            'status' => 'scheduled',
        ]);

        $this->expectException(TimetableConflictException::class);

        // New session starting at 11:00 UTC (overlaps by 30 mins)
        $this->conflictService->createSession([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupB->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-17',
            'start_time_utc' => Carbon::parse('2026-11-17 11:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-17 12:00:00', 'UTC'),
        ]);
    }

    public function test_non_overlapping_adjacent_sessions_are_allowed_for_same_teacher(): void
    {
        // Session 1: 08:00 - 09:00 UTC
        ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupA->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-18',
            'start_time_utc' => Carbon::parse('2026-11-18 08:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-18 09:00:00', 'UTC'),
            'status' => 'scheduled',
        ]);

        // Session 2: 09:00 - 10:00 UTC (Adjacent, exactly matches end of session 1, no overlap)
        $session2 = $this->conflictService->createSession([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupB->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-18',
            'start_time_utc' => Carbon::parse('2026-11-18 09:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-18 10:00:00', 'UTC'),
        ]);

        $this->assertNotNull($session2->id);
        $this->assertEquals($this->teacher->id, $session2->teacher_id);
    }

    public function test_student_in_overlapping_session_is_rejected_with_clear_message(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Zayd Abdullah',
            'status' => 'active',
        ]);

        // Enroll student in Class Group A
        ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $student->id,
            'class_group_id' => $this->classGroupA->id,
            'start_date' => '2026-11-01',
            'end_date' => null,
            'status' => 'enrolled',
        ]);

        // Class Group A has a session at 14:00 - 15:30 UTC
        ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroupA->id,
            'scheduled_date' => '2026-11-19',
            'start_time_utc' => Carbon::parse('2026-11-19 14:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-19 15:30:00', 'UTC'),
            'status' => 'scheduled',
        ]);

        $this->expectException(TimetableConflictException::class);
        $this->expectExceptionMessage("Timetable conflict: Zayd Abdullah is already enrolled in an overlapping session in 'Tajweed Morning A' from 14:00 to 15:30 UTC on 2026-11-19.");

        // Checking if student can take a session in Group B from 15:00 - 16:00 UTC (overlaps 15:00 - 15:30)
        $this->conflictService->assertNoStudentConflict(
            $this->institution->id,
            $student->id,
            Carbon::parse('2026-11-19 15:00:00', 'UTC'),
            Carbon::parse('2026-11-19 16:00:00', 'UTC')
        );
    }
}
