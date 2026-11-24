<?php

namespace Database\Seeders;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Teachers\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoInstitutionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        // Run seeding without global tenant scope restrictions
        $tenantManager->withoutTenantScope(function () {
            $matrix = new RolePermissionMatrix;
            $matrix->seedPermissions();
            $matrix->seedPlatformRole();

            $this->seedPlatformSuperAdmin();
            $this->seedBeyanAcademy($matrix);
            $this->seedAlHudaAcademy($matrix);
        });
    }

    protected function seedPlatformSuperAdmin(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $admin = User::withoutInstitutionScope()->updateOrCreate(
            ['email' => 'superadmin@beyan.platform'],
            [
                'institution_id' => null,
                'name' => 'Platform Super Admin',
                'role' => 'platform_super_admin',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => Carbon::now(),
            ]
        );

        $admin->assignRole('Platform Super Admin');
    }

    protected function seedBeyanAcademy(RolePermissionMatrix $matrix): void
    {
        $institution = Institution::updateOrCreate(
            ['slug' => 'beyan-academy'],
            [
                'name' => 'Beyan Academy',
                'mode' => 'hybrid',
                'timezone' => 'Africa/Addis_Ababa',
                'currency' => 'ETB',
                'locale' => 'am',
                'status' => 'active',
                'subscription_plan' => 'pilot',
                'subscription_ends_at' => Carbon::now()->addYear(),
                'contact' => [
                    'email' => 'contact@beyan.edu.et',
                    'phone' => '+251 91 123 4567',
                    'address' => 'Bole Subcity, Addis Ababa, Ethiopia',
                ],
            ]
        );

        $matrix->seedInstitutionRoles($institution->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($institution->id);

        // Owner User
        $owner = User::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'email' => 'admin@beyan.edu.et',
            ],
            [
                'name' => 'Ustadh Abdulrahim Beyan',
                'role' => 'owner',
                'phone' => '+251 91 123 4567',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => Carbon::now(),
            ]
        );
        $owner->assignRole('Owner');

        // Teacher User
        $teacher = User::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'email' => 'teacher@beyan.edu.et',
            ],
            [
                'name' => 'Ustadh Bilal Mohammed',
                'role' => 'teacher',
                'phone' => '+251 92 234 5678',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => Carbon::now(),
            ]
        );
        $teacher->assignRole('Teacher');

        // Teacher Profile
        Teacher::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'user_id' => $teacher->id,
            ],
            [
                'full_name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'specialization' => 'Hifz & Tajweed',
                'qualifications' => ['Ijazah in Hafs an Asim', '10 Years Madrasa Teaching'],
                'status' => 'active',
            ]
        );

        // Core Courses
        (new DefaultCoursesSeeder)->run($institution->id);

        // Academic Year
        $academicYear = AcademicYear::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'name' => '2017 E.C. (2024-2025)',
            ],
            [
                'start_date' => '2024-09-11',
                'end_date' => '2025-07-07',
                'is_current' => true,
            ]
        );

        // Terms
        Term::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Term 1',
            ],
            [
                'start_date' => '2024-09-11',
                'end_date' => '2024-12-25',
                'is_current' => false,
            ]
        );

        Term::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Term 2',
            ],
            [
                'start_date' => '2025-01-05',
                'end_date' => '2025-04-10',
                'is_current' => true,
            ]
        );

        Term::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Term 3',
            ],
            [
                'start_date' => '2025-04-20',
                'end_date' => '2025-07-07',
                'is_current' => false,
            ]
        );
    }

    protected function seedAlHudaAcademy(RolePermissionMatrix $matrix): void
    {
        $institution = Institution::updateOrCreate(
            ['slug' => 'alhuda-academy'],
            [
                'name' => 'Al-Huda Academy',
                'mode' => 'online',
                'timezone' => 'Africa/Addis_Ababa',
                'currency' => 'ETB',
                'locale' => 'en',
                'status' => 'active',
                'subscription_plan' => 'standard',
                'subscription_ends_at' => Carbon::now()->addYear(),
                'contact' => [
                    'email' => 'info@alhuda.edu.et',
                    'phone' => '+251 93 345 6789',
                    'address' => 'Mercato, Addis Ababa, Ethiopia',
                ],
            ]
        );

        $matrix->seedInstitutionRoles($institution->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($institution->id);

        // Owner User
        $owner = User::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'email' => 'admin@alhuda.edu.et',
            ],
            [
                'name' => 'Sheikh Omar Al-Huda',
                'role' => 'owner',
                'phone' => '+251 93 345 6789',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => Carbon::now(),
            ]
        );
        $owner->assignRole('Owner');

        // Teacher User
        $teacher = User::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'email' => 'teacher@alhuda.edu.et',
            ],
            [
                'name' => 'Ustadh Zayd Hamza',
                'role' => 'teacher',
                'phone' => '+251 94 456 7890',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => Carbon::now(),
            ]
        );
        $teacher->assignRole('Teacher');

        // Teacher Profile
        Teacher::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'user_id' => $teacher->id,
            ],
            [
                'full_name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'specialization' => 'Hifz & Tajweed',
                'qualifications' => ['Ijazah in Hafs an Asim', '10 Years Madrasa Teaching'],
                'status' => 'active',
            ]
        );

        // Core Courses
        (new DefaultCoursesSeeder)->run($institution->id);

        // Academic Year
        $academicYear = AcademicYear::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'name' => '1446-1447 Hijri',
            ],
            [
                'start_date' => '2024-07-07',
                'end_date' => '2025-06-25',
                'is_current' => true,
            ]
        );

        // Terms
        Term::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'First Semester',
            ],
            [
                'start_date' => '2024-07-07',
                'end_date' => '2024-12-15',
                'is_current' => false,
            ]
        );

        Term::withoutInstitutionScope()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Second Semester',
            ],
            [
                'start_date' => '2025-01-01',
                'end_date' => '2025-06-25',
                'is_current' => true,
            ]
        );
    }
}
