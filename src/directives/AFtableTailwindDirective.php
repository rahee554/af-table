<?php

namespace ArtflowStudio\AFtable\Directives;

class AFtableTailwindDirective
{
    public static function render($expression)
    {
        return "<?php echo app('livewire')->mount('tailwind.datatable', {$expression})->html(); ?>";
    }
}
