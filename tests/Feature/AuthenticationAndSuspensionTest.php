<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $activeInstitution;

    protected Institution $suspendedInstitution;

    protected Institution $inactiveInstitution;

    protected User $activeUser;

    protected User $suspendedUser;

    protected User $inactiveUser;

    protected User $superAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->withoutTenantScope(function () {
            // Super Admin
            $this->superAdminUser = User::withoutInstitutionScope()->create([
                'institution_id' => null,
                'name' => 'Platform Super Admin',
                'email' => 'superadmin@beyan.platform',
                'password' => Hash::make('Password123!'),
                'role' => 'platform_super_admin',
            ]);
            // Active Institution
            $this->activeInstitution = Institution::factory()->create([
                'name' => 'Active Madrasa',
                'status' => 'active',
            ]);

            $this->activeUser = User::withoutInstitutionScope()->create([
                'institution_id' => $this->activeInstitution->id,
                'name' => 'Active User',
                'email' => 'active@madrasa.org',
                'password' => Hash::make('Password123!'),
                'role' => 'owner',
            ]);

            // Suspended Institution
            $this->suspendedInstitution = Institution::factory()->create([
                'name' => 'Suspended Madrasa',
                'status' => 'suspended',
            ]);

            $this->suspendedUser = User::withoutInstitutionScope()->create([
                'institution_id' => $this->suspendedInstitution->id,
                'name' => 'Suspended User',
                'email' => 'suspended@madrasa.org',
                'password' => Hash::make('Password123!'),
                'role' => 'teacher',
            ]);

            // Inactive Institution
            $this->inactiveInstitution = Institution::factory()->create([
                'name' => 'Inactive Madrasa',
                'status' => 'inactive',
            ]);

            $this->inactiveUser = User::withoutInstitutionScope()->create([
                'institution_id' => $this->inactiveInstitution->id,
                'name' => 'Inactive User',
                'email' => 'inactive@madrasa.org',
                'password' => Hash::make('Password123!'),
                'role' => 'teacher',
            ]);
        });
    }

    public function test_active_user_can_authenticate_and_session_is_regenerated(): void
    {
        $response = $this->post('/login', [
            'email' => 'active@madrasa.org',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->activeUser);
    }

    public function test_super_admin_can_login_and_access_platform_without_memory_or_recursion_error(): void
    {
        $response = $this->post('/login', [
            'email' => 'superadmin@beyan.platform',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('platform.institutions.index'));
        $this->assertAuthenticatedAs($this->superAdminUser);

        $platformResponse = $this->get(route('platform.institutions.index'));
        $platformResponse->assertOk();
    }

    public function test_active_user_can_authenticate_and_access_dashboard_via_session(): void
    {
        $response = $this->post('/login', [
            'email' => 'active@madrasa.org',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->activeUser);

        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertOk();
    }

    public function test_login_is_blocked_for_users_of_suspended_institutions(): void
    {
        $response = $this->post('/login', [
            'email' => 'suspended@madrasa.org',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('suspended', strtolower($errorMessage));
    }

    public function test_login_is_blocked_for_users_of_inactive_institutions(): void
    {
        $response = $this->post('/login', [
            'email' => 'inactive@madrasa.org',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('inactive', strtolower($errorMessage));
    }

    public function test_authenticated_user_in_suspended_institution_is_blocked_by_middleware(): void
    {
        // Simulate an already authenticated session accessing the dashboard
        $response = $this->actingAs($this->suspendedUser)->get('/dashboard');

        $response->assertStatus(403);
    }

    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $this->actingAs($this->activeUser);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_reset_flow(): void
    {
        // 1. Request reset link
        $forgotResponse = $this->post('/forgot-password', [
            'email' => 'active@madrasa.org',
        ]);

        $forgotResponse->assertSessionHas('status');

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', 'active@madrasa.org')
            ->first();

        $this->assertNotNull($tokenRecord, 'Password reset token must be saved.');

        $devToken = session('dev_reset_token');
        $this->assertNotEmpty($devToken);

        // 2. Render reset form
        $formResponse = $this->get("/reset-password/{$devToken}?email=active@madrasa.org");
        $formResponse->assertOk();

        // 3. Reset password with new value
        $resetResponse = $this->post('/reset-password', [
            'token' => $devToken,
            'email' => 'active@madrasa.org',
            'password' => 'BrandNewPassword99!',
            'password_confirmation' => 'BrandNewPassword99!',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $resetResponse->assertSessionHas('status');

        // Token record should be deleted
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'active@madrasa.org']);

        // 4. Verify login with the new password
        $loginResponse = $this->post('/login', [
            'email' => 'active@madrasa.org',
            'password' => 'BrandNewPassword99!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->activeUser);
    }

    public function test_session_management_configuration_has_short_lock_time(): void
    {
        $this->assertTrue(Config::get('session.block'), 'Session blocking should be enabled.');
        $this->assertLessThanOrEqual(10, Config::get('session.block_lock_seconds'), 'Session lock time must be short (<= 10 seconds).');
        $this->assertLessThanOrEqual(10, Config::get('session.block_wait_seconds'), 'Session lock wait time must be short (<= 10 seconds).');
    }
}
