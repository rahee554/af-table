<?php

namespace ArtflowStudio\Table\Tests;

use ArtflowStudio\Table\TableServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Blaze\BlazeServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for the artflow-studio/table package.
 *
 * Boots a minimal Laravel 12 + Livewire 4 application via Orchestra Testbench,
 * runs the package's own test-table migration against an in-memory SQLite
 * database, and exposes small seeding helpers used across the suite.
 */
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            // Mirrors a real host app, where package discovery loads Blaze.
            // BootOrderTest deliberately reverses this order to prove the
            // provider no longer depends on it (AUDIT.md BUG-01).
            BlazeServiceProvider::class,
            TableServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // The package's own tests need its demo tables and routes.
        $app['config']->set('aftable.test_mode', true);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../resources/views'),
            realpath(__DIR__.'/Support/views'),
        ]);
    }

    // NOTE: no defineDatabaseMigrations() override. TableServiceProvider now
    // calls loadMigrationsFrom(), so RefreshDatabase picks the package's
    // migrations up on its own — which is exactly the behaviour BUG-17 was
    // about. BootOrderTest asserts the registration directly.

    /**
     * Seed a tiny, fully deterministic dataset: 1 company, 2 departments,
     * 5 employees. Small enough that every assertion can name exact rows.
     */
    protected function seedSmallDataset(): void
    {
        Support\DatasetSeeder::run();
    }
}
