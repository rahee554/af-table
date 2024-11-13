<?php

namespace ArtflowStudio\Table\Exports;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DataTableExport implements FromCollection, WithHeadings
{
    protected $model;
    protected $columns;
    protected $filters;

    public function __construct($model, $columns, $filters)
    {
        $this->model = $model;
        $this->columns = $columns;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->model::all();
    }

    public function headings(): array
    {
        return collect($this->columns)->pluck('label')->toArray();
    }
}
