<?php

namespace ArtflowStudio\Table\Concerns;

/**
 * Emits the lifecycle events host applications can listen for.
 *
 * Consolidated in 1.6.0 from: HasEventListeners.
 */
trait DispatchesEvents
{
    /**
     * Event listeners registry
     */
    protected $eventListeners = [];

    /**
     * Trigger event before query execution
     */
    protected function triggerBeforeQuery($query): void
    {
        $this->triggerEvent('before_query', [
            'query' => $query,
            'search' => $this->search,
            'filters' => $this->filters ?? [],
            'sort_column' => $this->sortColumn,
            'sort_direction' => $this->sortDirection,
        ]);
    }

    /**
     * Trigger an event
     */
    protected function triggerEvent(string $event, array $data = []): array
    {
        $results = [];

        if (! isset($this->eventListeners[$event])) {
            return $results;
        }

        foreach ($this->eventListeners[$event] as $listener) {
            try {
                $result = call_user_func($listener['callback'], $this, $data);
                $results[] = $result;
            } catch (\Exception $e) {
                // Log error but continue with other listeners
                if (class_exists('Log')) {
                    \Log::warning('Event listener failed: '.$e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * Trigger event when filter is applied
     */
    protected function triggerFilterEvent($columnKey, $filterValue, $oldValue = null): void
    {
        $this->triggerEvent('filter_applied', [
            'column_key' => $columnKey,
            'filter_value' => $filterValue,
            'old_value' => $oldValue,
            'filter_timestamp' => time(),
        ]);
    }

    /**
     * Trigger event when pagination changes
     */
    protected function triggerPaginationEvent($page, $perPage, $oldPage = null, $oldPerPage = null): void
    {
        $this->triggerEvent('pagination_changed', [
            'page' => $page,
            'per_page' => $perPage,
            'old_page' => $oldPage,
            'old_per_page' => $oldPerPage,
            'pagination_timestamp' => time(),
        ]);
    }

    /**
     * Trigger event when search is performed
     */
    protected function triggerSearchEvent($searchTerm, $oldSearchTerm = null): void
    {
        $this->triggerEvent('search_performed', [
            'search_term' => $searchTerm,
            'old_search_term' => $oldSearchTerm,
            'search_timestamp' => time(),
        ]);
    }

    /**
     * Trigger event when sorting changes
     */
    protected function triggerSortEvent($columnKey, $direction, $oldColumn = null, $oldDirection = null): void
    {
        $this->triggerEvent('sort_changed', [
            'column_key' => $columnKey,
            'direction' => $direction,
            'old_column' => $oldColumn,
            'old_direction' => $oldDirection,
            'sort_timestamp' => time(),
        ]);
    }
}
