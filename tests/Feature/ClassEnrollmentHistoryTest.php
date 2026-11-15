<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\Course;
use App\Domain\Academics\Services\TimetableConflictService;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassEnrollmentHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Course $course;

    protected ClassGroup $classGroup1;

    protected ClassGroup $classGroup2;

    protected Student $student;

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
            'name' => 'Hifz Program',
            'code' => 'HIFZ-101',
        ]);

        $this->classGroup1 = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Hifz Level 1 - Juz Amma',
            'capacity' => 15,
        ]);

        $this->classGroup2 = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $this->course->id,
            'name' => 'Hifz Level 2 - Juz Tabarak',
            'capacity' => 15,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Ibrahim Tariq',
            'status' => 'active',
        ]);
    }

    public function test_transferring_a_student_to_another_class_keeps_their_old_enrollment_history(): void
    {
        // 1. Initial enrollment in Level 1
        $initialEnrollment = ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'class_group_id' => $this->classGroup1->id,
            'start_date' => '2026-01-10',
            'end_date' => null,
            'status' => 'enrolled',
            'notes' => 'Initial admission enrollment',
        ]);

        $conflictService = app(TimetableConflictService::class);

        // 2. Transfer student to Level 2 effective 2026-06-15
        $newEnrollment = $conflictService->transferStudent(
            $this->student,
            $this->classGroup2,
            '2026-06-15',
            'Promoted to Juz Tabarak after evaluation'
        );

        // 3. Verify old enrollment was updated, NEVER deleted
        $initialEnrollment->refresh();
        $this->assertEquals('transferred', $initialEnrollment->status);
        $this->assertEquals('2026-06-15', $initialEnrollment->end_date->format('Y-m-d'));
        $this->assertEquals('Initial admission enrollment', $initialEnrollment->notes);

        // 4. Verify new enrollment is active
        $this->assertEquals($this->classGroup2->id, $newEnrollment->class_group_id);
        $this->assertEquals('enrolled', $newEnrollment->status);
        $this->assertEquals('2026-06-15', $newEnrollment->start_date->format('Y-m-d'));
        $this->assertNull($newEnrollment->end_date);
        $this->assertEquals('Promoted to Juz Tabarak after evaluation', $newEnrollment->notes);

        // 5. Total enrollment history records preserved
        $allEnrollments = ClassEnrollment::where('student_id', $this->student->id)->orderBy('start_date')->get();
        $this->assertCount(2, $allEnrollments);
        $this->assertEquals($this->classGroup1->id, $allEnrollments[0]->class_group_id);
        $this->assertEquals($this->classGroup2->id, $allEnrollments[1]->class_group_id);
    }
}
