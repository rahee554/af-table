<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\TableServiceProvider;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\BlazeServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PHPUnit\Framework\Attributes\Test;

/**
 * TableServiceProvider must boot regardless of where BlazeServiceProvider sits
 * in the provider list.
 *
 * It previously called the Blaze *facade* during boot(), guarded only by
 * `class_exists(Blaze::class)` — which is true the moment livewire/blaze is
 * installed, whether or not its provider has bound the `blaze` container entry.
 * Booting this provider first killed the application with
 * `ReflectionException: Class "blaze" does not exist`. See AUDIT.md BUG-01.
 */
class BootOrderTest extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            // Deliberately BEFORE Blaze.
            TableServiceProvider::class,
            BlazeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('aftable.test_mode', true);
    }

    #[Test]
    public function the_application_boots_with_the_table_provider_registered_before_blaze(): void
    {
        $this->assertTrue($this->app->isBooted());
        $this->assertTrue(View::exists('artflow-table::livewire.aftable-component'));
    }

    #[Test]
    public function blaze_is_still_wired_up_once_both_providers_have_booted(): void
    {
        $this->assertTrue(
            $this->app->bound('blaze'),
            'Blaze should still be available; the fix defers the call, it does not skip it.'
        );
    }

    #[Test]
    public function the_package_migrations_are_registered_with_the_framework(): void
    {
        $paths = $this->app['migrator']->paths();

        $expected = realpath(__DIR__.'/../../database/migrations');
        $resolved = array_map('realpath', $paths);

        $this->assertContains(
            $expected,
            $resolved,
            'loadMigrationsFrom() should expose the package migrations to `php artisan migrate`. '.
            'Registered paths: '.json_encode($resolved)
        );
    }
}
