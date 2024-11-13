<?php

namespace ArtflowStudio\AFtable;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use ArtflowStudio\AFtable\Directives\AFtableDirective;
use ArtflowStudio\AFtable\Directives\AFtableTailwindDirective;

class AFtableServiceProvider extends ServiceProvider
{
    public function register()
    {
        //$this->mergeConfigFrom(__DIR__.'/../config/af-table.php', 'af-table');
    }

    public function boot()
    {
        // Load Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'af-table');

        // Register Blade Directives
        Blade::directive('afTable', [AFtableDirective::class, 'render']);
        Blade::directive('afTableTw', [AFtableTailwindDirective::class, 'render']);

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/af-table'),
        ], 'public');
    }
}
