<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

class BackupSqliteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:sqlite
                            {--keep-days=14 : Number of days to retain backup files}
                            {--destination= : Custom destination path for backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a consistent SQLite database backup using VACUUM INTO and prune old backups';

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

        $sourcePath = config('database.connections.sqlite.database');

        if ($sourcePath === ':memory:' || str_starts_with($sourcePath, 'file:')) {
            $this->error('Cannot backup an in-memory SQLite database.');

            return self::FAILURE;
        }

        if (! file_exists($sourcePath)) {
            $this->error("Database file not found at: {$sourcePath}");

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0750, true);
        }

        $timestamp = date('Y-m-d_His');
        $customDestination = $this->option('destination');
        $destinationPath = $customDestination ?: $backupDir."/backup_{$timestamp}.sqlite";

        $destDir = dirname($destinationPath);
        if (! File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0750, true);
        }

        if (file_exists($destinationPath)) {
            File::delete($destinationPath);
        }

        $this->info("Starting SQLite VACUUM INTO backup...");

        try {
            $pdo = DB::connection('sqlite')->getPdo();
            $stmt = $pdo->prepare('VACUUM INTO ?');
            $stmt->execute([$destinationPath]);

            // Set secure permissions
            @chmod($destinationPath, 0640);

            // Verify integrity of created backup file
            $backupPdo = new PDO("sqlite:{$destinationPath}");
            $integrity = $backupPdo->query('PRAGMA integrity_check')->fetchColumn();

            if ($integrity !== 'ok') {
                File::delete($destinationPath);
                throw new RuntimeException("Integrity check failed on backup file. PRAGMA integrity_check returned: {$integrity}");
            }

            $size = round(filesize($destinationPath) / 1024, 2);
            $this->info("Backup created successfully: {$destinationPath} ({$size} KB) [Integrity: {$integrity}]");

            // Rotate backups
            $keepDays = (int) $this->option('keep-days');
            $pruned = $this->rotateBackups($backupDir, $keepDays);

            if ($pruned > 0) {
                $this->info("Pruned {$pruned} backup(s) older than {$keepDays} days.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Prune backups older than specified days.
     */
    protected function rotateBackups(string $directory, int $keepDays): int
    {
        if ($keepDays <= 0 || ! File::isDirectory($directory)) {
            return 0;
        }

        $cutoff = time() - ($keepDays * 86400);
        $files = File::glob($directory.'/backup_*.sqlite');
        $pruned = 0;

        foreach ($files as $file) {
            if (File::lastModified($file) < $cutoff) {
                File::delete($file);
                $pruned++;
            }
        }

        return $pruned;
    }
}
