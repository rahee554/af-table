<?php

namespace ArtflowStudio\Table\Http\Livewire;

use ArtflowStudio\Table\Traits\HasActions;
use ArtflowStudio\Table\Traits\HasAdvancedCaching;
use ArtflowStudio\Table\Traits\HasAdvancedExport;
use ArtflowStudio\Table\Traits\HasAdvancedFiltering;
use ArtflowStudio\Table\Traits\HasApiEndpoint;
use ArtflowStudio\Table\Traits\HasJsonFile;
use ArtflowStudio\Table\Traits\HasBulkActions;
use ArtflowStudio\Table\Traits\HasColumnConfiguration;
use ArtflowStudio\Table\Traits\HasColumnOptimization;
use ArtflowStudio\Table\Traits\HasColumnVisibility;
use ArtflowStudio\Table\Traits\HasDataValidation;
use ArtflowStudio\Table\Traits\HasDistinctValues;
use ArtflowStudio\Table\Traits\HasEagerLoading;
use ArtflowStudio\Table\Traits\HasEventListeners;
use ArtflowStudio\Table\Traits\HasForEach;
use ArtflowStudio\Table\Traits\HasJsonSupport;
use ArtflowStudio\Table\Traits\HasMemoryManagement;
use ArtflowStudio\Table\Traits\HasPerformanceMonitoring;
use ArtflowStudio\Table\Traits\HasQueryBuilder;
use ArtflowStudio\Table\Traits\HasQueryOptimization;
use ArtflowStudio\Table\Traits\HasQueryStringSupport;
use ArtflowStudio\Table\Traits\HasRawTemplates;
use ArtflowStudio\Table\Traits\HasRelationships;
use ArtflowStudio\Table\Traits\HasSearch;
use ArtflowStudio\Table\Traits\HasSessionManagement;
use ArtflowStudio\Table\Traits\HasSorting;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class DatatableTrait extends Component
{
    use HasActions {
        HasActions::clearSelection as clearActionSelection;
        HasActions::getSelectedCount as getActionSelectedCount;
    }
    use HasAdvancedCaching, HasDistinctValues {
        HasAdvancedCaching::generateDistinctValuesCacheKey insteadof HasDistinctValues;
        HasDistinctValues::generateDistinctValuesCacheKey as generateBasicDistinctCacheKey;
    }
    use HasAdvancedExport;
    use HasAdvancedFiltering;
    use HasApiEndpoint;
    use HasJsonFile;
    use HasBulkActions {
        HasBulkActions::clearSelection as clearBulkSelection;
        HasBulkActions::getSelectedCount as getBulkSelectedCount;
    }
    use HasColumnConfiguration;
    use HasColumnOptimization;
    use HasColumnVisibility;
    use HasDataValidation;
    use HasEagerLoading;
    use HasEventListeners;
    use HasForEach;
    use HasJsonSupport;
    use HasMemoryManagement;
    use HasPerformanceMonitoring;
    use HasQueryBuilder;
    use HasQueryOptimization;
    use HasQueryStringSupport;
    use HasRawTemplates;
    use HasRelationships;
    use HasSearch;
    use HasSessionManagement;
    use HasSorting;
    use WithPagination;

    // *----------- Properties -----------*//
    public $model;

    public $columns = [];

    public $visibleColumns = [];

    public $checkbox = false;

    public $records = 10;

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

    public $index = false; // Changed: Show index column false by default

    public $tableId = null;

    public $query = null;

    public $colvisBtn = true;

    public $perPage = 10;

    // Performance optimization properties
    protected $cachedRelations = null;

    protected $cachedSelectColumns = null;

    protected $distinctValuesCacheTime = 300; // 5 minutes

    protected $maxDistinctValues = 1000; // Limit for memory management

    // *----------- Optional Configuration -----------*//
    public $searchable = true;

    public $exportable = false;

    public $printable = false;

    public $colSort = true;

    public $sort = 'desc';

    public $refreshBtn = false;

    // *----------- Query String Parameters -----------*//
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

    // *----------- Additional Properties for Trait Functionality -----------*//
    public $enableSessionPersistence = true;

    public $enableQueryStringSupport = true;

    // *----------- Event Listeners -----------*//
    protected $listeners = [
        'dateRangeSelected' => 'applyDateRange',
        'refreshTable' => '$refresh',
    ];

    // *----------- Component Initialization -----------*//
    public function mount($model, $columns, $filters = [], $actions = [], $index = false, $tableId = null, $query = null)
    {
        $this->model = $model;
        $this->tableId = $tableId ?? (is_string($model) ? $model : (is_object($model) ? get_class($model) : uniqid('datatable_')));
        $this->query = $query;

        // Pre-cache relations and select columns for performance
        $this->initializeColumnConfiguration($columns);

        // Re-key columns by 'key' or 'function' with fallback
        // For JSON columns, use a unique identifier that includes the JSON path
        $this->columns = collect($columns)->mapWithKeys(function ($column, $index) {
            // Priority: function > key+json > key > auto-generated
            if (isset($column['function'])) {
                $identifier = $column['function'];
            } elseif (isset($column['key']) && isset($column['json'])) {
                // For JSON columns, create unique identifier using key and json path
                $identifier = $column['key'].'.'.$column['json'];
            } elseif (isset($column['key'])) {
                $identifier = $column['key'];
            } else {
                $identifier = 'col_'.$index; // Fallback for columns without key or function
            }

            return [$identifier => $column];
        })->toArray();

        // Session key for column visibility (unique per model/table and tableId)
        $sessionKey = $this->getColumnVisibilitySessionKey();

        // Initialize column visibility from session or defaults
        $sessionVisibility = \Illuminate\Support\Facades\Session::get($sessionKey, []);
        if (! empty($sessionVisibility)) {
            $this->visibleColumns = $this->getValidatedVisibleColumns($sessionVisibility);
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
        }

        // Ensure all columns have a visibility state
        foreach ($this->columns as $columnKey => $column) {
            if (! array_key_exists($columnKey, $this->visibleColumns)) {
                $this->visibleColumns[$columnKey] = ! ($column['hide'] ?? false);
            }
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

    /**
     * Get data for the datatable
     */
    public function getData()
    {
        $this->triggerBeforeQuery(null);

        try {
            $query = $this->buildQuery();
            $results = $query->paginate($this->perPage);

            $this->triggerAfterQuery($query, $results);

            return $results;
        } catch (\Exception $e) {
            $this->triggerErrorEvent($e, ['method' => 'getData']);
            throw $e;
        }
    }

    /**
     * Build the complete query
     */
    protected function buildQuery()
    {
        $query = $this->getQuery();

        // Apply column optimization for selective loading
        $query = $this->applyColumnOptimization($query);

        // Apply search
        if (! empty($this->search)) {
            $this->applyOptimizedSearch($query);
        }

        // Apply filters
        if (! empty($this->filters)) {
            $this->applyFilters($query);
        }

        // Apply sorting
        if (! empty($this->sortColumn)) {
            $this->applyOptimizedSorting($query);
        }

        // Apply eager loading with optimization
        $query = $this->applyLoadingStrategy($query);
        $query = $this->optimizeRelationLoading($query);

        // Optimize for memory if needed
        if ($this->isMemoryThresholdExceeded()) {
            $query = $this->optimizeQueryForMemory($query);
        }

        return $query;
    }

    /**
     * Render the component
     */
    public function render()
    {
        $data = $this->query()->paginate($this->records);

        return view('artflow-table::livewire.datatable-trait', [
            'data' => $data,
            'index' => $this->index,
        ]);
    }

    /**
     * Handle search updates
     */
    public function updatedSearch($value)
    {
        $oldValue = $this->search;
        $this->search = $value;
        $this->resetPage();

        $this->triggerSearchEvent($value, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->autoSaveState();
        }
    }

    /**
     * Handle filter updates
     */
    public function updatedFilters($value, $key)
    {
        $columnKey = str_replace('filters.', '', $key);
        $oldValue = $this->filters[$columnKey] ?? null;

        $this->resetPage();

        $this->triggerFilterEvent($columnKey, $value, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->autoSaveState();
        }
    }

    /**
     * Handle sorting
     */
    public function sortBy($column)
    {
        $oldColumn = $this->sortColumn;
        $oldDirection = $this->sortDirection;

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();

        $this->triggerSortEvent($this->sortColumn, $this->sortDirection, $oldColumn, $oldDirection);

        if ($this->enableSessionPersistence) {
            $this->autoSaveState();
        }
    }

    /**
     * Handle per page changes
     */
    public function updatedPerPage($value)
    {
        $oldValue = $this->perPage;
        $this->perPage = $value;
        $this->resetPage();

        $this->triggerPaginationEvent(1, $value, $this->page, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->autoSaveState();
        }
    }

    /**
     * Toggle column visibility
     */
    public function toggleColumn($columnKey)
    {
        $oldVisibility = $this->visibleColumns[$columnKey] ?? true;
        $this->visibleColumns[$columnKey] = ! $oldVisibility;

        $this->triggerColumnVisibilityEvent($columnKey, $this->visibleColumns[$columnKey], $oldVisibility);

        if ($this->enableSessionPersistence) {
            $this->saveColumnPreferences();
        }
    }

    /**
     * Clear all filters
     */
    public function clearAllFilters()
    {
        $this->filters = [];
        $this->resetPage();

        if ($this->enableSessionPersistence) {
            $this->autoSaveState();
        }

        $this->emit('filtersCleared');
    }

    /**
     * Clear search
     */
    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();

        if ($this->enableSessionPersistence) {
            $this->autoSaveState();
        }

        $this->emit('searchCleared');
    }

    /**
     * Handle export
     */
    public function handleExport($format = 'csv', $filename = null)
    {
        try {
            $stats = $this->getExportStats();

            if ($stats['total_records'] > 10000) {
                return $this->exportWithChunking($format, $filename);
            } else {
                switch ($format) {
                    case 'csv':
                        return $this->exportToCsv($filename);
                    case 'json':
                        return $this->exportToJson($filename);
                    case 'excel':
                        return $this->exportToExcel($filename);
                    default:
                        return $this->exportToCsv($filename);
                }
            }
        } catch (\Exception $e) {
            $this->triggerErrorEvent($e, ['method' => 'handleExport', 'format' => $format]);
            session()->flash('error', 'Export failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Handle bulk actions
     */
    public function handleBulkAction($actionKey)
    {
        try {
            $result = $this->executeBulkAction($actionKey);

            if ($result['success']) {
                session()->flash('success', $result['message']);
                $this->clearSelection();
                $this->emit('bulkActionCompleted', $actionKey);
            } else {
                session()->flash('error', $result['message']);
            }

            return $result;
        } catch (\Exception $e) {
            $this->triggerErrorEvent($e, ['method' => 'handleBulkAction', 'action' => $actionKey]);
            session()->flash('error', 'Bulk action failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Select all visible records
     */
    public function selectAllVisible()
    {
        $this->selectAllOnPage();
    }

    /**
     * Clear all selections
     */
    public function clearAllSelections()
    {
        $this->clearSelection();
    }

    /**
     * Refresh component
     */
    public function refresh()
    {
        // Clear any cached data
        if (method_exists($this, 'clearAllCaches')) {
            $this->clearAllCaches();
        }

        $this->emit('datatableRefreshed');
    }

    /**
     * Get component statistics
     */
    public function getComponentStats(): array
    {
        return [
            'query_stats' => $this->getQueryStats(),
            'column_stats' => $this->getColumnStats(),
            'filter_stats' => $this->getFilterStats(),
            'memory_stats' => $this->getMemoryStats(),
            'cache_stats' => $this->getCacheStats(),
            'relation_stats' => $this->getRelationColumnStats(),
            'json_stats' => $this->getJsonColumnStats(),
            'action_stats' => $this->getActionStats(),
            'session_stats' => $this->getSessionStats(),
            'event_stats' => $this->getEventListenerStats(),
        ];
    }

    /**
     * Get query string properties
     */
    public function getQueryString()
    {
        if (! $this->enableQueryStringSupport) {
            return [];
        }

        return [
            'search' => ['except' => ''],
            'sortColumn' => ['except' => ''],
            'sortDirection' => ['except' => 'asc'],
            'page' => ['except' => 1],
            'perPage' => ['except' => 10],
            'filters' => ['except' => []],
        ];
    }

    /**
     * Validate the complete configuration
     */
    protected function validateConfiguration()
    {
        $errors = [];

        // Validate model
        if (! $this->model) {
            $errors[] = 'Model is required';
        } elseif (! class_exists($this->model)) {
            $errors[] = 'Model class does not exist: '.$this->model;
        }

        // Validate columns
        if (empty($this->columns)) {
            $errors[] = 'Columns configuration is required';
        } else {
            $columnValidation = $this->validateColumns();
            if (! empty($columnValidation['errors'])) {
                $errors = array_merge($errors, $columnValidation['errors']);
            }
        }

        // Validate relations
        $relationValidation = $this->validateRelationColumns();
        if (! empty($relationValidation['invalid'])) {
            foreach ($relationValidation['invalid'] as $column => $error) {
                $errors[] = "Column {$column}: {$error}";
            }
        }

        if (! empty($errors)) {
            throw new \InvalidArgumentException('Datatable configuration errors: '.implode(', ', $errors));
        }
    }

    /**
     * Get debug information
     */
    public function getDebugInfo(): array
    {
        return [
            'component' => [
                'table_id' => $this->tableId,
                'model' => $this->model,
                'columns_count' => count($this->columns),
                'visible_columns_count' => count(array_filter($this->visibleColumns)),
                'has_search' => ! empty($this->search),
                'has_filters' => ! empty($this->filters),
                'has_sorting' => ! empty($this->sortColumn),
                'selected_records_count' => count($this->selectedRecords),
                'per_page' => $this->perPage,
                'current_page' => $this->page ?? 1,
            ],
            'configuration' => [
                'session_persistence' => $this->enableSessionPersistence,
                'query_string_support' => $this->enableQueryStringSupport,
                'cache_time' => $this->distinctValuesCacheTime,
                'max_distinct_values' => $this->maxDistinctValues,
            ],
            'statistics' => $this->getComponentStats(),
            'validation' => [
                'columns' => $this->validateColumns(),
                'relations' => $this->validateRelationColumns(),
            ],
        ];
    }

    // *----------- Missing Methods from Datatable.php -----------*//

    /**
     * Render raw HTML content
     */
    public function renderRawHtml($rawTemplate, $row)
    {
        return \Illuminate\Support\Facades\Blade::render($rawTemplate, compact('row'));
    }

    /**
     * Get dynamic CSS class for column
     */
    public function getDynamicClass($column, $row)
    {
        $classes = [];
        if (isset($column['classCondition']) && is_array($column['classCondition'])) {
            foreach ($column['classCondition'] as $condition => $class) {
                if (eval("return {$condition};")) {
                    $classes[] = $class;
                }
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Extract value from JSON column using dot notation path
     */
    public function extractJsonValue($row, $jsonColumn, $jsonPath)
    {
        try {
            $jsonData = $row->$jsonColumn;
            if (is_string($jsonData)) {
                $jsonData = json_decode($jsonData, true);
            }

            if (! is_array($jsonData)) {
                return null;
            }

            // Navigate through dot notation
            $keys = explode('.', $jsonPath);
            $value = $jsonData;

            foreach ($keys as $key) {
                if (! is_array($value) || ! array_key_exists($key, $value)) {
                    return null;
                }
                $value = $value[$key];
            }

            return $value;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Toggle column visibility
     */
    public function toggleColumnVisibility($columnKey)
    {
        // Ensure the column exists in the visibility array with proper default
        if (! array_key_exists($columnKey, $this->visibleColumns)) {
            $this->visibleColumns[$columnKey] = ! ($this->columns[$columnKey]['hide'] ?? false);
        }

        // Toggle the visibility
        $this->visibleColumns[$columnKey] = ! $this->visibleColumns[$columnKey];

        // Save to session
        \Illuminate\Support\Facades\Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);

        // Force component re-render
        $this->dispatch('$refresh');
    }

    /**
     * Update column visibility from wire:model
     */
    public function updateColumnVisibility($columnKey)
    {
        // This method is called when the wire:model updates
        // The visibleColumns array is automatically updated by Livewire
        // We just need to save it to the session
        \Illuminate\Support\Facades\Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);

        // Force component re-render to ensure table updates
        $this->dispatch('$refresh');
    }

    /**
     * Clear column visibility session
     */
    public function clearColumnVisibilitySession()
    {
        $sessionKey = $this->getColumnVisibilitySessionKey();
        \Illuminate\Support\Facades\Session::forget($sessionKey);

        // Reset to defaults
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        \Illuminate\Support\Facades\Session::put($sessionKey, $this->visibleColumns);
    }

    /**
     * Get column visibility session key
     */
    protected function getColumnVisibilitySessionKey()
    {
        // Use model class name and tableId for uniqueness
        if (is_string($this->model)) {
            $modelName = $this->model;
        } elseif (is_object($this->model)) {
            $modelName = get_class($this->model);
        } else {
            $modelName = 'datatable';
        }

        return 'datatable_visible_columns_'.md5($modelName.'_'.static::class.'_'.$this->tableId);
    }

    /**
     * Get default visible columns
     */
    protected function getDefaultVisibleColumns()
    {
        return collect($this->columns)->mapWithKeys(function ($column, $identifier) {
            // Default to visible unless explicitly hidden
            $isVisible = ! isset($column['hide']) || ! $column['hide'];

            return [$identifier => $isVisible];
        })->toArray();
    }

    /**
     * Get validated visible columns from session
     */
    protected function getValidatedVisibleColumns($sessionVisibility)
    {
        $validSessionVisibility = [];
        foreach ($this->columns as $columnKey => $columnConfig) {
            if (array_key_exists($columnKey, $sessionVisibility)) {
                $validSessionVisibility[$columnKey] = $sessionVisibility[$columnKey];
            } else {
                $validSessionVisibility[$columnKey] = ! ($columnConfig['hide'] ?? false);
            }
        }

        return $validSessionVisibility;
    }

    /**
     * Refresh table (legacy method)
     */
    public function refreshTable()
    {
        $this->resetPage();
        $this->search = '';
    }

    /**
     * Handle records per page update (legacy method)
     */
    public function updatedrecords()
    {
        $this->resetPage();
    }

    /**
     * Toggle sort (legacy method)
     */
    public function toggleSort($column)
    {
        if (! $this->isAllowedColumn($column)) {
            return; // Ignore or handle invalid column
        }

        // If clicking the same column, toggle direction
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, start with asc
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Handle selected column updates for filtering
     */
    public function updatedSelectedColumn($column)
    {
        if (! $this->isAllowedColumn($column)) {
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

    /**
     * Apply date range filter
     */
    public function applyDateRange($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->resetPage();
    }

    /**
     * Get distinct values for a column
     */
    public function getDistinctValues($columnKey)
    {
        // For relation, get distinct from related table, else from main table
        $values = isset($this->columns[$columnKey]['relation'])
            ? $this->getRelationDistinctValues($columnKey)
            : $this->getColumnDistinctValues($columnKey);

        // Sort values alphabetically (case-insensitive)
        if (is_array($values)) {
            usort($values, function ($a, $b) {
                return strcasecmp($a, $b);
            });
        }

        return $values;
    }

    /**
     * Export data in specified format
     */
    public function export($format)
    {
        // Use the new consolidated export functionality from HasAdvancedExport trait
        // The parent export method already handles all formats including PDF
        return parent::export($format);
    }

    /**
     * Export to CSV format
     */
    public function exportToCsv()
    {
        return $this->export('csv');
    }

    /**
     * Export to JSON format
     */
    public function exportToJson()
    {
        return $this->export('json');
    }

    /**
     * Export to Excel format
     */
    public function exportToExcel()
    {
        return $this->export('xlsx');
    }

    /**
     * Export PDF with chunking for large datasets
     */
    public function exportPdfChunked()
    {
        // Use the new consolidated export functionality
        return $this->export('pdf');
    }

    /**
     * Get data for export
     */
    public function getDataForExport()
    {
        // Use chunked processing to avoid memory issues
        return $this->query()->lazy(1000); // Use lazy collection for memory efficiency
    }

    /**
     * Clear distinct values cache
     */
    public function clearDistinctValuesCache()
    {
        $cachePattern = "datatable_distinct_{$this->tableId}_*";
        // Clear related cache entries (implementation depends on cache driver)
        \Illuminate\Support\Facades\Cache::flush(); // Consider more targeted cache clearing in production
    }

    /**
     * Handle filter column updates
     */
    public function updatedFilterColumn()
    {
        // Only clear the value, keep the column selected
        $this->filterValue = null;
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    /**
     * Clear filter
     */
    public function clearFilter()
    {
        // Keep filterColumn, just clear value and operator
        $this->filterValue = null;
        $this->filterOperator = '=';
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    /**
     * Handle filter value updates
     */
    public function updatedFilterValue()
    {
        $this->resetPage();
    }

    // *----------- Protected Helper Methods -----------*//

    /**
     * Initialize column configuration for performance
     */
    protected function initializeColumnConfiguration($columns)
    {
        // Pre-calculate relations and select columns for better performance
        $this->cachedRelations = $this->calculateRequiredRelations($columns);
        $this->cachedSelectColumns = $this->calculateSelectColumns($columns);
    }

    /**
     * Calculate required relations from columns
     */
    protected function calculateRequiredRelations($columns): array
    {
        $relations = [];

        foreach ($columns as $columnKey => $column) {
            // Skip if column is not visible to avoid loading unnecessary relations
            if (isset($this->visibleColumns) && ! ($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            if (isset($column['relation'])) {
                // For relations like 'flight.airline:name', we need to load 'flight.airline'
                $relationName = explode(':', $column['relation'])[0];
                $relations[] = $relationName;
                
                // Also add intermediate relations for nested paths
                // e.g., for 'flight.airline' also add 'flight'
                if (str_contains($relationName, '.')) {
                    $parts = explode('.', $relationName);
                    $currentPath = '';
                    foreach ($parts as $part) {
                        if ($currentPath) {
                            $currentPath .= '.';
                        }
                        $currentPath .= $part;
                        $relations[] = $currentPath;
                    }
                }
            }

            // Scan raw templates for relation references (improved parsing)
            if (isset($column['raw']) && is_string($column['raw'])) {
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)->/', $column['raw'], $matches);
                if (! empty($matches[1])) {
                    $relations = array_merge($relations, $matches[1]);
                }
            }
        }

        // Include filter relations
        foreach ($this->filters ?? [] as $filterKey => $filterConfig) {
            if (isset($filterConfig['relation']) && isset($columns[$filterKey]['relation'])) {
                $relationName = explode(':', $columns[$filterKey]['relation'])[0];
                $relations[] = $relationName;
            }
        }

        return array_unique($relations);
    }

    /**
     * Calculate select columns for performance
     */
    protected function calculateSelectColumns($columns): array
    {
        $selects = ['id']; // Always include ID

        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = 'updated_at';
        }

        foreach ($columns as $columnKey => $column) {
            // Skip non-visible columns for performance
            if (isset($this->visibleColumns) && ! ($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Function columns don't need database columns - skip them
            if (isset($column['function'])) {
                continue;
            }

            if (isset($column['relation'])) {
                // For relations, we don't need to select the column from main table
                continue;
            }

            // Handle JSON columns - always include the base JSON column when json is specified
            if (isset($column['json']) && isset($column['key'])) {
                if (! in_array($column['key'], $selects)) {
                    $selects[] = $column['key'];
                }

                continue;
            }

            // Only add database columns if they have a valid key
            if (isset($column['key']) && ! in_array($column['key'], $selects)) {
                $selects[] = $column['key'];
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

        // Ensure action columns are always included, even if not in $this->columns
        foreach ($validActionColumns as $col) {
            if (! in_array($col, $selects)) {
                $selects[] = $col;
            }
        }
        // Same for raw template columns
        foreach ($validRawTemplateColumns as $col) {
            if (! in_array($col, $selects)) {
                $selects[] = $col;
            }
        }

        return array_unique($selects);
    }

    /**
     * Get optimal sort column for performance
     */
    protected function getOptimalSortColumn(): ?string
    {
        // Prioritize updated_at for index sorting if it exists and index is enabled
        if ($this->index && $this->isValidColumn('updated_at')) {
            return 'updated_at';
        }

        // Find first indexed column for better sort performance
        $indexedColumns = ['id', 'created_at', 'updated_at']; // Common indexed columns

        foreach ($this->columns as $column) {
            if (isset($column['key']) && ! isset($column['function'])) {
                if (in_array($column['key'], $indexedColumns)) {
                    return $column['key'];
                }
            }
        }

        // Fallback to first sortable column
        foreach ($this->columns as $column) {
            if (isset($column['key']) && ! isset($column['function'])) {
                return $column['key'];
            }
        }

        return null;
    }

    /**
     * Get cached distinct values for a column
     */
    protected function getCachedDistinctValues($columnKey): array
    {
        $cacheKey = "datatable_distinct_{$this->tableId}_{$columnKey}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, $this->distinctValuesCacheTime, function () use ($columnKey) {
            if (isset($this->columns[$columnKey]['relation'])) {
                return $this->getRelationDistinctValues($columnKey);
            } else {
                return $this->getColumnDistinctValues($columnKey);
            }
        });
    }

    /**
     * Get distinct values from a relation
     */
    protected function getRelationDistinctValues($columnKey): array
    {
        [$relationName, $relatedColumn] = explode(':', $this->columns[$columnKey]['relation']);

        $modelInstance = new ($this->model);
        $relationObj = $modelInstance->$relationName();
        $relatedModel = $relationObj->getRelated();
        $relatedTable = $relatedModel->getTable();
        $relatedColumnFull = $relatedTable.'.'.$relatedColumn;

        // Determine join keys
        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $parentTable = $modelInstance->getTable();
            $foreignKey = $relationObj->getForeignKeyName();
            $ownerKey = $relationObj->getOwnerKeyName();

            return $this->model::query()
                ->join($relatedTable, $parentTable.'.'.$foreignKey, '=', $relatedTable.'.'.$ownerKey)
                ->select($relatedColumnFull)
                ->distinct()
                ->whereNotNull($relatedColumnFull)
                ->limit($this->maxDistinctValues)
                ->pluck($relatedColumn)
                ->toArray();
        } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
                  $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            $parentTable = $modelInstance->getTable();
            $foreignKey = $relationObj->getForeignKeyName();
            $localKey = $relationObj->getLocalKeyName();

            return $this->model::query()
                ->join($relatedTable, $parentTable.'.'.$localKey, '=', $relatedTable.'.'.$foreignKey)
                ->select($relatedColumnFull)
                ->distinct()
                ->whereNotNull($relatedColumnFull)
                ->limit($this->maxDistinctValues)
                ->pluck($relatedColumn)
                ->toArray();
        } else {
            // Fallback: try to join on guessed keys
            return [];
        }
    }

    /**
     * Get distinct values from a regular column
     */
    protected function getColumnDistinctValues($columnKey): array
    {
        return $this->model::query()
            ->select($columnKey)
            ->distinct()
            ->whereNotNull($columnKey)
            ->limit($this->maxDistinctValues)
            ->pluck($columnKey)
            ->toArray();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(\Illuminate\Database\Eloquent\Builder $query)
    {
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            $operator = $this->filterOperator ?: '=';
            $value = $this->sanitizeFilterValue($this->filterValue);

            if (isset($this->columns[$this->filterColumn]['relation'])) {
                // Handle relation filtering
                [$relationName, $relationColumn] = explode(':', $this->columns[$this->filterColumn]['relation']);
                $query->whereHas($relationName, function ($q) use ($relationColumn, $operator, $value) {
                    if ($operator === 'like') {
                        $q->where($relationColumn, 'like', '%'.$value.'%');
                    } else {
                        $q->where($relationColumn, $operator, $value);
                    }
                });
            } else {
                // Handle regular column filtering
                if ($operator === 'like') {
                    $query->where($this->filterColumn, 'like', '%'.$value.'%');
                } else {
                    $query->where($this->filterColumn, $operator, $value);
                }
            }
        }
    }

    /**
     * Get query builder instance
     */
    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            $this->applyCustomQueryConstraints($query);
        }

        // Use cached relations for better performance
        if (! empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        // Use cached select columns - but recalculate based on current visibility
        $selectColumns = $this->getValidSelectColumns();
        if (! empty($selectColumns)) {
            $query->select($selectColumns);
        }

        // Optimized search with indexed queries
        if ($this->searchable && $this->search) {
            $this->applyOptimizedSearch($query);
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

    /**
     * Get valid select columns based on current visibility
     */
    protected function getValidSelectColumns(): array
    {
        $modelInstance = new ($this->model);
        $parentTable = $modelInstance->getTable();

        $selects = [$parentTable.'.id']; // Always include ID with table qualifier

        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = $parentTable.'.updated_at';
        }

        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns for performance
            if (isset($this->visibleColumns) && ! ($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Function columns don't need database columns - skip them
            if (isset($column['function'])) {
                continue;
            }

            if (isset($column['relation'])) {
                [$relationName] = explode(':', $column['relation']);
                $relationParts = explode('.', $relationName);

                // For single-level relations like 'booking:unique_id'
                if (count($relationParts) === 1 && $this->isValidColumn($relationParts[0].'_id')) {
                    $foreignKey = $parentTable.'.'.$relationParts[0].'_id';
                    if (! in_array($foreignKey, $selects)) {
                        $selects[] = $foreignKey;
                    }
                }
                // For nested relations like 'flight.airline:name', we need the base relation foreign key
                elseif (count($relationParts) > 1 && $this->isValidColumn($relationParts[0].'_id')) {
                    $foreignKey = $parentTable.'.'.$relationParts[0].'_id';
                    if (! in_array($foreignKey, $selects)) {
                        $selects[] = $foreignKey;
                    }
                }

                continue;
            }

            // Handle JSON columns - always include the base JSON column when json is specified
            if (isset($column['json']) && isset($column['key'])) {
                $qualifiedColumn = $parentTable.'.'.$column['key'];
                if (! in_array($qualifiedColumn, $selects)) {
                    $selects[] = $qualifiedColumn;
                }

                continue;
            }

            // Only add database columns if they have a valid key - with table qualifier
            if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $qualifiedColumn = $parentTable.'.'.$column['key'];
                if (! in_array($qualifiedColumn, $selects)) {
                    $selects[] = $qualifiedColumn;
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

        // Ensure action columns are always included, even if not in $this->columns - with table qualifier
        foreach ($validActionColumns as $col) {
            $qualifiedColumn = $parentTable.'.'.$col;
            if (! in_array($qualifiedColumn, $selects)) {
                $selects[] = $qualifiedColumn;
            }
        }
        // Same for raw template columns - with table qualifier
        foreach ($validRawTemplateColumns as $col) {
            $qualifiedColumn = $parentTable.'.'.$col;
            if (! in_array($qualifiedColumn, $selects)) {
                $selects[] = $qualifiedColumn;
            }
        }

        return array_unique($selects);
    }

    /**
     * Apply custom query constraints
     */
    protected function applyCustomQueryConstraints(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if (is_array($this->query)) {
            foreach ($this->query as $column => $value) {
                // Handle different constraint formats
                if (is_array($value)) {
                    if (count($value) === 3) {
                        [$col, $operator, $val] = $value;
                        // Validate column exists to prevent SQL injection
                        if ($this->isValidColumn($col)) {
                            $query->where($col, $operator, $val);
                        }
                    } elseif (count($value) === 2) {
                        [$col, $val] = $value;
                        if ($this->isValidColumn($col)) {
                            $query->where($col, $val);
                        }
                    }
                } elseif (is_callable($value)) {
                    // Handle callable constraints for complex queries
                    $value($query);
                } else {
                    // Simple key-value constraint: 'column' => 'value'
                    if ($this->isValidColumn($column)) {
                        $query->where($column, $value);
                    }
                }
            }
        } elseif (is_callable($this->query)) {
            ($this->query)($query);
        }
    }

    /**
     * Check if column is valid in database
     */
    protected function isValidColumn($column): bool
    {
        try {
            $modelInstance = new ($this->model);
            $table = $modelInstance->getTable();
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

            return in_array($column, $columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if column is allowed for operations
     */
    protected function isAllowedColumn($column)
    {
        // Allow 'updated_at' for index column sorting
        if ($column === 'updated_at') {
            return true;
        }

        return array_key_exists($column, $this->columns);
    }

    /**
     * Apply optimized search to query
     */
    protected function applyOptimizedSearch(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $search = $this->sanitizeSearch($this->search);

        $query->where(function ($query) use ($search) {
            foreach ($this->columns as $columnKey => $column) {
                if (! ($this->visibleColumns[$columnKey] ?? true)) {
                    continue; // Skip non-visible columns
                }

                $this->applyColumnSearch($query, $columnKey, $search);
            }
        });
    }

    /**
     * Apply column-specific search
     */
    protected function applyColumnSearch(\Illuminate\Database\Eloquent\Builder $query, $columnKey, $search)
    {
        $column = $this->columns[$columnKey] ?? null;
        if (! $column) {
            return;
        }

        $search = $this->sanitizeSearch($search);

        // Remove 3-character limit, always search with LIKE %search%
        if ($search === '') {
            return;
        }

        $query->orWhere(function ($q) use ($column, $search) {
            if (isset($column['relation'])) {
                // Search in related table
                [$relationName, $relationColumn] = explode(':', $column['relation']);
                $q->orWhereHas($relationName, function ($relationQuery) use ($relationColumn, $search) {
                    $relationQuery->where($relationColumn, 'like', '%'.$search.'%');
                });
            } elseif (isset($column['key']) && $this->isValidColumn($column['key'])) {
                // Search in regular column
                $q->orWhere($column['key'], 'like', '%'.$search.'%');
            }
        });
    }

    /**
     * Apply optimized sorting to query
     */
    protected function applyOptimizedSorting(\Illuminate\Database\Eloquent\Builder $query): void
    {
        // First try to find by key (backward compatibility)
        $sortColumnConfig = collect($this->columns)->first(function ($col) {
            return isset($col['key']) && $col['key'] === $this->sortColumn;
        });

        // If not found by key, try to find by the full column identifier (for JSON columns)
        if (! $sortColumnConfig) {
            $sortColumnConfig = $this->columns[$this->sortColumn] ?? null;
        }

        // Validate sort direction
        $direction = strtolower($this->sortDirection);
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        if ($sortColumnConfig && isset($sortColumnConfig['relation'])) {
            try {
                [$relationName, $relationColumn] = explode(':', $sortColumnConfig['relation']);

                // Handle nested relations (e.g., "user.profile:name")
                if (strpos($relationName, '.') !== false) {
                    // For nested relations, use subquery sorting to avoid complex joins
                    $this->applySubquerySorting($query, $relationName, $relationColumn, $direction);
                } else {
                    // Simple relation - use join sorting
                    $modelInstance = new ($this->model);
                    if (method_exists($modelInstance, $relationName)) {
                        $relationObj = $modelInstance->$relationName();
                        $this->applyJoinSorting($query, $relationObj, $relationColumn, $direction);
                    }
                }
            } catch (\Exception $e) {
                // If relation sorting fails, fallback to basic sorting if possible
                if ($sortColumnConfig && isset($sortColumnConfig['key']) && $this->isValidColumn($sortColumnConfig['key'])) {
                    $query->orderBy($sortColumnConfig['key'], $direction);
                }
            }
        } elseif ($sortColumnConfig && isset($sortColumnConfig['key']) && $this->isValidColumn($sortColumnConfig['key'])) {
            $query->orderBy($sortColumnConfig['key'], $direction);
        } elseif ($this->sortColumn === 'updated_at' && $this->isValidColumn('updated_at')) {
            $query->orderBy('updated_at', $direction);
        }
    }

    /**
     * Apply join sorting for relations
     */
    protected function applyJoinSorting(\Illuminate\Database\Eloquent\Builder $query, $relationObj, $attribute, $direction = 'asc'): void
    {
        $modelInstance = new ($this->model);
        $relationTable = $relationObj->getRelated()->getTable();
        $parentTable = $modelInstance->getTable();

        // Create unique table alias to prevent join conflicts
        $tableAlias = $relationTable.'_sort_'.uniqid();

        // Get relation name for eager loading (extract from relation object)
        $relationName = null;
        if (method_exists($relationObj, 'getRelationName')) {
            $relationName = $relationObj->getRelationName();
        } else {
            // Fallback: get relation name from the relation object
            $relationClass = get_class($relationObj);
            if (property_exists($relationObj, 'relationName')) {
                $relationName = $relationObj->relationName;
            }
        }

        // Check if this join already exists to prevent duplicates
        $joinExists = false;
        $existingJoins = $query->getQuery()->joins ?? [];
        foreach ($existingJoins as $join) {
            if ($join->table === $relationTable || strpos($join->table, $relationTable.'_sort_') === 0) {
                $joinExists = true;
                $tableAlias = $join->table;
                break;
            }
        }

        if (! $joinExists) {
            if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $foreignKey = $relationObj->getForeignKeyName();
                $ownerKey = $relationObj->getOwnerKeyName();

                $query->leftJoin($relationTable.' as '.$tableAlias,
                    $parentTable.'.'.$foreignKey, '=', $tableAlias.'.'.$ownerKey);
            } elseif (
                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne
            ) {
                $foreignKey = $relationObj->getForeignKeyName();
                $localKey = $relationObj->getLocalKeyName();

                $query->leftJoin($relationTable.' as '.$tableAlias,
                    $parentTable.'.'.$localKey, '=', $tableAlias.'.'.$foreignKey);
            }
        }

        // Validate sort direction
        $direction = strtolower($direction);
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // Apply sorting with table alias
        $query->orderBy($tableAlias.'.'.$attribute, $direction);

        // Only modify select if no columns are currently selected
        // This prevents overriding the carefully constructed select columns
        if (empty($query->getQuery()->columns)) {
            $query->select($parentTable.'.*');
        }

        // Add group by to prevent duplicate results from joins
        // Include all selected columns in GROUP BY to satisfy MySQL strict mode
        $selectedColumns = $query->getQuery()->columns;
        if (! empty($selectedColumns)) {
            // Group by all selected columns
            $query->groupBy($selectedColumns);
        } else {
            // Fallback to grouping by primary key when selecting all columns
            $query->groupBy($parentTable.'.id');
        }
    }

    /**
     * Apply subquery sorting for nested relations
     */
    protected function applySubquerySorting(\Illuminate\Database\Eloquent\Builder $query, $relationPath, $attribute, $direction = 'asc'): void
    {
        try {
            $modelInstance = new ($this->model);
            $parentTable = $modelInstance->getTable();

            // Split the nested relation path (e.g., "user.profile" -> ["user", "profile"])
            $relationParts = explode('.', $relationPath);

            // Build the subquery step by step
            $currentModel = $modelInstance;
            $subquery = null;
            $previousTable = $parentTable;

            foreach ($relationParts as $index => $relationName) {
                if (! method_exists($currentModel, $relationName)) {
                    throw new \Exception("Relation {$relationName} does not exist on model ".get_class($currentModel));
                }

                $relationObj = $currentModel->$relationName();
                $relatedModel = $relationObj->getRelated();
                $relatedTable = $relatedModel->getTable();

                if ($index === 0) {
                    // First relation - start the subquery
                    $subquery = $relatedModel::query();

                    if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                        $foreignKey = $relationObj->getForeignKeyName();
                        $ownerKey = $relationObj->getOwnerKeyName();
                        $subquery->whereColumn($relatedTable.'.'.$ownerKey, $parentTable.'.'.$foreignKey);
                    } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                             $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                        $foreignKey = $relationObj->getForeignKeyName();
                        $localKey = $relationObj->getLocalKeyName();
                        $subquery->whereColumn($relatedTable.'.'.$foreignKey, $parentTable.'.'.$localKey);
                    }
                } else {
                    // Subsequent relations - join to the existing subquery
                    if ($subquery && $relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                        $foreignKey = $relationObj->getForeignKeyName();
                        $ownerKey = $relationObj->getOwnerKeyName();
                        $subquery->join($relatedTable, $previousTable.'.'.$foreignKey, '=', $relatedTable.'.'.$ownerKey);
                    } elseif ($subquery && ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                             $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne)) {
                        $foreignKey = $relationObj->getForeignKeyName();
                        $localKey = $relationObj->getLocalKeyName();
                        $subquery->join($relatedTable, $previousTable.'.'.$localKey, '=', $relatedTable.'.'.$foreignKey);
                    }
                }

                $currentModel = $relatedModel;
                $previousTable = $relatedTable;
            }

            if ($subquery) {
                // Add the sort column to the subquery
                $finalTable = $currentModel->getTable();
                $subquery->select($finalTable.'.'.$attribute);
                $subquery->limit(1);

                // Order the main query by the subquery result
                $query->orderByRaw("({$subquery->toSql()}) {$direction}", $subquery->getBindings());
            }

        } catch (\Exception $e) {
            // Log the error and fall back to no sorting for this column
            Log::warning("Failed to apply subquery sorting for relation path: {$relationPath}. Error: ".$e->getMessage());
        }
    }

    /**
     * Get columns needed for actions
     */
    protected function getColumnsNeededForActions()
    {
        $neededColumns = [];

        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            if (is_string($template)) {
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
                if (! empty($matches[1])) {
                    $neededColumns = array_merge($neededColumns, $matches[1]);
                }
            }
        }

        return array_unique($neededColumns);
    }

    /**
     * Get columns needed for raw templates
     */
    protected function getColumnsNeededForRawTemplates()
    {
        $neededColumns = [];

        foreach ($this->columns as $column) {
            if (isset($column['raw']) && is_string($column['raw'])) {
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $column['raw'], $matches);
                if (! empty($matches[1])) {
                    $neededColumns = array_merge($neededColumns, $matches[1]);
                }
            }
        }

        return array_unique($neededColumns);
    }

    /**
     * Sanitize search input
     */
    protected function sanitizeSearch($search)
    {
        $search = trim($search);

        // Limit length to prevent abuse
        return mb_substr($search, 0, 100);
    }

    /**
     * Sanitize filter value
     */
    protected function sanitizeFilterValue($value)
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Sanitize HTML content for raw templates
     */
    protected function sanitizeHtmlContent($content): string
    {
        if (! is_string($content)) {
            return '';
        }

        // Allow basic HTML tags but escape dangerous ones
        $allowedTags = '<p><br><strong><em><span><div><a><img><ul><ol><li>';

        return strip_tags($content, $allowedTags);
    }

    /**
     * Validate JSON path format
     */
    protected function validateJsonPath($jsonPath): bool
    {
        if (! is_string($jsonPath)) {
            return false;
        }

        // Should be alphanumeric with dots for nesting
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $jsonPath) === 1;
    }

    /**
     * Validate relation string format
     */
    protected function validateRelationString($relationString): bool
    {
        if (empty($relationString) || ! is_string($relationString)) {
            return false;
        }

        // Should contain at least relation:column format
        if (! str_contains($relationString, ':')) {
            return false;
        }

        [$relationName, $column] = explode(':', $relationString, 2);

        return ! empty($relationName) && ! empty($column);
    }

    /**
     * Validate export format
     */
    protected function validateExportFormat($format): string
    {
        $validFormats = ['csv', 'xlsx', 'pdf'];

        return in_array(strtolower($format), $validFormats) ? strtolower($format) : 'csv';
    }

    /**
     * Get default operator for filter type (from Datatable.php)
     */
    protected function getDefaultOperator($filterType)
    {
        switch ($filterType) {
            case 'text':
                return 'like';
            case 'number':
                return '=';
            case 'date':
                return '=';
            case 'select':
                return '=';
            default:
                return '=';
        }
    }

    /**
     * Prepare filter value (from Datatable.php)
     */
    protected function prepareFilterValue($filterType, $operator, $value)
    {
        // For text, we now always use the raw value (LIKE %value%)
        return $value;
    }

    // *----------- Trait Conflict Resolution -----------*//

    /**
     * Clear selection - resolves conflict between HasActions and HasBulkActions
     * Uses bulk actions version for enhanced functionality
     */
    public function clearSelection()
    {
        // Use the bulk actions version which has more features
        $this->clearBulkSelection();

        // Also clear the actions selection for compatibility
        $this->clearActionSelection();

        // Dispatch event for both contexts
        $this->dispatch('selectionCleared');
    }

    /**
     * Get selected count - resolves conflict between HasActions and HasBulkActions
     * Uses bulk actions version for enhanced functionality
     */
    public function getSelectedCount(): int
    {
        // Use the bulk actions version which typically has more features
        $bulkCount = $this->getBulkSelectedCount();
        $actionCount = $this->getActionSelectedCount();

        // Return the maximum count (they should be the same if synced properly)
        return max($bulkCount, $actionCount);
    }

    // ============================================================================
    // CONSOLIDATED TRAIT PUBLIC METHOD WRAPPERS
    // ============================================================================

    /**
     * Advanced Caching Public Methods
     */
    public function getCacheStrategy(): string
    {
        return $this->determineCacheStrategy();
    }

    public function getCacheStatistics(): array 
    {
        return $this->getCacheStats();
    }

    public function warmCache(): void
    {
        $this->warmSpecificCache();
    }

    public function generateIntelligentCacheKey(string $suffix = ''): string
    {
        return $this->generateAdvancedCacheKey($suffix);
    }

    /**
     * Advanced Filtering Public Methods  
     */
    public function getFilterOperators(): array
    {
        return $this->getAvailableFilterOperators();
    }

    public function applyDateFilters($query, string $column, string $operator, $value)
    {
        return $this->applyDateFilter($query, $column, $operator, $value);
    }

    public function validateFilterValue($value, string $type): bool
    {
        return $this->isValidFilterValue($value, $type);
    }

    /**
     * Advanced Export Public Methods
     */
    public function exportWithChunking(string $format = 'csv', int $chunkSize = 1000): \Illuminate\Http\Response
    {
        return $this->exportInChunks($format, $chunkSize);
    }

    /**
     * Column Optimization Public Methods
     */
    public function analyzeColumnTypes(): array
    {
        return $this->getColumnTypeAnalysis();
    }

    public function optimizeColumnSelection(): array
    {
        return $this->getOptimizedColumns();
    }

    public function detectHeavyColumns(): array
    {
        return $this->getHeavyColumns();
    }
}
