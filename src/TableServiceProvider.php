<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ArtflowStudio\Table\Components\Datatable;

class TableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'artflow-studio');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/artflow-studio/table'),
        ], 'artflow-table-views');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/artflow-studio/table'),
        ], 'artflow-table-assets');

        $this->publishes([
            __DIR__.'/../config/aftable.php' => config_path('aftable.php'),
        ], 'artflow-table-config');

        Livewire::component('artflow-studio::datatable', Datatable::class);

        Blade::directive('AFtable', function ($expression) {
            return "<?php echo \ArtflowStudio\Table\AFtableDirective::render($expression); ?>";
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/aftable.php', 'aftable'
        );
    }
}