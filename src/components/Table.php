<?php

namespace ArtflowStudio\AFtable\Components;

use Livewire\Component;

class Table extends Component
{
    public $model;
    public $columns;
    public $filters;
    public $searchable;
    public $dateSearch;
    public $exportable;
    public $checkbox;
    public $printable;

    public function render()
    {
        return view('af-table::components.table');
    }
}
