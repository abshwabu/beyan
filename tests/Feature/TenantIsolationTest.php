<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institutionA;

    protected Institution $institutionB;

    protected User $userA;

    protected User $userB;

    protected AcademicYear $yearA;

    protected AcademicYear $yearB;

    protected Term $termA;

    protected Term $termB;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->withoutTenantScope(function () {
            // Create Institution A
            $this->institutionA = Institution::factory()->create([
                'name' => 'Beyan Academy',
                'slug' => 'beyan-academy',
            ]);

            $this->userA = User::withoutInstitutionScope()->create([
                'institution_id' => $this->institutionA->id,
                'name' => 'User Institution A',
                'email' => 'user.a@beyan.edu.et',
                'password' => bcrypt('Password123!'),
                'role' => 'owner',
            ]);

            $this->yearA = AcademicYear::withoutInstitutionScope()->create([
                'institution_id' => $this->institutionA->id,
                'name' => 'Year A 2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2025-06-30',
                'is_current' => true,
            ]);

            $this->termA = Term::withoutInstitutionScope()->create([
                'institution_id' => $this->institutionA->id,
                'academic_year_id' => $this->yearA->id,
                'name' => 'Term A1',
                'start_date' => '2024-09-01',
                'end_date' => '2024-12-31',
                'is_current' => true,
            ]);

            // Create Institution B
            $this->institutionB = Institution::factory()->create([
                'name' => 'Al-Huda Academy',
                'slug' => 'alhuda-academy',
            ]);

            $this->userB = User::withoutInstitutionScope()->create([
                'institution_id' => $this->institutionB->id,
                'name' => 'User Institution B',
                'email' => 'user.b@alhuda.edu.et',
                'password' => bcrypt('Password123!'),
                'role' => 'owner',
            ]);

            $this->yearB = AcademicYear::withoutInstitutionScope()->create([
                'institution_id' => $this->institutionB->id,
                'name' => 'Year B 1446 AH',
                'start_date' => '2024-07-01',
                'end_date' => '2025-06-25',
                'is_current' => true,
            ]);

            $this->termB = Term::withoutInstitutionScope()->create([
                'institution_id' => $this->institutionB->id,
                'academic_year_id' => $this->yearB->id,
                'name' => 'Term B1',
                'start_date' => '2024-07-01',
                'end_date' => '2024-11-30',
                'is_current' => true,
            ]);
        });
    }

    public function test_user_a_cannot_read_institution_b_records_via_list_route(): void
    {
        $response = $this->actingAs($this->userA)
            ->getJson(route('academic-years.index'));

        $response->assertOk();

        $data = $response->json();
        $ids = array_column($data, 'id');

        $this->assertContains($this->yearA->id, $ids);
        $this->assertNotContains($this->yearB->id, $ids, 'User A must NEVER see Institution B records in list results.');
    }

    public function test_user_a_cannot_read_institution_b_record_via_direct_show_route(): void
    {
        $response = $this->actingAs($this->userA)
            ->getJson(route('academic-years.show', ['id' => $this->yearB->id]));

        $response->assertNotFound();
    }

    public function test_user_a_cannot_update_institution_b_records(): void
    {
        $response = $this->actingAs($this->userA)
            ->putJson(route('academic-years.update', ['id' => $this->yearB->id]), [
                'name' => 'Hacked Name Attempt',
            ]);

        $response->assertNotFound();

        // Verify Institution B record remains intact
        $this->assertDatabaseHas('academic_years', [
            'id' => $this->yearB->id,
            'name' => 'Year B 1446 AH',
            'institution_id' => $this->institutionB->id,
        ]);
    }

    public function test_user_a_cannot_delete_institution_b_records(): void
    {
        $response = $this->actingAs($this->userA)
            ->deleteJson(route('academic-years.destroy', ['id' => $this->yearB->id]));

        $response->assertNotFound();

        // Verify Institution B record was not deleted
        $this->assertDatabaseHas('academic_years', [
            'id' => $this->yearB->id,
        ]);
    }

    public function test_user_a_cannot_inject_institution_b_id_when_creating_record(): void
    {
        // User A attempts to create a record attributed to Institution B
        $this->actingAs($this->userA);

        try {
            AcademicYear::create([
                'institution_id' => $this->institutionB->id,
                'name' => 'Unauthorized Cross-Tenant Injection',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
            ]);
            $this->fail('Expected DomainException was not thrown when attempting cross-tenant injection.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Cross-tenant data manipulation is prohibited', $e->getMessage());
        }

        $this->assertDatabaseMissing('academic_years', [
            'name' => 'Unauthorized Cross-Tenant Injection',
        ]);
    }

    public function test_auto_fill_assigns_current_tenant_id_on_model_creation(): void
    {
        $this->actingAs($this->userA);

        // When creating a model without passing institution_id
        $newYear = AcademicYear::create([
            'name' => 'Auto-Filled Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->assertEquals($this->institutionA->id, $newYear->institution_id);
        $this->assertDatabaseHas('academic_years', [
            'id' => $newYear->id,
            'institution_id' => $this->institutionA->id,
            'name' => 'Auto-Filled Year',
        ]);
    }

    public function test_eloquent_queries_are_scoped_to_current_tenant(): void
    {
        $this->actingAs($this->userA);

        $visibleYears = AcademicYear::all();
        $this->assertTrue($visibleYears->contains('id', $this->yearA->id));
        $this->assertFalse($visibleYears->contains('id', $this->yearB->id));

        $visibleTerms = Term::all();
        $this->assertTrue($visibleTerms->contains('id', $this->termA->id));
        $this->assertFalse($visibleTerms->contains('id', $this->termB->id));

        $visibleUsers = User::all();
        $this->assertTrue($visibleUsers->contains('id', $this->userA->id));
        $this->assertFalse($visibleUsers->contains('id', $this->userB->id));
    }

    public function test_suspended_institution_user_is_blocked(): void
    {
        $this->institutionA->update(['status' => 'suspended']);

        $response = $this->actingAs($this->userA)
            ->getJson(route('academic-years.index'));

        $response->assertStatus(403);
    }
}
