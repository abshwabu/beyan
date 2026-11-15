<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Search\LikeStudentSearch;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentSearchPerformanceTest extends TestCase
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
            'name' => 'Admin User',
            'email' => 'admin@beyan.edu.et',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->admin->assignRole('Admin');
    }

    public function test_search_stays_under_2_seconds_with_5000_seeded_students(): void
    {
        $this->actingAs($this->admin);

        $totalStudents = 5000;
        $chunkSize = 500;
        $now = Carbon::now();

        $firstNames = ['Ahmed', 'Bilal', 'Fatima', 'Ibrahim', 'Khadija', 'Maryam', 'Omar', 'Umar', 'Zayd', 'Zainab', 'Yusuf', 'Amina'];
        $lastNames = ['Ali', 'Hassan', 'Mohamed', 'Hussein', 'Ibrahim', 'Abdullah', 'Tariq', 'Khalid', 'Mahmud', 'Suleiman'];
        $statuses = ['active', 'active', 'active', 'inactive', 'suspended'];

        $targetPrefix = 'Zayd';

        // 1. Bulk insert 5,000 students in fast chunks within a single transaction
        DB::transaction(function () use ($totalStudents, $chunkSize, $firstNames, $lastNames, $statuses, $now) {
            for ($c = 0; $c < ($totalStudents / $chunkSize); $c++) {
                $batch = [];
                for ($i = 0; $i < $chunkSize; $i++) {
                    $num = ($c * $chunkSize) + $i + 1;
                    $fName = $firstNames[array_rand($firstNames)];
                    $lName = $lastNames[array_rand($lastNames)];
                    $fullName = "{$fName} {$lName} {$num}";
                    $code = sprintf('BYN-2026-%05d', $num);
                    $status = $statuses[array_rand($statuses)];

                    $batch[] = [
                        'id' => (string) Str::ulid(),
                        'institution_id' => $this->institution->id,
                        'full_name' => $fullName,
                        'name' => $fullName,
                        'student_code' => $code,
                        'admission_number' => $code,
                        'gender' => ($i % 2 === 0) ? 'male' : 'female',
                        'status' => $status,
                        'admission_date' => '2026-01-10',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('students')->insert($batch);
            }
        });

        $this->assertEquals($totalStudents, Student::count());

        $searchService = app(LikeStudentSearch::class);

        // 2. Benchmark Search by Prefix Name
        $startTime = microtime(true);
        $resultsName = $searchService->search([
            'search' => $targetPrefix,
            'per_page' => 25,
        ]);
        $durationName = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $resultsName->total(), "Search for '{$targetPrefix}' should find matching students.");
        $this->assertLessThan(2.0, $durationName, "Search by name took {$durationName} seconds, which exceeds 2.0s limit.");

        // 3. Benchmark Search by Student Code Prefix
        $startTime = microtime(true);
        $resultsCode = $searchService->search([
            'search' => 'BYN-2026-004',
            'per_page' => 25,
        ]);
        $durationCode = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $resultsCode->total());
        $this->assertLessThan(2.0, $durationCode, "Search by code took {$durationCode} seconds, which exceeds 2.0s limit.");

        // 4. Benchmark Filter by Status
        $startTime = microtime(true);
        $resultsStatus = $searchService->search([
            'status' => 'suspended',
            'per_page' => 25,
        ]);
        $durationStatus = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $resultsStatus->total());
        $this->assertLessThan(2.0, $durationStatus, "Search by status took {$durationStatus} seconds, which exceeds 2.0s limit.");

        // 5. Test JSON HTTP Endpoint Response Time
        $startTime = microtime(true);
        $response = $this->getJson(route('students.index', [
            'search' => 'Ahmed',
            'status' => 'active',
        ]));
        $durationHttp = microtime(true) - $startTime;

        $response->assertOk();
        $this->assertLessThan(2.0, $durationHttp, "HTTP student search endpoint took {$durationHttp} seconds, which exceeds 2.0s limit.");
    }
}
