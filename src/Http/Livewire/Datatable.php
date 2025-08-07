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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Datatable extends Component
{
    use WithPagination;

    //*----------- Properties -----------*//
    public $model, $columns = [], $visibleColumns = [],
    $checkbox = false, $records = 10, $search = '', $sortColumn = null, $sortDirection = 'asc', $selectedRows = [],
    $selectAll = false, $filters = [], $filterColumn = null, $filterOperator = '=', $filterValue = null, $dateColumn = null,
    $startDate = null, $endDate = null, $selectedColumn = null, $numberOperator = '=', $distinctValues = [], $columnType = null,
    $actions = [];
    public $index = false; // Changed: Show index column false by default
    public $tableId = null;
    public $query = null;
    public $colvisBtn = true;

    // Performance optimization properties
    protected $cachedRelations = null;
    protected $cachedSelectColumns = null;
    protected $distinctValuesCacheTime = 300; // 5 minutes
    protected $maxDistinctValues = 1000; // Limit for memory management

    //*----------- Optional Configuration -----------*//
    public $searchable = true, $exportable = false, $printable = false, $colSort = true,
    $sort = 'desc',
    $refreshBtn = false;

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
    public function mount($model, $columns, $filters = [], $actions = [], $index = false, $tableId = null, $query = null)
    {
        $this->model = $model;
        $this->tableId = $tableId ?? (is_string($model) ? $model : (is_object($model) ? get_class($model) : uniqid('datatable_')));
        $this->query = $query;

        // Pre-cache relations and select columns for performance
        $this->initializeColumnConfiguration($columns);

        // Re-key columns by 'key' or 'function' with fallback
        $this->columns = collect($columns)->mapWithKeys(function ($column, $index) {
            // Priority: function > key > auto-generated
            if (isset($column['function'])) {
                $identifier = $column['function'];
            } elseif (isset($column['key'])) {
                $identifier = $column['key'];
            } else {
                $identifier = 'col_' . $index; // Fallback for columns without key or function
            }
            return [$identifier => $column];
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
        $this->index = $index; // Use passed value, defaults to false

        // Optimize initial sort column selection
        if (empty($this->sortColumn)) {
            $this->sortColumn = $this->getOptimalSortColumn();
            $this->sortDirection = $this->sort;
        }
    }

    //*----------- Performance Optimization Methods -----------*//
    protected function initializeColumnConfiguration($columns)
    {
        // Pre-calculate relations and select columns for better performance
        $this->cachedRelations = $this->calculateRequiredRelations($columns);
        $this->cachedSelectColumns = $this->calculateSelectColumns($columns);
    }

    protected function calculateRequiredRelations($columns): array
    {
        $relations = [];

        foreach ($columns as $columnKey => $column) {
            // Skip if column is not visible to avoid loading unnecessary relations
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            if (isset($column['relation'])) {
                [$relation, $attribute] = explode(':', $column['relation']);
                
                // Handle nested relationships like 'student.user'
                if (strpos($relation, '.') !== false) {
                    $relationParts = explode('.', $relation);
                    $currentRelation = '';
                    foreach ($relationParts as $part) {
                        $currentRelation .= ($currentRelation ? '.' : '') . $part;
                        $relations[] = $currentRelation;
                    }
                } else {
                    $relations[] = $relation;
                }
                
                // Handle nested attributes like 'user.name' by adding the nested relation
                if (strpos($attribute, '.') !== false) {
                    $attributeParts = explode('.', $attribute);
                    if (count($attributeParts) > 1) {
                        // Add the nested relation path
                        $nestedRelation = $relation . '.' . $attributeParts[0];
                        $relations[] = $nestedRelation;
                    }
                }
            }

            // Scan raw templates for relation references
            if (isset($column['raw'])) {
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)->/', $column['raw'], $matches);
                if (!empty($matches[1])) {
                    $relations = array_merge($relations, $matches[1]);
                }
            }
        }

        // Include filter relations
        foreach ($this->filters ?? [] as $filterKey => $filterConfig) {
            if (isset($filterConfig['relation']) && isset($columns[$filterKey]['relation'])) {
                [$relation,] = explode(':', $columns[$filterKey]['relation']);
                $relations[] = $relation;
            }
        }

        return array_unique($relations);
    }

    protected function calculateSelectColumns($columns): array
    {
        $selects = ['id']; // Always include ID

        foreach ($columns as $columnKey => $column) {
            // Skip non-visible columns for performance
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            if (isset($column['function']))
                continue;

            if (isset($column['relation'])) {
                // Use the foreign key for the relation if 'key' is not a valid column
                $fk = null;
                if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                    $fk = $column['key'];
                } else {
                    // Try to guess foreign key from relation name
                    [$relationName,] = explode(':', $column['relation']);
                    $guessedFk = $relationName . '_id';
                    if ($this->isValidColumn($guessedFk)) {
                        $fk = $guessedFk;
                    }
                }
                if ($fk && !in_array($fk, $selects)) {
                    $selects[] = $fk;
                }
                continue;
            }

            if (isset($column['key']) && !in_array($column['key'], $selects)) {
                if ($this->isValidColumn($column['key'])) {
                    $selects[] = $column['key'];
                }
            }
        }

        // Add columns needed for actions and raw templates
        $actionColumns = $this->getColumnsNeededForActions();
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();

        // Filter out invalid columns
        $validActionColumns = array_filter($actionColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        $validRawTemplateColumns = array_filter($rawTemplateColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        // --- Ensure action columns are always included, even if not in $this->columns ---
        foreach ($validActionColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }
        // --- Same for raw template columns ---
        foreach ($validRawTemplateColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }

        // --- Also, check for action columns that are NOT in $this->columns and add them to the query builder ---
        // This ensures $row->uuid and similar are always available
        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    // Only add if it's a valid column and not already in selects
                    if ($this->isValidColumn($columnName) && !in_array($columnName, $selects)) {
                        $selects[] = $columnName;
                    }
                }
            }
        }

        return array_unique($selects);
    }

    protected function getOptimalSortColumn(): ?string
    {
        // Find first indexed column for better sort performance
        $indexedColumns = ['id', 'created_at', 'updated_at']; // Common indexed columns

        foreach ($this->columns as $column) {
            if (isset($column['key']) && !isset($column['function'])) {
                if (in_array($column['key'], $indexedColumns)) {
                    return $column['key'];
                }
            }
        }

        // Fallback to first sortable column
        foreach ($this->columns as $column) {
            if (isset($column['key']) && !isset($column['function'])) {
                return $column['key'];
            }
        }

        return null;
    }

    //*----------- Column Visibility Management -----------*//
    protected function getDefaultVisibleColumns()
    {
        return collect($this->columns)->mapWithKeys(function ($column, $identifier) {
            return [$identifier => empty($column['hide'])];
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
        $this->search = $this->sanitizeSearch($this->search);
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
        if (!$this->isAllowedColumn($column)) {
            return; // Ignore or handle invalid column
        }
        $this->sortColumn = $column;
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    //*----------- Optimized Filter Management -----------*//
    public function updatedSelectedColumn($column)
    {
        if (!$this->isAllowedColumn($column)) {
            return;
        }
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->selectedColumn = $column;
            $this->columnType = $filterDetails['type'];

            // Use cached distinct values with memory limits
            $this->distinctValues = $this->getCachedDistinctValues($column);
        }
    }

    protected function getCachedDistinctValues($columnKey): array
    {
        $cacheKey = "datatable_distinct_{$this->tableId}_{$columnKey}";

        return Cache::remember($cacheKey, $this->distinctValuesCacheTime, function () use ($columnKey) {
            if (isset($this->columns[$columnKey]['relation'])) {
                return $this->getRelationDistinctValues($columnKey);
            } else {
                return $this->getColumnDistinctValues($columnKey);
            }
        });
    }

    protected function getRelationDistinctValues($columnKey): array
    {
        [$relationName, $relatedColumn] = explode(':', $this->columns[$columnKey]['relation']);

        $modelInstance = new ($this->model);
        $relationObj = $modelInstance->$relationName();
        $relatedModel = $relationObj->getRelated();
        $relatedTable = $relatedModel->getTable();
        $relatedColumnFull = $relatedTable . '.' . $relatedColumn;

        // Determine join keys
        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $parentTable = $modelInstance->getTable();
            $foreignKey = $relationObj->getForeignKeyName();
            $ownerKey = $relationObj->getOwnerKeyName();

            return $this->model::query()
                ->select($relatedColumnFull)
                ->join(
                    $relatedTable,
                    $parentTable . '.' . $foreignKey,
                    '=',
                    $relatedTable . '.' . $ownerKey
                )
                ->distinct()
                ->whereNotNull($relatedColumnFull)
                ->limit($this->maxDistinctValues)
                ->pluck($relatedColumnFull)
                ->values()
                ->toArray();
        } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
                  $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            $parentTable = $modelInstance->getTable();
            $foreignKey = $relationObj->getForeignKeyName();
            $localKey = $relationObj->getLocalKeyName();

            return $this->model::query()
                ->select($relatedColumnFull)
                ->join(
                    $relatedTable,
                    $relatedTable . '.' . $foreignKey,
                    '=',
                    $parentTable . '.' . $localKey
                )
                ->distinct()
                ->whereNotNull($relatedColumnFull)
                ->limit($this->maxDistinctValues)
                ->pluck($relatedColumnFull)
                ->values()
                ->toArray();
        } else {
            // Fallback: try to join on guessed keys
            return [];
        }
    }

    protected function getColumnDistinctValues($columnKey): array
    {
        return $this->model::query()
            ->select($columnKey)
            ->distinct()
            ->whereNotNull($columnKey)
            ->limit($this->maxDistinctValues)
            ->pluck($columnKey)
            ->values()
            ->toArray();
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
            if (!$this->isAllowedColumn($this->filterColumn)) {
                return;
            }
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

            // Determine filter type and operator
            $filterType = isset($this->filters[$this->filterColumn]['type']) ? $this->filters[$this->filterColumn]['type'] : 'text';
            $operator = $this->filterOperator ?? $this->getDefaultOperator($filterType);
            $value = $this->sanitizeFilterValue($this->prepareFilterValue($filterType, $operator, $this->filterValue));

            if ($isRelation && $relationDetails) {
                [$relation, $attribute] = $relationDetails;
                $query->whereHas($relation, function ($relQuery) use ($attribute, $operator, $value, $filterType) {
                    if ($filterType === 'date') {
                        $relQuery->whereDate($attribute, $operator, $value);
                    } elseif (in_array($filterType, ['integer', 'number'])) {
                        $relQuery->where($attribute, $operator, $value);
                    } elseif ($filterType === 'text') {
                        $relQuery->where($attribute, 'LIKE', '%' . $value . '%');
                    } elseif ($filterType === 'distinct') {
                        $relQuery->where($attribute, $operator, $value);
                    } else {
                        $relQuery->where($attribute, $operator, $value);
                    }
                });
            } else {
                if ($filterType === 'date') {
                    $query->whereDate($this->filterColumn, $operator, $value);
                } elseif (in_array($filterType, ['integer', 'number'])) {
                    $query->where($this->filterColumn, $operator, $value);
                } elseif ($filterType === 'text') {
                    $query->where($this->filterColumn, 'LIKE', '%' . $value . '%');
                } elseif ($filterType === 'distinct') {
                    $query->where($this->filterColumn, $operator, $value);
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
            case 'distinct':
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
        // For text, we now always use the raw value (LIKE %value%)
        return $value;
    }

    public function getDistinctValues($columnKey)
    {
        // For relation, get distinct from related table, else from main table
        $values = isset($this->columns[$columnKey]['relation'])
            ? $this->getRelationDistinctValues($columnKey)
            : $this->getColumnDistinctValues($columnKey);

        // Sort values alphabetically (case-insensitive)
        if (is_array($values)) {
            natcasesort($values);
            $values = array_values($values);
        }
        return $values;
    }

    //*----------- Optimized Query Builder -----------*//
    protected function query(): Builder
    {
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            try {
                $this->applyCustomQueryConstraints($query);
            } catch (\Exception $e) {
                logger()->error('AFTable custom query error: ' . $e->getMessage());
            }
        }

        // Use cached relations for better performance
        if (!empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        // Use cached select columns - but recalculate based on current visibility
        $selectColumns = $this->getValidSelectColumns();
        if (!empty($selectColumns)) {
            $query->select($selectColumns);
        }

        // Optimized search with indexed queries
        if ($this->searchable && $this->search) {
            // If a filter column is set, search only that column
            if ($this->filterColumn && $this->isAllowedColumn($this->filterColumn)) {
                $this->applyColumnSearch($query, $this->filterColumn, $this->search);
            } else {
                $this->applyOptimizedSearch($query);
            }
        }

        // Apply filters
        if ($this->filterColumn && $this->filterValue) {
            $this->applyFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $query->whereBetween($this->dateColumn, [$this->startDate, $this->endDate]);
        }

        // Optimized sorting
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        return $query;
    }

    // Add this method
    protected function applyColumnSearch(Builder $query, $columnKey, $search)
    {
        $column = $this->columns[$columnKey] ?? null;
        if (!$column) return;

        $search = $this->sanitizeSearch($search);

        // Remove 3-character limit, always search with LIKE %search%
        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($column, $search) {
            if (isset($column['relation'])) {
                [$relation, $attribute] = explode(':', $column['relation']);
                $q->whereHas($relation, function ($relQ) use ($attribute, $search) {
                    $relQ->where($attribute, 'like', '%' . $search . '%');
                });
            } elseif (isset($column['key'])) {
                $q->where($column['key'], 'like', '%' . $search . '%');
            }
        });
    }

    protected function getValidSelectColumns(): array
    {
        $selects = ['id']; // Always include ID

        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns for performance
            $isVisible = $this->visibleColumns[$columnKey] ?? false;
            if (!$isVisible)
                continue;
            if (isset($column['function']))
                continue;

            if (isset($column['relation'])) {
                $fk = null;
                if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                    $fk = $column['key'];
                } else {
                    [$relationName,] = explode(':', $column['relation']);
                    $guessedFk = $relationName . '_id';
                    if ($this->isValidColumn($guessedFk)) {
                        $fk = $guessedFk;
                    }
                }
                if ($fk && !in_array($fk, $selects)) {
                    $selects[] = $fk;
                }
                continue;
            }

            if (isset($column['key']) && !in_array($column['key'], $selects)) {
                if ($this->isValidColumn($column['key'])) {
                    $selects[] = $column['key'];
                }
            }
        }

        // Add columns needed for actions and raw templates (with validation)
        $actionColumns = $this->getColumnsNeededForActions();
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();

        // Filter out invalid columns
        $validActionColumns = array_filter($actionColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        $validRawTemplateColumns = array_filter($rawTemplateColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        // --- Ensure action columns are always included, even if not in $this->columns ---
        foreach ($validActionColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }
        // --- Same for raw template columns ---
        foreach ($validRawTemplateColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }

        // --- Also, check for action columns that are NOT in $this->columns and add them to the query builder ---
        // This ensures $row->uuid and similar are always available
        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    // Only add if it's a valid column and not already in selects
                    if ($this->isValidColumn($columnName) && !in_array($columnName, $selects)) {
                        $selects[] = $columnName;
                    }
                }
            }
        }

        return array_unique($selects);
    }

    protected function applyCustomQueryConstraints(Builder $query): void
    {
        if (is_array($this->query)) {
            foreach ($this->query as $constraint) {
                if (is_array($constraint)) {
                    if (count($constraint) === 3) {
                        [$column, $operator, $value] = $constraint;
                        // Validate column exists to prevent SQL injection
                        if ($this->isValidColumn($column)) {
                            $query->where($column, $operator, $value);
                        }
                    } elseif (count($constraint) === 2) {
                        [$column, $value] = $constraint;
                        if ($this->isValidColumn($column)) {
                            $query->where($column, $value);
                        }
                    }
                }
            }
        }
    }

    protected function isValidColumn($column): bool
    {
        try {
            $modelInstance = new ($this->model);
            return in_array($column, $modelInstance->getFillable()) ||
                in_array($column, ['id', 'created_at', 'updated_at']) ||
                $modelInstance->getConnection()->getSchemaBuilder()->hasColumn($modelInstance->getTable(), $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isAllowedColumn($column)
    {
        return array_key_exists($column, $this->columns);
    }

    protected function applyOptimizedSearch(Builder $query): void
    {
        $search = $this->sanitizeSearch($this->search);

        $query->where(function ($query) use ($search) {
            foreach ($this->columns as $columnKey => $column) {
                $isVisible = $this->visibleColumns[$columnKey] ?? false;

                if (!$isVisible || isset($column['function']) || !isset($column['key'])) {
                    continue;
                }

                if ($column['key'] === 'actions')
                    continue;

                if (isset($column['relation'])) {
                    [$relation, $attribute] = explode(':', $column['relation']);
                    $query->orWhereHas($relation, function ($relQ) use ($attribute, $search) {
                        // Use LIKE with leading wildcard only for better index usage
                        $relQ->where($attribute, 'like', $search . '%');
                    });
                } else {
                    // Use more efficient search patterns
                    if (is_numeric($search)) {
                        $query->orWhere($column['key'], $search);
                    } else {
                        $query->orWhere($column['key'], 'like', $search . '%');
                    }
                }
            }
        });
    }

    protected function applyOptimizedSorting(Builder $query): void
    {
        $sortColumnConfig = collect($this->columns)->first(function ($col) {
            return isset($col['key']) && $col['key'] === $this->sortColumn;
        });

        // Validate sort direction
        $direction = strtolower($this->sortDirection);
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        if ($sortColumnConfig && isset($sortColumnConfig['relation'])) {
            [$relation, $attribute] = explode(':', $sortColumnConfig['relation']);

            // Handle nested relations (e.g., 'student.user:name')
            if (strpos($relation, '.') !== false) {
                // For nested relations, disable sorting to prevent errors
                // Alternative: implement complex JOIN logic for nested relations
                logger()->warning("Sorting on nested relation '{$relation}' is not supported. Column: {$this->sortColumn}");
                return;
            }

            // Always eager load the relation for performance
            $query->with($relation);

            // Handle nested attributes (e.g., 'student:user.name')
            if (strpos($attribute, '.') !== false) {
                // For nested attributes, disable sorting to prevent errors
                logger()->warning("Sorting on nested attribute '{$attribute}' is not supported. Column: {$this->sortColumn}");
                return;
            }

            // Use more efficient subquery sorting for simple relations
            $modelInstance = new ($this->model);
            
            try {
                $relationObj = $modelInstance->$relation();

                if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $query->orderBy(
                        $relationObj->getRelated()::select($attribute)
                            ->whereColumn(
                                $relationObj->getRelated()->getTable() . '.' . $relationObj->getOwnerKeyName(),
                                $modelInstance->getTable() . '.' . $relationObj->getForeignKeyName()
                            )
                            ->limit(1),
                        $direction
                    );
                } else {
                    // Fallback to JOIN for other relation types
                    $this->applyJoinSorting($query, $relationObj, $attribute, $direction);
                }
            } catch (\Exception $e) {
                logger()->error("Error sorting by relation '{$relation}': " . $e->getMessage());
                // Fallback to sorting by the foreign key if relation sorting fails
                if ($this->isValidColumn($this->sortColumn)) {
                    $query->orderBy($this->sortColumn, $direction);
                }
            }
        } elseif ($sortColumnConfig && $this->isValidColumn($this->sortColumn)) {
            $query->orderBy($this->sortColumn, $direction);
        }
    }

    protected function applyJoinSorting(Builder $query, $relationObj, $attribute, $direction = 'asc'): void
    {
        $modelInstance = new ($this->model);
        $relationTable = $relationObj->getRelated()->getTable();

        // Always eager load the relation for performance
        $relationName = method_exists($relationObj, 'getRelationName') ? $relationObj->getRelationName() : null;
        if ($relationName) {
            $query->with($relationName);
        }

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
        } elseif (
            $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
            $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany
        ) {
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

        // Validate sort direction - use passed parameter instead of instance variable
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $query->orderBy($relationTable . '.' . $attribute, $direction)
            ->select($modelInstance->getTable() . '.*');
    }

    //*----------- Memory Optimized Export -----------*//
    public function export($format)
    {
        if ($format === 'pdf') {
            return $this->exportPdfChunked();
        }

        return Excel::download(new DataTableExport(
            $this->model,
            $this->columns,
            $this->getFilters(),
            $this->sortColumn,
            $this->sortDirection
        ), "export.{$format}");
    }

    public function exportPdfChunked()
    {
        // Use chunked processing for large datasets
        $chunkSize = 1000;
        $data = collect();

        $this->query()->chunk($chunkSize, function ($chunk) use ($data) {
            $data->push(...$chunk);
        });

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
        // Use chunked processing to avoid memory issues
        return $this->query()->lazy(1000); // Use lazy collection for memory efficiency
    }

    //*----------- Cache Management -----------*//
    public function clearDistinctValuesCache()
    {
        $cachePattern = "datatable_distinct_{$this->tableId}_*";
        // Clear related cache entries (implementation depends on cache driver)
        Cache::flush(); // Consider more targeted cache clearing in production
    }

    public function updatedFilterColumn()
    {
        // Only clear the value, keep the column selected
        $this->filterValue = null;
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    public function clearFilter()
    {
        // Keep filterColumn, just clear value and operator
        $this->filterValue = null;
        $this->filterOperator = '=';
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    //*----------- Template Detection Helpers -----------*//
    protected function getColumnsNeededForActions()
    {
        $neededColumns = [];

        foreach ($this->actions as $action) {
            // Get the action template string (handle both 'raw' key and direct string)
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;

            // Match Blade variables like {{$row->column_name}} but exclude method calls
            preg_match_all('/\{\{\s*\$row->([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $template, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    // Skip method calls
                    if (strpos($template, '$row->' . $columnName . '()') !== false) {
                        continue;
                    }
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
            // Skip function-based columns
            if (isset($column['function'])) {
                continue;
            }

            if (isset($column['raw'])) {
                // Use a more comprehensive pattern to catch all $row->column references and method calls
                $patterns = [
                    '/\{\{\s*.*?\$row->([a-zA-Z_][a-zA-Z0-9_]*)\s*.*?\}\}/',
                    '/\$row->([a-zA-Z_][a-zA-Z0-9_]*)(?![a-zA-Z0-9_]|->|\(\))/',
                ];

                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $column['raw'], $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $columnName) {
                            // Skip if this is a relation reference
                            if (strpos($column['raw'], '$row->' . $columnName . '->') !== false) {
                                continue;
                            }

                            // Skip if this is a method call
                            if (strpos($column['raw'], '$row->' . $columnName . '()') !== false) {
                                continue;
                            }

                            // Don't add relation names as columns
                            $isRelationName = false;
                            foreach ($this->columns as $col) {
                                if (isset($col['relation'])) {
                                    [$relationName,] = explode(':', $col['relation']);
                                    if ($columnName === $relationName) {
                                        $isRelationName = true;
                                        break;
                                    }
                                }
                            }

                            // Skip virtual columns
                            $virtualColumns = ['flight_status', 'ticket_status'];
                            if (in_array($columnName, $virtualColumns)) {
                                continue;
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

    // Add this method for sanitizing search input
    protected function sanitizeSearch($search)
    {
        $search = trim($search);
        // Limit length to prevent abuse
        return mb_substr($search, 0, 100);
    }

    // Add this method for sanitizing filter value
    protected function sanitizeFilterValue($value)
    {
        if (is_string($value)) {
            return mb_substr(trim($value), 0, 100);
        }
        return $value;
    }
}
