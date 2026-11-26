<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeePlan;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentDocument;
use App\Domain\Students\Models\StudentStatusHistory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

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
            'name' => 'Admin Staff',
            'email' => 'staff@beyan.edu.et',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->admin->assignRole('Admin');
    }

    public function test_can_register_student_via_mobile_first_form_with_minimal_fields(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('students.store'), [
            'full_name' => 'Abdullah Ibn Masood',
            'gender' => 'male',
            'date_of_birth' => '2015-04-12',
            'admission_date' => '2026-02-01',
            'initial_class' => 'Hifz Level 1',
            'contact' => [
                'phone' => '+251911223344',
                'address' => 'Addis Ababa, Kirkos',
            ],
            'guardians' => [
                [
                    'full_name' => 'Ibn Masood',
                    'relationship_type' => 'Father',
                    'phone' => '+251911223344',
                    'is_primary' => true,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $studentData = $response->json();

        $this->assertNotEmpty($studentData['id']);
        $this->assertMatchesRegularExpression('/^BYN-2026-\d{4}$/', $studentData['student_code']);
        $this->assertEquals('Abdullah Ibn Masood', $studentData['full_name']);
        $this->assertEquals('active', $studentData['status']);

        // Verify initial enrollment
        $student = Student::findOrFail($studentData['id']);
        $this->assertNotNull($student->currentEnrollment);
        $this->assertEquals('Hifz Level 1', $student->currentEnrollment->class_name);
        $this->assertEquals('current', $student->currentEnrollment->status);

        // Verify guardian
        $this->assertCount(1, $student->guardians);
        $this->assertEquals('Ibn Masood', $student->guardians->first()->full_name);
    }

    public function test_can_view_and_update_student_profile(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Original Name',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // View student profile
        $showResponse = $this->getJson(route('students.show', $student->id));
        $showResponse->assertOk()
            ->assertJsonPath('full_name', 'Original Name');

        // Update student profile
        $updateResponse = $this->putJson(route('students.update', $student->id), [
            'full_name' => 'Updated Name',
            'gender' => 'female',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('full_name', 'Updated Name')
            ->assertJsonPath('gender', 'female');

        $student->refresh();
        $this->assertEquals('Updated Name', $student->full_name);
        $this->assertEquals('female', $student->gender);
    }

    public function test_status_changes_record_reason_and_effective_date_preserving_history(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Hassan Student',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('students.status', $student->id), [
            'status' => 'suspended',
            'reason' => 'Attendance violation disciplinary review',
            'effective_date' => '2026-03-01',
        ]);

        $response->assertOk()
            ->assertJsonPath('student.status', 'suspended');

        $student->refresh();
        $this->assertEquals('suspended', $student->status);

        // Check history row created
        $history = StudentStatusHistory::where('student_id', $student->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('active', $history->previous_status);
        $this->assertEquals('suspended', $history->new_status);
        $this->assertEquals('Attendance violation disciplinary review', $history->reason);
        $this->assertEquals('2026-03-01', $history->effective_date->format('Y-m-d'));
        $this->assertEquals($this->admin->id, $history->changed_by_id);

        // Record a second status change
        $this->postJson(route('students.status', $student->id), [
            'status' => 'active',
            'reason' => 'Disciplinary suspension served',
            'effective_date' => '2026-03-15',
        ]);

        // Both history records are preserved!
        $this->assertCount(2, $student->statusHistories);
    }

    public function test_class_transfers_use_effective_dated_rows_and_never_delete_history(): void
    {
        $this->actingAs($this->admin);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Tariq Student',
            'status' => 'active',
        ]);

        // Initial class
        $initialEnrollment = $student->enrollments()->create([
            'institution_id' => $this->institution->id,
            'class_name' => 'Level 1 Beginners',
            'start_date' => '2026-01-01',
            'status' => 'current',
        ]);

        // Transfer to Level 2
        $response = $this->postJson(route('students.transfer', $student->id), [
            'class_name' => 'Level 2 Intermediate',
            'transfer_date' => '2026-06-01',
            'notes' => 'Passed Level 1 exam with distinction',
        ]);

        $response->assertOk();

        // Check previous enrollment was updated, NOT deleted!
        $initialEnrollment->refresh();
        $this->assertEquals('transferred', $initialEnrollment->status);
        $this->assertEquals('2026-06-01', $initialEnrollment->end_date->format('Y-m-d'));

        // Check new enrollment is active
        $student->refresh();
        $this->assertEquals('Level 2 Intermediate', $student->currentEnrollment->class_name);
        $this->assertEquals('current', $student->currentEnrollment->status);
        $this->assertEquals('2026-06-01', $student->currentEnrollment->start_date->format('Y-m-d'));

        // All history rows are retained
        $this->assertCount(2, $student->enrollments);
    }

    public function test_document_upload_uses_private_storage_and_tenant_scoped_paths(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'full_name' => 'Zainab Document Test',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('birth_certificate.pdf', 250, 'application/pdf');

        // Upload
        $uploadResponse = $this->post(route('students.documents.upload', $student->id), [
            'title' => 'Official Birth Certificate',
            'document' => $file,
        ]);

        $uploadResponse->assertRedirect();

        $document = StudentDocument::where('student_id', $student->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals('Official Birth Certificate', $document->title);
        $this->assertEquals('birth_certificate.pdf', $document->file_name);

        // Path must be tenant-scoped: tenants/{institution_id}/students/{student_id}/documents/...
        $expectedPrefix = "tenants/{$this->institution->id}/students/{$student->id}/documents/";
        $this->assertStringStartsWith($expectedPrefix, $document->file_path);

        // File must physically exist in private storage
        Storage::disk('local')->assertExists($document->file_path);

        // Download document
        $downloadResponse = $this->get(route('students.documents.download', [$student->id, $document->id]));
        $downloadResponse->assertOk();

        // Delete document
        $deleteResponse = $this->deleteJson(route('students.documents.delete', [$student->id, $document->id]));
        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('student_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->file_path);
    }

    public function test_can_link_student_to_fee_plan_on_creation_and_after_creation(): void
    {
        $this->actingAs($this->admin);

        $planA = FeePlan::create([
            'institution_id' => $this->institution->id,
            'name' => 'Standard Monthly',
            'code' => 'STD-M',
            'period_type' => 'monthly',
            'amount' => 150000,
            'currency' => 'ETB',
            'is_active' => true,
        ]);

        $planB = FeePlan::create([
            'institution_id' => $this->institution->id,
            'name' => 'Full Hifz Boarding',
            'code' => 'HIFZ-B',
            'period_type' => 'monthly',
            'amount' => 250000,
            'currency' => 'ETB',
            'is_active' => true,
        ]);

        // 1. Link during student creation
        $createResponse = $this->post(route('students.store'), [
            'full_name' => 'Hamza Tariq',
            'gender' => 'male',
            'fee_plan_id' => $planA->id,
        ]);

        $student = Student::where('full_name', 'Hamza Tariq')->first();
        $this->assertNotNull($student);
        $this->assertEquals($planA->id, $student->fee_plan_id);
        $createResponse->assertRedirect(route('students.show', $student->id));

        // 2. Change linked plan after creation
        $updateResponse = $this->put(route('students.update', $student->id), [
            'fee_plan_id' => $planB->id,
        ]);

        $updateResponse->assertRedirect(route('students.show', $student->id));
        $student->refresh();
        $this->assertEquals($planB->id, $student->fee_plan_id);
        $this->assertEquals('Full Hifz Boarding', $student->feePlan->name);
    }
}
