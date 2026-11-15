<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TimetableTimezoneAndTodayTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $teacherUser;

    protected Teacher $teacher;

    protected ClassGroup $onlineClassGroup;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et'],
            'mode' => 'online',
            'timezone' => 'Africa/Addis_Ababa', // UTC+3
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->seed(RolePermissionSeeder::class);

        $this->teacherUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Sheikh Omar Al-Madani',
            'email' => 'omar@beyan.edu.et',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);

        $this->teacherUser->assignRole('Teacher');

        $this->teacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Sheikh Omar Al-Madani',
            'email' => 'omar@beyan.edu.et',
            'specialization' => 'Qur\'an Reading',
        ]);

        $this->course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => "Qur'an Recitation & Memorization",
            'code' => 'QUR-200',
        ]);

        $this->onlineClassGroup = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Global Online Hifz Morning',
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/beyan-hifz-live',
        ]);

        $this->onlineClassGroup->teachers()->attach($this->teacher->id, [
            'institution_id' => $this->institution->id,
            'is_primary' => true,
        ]);
    }

    public function test_online_class_times_display_in_the_viewers_timezone(): void
    {
        // Session stored strictly in UTC: 06:00 UTC to 07:30 UTC
        $session = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->onlineClassGroup->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => '2026-11-20',
            'start_time_utc' => Carbon::parse('2026-11-20 06:00:00', 'UTC'),
            'end_time_utc' => Carbon::parse('2026-11-20 07:30:00', 'UTC'),
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/beyan-hifz-live',
            'status' => 'scheduled',
        ]);

        // 1. Viewer in Addis Ababa (UTC+3) -> Expect 09:00 - 10:30
        $addisView = $session->toTimezoneArray('Africa/Addis_Ababa');
        $this->assertEquals('09:00', $addisView['local_start_time']);
        $this->assertEquals('10:30', $addisView['local_end_time']);
        $this->assertEquals('9:00 AM - 10:30 AM', $addisView['formatted_time_range']);

        // 2. Viewer in London (UTC+0) -> Expect 06:00 - 07:30
        $londonView = $session->toTimezoneArray('Europe/London');
        $this->assertEquals('06:00', $londonView['local_start_time']);
        $this->assertEquals('07:30', $londonView['local_end_time']);
        $this->assertEquals('6:00 AM - 7:30 AM', $londonView['formatted_time_range']);

        // 3. Viewer in New York (UTC-5 in winter) -> Expect 01:00 - 02:30
        $nyView = $session->toTimezoneArray('America/New_York');
        $this->assertEquals('01:00', $nyView['local_start_time']);
        $this->assertEquals('02:30', $nyView['local_end_time']);
        $this->assertEquals('1:00 AM - 2:30 AM', $nyView['formatted_time_range']);

        // 4. Viewer in Tokyo (UTC+9) -> Expect 15:00 - 16:30
        $tokyoView = $session->toTimezoneArray('Asia/Tokyo');
        $this->assertEquals('15:00', $tokyoView['local_start_time']);
        $this->assertEquals('16:30', $tokyoView['local_end_time']);
        $this->assertEquals('3:00 PM - 4:30 PM', $tokyoView['formatted_time_range']);
    }

    public function test_teacher_today_page_lists_sessions_with_one_tap_into_attendance(): void
    {
        $this->actingAs($this->teacherUser);

        $tz = 'Africa/Addis_Ababa';
        $todayLocal = Carbon::now($tz)->format('Y-m-d');

        // Enroll 2 students in the online class
        $s1 = Student::create(['institution_id' => $this->institution->id, 'full_name' => 'Student 1']);
        $s2 = Student::create(['institution_id' => $this->institution->id, 'full_name' => 'Student 2']);

        ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $s1->id,
            'class_group_id' => $this->onlineClassGroup->id,
            'start_date' => $todayLocal,
            'status' => 'enrolled',
        ]);

        ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $s2->id,
            'class_group_id' => $this->onlineClassGroup->id,
            'start_date' => $todayLocal,
            'status' => 'enrolled',
        ]);

        // Create a scheduled session for today: 07:00 UTC to 08:30 UTC (= 10:00 to 11:30 Addis time)
        $session = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->onlineClassGroup->id,
            'teacher_id' => $this->teacher->id,
            'scheduled_date' => $todayLocal,
            'start_time_utc' => Carbon::parse("{$todayLocal} 07:00:00", 'UTC'),
            'end_time_utc' => Carbon::parse("{$todayLocal} 08:30:00", 'UTC'),
            'type' => 'online',
            'meeting_link' => 'https://meet.google.com/beyan-hifz-live',
            'status' => 'scheduled',
        ]);

        // Call the Today page
        $response = $this->getJson(route('timetable.today', ['timezone' => $tz]));

        $response->assertOk()
            ->assertJsonPath('today', $todayLocal)
            ->assertJsonPath('timezone', $tz)
            ->assertJsonCount(1, 'sessions');

        $sessionData = $response->json('sessions.0');

        $this->assertEquals($session->id, $sessionData['id']);
        $this->assertEquals("Qur'an Recitation & Memorization", $sessionData['course_name']);
        $this->assertEquals('Global Online Hifz Morning', $sessionData['class_name']);
        $this->assertEquals('online', $sessionData['type']);
        $this->assertEquals('https://meet.google.com/beyan-hifz-live', $sessionData['meeting_link']);
        $this->assertEquals(2, $sessionData['enrolled_count']);
        $this->assertNotEmpty($sessionData['attendance_url']);
        $this->assertStringContainsString('attendance', $sessionData['attendance_url']);
        $this->assertEquals('10:00 AM - 11:30 AM', $sessionData['formatted_time_range']);
    }
}
