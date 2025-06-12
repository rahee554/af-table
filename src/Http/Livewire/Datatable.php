<?php

namespace ArtflowStudio\Table\Http\Livewire;


use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DataTableExport;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;

class Datatable extends Component
{
    use WithPagination;

    //*-------- Properties ---------*//
    public $model, $columns = [], $visibleColumns = [],
        $checkbox = false, $records = 10, $search = '', $sortColumn = null, $sortDirection = 'asc', $selectedRows = [],
        $selectAll = false, $filters = [], $filterColumn = null, $filterOperator = '=', $filterValue = null, $dateColumn = null,
        $startDate = null, $endDate = null, $selectedColumn = null, $numberOperator = '=', $distinctValues = [], $columnType = null,
        $actions = [];
    public $index = true; // Show index column by default

    //*-------- Optional Checks ---------*//
    public $searchable = true, $exportable = false, $printable = false, $colSort = true,
        $sort = 'desc', // For Initial sorting
        $refreshBtn = false; //refresh Button

    //*-------- Query String ---------*//
    public $queryString = [
        'records' => ['except' => 10], // Default to 10 if not set
        'search' => ['except' => ''],
        'sortColumn' => ['except' => null],
        'sortDirection' => ['except' => 'asc'],
        'filterColumn' => ['except' => null],
        'filterValue' => ['except' => null],
        'filterOperator' => ['except' => '='],
        'startDate' => ['except' => null],
        'endDate' => ['except' => null],
    ];

    //*-------- Listeners ---------*//
    protected $listeners = [
        'dateRangeSelected' => 'applyDateRange',
        'refreshTable' => '$refresh',
    ];

    //*-------- Mount Method ---------*//
    public function mount($model, $columns, $filters = [], $actions = [], $index = true)
    {
        $this->model = $model;
        // Re-key columns by 'key'
        $this->columns = collect($columns)->mapWithKeys(function ($column) {
            return [$column['key'] => $column];
        })->toArray();

        // Session key for column visibility (unique per model/table)
        $sessionKey = $this->getColumnVisibilitySessionKey();

        // Try to load column visibility from session, else default
        $sessionVisibility = Session::get($sessionKey);
        if (is_array($sessionVisibility)) {
            $this->visibleColumns = $sessionVisibility;
        } else {
            $this->visibleColumns = collect($this->columns)->mapWithKeys(function ($column) {
                return [$column['key'] => empty($column['hide'])];
            })->toArray();
            // Save default to session
            Session::put($sessionKey, $this->visibleColumns);
        }

        $this->filters = $filters;
        $this->actions = $actions;
        $this->index = $index;

        if (empty($this->sortColumn)) {
            $first = collect($columns)->first();
            $this->sortColumn = $first['key'];
            $this->sortDirection = $this->sort;
        }
    }

    //*-------- Search-related Methods ---------*//
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function refreshTable()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function updatedrecords()
    {
        $this->resetPage();
    }

    //*-------- Column Visibility Methods ---------*//
    public function toggleColumnVisibility($columnKey)
    {
        $this->visibleColumns[$columnKey] = !$this->visibleColumns[$columnKey];
        // Store updated visibility in session
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
    }

    protected function getColumnVisibilitySessionKey()
    {
        // Use model class name as part of the key for uniqueness
        $modelName = is_string($this->model) ? $this->model : (is_object($this->model) ? get_class($this->model) : 'datatable');
        return 'datatable_visible_columns_' . md5($modelName . '_' . static::class);
    }

    //*-------- Sorting Methods ---------*//
    public function toggleSort($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    //*-------- Export Methods ---------*//
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
        // Only select visible columns for export
        $query = $this->query();
        $visibleKeys = array_keys(array_filter($this->visibleColumns));
        $selects = [];
        foreach ($visibleKeys as $key) {
            if (isset($this->columns[$key]['relation'])) continue; // skip relation columns for select
            $selects[] = $key;
        }
        if ($selects) {
            $query->select($selects);
        }
        return $query->get();
    }

    //*-------- Filter-related Methods ---------*//
    public function updatedSelectedColumn($column)
    {
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->selectedColumn = $column;
            $this->columnType = $filterDetails['type'];

            if (isset($filterDetails['relation'])) {
                [$relationName, $relatedColumn] = explode(':', $filterDetails['relation']);
                // Use distinct query for performance
                $this->distinctValues = $this->model::query()
                    ->joinRelation($relationName)
                    ->distinct()
                    ->pluck("$relationName.$relatedColumn")
                    ->filter()
                    ->values()
                    ->toArray();
            } else {
                $this->distinctValues = $this->model::query()
                    ->select($column)
                    ->distinct()
                    ->pluck($column)
                    ->filter()
                    ->values()
                    ->toArray();
            }
        }
    }

    public function applyDateRange($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->resetPage();
    }

    public function applyColumnFilter($columnKey)
    {
        $this->filterColumn = $columnKey;
        $filterDetails = $this->filters[$columnKey] ?? null;
        if ($filterDetails) {
            $this->columnType = $filterDetails['type'] ?? 'select';
        } else {
            $this->columnType = 'select';
        }
        $this->filterValue = $this->filterValue ?? '';
        $this->resetPage();
    }

    //*-------- Filter Application Logic ---------*//
    protected function applyFilters(Builder $query)
    {
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            $isRelation = false;
            $relationDetails = null;

            $relationString = null;
            if (isset($this->columns[$this->filterColumn]['relation'])) {
                $relationString = $this->columns[$this->filterColumn]['relation'];
            } elseif (isset($this->filters[$this->filterColumn]['relation'])) {
                $relationString = $this->filters[$this->filterColumn]['relation'];
            }

            if ($relationString) {
                $isRelation = true;
                $relationDetails = explode(':', $relationString);
            }

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
                default:
                    $operator = isset($this->filters[$this->filterColumn]['type']) && $this->filters[$this->filterColumn]['type'] === 'select' ? '=' : 'like';
                    if ($isRelation && $relationDetails) {
                        [$relation, $attribute] = $relationDetails;
                        $query->whereHas($relation, function ($relQuery) use ($attribute, $operator) {
                            if ($operator === '=') {
                                $relQuery->where($attribute, $this->filterValue);
                            } else {
                                $relQuery->where($attribute, 'like', '%' . $this->filterValue . '%');
                            }
                        });
                    } else {
                        if ($operator === '=') {
                            $query->where($this->filterColumn, $this->filterValue);
                        } else {
                            $query->where($this->filterColumn, 'like', '%' . $this->filterValue . '%');
                        }
                    }
                    break;
            }
        }
    }

    //*-------- Query Builder with Sorting and Filtering ---------*//
    protected function query(): Builder
    {
        $query = $this->model::query();

        // Only eager load relations for visible columns
        $relations = [];
        foreach ($this->columns as $column) {
            if (isset($column['relation']) && ($this->visibleColumns[$column['key']] ?? false)) {
                [$relation, ] = explode(':', $column['relation']);
                $relations[] = $relation;
            }
        }
        if (!empty($relations)) {
            $query->with(array_unique($relations));
        }

        // Only select visible columns for main table (skip relations)
        $visibleKeys = array_keys(array_filter($this->visibleColumns));
        $selects = [];
        foreach ($visibleKeys as $key) {
            if (isset($this->columns[$key]['relation'])) continue;
            $selects[] = $key;
        }
        if ($selects) {
            $query->select($selects);
        }

        // Debounced search for better performance
        if ($this->searchable && $this->search) {
            $search = $this->search;
            $query->where(function ($query) use ($search) {
                foreach ($this->columns as $column) {
                    if (empty($column['key']) || $column['key'] === 'actions') continue;
                    if ($this->visibleColumns[$column['key']]) {
                        if (isset($column['relation'])) {
                            [$relation, $attribute] = explode(':', $column['relation']);
                            $query->orWhereHas($relation, function ($relQ) use ($attribute, $search) {
                                $relQ->where($attribute, 'like', '%' . $search . '%');
                            });
                        } else {
                            $query->orWhere($column['key'], 'like', '%' . $search . '%');
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

        // Dynamic sorting for relation columns
        if ($this->sortColumn) {
            $sortColumnConfig = $this->columns[$this->sortColumn] ?? null;
            if ($sortColumnConfig && isset($sortColumnConfig['relation'])) {
                [$relation, $attribute] = explode(':', $sortColumnConfig['relation']);
                $modelInstance = new ($this->model);
                $relationObj = $modelInstance->$relation();
                $relationTable = $relationObj->getRelated()->getTable();

                if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $parentTable = $modelInstance->getTable();
                    $foreignKey = $relationObj->getForeignKeyName();
                    $ownerKey = $relationObj->getOwnerKeyName();
                    $query->leftJoin(
                        $relationTable,
                        $parentTable . '.' . $foreignKey,
                        '=',
                        $relationTable . '.' . $ownerKey
                    );
                } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
                          $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                    $parentTable = $modelInstance->getTable();
                    $foreignKey = $relationObj->getForeignKeyName();
                    $localKey = $relationObj->getLocalKeyName();
                    $query->leftJoin(
                        $relationTable,
                        $relationTable . '.' . $foreignKey,
                        '=',
                        $parentTable . '.' . $localKey
                    );
                }
                $query->orderBy($relationTable . '.' . $attribute, $this->sortDirection)
                    ->select($modelInstance->getTable() . '.*');
            } else {
                $query->orderBy($this->sortColumn, $this->sortDirection);
            }
        }

        return $query;
    }

    //*-------- Utility Methods ---------*//
    public function getDistinctValues($column)
    {
        // Cache distinct values for performance
        $cacheKey = 'datatable_distinct_' . md5($this->model . '_' . $column);
        return cache()->remember($cacheKey, 60, function () use ($column) {
            foreach ($this->columns as $col) {
                if (isset($col['relation'])) {
                    [$relation, $attribute] = explode(':', $col['relation']);
                    if ($attribute === $column) {
                        $modelInstance = new ($this->model);
                        return $modelInstance->$relation()
                            ->select($attribute)
                            ->distinct()
                            ->pluck($attribute)
                            ->filter()
                            ->values()
                            ->toArray();
                    }
                }
            }
            foreach ($this->filters as $filter) {
                if (isset($filter['relation'])) {
                    [$relation, $attribute] = explode(':', $filter['relation']);
                    if ($attribute === $column) {
                        $modelInstance = new ($this->model);
                        return $modelInstance->$relation()
                            ->select($attribute)
                            ->distinct()
                            ->pluck($attribute)
                            ->filter()
                            ->values()
                            ->toArray();
                    }
                }
            }
            return $this->model::query()->select($column)->distinct()->pluck($column)->filter()->values()->toArray();
        });
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

    //*-------- Render Method ---------*//
    public function render()
    {
        return view('artflow-studio.table::datatable', [
            'data' => $this->query()->paginate($this->records),
            'filters' => $this->filters,
            'columns' => $this->columns,
            'visibleColumns' => $this->visibleColumns,
            'checkbox' => $this->checkbox,
            'actions' => $this->actions,
            'searchable' => $this->searchable,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'records' => $this->records,
            'index' => $this->index,
        ]);
    }
}
