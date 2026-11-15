<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Actions\RegisterStudentAction;
use App\Domain\Students\Models\Guardian;
use App\Domain\Students\Models\Student;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GuardianRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

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
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_one_guardian_can_have_several_children(): void
    {
        $guardian = Guardian::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Fatima Zahra',
            'phone' => '+251911000111',
            'email' => 'fatima@example.com',
        ]);

        $child1 = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Zayd Abdullah',
            'gender' => 'male',
        ]);

        $child2 = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Maryam Abdullah',
            'gender' => 'female',
        ]);

        // Attach Fatima as mother to both children
        $child1->guardians()->attach($guardian->id, [
            'institution_id' => $this->institution->id,
            'relationship_type' => 'Mother',
            'is_primary' => true,
        ]);

        $child2->guardians()->attach($guardian->id, [
            'institution_id' => $this->institution->id,
            'relationship_type' => 'Mother',
            'is_primary' => true,
        ]);

        $this->assertCount(2, $guardian->students);
        $this->assertTrue($guardian->students->contains('id', $child1->id));
        $this->assertTrue($guardian->students->contains('id', $child2->id));

        $this->assertTrue($child1->guardians->contains('id', $guardian->id));
        $this->assertTrue($child2->guardians->contains('id', $guardian->id));
    }

    public function test_one_child_can_have_several_guardians(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Ibrahim Tariq',
            'gender' => 'male',
        ]);

        $father = Guardian::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Tariq Ibrahim',
            'email' => 'tariq@example.com',
        ]);

        $mother = Guardian::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Amina Hassan',
            'email' => 'amina@example.com',
        ]);

        $uncle = Guardian::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Ali Ibrahim',
            'email' => 'ali@example.com',
        ]);

        $student->guardians()->attach($father->id, [
            'institution_id' => $this->institution->id,
            'relationship_type' => 'Father',
            'is_primary' => true,
        ]);

        $student->guardians()->attach($mother->id, [
            'institution_id' => $this->institution->id,
            'relationship_type' => 'Mother',
            'is_primary' => false,
        ]);

        $student->guardians()->attach($uncle->id, [
            'institution_id' => $this->institution->id,
            'relationship_type' => 'Uncle',
            'is_primary' => false,
        ]);

        $student->load('guardians');

        $this->assertCount(3, $student->guardians);
        $this->assertEquals('Tariq Ibrahim', $student->primaryGuardian()->full_name);
        $this->assertTrue($student->guardians->contains('id', $father->id));
        $this->assertTrue($student->guardians->contains('id', $mother->id));
        $this->assertTrue($student->guardians->contains('id', $uncle->id));
    }

    public function test_guardian_account_creation_allows_viewing_own_children_only(): void
    {
        $registerAction = app(RegisterStudentAction::class);

        // Register student 1 with guardian account creation
        $student1 = $registerAction->execute([
            'institution_id' => $this->institution->id,
            'full_name' => 'Bilal Khalid',
            'gender' => 'male',
            'guardians' => [
                [
                    'full_name' => 'Khalid Al-Mansoor',
                    'email' => 'khalid@example.com',
                    'phone' => '+251922334455',
                    'relationship_type' => 'Father',
                    'is_primary' => true,
                    'create_account' => true,
                ],
            ],
        ]);

        // Register student 2 with the same guardian email
        $student2 = $registerAction->execute([
            'institution_id' => $this->institution->id,
            'full_name' => 'Sumayyah Khalid',
            'gender' => 'female',
            'guardians' => [
                [
                    'full_name' => 'Khalid Al-Mansoor',
                    'email' => 'khalid@example.com',
                    'relationship_type' => 'Father',
                    'is_primary' => true,
                ],
            ],
        ]);

        // Register student 3 belonging to an unrelated family
        $unrelatedStudent = $registerAction->execute([
            'institution_id' => $this->institution->id,
            'full_name' => 'Unrelated Student',
            'gender' => 'male',
        ]);

        // Verify guardian user was created with Guardian role
        $guardianUser = User::withoutInstitutionScope()->where('email', 'khalid@example.com')->first();
        $this->assertNotNull($guardianUser);
        $this->assertTrue($guardianUser->hasRole('Guardian'));

        // Guardian can view their own children
        $this->actingAs($guardianUser);
        $this->assertTrue(Gate::allows('view', $student1));
        $this->assertTrue(Gate::allows('view', $student2));

        $res1 = $this->getJson(route('students.show', $student1->id));
        $res1->assertOk();

        $res2 = $this->getJson(route('students.show', $student2->id));
        $res2->assertOk();

        // Guardian CANNOT view unlinked student
        $this->assertFalse(Gate::allows('view', $unrelatedStudent));
        $resUnrelated = $this->getJson(route('students.show', $unrelatedStudent->id));
        $resUnrelated->assertStatus(403);
    }
}
