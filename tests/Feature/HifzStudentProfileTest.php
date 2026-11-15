<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\HifzTarget;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HifzStudentProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $teacherUser;

    protected Teacher $teacher;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuranReferenceSeeder::class);

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

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();
        $matrix->seedInstitutionRoles($this->institution->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->teacherUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustaz Abdurrahman',
            'email' => 'ustaz@beyan.edu.et',
            'role' => 'teacher',
            'password' => bcrypt('password'),
        ]);
        $this->teacherUser->assignRole('Teacher');

        $this->teacher = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Ustaz Abdurrahman',
            'email' => 'ustaz@beyan.edu.et',
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Fatima',
            'last_name' => 'Hassan',
            'full_name' => 'Fatima Hassan',
            'date_of_birth' => '2014-03-12',
            'gender' => 'female',
            'admission_date' => '2024-09-01',
            'status' => 'active',
        ]);

        $this->teacherUser->assignedStudents()->attach($this->student->id, [
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_profile_shows_sabaq_sabqi_and_manzil_separately_without_single_percentage_reduction(): void
    {
        $this->actingAs($this->teacherUser);

        // 1. Sabaq Entry: Surah 114 (An-Nas), Ayahs 1 to 6
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 114,
            'start_ayah' => 1,
            'end_ayah' => 6,
            'quality_rating' => 'excellent',
            'tajweed_rating' => 'excellent',
            'mistakes_count' => 0,
        ]);

        // 2. Sabqi Entry: Surah 113 (Al-Falaq), Ayahs 1 to 5
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabqi',
            'surah_number' => 113,
            'start_ayah' => 1,
            'end_ayah' => 5,
            'quality_rating' => 'very_good',
            'tajweed_rating' => 'good',
            'mistakes_count' => 1,
        ]);

        // 3. Manzil Entry: Surah 1 (Al-Fatihah), Ayahs 1 to 7
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-14',
            'type' => 'manzil',
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 7,
            'quality_rating' => 'good',
            'tajweed_rating' => 'good',
            'mistakes_count' => 0,
        ]);

        $response = $this->getJson(route('hifz.student.profile', $this->student->id));

        $response->assertOk();

        // Must NOT have a single collapsed progress percentage
        $response->assertJsonMissingPath('overall_percentage');
        $response->assertJsonMissingPath('progress_percentage');

        // MUST have three distinct pillars
        $response->assertJsonStructure([
            'student' => ['id', 'name', 'student_code'],
            'sabaq' => [
                'type_label',
                'current_position' => ['surah_number', 'surah_name', 'start_ayah', 'end_ayah', 'date'],
                'total_ayahs_memorized',
                'completed_surahs_count',
            ],
            'sabqi' => [
                'type_label',
                'total_revisions_count',
                'total_ayahs_reviewed',
                'last_30_days_ayahs',
            ],
            'manzil' => [
                'type_label',
                'total_revisions_count',
                'total_ayahs_reviewed',
                'covered_juz_numbers',
            ],
            'targets',
            'certifications',
            'timeline',
        ]);

        // Verify values
        $response->assertJsonPath('sabaq.current_position.surah_name', 'An-Nas');
        $response->assertJsonPath('sabaq.current_position.end_ayah', 6);
        $response->assertJsonPath('sabaq.total_ayahs_memorized', 6);
        $response->assertJsonPath('sabaq.completed_surahs_count', 1);

        $response->assertJsonPath('sabqi.total_ayahs_reviewed', 5);
        $response->assertJsonPath('sabqi.last_30_days_ayahs', 5);

        $response->assertJsonPath('manzil.total_ayahs_reviewed', 7);
        $response->assertJsonPath('manzil.total_revisions_count', 1);
        $this->assertContains(1, $response->json('manzil.covered_juz_numbers'));
    }

    public function test_daily_and_weekly_targets_calculated_accurately(): void
    {
        $this->actingAs($this->teacherUser);

        // Set daily Sabaq target: 5 ayahs
        HifzTarget::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'period' => 'daily',
            'target_type' => 'sabaq_ayahs',
            'target_value' => 5,
            'is_active' => true,
        ]);

        // Set weekly Sabqi target: 20 ayahs
        HifzTarget::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'period' => 'weekly',
            'target_type' => 'sabqi_ayahs',
            'target_value' => 20,
            'is_active' => true,
        ]);

        // Record 5 Sabaq ayahs today (100% achieved)
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'type' => 'sabaq',
            'surah_number' => 112, // Al-Ikhlas (4 ayahs)
            'start_ayah' => 1,
            'end_ayah' => 4,
            'quality_rating' => 'excellent',
        ]);
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'type' => 'sabaq',
            'surah_number' => 113,
            'start_ayah' => 1,
            'end_ayah' => 2, // 2 ayahs -> total 6 ayahs today
            'quality_rating' => 'good',
        ]);

        // Record 15 Sabqi ayahs this week (75% achieved)
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'type' => 'sabqi',
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 7, // 7 ayahs
            'quality_rating' => 'good',
        ]);
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => now()->startOfWeek()->toDateString(),
            'type' => 'sabqi',
            'surah_number' => 2,
            'start_ayah' => 1,
            'end_ayah' => 8, // 8 ayahs -> total 15 ayahs
            'quality_rating' => 'good',
        ]);

        $response = $this->getJson(route('hifz.student.profile', $this->student->id));
        $response->assertOk();

        $targets = collect($response->json('targets'));

        $dailyTarget = $targets->firstWhere('period', 'daily');
        $this->assertNotNull($dailyTarget);
        $this->assertEquals(6, $dailyTarget['achieved_value']);
        $this->assertEquals(5, $dailyTarget['target_value']);
        $this->assertTrue($dailyTarget['is_completed']);
        $this->assertEquals(100, $dailyTarget['progress_percentage']);

        $weeklyTarget = $targets->firstWhere('period', 'weekly');
        $this->assertNotNull($weeklyTarget);
        $this->assertEquals(15, $weeklyTarget['achieved_value']);
        $this->assertEquals(20, $weeklyTarget['target_value']);
        $this->assertFalse($weeklyTarget['is_completed']);
        $this->assertEquals(75, $weeklyTarget['progress_percentage']);
    }

    public function test_hifz_completion_and_ijazah_certification_records(): void
    {
        $this->actingAs($this->teacherUser);

        $response = $this->postJson(route('hifz.student.certifications.store', $this->student->id), [
            'type' => 'ijazah',
            'title' => 'Ijazah in Hafs \'an \'Asim with Sanad',
            'riwayah' => 'Hafs \'an \'Asim via Shatibiyyah',
            'sanad_chain' => 'Connected golden chain through Imam Shatibi back to the Prophet (PBUH)',
            'completion_date' => '2026-11-15',
            'certificate_number' => 'IJZ-2026-0042',
            'grade' => 'Mumtaz Murtada',
            'notes' => 'Completed full recitation from memory with flawless tajweed.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('certification.type', 'ijazah');
        $response->assertJsonPath('certification.certificate_number', 'IJZ-2026-0042');

        $this->assertDatabaseHas('hifz_certifications', [
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'type' => 'ijazah',
            'certificate_number' => 'IJZ-2026-0042',
        ]);

        // Verify certification is visible on student profile
        $profileResponse = $this->getJson(route('hifz.student.profile', $this->student->id));
        $profileResponse->assertOk();
        $profileResponse->assertJsonCount(1, 'certifications');
        $profileResponse->assertJsonPath('certifications.0.certificate_number', 'IJZ-2026-0042');
    }

    public function test_progress_comparison_across_chosen_periods(): void
    {
        $this->actingAs($this->teacherUser);

        // Period A: 2026-10-01 to 2026-10-15
        // Sabaq: 10 ayahs, 2 mistakes
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-10-05',
            'type' => 'sabaq',
            'surah_number' => 114,
            'start_ayah' => 1,
            'end_ayah' => 6,
            'quality_rating' => 'good',
            'mistakes_count' => 1,
        ]);
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-10-10',
            'type' => 'sabaq',
            'surah_number' => 112,
            'start_ayah' => 1,
            'end_ayah' => 4,
            'quality_rating' => 'good',
            'mistakes_count' => 1,
        ]);

        // Period B: 2026-11-01 to 2026-11-15
        // Sabaq: 25 ayahs, 0 mistakes
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-05',
            'type' => 'sabaq',
            'surah_number' => 2,
            'start_ayah' => 1,
            'end_ayah' => 25,
            'quality_rating' => 'excellent',
            'mistakes_count' => 0,
        ]);

        $response = $this->getJson(route('hifz.student.compare', [
            'student' => $this->student->id,
            'start_date_a' => '2026-10-01',
            'end_date_a' => '2026-10-15',
            'start_date_b' => '2026-11-01',
            'end_date_b' => '2026-11-15',
        ]));

        $response->assertOk();
        $response->assertJsonPath('period_a.metrics.sabaq_ayahs', 10);
        $response->assertJsonPath('period_a.metrics.total_mistakes', 2);

        $response->assertJsonPath('period_b.metrics.sabaq_ayahs', 25);
        $response->assertJsonPath('period_b.metrics.total_mistakes', 0);

        // Difference: Period B - Period A (+15 Sabaq ayahs, -2 mistakes)
        $response->assertJsonPath('comparison.sabaq_ayahs_diff', 15);
        $response->assertJsonPath('comparison.total_mistakes_diff', -2);
    }

    public function test_tenant_isolation_prevents_user_in_institution_a_from_accessing_student_in_institution_b(): void
    {
        $tenantManager = app(TenantManager::class);

        // Create second institution and student outside tenant scope
        $studentB = $tenantManager->withoutTenantScope(function () {
            $institutionB = Institution::create([
                'name' => 'Al-Huda Academy',
                'slug' => 'al-huda',
                'contact' => ['email' => 'admin@alhuda.edu.et'],
                'mode' => 'online',
                'timezone' => 'Africa/Addis_Ababa',
                'currency' => 'ETB',
                'locale' => 'am',
                'status' => 'active',
                'subscription_plan' => 'standard',
            ]);

            return Student::create([
                'institution_id' => $institutionB->id,
                'first_name' => 'Bilal',
                'last_name' => 'Umar',
                'full_name' => 'Bilal Umar',
                'date_of_birth' => '2013-07-20',
                'gender' => 'male',
                'admission_date' => '2024-09-01',
                'status' => 'active',
            ]);
        });

        // Ensure tenant scope is set back to Institution A
        $tenantManager->setTenant($this->institution);

        // Attempt as teacher in Institution A to view Student in Institution B
        $this->actingAs($this->teacherUser);

        $response = $this->getJson(route('hifz.student.profile', $studentB->id));
        $this->assertTrue(in_array($response->status(), [403, 404], true));

        // Attempt to record an entry for Student in Institution B
        $postResponse = $this->postJson(route('hifz.entries.store'), [
            'student_id' => $studentB->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 7,
        ]);
        $this->assertTrue(in_array($postResponse->status(), [403, 404], true));
    }
}
