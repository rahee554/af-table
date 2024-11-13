<?php

namespace ArtflowStudio\AFtable\Directives;

class AFtableDirective
{
    public static function render($expression)
    {
        return "<?php echo app('livewire')->mount('datatable', {$expression})->html(); ?>";
    }
}
