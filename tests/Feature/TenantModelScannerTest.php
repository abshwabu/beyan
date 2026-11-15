<?php

namespace Tests\Feature;

use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class TenantModelScannerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * System/global tables that do not belong to a single tenant institution.
     */
    protected array $globalTables = [
        'institutions',
        'migrations',
        'sessions',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'student_teacher',
        'guardian_student',
        'class_group_teacher',
        'quran_surahs',
        'quran_juzs',
        'plans',
        'demo_requests',
    ];

    public function test_all_models_with_institution_id_use_belongs_to_institution_trait(): void
    {
        $modelClasses = $this->getAllModelClasses();
        $this->assertNotEmpty($modelClasses, 'At least one model class must be found.');

        $scannedTenantModels = [];

        foreach ($modelClasses as $class) {
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            if ($class === Institution::class) {
                continue;
            }

            /** @var Model $instance */
            $instance = new $class;
            $table = $instance->getTable();

            // Check if this model's table has an institution_id column
            if (Schema::hasColumn($table, 'institution_id')) {
                $traits = class_uses_recursive($class);

                $this->assertContains(
                    BelongsToInstitution::class,
                    $traits,
                    "Model [{$class}] manages tenant-owned table [{$table}] with 'institution_id' but lacks the [".BelongsToInstitution::class.'] trait!'
                );

                $scannedTenantModels[] = $class;
            }
        }

        $this->assertNotEmpty(
            $scannedTenantModels,
            'At least one tenant model must be detected and verified.'
        );
    }

    public function test_all_database_tables_with_institution_id_column_have_a_model_using_the_trait(): void
    {
        $tables = Schema::getTableListing();
        $modelClasses = $this->getAllModelClasses();

        // Map table names to model classes
        $tableModelMap = [];
        foreach ($modelClasses as $class) {
            $reflection = new ReflectionClass($class);
            if (! $reflection->isAbstract()) {
                $instance = new $class;
                $tableModelMap[$instance->getTable()][] = $class;
            }
        }

        foreach ($tables as $rawTable) {
            $table = str_contains($rawTable, '.') ? explode('.', $rawTable)[1] : $rawTable;

            if (in_array($table, $this->globalTables, true)) {
                continue;
            }

            if (Schema::hasColumn($table, 'institution_id')) {
                $this->assertArrayHasKey(
                    $table,
                    $tableModelMap,
                    "Database table [{$table}] has 'institution_id' but has no corresponding Eloquent Model."
                );

                $models = $tableModelMap[$table];
                $hasValidTenantModel = false;

                foreach ($models as $modelClass) {
                    $traits = class_uses_recursive($modelClass);
                    if (in_array(BelongsToInstitution::class, $traits, true)) {
                        $hasValidTenantModel = true;
                        break;
                    }
                }

                $this->assertTrue(
                    $hasValidTenantModel,
                    "Table [{$table}] contains 'institution_id', but none of its models [".implode(', ', $models).'] use BelongsToInstitution!'
                );
            }
        }
    }

    public function test_scanner_flags_any_model_lacking_trait(): void
    {
        // An anonymous or dynamic test class representing a flawed tenant model
        $flawedModel = new class extends Model
        {
            protected $table = 'academic_years'; // table has institution_id, but class omits BelongsToInstitution
        };

        $traits = class_uses_recursive($flawedModel::class);

        $hasTrait = in_array(BelongsToInstitution::class, $traits, true);
        $this->assertFalse(
            $hasTrait,
            'The test flawed model intentionally lacks the trait so the scanner can catch it.'
        );

        $table = $flawedModel->getTable();
        $this->assertTrue(Schema::hasColumn($table, 'institution_id'));
    }

    /**
     * Discover all Model classes across app/Domain and app/Models.
     *
     * @return array<class-string<Model>>
     */
    protected function getAllModelClasses(): array
    {
        $classes = [];
        $paths = [
            app_path('Domain'),
            app_path('Models'),
        ];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $files = File::allFiles($path);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = $file->getContents();

                // Extract namespace
                if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatches)) {
                    $namespace = trim($nsMatches[1]);

                    // Extract class name
                    if (preg_match('/class\s+([A-Za-z0-9_]+)/', $content, $classMatches)) {
                        $className = trim($classMatches[1]);
                        $fullClass = $namespace.'\\'.$className;

                        if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                            $classes[] = $fullClass;
                        }
                    }
                }
            }
        }

        return array_unique($classes);
    }
}
