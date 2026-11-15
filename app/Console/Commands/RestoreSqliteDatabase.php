<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

class RestoreSqliteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore
                            {file? : The backup file path or filename to restore}
                            {--latest : Restore the most recent backup available}
                            {--force : Force restore without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a SQLite database from a backup file with integrity verification';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = config('database.default', 'sqlite');

        if ($connection !== 'sqlite') {
            $this->error("Current database connection is '{$connection}'. This command only supports SQLite.");

            return self::FAILURE;
        }

        $targetPath = config('database.connections.sqlite.database');

        if ($targetPath === ':memory:' || str_starts_with($targetPath, 'file:')) {
            $this->error('Cannot restore to an in-memory SQLite database.');

            return self::FAILURE;
        }

        $backupPath = $this->resolveBackupPath();

        if (! $backupPath || ! file_exists($backupPath)) {
            $this->error("Backup file could not be found: {$backupPath}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to overwrite '{$targetPath}' with '{$backupPath}'?")) {
            $this->info('Restore cancelled.');

            return self::SUCCESS;
        }

        $this->info("Verifying integrity of backup file: {$backupPath}...");

        try {
            $backupPdo = new PDO("sqlite:{$backupPath}");
            $integrity = $backupPdo->query('PRAGMA integrity_check')->fetchColumn();

            if ($integrity !== 'ok') {
                throw new RuntimeException("Backup file failed integrity check. PRAGMA integrity_check returned: {$integrity}");
            }

            $this->info('Backup file integrity confirmed (ok). Proceeding with restore...');

            // Disconnect active database connection
            DB::purge('sqlite');

            // Ensure destination folder exists
            $targetDir = dirname($targetPath);
            if (! File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0750, true);
            }

            // Copy backup to database path
            if (! copy($backupPath, $targetPath)) {
                throw new RuntimeException("Failed to copy '{$backupPath}' to '{$targetPath}'.");
            }

            // Clean up old WAL and SHM journal files to prevent stale state
            $walPath = $targetPath.'-wal';
            $shmPath = $targetPath.'-shm';
            if (file_exists($walPath)) {
                @unlink($walPath);
            }
            if (file_exists($shmPath)) {
                @unlink($shmPath);
            }

            // Enforce correct permissions
            @chmod($targetPath, 0640);
            @chmod($targetDir, 0750);

            // Reconnect and verify integrity of the restored active database
            DB::reconnect('sqlite');
            $restoredPdo = DB::connection('sqlite')->getPdo();
            $checkRestored = $restoredPdo->query('PRAGMA integrity_check')->fetchColumn();

            if ($checkRestored !== 'ok') {
                throw new RuntimeException("Restored database failed integrity check: {$checkRestored}");
            }

            $this->info("Database restored successfully from: {$backupPath} [Integrity: {$checkRestored}]");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Resolve the backup file to restore.
     */
    protected function resolveBackupPath(): ?string
    {
        $file = $this->argument('file');
        $backupDir = storage_path('app/backups');

        if ($file) {
            if (file_exists($file)) {
                return realpath($file) ?: $file;
            }

            $inBackupDir = $backupDir.'/'.$file;
            if (file_exists($inBackupDir)) {
                return $inBackupDir;
            }

            return $file;
        }

        if ($this->option('latest') || empty($file)) {
            $backups = File::glob($backupDir.'/backup_*.sqlite');

            if (empty($backups)) {
                return null;
            }

            // Sort by mtime descending
            usort($backups, fn ($a, $b) => filemtime($b) <=> filemtime($a));

            return $backups[0];
        }

        return null;
    }
}
