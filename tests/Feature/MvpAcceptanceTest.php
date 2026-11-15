<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\StudentIdGenerator;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MvpAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institutionA;

    protected Institution $institutionB;

    protected User $adminUserA;

    protected User $adminUserB;

    protected User $teacherUserA;

    protected Teacher $teacherProfileA;

    protected Student $studentA1;

    protected Student $studentA2;

    protected Student $studentB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuranReferenceSeeder::class);

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();

        // 1. Institution A (Beyan Academy)
        $this->institutionA = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et', 'phone' => '+251911000001'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);
        $matrix->seedInstitutionRoles($this->institutionA->id);

        // 2. Institution B (Al-Hikmah Madrasa)
        $this->institutionB = Institution::create([
            'name' => 'Al-Hikmah Madrasa',
            'slug' => 'al-hikmah',
            'contact' => ['email' => 'admin@alhikmah.edu.et', 'phone' => '+251911000002'],
            'mode' => 'online',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'en',
            'status' => 'active',
            'subscription_plan' => 'basic',
        ]);
        $matrix->seedInstitutionRoles($this->institutionB->id);

        // Set tenant to Institution A
        app(TenantManager::class)->setTenant($this->institutionA);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institutionA->id);

        // Admin A
        $this->adminUserA = User::create([
            'institution_id' => $this->institutionA->id,
            'name' => 'Admin A',
            'email' => 'admin.a@beyan.edu.et',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);
        $this->adminUserA->assignRole('Admin');

        // Teacher A
        $this->teacherUserA = User::create([
            'institution_id' => $this->institutionA->id,
            'name' => 'Teacher A',
            'email' => 'teacher.a@beyan.edu.et',
            'role' => 'teacher',
            'password' => bcrypt('password'),
        ]);
        $this->teacherUserA->assignRole('Teacher');

        $this->teacherProfileA = Teacher::create([
            'institution_id' => $this->institutionA->id,
            'user_id' => $this->teacherUserA->id,
            'full_name' => 'Teacher A',
            'email' => 'teacher.a@beyan.edu.et',
            'status' => 'active',
        ]);

        // Students in Institution A
        $this->studentA1 = Student::create([
            'institution_id' => $this->institutionA->id,
            'first_name' => 'Ahmad',
            'last_name' => 'A',
            'full_name' => 'Ahmad A',
            'student_code' => 'BYN-2026-0001',
            'admission_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->studentA2 = Student::create([
            'institution_id' => $this->institutionA->id,
            'first_name' => 'Fatima',
            'last_name' => 'A',
            'full_name' => 'Fatima A',
            'student_code' => 'BYN-2026-0002',
            'admission_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Assign only studentA1 to teacherUserA
        $this->teacherUserA->assignedStudents()->attach($this->studentA1->id, [
            'institution_id' => $this->institutionA->id,
        ]);

        // Set tenant to Institution B to create its admin and student
        app(TenantManager::class)->withoutTenantScope(function () {
            app(PermissionRegistrar::class)->setPermissionsTeamId($this->institutionB->id);

            $this->adminUserB = User::create([
                'institution_id' => $this->institutionB->id,
                'name' => 'Admin B',
                'email' => 'admin.b@alhikmah.edu.et',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]);
            $this->adminUserB->assignRole('Admin');

            $this->studentB1 = Student::create([
                'institution_id' => $this->institutionB->id,
                'first_name' => 'Yusuf',
                'last_name' => 'B',
                'full_name' => 'Yusuf B',
                'student_code' => 'AHK-2026-0001',
                'admission_date' => '2026-01-01',
                'status' => 'active',
            ]);
        });

        // Reset tenant back to Institution A
        app(TenantManager::class)->setTenant($this->institutionA);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institutionA->id);
    }

    /**
     * Requirement 1: Separate workspace per institution.
     */
    public function test_acceptance_criteria_1_separate_workspace_per_institution(): void
    {
        $this->actingAs($this->adminUserA);

        // Institution A admin sees only Institution A's students
        $response = $this->getJson(route('students.index'));
        $response->assertOk();

        $studentNames = collect($response->json('data'))->pluck('full_name')->all();
        $this->assertContains('Ahmad A', $studentNames);
        $this->assertContains('Fatima A', $studentNames);
        $this->assertNotContains('Yusuf B', $studentNames);

        // Querying Student model inside tenant scope never returns Institution B records
        $studentsInDb = Student::all();
        $this->assertCount(2, $studentsInDb);
        $this->assertFalse($studentsInDb->contains('id', $this->studentB1->id));
    }

    /**
     * Requirement 2: Unique race-safe student ID generation.
     */
    public function test_acceptance_criteria_2_unique_student_id_generation(): void
    {
        $generator = app(StudentIdGenerator::class);
        $year = now()->year;

        $id1 = $generator->generate($this->institutionA);
        $id2 = $generator->generate($this->institutionA);

        $this->assertNotEquals($id1, $id2);
        $this->assertStringStartsWith("BYN-{$year}-", $id1);
        $this->assertStringStartsWith("BYN-{$year}-", $id2);

        // Verify database unique constraint on (institution_id, student_code)
        $this->expectException(QueryException::class);
        Student::create([
            'institution_id' => $this->institutionA->id,
            'first_name' => 'Duplicate',
            'last_name' => 'Code',
            'full_name' => 'Duplicate Code',
            'student_code' => $this->studentA1->student_code, // duplicate!
            'admission_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    /**
     * Requirement 3: Teacher sees only authorized students.
     */
    public function test_acceptance_criteria_3_teacher_sees_only_authorized_students(): void
    {
        $this->actingAs($this->teacherUserA);

        // Teacher can view their assigned student
        $assignedResponse = $this->getJson(route('students.show', $this->studentA1->id));
        $assignedResponse->assertOk();
        $this->assertEquals('Ahmad A', $assignedResponse->json('full_name'));

        // Teacher attempting to view an unassigned student in the same institution is blocked (403)
        $unassignedResponse = $this->getJson(route('students.show', $this->studentA2->id));
        $unassignedResponse->assertForbidden();

        // Teacher attempting to record Hifz for unassigned student is blocked (403)
        $hifzResponse = $this->postJson(route('hifz.entries.store'), [
            'student_id' => $this->studentA2->id,
            'date' => now()->toDateString(),
            'type' => 'sabaq',
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 7,
            'quality_rating' => 'good',
        ]);
        $hifzResponse->assertForbidden();
    }

    /**
     * Requirement 4: Payment updates balance atomically.
     */
    public function test_acceptance_criteria_4_payment_updates_balance_atomically(): void
    {
        $this->actingAs($this->adminUserA);

        // 1. Create invoice of 1,000.00 ETB (100,000 minor units)
        $invResponse = $this->postJson(route('finance.invoices.store'), [
            'student_id' => $this->studentA1->id,
            'invoice_number' => 'INV-TEST-001',
            'title' => 'Monthly Hifz Fee',
            'amount' => 1000,
            'due_date' => now()->addDays(15)->toDateString(),
        ]);
        $invResponse->assertStatus(201);
        $invoiceId = $invResponse->json('id');

        $invoice = FeeInvoice::findOrFail($invoiceId);
        $this->assertEquals(100000, $invoice->balance_due);
        $this->assertEquals('unpaid', $invoice->status);

        // 2. Partial payment: 400.00 ETB
        $partialPay = $this->postJson(route('finance.invoices.payments.store', ['id' => $invoiceId]), [
            'amount' => 400,
            'payment_method' => 'cash',
            'idempotency_key' => Str::uuid()->toString(),
        ]);
        $partialPay->assertStatus(201);
        $this->assertEquals(60000, $partialPay->json('balance_due'));
        $this->assertEquals('partial', $partialPay->json('status'));

        $invoice->refresh();
        $this->assertEquals(400, $invoice->paid_amount);
        $this->assertEquals(60000, $invoice->balance_due);
        $this->assertEquals('partial', $invoice->status);

        // 3. Final payment: 600.00 ETB
        $finalPay = $this->postJson(route('finance.invoices.payments.store', ['id' => $invoiceId]), [
            'amount' => 600,
            'payment_method' => 'bank_transfer',
            'idempotency_key' => Str::uuid()->toString(),
        ]);
        $finalPay->assertStatus(201);
        $this->assertEquals(0, $finalPay->json('balance_due'));
        $this->assertEquals('paid', $finalPay->json('status'));

        $invoice->refresh();
        $this->assertEquals(1000, $invoice->paid_amount);
        $this->assertEquals(0, $invoice->balance_due);
        $this->assertEquals('paid', $invoice->status);
    }

    /**
     * Requirement 5: Cross-institution access strictly denied.
     */
    public function test_acceptance_criteria_5_cross_institution_access_denied(): void
    {
        $this->actingAs($this->adminUserA);

        // Admin A attempts to access Institution B's student -> 404 (due to global tenancy scope)
        $studentResponse = $this->getJson(route('students.show', $this->studentB1->id));
        $this->assertTrue(in_array($studentResponse->status(), [403, 404]));

        // Admin A attempts to view reports with Institution B's student id filter -> empty results
        $reportResponse = $this->getJson(route('reports.show', [
            'type' => 'students',
            'student_id' => $this->studentB1->id,
        ]));
        $reportResponse->assertOk();
        $this->assertCount(2, $reportResponse->json('rows'));
        $this->assertFalse(collect($reportResponse->json('rows'))->contains('student_code', $this->studentB1->student_code));
    }
}
