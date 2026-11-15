<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularOwner;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();
        $matrix->seedPlatformRole();

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->withoutTenantScope(function () use ($matrix) {
            $this->institution = Institution::factory()->create([
                'name' => 'Addis Pilot Madrasa',
                'status' => 'active',
                'subscription_plan' => 'pilot',
            ]);

            $matrix->seedInstitutionRoles($this->institution->id);

            // Platform Super Admin: outside any institution
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            $this->superAdmin = User::withoutInstitutionScope()->create([
                'institution_id' => null,
                'name' => 'Platform Operator',
                'email' => 'super@beyan.platform',
                'password' => bcrypt('password'),
                'role' => 'platform_super_admin',
            ]);
            $this->superAdmin->assignRole('Platform Super Admin');

            // Regular Owner inside an institution
            app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);
            $this->regularOwner = User::withoutInstitutionScope()->create([
                'institution_id' => $this->institution->id,
                'name' => 'Madrasa Owner',
                'email' => 'owner@addis.madrasa',
                'password' => bcrypt('password'),
                'role' => 'owner',
            ]);
            $this->regularOwner->assignRole('Owner');
        });
    }

    public function test_platform_super_admin_can_view_all_institutions_across_platform(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('platform.institutions.index'));

        $response->assertOk();

        $data = $response->json();
        $ids = array_column($data, 'id');
        $this->assertContains($this->institution->id, $ids);
    }

    public function test_platform_super_admin_can_suspend_and_activate_institutions(): void
    {
        // 1. Suspend
        $suspendResponse = $this->actingAs($this->superAdmin)
            ->postJson(route('platform.institutions.suspend', $this->institution->id));

        $suspendResponse->assertOk();

        $this->assertEquals('suspended', $this->institution->fresh()->status);

        // Verify Audit Log
        $suspendAudit = AuditLog::where('auditable_type', Institution::class)
            ->where('auditable_id', $this->institution->id)
            ->where('action', 'status_changed')
            ->first();

        $this->assertNotNull($suspendAudit);
        $this->assertEquals('active', $suspendAudit->old_values['status']);
        $this->assertEquals('suspended', $suspendAudit->new_values['status']);

        // 2. Activate
        $activateResponse = $this->actingAs($this->superAdmin)
            ->postJson(route('platform.institutions.activate', $this->institution->id));

        $activateResponse->assertOk();

        $this->assertEquals('active', $this->institution->fresh()->status);
    }

    public function test_platform_super_admin_can_update_subscription_plan(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson(route('platform.institutions.plan', $this->institution->id), [
                'subscription_plan' => 'enterprise',
                'subscription_ends_at' => '2028-12-31',
            ]);

        $response->assertOk();

        $this->assertEquals('enterprise', $this->institution->fresh()->subscription_plan);

        // Verify Audit Log
        $planAudit = AuditLog::where('auditable_type', Institution::class)
            ->where('auditable_id', $this->institution->id)
            ->where('action', 'plan_updated')
            ->first();

        $this->assertNotNull($planAudit);
        $this->assertEquals('enterprise', $planAudit->new_values['subscription_plan']);
    }

    public function test_regular_institution_user_cannot_access_platform_admin_endpoints(): void
    {
        $response = $this->actingAs($this->regularOwner)
            ->getJson(route('platform.institutions.index'));

        $response->assertStatus(403);
    }
}
