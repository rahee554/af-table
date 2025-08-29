<?php

namespace ArtflowStudio\Table\Traits;

trait HasEventListeners
{
    /**
     * Event listeners registry
     */
    protected $eventListeners = [];

    /**
     * Register an event listener
     */
    public function addEventListener(string $event, callable $callback, int $priority = 0)
    {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }

        $this->eventListeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority (higher first)
        usort($this->eventListeners[$event], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * Remove an event listener
     */
    public function removeEventListener(string $event, callable $callback = null)
    {
        if (!isset($this->eventListeners[$event])) {
            return;
        }

        if ($callback === null) {
            // Remove all listeners for this event
            unset($this->eventListeners[$event]);
        } else {
            // Remove specific callback
            $this->eventListeners[$event] = array_filter(
                $this->eventListeners[$event],
                function ($listener) use ($callback) {
                    return $listener['callback'] !== $callback;
                }
            );
        }
    }

    /**
     * Trigger an event
     */
    protected function triggerEvent(string $event, array $data = []): array
    {
        $results = [];

        if (!isset($this->eventListeners[$event])) {
            return $results;
        }

        foreach ($this->eventListeners[$event] as $listener) {
            try {
                $result = call_user_func($listener['callback'], $this, $data);
                $results[] = $result;
            } catch (\Exception $e) {
                // Log error but continue with other listeners
                if (class_exists('Log')) {
                    \Log::warning('Event listener failed: ' . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * Trigger event before query execution
     */
    protected function triggerBeforeQuery($query): void
    {
        $this->triggerEvent('before_query', [
            'query' => $query,
            'search' => $this->search,
            'filters' => $this->filters ?? [],
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection
        ]);
    }

    /**
     * Trigger event after query execution
     */
    protected function triggerAfterQuery($query, $results): void
    {
        $this->triggerEvent('after_query', [
            'query' => $query,
            'results' => $results,
            'record_count' => is_countable($results) ? count($results) : 0
        ]);
    }

    /**
     * Trigger event before rendering
     */
    protected function triggerBeforeRender(): void
    {
        $this->triggerEvent('before_render', [
            'visible_columns' => array_keys(array_filter($this->visibleColumns)),
            'current_page' => $this->page ?? 1,
            'per_page' => $this->perPage
        ]);
    }

    /**
     * Trigger event after rendering
     */
    protected function triggerAfterRender($renderedOutput): void
    {
        $this->triggerEvent('after_render', [
            'output' => $renderedOutput
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
            'search_timestamp' => time()
        ]);
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
            'filter_timestamp' => time()
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
            'sort_timestamp' => time()
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
            'pagination_timestamp' => time()
        ]);
    }

    /**
     * Trigger event when column visibility changes
     */
    protected function triggerColumnVisibilityEvent($columnKey, $isVisible, $oldVisibility = null): void
    {
        $this->triggerEvent('column_visibility_changed', [
            'column_key' => $columnKey,
            'is_visible' => $isVisible,
            'old_visibility' => $oldVisibility,
            'visibility_timestamp' => time()
        ]);
    }

    /**
     * Trigger event when export is performed
     */
    protected function triggerExportEvent($format, $filename, $recordCount): void
    {
        $this->triggerEvent('export_performed', [
            'format' => $format,
            'filename' => $filename,
            'record_count' => $recordCount,
            'export_timestamp' => time()
        ]);
    }

    /**
     * Trigger event when error occurs
     */
    protected function triggerErrorEvent($error, $context = []): void
    {
        $this->triggerEvent('error_occurred', [
            'error' => $error,
            'context' => $context,
            'error_timestamp' => time()
        ]);
    }

    /**
     * Set up default event listeners
     */
    protected function setupDefaultEventListeners(): void
    {
        // Search history tracking
        $this->addEventListener('search_performed', function ($datatable, $data) {
            if (method_exists($datatable, 'saveSearchHistory')) {
                $datatable->saveSearchHistory($data['search_term']);
            }
        });

        // State persistence
        $this->addEventListener('filter_applied', function ($datatable, $data) {
            if (method_exists($datatable, 'autoSaveState')) {
                $datatable->autoSaveState();
            }
        });

        $this->addEventListener('sort_changed', function ($datatable, $data) {
            if (method_exists($datatable, 'autoSaveState')) {
                $datatable->autoSaveState();
            }
        });

        $this->addEventListener('pagination_changed', function ($datatable, $data) {
            if (method_exists($datatable, 'autoSaveState')) {
                $datatable->autoSaveState();
            }
        });

        // Column preferences saving
        $this->addEventListener('column_visibility_changed', function ($datatable, $data) {
            if (method_exists($datatable, 'saveColumnPreferences')) {
                $datatable->saveColumnPreferences();
            }
        });
    }

    /**
     * Get all registered event listeners
     */
    public function getEventListeners(): array
    {
        return $this->eventListeners;
    }

    /**
     * Get listeners for a specific event
     */
    public function getEventListenersFor(string $event): array
    {
        return $this->eventListeners[$event] ?? [];
    }

    /**
     * Check if event has listeners
     */
    public function hasEventListeners(string $event): bool
    {
        return isset($this->eventListeners[$event]) && !empty($this->eventListeners[$event]);
    }

    /**
     * Clear all event listeners
     */
    public function clearEventListeners(): void
    {
        $this->eventListeners = [];
    }

    /**
     * Clear listeners for specific event
     */
    public function clearEventListenersFor(string $event): void
    {
        unset($this->eventListeners[$event]);
    }

    /**
     * Get event listener statistics
     */
    public function getEventListenerStats(): array
    {
        $stats = [
            'total_events' => count($this->eventListeners),
            'total_listeners' => 0,
            'events' => []
        ];

        foreach ($this->eventListeners as $event => $listeners) {
            $listenerCount = count($listeners);
            $stats['total_listeners'] += $listenerCount;
            $stats['events'][$event] = [
                'listener_count' => $listenerCount,
                'priorities' => array_column($listeners, 'priority')
            ];
        }

        return $stats;
    }

    /**
     * Create event listener from string (for configuration)
     */
    public function addEventListenerFromString(string $event, string $listenerClass, string $method = 'handle', int $priority = 0): void
    {
        if (!class_exists($listenerClass)) {
            throw new \InvalidArgumentException("Class {$listenerClass} does not exist");
        }

        $instance = new $listenerClass();

        if (!method_exists($instance, $method)) {
            throw new \InvalidArgumentException("Method {$method} does not exist on {$listenerClass}");
        }

        $this->addEventListener($event, [$instance, $method], $priority);
    }

    /**
     * Add multiple event listeners from configuration
     */
    public function addEventListenersFromConfig(array $config): void
    {
        foreach ($config as $event => $listeners) {
            if (!is_array($listeners)) {
                continue;
            }

            foreach ($listeners as $listener) {
                if (is_string($listener)) {
                    // Simple class name, use default method
                    $this->addEventListenerFromString($event, $listener);
                } elseif (is_array($listener)) {
                    // Array with class, method, and priority
                    $class = $listener['class'] ?? $listener[0] ?? null;
                    $method = $listener['method'] ?? $listener[1] ?? 'handle';
                    $priority = $listener['priority'] ?? $listener[2] ?? 0;

                    if ($class) {
                        $this->addEventListenerFromString($event, $class, $method, $priority);
                    }
                }
            }
        }
    }

    /**
     * Get available events that can be listened to
     */
    public function getAvailableEvents(): array
    {
        return [
            'before_query' => 'Triggered before executing database query',
            'after_query' => 'Triggered after executing database query',
            'before_render' => 'Triggered before rendering the datatable',
            'after_render' => 'Triggered after rendering the datatable',
            'search_performed' => 'Triggered when search is performed',
            'filter_applied' => 'Triggered when filter is applied',
            'sort_changed' => 'Triggered when sorting changes',
            'pagination_changed' => 'Triggered when pagination changes',
            'column_visibility_changed' => 'Triggered when column visibility changes',
            'export_performed' => 'Triggered when export is performed',
            'error_occurred' => 'Triggered when an error occurs'
        ];
    }
}
