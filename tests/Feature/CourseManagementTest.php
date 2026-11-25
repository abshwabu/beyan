<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\Course;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseManagementTest extends TestCase
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

    public function test_owner_can_view_courses_page(): void
    {
        Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Qur\'an Tajweed',
            'code' => 'TAJ-101',
            'level' => 'beginner',
            'duration' => '3 Months',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->get(route('courses.index'));
        $response->assertOk();
    }

    public function test_owner_can_create_course(): void
    {
        $response = $this->actingAs($this->owner)->post(route('courses.store'), [
            'name' => 'Full Qur\'an Memorization',
            'code' => 'HIFZ-01',
            'level' => 'all_levels',
            'duration' => '2 Years',
            'learning_objectives' => "Memorize 30 Juz\nMaster Tajweed",
            'description' => 'Comprehensive Hifz program covering Sabaq, Sabqi, and Manzil.',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('courses.index'));

        $this->assertDatabaseHas('courses', [
            'institution_id' => $this->institution->id,
            'name' => 'Full Qur\'an Memorization',
            'code' => 'HIFZ-01',
            'level' => 'all_levels',
            'status' => 'active',
        ]);

        $course = Course::where('code', 'HIFZ-01')->first();
        $this->assertNotNull($course);
        $this->assertCount(2, $course->learning_objectives);
    }

    public function test_cannot_create_course_with_duplicate_code_in_same_institution(): void
    {
        Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Course A',
            'code' => 'ARABIC-1',
            'level' => 'beginner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->post(route('courses.store'), [
            'name' => 'Course B',
            'code' => 'ARABIC-1',
            'level' => 'intermediate',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_owner_can_update_course(): void
    {
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Old Title',
            'code' => 'OLD-01',
            'level' => 'beginner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->put(route('courses.update', $course->id), [
            'name' => 'Updated Title',
            'code' => 'NEW-01',
            'level' => 'intermediate',
            'duration' => '6 Months',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('courses.index'));

        $course->refresh();
        $this->assertEquals('Updated Title', $course->name);
        $this->assertEquals('NEW-01', $course->code);
        $this->assertEquals('intermediate', $course->level);
    }

    public function test_owner_can_delete_unused_course(): void
    {
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Unused Course',
            'code' => 'UNUSED-01',
            'level' => 'beginner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('courses.destroy', $course->id));
        $response->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_cannot_delete_course_with_active_class_groups(): void
    {
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Active Course',
            'code' => 'ACTIVE-01',
            'level' => 'beginner',
            'status' => 'active',
        ]);

        ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $course->id,
            'name' => 'Halaqah 1',
            'capacity' => 15,
            'type' => 'physical',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('courses.destroy', $course->id));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
        ]);
    }
}
