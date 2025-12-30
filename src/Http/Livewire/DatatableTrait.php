<?php

namespace ArtflowStudio\Table\Http\Livewire;

// Core Unified Traits - Consolidated for conflict resolution
use ArtflowStudio\Table\Traits\Core\HasUnifiedSearch;
use ArtflowStudio\Table\Traits\Core\HasUnifiedValidation;
use ArtflowStudio\Table\Traits\Core\HasTemplateRendering;
use ArtflowStudio\Table\Traits\Core\HasActionHandling;

// Essential Core Traits - No conflicts detected
use ArtflowStudio\Table\Traits\Core\HasBasicFeatures;
use ArtflowStudio\Table\Traits\Core\HasDataValidation;
use ArtflowStudio\Table\Traits\Core\HasSorting;
use ArtflowStudio\Table\Traits\Core\HasUnifiedCaching;
use ArtflowStudio\Table\Traits\Core\HasRelationships;
use ArtflowStudio\Table\Traits\Core\HasJsonSupport;
use ArtflowStudio\Table\Traits\Core\HasJsonFile;
use ArtflowStudio\Table\Traits\Core\HasColumnManagement;
use ArtflowStudio\Table\Traits\Core\HasQueryBuilding;

// UI & Interaction Traits - Reduced conflict footprint
use ArtflowStudio\Table\Traits\UI\HasColumnVisibility;
use ArtflowStudio\Table\Traits\UI\HasEventListeners;
use ArtflowStudio\Table\Traits\UI\HasSortingUI;

// Advanced Features Traits - Performance & API
use ArtflowStudio\Table\Traits\Advanced\HasApiEndpoint;
use ArtflowStudio\Table\Traits\Advanced\HasPerformanceMonitoring;
use ArtflowStudio\Table\Traits\Advanced\HasQueryOptimization;
use ArtflowStudio\Table\Traits\Advanced\HasQueryStringSupport;
use ArtflowStudio\Table\Traits\Advanced\HasSessionManagement;
use ArtflowStudio\Table\Traits\Advanced\HasUnifiedOptimization;
use ArtflowStudio\Table\Traits\Advanced\HasUtilities;
use ArtflowStudio\Table\Traits\Advanced\HasExportFeatures;
use ArtflowStudio\Table\Traits\Advanced\HasCountAggregations;
use ArtflowStudio\Table\Traits\Advanced\HasAutoOptimization;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class DatatableTrait extends Component
{
    // *----------- Unified Traits - Conflict Resolution Complete -----------*//
    
    // Core unified traits - these replace multiple conflicting traits
    use HasUnifiedSearch {
        HasUnifiedSearch::applyOptimizedSearch as searchApplyOptimized;
        HasUnifiedSearch::sanitizeSearch as searchSanitize;
        HasUnifiedSearch::clearSearch as clearUnifiedSearch;
    }
    
    use HasUnifiedValidation {
        HasUnifiedValidation::validateColumn insteadof HasDataValidation;
        HasUnifiedValidation::validateAction as validateUnifiedAction;
    }
    
    use HasTemplateRendering {
        HasTemplateRendering::renderCellValue insteadof HasDataValidation;
        HasTemplateRendering::processTemplate as processUnifiedTemplate;
    }
    
    use HasActionHandling {
        HasActionHandling::executeAction insteadof HasDataValidation;
        HasActionHandling::clearSelectedRows as clearActionSelectedRows;
    }
    
    // Legacy traits with conflict resolution
    use HasQueryBuilding {
        HasQueryBuilding::buildQuery as buildQueryFromTrait;
        HasQueryBuilding::query as getQueryBuilderInstance;
        HasQueryBuilding::applyOptimizedSorting insteadof HasSorting;
        HasQueryBuilding::applyJoinSorting insteadof HasSorting;
        HasUnifiedSearch::applyOptimizedSearch insteadof HasQueryBuilding;
        HasUnifiedSearch::applyColumnSearch insteadof HasQueryBuilding;
        HasUnifiedSearch::applyRelationSearch insteadof HasQueryBuilding;
        HasUnifiedSearch::applyJsonSearch insteadof HasQueryBuilding;
        HasUnifiedSearch::sanitizeSearch insteadof HasQueryBuilding;
    }
    
    // Essential traits - minimal conflicts
    use HasDataValidation {
        HasDataValidation::isValidColumn insteadof HasColumnManagement;
        HasDataValidation::isAllowedColumn insteadof HasColumnManagement;
    }
    
    use HasColumnVisibility {
        HasColumnVisibility::getDefaultVisibleColumns insteadof HasColumnManagement;
        HasColumnVisibility::getValidatedVisibleColumns insteadof HasColumnManagement, HasSessionManagement;
        HasColumnVisibility::getColumnVisibilitySessionKey insteadof HasSessionManagement;
        HasColumnVisibility::getUserIdentifierForSession insteadof HasSessionManagement;
    }
    
    use HasSorting {
        HasSorting::toggleSort as toggleUnifiedSort;
        HasSorting::getSortIcon insteadof HasSortingUI;
        HasSorting::getSortableColumns insteadof HasSortingUI;
        HasSorting::getSortState insteadof HasSortingUI;
    }
    
    use HasUnifiedCaching {
        HasUnifiedCaching::getColumnDistinctValues as getCachedColumnDistinctValues;
        HasUnifiedCaching::getRelationDistinctValues as getCachedRelationDistinctValues;
    }
    
    use HasSessionManagement {
        HasSessionManagement::autoSaveState as autoSaveSessionState;
    }
    
    use HasExportFeatures {
        HasExportFeatures::exportWithChunking as exportWithChunks;
        HasExportFeatures::export as exportUnified;
    }
    
    use HasRelationships {
        HasRelationships::validateRelationColumns insteadof HasDataValidation;
        HasRelationships::validateRelationString insteadof HasUtilities;
    }
    
    use HasJsonFile {
        HasJsonFile::applyJsonFilter insteadof HasQueryBuilding;
    }
    
    use HasUtilities {
        HasDataValidation::sanitizeHtmlContent insteadof HasUtilities;
        HasDataValidation::validateJsonPath insteadof HasUtilities;
        HasDataValidation::validateExportFormat insteadof HasUtilities;
        HasDataValidation::sanitizeFilterValue insteadof HasUtilities;
        HasUtilities::sanitizeUtilitySearch as sanitizeSearchFromUtilities;
        HasUtilities::generateBasicCacheKey as generateUtilityCacheKey;
    }
    
    // Remaining traits with no conflicts
    use HasBasicFeatures;
    use HasApiEndpoint;
    use HasColumnManagement;
    use HasJsonSupport {
        HasTemplateRendering::getNestedValue insteadof HasJsonSupport;
    }
    use HasPerformanceMonitoring;
    use HasQueryOptimization {
        HasQueryBuilding::applyOptimizedSorting insteadof HasQueryOptimization;
    }
    use HasQueryStringSupport;
    use HasUnifiedOptimization;
    use HasEventListeners;
    use HasSortingUI {
        HasSortingUI::isSorted insteadof HasSorting;
    }
    use HasCountAggregations;
    use HasAutoOptimization;
    use WithPagination;

    // *----------- Properties -----------*//
    public $model;
    public $columns = [];
    public $visibleColumns = [];
    public $checkbox = false;
    public $search = '';
    public $sortColumn = null;
    public $sortBy = null; // Alias for sortColumn - for user convenience
    public $sortDirection = 'asc';
    public $selectedRows = [];
    public $selectAll = false;
    public $filters = [];
    public $filterColumn = null;
    public $filterOperator = '=';
    public $filterValue = null;
    public $multipleFilters = [];
    public $filterInstances = [];
    public $dateColumn = null;
    public $startDate = null;
    public $endDate = null;
    public $selectedColumn = null;
    public $numberOperator = '=';
    public $distinctValues = [];
    public $columnType = null;
    public $index = false;
    public $tableId = null;
    public $query = null;
    public $colvisBtn = true;
    public $perPage = 10;
    public $records = 10; // For template pagination dropdown wire:model
    public array $routeFallbacks = [];
    public $actions = [];

    // Performance optimization properties
    protected $cachedRelations = null;
    protected $cachedSelectColumns = null;
    protected $distinctValuesCacheTime = 300;
    protected $maxDistinctValues = 1000;
    
    // Phase 1 Performance Optimizations
    protected $cachedQueryResults = null;
    protected $cachedQueryHash = null;
    protected $distinctValuesCache = [];

    // *----------- Optional Configuration -----------*//
    public $searchable = true;
    public $exportable = false;
    public $printable = false;
    public $colSort = true;
    public $sort = 'desc';
    public $refreshBtn = false;

    // *----------- Query String Parameters -----------*//
    public $queryString = [
        'records' => ['except' => 10],
        'perPage' => ['except' => 10], // Keep both for compatibility
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

    // *----------- INITIALIZATION AND CONFIGURATION METHODS -----------*//

    /**
     * Initialize columns from configuration
     * Normalizes column configuration to ensure consistency
     * Supports both direct arrays and named arrays
     * 
     * Note: sortable/searchable are auto-enabled by HasAutoOptimization trait
     * No need to manually set them - just provide key and label
     */
    protected function initializeColumns(array $columns): array
    {
        $initialized = [];
        
        foreach ($columns as $key => $config) {
            if (is_string($config)) {
                // Simple string configuration: 'user_name' => 'user_name'
                $columnKey = $config;
                $initialized[$columnKey] = [
                    'key' => $config,
                    'label' => ucfirst(str_replace('_', ' ', $config)),
                    // sortable/searchable auto-enabled by autoOptimizeColumns()
                ];
            } elseif (is_array($config)) {
                // Array configuration - check if it's a direct array or named array
                if (isset($config['key'])) {
                    // Direct array format: ['key' => 'id', 'label' => 'ID']
                    $columnKey = $config['key'];
                    $initialized[$columnKey] = array_merge([
                        'key' => $columnKey,
                        'label' => ucfirst(str_replace('_', ' ', $columnKey)),
                        // sortable/searchable auto-enabled by autoOptimizeColumns()
                    ], $config);
                } else {
                    // Named array format: 'id' => ['label' => 'ID'] (legacy support)
                    $columnKey = $key;
                    $initialized[$columnKey] = array_merge([
                        'key' => $columnKey,
                        'label' => ucfirst(str_replace('_', ' ', $columnKey)),
                        // sortable/searchable auto-enabled by autoOptimizeColumns()
                    ], $config);
                }
            }
        }
        
        return $initialized;
    }

    /**
     * Initialize column configuration metadata
     * Sets up eager loading, caching, and optimization
     */
    protected function initializeColumnConfiguration(array $columns): void
    {
        // Calculate required relations for eager loading
        $this->cachedRelations = $this->calculateRequiredRelations($columns);
        
        // Calculate select columns for optimization
        $this->cachedSelectColumns = $this->calculateSelectColumns($columns);
    }

    /**
     * Calculate required relations for eager loading optimization
     * Extracts all relation names from column configurations
     */
    protected function calculateRequiredRelations(array $columns): array
    {
        $relations = [];
        
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }
            
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                [$relationPath, $attribute] = explode(':', $relationString);
                
                // Add all nested relation paths
                // e.g., 'student.user:name' => ['student', 'student.user']
                $parts = explode('.', $relationPath);
                $path = '';
                
                foreach ($parts as $part) {
                    $path = $path ? "{$path}.{$part}" : $part;
                    if (!in_array($path, $relations)) {
                        $relations[] = $path;
                    }
                }
            }
        }
        
        return $relations;
    }

    /**
     * Calculate select columns for query optimization
     * Returns array of columns to select from main table
     */
    protected function calculateSelectColumns(array $columns): array
    {
        $selectColumns = ['*']; // Start with all columns from main table
        
        // If we want to optimize further, we could specify exact columns
        // But for safety and compatibility, we use '*'
        
        return $selectColumns;
    }

    /**
     * Get the optimal sort column for initialization
     * Returns first sortable column or 'id'
     */
    protected function getOptimalSortColumn(): ?string
    {
        // Try to find first sortable column
        foreach ($this->columns as $columnKey => $column) {
            if (!is_array($column)) {
                continue;
            }
            
            $sortable = $column['sortable'] ?? true;
            if ($sortable && !isset($column['function']) && !isset($column['raw'])) {
                return $columnKey;
            }
        }
        
        // Default to id if it exists
        if (isset($this->columns['id'])) {
            return 'id';
        }
        
        return null;
    }

    // *----------- CORE LIVEWIRE METHODS -----------*//

    /**
     * Component Initialization - Core Livewire method
     * PERFORMANCE FIX: Pre-load distinct values on mount
     */
    public function mount($model, $columns, $filters = [], $actions = [], $index = false, $tableId = null, $query = null, $countAggregations = [], $sortBy = null, $sortDirection = null, $sort = null)
    {
        $this->model = $model;
        $this->tableId = $tableId ?? (is_string($model) ? $model : (is_object($model) ? get_class($model) : uniqid('datatable_')));
        $this->query = $query;

        // Initialize count aggregations FIRST before anything else
        if (!empty($countAggregations)) {
            $this->setCountAggregations($countAggregations);
        }

        // Delegate column initialization to HasColumnManagement trait
        $this->columns = $this->initializeColumns($columns);
        $this->initializeColumnConfiguration($this->columns);

        // AUTO-OPTIMIZATION: Auto-detect and apply optimizations (NO MANUAL CONFIG NEEDED!)
        $this->autoDetectCountAggregations();  // Detect _count columns
        $this->autoOptimizeColumns();          // Auto-enable sorting/searching and eager loading
        $this->autoDetectOptimalSort();        // Auto-detect best sort column
        $this->autoApplyEagerLoading($this->model::query());  // Prepare eager loading

        // Delegate column visibility to HasColumnVisibility trait  
        $this->initializeColumnVisibility();

        $this->filters = $filters;
        $this->actions = $actions;
        $this->index = $index;

        // Handle sorting parameters: sortBy takes precedence over auto-detection
        if (!empty($sortBy)) {
            $this->sortColumn = $sortBy;
            $this->sortBy = $sortBy;
        }
        
        // Handle sort direction: sortDirection takes precedence, fallback to sort for backward compatibility
        if (!empty($sortDirection)) {
            $this->sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc';
        } elseif (!empty($sort)) {
            // Backward compatibility: 'sort' parameter can specify direction
            $this->sortDirection = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
        }

        // Delegate sort optimization to HasSorting trait - only if not already set
        if (empty($this->sortColumn)) {
            $this->sortColumn = $this->getOptimalSortColumn();
        }
        
        // Keep sortBy in sync with sortColumn for Livewire reactivity
        if (empty($this->sortBy) && !empty($this->sortColumn)) {
            $this->sortBy = $this->sortColumn;
        }
        
        // PERFORMANCE FIX: Pre-load all distinct values on mount
        $this->preloadDistinctValues();
    }
    
    /**
     * Pre-load distinct values for all select/distinct filters
     * PERFORMANCE FIX: Prevents repeated database queries on each render
     */
    protected function preloadDistinctValues(): void
    {
        foreach ($this->filters as $filterKey => $filterConfig) {
            $filterType = $filterConfig['type'] ?? null;
            if (in_array($filterType, ['select', 'distinct'])) {
                try {
                    $this->distinctValuesCache[$filterKey] = $this->loadDistinctValuesForFilter($filterKey);
                } catch (\Exception $e) {
                    // Silently fail for individual filters to not break the component
                    Log::warning("Failed to preload distinct values for filter: {$filterKey}", [
                        'error' => $e->getMessage()
                    ]);
                    $this->distinctValuesCache[$filterKey] = [];
                }
            }
        }
    }
    
    /**
     * Load distinct values for a specific filter
     * Internal method used by preloadDistinctValues()
     */
    protected function loadDistinctValuesForFilter(string $filterKey): array
    {
        if (!isset($this->columns[$filterKey]) || !isset($this->filters[$filterKey])) {
            return [];
        }

        try {
            // Use the existing cached method from traits
            return $this->getCachedColumnDistinctValues($filterKey);
        } catch (\Exception $e) {
            Log::warning("Error loading distinct values for {$filterKey}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Core query building - Unique to main class
     * PERFORMANCE FIX: Consolidated filter logic to prevent duplicates
     */
    protected function buildUnifiedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Start with base model query
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            $this->applyCustomQueryConstraints($query);
        }

        // Apply count aggregations FIRST to prevent N+1 queries
        $query = $this->applyCountAggregations($query);

        // Delegate optimization to traits
        $query = $this->applyColumnOptimization($query);
        $query = $this->applyOptimizedEagerLoading($query);

        // Delegate search to HasUnifiedSearch trait
        if ($this->searchable && $this->search && strlen(trim($this->search)) >= 3) {
            $this->applyOptimizedSearch($query);
        }

        // PERFORMANCE FIX: Apply all filters through consolidated method
        $this->applyAllFilters($query);

        // Delegate sorting to HasSorting trait
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        return $query;
    }
    
    /**
     * Apply all filters in a consolidated way to prevent duplicates
     * PERFORMANCE FIX: Consolidates filter logic from multiple sources
     */
    protected function applyAllFilters(\Illuminate\Database\Eloquent\Builder $query): void
    {
        // Primary filter
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            if (isset($this->filters[$this->filterColumn]['relation'])) {
                $this->applyRelationFilter($query, $this->filterColumn, $this->filterOperator, $this->filterValue);
            } else {
                $this->applyColumnFilter($query, $this->filterColumn, $this->filterOperator, $this->filterValue);
            }
        }

        // Additional filters from filterInstances
        if (!empty($this->filterInstances)) {
            $this->applyMultipleFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $this->applyDateRangeFilter($query, $this->dateColumn, $this->startDate, $this->endDate);
        }
        
        // Note: applyAdvancedFilters is NOT called here as it would duplicate filters
        // The filters are already applied through filterColumn and filterInstances
    }

    /**
     * Get the per-page value for pagination
     */
    public function getPerPageValue(): int
    {
        // Use records if set (from template), otherwise use perPage
        return $this->records ?? $this->perPage ?? 10;
    }

    /**
     * Get data using the unified query - Core Livewire method
     */
    public function getData()
    {
        $this->triggerBeforeQuery(null);
        
        try {
            $query = $this->buildUnifiedQuery();
            return $query->paginate($this->getPerPageValue());
        } catch (\Exception $e) {
            Log::error('DatatableTrait getData error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Render the component - Core Livewire method
     * PERFORMANCE FIX: Cache query results between renders when nothing changed
     */
    public function render()
    {
        $queryHash = $this->generateQueryHash();
        
        // Return cached results if query parameters haven't changed
        if ($this->cachedQueryHash === $queryHash && $this->cachedQueryResults !== null) {
            $data = $this->cachedQueryResults;
        } else {
            $data = $this->getData();
            $this->cachedQueryResults = $data;
            $this->cachedQueryHash = $queryHash;
        }

        return view('artflow-table::livewire.datatable-trait', [
            'data' => $data,
            'index' => $this->index,
        ]);
    }
    
    /**
     * Generate a hash of all query-affecting parameters
     * Used to determine if we need to re-query the database
     */
    protected function generateQueryHash(): string
    {
        return md5(json_encode([
            'search' => $this->search,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'filterColumn' => $this->filterColumn,
            'filterValue' => $this->filterValue,
            'filterOperator' => $this->filterOperator,
            'filterInstances' => $this->filterInstances,
            'dateColumn' => $this->dateColumn,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'page' => $this->page ?? 1,
            'perPage' => $this->getPerPageValue(),
        ]));
    }
    
    /**
     * Invalidate cached query results
     * Call this whenever query-affecting parameters change
     */
    protected function invalidateQueryCache(): void
    {
        $this->cachedQueryResults = null;
        $this->cachedQueryHash = null;
    }

    // *----------- LIVEWIRE LIFECYCLE METHODS -----------*//

    /**
     * Handle search updates - Core Livewire lifecycle
     * PERFORMANCE FIX: Only trigger events and reset page when value actually changed
     */
    public function updatedSearch($value)
    {
        // Skip if value hasn't changed
        if ($value === $this->search) {
            return;
        }
        
        $oldValue = $this->search;
        $this->search = $value;
        
        // Only reset page and invalidate cache if search is meaningful
        if (strlen(trim($value)) >= 3 || strlen(trim($oldValue)) >= 3) {
            $this->resetPage();
            $this->invalidateQueryCache();
        }

        $this->triggerSearchEvent($value, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->saveSearchToSession($value);
        }
    }

    /**
     * Handle filter updates - Core Livewire lifecycle
     */
    public function updatedFilters($value, $key)
    {
        $columnKey = str_replace('filters.', '', $key);
        $oldValue = $this->filters[$columnKey] ?? null;

        $this->resetPage();
        $this->triggerFilterEvent($columnKey, $value, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->saveFiltersToSession();
        }
    }

    /**
     * Handle filter column changes - Reset filter value when column changes
     * This prevents the value from previous column persisting
     * PERFORMANCE FIX: Invalidate cache when filter changes
     */
    public function updatedFilterColumn($value)
    {
        // Reset filter value and operator when column changes
        $this->filterValue = null;
        $this->filterOperator = '=';
        $this->resetPage();
        $this->invalidateQueryCache();
    }

    /**
     * Handle sorting - Core Livewire lifecycle
     * PERFORMANCE FIX: Invalidate cache when sort changes
     * Now syncs both sortColumn and sortBy for consistency
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

        // Keep sortBy in sync with sortColumn
        $this->sortBy = $this->sortColumn;

        $this->resetPage();
        $this->invalidateQueryCache();
        $this->triggerSortEvent($this->sortColumn, $this->sortDirection, $oldColumn, $oldDirection);

        if ($this->enableSessionPersistence) {
            $this->saveSortingToSession();
        }
    }

    /**
     * Handle per page changes - Core Livewire lifecycle
     */
    public function updatedPerPage($value)
    {
        $oldValue = $this->perPage;
        $this->perPage = $value;
        $this->resetPage();

        $this->triggerPaginationEvent(1, $value, $this->page, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->savePerPageToSession($value);
        }
    }

    /**
     * Handle records per page update (legacy method for template compatibility)
     */
    public function updatedrecords($value)
    {
        $this->perPage = $value;
        $this->records = $value; // Keep both in sync
        $this->resetPage();
    }

    /**
     * Toggle column visibility - Delegates to HasColumnVisibility
     */
    public function toggleColumn($columnKey)
    {
        $this->toggleColumnVisibility($columnKey);
    }

    // *----------- USER ACTION DELEGATES -----------*//

    /**
     * Add a new filter instance
     */
    public function addFilterInstance()
    {
        $this->filterInstances[] = [
            'id' => uniqid(),
            'column' => null,
            'operator' => '=',
            'value' => null
        ];
    }

    /**
     * Remove a filter instance by ID
     */
    public function removeFilterInstance($instanceId)
    {
        $this->filterInstances = array_filter($this->filterInstances, function($instance) use ($instanceId) {
            return $instance['id'] !== $instanceId;
        });
        $this->resetPage();
    }

    /**
     * Update a filter instance
     * PERFORMANCE FIX: Invalidate cache when filter changes
     */
    public function updateFilterInstance($instanceId, $field, $value)
    {
        foreach ($this->filterInstances as &$instance) {
            if ($instance['id'] === $instanceId) {
                $instance[$field] = $value;
                break;
            }
        }
        $this->resetPage();
        $this->invalidateQueryCache();
    }

    /**
     * Apply multiple filter instances to query
     */
    protected function applyMultipleFilters(\Illuminate\Database\Eloquent\Builder $query): void
    {
        foreach ($this->filterInstances as $filterInstance) {
            if (!$filterInstance['column'] || $filterInstance['value'] === null || $filterInstance['value'] === '') {
                continue;
            }

            $column = $filterInstance['column'];
            $operator = $filterInstance['operator'] ?? '=';
            $value = $filterInstance['value'];

            // Check if this is a relation filter
            if (isset($this->filters[$column]['relation'])) {
                $this->applyRelationFilter($query, $column, $operator, $value);
            } else {
                // Apply regular column filter
                $this->applyColumnFilter($query, $column, $operator, $value);
            }
        }
    }

    /**
     * Apply relation filter for multiple filters
     * FIX: Handle distinct filters differently from search/manual filters
     * Distinct filters use IDs, so we filter by foreign key directly
     * Other filters search through the relation
     */
    protected function applyRelationFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, string $operator, $value): void
    {
        $relationConfig = $this->filters[$column]['relation'] ?? null;
        
        if (!$relationConfig) {
            // Fallback to direct column filtering if no relation config
            $this->applyOperatorCondition($query, $column, $operator, $value);
            return;
        }
        
        // Check if this is a distinct filter
        $filterType = $this->filters[$column]['type'] ?? null;
        
        if ($filterType === 'distinct') {
            // DISTINCT FILTERS: Use foreign key directly
            // The distinct dropdown sends the ID (e.g., module_id = 1)
            // So we filter by the foreign key column directly
            $this->applyOperatorCondition($query, $column, $operator, $value);
            return;
        }
        
        // NON-DISTINCT FILTERS: Filter through the relation
        // Parse the relation string
        $parsed = $this->parseRelationString($relationConfig);
        
        if (empty($parsed)) {
            return;
        }
        
        $relationPath = $parsed['relationPath'];
        $attribute = $parsed['attribute'];

        // Filter through the relation for search/manual filters
        if (strpos($relationPath, '.') !== false) {
            // Nested relation - use nested whereHas
            $this->applyNestedRelationFilter($query, $relationPath, $attribute, $operator, $value);
        } else {
            // Simple relation - use standard whereHas
            $query->whereHas($relationPath, function ($q) use ($attribute, $operator, $value) {
                $this->applyOperatorCondition($q, $attribute, $operator, $value);
            });
        }
    }

    /**
     * Apply nested relation filter for multi-level relations
     * e.g., 'student.user:name' => whereHas('student', function() { whereHas('user', ...) })
     */
    protected function applyNestedRelationFilter(\Illuminate\Database\Eloquent\Builder $query, string $relationPath, string $attribute, string $operator, $value): void
    {
        $parts = explode('.', $relationPath);
        
        // Build nested whereHas calls from outside in
        $this->buildNestedWhereHas($query, $parts, $attribute, $operator, $value);
    }

    /**
     * Recursively build nested whereHas clauses
     */
    protected function buildNestedWhereHas(\Illuminate\Database\Eloquent\Builder $query, array $relationParts, string $attribute, string $operator, $value, int $depth = 0): void
    {
        if ($depth >= count($relationParts)) {
            return;
        }

        $currentRelation = $relationParts[$depth];
        
        if ($depth === count($relationParts) - 1) {
            // Last relation - apply the filter
            $query->whereHas($currentRelation, function ($q) use ($attribute, $operator, $value) {
                $this->applyOperatorCondition($q, $attribute, $operator, $value);
            });
        } else {
            // Intermediate relation - nest another whereHas
            $query->whereHas($currentRelation, function ($q) use ($relationParts, $attribute, $operator, $value, $depth) {
                $this->buildNestedWhereHas($q, $relationParts, $attribute, $operator, $value, $depth + 1);
            });
        }
    }

    /**
     * Apply column filter for multiple filters
     */
    protected function applyColumnFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, string $operator, $value): void
    {
        $this->applyOperatorCondition($query, $column, $operator, $value);
    }

    /**
     * Apply operator condition to query
     */
    protected function applyOperatorCondition(\Illuminate\Database\Eloquent\Builder $query, string $column, string $operator, $value): void
    {
        switch ($operator) {
            case '=':
                $query->where($column, $value);
                break;
            case '!=':
                $query->where($column, '!=', $value);
                break;
            case '>':
                $query->where($column, '>', $value);
                break;
            case '>=':
                $query->where($column, '>=', $value);
                break;
            case '<':
                $query->where($column, '<', $value);
                break;
            case '<=':
                $query->where($column, '<=', $value);
                break;
            case 'like':
                $query->where($column, 'LIKE', '%' . $value . '%');
                break;
            default:
                $query->where($column, $value);
                break;
        }
    }

    /**
     * Clear all filter instances
     */
    public function clearAllFilterInstances()
    {
        $this->filterInstances = [];
        $this->resetPage();
    }

    /**
     * Clear all filters - Delegates to HasUserActions
     */
    public function clearAllFilters()
    {
        $this->clearFilters();
    }

    /**
     * Clear search - Delegates to HasUserActions  
     */
    public function clearSearch()
    {
        $this->clearSearchInput();
    }

    /**
     * Handle export - Delegates to HasExportFeatures
     */
    public function handleExport($format = 'csv', $filename = null)
    {
        try {
            // Build query with current filters
            $query = $this->buildUnifiedQuery();
            
            // Get all data without pagination
            $data = $query->get();
            
            if ($data->isEmpty()) {
                $this->dispatch('showAlert', [
                    'type' => 'warning', 
                    'message' => 'No data to export'
                ]);
                return;
            }
            
            // Prepare filename
            if (!$filename) {
                $modelName = class_basename($this->model);
                $filename = strtolower($modelName) . '_export_' . date('Y-m-d_H-i-s');
            }
            
            // Export based on format
            switch (strtolower($format)) {
                case 'csv':
                    return $this->exportToCsv($data, $filename);
                case 'excel':
                case 'xlsx':
                    return $this->exportToExcel($data, $filename);
                default:
                    return $this->exportToCsv($data, $filename);
            }
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export error: ' . $e->getMessage());
            $this->dispatch('showAlert', [
                'type' => 'error', 
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Export data to CSV
     */
    protected function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];
        
        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            
            // Get headers from first row
            if ($data->isNotEmpty()) {
                $firstRow = $data->first();
                $headers = [];
                
                // Extract headers from column configuration
                foreach ($this->columns as $columnKey => $columnConfig) {
                    if (is_string($columnConfig)) {
                        $headers[] = ucfirst(str_replace('_', ' ', $columnKey));
                    } elseif (is_array($columnConfig)) {
                        $headers[] = $columnConfig['label'] ?? ucfirst(str_replace('_', ' ', $columnKey));
                    }
                }
                
                // Write headers
                fputcsv($output, $headers);
                
                // Write data rows
                foreach ($data as $row) {
                    $rowData = [];
                    foreach ($this->columns as $columnKey => $columnConfig) {
                        if (is_string($columnConfig)) {
                            $rowData[] = $row->{$columnKey} ?? '';
                        } elseif (is_array($columnConfig)) {
                            // For columns with keys, use the key value
                            if (isset($columnConfig['key']) && !empty($columnConfig['key'])) {
                                $value = data_get($row, $columnConfig['key']);
                                $rowData[] = $value ?? '';
                            } 
                            // For raw columns without keys, try to extract meaningful data
                            else {
                                // Skip computed/raw columns for export
                                $rowData[] = '';
                            }
                        }
                    }
                    fputcsv($output, $rowData);
                }
            }
            
            fclose($output);
        }, $filename . '.csv', $headers);
    }

    /**
     * Handle bulk actions - Delegates to HasBulkActions
     */
    public function handleBulkAction($actionKey)
    {
        return $this->executeBulkAction($actionKey);
    }

    /**
     * Select all visible records - Delegates to HasBulkActions
     */
    public function selectAllVisible()
    {
        $this->selectAllOnPage();
    }

    /**
     * Clear all selections - Delegates to HasBulkActions
     */
    public function clearAllSelections()
    {
        $this->clearSelection();
    }

    /**
     * Refresh component - Delegates to HasUtilities
     */
    public function refresh()
    {
        $this->refreshComponent();
    }

    // *----------- CONFIGURATION AND STATS -----------*//

    /**
     * Get component statistics - Delegates to HasPerformanceMonitoring
     */
    public function getComponentStats(): array
    {
        return $this->getPerformanceStats();
    }

    /**
     * Get query string properties - Delegates to HasQueryStringSupport
     */
    public function getQueryString()
    {
        if (!$this->enableQueryStringSupport) {
            return [];
        }

        return $this->getQueryStringConfiguration();
    }

    /**
     * Get debug information - Delegates to HasUtilities
     */
    public function getDebugInfo(): array
    {
        return $this->getComponentDebugInfo();
    }

    /**
     * Get distinct values for a column (required by template for filter dropdowns)
     * PERFORMANCE FIX: Return from component-lifetime cache
     */
    public function getDistinctValues($columnKey)
    {
        // Return from cache if available
        if (isset($this->distinctValuesCache[$columnKey])) {
            return $this->distinctValuesCache[$columnKey];
        }
        
        // Check if column exists and has filter configuration
        if (!isset($this->columns[$columnKey]) || !isset($this->filters[$columnKey])) {
            return [];
        }

        try {
            // For relation columns, get distinct from related table
            if (isset($this->filters[$columnKey]['relation'])) {
                $values = $this->getCachedRelationDistinctValues($columnKey);
            } else {
                // For regular columns, get distinct from main table
                $values = $this->getCachedColumnDistinctValues($columnKey);
            }
            
            // Cache the result
            $this->distinctValuesCache[$columnKey] = $values;
            return $values;
        } catch (\Exception $e) {
            Log::warning('DatatableTrait getDistinctValues error: ' . $e->getMessage(), [
                'column' => $columnKey
            ]);
            $this->distinctValuesCache[$columnKey] = [];
            return [];
        }
    }

    // *----------- SEARCH SUPPORT METHODS -----------*//

    /**
     * Get searchable columns from current column configuration
     * Required by search traits but not defined anywhere
     */
    protected function getSearchableColumns(): array
    {
        $searchableColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            if ($this->isColumnSearchable($columnKey)) {
                $searchableColumns[] = $columnKey;
            }
        }
        
        return $searchableColumns;
    }

    /**
     * Get searchable relations from column configuration
     * Extracts relation columns for relation-based searching
     * Required by HasUnifiedSearch trait
     */
    protected function getSearchableRelations(): array
    {
        $relationColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            if (!is_array($column)) {
                continue;
            }
            
            if (!isset($column['relation'])) {
                continue;
            }
            
            // Check if this column is searchable
            if (isset($column['searchable']) && $column['searchable'] === false) {
                continue;
            }
            
            $relationString = $column['relation'];
            [$relationPath, $attribute] = explode(':', $relationString);
            
            // Parse the relation path
            $relationParts = explode('.', $relationPath);
            $firstRelation = $relationParts[0];
            
            if (!isset($relationColumns[$firstRelation])) {
                $relationColumns[$firstRelation] = [];
            }
            
            // For simple relations, add the attribute
            if (count($relationParts) === 1) {
                $relationColumns[$firstRelation][] = $attribute;
            } else {
                // For nested relations like 'student.user:name'
                // We need to use nested where has
                // Store the full nested path
                if (!isset($relationColumns[$relationPath])) {
                    $relationColumns[$relationPath] = [];
                }
                $relationColumns[$relationPath][] = $attribute;
            }
        }
        
        return $relationColumns;
    }

    /**
     * Get searchable JSON columns from configuration
     * Extracts JSON columns for JSON-based searching
     * Required by HasUnifiedSearch trait
     */
    protected function getSearchableJsonColumns(): array
    {
        $jsonColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            if (!is_array($column)) {
                continue;
            }
            
            if (!isset($column['json'])) {
                continue;
            }
            
            // Check if this column is searchable
            if (isset($column['searchable']) && $column['searchable'] === false) {
                continue;
            }
            
            $jsonPath = $column['json'];
            $actualColumn = $column['key'] ?? $columnKey;
            
            if (!isset($jsonColumns[$actualColumn])) {
                $jsonColumns[$actualColumn] = [];
            }
            
            $jsonColumns[$actualColumn][] = $jsonPath;
        }
        
        return $jsonColumns;
    }

    /**
     * Parse a relation string into components
     * Handles nested relations like 'student.user:name' => ['student', 'student.user'], 'name'
     * Required for proper relation handling in sorting, filtering, and searching
     */
    protected function parseRelationString(string $relationString): array
    {
        if (strpos($relationString, ':') === false) {
            // Invalid format, return empty
            return [];
        }
        
        [$relationPath, $attribute] = explode(':', $relationString, 2);
        
        // Build progressive paths for nested relations
        $relations = [];
        $parts = explode('.', $relationPath);
        $path = '';
        
        foreach ($parts as $part) {
            $path = $path ? "{$path}.{$part}" : $part;
            $relations[] = $path;
        }
        
        return [
            'relations' => $relations,
            'relationPath' => $relationPath,
            'attribute' => $attribute,
        ];
    }

    // *----------- TRAIT CONFLICT RESOLUTION -----------*//

    /**
     * Clear selection - Resolves conflict between HasActions and HasBulkActions
     */
    public function clearSelection()
    {
        $this->clearBulkSelection();
        $this->clearActionSelection();
        $this->dispatch('selectionCleared');
    }

    /**
     * Get selected count - Resolves conflict between HasActions and HasBulkActions
     */
    public function getSelectedCount(): int
    {
        $bulkCount = $this->getBulkSelectedCount();
        $actionCount = $this->getActionSelectedCount();
        return max($bulkCount, $actionCount);
    }

    /**
     * Check if a column is searchable (required by search traits)
     */
    protected function isColumnSearchable($column): bool
    {
        // Check if column is defined in our column configuration
        if (!isset($this->columns[$column])) {
            return false;
        }
        
        $columnConfig = $this->columns[$column];
        
        // If it's a simple string definition, assume it's searchable
        if (is_string($columnConfig)) {
            return true;
        }
        
        // If it's an array, check for explicit searchable flag
        if (is_array($columnConfig)) {
            // If explicitly marked as not searchable, return false
            if (isset($columnConfig['searchable']) && $columnConfig['searchable'] === false) {
                return false;
            }
            
            // Check if this is a raw-only column (has raw but no actual database key)
            $hasKey = isset($columnConfig['key']) && !empty($columnConfig['key']);
            $hasFunction = isset($columnConfig['function']) && !empty($columnConfig['function']);
            $hasRaw = isset($columnConfig['raw']) && !empty($columnConfig['raw']);

            // New rule: if the column is function-based or raw-only and lacks a real 'key', skip searching.
            // This prevents pseudo columns like 'function_8' from being injected into the WHERE clause.
            if (!$hasKey && ($hasFunction || $hasRaw)) {
                return false;
            }
            
            // If it has raw but no key or function, it's a computed column and not searchable in database
            if ($hasRaw && !$hasKey && !$hasFunction) {
                return false;
            }
            
            // If it has a key, check if the key exists in the actual database table
            if ($hasKey) {
                try {
                    // Get the table name from the model
                    $model = $this->model;
                    $tableName = (new $model)->getTable();
                    
                    // Check if the column exists in the database
                    $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
                    
                    // Only search on columns that actually exist in the database
                    return in_array($columnConfig['key'], $columns);
                } catch (\Exception $e) {
                    // If we can't check the database, default to false for safety
                    \Illuminate\Support\Facades\Log::warning('Could not check column existence for search: ' . $e->getMessage());
                    return false;
                }
            }
            
            // Default to true for basic columns unless explicitly disabled
            return $columnConfig['searchable'] ?? true;
        }
        
        return false;
    }

    /**
     * Apply column optimization to query - Missing method implementation
     */
    protected function applyColumnOptimization(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Use cached select columns if available
        if (!empty($this->cachedSelectColumns)) {
            $query->select($this->cachedSelectColumns);
        }
        
        return $query;
    }

    /**
     * Apply optimized eager loading to query - Missing method implementation
     * PERFORMANCE FIX: Enhanced logging and verification
     */
    protected function applyOptimizedEagerLoading(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Use cached relations if available
        if (!empty($this->cachedRelations)) {
            // Log for debugging N+1 issues
            if (config('app.debug')) {
                Log::debug('DatatableTrait: Eager loading relations', [
                    'relations' => $this->cachedRelations,
                    'model' => is_object($this->model) ? get_class($this->model) : $this->model
                ]);
            }
            
            $query->with($this->cachedRelations);
        }
        
        return $query;
    }

    // *----------- ABSTRACT METHOD IMPLEMENTATIONS -----------*//

    /**
     * Implementation of abstract method from HasBladeRendering
     */
    protected function evaluateCondition($actualValue, string $operator, $checkValue): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actualValue == $checkValue;
            case '!=':
            case '<>':
                return $actualValue != $checkValue;
            case '>':
                return $actualValue > $checkValue;
            case '>=':
                return $actualValue >= $checkValue;
            case '<':
                return $actualValue < $checkValue;
            case '<=':
                return $actualValue <= $checkValue;
            case 'contains':
            case 'like':
                return str_contains(strtolower($actualValue), strtolower($checkValue));
            case 'starts_with':
                return str_starts_with(strtolower($actualValue), strtolower($checkValue));
            case 'ends_with':
                return str_ends_with(strtolower($actualValue), strtolower($checkValue));
            case 'in':
                return in_array($actualValue, is_array($checkValue) ? $checkValue : [$checkValue]);
            case 'not_in':
                return !in_array($actualValue, is_array($checkValue) ? $checkValue : [$checkValue]);
            case 'empty':
                return empty($actualValue);
            case 'not_empty':
                return !empty($actualValue);
            default:
                return false;
        }
    }

    /**
     * Implementation of abstract method from HasUserActions
     */
    protected function emit(string $event, ...$params): void
    {
        $this->dispatch($event, ...$params);
    }

    /**
     * Implementation of abstract method from HasValidation
     */
    protected function isValidFilterValue($value, string $type): bool
    {
        switch ($type) {
            case 'string':
            case 'text':
                return is_string($value) || is_numeric($value);
            case 'number':
            case 'integer':
                return is_numeric($value);
            case 'float':
            case 'decimal':
                return is_numeric($value);
            case 'boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);
            case 'date':
                return strtotime($value) !== false;
            case 'array':
                return is_array($value);
            case 'json':
                return is_string($value) && json_decode($value) !== null;
            default:
                return true;
        }
    }

    /**
     * Implementation of abstract method from HasExportFeatures
     */
    protected function exportInChunks(string $format, int $chunkSize): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = [];
        
        $query = $this->buildUnifiedQuery();
        $query->chunk($chunkSize, function ($chunk) use (&$data) {
            foreach ($chunk as $row) {
                $data[] = $row->toArray();
            }
        });

        $filename = 'export_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Implementation of abstract method from HasQueryBuilding
     */
    protected function sanitizeSearch($search): string
    {
        if (!is_string($search)) {
            return '';
        }
        
        // Remove potentially dangerous characters and trim
        $sanitized = trim(strip_tags($search));
        
        // Remove SQL injection patterns
        $sanitized = preg_replace('/[^\w\s\-_@.]/i', '', $sanitized);
        
        return $sanitized;
    }

    /**
     * Implementation of abstract method from HasBladeRendering
     */
    protected function getRowPropertyValue($row, string $property)
    {
        if (is_array($row)) {
            return $row[$property] ?? null;
        }
        
        if (is_object($row)) {
            // Handle Eloquent models
            if (method_exists($row, 'getAttribute')) {
                return $row->getAttribute($property);
            }
            
            // Handle stdClass or other objects
            return $row->{$property} ?? null;
        }
        
        return null;
    }

    /**
     * Implementation of abstract method from HasBladeRendering
     */
    protected function getNestedPropertyValue($row, string $path)
    {
        $keys = explode('.', $path);
        $value = $row;
        
        foreach ($keys as $key) {
            if (is_array($value)) {
                $value = $value[$key] ?? null;
            } elseif (is_object($value)) {
                if (method_exists($value, 'getAttribute')) {
                    $value = $value->getAttribute($key);
                } else {
                    $value = $value->{$key} ?? null;
                }
            } else {
                return null;
            }
            
            if ($value === null) {
                break;
            }
        }
        
        return $value;
    }

    /**
     * Render a raw HTML/template string safely using Blade.
     * This is used by views that call `$this->renderRawHtml(...)`.
     */
    public function renderRawHtml($rawTemplate, $row)
    {
        try {
            if (empty($rawTemplate) || !is_string($rawTemplate)) {
                return '';
            }

            // Use Laravel Blade renderer for correct template evaluation
            return 
                \Illuminate\Support\Facades\Blade::render($rawTemplate, compact('row'));
        } catch (\Exception $e) {
            // Log and return a safe fallback (escaped template) so views don't break
            Log::warning('DatatableTrait renderRawHtml error: ' . $e->getMessage(), [
                'template' => $rawTemplate,
                'row_id' => $row->id ?? 'unknown'
            ]);

            return htmlspecialchars($rawTemplate, ENT_QUOTES, 'UTF-8');
        }
    }
}
