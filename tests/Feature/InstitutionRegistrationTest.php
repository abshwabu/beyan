<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstitutionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register-institution');

        $response->assertStatus(200);
    }

    public function test_registering_an_institution_creates_an_isolated_workspace(): void
    {
        $payload = [
            'institution_name' => 'Beyan Pilot Academy',
            'institution_slug' => 'beyan-pilot-academy',
            'mode' => 'hybrid',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'phone' => '+251 91 100 2000',
            'email' => 'contact@beyanpilot.edu.et',
            'address' => 'Addis Ababa, Ethiopia',
            'owner_name' => 'Ustadh Omar',
            'owner_email' => 'omar@beyanpilot.edu.et',
            'owner_password' => 'SecurePass123!',
            'owner_password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->post('/register-institution', $payload);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // 1. Institution assertions
        $institution = Institution::where('slug', 'beyan-pilot-academy')->first();
        $this->assertNotNull($institution);
        $this->assertEquals('Beyan Pilot Academy', $institution->name);
        $this->assertEquals('hybrid', $institution->mode);
        $this->assertEquals('Africa/Addis_Ababa', $institution->timezone);
        $this->assertEquals('ETB', $institution->currency);
        $this->assertEquals('am', $institution->locale);
        $this->assertEquals('active', $institution->status);

        // Verify ULID primary key
        $this->assertTrue(Str::isUlid($institution->id), 'Institution ID must be a valid ULID string.');

        // 2. Owner User assertions
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $user = $tenantManager->withoutTenantScope(function () use ($institution) {
            return User::where('institution_id', $institution->id)->first();
        });

        $this->assertNotNull($user);
        $this->assertEquals('Ustadh Omar', $user->name);
        $this->assertEquals('omar@beyanpilot.edu.et', $user->email);
        $this->assertEquals('owner', $user->role);
        $this->assertEquals($institution->id, $user->institution_id);
        $this->assertTrue(Str::isUlid($user->id), 'User ID must be a valid ULID string.');

        // 3. Initial Academic Year & Term assertions
        $academicYear = $tenantManager->withoutTenantScope(function () use ($institution) {
            return AcademicYear::where('institution_id', $institution->id)->first();
        });
        $this->assertNotNull($academicYear);
        $this->assertTrue($academicYear->is_current);
        $this->assertTrue(Str::isUlid($academicYear->id), 'AcademicYear ID must be a valid ULID string.');

        $term = $tenantManager->withoutTenantScope(function () use ($institution) {
            return Term::where('institution_id', $institution->id)->first();
        });
        $this->assertNotNull($term);
        $this->assertTrue($term->is_current);
        $this->assertEquals($academicYear->id, $term->academic_year_id);
        $this->assertTrue(Str::isUlid($term->id), 'Term ID must be a valid ULID string.');

        // 4. Isolated workspace verified via authenticated session
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertOk();
    }

    public function test_registration_validation_fails_on_duplicate_slug_or_mismatched_password(): void
    {
        Institution::factory()->create(['slug' => 'existing-madrasa']);

        $response = $this->post('/register-institution', [
            'institution_name' => 'Existing Madrasa',
            'institution_slug' => 'existing-madrasa',
            'owner_name' => 'Admin User',
            'owner_email' => 'admin@existing.com',
            'owner_password' => 'Password123!',
            'owner_password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertSessionHasErrors(['institution_slug', 'owner_password']);
    }
}
