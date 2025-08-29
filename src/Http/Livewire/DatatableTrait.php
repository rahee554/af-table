<?php

namespace ArtflowStudio\Table\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use ArtflowStudio\Table\Traits\HasQueryBuilder;
use ArtflowStudio\Table\Traits\HasDataValidation;
use ArtflowStudio\Table\Traits\HasColumnConfiguration;
use ArtflowStudio\Table\Traits\HasColumnVisibility;
use ArtflowStudio\Table\Traits\HasSearch;
use ArtflowStudio\Table\Traits\HasFiltering;
use ArtflowStudio\Table\Traits\HasSorting;
use ArtflowStudio\Table\Traits\HasCaching;
use ArtflowStudio\Table\Traits\HasEagerLoading;
use ArtflowStudio\Table\Traits\HasMemoryManagement;
use ArtflowStudio\Table\Traits\HasJsonSupport;
use ArtflowStudio\Table\Traits\HasRelationships;
use ArtflowStudio\Table\Traits\HasExport;
use ArtflowStudio\Table\Traits\HasRawTemplates;
use ArtflowStudio\Table\Traits\HasSessionManagement;
use ArtflowStudio\Table\Traits\HasQueryStringSupport;
use ArtflowStudio\Table\Traits\HasEventListeners;
use ArtflowStudio\Table\Traits\HasActions;

class DatatableTrait extends Component
{
    use WithPagination;
    use HasQueryBuilder;
    use HasDataValidation;
    use HasColumnConfiguration;
    use HasColumnVisibility;
    use HasSearch;
    use HasFiltering;
    use HasSorting;
    use HasCaching;
    use HasEagerLoading;
    use HasMemoryManagement;
    use HasJsonSupport;
    use HasRelationships;
    use HasExport;
    use HasRawTemplates;
    use HasSessionManagement;
    use HasQueryStringSupport;
    use HasEventListeners;
    use HasActions;

    /**
     * Livewire component properties
     */
    public $tableId = 'datatable';
    public $model;
    public $columns = [];
    public $visibleColumns = [];
    public $search = '';
    public $sortBy = '';
    public $sortDirection = 'asc';
    public $perPage = 10;
    public $filters = [];
    public $selectedRecords = [];
    public $enableSessionPersistence = true;
    public $enableQueryStringSupport = true;
    public $distinctValuesCacheTime = 3600; // 1 hour
    public $maxDistinctValues = 100;

    /**
     * Livewire listeners
     */
    protected $listeners = [
        'refreshDatatable' => 'refresh',
        'clearFilters' => 'clearAllFilters',
        'exportData' => 'handleExport',
        'bulkAction' => 'handleBulkAction'
    ];

    /**
     * Component initialization
     */
    public function mount($model = null, $columns = [], $config = [])
    {
        // Set model
        if ($model) {
            $this->model = $model;
        }

        // Set columns
        if (!empty($columns)) {
            $this->columns = $columns;
            $this->initializeColumnVisibility();
        }

        // Apply configuration
        $this->applyConfiguration($config);

        // Initialize from session if enabled
        if ($this->enableSessionPersistence) {
            $this->initializeFromSession();
        }

        // Initialize from query string if enabled
        if ($this->enableQueryStringSupport) {
            $this->loadFromQueryString();
        }

        // Set up event listeners
        $this->setupDefaultEventListeners();

        // Set up default actions if none defined
        if (empty($this->actions) && empty($this->bulkActions)) {
            $this->setupDefaultActions();
        }

        // Validate configuration
        $this->validateConfiguration();
    }

    /**
     * Apply configuration array
     */
    protected function applyConfiguration(array $config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
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

        // Apply search
        if (!empty($this->search)) {
            $query = $this->applySearch($query, $this->search);
        }

        // Apply filters
        if (!empty($this->filters)) {
            $query = $this->applyFilters($query, $this->filters);
        }

        // Apply sorting
        if (!empty($this->sortBy)) {
            $query = $this->applySorting($query, $this->sortBy, $this->sortDirection);
        }

        // Apply eager loading
        $query = $this->applyLoadingStrategy($query);

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
        $this->triggerBeforeRender();

        $data = $this->getData();
        $viewData = [
            'data' => $data,
            'columns' => $this->getVisibleColumns(),
            'actions' => $this->actions,
            'bulkActions' => $this->getAvailableBulkActions(),
            'selectedCount' => $this->getSelectedCount(),
            'stats' => $this->getComponentStats()
        ];

        $output = view('artflow-table::datatable', $viewData);

        $this->triggerAfterRender($output);

        return $output;
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
        $oldColumn = $this->sortBy;
        $oldDirection = $this->sortDirection;

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
        
        $this->triggerSortEvent($this->sortBy, $this->sortDirection, $oldColumn, $oldDirection);
        
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
        $this->visibleColumns[$columnKey] = !$oldVisibility;
        
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
            session()->flash('error', 'Export failed: ' . $e->getMessage());
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
            session()->flash('error', 'Bulk action failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Select/deselect record
     */
    public function toggleRecordSelection($recordId)
    {
        if ($this->isRecordSelected($recordId)) {
            $this->selectedRecords = array_filter($this->selectedRecords, function($id) use ($recordId) {
                return $id != $recordId;
            });
        } else {
            $this->selectedRecords[] = $recordId;
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
            'event_stats' => $this->getEventListenerStats()
        ];
    }

    /**
     * Get query string properties
     */
    public function getQueryString()
    {
        if (!$this->enableQueryStringSupport) {
            return [];
        }

        return [
            'search' => ['except' => ''],
            'sortBy' => ['except' => ''],
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
        if (!$this->model) {
            $errors[] = 'Model is required';
        } elseif (!class_exists($this->model)) {
            $errors[] = 'Model class does not exist: ' . $this->model;
        }

        // Validate columns
        if (empty($this->columns)) {
            $errors[] = 'Columns configuration is required';
        } else {
            $columnValidation = $this->validateColumns();
            if (!empty($columnValidation['errors'])) {
                $errors = array_merge($errors, $columnValidation['errors']);
            }
        }

        // Validate relations
        $relationValidation = $this->validateRelationColumns();
        if (!empty($relationValidation['invalid'])) {
            foreach ($relationValidation['invalid'] as $column => $error) {
                $errors[] = "Column {$column}: {$error}";
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datatable configuration errors: ' . implode(', ', $errors));
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
                'has_search' => !empty($this->search),
                'has_filters' => !empty($this->filters),
                'has_sorting' => !empty($this->sortBy),
                'selected_records_count' => count($this->selectedRecords),
                'per_page' => $this->perPage,
                'current_page' => $this->page ?? 1
            ],
            'configuration' => [
                'session_persistence' => $this->enableSessionPersistence,
                'query_string_support' => $this->enableQueryStringSupport,
                'cache_time' => $this->distinctValuesCacheTime,
                'max_distinct_values' => $this->maxDistinctValues
            ],
            'statistics' => $this->getComponentStats(),
            'validation' => [
                'columns' => $this->validateColumns(),
                'relations' => $this->validateRelationColumns()
            ]
        ];
    }
}
