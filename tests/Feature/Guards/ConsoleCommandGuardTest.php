<?php

namespace ArtflowStudio\Table\Tests\Feature\Guards;

use ArtflowStudio\Table\Console\Commands\ClearPhantomColumnsCommand;
use ArtflowStudio\Table\Console\Commands\ImprovedTestTraitCommand;
use ArtflowStudio\Table\Console\Commands\MigrateCommand;
use ArtflowStudio\Table\Database\Seeders\AftableTestSeeder;
use ArtflowStudio\Table\TableServiceProvider;
use ArtflowStudio\Table\Tests\Support\SourceScanner;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Defects in the Artisan commands the package ships.
 *
 * Findings still OPEN carry #[Group('known-bugs')] on the method and are listed
 * by `composer test:bugs`. Findings already fixed stay here untagged, so they
 * run in the default suite as regression guards.
 */
class ConsoleCommandGuardTest extends TestCase
{
    #[Test]
    #[TestDox('BUG-11 (high): af-table:clear-phantom-columns flushes the entire application cache')]
    public function clearing_phantom_columns_does_not_flush_unrelated_cache_entries(): void
    {
        Cache::put('unrelated:business:key', 'important', 600);

        $this->artisan('af-table:clear-phantom-columns');

        $this->assertSame(
            'important',
            Cache::get('unrelated:business:key'),
            'The command calls Cache::flush() inside a loop over five "patterns", wiping every '.
            'cache entry in the application five times over. It should delete only its own keys '.
            '(cache tags, or a scanned key prefix).'
        );
    }

    #[Test]
    #[TestDox('BUG-11b (high): af-table:clear-phantom-columns destroys the host app config/route/view caches')]
    public function clearing_phantom_columns_does_not_clear_host_application_caches(): void
    {
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $call) {
            $this->assertFalse(
                SourceScanner::contains(ClearPhantomColumnsCommand::class, $call),
                "A package command must never run `{$call}` on the host application. On a ".
                'production server this discards `php artisan config:cache` / `route:cache` and '.
                'can take the app down if it relies on cached config with no .env present.'
            );
        }
    }

    #[Test]
    #[TestDox('BUG-11c: an advertised --tenant option must actually scope what it clears')]
    public function the_tenant_option_is_either_functional_or_absent(): void
    {
        $definition = $this->app[Kernel::class]
            ->all()['af-table:clear-phantom-columns']->getDefinition();

        if (! $definition->hasOption('tenant')) {
            $this->addToAssertionCount(1);   // removed rather than left as decoration

            return;
        }

        $this->assertMatchesRegularExpression(
            '/(Cache|Session)::[a-zA-Z]+\([^)]*\$tenant/',
            SourceScanner::executableSourceOf(ClearPhantomColumnsCommand::class),
            '--tenant is advertised but never narrows the cache or session keys that are '.
            'cleared, so on a multi-tenant install it clears every tenant regardless.'
        );
    }

    #[Test]
    #[TestDox('BUG-12 (fixed): the hand-rolled test commands were removed in 1.6.0')]
    public function the_stubbed_test_commands_are_gone(): void
    {
        // af-table:test-enhanced reported PASS for 8 suites that printed
        // "Test implementation in progress" and asserted nothing; the other two
        // exercised the legacy component forks with method_exists() checks.
        // `composer test` replaces all three with a real PHPUnit suite.
        $registered = array_keys($this->app[Kernel::class]->all());

        foreach (['af-table:test', 'af-table:test-trait', 'af-table:test-enhanced'] as $command) {
            $this->assertNotContains($command, $registered);
        }

        $this->assertFalse(
            class_exists(ImprovedTestTraitCommand::class),
            'ImprovedTestTraitCommand should no longer exist.'
        );
    }

    #[Test]
    #[TestDox('BUG-17: af-table:migrate hand-rolls a migration runner instead of using loadMigrationsFrom()')]
    public function migrations_are_registered_with_the_framework(): void
    {
        $this->assertTrue(
            SourceScanner::contains(TableServiceProvider::class, 'loadMigrationsFrom'),
            'TableServiceProvider never calls loadMigrationsFrom(), so `php artisan migrate` '.
            'ignores the package. MigrateCommand re-implements migration discovery, batch '.
            'numbering and the `migrations` table by hand, with no rollback support.'
        );
    }

    #[Test]
    #[TestDox('BUG-31: af-table:migrate --fresh is MySQL-only')]
    public function the_fresh_flag_works_on_any_supported_database(): void
    {
        $this->assertFalse(
            SourceScanner::contains(MigrateCommand::class, 'SET FOREIGN_KEY_CHECKS')
            || SourceScanner::contains(AftableTestSeeder::class, 'SET FOREIGN_KEY_CHECKS'),
            '`SET FOREIGN_KEY_CHECKS` is MySQL syntax; --fresh throws on SQLite, PostgreSQL and '.
            'SQL Server. Use Schema::disableForeignKeyConstraints() / dropIfExists().'
        );
    }

    #[Test]
    #[TestDox('BUG-19: af-table:migrate --force is declared but never checked')]
    public function the_force_flag_guards_production(): void
    {
        $usesConfirmable = in_array(
            ConfirmableTrait::class,
            class_uses(MigrateCommand::class) ?: [],
            true
        );

        $this->assertTrue(
            $usesConfirmable || SourceScanner::contains(MigrateCommand::class, "option('force')"),
            'The command advertises `--force : Force the operation to run when in production` '.
            'but never reads the option and never guards on app()->isProduction(). --fresh will '.
            'happily drop tables on a production database with no prompt.'
        );
    }

    #[Test]
    #[TestDox("BUG-20: af-table:migrate DELETEs any migrations row whose name contains 'aftable'")]
    public function the_fresh_flag_only_removes_its_own_migration_rows(): void
    {
        $this->assertFalse(
            SourceScanner::contains(MigrateCommand::class, 'DELETE FROM migrations'),
            'A host-application migration named e.g. 2025_01_01_add_aftable_settings would be '.
            'deleted from the migrations table and silently re-run on the next deploy.'
        );
    }

    #[Test]
    #[TestDox('BUG-21: the seeder hard-codes ~118,600 rows with no way to ask for fewer')]
    public function the_seeder_row_count_is_configurable(): void
    {
        $definition = $this->app[Kernel::class]
            ->all()['af-table:migrate']->getDefinition();

        $this->assertTrue(
            $definition->hasOption('rows') || $definition->hasOption('scale'),
            'af-table:migrate --seed always inserts ~118,600 rows (50,000 timesheets alone). '.
            'There should be a --rows/--scale option so the command is usable in CI and on a '.
            'developer laptop.'
        );
    }
}
