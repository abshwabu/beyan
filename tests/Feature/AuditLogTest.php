<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->withoutTenantScope(function () use ($matrix) {
            $this->institution = Institution::factory()->create([
                'name' => 'Audit Test Madrasa',
                'status' => 'active',
            ]);

            $matrix->seedInstitutionRoles($this->institution->id);

            $this->adminUser = User::withoutInstitutionScope()->create([
                'institution_id' => $this->institution->id,
                'name' => 'Admin Auditor',
                'email' => 'auditor@test.madrasa',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);
            $this->adminUser->assignRole('Admin');
        });
    }

    public function test_model_mutations_create_audit_entries_in_same_transaction(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Zayd Student',
            'admission_number' => 'AUDIT-001',
            'gender' => 'male',
        ]);

        $createLog = AuditLog::where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($createLog);
        $this->assertEquals($this->adminUser->id, $createLog->user_id);
        $this->assertEquals($this->institution->id, $createLog->institution_id);
        $this->assertEquals('Zayd Student', $createLog->new_values['name']);
        $this->assertNull($createLog->old_values);

        // 2. Update
        $student->update(['name' => 'Zayd Updated']);

        $updateLog = AuditLog::where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->where('action', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertEquals('Zayd Student', $updateLog->old_values['name']);
        $this->assertEquals('Zayd Updated', $updateLog->new_values['name']);

        // 3. Delete
        $student->delete();

        $deleteLog = AuditLog::where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->where('action', 'deleted')
            ->first();

        $this->assertNotNull($deleteLog);
        $this->assertEquals('Zayd Updated', $deleteLog->old_values['name']);
        $this->assertNull($deleteLog->new_values);
    }

    public function test_audit_log_rolls_back_if_database_transaction_fails(): void
    {
        $this->actingAs($this->adminUser);

        try {
            DB::transaction(function () {
                $student = Student::create([
                    'institution_id' => $this->institution->id,
                    'name' => 'Transaction Rollback Test',
                    'admission_number' => 'AUDIT-ROLLBACK',
                ]);

                // Deliberately throw an exception inside the transaction
                throw new \Exception('Simulated crash inside transaction');
            });
        } catch (\Exception $e) {
            // Handled
        }

        // Neither the student nor the audit log should exist
        $this->assertDatabaseMissing('students', ['admission_number' => 'AUDIT-ROLLBACK']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'created', 'auditable_type' => Student::class]);
    }

    public function test_audit_entries_exist_for_role_changes(): void
    {
        $this->actingAs($this->adminUser);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $targetUser = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Multi-Role Staff',
            'email' => 'multirole@test.madrasa',
            'password' => bcrypt('password'),
        ]);

        // Assign initial Teacher role
        $targetUser->assignRolesWithAudit('Teacher');

        $roleLog = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $targetUser->id)
            ->where('action', 'role_assigned')
            ->first();

        $this->assertNotNull($roleLog, 'Audit log entry must exist for role assignment.');
        $this->assertContains('Teacher', $roleLog->new_values['roles']);

        // Small madrasa: assign secondary role (Accountant) as well!
        $targetUser->assignRolesWithAudit('Accountant');

        $secondRoleLog = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $targetUser->id)
            ->where('action', 'role_assigned')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($secondRoleLog);
        $this->assertContains('Accountant', $secondRoleLog->new_values['roles']);

        // Sync roles to change to single role
        $targetUser->syncRolesWithAudit(['Academic Coordinator']);

        $syncLog = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $targetUser->id)
            ->where('action', 'roles_synced')
            ->first();

        $this->assertNotNull($syncLog, 'Audit log entry must exist for roles synced.');
        $this->assertContains('Academic Coordinator', $syncLog->new_values['roles']);
    }

    public function test_audit_entries_exist_for_permission_edits(): void
    {
        $this->actingAs($this->adminUser);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $role = Role::where('name', 'Teacher')
            ->where('institution_id', $this->institution->id)
            ->first();

        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $newPermissions = array_merge($oldPermissions, ['attendance:update']);

        DB::transaction(function () use ($role, $oldPermissions, $newPermissions) {
            $role->syncPermissions($newPermissions);

            $this->institution->recordAudit(
                'permission_edited',
                ['role' => $role->name, 'permissions' => $oldPermissions],
                ['role' => $role->name, 'permissions' => $newPermissions]
            );
        });

        $permAudit = AuditLog::where('action', 'permission_edited')
            ->where('auditable_id', $this->institution->id)
            ->first();

        $this->assertNotNull($permAudit, 'Audit log entry must exist for permission edit.');
        $this->assertContains('attendance:update', $permAudit->new_values['permissions']);
    }
}
