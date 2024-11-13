<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use ArtflowStudio\Table\Components\AFTable;

class AFTableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/Views', 'aftable');
        
        $this->publishes([
            __DIR__.'/../config/aftable.php' => config_path('aftable.php'),
        ], 'aftable-config');

        Livewire::component('aftable', AFTable::class);

        Blade::component('aftable', AFTable::class);

        Blade::directive('AFtable', function ($expression) {
            return "<?php echo \ArtflowStudio\Table\Components\AFTable::render($expression); ?>";
        });

        $this->loadAssetsFrom(__DIR__.'/../resources', 'aftable');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/aftable.php', 'aftable'
        );
    }

    protected function loadAssetsFrom($path, $packageName)
    {
        $this->publishes([
            $path => public_path("vendor/{$packageName}"),
        ], "{$packageName}-assets");

        $this->app['config']->set('filesystems.disks.'.$packageName, [
            'driver' => 'local',
            'root' => $path,
        ]);
    }
}