<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSchedule;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffAndClassManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan-academy',
            'contact' => ['email' => 'admin@beyan.edu.et'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'en',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Madrasa Owner',
            'email' => 'owner@beyan.edu.et',
            'password' => Hash::make('password123'),
            'role' => 'owner',
        ]);

        $this->owner->assignRolesWithAudit('Owner');
    }

    public function test_owner_can_view_staff_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('staff.index'));
        $response->assertOk();
    }

    public function test_owner_can_create_staff_member_with_teacher_role(): void
    {
        $response = $this->actingAs($this->owner)->post(route('staff.store'), [
            'name' => 'Ustadh Zayd',
            'email' => 'zayd@beyan.edu.et',
            'password' => 'Password123!',
            'role' => 'Teacher',
            'phone' => '+251 91 234 5678',
            'specialization' => 'Qur\'an Memorization',
        ]);

        $response->assertRedirect(route('staff.index'));

        $user = User::where('email', 'zayd@beyan.edu.et')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Teacher'));

        // Verify teacher profile was created
        $teacher = Teacher::where('user_id', $user->id)->first();
        $this->assertNotNull($teacher);
        $this->assertEquals('Ustadh Zayd', $teacher->full_name);
        $this->assertEquals('Qur\'an Memorization', $teacher->specialization);
    }

    public function test_owner_can_view_classes_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('classes.index'));
        $response->assertOk();
    }

    public function test_owner_can_create_class_group(): void
    {
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Hifz Program',
            'code' => 'HIFZ-101',
            'level' => 'all_levels',
            'status' => 'active',
        ]);

        $teacherUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Teacher Ali',
            'email' => 'ali@beyan.edu.et',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $teacherUser->id,
            'full_name' => 'Teacher Ali',
            'email' => $teacherUser->email,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->post(route('classes.store'), [
            'name' => 'Hifz Group 1',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'type' => 'physical',
            'capacity' => 25,
            'room' => 'Room 101',
        ]);

        $response->assertRedirect(route('classes.index'));

        $classGroup = ClassGroup::where('name', 'Hifz Group 1')->first();
        $this->assertNotNull($classGroup);
        $this->assertEquals($course->id, $classGroup->course_id);
        $this->assertEquals(25, $classGroup->capacity);
        $this->assertEquals('Room 101', $classGroup->room);

        // Verify teacher was attached
        $this->assertTrue($classGroup->teachers->contains($teacher->id));
    }

    public function test_owner_can_create_class_group_with_schedule_and_generates_sessions(): void
    {
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tajweed Foundation',
            'code' => 'TAJ-101',
            'level' => 'beginner',
            'status' => 'active',
        ]);

        $teacherUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Teacher Bilal',
            'email' => 'bilal@beyan.edu.et',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
        ]);

        $teacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $teacherUser->id,
            'full_name' => 'Teacher Bilal',
            'email' => $teacherUser->email,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->post(route('classes.store'), [
            'name' => 'Morning Tajweed A',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'type' => 'physical',
            'capacity' => 20,
            'room' => 'Room 202',
            'schedule_days' => [1, 3, 5],
            'start_time' => '08:00',
            'end_time' => '09:30',
        ]);

        $response->assertRedirect(route('classes.index'));

        $classGroup = ClassGroup::where('name', 'Morning Tajweed A')->first();
        $this->assertNotNull($classGroup);

        $schedules = ClassSchedule::where('class_group_id', $classGroup->id)->get();
        $this->assertCount(3, $schedules);
        $this->assertEquals('08:00:00', $schedules->first()->start_time);
        $this->assertEquals('09:30:00', $schedules->first()->end_time);

        $sessionsCount = ClassSession::where('class_group_id', $classGroup->id)->count();
        $this->assertGreaterThan(0, $sessionsCount);

        // Creating another class for the same teacher with overlapping time should return validation error
        $conflictResponse = $this->actingAs($this->owner)->post(route('classes.store'), [
            'name' => 'Conflicting Class',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'type' => 'physical',
            'capacity' => 15,
            'schedule_days' => [1],
            'start_time' => '08:30',
            'end_time' => '10:00',
        ]);

        $conflictResponse->assertSessionHasErrors('start_time');
    }
}
