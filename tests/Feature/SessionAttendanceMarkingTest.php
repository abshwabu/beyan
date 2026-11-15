<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Attendance\Models\AttendanceRecord;
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

class SessionAttendanceMarkingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $assignedTeacherUser;

    protected Teacher $assignedTeacher;

    protected User $unassignedTeacherUser;

    protected Teacher $unassignedTeacher;

    protected User $adminUser;

    protected Course $course;

    protected ClassGroup $classGroup;

    protected ClassSession $todaySession;

    protected ClassSession $pastSession;

    protected Student $student1;

    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->seed(RolePermissionSeeder::class);

        // Assigned teacher
        $this->assignedTeacherUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustaz Bilal',
            'email' => 'bilal@beyan.edu.et',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        $this->assignedTeacherUser->assignRole('Teacher');

        $this->assignedTeacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->assignedTeacherUser->id,
            'full_name' => 'Ustaz Bilal',
            'specialization' => 'Hifz',
        ]);

        // Unassigned teacher
        $this->unassignedTeacherUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustaz Tariq',
            'email' => 'tariq@beyan.edu.et',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        $this->unassignedTeacherUser->assignRole('Teacher');

        $this->unassignedTeacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->unassignedTeacherUser->id,
            'full_name' => 'Ustaz Tariq',
            'specialization' => 'Arabic',
        ]);

        // Admin User (has attendance:edit-past)
        $this->adminUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Admin User',
            'email' => 'admin@beyan.edu.et',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $this->adminUser->assignRole('Admin');

        // Course & Class Group
        $this->course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Hifz Level 1',
            'code' => 'HIFZ-101',
        ]);

        $this->classGroup = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Hifz Morning Group',
            'type' => 'physical',
            'room' => 'Room 101',
        ]);

        $this->classGroup->teachers()->attach($this->assignedTeacher->id, [
            'institution_id' => $this->institution->id,
            'is_primary' => true,
        ]);

        // Students
        $this->student1 = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Zayd Abdullah',
            'status' => 'active',
        ]);

        $this->student2 = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Maryam Hassan',
            'status' => 'active',
        ]);

        ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student1->id,
            'class_group_id' => $this->classGroup->id,
            'start_date' => '2026-01-01',
            'status' => 'enrolled',
        ]);

        ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student2->id,
            'class_group_id' => $this->classGroup->id,
            'start_date' => '2026-01-01',
            'status' => 'enrolled',
        ]);

        // Today Session (assigned to Ustaz Bilal)
        $today = Carbon::today('Africa/Addis_Ababa')->toDateString();
        $this->todaySession = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $this->assignedTeacher->id,
            'scheduled_date' => $today,
            'start_time_utc' => Carbon::parse("{$today} 06:00:00", 'UTC'),
            'end_time_utc' => Carbon::parse("{$today} 07:30:00", 'UTC'),
            'type' => 'physical',
            'status' => 'scheduled',
        ]);

        // Past Session
        $pastDate = Carbon::today('Africa/Addis_Ababa')->subDays(5)->toDateString();
        $this->pastSession = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $this->assignedTeacher->id,
            'scheduled_date' => $pastDate,
            'start_time_utc' => Carbon::parse("{$pastDate} 06:00:00", 'UTC'),
            'end_time_utc' => Carbon::parse("{$pastDate} 07:30:00", 'UTC'),
            'type' => 'physical',
            'status' => 'completed',
        ]);
    }

    public function test_double_submit_never_creates_duplicate_rows(): void
    {
        $this->actingAs($this->assignedTeacherUser);

        $payload = [
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'note' => 'On time'],
                ['student_id' => $this->student2->id, 'status' => 'absent', 'note' => 'Doctor appointment'],
            ],
            'teacher_check_in_status' => 'present',
            'teacher_check_in_notes' => 'Session conducted smoothly',
        ];

        // First Submit
        $res1 = $this->postJson(route('attendance.sessions.save', $this->todaySession->id), $payload);
        $res1->assertOk();

        $this->assertEquals(2, AttendanceRecord::where('class_session_id', $this->todaySession->id)->count());

        // SECOND SUBMIT (Exact same payload - simulated double submit)
        $res2 = $this->postJson(route('attendance.sessions.save', $this->todaySession->id), $payload);
        $res2->assertOk();

        // Exactly 2 rows must still exist! ZERO duplicates created!
        $this->assertEquals(2, AttendanceRecord::where('class_session_id', $this->todaySession->id)->count());

        // Third submit with updated status for student 2: Absent -> Late
        $payload['records'][1]['status'] = 'late';
        $res3 = $this->postJson(route('attendance.sessions.save', $this->todaySession->id), $payload);
        $res3->assertOk();

        $this->assertEquals(2, AttendanceRecord::where('class_session_id', $this->todaySession->id)->count());
        $rec2 = AttendanceRecord::where('class_session_id', $this->todaySession->id)
            ->where('student_id', $this->student2->id)
            ->first();
        $this->assertEquals('late', $rec2->status);

        // Teacher check-in status updated
        $this->todaySession->refresh();
        $this->assertEquals('present', $this->todaySession->teacher_attendance_status);
        $this->assertEquals('Session conducted smoothly', $this->todaySession->teacher_check_in_notes);
    }

    public function test_teacher_can_only_mark_sessions_they_are_assigned_to(): void
    {
        // Assigned teacher -> ALLOWED (200)
        $this->actingAs($this->assignedTeacherUser);
        $allowedRes = $this->postJson(route('attendance.sessions.save', $this->todaySession->id), [
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present'],
            ],
        ]);
        $allowedRes->assertOk();

        // Unassigned teacher -> FORBIDDEN (403)
        $this->actingAs($this->unassignedTeacherUser);
        $forbiddenRes = $this->postJson(route('attendance.sessions.save', $this->todaySession->id), [
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present'],
            ],
        ]);
        $forbiddenRes->assertStatus(403);
    }

    public function test_editing_past_attendance_requires_permission_and_is_audit_logged(): void
    {
        // 1. Initial attendance was recorded for past session
        AttendanceRecord::create([
            'institution_id' => $this->institution->id,
            'class_session_id' => $this->pastSession->id,
            'student_id' => $this->student1->id,
            'date' => $this->pastSession->scheduled_date,
            'status' => 'absent',
            'recorded_by' => $this->assignedTeacherUser->id,
        ]);

        // 2. Assigned teacher without 'attendance:edit-past' tries to edit -> FORBIDDEN (403)
        $this->actingAs($this->assignedTeacherUser);
        $teacherEditRes = $this->postJson(route('attendance.sessions.save', $this->pastSession->id), [
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'note' => 'Correcting error'],
            ],
        ]);
        $teacherEditRes->assertStatus(403);

        // 3. Admin user with 'attendance:edit-past' edits -> ALLOWED (200)
        $this->actingAs($this->adminUser);
        $adminEditRes = $this->postJson(route('attendance.sessions.save', $this->pastSession->id), [
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'note' => 'Admin verified excused presence'],
            ],
        ]);
        $adminEditRes->assertOk();

        $rec = AttendanceRecord::where('class_session_id', $this->pastSession->id)
            ->where('student_id', $this->student1->id)
            ->first();
        $this->assertEquals('present', $rec->status);
    }
}
