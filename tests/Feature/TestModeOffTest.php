<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\TableServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PHPUnit\Framework\Attributes\Test;

/**
 * With the default config (aftable.test_mode off) a host app gets no demo tables and no demo routes.
 */
class TestModeOffTest extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, TableServiceProvider::class];
    }

    #[Test]
    public function test_mode_is_off_by_default(): void
    {
        $this->assertFalse(config('aftable.test_mode'));
    }

    #[Test]
    public function the_demo_tables_are_not_registered_with_the_migrator(): void
    {
        $expected = realpath(__DIR__.'/../../database/migrations');

        $this->assertNotContains($expected, array_map('realpath', $this->app['migrator']->paths()));
    }

    #[Test]
    public function no_demo_routes_are_registered(): void
    {
        $uris = collect($this->app['router']->getRoutes()->getRoutes())->map(fn ($r) => $r->uri());

        $this->assertTrue($uris->filter(fn ($u) => str_starts_with($u, 'aftable'))->isEmpty());
    }
}
