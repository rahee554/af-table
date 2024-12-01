<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class TableServiceProvider extends ServiceProvider
{
    public function boot()
{
    // Register the custom Blade directive
    Blade::directive('AFtable', function ($expression) {
        return "<?php echo app('view')->make('artflow-studio.table.' . {$expression}); ?>";
    });

    // Publish the views if needed
    $this->loadViewsFrom(__DIR__ . '/resources/views', 'artflow-studio.table');

    // Optional: Publish assets if needed
    $this->publishes([
        __DIR__ . '/resources/assets' => public_path('vendor/artflow-studio/table'),
    ], 'assets');
}


    public function register()
    {
        // You can register services here if needed
    }
}
