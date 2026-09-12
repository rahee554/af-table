<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('registeredComponents')]
    public function it_registers_every_livewire_component_alias(string $alias, string $class): void
    {
        $this->assertInstanceOf($class, Livewire::new($alias));
    }

    public static function registeredComponents(): array
    {
        // The two legacy aliases now resolve to the maintained component. They
        // remain registered so existing markup keeps working; see UPGRADING.md.
        return [
            'aftable' => ['aftable', AftableComponent::class],
            'aftable-simple' => ['aftable-simple', AftableComponent::class],
            'aftable-legacy' => ['aftable-legacy', AftableComponent::class],
        ];
    }

    #[Test]
    #[DataProvider('registeredDirectives')]
    public function it_registers_every_blade_directive(string $directive): void
    {
        $this->assertArrayHasKey($directive, Blade::getCustomDirectives());
    }

    public static function registeredDirectives(): array
    {
        return [
            ['AFtable'],
            ['AFtableTrait'],
            ['aftable'],
            ['endaftable'],
        ];
    }

    #[Test]
    public function it_loads_the_package_view_namespace(): void
    {
        $this->assertTrue(View::exists('artflow-table::livewire.aftable-component'));
        $this->assertTrue(View::exists('artflow-table::livewire.pagination'));
        $this->assertTrue(View::exists('artflow-table::docs'));
        $this->assertTrue(View::exists('artflow-table::generator'));
    }

    #[Test]
    public function it_registers_the_anonymous_blade_components(): void
    {
        $this->assertTrue(View::exists('artflow-table::components.empty-state'));
        $this->assertTrue(View::exists('artflow-table::components.skeleton-table'));
    }

    #[Test]
    #[DataProvider('registeredCommands')]
    public function it_registers_every_artisan_command(string $signature): void
    {
        $this->assertArrayHasKey($signature, $this->app[Kernel::class]->all());
    }

    public static function registeredCommands(): array
    {
        return [
            ['af-table:clear-phantom-columns'],
            ['af-table:make'],
            ['af-table:migrate'],
        ];
    }

    #[Test]
    public function it_registers_the_package_test_routes(): void
    {
        $names = [
            'aftable.test', 'aftable.docs', 'aftable.generator',
            'aftable.generator.schema', 'aftable.generator.describe',
        ];

        foreach ($names as $name) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($name),
                "Route [{$name}] should be registered by TableServiceProvider."
            );
        }
    }

    #[Test]
    public function the_route_prefix_and_middleware_come_from_config(): void
    {
        // Both keys shipped in 1.5.6 and were ignored: routes/test.php
        // hard-coded 'aftable' and ['web'].
        config()->set('aftable.routes.prefix', 'tables');
        config()->set('aftable.routes.middleware', ['web', 'auth']);

        $router = $this->app['router'];
        $router->getRoutes()->refreshNameLookups();

        // Re-run the route file against the fresh config.
        require __DIR__.'/../../routes/test.php';
        $router->getRoutes()->refreshNameLookups();

        $route = collect($router->getRoutes()->getRoutes())
            ->last(fn ($r) => $r->getName() === 'aftable.test');

        $this->assertSame('tables/test', $route->uri());
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    #[Test]
    public function the_removed_performance_command_route_is_gone(): void
    {
        // POST /aftable/test/run-performance called af-table:test-trait, which
        // was deleted in 1.6.0 — a guaranteed 500 on a shipped route.
        $this->assertNull(
            app('router')->getRoutes()->getByName('aftable.test.run-performance'),
            'This route invoked a command that no longer exists.'
        );
    }

    #[Test]
    public function the_aftable_directive_compiles_to_a_livewire_mount_call(): void
    {
        $compiled = Blade::compileString("@AFtable(['model' => 'Foo'])");

        $this->assertStringContainsString('Livewire\Livewire::mount', $compiled);
        $this->assertStringContainsString("'aftable'", $compiled);
    }

    #[Test]
    public function the_paired_aftable_directive_captures_its_body_as_a_custom_template(): void
    {
        $compiled = Blade::compileString(
            "@aftable(['model' => 'Foo'])<table class=\"x\"></table>@endaftable"
        );

        $this->assertStringContainsString('ob_start()', $compiled);
        $this->assertStringContainsString('customTemplate', $compiled);
        $this->assertStringContainsString('ob_get_clean()', $compiled);
    }
}
