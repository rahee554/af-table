<?php

namespace ArtflowStudio\Table\Concerns;

/**
 * Remembers search, sort, filters, page size and column preferences
 * per user, per table.
 *
 * Consolidated in 1.6.0 from: HasSessionManagement.
 */
trait PersistsState
{
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
     * Session key prefix for this datatable
     */
    protected function getSessionKey($key = null): string
    {
        // Include user ID for session isolation - prevents data leakage between users
        $userId = $this->getUserIdentifierForSession();
        $baseKey = 'datatable_'.$this->tableId.'_'.$userId;

        return $key ? $baseKey.'_'.$key : $baseKey;
    }

    /**
     * Save filter preferences
     */
    public function saveFilterPreferences()
    {
        $filterPreferences = [
            'filters' => $this->filters ?? [],
            'defaultFilters' => $this->getDefaultFilters(),
            'saved_at' => now()->toISOString(),
        ];

        session()->put($this->getSessionKey('filter_preferences'), $filterPreferences);
    }

    /**
     * Persist the active filter selection.
     *
     * Called from AftableComponent::updatedFilters(), which fires on any
     * `wire:model="filters.*"` change. Previously undefined. See AUDIT.md BUG-09.
     */
    public function saveFiltersToSession(): void
    {
        session()->put($this->getSessionKey('active_filters'), [
            'column' => $this->filterColumn,
            'operator' => $this->filterOperator,
            'value' => $this->filterValue,
            'instances' => $this->filterInstances ?? [],
            'dateColumn' => $this->dateColumn,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    /**
     * Persist the page size.
     *
     * Called from AftableComponent::updatedPerPage(). Previously undefined.
     * See AUDIT.md BUG-09.
     */
    public function savePerPageToSession($perPage): void
    {
        $perPage = (int) $perPage;

        if ($perPage < 1) {
            return;
        }

        session()->put($this->getSessionKey('per_page'), $perPage);
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
        $history = array_filter($history, function ($item) use ($searchTerm) {
            return $item['term'] !== $searchTerm;
        });

        // Add to beginning
        array_unshift($history, [
            'term' => $searchTerm,
            'searched_at' => now()->toISOString(),
        ]);

        // Keep only last 10 searches
        $history = array_slice($history, 0, 10);

        session()->put($this->getSessionKey('search_history'), $history);
    }

    /**
     * Save search to session
     */
    public function saveSearchToSession($searchTerm)
    {
        if (empty($searchTerm)) {
            return;
        }

        // Save current search value to session
        session()->put($this->getSessionKey('current_search'), $searchTerm);

        // Also save to search history
        $this->saveSearchHistory($searchTerm);
    }

    /**
     * Persist the active sort column and direction.
     *
     * Called from AftableComponent::sortBy(). Previously undefined, which made
     * that action throw BadMethodCallException. See AUDIT.md BUG-09.
     */
    public function saveSortingToSession(): void
    {
        session()->put($this->getSessionKey('sorting'), [
            'column' => $this->sortColumn,
            'direction' => $this->sortDirection,
        ]);
    }

    /**
     * Save current state to session
     */
    public function saveStateToSession()
    {
        $state = [
            'search' => $this->search,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'visibleColumns' => $this->visibleColumns,
            'filters' => $this->filters ?? [],
            'page' => $this->page ?? 1,
            'saved_at' => now()->toISOString(),
        ];

        session()->put($this->getSessionKey('state'), $state);
    }
}
