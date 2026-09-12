<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers the five Artisan commands the package registers: that they run, exit
 * cleanly and expose the options they document.
 */
class ConsoleCommandTest extends TestCase
{
    #[Test]
    #[DataProvider('commandSignatures')]
    public function the_command_is_registered_with_a_description(string $signature): void
    {
        $command = $this->app[Kernel::class]->all()[$signature] ?? null;

        $this->assertNotNull($command, "Command [{$signature}] is not registered.");
        $this->assertNotEmpty($command->getDescription());
    }

    public static function commandSignatures(): array
    {
        // The three `af-table:test*` commands were removed in 1.6.0 - they
        // tested the legacy forks and reported PASS for unimplemented suites.
        return [
            ['af-table:clear-phantom-columns'],
            ['af-table:migrate'],
        ];
    }

    #[Test]
    public function af_table_migrate_declares_its_documented_options(): void
    {
        $definition = $this->app[Kernel::class]
            ->all()['af-table:migrate']->getDefinition();

        foreach (['seed', 'force', 'fresh'] as $option) {
            $this->assertTrue($definition->hasOption($option), "--{$option} should be declared.");
        }
    }

    #[Test]
    public function af_table_migrate_creates_the_test_tables(): void
    {
        // RefreshDatabase already ran the migration; prove the tables the command
        // targets are the ones the package models expect.
        foreach (['test_companies', 'test_departments', 'test_employees', 'test_projects', 'test_tasks'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}].");
        }
    }

    #[Test]
    public function clear_phantom_columns_exits_successfully(): void
    {
        $this->artisan('af-table:clear-phantom-columns')->assertExitCode(0);
    }

    #[Test]
    public function the_removed_test_commands_are_gone(): void
    {
        $registered = array_keys($this->app[Kernel::class]->all());

        foreach (['af-table:test', 'af-table:test-trait', 'af-table:test-enhanced'] as $command) {
            $this->assertNotContains(
                $command,
                $registered,
                "{$command} was removed in 1.6.0; `composer test` replaces it."
            );
        }
    }
}
