<?php

namespace App\Providers;

use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function () {
            return new TenantManager;
        });

        $this->app->alias(TenantManager::class, 'tenant.manager');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureSqlitePermissions();
    }

    /**
     * Ensure SQLite database file permissions and location.
     */
    protected function ensureSqlitePermissions(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if ($database && $database !== ':memory:' && ! str_starts_with($database, 'file:') && file_exists($database)) {
            // Ensure permissions: 0640 for db file, 0750 for db folder
            @chmod($database, 0640);
            $dir = dirname($database);
            if (is_dir($dir)) {
                @chmod($dir, 0750);
            }
        }
    }
}
