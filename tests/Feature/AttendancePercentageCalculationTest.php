<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Services\AttendanceAnalyticsService;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePercentageCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Course $course;

    protected ClassGroup $classGroup;

    protected Student $student1;

    protected Student $student2;

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

        $this->course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Qur\'an Reading',
            'code' => 'QUR-101',
        ]);

        $this->classGroup = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Qur\'an Reading Elementary',
        ]);

        $this->student1 = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Student One',
            'status' => 'active',
        ]);

        $this->student2 = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Student Two',
            'status' => 'active',
        ]);

        $this->analyticsService = app(AttendanceAnalyticsService::class);
    }

    public function test_percentages_match_a_hand_calculated_seeded_dataset_in_tests(): void
    {
        // -------------------------------------------------------------
        // SEED HAND-CALCULATED DATASET
        // -------------------------------------------------------------
        // Student 1: 10 sessions -> 7 present, 1 late, 1 absent, 1 excused
        // Student 2: 8 sessions  -> 6 present, 0 late, 2 absent, 0 excused
        // -------------------------------------------------------------
        $student1Statuses = [
            'present', 'present', 'present', 'present', 'present',
            'present', 'present', 'late', 'absent', 'excused',
        ];

        $student2Statuses = [
            'present', 'present', 'present', 'present', 'present',
            'present', 'absent', 'absent',
        ];

        $startDate = Carbon::parse('2026-11-01');

        foreach ($student1Statuses as $index => $status) {
            $sessionDate = $startDate->copy()->addDays($index)->toDateString();

            $session = ClassSession::create([
                'institution_id' => $this->institution->id,
                'class_group_id' => $this->classGroup->id,
                'scheduled_date' => $sessionDate,
                'start_time_utc' => Carbon::parse("{$sessionDate} 06:00:00", 'UTC'),
                'end_time_utc' => Carbon::parse("{$sessionDate} 07:30:00", 'UTC'),
                'status' => 'completed',
            ]);

            AttendanceRecord::create([
                'institution_id' => $this->institution->id,
                'class_session_id' => $session->id,
                'student_id' => $this->student1->id,
                'date' => $sessionDate,
                'status' => $status,
                'recorded_at' => now(),
            ]);

            // Seed student 2 records for the first 8 sessions
            if (isset($student2Statuses[$index])) {
                AttendanceRecord::create([
                    'institution_id' => $this->institution->id,
                    'class_session_id' => $session->id,
                    'student_id' => $this->student2->id,
                    'date' => $sessionDate,
                    'status' => $student2Statuses[$index],
                    'recorded_at' => now(),
                ]);
            }
        }

        // -------------------------------------------------------------
        // 1. VERIFY STUDENT 1 HAND CALCULATIONS
        // -------------------------------------------------------------
        // Hand calculations:
        // Total = 10
        // Present = 7
        // Late = 1
        // Absent = 1
        // Excused = 1
        // Present percentage = (7 / 10) * 100 = 70.00%
        // Attendance percentage (present + late) = (8 / 10) * 100 = 80.00%
        $s1Stats = $this->analyticsService->computeStudentPercentage($this->student1->id);

        $this->assertEquals(10, $s1Stats['total_records']);
        $this->assertEquals(7, $s1Stats['present_count']);
        $this->assertEquals(1, $s1Stats['late_count']);
        $this->assertEquals(1, $s1Stats['absent_count']);
        $this->assertEquals(1, $s1Stats['excused_count']);
        $this->assertSame(70.0, $s1Stats['present_percentage']);
        $this->assertSame(80.0, $s1Stats['attendance_percentage']);

        // -------------------------------------------------------------
        // 2. VERIFY STUDENT 2 HAND CALCULATIONS
        // -------------------------------------------------------------
        // Hand calculations:
        // Total = 8
        // Present = 6
        // Late = 0
        // Absent = 2
        // Excused = 0
        // Present percentage = (6 / 8) * 100 = 75.00%
        // Attendance percentage = (6 / 8) * 100 = 75.00%
        $s2Stats = $this->analyticsService->computeStudentPercentage($this->student2->id);

        $this->assertEquals(8, $s2Stats['total_records']);
        $this->assertEquals(6, $s2Stats['present_count']);
        $this->assertEquals(0, $s2Stats['late_count']);
        $this->assertEquals(2, $s2Stats['absent_count']);
        $this->assertEquals(0, $s2Stats['excused_count']);
        $this->assertSame(75.0, $s2Stats['present_percentage']);
        $this->assertSame(75.0, $s2Stats['attendance_percentage']);

        // -------------------------------------------------------------
        // 3. VERIFY CLASS GROUP AGGREGATE HAND CALCULATIONS
        // -------------------------------------------------------------
        // Total records = 10 + 8 = 18
        // Total present = 7 + 6 = 13
        // Total late = 1 + 0 = 1
        // Total absent = 1 + 2 = 3
        // Total excused = 1 + 0 = 1
        // Total attended = 13 + 1 = 14
        // Hand-calculated class rates:
        // Present rate = round((13 / 18) * 100, 2) = 72.22%
        // Attendance rate = round((14 / 18) * 100, 2) = 77.78%
        $classStats = $this->analyticsService->computeClassPercentage($this->classGroup->id);

        $this->assertEquals(18, $classStats['total_records']);
        $this->assertEquals(13, $classStats['present_count']);
        $this->assertEquals(1, $classStats['late_count']);
        $this->assertEquals(3, $classStats['absent_count']);
        $this->assertEquals(1, $classStats['excused_count']);
        $this->assertSame(72.22, $classStats['present_percentage']);
        $this->assertSame(77.78, $classStats['attendance_percentage']);

        // -------------------------------------------------------------
        // 4. VERIFY PERIOD AGGREGATE HAND CALCULATIONS
        // -------------------------------------------------------------
        $periodStats = $this->analyticsService->computePeriodPercentage(
            $this->institution->id,
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-11-30')
        );

        $this->assertEquals(18, $periodStats['total_records']);
        $this->assertSame(77.78, $periodStats['attendance_percentage']);
    }
}
