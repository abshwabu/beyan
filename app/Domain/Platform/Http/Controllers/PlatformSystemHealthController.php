<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSystemHealthController extends Controller
{
    /**
     * Display system health metrics, storage, and backup statuses.
     */
    public function index(): Response
    {
        $dbPath = config('database.connections.sqlite.database');
        $dbSizeBytes = file_exists($dbPath) ? filesize($dbPath) : 0;
        $dbSizeMb = round($dbSizeBytes / (1024 * 1024), 2);

        // Run quick SQLite integrity check
        $integrityResult = 'ok';
        try {
            $check = DB::select('PRAGMA quick_check');
            $integrityResult = $check[0]->quick_check ?? 'ok';
        } catch (\Throwable $e) {
            $integrityResult = 'check failed: '.$e->getMessage();
        }

        // Backups directory scan
        $backupDir = storage_path('app/backups');
        $backups = [];
        $lastBackupTime = null;

        if (File::isDirectory($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size_mb' => round($file->getSize() / (1024 * 1024), 2),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
            usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
            $lastBackupTime = $backups[0]['created_at'] ?? null;
        }

        // Queue statuses
        $jobsCount = 0;
        $failedJobsCount = 0;
        try {
            $jobsCount = DB::table('jobs')->count();
            $failedJobsCount = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            // tables might not exist or empty
        }

        // SQLite version
        $sqliteVersion = 'unknown';
        try {
            $res = DB::select('select sqlite_version() as version');
            $sqliteVersion = $res[0]->version ?? 'unknown';
        } catch (\Throwable $e) {
            //
        }

        $health = [
            'app_version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'sqlite_version' => $sqliteVersion,
            'database_size_mb' => $dbSizeMb,
            'database_path' => $dbPath,
            'integrity_check' => $integrityResult,
            'last_successful_backup' => $lastBackupTime,
            'recent_backups' => array_slice($backups, 0, 5),
            'queue' => [
                'pending_jobs' => $jobsCount,
                'failed_jobs' => $failedJobsCount,
            ],
            'server_time' => now()->toIso8601String(),
        ];

        return Inertia::render('Platform/Health/Index', [
            'health' => $health,
        ]);
    }

    /**
     * Trigger manual database backup snapshot.
     */
    public function triggerBackup(Request $request): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('backup:sqlite', ['--keep-days' => 14]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return back()->with('success', 'Backup created successfully: '.trim($output));
            }

            return back()->with('error', 'Backup failed: '.trim($output));
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup exception: '.$e->getMessage());
        }
    }
}
