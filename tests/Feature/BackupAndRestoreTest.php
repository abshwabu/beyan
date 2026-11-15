<?php

namespace Tests\Feature;

use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class BackupAndRestoreTest extends TestCase
{
    protected string $testDbPath;

    protected string $originalDbPath;

    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDir = storage_path('app/backups');
        if (! File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0750, true);
        }

        // Setup an isolated file-based database for backup/restore testing
        $this->originalDbPath = Config::get('database.connections.sqlite.database');
        $this->testDbPath = storage_path('app/test_backup_source.sqlite');

        if (file_exists($this->testDbPath)) {
            @unlink($this->testDbPath);
        }

        // Copy current structure or touch file
        touch($this->testDbPath);
        chmod($this->testDbPath, 0640);

        Config::set('database.connections.sqlite.database', $this->testDbPath);
        DB::purge('sqlite');

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');

        if (file_exists($this->testDbPath)) {
            @unlink($this->testDbPath);
        }
        if (file_exists($this->testDbPath.'-wal')) {
            @unlink($this->testDbPath.'-wal');
        }
        if (file_exists($this->testDbPath.'-shm')) {
            @unlink($this->testDbPath.'-shm');
        }

        // Restore original database config
        Config::set('database.connections.sqlite.database', $this->originalDbPath);
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_backup_creates_vacuum_into_copy_and_passes_integrity_check(): void
    {
        // 1. Seed data into test database
        $institution = Institution::create([
            'name' => 'Integrity Check Madrasa',
            'slug' => 'integrity-check-madrasa',
            'mode' => 'hybrid',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'en',
            'status' => 'active',
        ]);

        $customBackupPath = $this->backupDir.'/test_backup_integrity.sqlite';
        if (file_exists($customBackupPath)) {
            @unlink($customBackupPath);
        }

        // 2. Run backup command
        $exitCode = Artisan::call('backup:sqlite', [
            '--destination' => $customBackupPath,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertFileExists($customBackupPath);

        // 3. Verify integrity check directly on backup file
        $backupPdo = new PDO("sqlite:{$customBackupPath}");
        $integrityResult = $backupPdo->query('PRAGMA integrity_check')->fetchColumn();
        $this->assertEquals('ok', $integrityResult, 'PRAGMA integrity_check must return ok on the backup file.');

        // Clean up
        if (file_exists($customBackupPath)) {
            @unlink($customBackupPath);
        }
    }

    public function test_restore_recovers_database_and_verifies_integrity(): void
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        // 1. Create a known state
        $institution = $tenantManager->withoutTenantScope(function () {
            return Institution::create([
                'name' => 'Original Madrasa Before Crash',
                'slug' => 'original-madrasa',
                'mode' => 'physical',
                'timezone' => 'Africa/Addis_Ababa',
                'currency' => 'ETB',
                'locale' => 'am',
                'status' => 'active',
            ]);
        });

        $customBackupPath = $this->backupDir.'/backup_restore_test.sqlite';
        if (file_exists($customBackupPath)) {
            @unlink($customBackupPath);
        }

        // 2. Create the backup
        $backupExit = Artisan::call('backup:sqlite', [
            '--destination' => $customBackupPath,
        ]);
        $this->assertEquals(0, $backupExit);
        $this->assertFileExists($customBackupPath);

        // 3. Mutate the active database (simulate data loss / corruption)
        $tenantManager->withoutTenantScope(function () use ($institution) {
            $institution->delete();
        });

        $this->assertDatabaseMissing('institutions', ['slug' => 'original-madrasa']);

        // 4. Restore from the backup file
        $restoreExit = Artisan::call('backup:restore', [
            'file' => $customBackupPath,
            '--force' => true,
        ]);

        $this->assertEquals(0, $restoreExit, 'Restore command must exit with code 0.');

        // 5. Assert the lost data has been fully restored
        $restoredInstitution = $tenantManager->withoutTenantScope(function () {
            return Institution::where('slug', 'original-madrasa')->first();
        });

        $this->assertNotNull($restoredInstitution, 'Restored database must contain original institution.');
        $this->assertEquals('Original Madrasa Before Crash', $restoredInstitution->name);

        // 6. Assert integrity of restored database
        $restoredPdo = DB::connection('sqlite')->getPdo();
        $integrity = $restoredPdo->query('PRAGMA integrity_check')->fetchColumn();
        $this->assertEquals('ok', $integrity, 'Restored database must pass PRAGMA integrity_check.');

        // Clean up
        if (file_exists($customBackupPath)) {
            @unlink($customBackupPath);
        }
    }

    public function test_backup_rotation_prunes_backups_older_than_14_days(): void
    {
        // Create an old backup file with modification time 16 days ago
        $oldBackup = $this->backupDir.'/backup_2020-01-01_000000.sqlite';
        touch($oldBackup, time() - (16 * 86400));
        $this->assertFileExists($oldBackup);

        // Create a recent backup file (1 day old)
        $recentBackup = $this->backupDir.'/backup_2026-11-14_000000.sqlite';
        touch($recentBackup, time() - (1 * 86400));
        $this->assertFileExists($recentBackup);

        // Run backup with 14-day retention
        Artisan::call('backup:sqlite', ['--keep-days' => 14]);

        // Old file must have been deleted, recent file retained
        $this->assertFileDoesNotExist($oldBackup, 'Backups older than 14 days must be pruned.');
        $this->assertFileExists($recentBackup, 'Recent backups must be retained.');

        // Clean up
        if (file_exists($recentBackup)) {
            @unlink($recentBackup);
        }
    }
}
