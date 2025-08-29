<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupDummyTablesCommand extends Command
{
    protected $signature = 'aftable:cleanup-dummy-tables {--force : Force cleanup without confirmation}';
    protected $description = 'Cleanup dummy test tables and models created for testing';

    public function handle()
    {
        $force = $this->option('force');

        if (!$force && !$this->confirm('This will remove all dummy test data. Are you sure?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('🧹 Cleaning up dummy test data...');

        // Drop tables
        $this->dropTables();

        // Remove models
        $this->removeModels();

        $this->info('✅ Cleanup completed successfully!');
        return 0;
    }

    protected function dropTables()
    {
        $tables = [
            'af_test_table',
            'af_test_categories', 
            'af_test_departments'
        ];

        foreach ($tables as $table) {
            if (\Schema::hasTable($table)) {
                \Schema::dropIfExists($table);
                $this->line("  Dropped table: {$table}");
            }
        }
    }

    protected function removeModels()
    {
        $models = [
            'AfTestTable',
            'AfTestCategory',
            'AfTestDepartment'
        ];

        foreach ($models as $model) {
            $modelPath = app_path("Models/{$model}.php");
            if (File::exists($modelPath)) {
                File::delete($modelPath);
                $this->line("  Removed model: {$model}");
            }
        }
    }
}
