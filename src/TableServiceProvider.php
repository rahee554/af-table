<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Explicitly register the Livewire component
        Livewire::component('aftable', \ArtflowStudio\Table\Http\Livewire\Datatable::class);

        // Register the custom Blade directive
        Blade::directive('AFtable', function ($expression) {
            // Dynamically mount the Datatable Livewire component and pass the array to the component
            return "<?php echo app('livewire')->mount('aftable', {$expression})->html(); ?>";
        });

        // Load views from the resources/views folder in your package
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'artflow-studio.table');
        

        // Optionally publish the views to the app's resources/views/vendor directory
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/artflow-studio/table'),
        ], 'views');

        // Optionally, publish assets (CSS/JS)
        $this->publishes([
            __DIR__ . '/resources/assets' => public_path('vendor/artflow-studio/table'),
        ], 'assets');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \ArtflowStudio\Table\Commands\AFTableTestCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Register any services if needed
    }
}
