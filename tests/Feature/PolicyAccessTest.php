<?php

namespace Tests\Feature;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PolicyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $owner;

    protected User $admin;

    protected User $coordinator;

    protected User $teacher;

    protected User $accountant;

    protected User $receptionist;

    protected User $studentUser;

    protected User $guardianUser;

    protected Student $assignedStudent;

    protected Student $unassignedStudent;

    protected AttendanceRecord $assignedAttendance;

    protected AttendanceRecord $unassignedAttendance;

    protected FeeInvoice $assignedInvoice;

    protected FeeInvoice $unassignedInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->withoutTenantScope(function () use ($matrix) {
            $this->institution = Institution::factory()->create([
                'name' => 'Policy Test Madrasa',
                'status' => 'active',
            ]);

            $matrix->seedInstitutionRoles($this->institution->id);
            app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

            // Create users with every institution role
            $this->owner = $this->createUserWithRole('Owner');
            $this->admin = $this->createUserWithRole('Admin');
            $this->coordinator = $this->createUserWithRole('Academic Coordinator');
            $this->teacher = $this->createUserWithRole('Teacher');
            $this->accountant = $this->createUserWithRole('Accountant');
            $this->receptionist = $this->createUserWithRole('Receptionist');
            $this->studentUser = $this->createUserWithRole('Student');
            $this->guardianUser = $this->createUserWithRole('Guardian');

            // Create Students
            $this->assignedStudent = Student::create([
                'institution_id' => $this->institution->id,
                'name' => 'Assigned Student',
                'admission_number' => 'STU-001',
                'user_id' => $this->studentUser->id,
                'guardian_id' => $this->guardianUser->id,
            ]);

            $this->unassignedStudent = Student::create([
                'institution_id' => $this->institution->id,
                'name' => 'Unassigned Student',
                'admission_number' => 'STU-002',
            ]);

            // Assign teacher to assignedStudent ONLY
            $this->assignedStudent->teachers()->attach($this->teacher->id, [
                'institution_id' => $this->institution->id,
            ]);

            // Create Attendance records
            $this->assignedAttendance = AttendanceRecord::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->assignedStudent->id,
                'date' => '2026-11-15',
                'status' => 'present',
            ]);

            $this->unassignedAttendance = AttendanceRecord::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->unassignedStudent->id,
                'date' => '2026-11-15',
                'status' => 'present',
            ]);

            // Create Finance Invoices
            $this->assignedInvoice = FeeInvoice::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->assignedStudent->id,
                'invoice_number' => 'INV-001',
                'title' => 'Assigned Tuition',
                'amount' => 1000,
                'due_date' => '2026-12-01',
            ]);

            $this->unassignedInvoice = FeeInvoice::create([
                'institution_id' => $this->institution->id,
                'student_id' => $this->unassignedStudent->id,
                'invoice_number' => 'INV-002',
                'title' => 'Unassigned Tuition',
                'amount' => 1500,
                'due_date' => '2026-12-01',
            ]);
        });
    }

    protected function createUserWithRole(string $roleName): User
    {
        $user = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => "User {$roleName}",
            'email' => strtolower(str_replace(' ', '.', $roleName)).'@test.madrasa',
            'password' => bcrypt('password'),
            'role' => strtolower(str_replace(' ', '_', $roleName)),
            'email_verified_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);
        $user->assignRole($roleName);

        return $user;
    }

    // ==========================================
    // 1. STUDENTS POLICY TESTS
    // ==========================================

    public function test_teacher_can_open_assigned_student_but_cannot_open_unassigned_student(): void
    {
        // Teacher opening assigned student -> Allowed
        $this->actingAs($this->teacher);
        $this->assertTrue(Gate::allows('view', $this->assignedStudent), 'Teacher MUST be allowed to view assigned student.');

        $responseAssigned = $this->getJson(route('students.show', $this->assignedStudent->id));
        $responseAssigned->assertOk();

        // Teacher opening unassigned student -> FORBIDDEN
        $this->assertFalse(Gate::allows('view', $this->unassignedStudent), 'Teacher must NOT be allowed to view unassigned student.');

        $responseUnassigned = $this->getJson(route('students.show', $this->unassignedStudent->id));
        $responseUnassigned->assertStatus(403);
    }

    public function test_every_role_access_on_students(): void
    {
        // Owner: full CRUD
        $this->actingAs($this->owner);
        $this->assertTrue(Gate::allows('viewAny', Student::class));
        $this->assertTrue(Gate::allows('view', $this->unassignedStudent));
        $this->assertTrue(Gate::allows('create', Student::class));
        $this->assertTrue(Gate::allows('update', $this->unassignedStudent));
        $this->assertTrue(Gate::allows('delete', $this->unassignedStudent));

        // Admin: view, create, update
        $this->actingAs($this->admin);
        $this->assertTrue(Gate::allows('viewAny', Student::class));
        $this->assertTrue(Gate::allows('view', $this->unassignedStudent));
        $this->assertTrue(Gate::allows('create', Student::class));
        $this->assertTrue(Gate::allows('update', $this->unassignedStudent));

        // Academic Coordinator: view, create, update
        $this->actingAs($this->coordinator);
        $this->assertTrue(Gate::allows('viewAny', Student::class));
        $this->assertTrue(Gate::allows('view', $this->unassignedStudent));
        $this->assertTrue(Gate::allows('create', Student::class));
        $this->assertTrue(Gate::allows('update', $this->unassignedStudent));

        // Accountant: view only
        $this->actingAs($this->accountant);
        $this->assertTrue(Gate::allows('viewAny', Student::class));
        $this->assertTrue(Gate::allows('view', $this->unassignedStudent));
        $this->assertFalse(Gate::allows('create', Student::class));

        // Receptionist: view, create, update
        $this->actingAs($this->receptionist);
        $this->assertTrue(Gate::allows('viewAny', Student::class));
        $this->assertTrue(Gate::allows('view', $this->unassignedStudent));
        $this->assertTrue(Gate::allows('create', Student::class));
        $this->assertTrue(Gate::allows('update', $this->unassignedStudent));

        // Student User: can view own record, cannot view others
        $this->actingAs($this->studentUser);
        $this->assertTrue(Gate::allows('view', $this->assignedStudent));
        $this->assertFalse(Gate::allows('view', $this->unassignedStudent));
        $this->assertFalse(Gate::allows('create', Student::class));

        // Guardian User: can view their child's record, cannot view others
        $this->actingAs($this->guardianUser);
        $this->assertTrue(Gate::allows('view', $this->assignedStudent));
        $this->assertFalse(Gate::allows('view', $this->unassignedStudent));
        $this->assertFalse(Gate::allows('create', Student::class));
    }

    // ==========================================
    // 2. ATTENDANCE POLICY TESTS
    // ==========================================

    public function test_teacher_can_view_and_record_attendance_for_assigned_student_only(): void
    {
        $this->actingAs($this->teacher);

        // Assigned student's attendance record
        $this->assertTrue(Gate::allows('view', $this->assignedAttendance));
        $responseAssigned = $this->getJson(route('attendance.show', $this->assignedAttendance->id));
        $responseAssigned->assertOk();

        // Unassigned student's attendance record -> Forbidden
        $this->assertFalse(Gate::allows('view', $this->unassignedAttendance));
        $responseUnassigned = $this->getJson(route('attendance.show', $this->unassignedAttendance->id));
        $responseUnassigned->assertStatus(403);
    }

    public function test_every_role_access_on_attendance(): void
    {
        // Owner, Admin, Coordinator, Receptionist can view and record attendance
        foreach ([$this->owner, $this->admin, $this->coordinator, $this->receptionist] as $user) {
            $this->actingAs($user);
            $this->assertTrue(Gate::allows('viewAny', AttendanceRecord::class));
            $this->assertTrue(Gate::allows('view', $this->unassignedAttendance));
            $this->assertTrue(Gate::allows('create', AttendanceRecord::class));
        }

        // Student can view own attendance record
        $this->actingAs($this->studentUser);
        $this->assertTrue(Gate::allows('view', $this->assignedAttendance));
        $this->assertFalse(Gate::allows('view', $this->unassignedAttendance));
        $this->assertFalse(Gate::allows('create', AttendanceRecord::class));

        // Guardian can view child's attendance record
        $this->actingAs($this->guardianUser);
        $this->assertTrue(Gate::allows('view', $this->assignedAttendance));
        $this->assertFalse(Gate::allows('view', $this->unassignedAttendance));
        $this->assertFalse(Gate::allows('create', AttendanceRecord::class));
    }

    // ==========================================
    // 3. FINANCE POLICY TESTS
    // ==========================================

    public function test_teacher_cannot_access_finance_invoices(): void
    {
        $this->actingAs($this->teacher);

        $this->assertFalse(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertFalse(Gate::allows('view', $this->assignedInvoice));
        $this->assertFalse(Gate::allows('view', $this->unassignedInvoice));
        $this->assertFalse(Gate::allows('create', FeeInvoice::class));

        // HTTP access forbidden
        $this->getJson(route('finance.invoices.index'))->assertStatus(403);
        $this->getJson(route('finance.invoices.show', $this->assignedInvoice->id))->assertStatus(403);
    }

    public function test_every_role_access_on_finance(): void
    {
        // Owner & Admin: full finance access
        $this->actingAs($this->owner);
        $this->assertTrue(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertTrue(Gate::allows('view', $this->assignedInvoice));
        $this->assertTrue(Gate::allows('create', FeeInvoice::class));
        $this->assertTrue(Gate::allows('update', $this->assignedInvoice));

        $this->actingAs($this->admin);
        $this->assertTrue(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertTrue(Gate::allows('view', $this->assignedInvoice));
        $this->assertTrue(Gate::allows('create', FeeInvoice::class));
        $this->assertTrue(Gate::allows('update', $this->assignedInvoice));

        // Accountant: view, create, update
        $this->actingAs($this->accountant);
        $this->assertTrue(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertTrue(Gate::allows('view', $this->assignedInvoice));
        $this->assertTrue(Gate::allows('create', FeeInvoice::class));
        $this->assertTrue(Gate::allows('update', $this->assignedInvoice));

        // Academic Coordinator & Receptionist: cannot view general finance invoices
        $this->actingAs($this->coordinator);
        $this->assertFalse(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertFalse(Gate::allows('create', FeeInvoice::class));

        $this->actingAs($this->receptionist);
        $this->assertFalse(Gate::allows('viewAny', FeeInvoice::class));

        // Student: view own invoice, but not general list
        $this->actingAs($this->studentUser);
        $this->assertFalse(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertTrue(Gate::allows('view', $this->assignedInvoice));
        $this->assertFalse(Gate::allows('view', $this->unassignedInvoice));

        // Guardian: view child's invoice, but not general list
        $this->actingAs($this->guardianUser);
        $this->assertFalse(Gate::allows('viewAny', FeeInvoice::class));
        $this->assertTrue(Gate::allows('view', $this->assignedInvoice));
        $this->assertFalse(Gate::allows('view', $this->unassignedInvoice));
    }
}
