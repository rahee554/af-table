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

    //*----------- Properties -----------*//
    public $model, $columns, $visibleColumns = [],
        $checkbox = false, $records = 10, $search = '', $sortColumn = null, $sortDirection = 'asc', $selectedRows = [],
        $selectAll = false, $filters = [], $filterColumn = null, $filterOperator = '=', $filterValue = null, $dateColumn = null,
        $startDate = null, $endDate = null, $selectedColumn = null, $numberOperator = '=', $distinctValues = [], $columnType = null,
        $actions = [];
    public $index = true; // Show index column by default
    public $tableId = null; // Add unique table identifier
    public $query = null; // Custom query constraints
    public $colvisBtn = true;
    //*----------- Optional Configuration -----------*//
    public $searchable = true, $exportable = false, $printable = false, $colSort = true,
        $sort = 'desc', // For Initial sorting
        $refreshBtn = false; //refresh Button - false by default, can be enabled by passing true

    //*----------- Query String Parameters -----------*//
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

    //*----------- Event Listeners -----------*//
    protected $listeners = [
        'dateRangeSelected' => 'applyDateRange',
        'refreshTable' => '$refresh',
    ];

    //*----------- Component Initialization -----------*//
    public function mount($model, $columns, $filters = [], $actions = [], $index = true, $tableId = null, $query = null)
    {
        $this->model = $model;
        $this->tableId = $tableId ?? (is_string($model) ? $model : (is_object($model) ? get_class($model) : uniqid('datatable_')));
        $this->query = $query; // Store custom query constraints

        // Re-key columns by 'key'
        $this->columns = collect($columns)->mapWithKeys(function ($column) {
            return [$column['key'] => $column];
        })->toArray();

        // Session key for column visibility (unique per model/table and tableId)
        $sessionKey = $this->getColumnVisibilitySessionKey();

        // Try to load column visibility from session, else default
        $sessionVisibility = Session::get($sessionKey);
        if (is_array($sessionVisibility)) {
            $this->visibleColumns = $this->getValidatedVisibleColumns($sessionVisibility);
            Session::put($sessionKey, $this->visibleColumns);
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
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

    //*----------- Column Visibility Management -----------*//
    protected function getDefaultVisibleColumns()
    {
        return collect($this->columns)->mapWithKeys(function ($column) {
            return [$column['key'] => empty($column['hide'])];
        })->toArray();
    }

    protected function getValidatedVisibleColumns($sessionVisibility)
    {
        $validSessionVisibility = [];
        foreach ($this->columns as $columnKey => $columnConfig) {
            if (array_key_exists($columnKey, $sessionVisibility)) {
                $validSessionVisibility[$columnKey] = $sessionVisibility[$columnKey];
            } else {
                $validSessionVisibility[$columnKey] = empty($columnConfig['hide']);
            }
        }
        return $validSessionVisibility;
    }

    public function toggleColumnVisibility($columnKey)
    {
        $this->visibleColumns[$columnKey] = !$this->visibleColumns[$columnKey];
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
    }

    protected function getColumnVisibilitySessionKey()
    {
        // Use model class name and tableId for uniqueness
        $modelName = is_string($this->model) ? $this->model : (is_object($this->model) ? get_class($this->model) : 'datatable');
        return 'datatable_visible_columns_' . md5($modelName . '_' . static::class . '_' . $this->tableId);
    }

    public function clearColumnVisibilitySession()
    {
        $sessionKey = $this->getColumnVisibilitySessionKey();
        Session::forget($sessionKey);

        // Reset to defaults
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        Session::put($sessionKey, $this->visibleColumns);
    }

    //*----------- Search Functionality -----------*//
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

    //*----------- Sorting Functionality -----------*//
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

    //*----------- Filter Management -----------*//
    public function updatedFilterValue()
    {
        $this->resetPage();
    }

    public function updatedFilterColumn()
    {
        $this->filterValue = null;
        $this->resetPage();
    }

    public function updatedSelectedColumn($column)
    {
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->selectedColumn = $column;
            $this->columnType = $filterDetails['type'];

            if (isset($filterDetails['relation'])) {
                [$relationName, $relatedColumn] = explode(':', $filterDetails['relation']);
                // Eager load relation and get distinct values
                $modelInstance = new ($this->model);
                $distinct = $modelInstance->with($relationName)
                    ->get()
                    ->pluck($relationName)
                    ->pluck($relatedColumn)
                    ->unique()
                    ->filter()
                    ->values()
                    ->toArray();
                $this->distinctValues = $distinct;
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

            // Determine operator and value based on filter type
            $filterType = isset($this->filters[$this->filterColumn]['type']) ? $this->filters[$this->filterColumn]['type'] : 'text';
            $operator = $this->filterOperator ?? $this->getDefaultOperator($filterType);
            $value = $this->prepareFilterValue($filterType, $operator, $this->filterValue);

            if ($isRelation && $relationDetails) {
                [$relation, $attribute] = $relationDetails;
                $query->whereHas($relation, function ($relQuery) use ($attribute, $operator, $value, $filterType) {
                    if ($filterType === 'date') {
                        $relQuery->whereDate($attribute, $operator, $value);
                    } else {
                        $relQuery->where($attribute, $operator, $value);
                    }
                });
            } else {
                if ($filterType === 'date') {
                    $query->whereDate($this->filterColumn, $operator, $value);
                } else {
                    $query->where($this->filterColumn, $operator, $value);
                }
            }
        }
    }

    protected function getDefaultOperator($filterType)
    {
        switch ($filterType) {
            case 'select':
                return '=';
            case 'integer':
            case 'number':
                return '=';
            case 'date':
                return '=';
            case 'text':
            default:
                return 'LIKE';
        }
    }

    protected function prepareFilterValue($filterType, $operator, $value)
    {
        if ($filterType === 'text' && strtoupper($operator) === 'LIKE') {
            return "%{$value}%";
        }
        return $value;
    }

    public function getDistinctValues($columnKey)
    {
        if (isset($this->columns[$columnKey]['relation'])) {
            [$relation, $attribute] = explode(':', $this->columns[$columnKey]['relation']);
            $modelInstance = new ($this->model);
            $relationObj = $modelInstance->$relation();
            $relatedTable = $relationObj->getRelated()->getTable();

            // Use join to get distinct values from related table
            $query = $this->model::query()
                ->join($relatedTable, $modelInstance->getTable() . '.' . $relationObj->getForeignKeyName(), '=', $relatedTable . '.' . $relationObj->getOwnerKeyName())
                ->distinct()
                ->pluck($relatedTable . '.' . $attribute)
                ->filter()
                ->values()
                ->toArray();
            return $query;
        } else {
            return $this->model::query()
                ->distinct()
                ->pluck($columnKey)
                ->filter()
                ->values()
                ->toArray();
        }
    }

    //*----------- Query Builder -----------*//
    protected function query(): Builder
    {
        $query = $this->model::query();

        // Apply custom query constraints first with error handling
        if ($this->query) {
            try {
                if (is_array($this->query)) {
                    // If query is an array of constraints
                    foreach ($this->query as $constraint) {
                        if (is_array($constraint)) {
                            // Handle array format: ['column', 'operator', 'value'] or ['column', 'value']
                            if (count($constraint) === 3) {
                                [$column, $operator, $value] = $constraint;
                                $query->where($column, $operator, $value);
                            } elseif (count($constraint) === 2) {
                                [$column, $value] = $constraint;
                                $query->where($column, $value);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log the error but don't break the table
                logger()->error('AFTable custom query error: ' . $e->getMessage());
            }
        }

        // Eager load relations for visible columns and filter columns
        $relations = [];
        foreach ($this->columns as $column) {
            if (isset($column['relation']) && ($this->visibleColumns[$column['key']] ?? false)) {
                [$relation, ] = explode(':', $column['relation']);
                $relations[] = $relation;
            }
        }
        
        // Also load relations needed for raw templates that reference relations
        foreach ($this->columns as $column) {
            if (isset($column['raw']) && ($this->visibleColumns[$column['key']] ?? false)) {
                // Check if raw template references any relations
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)->/', $column['raw'], $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $relationName) {
                        $relations[] = $relationName;
                    }
                }
            }
        }
        
        // Also load relations needed for filters
        foreach ($this->filters ?? [] as $filterKey => $filterConfig) {
            if (isset($filterConfig['relation']) && isset($this->columns[$filterKey]['relation'])) {
                [$relation, ] = explode(':', $this->columns[$filterKey]['relation']);
                $relations[] = $relation;
            }
        }
        
        if (!empty($relations)) {
            $query->with(array_unique($relations));
        }

        // Select visible columns and filter columns for main table (skip relations)
        $visibleKeys = array_keys(array_filter($this->visibleColumns));
        $filterKeys = array_keys($this->filters ?? []);
        $columnKeys = array_keys($this->columns ?? []);
        $allNeededKeys = array_unique(array_merge($visibleKeys, $filterKeys, $columnKeys));

        $selects = [];
        foreach ($allNeededKeys as $key) {
            if (isset($this->columns[$key])) {
                // Include the foreign key column for relation columns
                if (isset($this->columns[$key]['relation'])) {
                    // Add the foreign key (e.g., category_id for category relation)
                    if (!in_array($key, $selects)) {
                        $selects[] = $key;
                    }
                } else {
                    // Regular column
                    try {
                        $modelInstance = new ($this->model);
                        if (!in_array($key, $modelInstance->getHidden())) {
                            $selects[] = $key;
                        }
                    } catch (\Exception $e) {
                        $selects[] = $key;
                    }
                }
            }
        }

        // Always include id column if it exists and is not hidden
        if (!in_array('id', $selects)) {
            try {
                $modelInstance = new ($this->model);
                if (!in_array('id', $modelInstance->getHidden())) {
                    $selects[] = 'id';
                }
            } catch (\Exception $e) {
                $selects[] = 'id';
            }
        }

        // Dynamically detect columns needed for actions by scanning for $row->xxx in all actions
        $actionColumns = [];
        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    // Only add if not a relation (no '->' in name)
                    if (strpos($columnName, '->') === false && !in_array($columnName, $selects)) {
                        $actionColumns[] = $columnName;
                    }
                }
            }
        }
        foreach (array_unique($actionColumns) as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }

        // Dynamically detect columns needed for raw templates
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();
        foreach ($rawTemplateColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
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

    //*----------- Template Detection Helpers -----------*//
    protected function getColumnsNeededForActions()
    {
        $neededColumns = [];
        
        foreach ($this->actions as $action) {
            // Get the action template string (handle both 'raw' key and direct string)
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            
            // Match Blade variables like {{$row->column_name}}
            preg_match_all('/\{\{\s*\$row->([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $template, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    $neededColumns[] = $columnName;
                }
            }
        }
        
        return array_unique($neededColumns);
    }

    protected function getColumnsNeededForRawTemplates()
    {
        $neededColumns = [];
        
        foreach ($this->columns as $column) {
            if (isset($column['raw'])) {
                // Use a more comprehensive pattern to catch all $row->column references
                $patterns = [
                    '/\{\{\s*.*?\$row->([a-zA-Z_][a-zA-Z0-9_]*)\s*.*?\}\}/',  // {{ anything with $row->column }}
                    '/\$row->([a-zA-Z_][a-zA-Z0-9_]*)(?![a-zA-Z0-9_]|->)/',    // $row->column not followed by more chars or ->
                ];
                
                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $column['raw'], $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $columnName) {
                            // Skip if this is a relation reference (check if $row->columnName-> exists)
                            if (strpos($column['raw'], '$row->' . $columnName . '->') !== false) {
                                continue;
                            }
                            
                            // Don't add relation names as columns
                            $isRelationName = false;
                            foreach ($this->columns as $col) {
                                if (isset($col['relation'])) {
                                    [$relationName, ] = explode(':', $col['relation']);
                                    if ($columnName === $relationName) {
                                        $isRelationName = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!$isRelationName) {
                                $neededColumns[] = $columnName;
                            }
                        }
                    }
                }
            }
        }
        
        return array_unique($neededColumns);
    }

    //*----------- Export Functionality -----------*//
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
        // Select visible columns for export (but build query properly first)
        $query = $this->query();
        $visibleKeys = array_keys(array_filter($this->visibleColumns));
        $selects = [];
        foreach ($visibleKeys as $key) {
            if (isset($this->columns[$key]) && !isset($this->columns[$key]['relation'])) {
                $selects[] = $key;
            }
        }
        
        // Always include id for export
        if (!in_array('id', $selects)) {
            $selects[] = 'id';
        }
        
        if ($selects) {
            $query->select($selects);
        }
        return $query->get();
    }

    //*----------- Utility Methods -----------*//
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

    //*----------- Component Render -----------*//
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
