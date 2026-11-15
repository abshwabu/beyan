<?php

namespace App\Providers;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Policies\AttendancePolicy;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Policies\FinancePolicy;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Policies\StudentPolicy;
use App\Domain\Students\Search\LikeStudentSearch;
use App\Domain\Students\Search\StudentSearchInterface;
use Illuminate\Support\Facades\Gate;
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

        $this->app->bind(
            StudentSearchInterface::class,
            LikeStudentSearch::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureSqlitePermissions();

        Gate::policy(
            Student::class,
            StudentPolicy::class
        );

        Gate::policy(
            AttendanceRecord::class,
            AttendancePolicy::class
        );

        Gate::policy(
            FeeInvoice::class,
            FinancePolicy::class
        );

        Gate::before(function ($user, $ability) {
            return method_exists($user, 'isPlatformSuperAdmin') && $user->isPlatformSuperAdmin() ? true : null;
        });
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
