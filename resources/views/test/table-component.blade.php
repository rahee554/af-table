<div>
    @livewire('aftable', [
        'model' => $model ?? null,
        'data' => $data ?? null,
        'vars' => $vars ?? [],
        'columns' => $columns,
        'filters' => $filters,
        'actions' => $actions,
        'tableId' => $tableId,
        'searchable' => true,
        'exportable' => true,
        'printable' => $printable ?? false,
        'printConfig' => $printConfig ?? [],
        'colvisBtn' => true,
        'colSort' => true,
        'records' => 25,
        'checkbox' => false,
        'index' => true,
    ])
</div>
