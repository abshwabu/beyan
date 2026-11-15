<?php

namespace Tests\Feature;

use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Services\StudentIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class StudentIdConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan-academy',
            'contact' => ['email' => 'info@beyan.edu.et'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);
    }

    public function test_student_ids_are_sequential_and_follow_institution_prefix_and_year(): void
    {
        $generator = app(StudentIdGenerator::class);

        $id1 = $generator->generate($this->institution);
        $id2 = $generator->generate($this->institution);
        $id3 = $generator->generate($this->institution);

        $year = now()->year;

        $this->assertEquals("BYN-{$year}-0001", $id1);
        $this->assertEquals("BYN-{$year}-0002", $id2);
        $this->assertEquals("BYN-{$year}-0003", $id3);
    }

    public function test_student_ids_are_unique_under_concurrent_parallel_executions(): void
    {
        $tempDb = database_path('test_concurrency_'.uniqid().'.sqlite');
        touch($tempDb);

        // Configure a dedicated SQLite connection pointing to the physical file
        config([
            'database.connections.concurrency_test' => [
                'driver' => 'sqlite',
                'database' => $tempDb,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 5000,
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
                'transaction_mode' => 'IMMEDIATE',
            ],
        ]);

        // Run migrations on the file
        Artisan::call('migrate', [
            '--database' => 'concurrency_test',
            '--path' => 'database/migrations',
        ]);

        // Insert the institution into this file
        $institutionId = $this->institution->id;
        DB::connection('concurrency_test')->table('institutions')->insert([
            'id' => $institutionId,
            'name' => 'Beyan Academy',
            'slug' => 'beyan-academy',
            'contact' => json_encode(['email' => 'info@beyan.edu.et']),
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $numProcesses = 10;
        $idsPerProcess = 5; // Total 50 IDs across 10 concurrent processes

        $scriptPath = base_path('temp_concurrency_worker.php');

        $workerCode = <<<PHP
<?php
require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$tempDb = '{$tempDb}';
config([
    'database.default' => 'concurrency_test',
    'database.connections.concurrency_test' => [
        'driver' => 'sqlite',
        'database' => \$tempDb,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 10000,
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'transaction_mode' => 'IMMEDIATE',
    ],
]);

DB::purge('concurrency_test');
DB::setDefaultConnection('concurrency_test');

\$institution = App\Domain\Institution\Models\Institution::findOrFail('{$institutionId}');
\$generator = app(App\Domain\Students\Services\StudentIdGenerator::class);

\$generated = [];
for (\$i = 0; \$i < {$idsPerProcess}; \$i++) {
    \$generated[] = \$generator->generate(\$institution);
    usleep(rand(1000, 5000));
}

echo implode(',', \$generated);
PHP;

        file_put_contents($scriptPath, $workerCode);

        /** @var Process[] $processes */
        $processes = [];

        try {
            // Launch parallel independent processes
            for ($p = 0; $p < $numProcesses; $p++) {
                $process = new Process(['php', $scriptPath]);
                $process->start();
                $processes[] = $process;
            }

            $allIds = [];

            // Wait for all processes to complete
            foreach ($processes as $index => $process) {
                $process->wait();
                $err = $process->getErrorOutput();
                $out = trim($process->getOutput());
                $this->assertTrue($process->isSuccessful(), "Subprocess {$index} failed: {$err}\nOutput: {$out}");

                $ids = array_filter(array_map('trim', explode(',', $out)));
                $this->assertCount($idsPerProcess, $ids, "Subprocess {$index} did not produce {$idsPerProcess} IDs. Output: {$out}");

                foreach ($ids as $id) {
                    $allIds[] = $id;
                }
            }

            $expectedTotal = $numProcesses * $idsPerProcess;
            $this->assertCount($expectedTotal, $allIds, "Expected {$expectedTotal} total IDs from parallel processes.");

            // Verify strict uniqueness
            $uniqueIds = array_unique($allIds);
            $this->assertCount($expectedTotal, $uniqueIds, 'Collision detected! All generated student IDs must be strictly unique.');

            // Verify final counter value
            $year = now()->year;
            $counterValue = DB::connection('concurrency_test')->table('counters')
                ->where('institution_id', $institutionId)
                ->where('key', 'student_code')
                ->where('year', $year)
                ->value('value');

            $this->assertEquals($expectedTotal, $counterValue);
        } finally {
            if (file_exists($scriptPath)) {
                unlink($scriptPath);
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
