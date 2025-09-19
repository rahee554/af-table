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

// Advanced Features Traits - Performance & API
use ArtflowStudio\Table\Traits\Advanced\HasApiEndpoint;
use ArtflowStudio\Table\Traits\Advanced\HasPerformanceMonitoring;
use ArtflowStudio\Table\Traits\Advanced\HasQueryOptimization;
use ArtflowStudio\Table\Traits\Advanced\HasQueryStringSupport;
use ArtflowStudio\Table\Traits\Advanced\HasSessionManagement;
use ArtflowStudio\Table\Traits\Advanced\HasUnifiedOptimization;
use ArtflowStudio\Table\Traits\Advanced\HasUtilities;
use ArtflowStudio\Table\Traits\Advanced\HasExportFeatures;
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
    use WithPagination;

    // *----------- Properties -----------*//
    public $model;
    public $columns = [];
    public $visibleColumns = [];
    public $checkbox = false;
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
    public $index = false;
    public $tableId = null;
    public $query = null;
    public $colvisBtn = true;
    public $perPage = 10;
    public array $routeFallbacks = [];
    public $actions = [];

    // Performance optimization properties
    protected $cachedRelations = null;
    protected $cachedSelectColumns = null;
    protected $distinctValuesCacheTime = 300;
    protected $maxDistinctValues = 1000;

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

    // *----------- CORE LIVEWIRE METHODS -----------*//

    /**
     * Component Initialization - Core Livewire method
     */
    public function mount($model, $columns, $filters = [], $actions = [], $index = false, $tableId = null, $query = null)
    {
        $this->model = $model;
        $this->tableId = $tableId ?? (is_string($model) ? $model : (is_object($model) ? get_class($model) : uniqid('datatable_')));
        $this->query = $query;

        // Delegate column initialization to HasColumnManagement trait
        $this->columns = $this->initializeColumns($columns);
        $this->initializeColumnConfiguration($this->columns);

        // Delegate column visibility to HasColumnVisibility trait  
        $this->initializeColumnVisibility();

        $this->filters = $filters;
        $this->actions = $actions;
        $this->index = $index;

        // Delegate sort optimization to HasSorting trait
        if (empty($this->sortColumn)) {
            $this->sortColumn = $this->getOptimalSortColumn();
        }
    }

    /**
     * Core query building - Unique to main class
     */
    protected function buildUnifiedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Start with base model query
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            $this->applyCustomQueryConstraints($query);
        }

        // Delegate optimization to traits
        $query = $this->applyColumnOptimization($query);
        $query = $this->applyOptimizedEagerLoading($query);

        // Delegate search to HasSearch trait
        if ($this->searchable && $this->search && strlen(trim($this->search)) >= 3) {
            $this->applyOptimizedSearch($query);
        }

        // Delegate filtering to HasAdvancedFiltering trait
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            $this->applyFilters($query);
        }

        // Delegate additional filters
        if (!empty($this->filters)) {
            $this->applyAdvancedFilters($query, $this->filters);
        }

        // Delegate date range filtering
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $this->applyDateRangeFilter($query, $this->dateColumn, $this->startDate, $this->endDate);
        }

        // Delegate sorting to HasSorting trait
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        return $query;
    }

    /**
     * Get the per-page value for pagination
     */
    public function getPerPageValue(): int
    {
        return $this->perPage ?? 10;
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
     */
    public function render()
    {
        $data = $this->getData();

        return view('artflow-table::livewire.datatable-trait', [
            'data' => $data,
            'index' => $this->index,
        ]);
    }

    // *----------- LIVEWIRE LIFECYCLE METHODS -----------*//

    /**
     * Handle search updates - Core Livewire lifecycle
     */
    public function updatedSearch($value)
    {
        $oldValue = $this->search;
        $this->search = $value;
        $this->resetPage();

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
     * Handle sorting - Core Livewire lifecycle
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
     * Toggle column visibility - Delegates to HasColumnVisibility
     */
    public function toggleColumn($columnKey)
    {
        $this->toggleColumnVisibility($columnKey);
    }

    // *----------- USER ACTION DELEGATES -----------*//

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
        return $this->export($format, $filename);
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
