<?php

namespace ArtflowStudio\Table;

use Illuminate\Support\Facades\Blade;

class AFtableDirective
{
    public static function render($expression)
    {
        $expression = trim($expression, '()');
        return "<?php echo Livewire::mount('artflow-studio::datatable', $expression)->html(); ?>";
    }
}