<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Hifz\Actions\RecordHifzEntryAction;
use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Hifz\Models\QuranSurah;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HifzValidationAndEntryTest extends TestCase
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
            'first_name' => 'Zayd',
            'last_name' => 'Ali',
            'full_name' => 'Zayd Ali',
            'date_of_birth' => '2015-05-10',
            'gender' => 'male',
            'admission_date' => '2024-09-01',
            'status' => 'active',
        ]);

        // Assign student to teacher
        $this->teacherUser->assignedStudents()->attach($this->student->id, [
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_rejects_ayah_range_where_start_ayah_is_greater_than_end_ayah(): void
    {
        $action = app(RecordHifzEntryAction::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The start ayah cannot be greater than the end ayah.');

        $action->execute([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 1, // Al-Fatihah (7 ayahs)
            'start_ayah' => 5,
            'end_ayah' => 3, // Invalid: start > end
        ]);
    }

    public function test_rejects_ayah_range_where_end_ayah_exceeds_total_ayahs_in_surah(): void
    {
        $action = app(RecordHifzEntryAction::class);

        // Surah 1 (Al-Fatihah) has exactly 7 ayahs
        $surah = QuranSurah::find(1);
        $this->assertEquals(7, $surah->total_ayahs);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds the total ayahs (7)');

        $action->execute([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 8, // Invalid: Al-Fatihah only has 7 ayahs
        ]);
    }

    public function test_rejects_start_ayah_less_than_one(): void
    {
        $action = app(RecordHifzEntryAction::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The start ayah must be at least 1.');

        $action->execute([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 1,
            'start_ayah' => 0, // Invalid: ayahs start at 1
            'end_ayah' => 5,
        ]);
    }

    public function test_database_check_constraint_rejects_invalid_range_on_raw_insert(): void
    {
        $this->expectException(QueryException::class);

        // Attempt raw SQL insert violating start_ayah <= end_ayah CHECK constraint
        DB::table('hifz_entries')->insert([
            'id' => (string) Str::ulid(),
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 1,
            'start_ayah' => 6,
            'end_ayah' => 2, // Fails check constraint start_ayah <= end_ayah
            'quality_rating' => 'good',
            'tajweed_rating' => 'good',
            'mistakes_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_valid_entry_stores_student_teacher_date_and_type_with_audit_trail(): void
    {
        $this->actingAs($this->teacherUser);

        $response = $this->postJson(route('hifz.entries.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-15',
            'type' => 'sabaq',
            'surah_number' => 114, // An-Nas (6 ayahs)
            'start_ayah' => 1,
            'end_ayah' => 6,
            'quality_rating' => 'excellent',
            'tajweed_rating' => 'excellent',
            'mistakes_count' => 0,
            'teacher_comment' => 'Masha Allah, perfect recitation',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('entry.type', 'sabaq');
        $response->assertJsonPath('entry.ayahs_count', 6);
        $response->assertJsonPath('entry.surah_name', 'An-Nas');

        $this->assertDatabaseHas('hifz_entries', [
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'type' => 'sabaq',
            'surah_number' => 114,
            'start_ayah' => 1,
            'end_ayah' => 6,
            'quality_rating' => 'excellent',
            'mistakes_count' => 0,
        ]);

        // Verify audit log exists for creation
        $this->assertDatabaseHas('audit_logs', [
            'institution_id' => $this->institution->id,
            'auditable_type' => HifzEntry::class,
            'action' => 'created',
        ]);
    }

    public function test_fast_entry_endpoint_returns_data_and_prefills_last_sabaq(): void
    {
        $this->actingAs($this->teacherUser);

        // Create an existing Sabaq entry
        HifzEntry::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-14',
            'type' => 'sabaq',
            'surah_number' => 2, // Al-Baqarah
            'start_ayah' => 1,
            'end_ayah' => 15,
            'quality_rating' => 'good',
            'tajweed_rating' => 'good',
            'mistakes_count' => 1,
        ]);

        $response = $this->getJson(route('hifz.fast-entry', ['student_id' => $this->student->id]));

        $response->assertOk();
        $response->assertJsonCount(114, 'surahs');
        $response->assertJsonPath('last_sabaq.surah_number', 2);
        $response->assertJsonPath('last_sabaq.end_ayah', 15);
        $response->assertJsonPath('last_sabaq.suggested_start_ayah', 16);
    }
}
