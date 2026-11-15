<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Academics\Models\Term;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Finance\Models\Payment;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MvpVerticalSliceEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $adminUser;

    protected User $teacherUser;

    protected Teacher $teacherProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuranReferenceSeeder::class);

        $this->institution = Institution::create([
            'name' => 'Beyan Academy Pilot',
            'slug' => 'beyan-pilot',
            'contact' => ['email' => 'pilot@beyan.edu.et', 'phone' => '+251911223344'],
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

        // Admin User
        $this->adminUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Admin Ustadh Harun',
            'email' => 'harun@beyan.edu.et',
            'role' => 'admin',
            'password' => bcrypt('secret123'),
        ]);
        $this->adminUser->assignRole('Admin');

        // Teacher User
        $this->teacherUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustadh Zayd',
            'email' => 'zayd@beyan.edu.et',
            'role' => 'teacher',
            'password' => bcrypt('secret123'),
        ]);
        $this->teacherUser->assignRole('Teacher');

        $this->teacherProfile = Teacher::create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Ustadh Zayd',
            'email' => 'zayd@beyan.edu.et',
            'status' => 'active',
            'specialization' => 'Hifz and Tajweed',
        ]);
    }

    public function test_complete_vertical_slice_from_registration_to_reports(): void
    {
        // -------------------------------------------------------------
        // STEP 1: Register Student via HTTP POST
        // -------------------------------------------------------------
        $this->actingAs($this->adminUser);

        $studentData = [
            'full_name' => 'Zubair Abdullah',
            'gender' => 'male',
            'date_of_birth' => '2015-04-10',
            'admission_date' => '2026-09-01',
            'contact' => [
                'phone' => '+251911556677',
                'email' => 'parent@zubair.et',
                'address' => 'Bole Subcity, Addis Ababa',
            ],
            'guardians' => [
                [
                    'full_name' => 'Abdullah Zubair Sr.',
                    'phone' => '+251911556677',
                    'relationship_type' => 'Father',
                    'is_primary' => true,
                ],
            ],
        ];

        $regResponse = $this->postJson(route('students.store'), $studentData);
        $regResponse->assertStatus(201);
        $studentId = $regResponse->json('id');
        $studentCode = $regResponse->json('student_code');

        $this->assertNotEmpty($studentCode);
        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'institution_id' => $this->institution->id,
            'full_name' => 'Zubair Abdullah',
            'status' => 'active',
        ]);

        $student = Student::findOrFail($studentId);

        // -------------------------------------------------------------
        // STEP 2: Assign Teacher to Student
        // -------------------------------------------------------------
        $this->teacherUser->assignedStudents()->attach($student->id, [
            'institution_id' => $this->institution->id,
        ]);

        $this->assertDatabaseHas('student_teacher', [
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacherUser->id,
            'student_id' => $student->id,
        ]);

        // -------------------------------------------------------------
        // STEP 3: Setup Academics & Class Group
        // -------------------------------------------------------------
        $academicYear = AcademicYear::create([
            'institution_id' => $this->institution->id,
            'name' => 'Academic Year 2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ]);

        $term = Term::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Hifz Intensive',
            'code' => 'HIFZ-INT',
            'type' => 'hifz',
            'status' => 'active',
        ]);

        $classGroup = ClassGroup::create([
            'institution_id' => $this->institution->id,
            'course_id' => $course->id,
            'name' => 'Hifz Circle Al-Firdaws',
            'type' => 'physical',
            'capacity' => 12,
            'status' => 'active',
        ]);

        // Enroll Student in Class Group
        ClassEnrollment::create([
            'institution_id' => $this->institution->id,
            'student_id' => $student->id,
            'class_group_id' => $classGroup->id,
            'start_date' => '2026-09-01',
            'status' => 'active',
        ]);

        // -------------------------------------------------------------
        // STEP 4: Generate Class Session for Today
        // -------------------------------------------------------------
        $today = now()->toDateString();
        $session = ClassSession::create([
            'institution_id' => $this->institution->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $this->teacherProfile->id,
            'title' => 'Morning Hifz Recitation',
            'scheduled_date' => $today,
            'start_time_utc' => "{$today} 06:30:00",
            'end_time_utc' => "{$today} 08:00:00",
            'status' => 'scheduled',
        ]);

        // -------------------------------------------------------------
        // STEP 5: Teacher Records Attendance (Marking Student Present)
        // -------------------------------------------------------------
        $this->actingAs($this->teacherUser);

        $attendancePayload = [
            'records' => [
                [
                    'student_id' => $student->id,
                    'status' => 'present',
                    'note' => 'Punctual with great focus',
                ],
            ],
            'teacher_check_in_status' => 'present',
            'completed' => true,
        ];

        $attResponse = $this->postJson(
            route('attendance.sessions.save', ['sessionId' => $session->id]),
            $attendancePayload
        );
        $attResponse->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'institution_id' => $this->institution->id,
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);

        $session->refresh();
        $this->assertNotNull($session->teacher_checked_in_at);
        $this->assertEquals('present', $session->teacher_attendance_status);

        // -------------------------------------------------------------
        // STEP 6: Teacher Records Qur'an Hifz Progress (Sabaq)
        // -------------------------------------------------------------
        $hifzPayload = [
            'student_id' => $student->id,
            'class_session_id' => $session->id,
            'date' => $today,
            'type' => 'sabaq',
            'surah_number' => 1, // Al-Fatihah
            'start_ayah' => 1,
            'end_ayah' => 7,
            'quality_rating' => 'excellent',
            'tajweed_rating' => 'excellent',
            'mistakes_count' => 0,
            'teacher_comment' => 'Completed Surah Al-Fatihah flawlessly with full tajweed rules.',
        ];

        $hifzResponse = $this->postJson(route('hifz.entries.store'), $hifzPayload);
        $hifzResponse->assertStatus(201);

        $this->assertDatabaseHas('hifz_entries', [
            'institution_id' => $this->institution->id,
            'student_id' => $student->id,
            'type' => 'sabaq',
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 7,
            'mistakes_count' => 0,
        ]);

        // -------------------------------------------------------------
        // STEP 7: Finance - Create Invoice and Record Payment
        // -------------------------------------------------------------
        $this->actingAs($this->adminUser);

        // 1,200.00 ETB invoice (120,000 minor units)
        $invoiceResponse = $this->postJson(route('finance.invoices.store'), [
            'student_id' => $student->id,
            'invoice_number' => 'INV-2026-V001',
            'title' => 'Term 1 Tuition Fee',
            'amount' => 1200,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
        $invoiceResponse->assertStatus(201);
        $invoiceId = $invoiceResponse->json('id');

        // Record full payment of 1,200.00 ETB
        $paymentResponse = $this->postJson(route('finance.invoices.payments.store', ['id' => $invoiceId]), [
            'amount' => 1200,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CBE-TX-99887766',
            'idempotency_key' => Str::uuid()->toString(),
            'notes' => 'Direct CBE transfer verified by accountant',
        ]);
        $paymentResponse->assertStatus(201);
        $this->assertEquals(0, $paymentResponse->json('balance_due'));
        $this->assertEquals('paid', $paymentResponse->json('status'));

        $this->assertDatabaseHas('fee_invoices', [
            'id' => $invoiceId,
            'balance_due' => 0,
            'status' => 'paid',
        ]);

        // -------------------------------------------------------------
        // STEP 8: View & Export Reports
        // -------------------------------------------------------------
        // 1. Attendance Report
        $attReportResponse = $this->getJson(route('reports.show', ['type' => 'attendance']));
        $attReportResponse->assertOk();
        $attReportData = $attReportResponse->json();
        $this->assertEquals('Attendance Performance Report', $attReportData['title']);
        $this->assertCount(1, $attReportData['rows']);
        $this->assertEquals('Zubair Abdullah', $attReportData['rows'][0]['student_name']);
        $this->assertEquals('100%', $attReportData['rows'][0]['percentage']);

        // 2. Hifz Progress Report
        $hifzReportResponse = $this->getJson(route('reports.show', ['type' => 'hifz']));
        $hifzReportResponse->assertOk();
        $hifzReportData = $hifzReportResponse->json();
        $this->assertEquals('Qur\'an & Hifz Progress Report', $hifzReportData['title']);
        $this->assertCount(1, $hifzReportData['rows']);
        $this->assertEquals('Zubair Abdullah', $hifzReportData['rows'][0]['student_name']);
        $this->assertEquals('Sabaq', $hifzReportData['rows'][0]['type']);
        $this->assertEquals('Al-Fatihah', $hifzReportData['rows'][0]['surah']);

        // 3. Payments Ledger Report
        $payReportResponse = $this->getJson(route('reports.show', ['type' => 'payments']));
        $payReportResponse->assertOk();
        $payReportData = $payReportResponse->json();
        $this->assertEquals('Tuition Payments Report', $payReportData['title']);
        $this->assertCount(1, $payReportData['rows']);
        $this->assertEquals('Zubair Abdullah', $payReportData['rows'][0]['student_name']);
        $this->assertEquals('1,200.00 ETB', $payReportData['rows'][0]['amount']);

        // 4. Export CSV with UTF-8 BOM
        $csvResponse = $this->get(route('reports.export', ['type' => 'hifz', 'format' => 'csv']));
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvContent);
        $this->assertStringContainsString('Zubair Abdullah', $csvContent);
        $this->assertStringContainsString('Al-Fatihah', $csvContent);

        // 5. Export Printable View
        $pdfResponse = $this->get(route('reports.export', ['type' => 'attendance', 'format' => 'pdf']));
        $pdfResponse->assertOk();
        $pdfResponse->assertSee('Beyan Academy Pilot');
        $pdfResponse->assertSee('Attendance Performance Report');
        $pdfResponse->assertSee('Zubair Abdullah');
    }
}
