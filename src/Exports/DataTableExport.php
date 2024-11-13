<?php

namespace ArtflowStudio\Table\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DataTableExport implements FromCollection, WithHeadings
{
    protected $model;
    protected $columns;
    protected $filters;
    protected $sortColumn;
    protected $sortDirection;

    public function __construct($model, $columns, $filters, $sortColumn, $sortDirection)
    {
        $this->model = $model;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->sortColumn = $sortColumn;
        $this->sortDirection = $sortDirection;
    }

    public function collection()
    {
        $query = $this->model::query();

        // Apply filters and sorting
        // ... (implement filtering and sorting logic here)

        return $query->get();
    }

    public function headings(): array
    {
        return collect($this->columns)->pluck('label')->toArray();
    }
}