<?php

namespace ArtflowStudio\Table;

use ArtflowStudio\Table\Console\Commands\ClearPhantomColumnsCommand;
use ArtflowStudio\Table\Console\Commands\MakeTableCommand;
use ArtflowStudio\Table\Console\Commands\MigrateCommand;
use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Support\GuardsActions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;

class TableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('aftable', AftableComponent::class);

        // Back-compat aliases. `aftable-simple` and `aftable-legacy` used to be
        // two separate forks of this component, each with its own copy of the
        // relation / JSON / sorting / filtering logic - so every fix had to be
        // made three times, and in practice only ever landed in one. They now
        // resolve to the maintained component: existing markup keeps working and
        // silently gains every fix the forks never received.
        //
        // Deprecated since 1.6.0. Prefer `aftable`.
        Livewire::component('aftable-simple', AftableComponent::class);
        Livewire::component('aftable-legacy', AftableComponent::class);

        // Simple shorthand directives — equivalent to @livewire('aftable', [...])
        // Usage: @AFtable(['model' => App\Models\User::class, 'columns' => [...]])
        Blade::directive('AFtable', function ($expression) {
            return "<?php echo \Livewire\Livewire::mount('aftable', {$expression}); ?>";
        });

        Blade::directive('AFtableTrait', function ($expression) {
            return "<?php echo \Livewire\Livewire::mount('aftable', {$expression}); ?>";
        });

        // Paired directive — lets you wrap the table in a custom HTML skeleton.
        //
        // Usage (without custom template — identical to @livewire):
        //   @aftable(['model' => App\Models\User::class, 'columns' => [...]])
        //   @endaftable
        //
        // Usage (with custom table markup):
        //   @aftable(['model' => App\Models\User::class, 'columns' => [...]])
        //       <table class="table table-hover my-theme">
        //           <thead class="bg-primary text-white"><tr></tr></thead>
        //           <tbody></tbody>
        //       </table>
        //   @endaftable
        //
        // The HTML between the tags is passed as 'customTemplate' to the component,
        // which uses it as the outer table wrapper instead of the default Bootstrap one.
        Blade::directive('aftable', function ($expression) {
            return "<?php \$__aftableParams = {$expression}; ob_start(); ?>";
        });

        Blade::directive('endaftable', function () {
            return <<<'PHP'
<?php
    $__aftableTpl = trim(ob_get_clean());
    if ($__aftableTpl !== '') {
        $__aftableParams['customTemplate'] = $__aftableTpl;
    }
    echo \Livewire\Livewire::mount('aftable', $__aftableParams);
    unset($__aftableParams, $__aftableTpl);
?>
PHP;
        });

        // Load package views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'artflow-table');

        // The package's `test_*` demo tables and the demo/docs/generator routes
        // (/aftable/test, /aftable/docs, /aftable/generator) exist only when
        // `aftable.test_mode` is on. A host app never gets the tables or the
        // routes unless it opts in. Test mode is off by default and the package
        // test suite switches it on. The demo routes are additionally off in
        // production and when `aftable.routes.enabled` is false. See AUDIT.md
        // BUG-17 and STRUCT-09.
        if ($this->testMode()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            if ($this->demoRoutesEnabled()) {
                $this->loadRoutesFrom(__DIR__.'/../routes/test.php');
            }
        }

        // Publishable assets
        // NOTE: publishes the SAME directory that loadViewsFrom() reads. This
        // used to point at src/resources/views — a stale duplicate tree — so
        // `vendor:publish --tag=views` handed users unmaintained templates.
        // See AUDIT.md BUG-27.
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/artflow-table'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../config/aftable.php' => config_path('aftable.php'),
        ], 'aftable-config');

        // Blaze optimization — speeds up anonymous Blade components in the package.
        // Only activated when livewire/blaze is installed in the host application.
        $this->configureBlazeOptimization();

        // Restrict which component methods a browser may invoke. Livewire treats
        // every public method as an action, and this component has 400+ of them.
        // See AUDIT.md SEC-05 and Support/GuardsActions.
        Livewire::componentHook(GuardsActions::class);

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            // The three `af-table:test*` commands were removed in 1.6.0. They
            // exercised the legacy forks, asserted mostly via method_exists(),
            // and 8 of one command's 13 suites were stubs that reported PASS
            // without running anything. `composer test` replaces them with a
            // real PHPUnit suite.
            $this->commands([
                ClearPhantomColumnsCommand::class,
                MakeTableCommand::class,
                MigrateCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aftable.php', 'aftable');
    }

    /**
     * Should the demo/docs/generator routes be registered?
     *
     * Off in production regardless of config: these pages read demo tables and
     * expose a code generator, neither of which belongs on a public host.
     */
    protected function testMode(): bool
    {
        return (bool) config('aftable.test_mode', false);
    }

    protected function demoRoutesEnabled(): bool
    {
        if (! (bool) config('aftable.routes.enabled', true)) {
            return false;
        }

        return ! $this->app->environment('production');
    }

    /**
     * Apply Blaze component compilation for any anonymous Blade components
     * the package exposes. Gracefully skipped when Blaze is not installed.
     */
    protected function configureBlazeOptimization(): void
    {
        // `Blaze` is a FACADE: class_exists() on it is true the moment
        // livewire/blaze is installed, which says nothing about whether
        // BlazeServiceProvider has bound the `blaze` container entry yet. When
        // this provider booted first the app died with
        // `ReflectionException: Class "blaze" does not exist`.
        // Defer to booted() so provider order stops mattering, and check the
        // binding rather than the facade class. See AUDIT.md BUG-01.
        $this->app->booted(function () {
            if (! $this->app->bound('blaze')) {
                return;
            }

            $componentsPath = __DIR__.'/../resources/views/components';

            if (! is_dir($componentsPath)) {
                return;
            }

            try {
                Blaze::optimize()->in($componentsPath);
            } catch (\Throwable $e) {
                // Blaze is an optimisation, never a hard requirement.
                report($e);
            }
        });
    }
}
