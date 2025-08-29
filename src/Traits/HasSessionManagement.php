<?php

namespace ArtflowStudio\Table\Traits;

trait HasSessionManagement
{
    /**
     * Session key prefix for this datatable
     */
    protected function getSessionKey($key = null): string
    {
        $baseKey = 'datatable_' . $this->tableId;
        return $key ? $baseKey . '_' . $key : $baseKey;
    }

    /**
     * Save current state to session
     */
    public function saveStateToSession()
    {
        $state = [
            'search' => $this->search,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'visibleColumns' => $this->visibleColumns,
            'filters' => $this->filters ?? [],
            'page' => $this->page ?? 1,
            'saved_at' => now()->toISOString()
        ];

        session()->put($this->getSessionKey('state'), $state);
    }

    /**
     * Load state from session
     */
    public function loadStateFromSession()
    {
        $state = session()->get($this->getSessionKey('state'));

        if (!is_array($state)) {
            return false;
        }

        // Restore state
        $this->search = $state['search'] ?? '';
        $this->sortBy = $state['sortBy'] ?? '';
        $this->sortDirection = $state['sortDirection'] ?? 'asc';
        $this->perPage = $state['perPage'] ?? $this->perPage;
        $this->visibleColumns = $state['visibleColumns'] ?? $this->visibleColumns;
        $this->filters = $state['filters'] ?? [];
        
        if (isset($state['page'])) {
            $this->page = $state['page'];
        }

        return true;
    }

    /**
     * Clear state from session
     */
    public function clearSessionState()
    {
        session()->forget($this->getSessionKey('state'));
    }

    /**
     * Save column preferences
     */
    public function saveColumnPreferences()
    {
        $preferences = [
            'visibleColumns' => $this->visibleColumns,
            'columnOrder' => $this->getColumnOrder(),
            'saved_at' => now()->toISOString()
        ];

        session()->put($this->getSessionKey('column_preferences'), $preferences);
    }

    /**
     * Load column preferences
     */
    public function loadColumnPreferences()
    {
        $preferences = session()->get($this->getSessionKey('column_preferences'));

        if (!is_array($preferences)) {
            return false;
        }

        if (isset($preferences['visibleColumns'])) {
            $this->visibleColumns = $preferences['visibleColumns'];
        }

        if (isset($preferences['columnOrder'])) {
            $this->applyColumnOrder($preferences['columnOrder']);
        }

        return true;
    }

    /**
     * Get current column order
     */
    protected function getColumnOrder(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Apply column order
     */
    protected function applyColumnOrder(array $order)
    {
        $reorderedColumns = [];
        
        // First, add columns in the specified order
        foreach ($order as $columnKey) {
            if (isset($this->columns[$columnKey])) {
                $reorderedColumns[$columnKey] = $this->columns[$columnKey];
            }
        }
        
        // Then add any remaining columns that weren't in the order
        foreach ($this->columns as $columnKey => $column) {
            if (!isset($reorderedColumns[$columnKey])) {
                $reorderedColumns[$columnKey] = $column;
            }
        }
        
        $this->columns = $reorderedColumns;
    }

    /**
     * Save filter preferences
     */
    public function saveFilterPreferences()
    {
        $filterPreferences = [
            'filters' => $this->filters ?? [],
            'defaultFilters' => $this->getDefaultFilters(),
            'saved_at' => now()->toISOString()
        ];

        session()->put($this->getSessionKey('filter_preferences'), $filterPreferences);
    }

    /**
     * Load filter preferences
     */
    public function loadFilterPreferences()
    {
        $preferences = session()->get($this->getSessionKey('filter_preferences'));

        if (!is_array($preferences)) {
            return false;
        }

        if (isset($preferences['filters'])) {
            $this->filters = $preferences['filters'];
        }

        return true;
    }

    /**
     * Get default filters
     */
    protected function getDefaultFilters(): array
    {
        $defaultFilters = [];
        
        foreach ($this->filters as $columnKey => $filterConfig) {
            if (isset($filterConfig['default'])) {
                $defaultFilters[$columnKey] = $filterConfig['default'];
            }
        }
        
        return $defaultFilters;
    }

    /**
     * Save search history
     */
    public function saveSearchHistory($searchTerm)
    {
        if (empty($searchTerm)) {
            return;
        }

        $history = session()->get($this->getSessionKey('search_history'), []);
        
        // Remove if already exists
        $history = array_filter($history, function($item) use ($searchTerm) {
            return $item['term'] !== $searchTerm;
        });
        
        // Add to beginning
        array_unshift($history, [
            'term' => $searchTerm,
            'searched_at' => now()->toISOString()
        ]);
        
        // Keep only last 10 searches
        $history = array_slice($history, 0, 10);
        
        session()->put($this->getSessionKey('search_history'), $history);
    }

    /**
     * Get search history
     */
    public function getSearchHistory(): array
    {
        return session()->get($this->getSessionKey('search_history'), []);
    }

    /**
     * Clear search history
     */
    public function clearSearchHistory()
    {
        session()->forget($this->getSessionKey('search_history'));
    }

    /**
     * Save export preferences
     */
    public function saveExportPreferences($format, $options = [])
    {
        $preferences = [
            'format' => $format,
            'options' => $options,
            'exported_at' => now()->toISOString()
        ];

        session()->put($this->getSessionKey('export_preferences'), $preferences);
    }

    /**
     * Get export preferences
     */
    public function getExportPreferences(): array
    {
        return session()->get($this->getSessionKey('export_preferences'), [
            'format' => 'csv',
            'options' => []
        ]);
    }

    /**
     * Get all session data for this datatable
     */
    public function getAllSessionData(): array
    {
        return [
            'state' => session()->get($this->getSessionKey('state')),
            'column_preferences' => session()->get($this->getSessionKey('column_preferences')),
            'filter_preferences' => session()->get($this->getSessionKey('filter_preferences')),
            'search_history' => session()->get($this->getSessionKey('search_history')),
            'export_preferences' => session()->get($this->getSessionKey('export_preferences'))
        ];
    }

    /**
     * Clear all session data for this datatable
     */
    public function clearAllSessionData()
    {
        $keys = [
            'state',
            'column_preferences', 
            'filter_preferences',
            'search_history',
            'export_preferences'
        ];

        foreach ($keys as $key) {
            session()->forget($this->getSessionKey($key));
        }
    }

    /**
     * Check if session state exists
     */
    public function hasSessionState(): bool
    {
        return session()->has($this->getSessionKey('state'));
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): array
    {
        $sessionData = $this->getAllSessionData();
        $stats = [
            'has_state' => !is_null($sessionData['state']),
            'has_column_preferences' => !is_null($sessionData['column_preferences']),
            'has_filter_preferences' => !is_null($sessionData['filter_preferences']),
            'search_history_count' => count($sessionData['search_history'] ?? []),
            'has_export_preferences' => !is_null($sessionData['export_preferences']),
            'session_size_estimate' => strlen(serialize($sessionData))
        ];

        // Add timestamps if available
        foreach ($sessionData as $key => $data) {
            if (is_array($data) && isset($data['saved_at'])) {
                $stats[$key . '_saved_at'] = $data['saved_at'];
            }
        }

        return $stats;
    }

    /**
     * Auto-save state when component is updated
     */
    public function autoSaveState()
    {
        if ($this->enableSessionPersistence ?? true) {
            $this->saveStateToSession();
        }
    }

    /**
     * Initialize from session on mount
     */
    public function initializeFromSession()
    {
        if ($this->enableSessionPersistence ?? true) {
            $this->loadStateFromSession();
            $this->loadColumnPreferences();
            $this->loadFilterPreferences();
        }
    }

    /**
     * Create session snapshot for debugging
     */
    public function createSessionSnapshot(): array
    {
        return [
            'snapshot_created_at' => now()->toISOString(),
            'table_id' => $this->tableId,
            'session_data' => $this->getAllSessionData(),
            'current_state' => [
                'search' => $this->search,
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
                'perPage' => $this->perPage,
                'visibleColumns' => $this->visibleColumns,
                'filters' => $this->filters ?? [],
                'page' => $this->page ?? 1
            ],
            'session_stats' => $this->getSessionStats()
        ];
    }
}
