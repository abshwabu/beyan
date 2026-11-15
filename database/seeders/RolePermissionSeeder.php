<?php

namespace Database\Seeders;

use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = new RolePermissionMatrix;

        // 1. Seed global permissions
        $matrix->seedPermissions();

        // 2. Seed platform role
        $matrix->seedPlatformRole();

        // 3. Seed roles for all institutions
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->withoutTenantScope(function () use ($matrix) {
            $institutions = Institution::all();
            foreach ($institutions as $institution) {
                $matrix->seedInstitutionRoles($institution->id);
            }
        });
    }
}
