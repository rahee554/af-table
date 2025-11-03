<div>
    @livewire('aftable', [
        'model' => $model,
        'columns' => $columns,
        'filters' => $filters,
        'actions' => $actions,
        'tableId' => $tableId,
        'searchable' => true,
        'exportable' => true,
        'printable' => false,
        'colvisBtn' => true,
        'colSort' => true,
        'records' => 25,
        'checkbox' => false,
        'index' => true,
    ])
</div>
