<?php

namespace ArtflowStudio\Table\Console\Commands;

use ArtflowStudio\Table\Database\Seeders\AftableTestSeeder;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;

/**
 * Create (and optionally seed) the package's `test_*` demo tables.
 *
 * This used to be a hand-rolled migration runner: it discovered migration files
 * itself, derived class names from filenames, invented its own `migrations`
 * table schema, allocated a new batch per file, and on `--fresh` ran
 * `DELETE FROM migrations WHERE migration LIKE '%aftable%'` — which would also
 * delete a host-application migration that merely had "aftable" in its name.
 * It advertised `--force` and never read it, and used MySQL-only
 * `SET FOREIGN_KEY_CHECKS`.
 *
 * It now delegates to Laravel's own migrator, which the service provider
 * registers via loadMigrationsFrom(). See AUDIT.md BUG-17, BUG-19, BUG-20, BUG-31.
 */
class MigrateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'af-table:migrate
                            {--seed : Seed the demo tables with test data}
                            {--scale=1.0 : Multiplier for the seeded row counts (1.0 ≈ 118,600 rows)}
                            {--fresh : Roll the package migrations back before re-running them}
                            {--force : Run without confirmation when in production}';

    protected $description = "Run this package's demo-table migrations and optionally seed them";

    /** The demo tables this command owns. */
    private const TABLES = [
        'test_documents', 'test_timesheets', 'test_invoices', 'test_clients',
        'test_employee_project', 'test_tasks', 'test_projects', 'test_employees',
        'test_departments', 'test_companies',
    ];

    public function handle(): int
    {
        // ConfirmableTrait: prompts in production unless --force is passed.
        // The old command declared --force and never checked it, so --fresh
        // would drop tables on a production database with no prompt at all.
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $path = realpath(__DIR__.'/../../../database/migrations');

        if ($path === false || ! is_dir($path)) {
            $this->error('Package migrations directory not found.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->rollbackDemoTables($path);
        }

        $this->call('migrate', array_filter([
            '--path' => $path,
            '--realpath' => true,
            '--force' => true, // already confirmed above
        ]));

        if ($this->option('seed') && ($code = $this->seed()) !== self::SUCCESS) {
            return $code;
        }

        $this->newLine();
        $this->info('AF Table demo tables are ready.');

        if (! $this->option('seed')) {
            $this->line('  Add --seed to populate them, e.g. <comment>--seed --scale=0.01</comment> for a small set.');
        }

        return self::SUCCESS;
    }

    /**
     * Roll the package's own migrations back.
     *
     * Uses `migrate:rollback --path`, so only rows this package inserted into
     * the migrations table are removed — never a host migration that happens to
     * contain "aftable" in its name.
     */
    private function rollbackDemoTables(string $path): void
    {
        $this->info('Rolling back AF Table demo migrations...');

        $this->call('migrate:rollback', [
            '--path' => $path,
            '--realpath' => true,
            '--force' => true,
        ]);

        // The migration's down() is authoritative; this is a belt-and-braces
        // sweep for tables left behind by an older, hand-rolled run that never
        // recorded itself properly.
        $orphans = array_values(array_filter(
            self::TABLES,
            fn (string $table) => Schema::hasTable($table)
        ));

        if ($orphans === []) {
            return;
        }

        $this->warn('  dropping '.count($orphans).' table(s) left by a previous hand-rolled run');

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($orphans as $table) {
                Schema::dropIfExists($table);
                $this->line("    dropped {$table}");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function seed(): int
    {
        $scale = (float) $this->option('scale');

        if ($scale <= 0) {
            $this->error('--scale must be greater than zero.');

            return self::FAILURE;
        }

        $this->info("Seeding demo data (scale {$scale})...");

        try {
            (new AftableTestSeeder)->setScale($scale)->run();
        } catch (\Throwable $e) {
            $this->error('Seeding failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
