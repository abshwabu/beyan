<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $adminUser;

    protected User $teacherUser;

    protected Teacher $teacherProfile;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuranReferenceSeeder::class);

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et', 'phone' => '+251911000000'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();
        $matrix->seedInstitutionRoles($this->institution->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->adminUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Admin Ustadh',
            'email' => 'admin@beyan.edu.et',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->assignRole('Admin');

        $this->teacherUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Sheikh Omar',
            'email' => 'teacher@beyan.edu.et',
            'role' => 'teacher',
            'password' => bcrypt('password'),
        ]);
        $this->teacherUser->assignRole('Teacher');

        $this->teacherProfile = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Sheikh Omar',
            'email' => 'teacher@beyan.edu.et',
            'status' => 'active',
            'specialization' => 'Hifz and Tajweed',
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Bilal',
            'last_name' => 'Tariq',
            'full_name' => 'Bilal Tariq',
            'student_code' => 'BYN-2026-0001',
            'date_of_birth' => '2014-06-15',
            'gender' => 'male',
            'admission_date' => '2024-09-01',
            'status' => 'active',
        ]);

        // Assign student to teacher
        $this->teacherUser->assignedStudents()->attach($this->student->id, [
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_admin_dashboard_shows_aggregate_metrics_and_caches_results(): void
    {
        $this->actingAs($this->adminUser);

        Cache::flush();

        $response = $this->getJson(route('dashboard'));

        $response->assertOk();
        $response->assertJsonStructure([
            'institution',
            'is_teacher_only',
            'admin_metrics' => [
                'active_students_count',
                'active_teachers_count',
                'active_classes_count',
                'today_attendance' => [
                    'percentage',
                    'total_records',
                    'present_count',
                    'sessions_count',
                    'marked_sessions_count',
                ],
                'fees' => [
                    'pending_total',
                    'overdue_count',
                    'overdue_total',
                    'currency',
                ],
                'upcoming_sessions',
                'recent_activity',
            ],
        ]);

        $this->assertEquals(1, $response->json('admin_metrics.active_students_count'));
        $this->assertEquals(1, $response->json('admin_metrics.active_teachers_count'));

        // Verify cache is populated
        $cacheKey = "dash_admin_metrics_{$this->institution->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_teacher_dashboard_shows_today_sessions_and_students_needing_attention(): void
    {
        $this->actingAs($this->teacherUser);

        // Create course and class group
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Hifz Level 1',
            'code' => 'HIFZ-01',
            'type' => 'hifz',
            'status' => 'active',
        ]);

        $classGroup = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $course->id,
            'name' => 'Morning Hifz Class',
            'type' => 'physical',
            'capacity' => 15,
            'status' => 'active',
        ]);

        $today = now()->toDateString();
        $session = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $this->teacherProfile->id,
            'title' => 'Morning Hifz Session',
            'scheduled_date' => $today,
            'start_time_utc' => "{$today} 06:00:00",
            'end_time_utc' => "{$today} 07:30:00",
            'status' => 'scheduled',
        ]);

        Cache::flush();

        $response = $this->getJson(route('dashboard'));

        $response->assertOk();
        $this->assertTrue($response->json('is_teacher_only'));
        $this->assertNull($response->json('admin_metrics'));

        $teacherMetrics = $response->json('teacher_metrics');
        $this->assertNotNull($teacherMetrics);
        $this->assertCount(1, $teacherMetrics['today_sessions']);
        $this->assertEquals('Morning Hifz Class', $teacherMetrics['today_sessions'][0]['class_name']);

        // Since student Bilal has not logged Sabaq in 7 days, they should be in "needs attention"
        $this->assertNotEmpty($teacherMetrics['students_needing_attention']);
        $this->assertEquals($this->student->id, $teacherMetrics['students_needing_attention'][0]['id']);
    }

    public function test_reports_index_displays_available_reports_for_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_teacher_without_permission_cannot_access_reports(): void
    {
        $this->actingAs($this->teacherUser);

        $response = $this->get(route('reports.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_generate_student_and_attendance_reports(): void
    {
        $this->actingAs($this->adminUser);

        $studentsReport = $this->getJson(route('reports.show', ['type' => 'students']));
        $studentsReport->assertOk();
        $this->assertEquals('Student Directory Report', $studentsReport->json('title'));
        $this->assertCount(1, $studentsReport->json('rows'));

        $attendanceReport = $this->getJson(route('reports.show', ['type' => 'attendance']));
        $attendanceReport->assertOk();
        $this->assertEquals('Attendance Performance Report', $attendanceReport->json('title'));
    }

    public function test_admin_can_export_report_as_csv_with_utf8_bom(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.export', ['type' => 'students', 'format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // UTF-8 BOM is \xEF\xBB\xBF
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Bilal Tariq', $content);
        $this->assertStringContainsString('BYN-2026-0001', $content);
    }

    public function test_admin_can_export_report_as_printable_pdf_view(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.export', ['type' => 'students', 'format' => 'pdf']));

        $response->assertOk();
        $response->assertSee('Beyan Academy');
        $response->assertSee('Student Directory');
        $response->assertSee('Bilal Tariq');
        $response->assertSee('@media print', false);
    }
}
