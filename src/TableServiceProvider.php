<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Explicitly register the Livewire components
        Livewire::component('aftable-simple', \ArtflowStudio\Table\Http\Livewire\Datatable::class);
        Livewire::component('aftable', \ArtflowStudio\Table\Http\Livewire\DatatableTrait::class);
        Livewire::component('test-table-component', \ArtflowStudio\Table\Http\Livewire\TestTableComponent::class);

        // Register the custom Blade directives
        Blade::directive('AFtable', function ($expression) {
            // Dynamically mount the Datatable Livewire component and pass the array to the component
            return "<?php echo app('livewire')->mount('aftable', {$expression})->html(); ?>";
        });

        Blade::directive('AFtableTrait', function ($expression) {
            // Dynamically mount the DatatableTrait Livewire component and pass the array to the component
            return "<?php echo app('livewire')->mount('aftable-trait', {$expression})->html(); ?>";
        });

        // Load views from the resources/views folder in your package
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'artflow-table');
        
        // Register test routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/test.php');

        // Optionally publish the views to the app's resources/views/vendor directory
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/artflow-table'),
        ], 'views');

        // Optionally, publish assets (CSS/JS)
        $this->publishes([
            __DIR__ . '/resources/assets' => public_path('vendor/artflow-studio/table'),
        ], 'assets');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \ArtflowStudio\Table\Console\Commands\TestTraitCommand::class,
                \ArtflowStudio\Table\Console\Commands\ImprovedTestTraitCommand::class,
                \ArtflowStudio\Table\Console\Commands\ClearPhantomColumnsCommand::class,
                \ArtflowStudio\Table\Console\Commands\MigrateCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Register any services if needed
    }
}
