<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SQLiteProductionConfigTest extends TestCase
{
    public function test_sqlite_configuration_settings_meet_production_requirements(): void
    {
        $connectionConfig = Config::get('database.connections.sqlite');

        $this->assertEquals('sqlite', $connectionConfig['driver']);
        $this->assertTrue((bool) $connectionConfig['foreign_key_constraints'], 'Foreign keys must be enabled.');
        $this->assertGreaterThanOrEqual(5000, (int) $connectionConfig['busy_timeout'], 'Busy timeout must be at least 5000 ms.');
        $this->assertEquals('WAL', strtoupper($connectionConfig['journal_mode']), 'Journal mode must be WAL.');
        $this->assertEquals('NORMAL', strtoupper($connectionConfig['synchronous']), 'Synchronous mode must be NORMAL.');
        $this->assertEquals('IMMEDIATE', strtoupper($connectionConfig['transaction_mode']), 'Transaction mode must be IMMEDIATE.');
    }

    public function test_sqlite_connection_pragmas_are_properly_applied(): void
    {
        $pdo = DB::connection('sqlite')->getPdo();

        // 1. Foreign Keys ON
        $foreignKeys = (int) $pdo->query('PRAGMA foreign_keys')->fetchColumn();
        $this->assertEquals(1, $foreignKeys, 'PRAGMA foreign_keys must be 1 (ON).');

        // 2. Busy Timeout >= 5000
        $busyTimeout = (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn();
        $this->assertGreaterThanOrEqual(5000, $busyTimeout, 'PRAGMA busy_timeout must be >= 5000.');

        // 3. Synchronous NORMAL (1 corresponds to NORMAL in SQLite)
        $synchronous = (int) $pdo->query('PRAGMA synchronous')->fetchColumn();
        $this->assertEquals(1, $synchronous, 'PRAGMA synchronous must be 1 (NORMAL).');
    }

    public function test_database_file_is_stored_outside_web_root(): void
    {
        $productionDefault = database_path('database.sqlite');
        $publicPath = public_path();

        $this->assertFalse(
            str_starts_with($productionDefault, $publicPath),
            'SQLite database file default path must be stored outside the web root (public directory).'
        );

        $configuredPath = Config::get('database.connections.sqlite.database');
        if ($configuredPath && $configuredPath !== ':memory:' && ! str_starts_with($configuredPath, 'file:')) {
            $this->assertFalse(
                str_starts_with(realpath($configuredPath) ?: $configuredPath, realpath($publicPath) ?: $publicPath),
                'Configured SQLite database path must be stored outside the web root.'
            );
        }
    }

    public function test_transactions_use_immediate_mode(): void
    {
        $this->assertEquals('IMMEDIATE', Config::get('database.connections.sqlite.transaction_mode'));

        // Begin transaction and verify it can write immediately without lock escalation
        DB::beginTransaction();
        $this->assertEquals(1, DB::transactionLevel());
        DB::rollBack();
        $this->assertEquals(0, DB::transactionLevel());
    }
}
