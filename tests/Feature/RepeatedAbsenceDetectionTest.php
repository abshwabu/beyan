<?php

namespace Tests\Feature;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Services\AttendanceAnalyticsService;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Guardian;
use App\Domain\Students\Models\Student;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RepeatedAbsenceDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected Student $studentHighAbsence;

    protected Student $studentLowAbsence;

    protected Student $studentNoAbsence;

    protected Guardian $guardian;

    protected AttendanceAnalyticsService $analyticsService;

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

        $this->admin = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Admin User',
            'email' => 'admin@beyan.edu.et',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Admin');

        // Create Students
        $this->studentHighAbsence = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Khalid Frequent Absence',
            'status' => 'active',
        ]);

        $this->studentLowAbsence = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Sara Occasional Absence',
            'status' => 'active',
        ]);

        $this->studentNoAbsence = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Ahmad Perfect Attendance',
            'status' => 'active',
        ]);

        // Link Guardian to High Absence Student
        $this->guardian = Guardian::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Sheikh Abu Khalid',
            'phone' => '+251911998877',
            'email' => 'abukhalid@example.com',
        ]);

        $this->studentHighAbsence->guardians()->attach($this->guardian->id, [
            'institution_id' => $this->institution->id,
            'relationship_type' => 'Father',
            'is_primary' => true,
        ]);

        $this->analyticsService = app(AttendanceAnalyticsService::class);
    }

    public function test_repeated_absence_detection_flags_students_exceeding_configurable_threshold(): void
    {
        $today = Carbon::today();

        // 1. Seed 4 absences for Khalid within the last 10 days
        for ($i = 1; $i <= 4; $i++) {
            AttendanceRecord::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->studentHighAbsence->id,
                'date' => $today->copy()->subDays($i)->toDateString(),
                'status' => 'absent',
            ]);
        }

        // 2. Seed 2 absences for Sara within the last 10 days
        for ($i = 1; $i <= 2; $i++) {
            AttendanceRecord::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->studentLowAbsence->id,
                'date' => $today->copy()->subDays($i)->toDateString(),
                'status' => 'absent',
            ]);
        }

        // 3. Seed 5 present records for Ahmad
        for ($i = 1; $i <= 5; $i++) {
            AttendanceRecord::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->studentNoAbsence->id,
                'date' => $today->copy()->subDays($i)->toDateString(),
                'status' => 'present',
            ]);
        }

        // -----------------------------------------------------------------
        // CASE 1: Default threshold = 3 absences
        // -----------------------------------------------------------------
        $flaggedThreshold3 = $this->analyticsService->detectRepeatedAbsences($this->institution->id, threshold: 3);

        $this->assertCount(1, $flaggedThreshold3);
        $flagged = $flaggedThreshold3->first();

        $this->assertEquals($this->studentHighAbsence->id, $flagged['student_id']);
        $this->assertEquals('Khalid Frequent Absence', $flagged['full_name']);
        $this->assertEquals(4, $flagged['absence_count']);
        $this->assertEquals('Sheikh Abu Khalid', $flagged['guardian_name']);
        $this->assertEquals('+251911998877', $flagged['guardian_phone']);

        // -----------------------------------------------------------------
        // CASE 2: Configurable threshold = 2 absences
        // -----------------------------------------------------------------
        $flaggedThreshold2 = $this->analyticsService->detectRepeatedAbsences($this->institution->id, threshold: 2);

        $this->assertCount(2, $flaggedThreshold2);
        $flaggedStudentIds = $flaggedThreshold2->pluck('student_id')->all();

        $this->assertContains($this->studentHighAbsence->id, $flaggedStudentIds);
        $this->assertContains($this->studentLowAbsence->id, $flaggedStudentIds);
        $this->assertNotContains($this->studentNoAbsence->id, $flaggedStudentIds);

        // -----------------------------------------------------------------
        // CASE 3: HTTP Endpoint Test
        // -----------------------------------------------------------------
        $this->actingAs($this->admin);

        $response = $this->getJson(route('attendance.flagged', ['threshold' => 3]));

        $response->assertOk()
            ->assertJsonPath('threshold', 3)
            ->assertJsonCount(1, 'flagged_students')
            ->assertJsonPath('flagged_students.0.student_id', $this->studentHighAbsence->id)
            ->assertJsonPath('flagged_students.0.absence_count', 4)
            ->assertJsonPath('flagged_students.0.guardian_name', 'Sheikh Abu Khalid');
    }
}
