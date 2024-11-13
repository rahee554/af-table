<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use ArtflowStudio\Table\Helpers\BladeDirective;

class ArtflowTableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish assets and views
        $this->publishes([
            __DIR__ . '/../resources/views/components' => resource_path('views/vendor/artflow-studio/table/components'),
            __DIR__ . '/../resources/assets' => public_path('vendor/artflow-studio/table'),
        ], 'artflow-table');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'artflow-studio');

        // Register Blade directive
        Blade::directive('AFtable', [BladeDirective::class, 'renderDirective']);
    }

    public function register()
    {
        // Register services, if needed
    }
}
