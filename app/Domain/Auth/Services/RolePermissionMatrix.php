<?php

namespace App\Domain\Auth\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionMatrix
{
    /**
     * Complete list of permissions across madrasa domains.
     */
    public const PERMISSIONS = [
        // Students
        'students:view',
        'students:view-assigned',
        'students:create',
        'students:update',
        'students:delete',

        // Attendance
        'attendance:view',
        'attendance:view-assigned',
        'attendance:record',
        'attendance:update',
        'attendance:edit-past',

        // Finance
        'finance:view',
        'finance:manage-fees',
        'finance:record-payment',
        'finance:view-reports',

        // Academics
        'academics:view',
        'academics:manage',

        // Users & Roles
        'users:view',
        'users:invite',
        'users:manage-roles',

        // Institution settings
        'institution:view',
        'institution:update',
        'institution:manage-subscription',

        // Audit Logs
        'audit:view',

        // Platform Super Admin
        'platform:manage-institutions',
    ];

    /**
     * Map of roles to their default permission set.
     */
    public const ROLE_PERMISSIONS = [
        'Owner' => [
            'students:view',
            'students:view-assigned',
            'students:create',
            'students:update',
            'students:delete',
            'attendance:view',
            'attendance:view-assigned',
            'attendance:record',
            'attendance:update',
            'attendance:edit-past',
            'finance:view',
            'finance:manage-fees',
            'finance:record-payment',
            'finance:view-reports',
            'academics:view',
            'academics:manage',
            'users:view',
            'users:invite',
            'users:manage-roles',
            'institution:view',
            'institution:update',
            'institution:manage-subscription',
            'audit:view',
        ],
        'Admin' => [
            'students:view',
            'students:view-assigned',
            'students:create',
            'students:update',
            'attendance:view',
            'attendance:view-assigned',
            'attendance:record',
            'attendance:update',
            'attendance:edit-past',
            'finance:view',
            'finance:manage-fees',
            'finance:record-payment',
            'finance:view-reports',
            'academics:view',
            'academics:manage',
            'users:view',
            'users:invite',
            'users:manage-roles',
            'institution:view',
            'institution:update',
            'audit:view',
        ],
        'Academic Coordinator' => [
            'students:view',
            'students:create',
            'students:update',
            'attendance:view',
            'attendance:record',
            'attendance:update',
            'attendance:edit-past',
            'academics:view',
            'academics:manage',
            'audit:view',
        ],
        'Teacher' => [
            'academics:view',
            'students:view-assigned',
            'attendance:view-assigned',
            'attendance:record',
        ],
        'Accountant' => [
            'students:view',
            'finance:view',
            'finance:manage-fees',
            'finance:record-payment',
            'finance:view-reports',
        ],
        'Receptionist' => [
            'students:view',
            'students:create',
            'students:update',
            'attendance:view',
            'attendance:record',
            'finance:record-payment',
        ],
        'Student' => [
            'students:view-assigned',
            'academics:view',
            'attendance:view-assigned',
            'finance:view-reports',
        ],
        'Guardian' => [
            'students:view-assigned',
            'attendance:view-assigned',
            'finance:view-reports',
        ],
    ];

    /**
     * Seed all system permissions.
     */
    public function seedPermissions(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }
    }

    /**
     * Create the Platform Super Admin role (outside any institution).
     */
    public function seedPlatformRole(): Role
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $role = Role::firstOrCreate([
            'name' => 'Platform Super Admin',
            'guard_name' => 'web',
            'institution_id' => null,
        ]);

        $role->syncPermissions(['platform:manage-institutions']);

        return $role;
    }

    /**
     * Seed the default roles and permissions for a specific institution.
     */
    public function seedInstitutionRoles(string $institutionId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($institutionId);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'institution_id' => $institutionId,
            ]);

            $role->syncPermissions($permissions);
        }
    }
}
