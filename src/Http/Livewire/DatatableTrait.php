<?php

namespace ArtflowStudio\Table\Http\Livewire;

use ArtflowStudio\Table\Traits\HasActions;
use ArtflowStudio\Table\Traits\HasUnifiedCaching;   
use ArtflowStudio\Table\Traits\HasUnifiedOptimization;
use ArtflowStudio\Table\Traits\HasBasicFeatures;
use ArtflowStudio\Table\Traits\HasAdvancedFiltering;
use ArtflowStudio\Table\Traits\HasApiEndpoint;
use ArtflowStudio\Table\Traits\HasJsonFile;
use ArtflowStudio\Table\Traits\HasBulkActions;
use ArtflowStudio\Table\Traits\HasColumnConfiguration;
use ArtflowStudio\Table\Traits\HasColumnVisibility;
use ArtflowStudio\Table\Traits\HasDataValidation;
use ArtflowStudio\Table\Traits\HasEventListeners;
use ArtflowStudio\Table\Traits\HasJsonSupport;
use ArtflowStudio\Table\Traits\HasQueryOptimization;
use ArtflowStudio\Table\Traits\HasQueryStringSupport;
use ArtflowStudio\Table\Traits\HasRawTemplates;
use ArtflowStudio\Table\Traits\HasRelationships;
use ArtflowStudio\Table\Traits\HasSearch;
use ArtflowStudio\Table\Traits\HasSessionManagement;
use ArtflowStudio\Table\Traits\HasSorting;
use ArtflowStudio\Table\Traits\HasPerformanceMonitoring;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\WithPagination;

class DatatableTrait extends Component
{
    use HasActions {
        HasActions::clearSelection as clearActionSelection;
        HasActions::getSelectedCount as getActionSelectedCount;
    }
    use HasUnifiedCaching;
    use HasUnifiedOptimization;
    use HasBasicFeatures;
    use HasApiEndpoint;
    use HasBulkActions {
        HasBulkActions::clearSelection as clearBulkSelection;
        HasBulkActions::getSelectedCount as getBulkSelectedCount;
    }
    use HasColumnConfiguration;
    use HasColumnVisibility;
    use HasDataValidation;
    use HasEventListeners;
    use HasJsonSupport;
    use HasQueryStringSupport;
    use HasRawTemplates;
    use HasRelationships;
    use HasSearch;
    use HasSessionManagement;
    use HasSorting;
    use HasAdvancedFiltering;
    use HasQueryOptimization;
    use HasPerformanceMonitoring;
    use HasJsonFile;
    // Removed conflicting traits - functionality consolidated in unified traits:
    // - HasQueryBuilder - conflicts with HasUnifiedOptimization::getQuery
    // - HasEagerLoading - conflicts with HasUnifiedOptimization::optimizeEagerLoading  
    // - HasAdvancedCaching - conflicts with HasUnifiedCaching::determineCacheDuration
    // - HasColumnOptimization - conflicts with HasBasicFeatures::applyColumnOptimization
    // - HasDistinctValues - conflicts with HasUnifiedCaching::generateDistinctValuesCacheKey
    // - HasMemoryManagement - functionality consolidated in HasUnifiedOptimization
    // - HasTargetedCaching - functionality consolidated in HasUnifiedCaching
    // - HasOptimizedMemory, HasOptimizedCollections, HasOptimizedRelationships - consolidated in HasUnifiedOptimization
    // - HasIntelligentCaching - consolidated in HasUnifiedCaching
    // - HasAdvancedExport - property conflict with HasBasicFeatures
    // - HasForEach - conflicts with HasBasicFeatures::setForEachData (functionality included in HasBasicFeatures)
    // Keeping potentially compatible traits for testing:
    use WithPagination;

    // *----------- Properties -----------*//
    public $model;

    public $columns = [];

    public $visibleColumns = [];

    public $checkbox = false;

    // Removed $records property - use $perPage for unified pagination

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

        // Use memory-optimized column initialization FIRST
        $this->columns = $this->initializeColumnsOptimized($columns);

        // THEN calculate relations and select columns using the optimized columns
        $this->initializeColumnConfiguration($this->columns);

        // Session key for column visibility (unique per model/table and tableId)
        $sessionKey = $this->getColumnVisibilitySessionKey();

        // Initialize column visibility from session or defaults
        $sessionVisibility = \Illuminate\Support\Facades\Session::get($sessionKey, []);
        if (! empty($sessionVisibility)) {
            $this->visibleColumns = $this->getValidatedVisibleColumns($sessionVisibility);
        } else {
            // Use optimized default visible columns
            $this->visibleColumns = $this->getDefaultVisibleColumnsOptimized();
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
     * Build the complete unified query (replaces both buildQuery and query methods)
     */
    protected function buildUnifiedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Start with base model query
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            $this->applyCustomQueryConstraints($query);
        }

        // Apply column optimization for selective loading
        $query = $this->applyColumnOptimization($query);

        // Load relations using cached relations (like in working Datatable.php)
        if (!empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        // Use optimized relation loading with selective column loading
        $query = $this->applyOptimizedEagerLoading($query);

        // Apply optimized column selection for reduced memory usage
        $selectColumns = $this->calculateSelectColumns($this->columns);
        if (! empty($selectColumns)) {
            // Qualify columns with table name to prevent ambiguous column errors
            $tableName = $this->model::make()->getTable();
            $qualifiedColumns = array_map(function($column) use ($tableName) {
                // Don't qualify columns that already have table prefix or are functions
                if (strpos($column, '.') !== false || strpos($column, '(') !== false) {
                    return $column;
                }
                return $tableName . '.' . $column;
            }, $selectColumns);
            
            $query->select($qualifiedColumns);
        }

        // Apply search - optimized with minimum character threshold
        if ($this->searchable && $this->search && strlen(trim($this->search)) >= 3) {
            $this->applyOptimizedSearch($query);
        }

        // Apply filters with improved logic
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            // Get filter type for the current filter column
            $filterType = isset($this->filters[$this->filterColumn]['type']) 
                ? $this->filters[$this->filterColumn]['type'] 
                : 'text';
            
            // For text filters, only apply if 3+ characters
            if ($filterType === 'text') {
                if (strlen(trim($this->filterValue)) >= 3) {
                    $this->applyFilters($query);
                }
            } else {
                // For non-text filters, apply immediately
                $this->applyFilters($query);
            }
        }

        // Apply additional filters from $this->filters array
        if (! empty($this->filters)) {
            $this->applyFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $query->whereBetween($this->dateColumn, [$this->startDate, $this->endDate]);
        }

        // Apply sorting with optimization
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        // Return the built query
        return $query;
    }

    /**
     * Get the per-page value for pagination (unified)
     */
    public function getPerPageValue(): int
    {
        return $this->perPage ?? 10;
    }

    /**
     * Build the complete query (DEPRECATED - use buildUnifiedQuery)
     * @deprecated Use buildUnifiedQuery() instead for better performance
     */
    protected function buildQuery()
    {
        return $this->buildUnifiedQuery();
    }

    /**
     * Get data using the unified query
     */
    public function getData()
    {
        $this->triggerBeforeQuery(null);

        try {
            $query = $this->buildUnifiedQuery();
            $results = $query->paginate($this->getPerPageValue());

            $this->triggerAfterQuery($query, $results);

            return $results;
        } catch (\Exception $e) {
            $this->triggerErrorEvent($e, ['method' => 'getData']);
            throw $e;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        $data = $this->buildUnifiedQuery()->paginate($this->getPerPageValue());

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
     * Render raw HTML content (SECURED) - Enhanced to support both Blade and simple syntax
     */
    public function renderRawHtml($rawTemplate, $row)
    {
        if (empty($rawTemplate)) {
            return new HtmlString('');
        }

        // 1) Prefer Blade engine to support full Blade syntax (e.g., route(), asset(), conditionals)
        try {
            $rendered = Blade::render($rawTemplate, [
                'row' => $row,
                // Provide optional references if templates need them
                'component' => $this,
                'table' => $this,
            ]);

            return new HtmlString($rendered);
        } catch (\Throwable $e) {
            // Log at debug level to avoid noisy production logs; continue with fallbacks
            Log::debug('Blade::render failed for DatatableTrait raw template', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2) If trait-based advanced renderer exists, use it
        if (method_exists($this, 'renderTemplateWithFunctions')) {
            try {
                $html = $this->renderTemplateWithFunctions($row, $rawTemplate);
                return new HtmlString($html);
            } catch (\Throwable $e) {
                Log::debug('renderTemplateWithFunctions failed for DatatableTrait', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3) Final fallback: our secure mini-renderer that supports {{ }} and {prop}
        $html = $this->renderSecureTemplate($rawTemplate, $row);
        return new HtmlString($html);
    }

    /**
     * Secure template rendering with sanitization - Enhanced for complex expressions
     */
    protected function renderSecureTemplate($template, $row): string
    {
        if (empty($template)) {
            return '';
        }

        $processedTemplate = $template;
        
        // Enhanced processor using callback-based replacement for better control
        $processedTemplate = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($matches) use ($row) {
            $expression = trim($matches[1]);
            return $this->evaluateExpression($expression, $row);
        }, $processedTemplate);
        
        // Handle simple placeholder syntax {column_name} - for backward compatibility
        $processedTemplate = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) use ($row) {
            return $this->getRowPropertyValue($row, $matches[1]);
        }, $processedTemplate);
        
        return $processedTemplate;
    }
    
    /**
     * Evaluate a complex expression safely
     */
    protected function evaluateExpression($expression, $row): string
    {
        // Handle ternary operators: $row->active == 1 ? "Active" : "Inactive"
        if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*==\s*(\d+)\s*\?\s*["\']([^"\']*)["\']?\s*:\s*["\']([^"\']*)["\']?/', $expression, $ternaryMatches)) {
            $property = $ternaryMatches[1];
            $checkValue = (int)$ternaryMatches[2];
            $trueValue = $ternaryMatches[3];
            $falseValue = $ternaryMatches[4];
            
            $actualValue = (int)$this->getRowPropertyValue($row, $property);
            $result = ($actualValue == $checkValue) ? $trueValue : $falseValue;
            return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }
        
        // Handle simple property access: $row->property
        if (preg_match('/^\$row->([a-zA-Z0-9_]+)$/', $expression, $propertyMatches)) {
            return $this->getRowPropertyValue($row, $propertyMatches[1]);
        }
        
        // Handle complex expressions with multiple parts
        if (str_contains($expression, '$row->')) {
            // Replace all $row->property references with actual values
            $processedExpression = preg_replace_callback('/\$row->([a-zA-Z0-9_]+)/', function($matches) use ($row) {
                $value = $this->getRowPropertyValue($row, $matches[1]);
                // Return numeric values as-is for comparisons, strings wrapped in quotes
                return is_numeric($value) ? $value : '"' . addslashes($value) . '"';
            }, $expression);
            
            // Safely evaluate simple expressions (use safe evaluation instead of eval)
            if (preg_match('/^[0-9"\'\s\?\:=<>!&|()]+$/', $processedExpression)) {
                try {
                    // Use safe expression evaluation instead of eval()
                    $result = $this->evaluateExpressionSafely($processedExpression, $row);
                    return htmlspecialchars((string)$result, ENT_QUOTES, 'UTF-8');
                } catch (\Exception $e) {
                    return '[Invalid Expression]';
                }
            }
        }
        
        return htmlspecialchars($expression, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Safely get property value from row object or array
     */
    protected function getRowPropertyValue($row, $property): string
    {
        $value = '';
        
        if (is_object($row) && isset($row->$property)) {
            $value = $row->$property;
        } elseif (is_array($row) && isset($row[$property])) {
            $value = $row[$property];
        }
        
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get dynamic CSS class for column
     */
    public function getDynamicClass($column, $row)
    {
        $classes = [];
        if (isset($column['classCondition']) && is_array($column['classCondition'])) {
            foreach ($column['classCondition'] as $condition => $class) {
                // Use safe condition evaluation instead of eval()
                if ($this->evaluateConditionSafely($condition, $row)) {
                    $classes[] = $class;
                }
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Safely evaluate expressions without using eval()
     * SECURITY: Replaces dangerous eval() usage with safe alternatives
     */
    protected function evaluateExpressionSafely($expression, $row)
    {
        // Remove return statement if present
        $expression = preg_replace('/^return\s+/', '', trim($expression));
        $expression = rtrim($expression, ';');
        
        // For simple ternary operations: condition ? value1 : value2
        if (preg_match('/^(.+?)\s*\?\s*(.+?)\s*:\s*(.+)$/', $expression, $matches)) {
            $condition = trim($matches[1]);
            $trueValue = trim($matches[2], '"\'');
            $falseValue = trim($matches[3], '"\'');
            
            if ($this->evaluateConditionSafely($condition, $row)) {
                return $trueValue;
            } else {
                return $falseValue;
            }
        }
        
        // For simple comparisons that return boolean values
        if ($this->evaluateConditionSafely($expression, $row)) {
            return 'true';
        }
        
        return 'false';
    }

    /**
     * Safely evaluate boolean conditions without eval()
     * SECURITY: Replaces dangerous eval() usage
     */
    protected function evaluateConditionSafely($condition, $row): bool
    {
        // Basic comparison patterns
        if (preg_match('/^"?([^"]+)"?\s*(==|!=|>|<|>=|<=)\s*"?([^"]+)"?$/', $condition, $matches)) {
            $left = trim($matches[1], '"\'');
            $operator = $matches[2];
            $right = trim($matches[3], '"\'');
            
            // Convert numeric strings to numbers for comparison
            if (is_numeric($left)) $left = (float)$left;
            if (is_numeric($right)) $right = (float)$right;
            
            switch ($operator) {
                case '==': return $left == $right;
                case '!=': return $left != $right;
                case '>': return $left > $right;
                case '<': return $left < $right;
                case '>=': return $left >= $right;
                case '<=': return $left <= $right;
            }
        }
        
        // Simple boolean checks like "active" or "1"
        $condition = trim($condition, '"\'');
        if ($condition === 'true' || $condition === '1') return true;
        if ($condition === 'false' || $condition === '0' || $condition === '') return false;
        
        // Default to false for safety
        return false;
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
     * Clear phantom column cache and reset column configuration
     */
    public function clearPhantomColumnCache()
    {
        // Clear session-based column visibility
        $this->clearColumnVisibilitySession();
        
        // Clear cached column selections
        $this->cachedSelectColumns = null;
        $this->cachedRelations = null;
        
        // Reset visible columns to default
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        
        // Clear any cache keys that might contain phantom columns
        if (method_exists($this, 'clearCacheByPattern')) {
            $this->clearCacheByPattern('datatable_columns_*');
            $this->clearCacheByPattern('select_columns_*');
            $this->clearCacheByPattern('visible_columns_*');
        }
        
        // Force recalculation of select columns
        if (!empty($this->columns)) {
            $this->cachedSelectColumns = $this->calculateSelectColumns($this->columns);
        }
        
        Log::info('Phantom column cache cleared', [
            'component' => static::class,
            'table_id' => $this->tableId,
            'visible_columns' => $this->visibleColumns
        ]);
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

        // Include user ID for session isolation - prevents data leakage between users
        $userId = $this->getUserIdentifierForSession();

        return 'datatable_visible_columns_'.md5($modelName.'_'.static::class.'_'.$this->tableId.'_'.$userId);
    }

    /**
     * Get user identifier for session isolation
     */
    protected function getUserIdentifierForSession()
    {
        // Try different auth methods in order of preference
        if (function_exists('auth') && auth()->check()) {
            return 'user_' . auth()->id();
        }
        
        if (function_exists('request') && request()->ip()) {
            // Fallback to session ID + IP for guest users
            return 'guest_' . md5(session()->getId() . '_' . request()->ip());
        }
        
        // Final fallback to session ID only
        return 'session_' . session()->getId();
    }

    /**
     * Get default visible columns
     */
    protected function getDefaultVisibleColumns()
    {
        return $this->optimizedMapWithKeys($this->columns, function ($column, $identifier) {
            // Default to visible unless explicitly hidden
            $isVisible = ! isset($column['hide']) || ! $column['hide'];

            return [$identifier => $isVisible];
        });
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
        return $this->buildUnifiedQuery()->lazy(1000); // Use lazy collection for memory efficiency
    }

    /**
     * Clear distinct values cache
     */
    public function clearDistinctValuesCache()
    {
        $cachePattern = "datatable_distinct_{$this->tableId}_*";
        
        // Use targeted cache clearing instead of flushing all cache
        if (method_exists($this, 'clearCacheByPattern')) {
            $this->clearCacheByPattern($cachePattern);
        } else {
            // Fallback: only flush in development/testing environments
            if (app()->environment(['testing', 'local'])) {
                \Illuminate\Support\Facades\Cache::flush();
            } else {
                Log::warning("Unable to clear cache pattern: {$cachePattern}. clearCacheByPattern method not available.");
            }
        }
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
        // Get the filter type for the current filter column
        $filterType = isset($this->filters[$this->filterColumn]['type']) 
            ? $this->filters[$this->filterColumn]['type'] 
            : 'text';
        
        // For text filters, only process if minimum 3 characters or empty
        if ($filterType === 'text' && !empty($this->filterValue) && strlen(trim($this->filterValue)) < 3) {
            // Don't reset page or trigger search for text with less than 3 characters
            return;
        }
        
        $this->resetPage();
        
        // Emit event for frontend handling
        $this->dispatch('filterValueUpdated', $this->filterColumn, $this->filterValue);
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
                // For relations, we need to ensure foreign key columns are selected
                $relationParts = explode('.', explode(':', $column['relation'])[0]);
                $relationName = $relationParts[0];
                
                // Get the foreign key for this relation from the model
                try {
                    $modelInstance = $this->model::make();
                    $relation = $modelInstance->$relationName();
                    
                    if (method_exists($relation, 'getForeignKeyName')) {
                        $foreignKey = $relation->getForeignKeyName();
                        if (!in_array($foreignKey, $selects)) {
                            $selects[] = $foreignKey;
                        }
                    } elseif (method_exists($relation, 'getQualifiedForeignKeyName')) {
                        $foreignKey = basename($relation->getQualifiedForeignKeyName());
                        if (!in_array($foreignKey, $selects)) {
                            $selects[] = $foreignKey;
                        }
                    }
                } catch (\Exception $e) {
                    // If we can't determine the foreign key, skip silently
                }
                
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
            
            // Get filter type for determining search behavior
            $filterType = isset($this->filters[$this->filterColumn]['type']) 
                ? $this->filters[$this->filterColumn]['type'] 
                : 'text';

            // For text filters, default to LIKE search
            if ($filterType === 'text' && $operator === '=') {
                $operator = 'LIKE';
            }

            if (isset($this->columns[$this->filterColumn]['relation'])) {
                // Handle relation filtering
                [$relationName, $relationColumn] = explode(':', $this->columns[$this->filterColumn]['relation']);
                $query->whereHas($relationName, function ($q) use ($relationColumn, $operator, $value) {
                    if (strtoupper($operator) === 'LIKE') {
                        $q->where($relationColumn, 'LIKE', '%'.$value.'%');
                    } else {
                        $q->where($relationColumn, $operator, $value);
                    }
                });
            } else {
                // Handle regular column filtering
                if (strtoupper($operator) === 'LIKE') {
                    $query->where($this->filterColumn, 'LIKE', '%'.$value.'%');
                } else {
                    $query->where($this->filterColumn, $operator, $value);
                }
            }
        }
    }

    /**
     * Get query builder instance (DEPRECATED - use buildUnifiedQuery)
     * @deprecated Use buildUnifiedQuery() instead for better performance
     */
    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->buildUnifiedQuery();
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
        $sortColumnConfig = null;
        foreach ($this->columns as $col) {
            if (isset($col['key']) && $col['key'] === $this->sortColumn) {
                $sortColumnConfig = $col;
                break;
            }
        }

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
            // Ensure columns are qualified for GROUP BY to prevent ambiguous errors
            $tableName = $this->model::make()->getTable();
            $qualifiedGroupBy = array_map(function($column) use ($tableName) {
                // Skip already qualified columns or functions
                if (strpos($column, '.') !== false || strpos($column, '(') !== false) {
                    return $column;
                }
                return $tableName . '.' . $column;
            }, $selectedColumns);
            
            $query->groupBy($qualifiedGroupBy);
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
            // Skip function columns - they don't need database columns
            if (isset($column['function'])) {
                continue;
            }
            
            if (isset($column['raw']) && is_string($column['raw'])) {
                // Match $row->property but exclude $row->method() calls
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)\s*(?!\()/i', $column['raw'], $matches);
                if (! empty($matches[1])) {
                    // Filter out method calls and only include actual properties/columns
                    $filteredColumns = array_filter($matches[1], function ($columnName) {
                        // Only include if it's a valid database column
                        return $this->isValidColumn($columnName);
                    });
                    $neededColumns = array_merge($neededColumns, $filteredColumns);
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

    /**
     * Check if a column is searchable
     */
    protected function isColumnSearchable($column): bool
    {
        // Skip if explicitly marked as non-searchable
        if (isset($column['searchable']) && !$column['searchable']) {
            return false;
        }
        
        // Skip function columns (computed values)
        if (isset($column['function'])) {
            return false;
        }
        
        // Skip JSON columns that don't have a searchable path
        if (isset($column['json']) && !isset($column['searchable_json_path'])) {
            return false;
        }
        
        // Allow relation columns if they have valid relation string
        if (isset($column['relation'])) {
            return $this->validateRelationString($column['relation']);
        }
        
        // Allow regular columns with valid keys
        if (isset($column['key'])) {
            return $this->isValidColumn($column['key']);
        }
        
        return false;
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
