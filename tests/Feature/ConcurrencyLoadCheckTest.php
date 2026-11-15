<?php

namespace Tests\Feature;

use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSession;
use App\Domain\Academics\Models\Course;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyLoadCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_20_concurrent_teachers_saving_attendance_and_hifz_while_admin_queries_reports(): void
    {
        $tempDb = database_path('test_concurrency_load_'.uniqid().'.sqlite');
        touch($tempDb);

        // Configure a dedicated physical SQLite connection
        config([
            'database.connections.concurrency_load_test' => [
                'driver' => 'sqlite',
                'database' => $tempDb,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 15000,
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
                'transaction_mode' => 'IMMEDIATE',
            ],
        ]);

        // Run migrations on the physical file
        Artisan::call('migrate', [
            '--database' => 'concurrency_load_test',
            '--path' => 'database/migrations',
        ]);

        // Seed Quran reference tables into this connection
        DB::purge('concurrency_load_test');
        $oldDefault = DB::getDefaultConnection();
        DB::setDefaultConnection('concurrency_load_test');

        $this->seed(QuranReferenceSeeder::class);

        // 1. Create Institution
        $institution = Institution::create([
            'name' => 'Beyan High Concurrency Academy',
            'slug' => 'beyan-concurrency',
            'contact' => ['email' => 'concurrency@beyan.edu.et'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'enterprise',
        ]);

        app(TenantManager::class)->setTenant($institution);

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();
        $matrix->seedInstitutionRoles($institution->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($institution->id);

        // 2. Create Course and Class Group
        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => 'Advanced Hifz Program',
            'code' => 'HIFZ-ADV',
            'type' => 'hifz',
            'status' => 'active',
        ]);

        $classGroup = ClassGroup::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'name' => 'Main Memorization Hall',
            'type' => 'physical',
            'capacity' => 50,
            'status' => 'active',
        ]);

        $today = now()->toDateString();
        $classSession = ClassSession::create([
            'institution_id' => $institution->id,
            'class_group_id' => $classGroup->id,
            'title' => 'Morning Halaqah Session',
            'scheduled_date' => $today,
            'start_time_utc' => "{$today} 06:00:00",
            'end_time_utc' => "{$today} 08:00:00",
            'status' => 'scheduled',
        ]);

        // 3. Create 20 Teachers and 20 Students
        $workersData = [];
        $concurrencyCount = 20;

        for ($i = 1; $i <= $concurrencyCount; $i++) {
            $user = User::create([
                'institution_id' => $institution->id,
                'name' => "Ustadh {$i}",
                'email' => "ustadh{$i}@beyan.edu.et",
                'role' => 'teacher',
                'password' => bcrypt('secret123'),
            ]);
            $user->assignRole('Teacher');

            $teacher = Teacher::create([
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'full_name' => "Ustadh {$i}",
                'email' => "ustadh{$i}@beyan.edu.et",
                'status' => 'active',
            ]);

            $student = Student::create([
                'institution_id' => $institution->id,
                'first_name' => "Talib{$i}",
                'last_name' => 'Beyan',
                'full_name' => "Talib{$i} Beyan",
                'student_code' => sprintf('BYN-2026-%04d', $i),
                'admission_date' => '2026-01-01',
                'status' => 'active',
            ]);

            $user->assignedStudents()->attach($student->id, [
                'institution_id' => $institution->id,
            ]);

            ClassEnrollment::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'class_group_id' => $classGroup->id,
                'start_date' => '2026-01-01',
                'status' => 'active',
            ]);

            $session = ClassSession::create([
                'institution_id' => $institution->id,
                'class_group_id' => $classGroup->id,
                'teacher_id' => $teacher->id,
                'title' => "Halaqah Session {$i}",
                'scheduled_date' => $today,
                'start_time_utc' => "{$today} 06:00:00",
                'end_time_utc' => "{$today} 08:00:00",
                'status' => 'scheduled',
            ]);

            $workersData[] = [
                'worker_id' => $i,
                'teacher_user_id' => $user->id,
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'session_id' => $session->id,
            ];
        }

        // Reset default connection back to test connection
        DB::setDefaultConnection($oldDefault);

        // 4. Create Worker Script for Teacher (Attendance + Hifz Save)
        $teacherWorkerScript = base_path('temp_loadcheck_teacher_worker.php');
        $teacherWorkerCode = <<<PHP
<?php
require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$tempDb = '{$tempDb}';
config([
    'database.default' => 'concurrency_load_test',
    'database.connections.concurrency_load_test' => [
        'driver' => 'sqlite',
        'database' => \$tempDb,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 15000,
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'transaction_mode' => 'IMMEDIATE',
    ],
]);

DB::purge('concurrency_load_test');
DB::setDefaultConnection('concurrency_load_test');

\$institutionId = '{$institution->id}';
\$teacherId = \$argv[1];
\$teacherUserId = \$argv[2];
\$studentId = \$argv[3];
\$workerIndex = \$argv[4];
\$sessionId = \$argv[5];

\$institution = App\Domain\Institution\Models\Institution::findOrFail(\$institutionId);
app(App\Domain\Institution\Tenancy\TenantManager::class)->setTenant(\$institution);
app(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(\$institutionId);

\$session = App\Domain\Academics\Models\ClassSession::findOrFail(\$sessionId);
\$teacherUser = App\Domain\Auth\Models\User::findOrFail(\$teacherUserId);

// 1. Record Attendance via Action
\$attAction = app(App\Domain\Attendance\Actions\RecordSessionAttendanceAction::class);
\$records = [
    [
        'student_id' => \$studentId,
        'status' => 'present',
        'note' => 'Attendance logged by worker ' . \$workerIndex,
    ],
];

\$attAction->execute(\$session, \$records, \$teacherUser, 'present');

// 2. Small random jitter (10-50ms) to interleave concurrent operations
usleep(rand(10000, 50000));

// 3. Record Qur'an Hifz Progress
\$hifzAction = app(App\Domain\Hifz\Actions\RecordHifzEntryAction::class);
\$hifzAction->execute([
    'student_id' => \$studentId,
    'teacher_id' => \$teacherId,
    'class_session_id' => \$session->id,
    'date' => date('Y-m-d'),
    'type' => 'sabaq',
    'surah_number' => 1,
    'start_ayah' => 1,
    'end_ayah' => 7,
    'quality_rating' => 'excellent',
    'tajweed_rating' => 'excellent',
    'mistakes_count' => 0,
    'teacher_comment' => 'Saved concurrently by worker ' . \$workerIndex,
]);

echo "SUCCESS_WORKER_" . \$workerIndex;
PHP;

        file_put_contents($teacherWorkerScript, $teacherWorkerCode);

        // 5. Create Reader Script for Admin (Reports queries)
        $adminReaderScript = base_path('temp_loadcheck_admin_reader.php');
        $adminReaderCode = <<<PHP
<?php
require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$tempDb = '{$tempDb}';
config([
    'database.default' => 'concurrency_load_test',
    'database.connections.concurrency_load_test' => [
        'driver' => 'sqlite',
        'database' => \$tempDb,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 15000,
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'transaction_mode' => 'IMMEDIATE',
    ],
]);

DB::purge('concurrency_load_test');
DB::setDefaultConnection('concurrency_load_test');

\$institutionId = '{$institution->id}';
\$institution = App\Domain\Institution\Models\Institution::findOrFail(\$institutionId);
app(App\Domain\Institution\Tenancy\TenantManager::class)->setTenant(\$institution);

\$reportService = app(App\Domain\Reports\Services\ReportGeneratorService::class);

// Run reporting queries multiple times in a loop while workers write
\$iterations = 5;
for (\$i = 0; \$i < \$iterations; \$i++) {
    \$attReport = \$reportService->generateAttendanceReport(\$institution, []);
    \$hifzReport = \$reportService->generateHifzProgressReport(\$institution, []);
    \$studentReport = \$reportService->generateStudentListReport(\$institution, []);
    usleep(rand(20000, 60000));
}

echo "READER_SUCCESS";
PHP;

        file_put_contents($adminReaderScript, $adminReaderCode);

        /** @var Process[] $processes */
        $processes = [];

        try {
            // Launch 2 Admin Reader processes running report queries simultaneously
            for ($r = 1; $r <= 2; $r++) {
                $process = new Process(['php', $adminReaderScript]);
                $process->start();
                $processes[] = ['type' => 'reader', 'process' => $process];
            }

            // Launch 20 Teacher Worker processes saving attendance and Hifz simultaneously
            foreach ($workersData as $data) {
                $process = new Process([
                    'php',
                    $teacherWorkerScript,
                    $data['teacher_id'],
                    $data['teacher_user_id'],
                    $data['student_id'],
                    (string) $data['worker_id'],
                    $data['session_id'],
                ]);
                $process->start();
                $processes[] = ['type' => 'worker', 'id' => $data['worker_id'], 'process' => $process];
            }

            // Wait for all 22 processes to finish
            foreach ($processes as $entry) {
                /** @var Process $p */
                $p = $entry['process'];
                $p->wait();

                $err = $p->getErrorOutput();
                $out = trim($p->getOutput());

                // Assert zero exit failure
                $this->assertTrue(
                    $p->isSuccessful(),
                    "Process [{$entry['type']}] failed with code {$p->getExitCode()}!\nError: {$err}\nOutput: {$out}"
                );

                // Assert zero "database is locked" errors
                $this->assertStringNotContainsString('database is locked', $err);
                $this->assertStringNotContainsString('database is locked', $out);
                $this->assertStringNotContainsString('SQLSTATE[HY000]', $err);
                $this->assertStringNotContainsString('SQLSTATE[HY000]', $out);
            }

            // Verify final data integrity in the SQLite database
            $totalAttendance = DB::connection('concurrency_load_test')
                ->table('attendance_records')
                ->where('institution_id', $institution->id)
                ->count();

            $totalHifz = DB::connection('concurrency_load_test')
                ->table('hifz_entries')
                ->where('institution_id', $institution->id)
                ->count();

            $this->assertEquals($concurrencyCount, $totalAttendance, 'All 20 attendance records must be saved without loss.');
            $this->assertEquals($concurrencyCount, $totalHifz, 'All 20 Hifz entries must be saved without loss.');
        } finally {
            if (file_exists($teacherWorkerScript)) {
                unlink($teacherWorkerScript);
            }
            if (file_exists($adminReaderScript)) {
                unlink($adminReaderScript);
            }
            if (file_exists($tempDb)) {
                @unlink($tempDb);
            }
            if (file_exists("{$tempDb}-wal")) {
                @unlink("{$tempDb}-wal");
            }
            if (file_exists("{$tempDb}-shm")) {
                @unlink("{$tempDb}-shm");
            }
        }
    }
}
