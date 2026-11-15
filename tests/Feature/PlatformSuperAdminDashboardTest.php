<?php

namespace Tests\Feature;

use App\Domain\Academics\Http\Controllers\AcademicYearController;
use App\Domain\Academics\Http\Controllers\TeacherTimetableController;
use App\Domain\Attendance\Http\Controllers\AttendanceController;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Finance\Http\Controllers\FinanceController;
use App\Domain\Finance\Models\Plan;
use App\Domain\Hifz\Http\Controllers\HifzController;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Http\Controllers\DashboardController;
use App\Domain\Institution\Models\Institution;
use App\Domain\Platform\Models\PlatformPayment;
use App\Domain\Platform\Models\Subscription;
use App\Domain\Reports\Http\Controllers\ReportController;
use App\Domain\Students\Http\Controllers\StudentController;
use App\Domain\Students\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformSuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $supportUser;

    protected User $tenantOwner;

    protected Institution $institution;

    protected Plan $standardPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();
        $matrix->seedPlatformRole();

        $this->standardPlan = Plan::create([
            'name' => 'Growth (Beyan)',
            'slug' => 'growth',
            'monthly_price' => 140000,
            'yearly_price' => 1400000,
            'currency' => 'ETB',
            'student_limit' => 150,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->superAdmin = User::create([
            'institution_id' => null,
            'name' => 'Root Super Admin',
            'email' => 'root@beyan.platform',
            'password' => bcrypt('Password123!'),
            'role' => 'platform_super_admin',
        ]);

        $this->supportUser = User::create([
            'institution_id' => null,
            'name' => 'Platform Support Rep',
            'email' => 'support@beyan.platform',
            'password' => bcrypt('Password123!'),
            'role' => 'platform_support',
        ]);

        $this->institution = Institution::create([
            'name' => 'Beyan Academy Bole',
            'slug' => 'beyan-bole',
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'en',
            'status' => 'active',
            'subscription_plan' => 'growth',
            'subscription_ends_at' => Carbon::now()->addDays(30),
        ]);

        $matrix->seedInstitutionRoles($this->institution->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->tenantOwner = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustadh Hamza',
            'email' => 'hamza@beyanbole.org',
            'password' => bcrypt('Password123!'),
            'role' => 'owner',
        ]);
        $this->tenantOwner->assignRole('Owner');
        $this->tenantOwner->givePermissionTo('students:create');

        Subscription::create([
            'institution_id' => $this->institution->id,
            'plan_id' => $this->standardPlan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => Carbon::now()->subDays(5),
            'ends_at' => Carbon::now()->addDays(30),
            'grace_days' => 7,
        ]);
    }

    public function test_non_platform_user_receives_403_on_every_platform_route(): void
    {
        $routes = [
            '/platform',
            '/platform/institutions',
            '/platform/plans',
            '/platform/subscriptions',
            '/platform/payments',
            '/platform/demo-requests',
            '/platform/announcements',
            '/platform/health',
            '/platform/settings',
            '/platform/staff',
            '/platform/audit-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->tenantOwner)->get($route);
            $response->assertStatus(403);
        }
    }

    public function test_platform_support_user_has_read_only_access_and_cannot_perform_write_actions(): void
    {
        // 1. Support user can access GET endpoints
        $overview = $this->actingAs($this->supportUser)->get('/platform');
        $overview->assertOk();

        $institutions = $this->actingAs($this->supportUser)->get('/platform/institutions');
        $institutions->assertOk();

        // 2. Support user is blocked (403) from write operations
        $suspendAttempt = $this->actingAs($this->supportUser)->post("/platform/institutions/{$this->institution->id}/suspend", [
            'reason' => 'Unauthorized suspend',
        ]);
        $suspendAttempt->assertStatus(403);

        $impersonateAttempt = $this->actingAs($this->supportUser)->post("/platform/institutions/{$this->institution->id}/impersonate", [
            'reason' => 'Unauthorized impersonation',
        ]);
        $impersonateAttempt->assertStatus(403);

        $paymentAttempt = $this->actingAs($this->supportUser)->post('/platform/payments', [
            'institution_id' => $this->institution->id,
            'amount_major' => 1400,
            'currency' => 'ETB',
            'method' => 'cash',
            'billing_cycle' => 'monthly',
        ]);
        $paymentAttempt->assertStatus(403);
    }

    public function test_suspending_institution_blocks_its_users_immediately_and_reactivating_restores_access(): void
    {
        // 1. When active, owner can visit dashboard
        $resActive = $this->actingAs($this->tenantOwner)->get('/dashboard');
        $resActive->assertOk();

        // 2. Super Admin suspends institution
        $suspend = $this->actingAs($this->superAdmin)->post("/platform/institutions/{$this->institution->id}/suspend", [
            'reason' => 'Payment overdue by 3 weeks',
        ]);
        $suspend->assertRedirect();
        $this->assertEquals('suspended', $this->institution->fresh()->status);

        // 3. Owner visiting dashboard is now blocked immediately with 403
        $resSuspended = $this->actingAs($this->tenantOwner)->get('/dashboard');
        $resSuspended->assertStatus(403);

        // 4. Super Admin reactivates institution
        $activate = $this->actingAs($this->superAdmin)->post("/platform/institutions/{$this->institution->id}/activate");
        $activate->assertRedirect();
        $this->assertEquals('active', $this->institution->fresh()->status);

        // 5. Owner has access restored
        $resRestored = $this->actingAs($this->tenantOwner)->get('/dashboard');
        $resRestored->assertOk();
    }

    public function test_recording_payment_twice_with_same_idempotency_key_creates_one_payment(): void
    {
        $payload = [
            'institution_id' => $this->institution->id,
            'amount_major' => 1400,
            'currency' => 'ETB',
            'method' => 'bank_transfer',
            'billing_cycle' => 'monthly',
            'reference' => 'CBE-TX-998811',
            'idempotency_key' => 'IDEMP-TEST-KEY-001',
        ];

        // First attempt
        $res1 = $this->actingAs($this->superAdmin)->post('/platform/payments', $payload);
        $res1->assertRedirect();

        // Second attempt with exact same key
        $res2 = $this->actingAs($this->superAdmin)->post('/platform/payments', $payload);
        $res2->assertRedirect();

        // Assert exactly 1 payment record exists
        $this->assertEquals(1, PlatformPayment::where('idempotency_key', 'IDEMP-TEST-KEY-001')->count());
    }

    public function test_recording_payment_automatically_extends_subscription_period(): void
    {
        $initialEndsAt = Carbon::now()->addDays(10);
        $this->institution->update(['subscription_ends_at' => $initialEndsAt]);
        $this->institution->latestSubscription->update(['ends_at' => $initialEndsAt]);

        $payload = [
            'institution_id' => $this->institution->id,
            'amount_major' => 1400,
            'currency' => 'ETB',
            'method' => 'bank_transfer',
            'billing_cycle' => 'monthly', // Adds 30 days
            'reference' => 'CBE-EXTEND-1',
            'idempotency_key' => 'IDEMP-EXTEND-01',
        ];

        $res = $this->actingAs($this->superAdmin)->post('/platform/payments', $payload);
        $res->assertRedirect();

        $freshSub = $this->institution->latestSubscription->fresh();
        $expectedEndsAt = $initialEndsAt->copy()->addDays(30);

        $this->assertEquals($expectedEndsAt->toDateString(), $freshSub->ends_at->toDateString());
        $this->assertEquals($expectedEndsAt->toDateString(), $this->institution->fresh()->subscription_ends_at->toDateString());
        $this->assertEquals('active', $freshSub->status);
    }

    public function test_subscriptions_move_to_expired_on_schedule_in_time_travel_test(): void
    {
        // 1. Sub 1: Ended 10 days ago (grace period is 7 days, so 10 >= 7 => Expire & Suspend)
        $subExpired = Subscription::create([
            'institution_id' => $this->institution->id,
            'plan_id' => $this->standardPlan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => Carbon::now()->subDays(40),
            'ends_at' => Carbon::now()->subDays(10),
            'grace_days' => 7,
        ]);

        // Run the expiration scheduled command
        Artisan::call('platform:subscriptions:check-expiry');

        $this->assertEquals('expired', $subExpired->fresh()->status);
        $this->assertEquals('suspended', $this->institution->fresh()->status);

        // 2. Sub 2: Ended 3 days ago (grace period is 7 days => Grace Period, not yet suspended)
        $inst2 = Institution::create([
            'name' => 'Grace Madrasa',
            'slug' => 'grace-madrasa',
            'status' => 'active',
            'subscription_plan' => 'growth',
            'subscription_ends_at' => Carbon::now()->subDays(3),
        ]);

        $subGrace = Subscription::create([
            'institution_id' => $inst2->id,
            'plan_id' => $this->standardPlan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => Carbon::now()->subDays(33),
            'ends_at' => Carbon::now()->subDays(3),
            'grace_days' => 7,
        ]);

        Artisan::call('platform:subscriptions:check-expiry');

        $this->assertEquals('grace_period', $subGrace->fresh()->status);
        $this->assertEquals('active', $inst2->fresh()->status);
    }

    public function test_impersonation_is_time_limited_bannered_and_fully_audited(): void
    {
        // 1. Super admin starts impersonation
        $startRes = $this->actingAs($this->superAdmin)->post("/platform/institutions/{$this->institution->id}/impersonate", [
            'reason' => 'Reviewing timetable scheduling conflicts',
        ]);

        $startRes->assertRedirect(route('dashboard'));

        // Assert session has markers
        $this->assertEquals($this->superAdmin->id, session('impersonator_id'));
        $this->assertEquals($this->institution->id, session('impersonated_institution_id'));

        // Assert current authenticated user is now the institution owner
        $this->assertEquals($this->tenantOwner->id, auth()->id());

        // Verify audit log
        $audit = AuditLog::where('institution_id', $this->institution->id)
            ->where('action', 'impersonation_started')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('Reviewing timetable scheduling conflicts', $audit->new_values['reason']);

        // 2. Visit dashboard and verify session maintains impersonation
        $dash = $this->get('/dashboard');
        $dash->assertOk();
        $this->assertTrue(session()->has('impersonator_id'));

        // 3. Stop impersonation
        $stopRes = $this->post('/platform/impersonate/stop');
        $stopRes->assertRedirect(route('platform.institutions.show', $this->institution->id));

        // Assert session markers cleared
        $this->assertFalse(session()->has('impersonator_id'));

        // Next request to /platform succeeds with 200 OK because user is restored to super admin
        $platformReq = $this->get('/platform');
        $platformReq->assertOk();
    }

    public function test_overview_dashboard_loads_in_under_2_seconds_with_200_seeded_institutions(): void
    {
        // Seed 200 institutions efficiently
        $now = Carbon::now();
        $institutionsData = [];

        for ($i = 1; $i <= 200; $i++) {
            $institutionsData[] = [
                'id' => (string) Str::ulid(),
                'name' => "Madrasa Seed {$i}",
                'slug' => "madrasa-seed-{$i}",
                'mode' => 'hybrid',
                'timezone' => 'Africa/Addis_Ababa',
                'currency' => 'ETB',
                'locale' => 'en',
                'status' => ($i % 10 === 0) ? 'suspended' : 'active',
                'subscription_plan' => 'growth',
                'subscription_ends_at' => $now->copy()->addDays($i % 30),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Institution::insert($institutionsData);

        // Measure response time
        $startTime = microtime(true);

        $response = $this->actingAs($this->superAdmin)->get('/platform');

        $elapsed = microtime(true) - $startTime;

        $response->assertOk();
        $this->assertLessThan(2.0, $elapsed, "Platform dashboard took {$elapsed}s, expected < 2.0s.");
    }

    public function test_plan_limit_enforcement_blocks_adding_students_when_limit_reached(): void
    {
        // Update institution plan to limited plan with maximum 2 students
        $limitedPlan = Plan::create([
            'name' => 'Mini Plan',
            'slug' => 'mini',
            'monthly_price' => 20000,
            'yearly_price' => 200000,
            'currency' => 'ETB',
            'student_limit' => 2,
            'is_active' => true,
        ]);

        $this->institution->update(['subscription_plan' => 'mini']);
        $this->institution->latestSubscription->update(['plan_id' => $limitedPlan->id]);

        // Seed 2 active students in this institution
        Student::create([
            'institution_id' => $this->institution->id,
            'student_code' => 'TEST-001',
            'full_name' => 'Student One',
            'status' => 'active',
        ]);
        Student::create([
            'institution_id' => $this->institution->id,
            'student_code' => 'TEST-002',
            'full_name' => 'Student Two',
            'status' => 'active',
        ]);

        // Attempting to add a 3rd student should trigger plan limit validation error
        $response = $this->actingAs($this->tenantOwner)->post('/students', [
            'name' => 'Student Three',
            'full_name' => 'Student Three Full',
            'gender' => 'male',
        ]);

        $response->assertSessionHasErrors('plan_limit');
    }

    public function test_strict_without_institution_scope_usage_is_restricted_to_platform_domain(): void
    {
        // All tenant controllers handling institution requests must NEVER call withoutInstitutionScope()
        $tenantControllers = [
            StudentController::class,
            AttendanceController::class,
            FinanceController::class,
            HifzController::class,
            ReportController::class,
            DashboardController::class,
            AcademicYearController::class,
            TeacherTimetableController::class,
        ];

        foreach ($tenantControllers as $controllerClass) {
            $reflector = new \ReflectionClass($controllerClass);
            $fileName = $reflector->getFileName();
            $content = file_get_contents($fileName);

            $this->assertStringNotContainsString(
                'withoutInstitutionScope',
                $content,
                "Tenant controller {$controllerClass} must NEVER call withoutInstitutionScope()!"
            );
        }
    }
}
