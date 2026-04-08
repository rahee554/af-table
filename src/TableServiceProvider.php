<?php

namespace ArtflowStudio\Table;

use ArtflowStudio\Table\Commands\AFTableTestCommand;
use ArtflowStudio\Table\Console\Commands\ClearPhantomColumnsCommand;
use ArtflowStudio\Table\Console\Commands\ImprovedTestTraitCommand;
use ArtflowStudio\Table\Console\Commands\MigrateCommand;
use ArtflowStudio\Table\Console\Commands\TestTraitCommand;
use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Http\Livewire\Datatable;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use ArtflowStudio\Table\Http\Livewire\TestTableComponent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;

class TableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('aftable-simple', Datatable::class);
        Livewire::component('aftable', AftableComponent::class);
        Livewire::component('aftable-legacy', DatatableTrait::class); // kept as backup alias
        Livewire::component('test-table-component', TestTableComponent::class);

        // Simple shorthand directives — equivalent to @livewire('aftable', [...])
        // Usage: @AFtable(['model' => App\Models\User::class, 'columns' => [...]])
        Blade::directive('AFtable', function ($expression) {
            return "<?php echo \Livewire\Livewire::mount('aftable', {$expression})->html(); ?>";
        });

        Blade::directive('AFtableTrait', function ($expression) {
            return "<?php echo \Livewire\Livewire::mount('aftable', {$expression})->html(); ?>";
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
    echo \Livewire\Livewire::mount('aftable', $__aftableParams)->html();
    unset($__aftableParams, $__aftableTpl);
?>
PHP;
        });

        // Load package views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'artflow-table');

        // Load test routes
        $this->loadRoutesFrom(__DIR__.'/../routes/test.php');

        // Publishable assets
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/artflow-table'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/artflow-studio/table/assets'),
        ], 'assets');

        // Blaze optimization — speeds up anonymous Blade components in the package.
        // Only activated when livewire/blaze is installed in the host application.
        $this->configureBlazeOptimization();

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                AFTableTestCommand::class,
                TestTraitCommand::class,
                ImprovedTestTraitCommand::class,
                ClearPhantomColumnsCommand::class,
                MigrateCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        //
    }

    /**
     * Apply Blaze component compilation for any anonymous Blade components
     * the package exposes. Gracefully skipped when Blaze is not installed.
     */
    protected function configureBlazeOptimization(): void
    {
        if (! class_exists(Blaze::class)) {
            return;
        }

        $componentsPath = __DIR__.'/../resources/views/components';

        if (is_dir($componentsPath)) {
            Blaze::optimize()->in($componentsPath);
        }
    }
}
