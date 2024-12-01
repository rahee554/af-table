<?php

namespace ArtflowStudio\Table;

use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DataTableExport;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\Blade;

class Datatable extends Component
{

    use WithPagination;

    // Public properties grouped for better clarity
    public $model;
    public $columns = [];
    public $visibleColumns = [];
    public $searchable = true;
    public $exportable = true;
    public $printable = true;
    public $checkbox = false;
    public $recordsPerPage = 10;
    public $search = '';
    public $sortColumn = null;
    public $sortDirection = 'asc';
    public $selectedRows = [];
    public $selectAll = false;
    public $filters = [];
    public $filterColumn = null;
    public $filterOperator = '=';
    public $filterValue = null;
    public $dateColumn = null;
    public $startDate = null;
    public $endDate = null;
    public $selectedColumn = null;
    public $numberOperator = '=';
    public $distinctValues = [];
    public $columnType = null;

    public $queryString = [
        'recordsPerPage' => ['except' => 10], // Default to 10 if not set
    ];

    protected $listeners = [
        'dateRangeSelected' => 'applyDateRange',
    ];

    public function mount($model, $columns, $filters = [])
    {
        $this->model = $model;
        $this->columns = $columns;
        $this->visibleColumns = array_fill_keys(array_column($columns, 'key'), true);

        // Set filters if passed, otherwise initialize as empty
        $this->filters = $filters;
    }

    // Search-related methods
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRecordsPerPage()
    {
        $this->resetPage();
    }

    // Column visibility toggling
    public function toggleColumnVisibility($columnKey)
    {
        $this->visibleColumns[$columnKey] = !$this->visibleColumns[$columnKey];
    }

    // Sorting-related methods
    public function toggleSort($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    // Export-related methods
    public function export($format)
    {
        $data = $this->getDataForExport(); // Prepare data for export
        if ($format === 'pdf') {
            return $this->exportPdf($data);
        }

        return Excel::download(new DataTableExport(
            $this->model,
            $this->columns,
            $this->getFilters(),
            $this->sortColumn,
            $this->sortDirection
        ), "export.{$format}");
    }

    public function exportPdf($data)
    {
        $pdf = PDF::loadView('exports.pdf.datatable', [
            'data' => $data,
            'columns' => $this->columns,
            'visibleColumns' => $this->visibleColumns,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'export.pdf');
    }

    public function getDataForExport()
    {
        return $this->query()->get(); // Get data for export
    }

    // Filter-related methods
    public function updatedSelectedColumn($column)
    {
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->selectedColumn = $column;
            $this->columnType = $filterDetails['type'];

            if (isset($filterDetails['relation'])) {
                // Fetch distinct values for related columns
                $relationName = $filterDetails['relation']['name'];
                $relatedColumn = $filterDetails['relation']['column'];

                $this->distinctValues = $this->model::with($relationName)
                    ->get()
                    ->pluck("$relationName.$relatedColumn")
                    ->unique()
                    ->toArray();
            } else {
                // Fetch distinct values for direct columns
                $this->distinctValues = $this->model::select($column)
                    ->distinct()
                    ->pluck($column)
                    ->toArray();
            }
        }
    }




    public function applyDateRange($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function applyColumnFilter($columnKey)
    {
        $this->filterColumn = $columnKey;
        $this->filterValue = $this->filterValue ?? ''; // Default empty value for filter
        $this->resetPage();
    }

    public function applyDateFilter()
    {
        $this->filterColumn = $this->dateColumn;
        $this->startDate = $this->startDate ?? '';
        $this->endDate = $this->endDate ?? '';
        $this->resetPage();
    }

    // Filter application logic
    protected function applyFilters(Builder $query)
    {
        if ($this->filterColumn && $this->filterValue !== null) {
            $isRelation = false;
            $relationDetails = null;

            // Check if the filterColumn is a relational column
            if (isset($this->columns[$this->filterColumn]['relation'])) {
                $isRelation = true;
                $relationDetails = explode(':', $this->columns[$this->filterColumn]['relation']);
            }

            // Handle filtering based on the column type
            switch ($this->columnType) {
                case 'number':
                    if ($isRelation && $relationDetails) {
                        [$relation, $attribute] = $relationDetails;
                        $query->whereHas($relation, function ($relQuery) use ($attribute) {
                            $relQuery->where($attribute, $this->numberOperator, $this->filterValue);
                        });
                    } else {
                        $query->where($this->filterColumn, $this->numberOperator, $this->filterValue);
                    }
                    break;

                case 'date':
                    if ($isRelation && $relationDetails) {
                        [$relation, $attribute] = $relationDetails;
                        $query->whereHas($relation, function ($relQuery) use ($attribute) {
                            $relQuery->whereDate($attribute, $this->filterValue);
                        });
                    } else {
                        $query->whereDate($this->filterColumn, $this->filterValue);
                    }
                    break;

                case 'select':
                    if ($isRelation && $relationDetails) {
                        [$relation, $attribute] = $relationDetails;
                        $query->whereHas($relation, function ($relQuery) use ($attribute) {
                            $relQuery->where($attribute, $this->filterValue);
                        });
                    } else {
                        $query->where($this->filterColumn, '=', $this->filterValue);
                    }
                    break;
            }
        }
    }



    // Query builder with sorting and filtering
    protected function query(): Builder
    {
        $query = $this->model::query();

        // Search functionality
        if ($this->searchable && $this->search) {
            $query->where(function ($query) {
                foreach ($this->columns as $column) {
                    // Skip non-database columns
                    if (empty($column['key']) || $column['key'] === 'actions') {
                        continue;
                    }

                    if ($this->visibleColumns[$column['key']]) {
                        if (isset($column['relation'])) {
                            [$relation, $attribute] = explode(':', $column['relation']);
                            $query->orWhereHas($relation, function ($relQ) use ($attribute) {
                                $relQ->where($attribute, 'like', '%' . $this->search . '%')
                                    ->orWhere('id', 'like', '%' . $this->search . '%');
                            });
                        } else {
                            $query->orWhere($column['key'], 'like', '%' . $this->search . '%');
                        }
                    }
                }
            });
        }

        // Apply filters for selected column and filter value
        if ($this->filterColumn && $this->filterValue) {
            $this->applyFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $query->whereBetween($this->dateColumn, [$this->startDate, $this->endDate]);
        }

        // Sorting
        if ($this->sortColumn) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }

        return $query;
    }


    // Additional utility methods
    public function getDistinctValues($column)
    {
        return $this->model::distinct()->pluck($column)->toArray();
    }

    public function renderRawHtml($rawTemplate, $row)
    {
        return Blade::render($rawTemplate, compact('row'));
    }

    public function getDynamicClass($column, $row)
    {
        $classes = [];
        if (isset($column['classCondition']) && is_array($column['classCondition'])) {
            foreach ($column['classCondition'] as $class => $condition) {
                if (is_callable($condition) && $condition($row)) {
                    $classes[] = $class;
                }
            }
        }
        return implode(' ', $classes);
    }

    // Render method
    public function render()
    {
        return view('artflow-studio.table.datatable', [
            'data' => $this->query()->paginate($this->recordsPerPage),
            'filters' => $this->filters,
        ]);
    }
}
