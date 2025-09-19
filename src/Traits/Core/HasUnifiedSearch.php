<?php

namespace ArtflowStudio\Table\Traits\Core;

/**
 * HasUnifiedSearch - Consolidates search functionality from multiple traits
 * 
 * This trait consolidates search methods from:
 * - HasSearch (basic search operations)
 * - HasQueryBuilding (query-level search operations)  
 * - HasAdvancedFiltering (advanced filter operations)
 * 
 * Purpose: Fix search/filtering issues caused by method conflicts across 21 traits
 */
trait HasUnifiedSearch
{
    // *----------- Search Properties -----------*//
    
    protected $searchHistory = [];
    protected $searchCache = [];
    protected $lastSearchQuery = null;
    
    // *----------- Core Search Methods -----------*//
    
    /**
     * Sanitize search input for safe database querying
     * Consolidated from HasSearch and HasQueryBuilding
     */
    public function sanitizeSearch($search): string
    {
        if (!is_string($search)) {
            return '';
        }
        
        // Remove potentially dangerous characters and trim
        $sanitized = trim(strip_tags($search));
        
        // Remove SQL injection patterns
        $sanitized = preg_replace('/[^\w\s\-_@.]/i', '', $sanitized);
        
        // Limit length to prevent abuse
        return substr($sanitized, 0, 255);
    }
    
    /**
     * Apply optimized search to query
     * Consolidated from HasSearch and HasQueryBuilding
     */
    public function applyOptimizedSearch($query)
    {
        if (empty($this->search) || strlen(trim($this->search)) < 3) {
            return $query;
        }
        
        $searchTerm = $this->sanitizeSearch($this->search);
        if (empty($searchTerm)) {
            return $query;
        }
        
        // Store for debugging
        $this->lastSearchQuery = $searchTerm;
        
        return $query->where(function ($subQuery) use ($searchTerm) {
            $this->applyColumnSearch($subQuery, $searchTerm);
            $this->applyRelationSearch($subQuery, $searchTerm);
            $this->applyJsonSearch($subQuery, $searchTerm);
        });
    }
    
    /**
     * Apply search to regular columns
     * From HasQueryBuilding
     */
    public function applyColumnSearch($query, $searchTerm)
    {
        $searchableColumns = $this->getSearchableColumns();
        
        foreach ($searchableColumns as $column) {
            if ($this->isColumnSearchable($column)) {
                $query->orWhere($column, 'LIKE', "%{$searchTerm}%");
            }
        }
        
        return $query;
    }
    
    /**
     * Apply search to relationship columns
     * From HasQueryBuilding and HasSearch
     */
    public function applyRelationSearch($query, $searchTerm)
    {
        $relationColumns = $this->getSearchableRelations();
        
        foreach ($relationColumns as $relation => $columns) {
            if (is_array($columns)) {
                $query->orWhereHas($relation, function ($relationQuery) use ($columns, $searchTerm) {
                    $relationQuery->where(function ($subQuery) use ($columns, $searchTerm) {
                        foreach ($columns as $column) {
                            $subQuery->orWhere($column, 'LIKE', "%{$searchTerm}%");
                        }
                    });
                });
            }
        }
        
        return $query;
    }
    
    /**
     * Apply search to JSON columns
     * From HasQueryBuilding and HasSearch
     */
    public function applyJsonSearch($query, $searchTerm)
    {
        $jsonColumns = $this->getSearchableJsonColumns();
        
        foreach ($jsonColumns as $column => $paths) {
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $query->orWhere("{$column}->{$path}", 'LIKE', "%{$searchTerm}%");
                }
            } else {
                // Search in entire JSON column
                $query->orWhere($column, 'LIKE', "%{$searchTerm}%");
            }
        }
        
        return $query;
    }
    
    /**
     * Clear search and reset related state
     * Consolidated from HasSearch and HasUserActions
     */
    public function clearSearch()
    {
        $oldSearch = $this->search;
        $this->search = '';
        $this->resetPage();
        
        // Clear search cache
        $this->searchCache = [];
        $this->lastSearchQuery = null;
        
        // Trigger search cleared event
        $this->triggerSearchEvent('', $oldSearch);
        
        // Auto-save state if enabled
        if ($this->enableSessionPersistence ?? true) {
            $this->saveSearchToSession('');
        }
    }
    
    /**
     * Set search value programmatically
     * From HasSearch
     */
    public function setSearch($searchTerm)
    {
        $oldSearch = $this->search;
        $this->search = $this->sanitizeSearch($searchTerm);
        $this->resetPage();
        
        // Trigger search event
        $this->triggerSearchEvent($this->search, $oldSearch);
        
        // Auto-save if enabled
        if ($this->enableSessionPersistence ?? true) {
            $this->saveSearchToSession($this->search);
        }
    }
    
    // *----------- Advanced Filtering Methods -----------*//
    
    /**
     * Initialize advanced filtering
     * From HasAdvancedFiltering
     */
    public function initializeAdvancedFiltering()
    {
        if (!isset($this->advancedFilters)) {
            $this->advancedFilters = [];
        }
        
        if (!isset($this->activeFilterCount)) {
            $this->activeFilterCount = 0;
        }
    }
    
    /**
     * Apply advanced filters to query
     * Consolidated from HasAdvancedFiltering and HasQueryBuilding
     */
    public function applyAdvancedFilters($query, $filters = null)
    {
        $filtersToApply = $filters ?? $this->advancedFilters ?? [];
        
        if (empty($filtersToApply)) {
            return $query;
        }
        
        foreach ($filtersToApply as $column => $filterConfig) {
            if (is_array($filterConfig) && isset($filterConfig['value']) && $filterConfig['value'] !== '') {
                $this->applyAdvancedFilterToQuery($query, $column, $filterConfig);
            }
        }
        
        return $query;
    }
    
    /**
     * Apply single advanced filter to query
     * From HasAdvancedFiltering
     */
    protected function applyAdvancedFilterToQuery($query, $column, $filterConfig)
    {
        $operator = $filterConfig['operator'] ?? '=';
        $value = $filterConfig['value'];
        
        switch ($operator) {
            case 'like':
            case 'contains':
                $query->where($column, 'LIKE', "%{$value}%");
                break;
            case 'starts_with':
                $query->where($column, 'LIKE', "{$value}%");
                break;
            case 'ends_with':
                $query->where($column, 'LIKE', "%{$value}");
                break;
            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($column, $values);
                break;
            case 'not_in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereNotIn($column, $values);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($column, $value);
                }
                break;
            case 'null':
                $query->whereNull($column);
                break;
            case 'not_null':
                $query->whereNotNull($column);
                break;
            default:
                $query->where($column, $operator, $value);
                break;
        }
    }
    
    /**
     * Clear all advanced filters
     * From HasAdvancedFiltering
     */
    public function clearAllAdvancedFilters()
    {
        $this->advancedFilters = [];
        $this->activeFilterCount = 0;
        $this->resetPage();
        
        // Auto-save state
        if ($this->enableSessionPersistence ?? true) {
            $this->saveFilterPreferences();
        }
    }
    
    /**
     * Add advanced filter
     * From HasAdvancedFiltering
     */
    public function addAdvancedFilter($column, $operator, $value)
    {
        if (!isset($this->advancedFilters)) {
            $this->advancedFilters = [];
        }
        
        $this->advancedFilters[$column] = [
            'operator' => $operator,
            'value' => $value,
            'created_at' => now()->toISOString()
        ];
        
        $this->updateActiveFilterCount();
        $this->resetPage();
        
        // Auto-save state
        if ($this->enableSessionPersistence ?? true) {
            $this->saveFilterPreferences();
        }
    }
    
    /**
     * Clear specific advanced filter
     * From HasAdvancedFiltering
     */
    public function clearAdvancedFilter($column)
    {
        if (isset($this->advancedFilters[$column])) {
            unset($this->advancedFilters[$column]);
            $this->updateActiveFilterCount();
            $this->resetPage();
            
            // Auto-save state
            if ($this->enableSessionPersistence ?? true) {
                $this->saveFilterPreferences();
            }
        }
    }
    
    // *----------- Helper Methods -----------*//
    
    /**
     * Get searchable columns from configuration
     */
    protected function getSearchableColumns(): array
    {
        $searchableColumns = [];
        
        foreach ($this->columns as $columnKey => $config) {
            // Use the isColumnSearchable method from the main trait that has better logic
            if ($this->isColumnSearchable($columnKey)) {
                // For database columns, use the 'key' if available, otherwise use column name
                if (is_array($config) && isset($config['key'])) {
                    $searchableColumns[] = $config['key'];
                } else {
                    $searchableColumns[] = $columnKey;
                }
            }
        }
        
        return $searchableColumns;
    }
    
    /**
     * Get searchable relationships
     */
    protected function getSearchableRelations(): array
    {
        $relations = [];
        
        foreach ($this->columns as $column => $config) {
            if (is_array($config) && isset($config['relation']) && ($config['searchable'] ?? false)) {
                $relationName = $config['relation'];
                $relationColumn = $config['relation_column'] ?? 'name';
                
                if (!isset($relations[$relationName])) {
                    $relations[$relationName] = [];
                }
                
                $relations[$relationName][] = $relationColumn;
            }
        }
        
        return $relations;
    }
    
    /**
     * Get searchable JSON columns
     */
    protected function getSearchableJsonColumns(): array
    {
        $jsonColumns = [];
        
        foreach ($this->columns as $column => $config) {
            if (is_array($config) && ($config['type'] ?? '') === 'json' && ($config['searchable'] ?? false)) {
                $jsonColumns[$column] = $config['search_paths'] ?? ['*'];
            }
        }
        
        return $jsonColumns;
    }
    
    /**
     * Update active filter count
     */
    protected function updateActiveFilterCount()
    {
        $this->activeFilterCount = count(array_filter($this->advancedFilters ?? [], function ($filter) {
            return !empty($filter['value']);
        }));
    }
    
    /**
     * Get active filters count
     * From HasAdvancedFiltering
     */
    public function getActiveFiltersCount(): int
    {
        return $this->activeFilterCount ?? 0;
    }
    
    /**
     * Get search statistics
     * From HasSearch
     */
    public function getSearchStats(): array
    {
        return [
            'current_search' => $this->search,
            'last_query' => $this->lastSearchQuery,
            'search_history_count' => count($this->searchHistory),
            'cache_hits' => count($this->searchCache),
            'active_filters' => $this->getActiveFiltersCount(),
            'searchable_columns' => count($this->getSearchableColumns()),
            'searchable_relations' => count($this->getSearchableRelations()),
            'searchable_json_columns' => count($this->getSearchableJsonColumns())
        ];
    }
    
    /**
     * Get filters summary
     * From HasAdvancedFiltering
     */
    public function getFiltersSummary(): array
    {
        $summary = [
            'total_filters' => count($this->advancedFilters ?? []),
            'active_filters' => $this->getActiveFiltersCount(),
            'filter_types' => []
        ];
        
        foreach ($this->advancedFilters ?? [] as $column => $config) {
            $operator = $config['operator'] ?? '=';
            if (!isset($summary['filter_types'][$operator])) {
                $summary['filter_types'][$operator] = 0;
            }
            $summary['filter_types'][$operator]++;
        }
        
        return $summary;
    }
    
    /**
     * Refresh table data (legacy support)
     * From HasSearch and HasUserActions
     */
    public function refreshTable()
    {
        $this->refreshComponent();
    }
    
    /**
     * Get search suggestions based on history
     * From HasSearch
     */
    public function getSearchSuggestions($limit = 5): array
    {
        $history = $this->getSearchHistory();
        return array_slice(array_column($history, 'term'), 0, $limit);
    }
}
