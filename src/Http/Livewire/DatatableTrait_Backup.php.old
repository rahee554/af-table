<?php

namespace ArtflowStudio\Table\Http\Livewire;

// Core Data Management Traits
use ArtflowStudio\Table\Traits\Core\HasBasicFeatures;
use ArtflowStudio\Table\Traits\Core\HasDataValidation;
use ArtflowStudio\Table\Traits\Core\HasColumnConfiguration;
use ArtflowStudio\Table\Traits\Core\HasSearch;
use ArtflowStudio\Table\Traits\Core\HasAdvancedFiltering;
use ArtflowStudio\Table\Traits\Core\HasSorting;
use ArtflowStudio\Table\Traits\Core\HasUnifiedCaching;
use ArtflowStudio\Table\Traits\Core\HasRelationships;
use ArtflowStudio\Table\Traits\Core\HasJsonSupport;
use ArtflowStudio\Table\Traits\Core\HasJsonFile;
use ArtflowStudio\Table\Traits\Core\HasValidation;
use ArtflowStudio\Table\Traits\Core\HasBladeRendering;
use ArtflowStudio\Table\Traits\Core\HasColumnManagement;
use ArtflowStudio\Table\Traits\Core\HasQueryBuilding;

// UI & Interaction Traits
use ArtflowStudio\Table\Traits\UI\HasActions;
use ArtflowStudio\Table\Traits\UI\HasColumnVisibility;
use ArtflowStudio\Table\Traits\UI\HasBulkActions;
use ArtflowStudio\Table\Traits\UI\HasEventListeners;
use ArtflowStudio\Table\Traits\UI\HasRawTemplates;
use ArtflowStudio\Table\Traits\UI\HasUserActions;

// Advanced Features Traits
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
    use HasActions {
        HasActions::clearSelection as clearActionSelection;
        HasActions::getSelectedCount as getActionSelectedCount;
    }
    use HasAdvancedFiltering;
    use HasApiEndpoint;
    use HasBasicFeatures;
    use HasBladeRendering;
    use HasBulkActions {
        HasBulkActions::clearSelection as clearBulkSelection;
        HasBulkActions::getSelectedCount as getBulkSelectedCount;
    }
    use HasColumnConfiguration;
    use HasColumnManagement;
    use HasColumnVisibility;
    use HasDataValidation;
    use HasEventListeners;
    use HasExportFeatures;
    use HasJsonFile;
    use HasJsonSupport;
    use HasPerformanceMonitoring;
    use HasQueryBuilding;
    use HasQueryOptimization;
    use HasQueryStringSupport;
    use HasRawTemplates;
    use HasRelationships;
    use HasSearch;
    use HasSessionManagement;
    use HasSorting;
    use HasUnifiedCaching;
    use HasUnifiedOptimization;
    use HasUserActions;
    use HasUtilities;
    use HasValidation;
    use HasUtilities {
        HasUtilities::sanitizeUtilitySearch as sanitizeSearchFromUtilities;
        HasUtilities::generateBasicCacheKey as generateUtilityCacheKey;
    }
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
}
