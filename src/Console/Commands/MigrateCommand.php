<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class MigrateCommand extends Command
{
    protected $signature = 'af-table:migrate {--seed : Seed the database after migration} {--force : Force the operation to run when in production} {--fresh : Drop all tables and re-run migrations}';
    protected $description = 'Run AFTable package migrations and optionally seed the database with test data';

    public function handle()
    {
        $this->displayHeader();

        try {
            // Run migrations from the package
            $this->runMigrations();

            // Seed if requested
            if ($this->option('seed')) {
                $this->seedDatabase();
            }

            $this->displaySuccess();
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function displayHeader()
    {
        $this->info('╭──────────────────────────────────────────────────────╮');
        $this->info('│   🚀 AFTable Database Migration & Seeding 🚀        │');
        $this->info('╰──────────────────────────────────────────────────────╯');
        $this->newLine();
    }

    private function runMigrations()
    {
        $this->info('📦 Running AFTable Migrations...');
        $this->newLine();

        // Get package base path
        $packageBasePath = dirname(dirname(dirname(__DIR__)));
        $migrationsPath = $packageBasePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

        if (!File::isDirectory($migrationsPath)) {
            throw new \Exception("Migrations path not found: {$migrationsPath}");
        }

        // If fresh is requested, drop all tables first
        if ($this->option('fresh')) {
            $this->info('🔄 Fresh migration: Dropping all tables...');
            $this->freshTables();
            $this->newLine();
        }

        // Get all migration files
        $migrations = File::files($migrationsPath);

        if (empty($migrations)) {
            $this->warn('⚠️  No migration files found');
            return;
        }

        $migratedCount = 0;

        foreach ($migrations as $file) {
            $migrationName = $file->getFilenameWithoutExtension();
            
            // Check if already migrated
            if ($this->isMigrated($migrationName)) {
                $this->info("✅ Already migrated: {$migrationName}");
                continue;
            }

            try {
                // Include and run the migration (handles both class-based and closure-based)
                $migration = require $file->getRealPath();
                
                // Check if it's a closure-based migration (returns Migration instance)
                if ($migration instanceof \Illuminate\Database\Migrations\Migration) {
                    $migration->up();
                    $this->recordMigration($migrationName);
                    $this->info("✅ Migrated: {$migrationName}");
                    $migratedCount++;
                } else {
                    // Try class-based migration
                    $className = $this->getMigrationClassName($migrationName);
                    
                    if (class_exists($className)) {
                        $migration = new $className();
                        
                        if (method_exists($migration, 'up')) {
                            $migration->up();
                            $this->recordMigration($migrationName);
                            $this->info("✅ Migrated: {$migrationName}");
                            $migratedCount++;
                        }
                    } else {
                        $this->warn("⚠️  Could not find class for migration: {$migrationName}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("❌ Migration failed: {$migrationName}");
                $this->error("   Error: " . $e->getMessage());
                throw $e;
            }
        }

        $this->newLine();
        $this->info("✅ Completed {$migratedCount} migration(s)");
    }

    private function seedDatabase()
    {
        $this->info('🌱 Seeding Database with Test Data...');
        $this->newLine();

        // Get package base path
        $packageBasePath = dirname(dirname(dirname(__DIR__)));
        $seederPath = $packageBasePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders';

        if (!File::isDirectory($seederPath)) {
            throw new \Exception("Seeders path not found: {$seederPath}");
        }

        try {
            // Include the seeder
            $seederFile = $seederPath . '/AftableTestSeeder.php';
            
            if (!File::exists($seederFile)) {
                throw new \Exception("Seeder file not found: {$seederFile}");
            }

            require_once $seederFile;
            
            $seederClass = 'ArtflowStudio\Table\Database\Seeders\AftableTestSeeder';
            
            if (!class_exists($seederClass)) {
                throw new \Exception("Seeder class not found: {$seederClass}");
            }

            $seeder = new $seederClass();
            
            $this->info('📊 Seeding test data...');
            
            // Run the seeder
            if (method_exists($seeder, 'run')) {
                $seeder->run();
                $this->info('✅ Database seeded successfully');
            } else {
                $this->warn('⚠️  Seeder does not have run() method');
            }

        } catch (\Exception $e) {
            $this->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function freshTables()
    {
        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Drop tables if they exist
            $tables = [
                'test_employee_project',
                'test_timesheets',
                'test_documents',
                'test_invoices',
                'test_orders',
                'test_clients',
                'test_products',
                'test_categories',
                'test_tasks',
                'test_projects',
                'test_employees',
                'test_departments',
                'test_companies'
            ];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::statement("DROP TABLE IF EXISTS {$table}");
                    $this->info("   ✅ Dropped table: {$table}");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Reset migrations table
            if (DB::getSchemaBuilder()->hasTable('migrations')) {
                DB::statement("DELETE FROM migrations WHERE migration LIKE '%aftable%'");
            }

        } catch (\Exception $e) {
            // Re-enable foreign key checks even if error occurs
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn('⚠️  Could not drop tables: ' . $e->getMessage());
        }
    }

    private function isMigrated($migrationName)
    {
        try {
            return DB::table('migrations')
                ->where('migration', 'LIKE', "%{$migrationName}%")
                ->exists();
        } catch (\Exception $e) {
            // If migrations table doesn't exist, assume not migrated
            return false;
        }
    }

    private function recordMigration($migrationName)
    {
        try {
            // Ensure migrations table exists
            if (!DB::getSchemaBuilder()->hasTable('migrations')) {
                DB::statement("CREATE TABLE migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INT NOT NULL
                )");
            }

            // Get current batch number
            $batch = DB::table('migrations')->max('batch') ?? 0;
            $batch++;

            // Insert migration record
            DB::table('migrations')->insert([
                'migration' => "aftable_{$migrationName}",
                'batch' => $batch
            ]);

        } catch (\Exception $e) {
            $this->warn('⚠️  Could not record migration: ' . $e->getMessage());
        }
    }

    private function getMigrationClassName($migrationName)
    {
        // Convert filename to class name
        // e.g., "2024_01_01_000000_create_aftable_test_tables" 
        // becomes "CreateAftableTestTables"
        
        $parts = explode('_', $migrationName);
        
        // Remove timestamp parts (first 3 parts: year, month, day, sequence)
        $parts = array_slice($parts, 4);
        
        // Capitalize each part and join
        $className = implode('', array_map('ucfirst', $parts));
        
        return $className;
    }

    private function displaySuccess()
    {
        $this->newLine();
        $this->info('╭──────────────────────────────────────────────────────╮');
        $this->info('│   ✅ AFTable Migration Complete! ✅                  │');
        $this->info('╰──────────────────────────────────────────────────────╯');
        $this->newLine();

        if ($this->option('seed')) {
            $this->info('✨ Database is ready with test data!');
        } else {
            $this->info('💡 Run with --seed flag to populate test data:');
            $this->comment('   php artisan af-table:migrate --seed');
        }

        $this->newLine();
        $this->info('🚀 Ready to test AFTable components!');
        $this->info('📊 Run: php artisan af-table:test-trait');
        $this->newLine();
    }
}
