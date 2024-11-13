<?php

namespace ArtflowStudio\Table\Helpers;

class BladeDirective
{
    public static function renderDirective($expression)
    {
        return "<?php echo \Livewire\Livewire::mount('artflow-studio.table.components.datatable', {$expression})->html(); ?>";
    }
}
