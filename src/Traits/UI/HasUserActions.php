<?php

namespace ArtflowStudio\Table\Traits\UI;

use Illuminate\Support\Facades\Log;

/**
 * Trait HasUserActions
 * 
 * Handles all user interaction methods including filters, search,
 * selections, refresh, and general user-initiated actions
 * Consolidates user action logic moved from main DatatableTrait
 */
trait HasUserActions
{
    /**
     * Clear all filters
     * Moved from DatatableTrait.php line 462
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
     * Moved from DatatableTrait.php line 477
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
     * Moved from DatatableTrait.php line 492
     */
    public function handleExport($format = 'csv', $filename = null)
    {
        try {
            $stats = $this->getExportStats();

            if ($stats['total_records'] > 10000) {
                return $this->exportWithChunking($format, $filename);
            } else {
                return $this->export($format, $filename);
            }
        } catch (\Exception $e) {
            $this->triggerErrorEvent($e, ['method' => 'handleExport', 'format' => $format]);
            session()->flash('error', 'Export failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Handle bulk actions
     * Moved from DatatableTrait.php line 522
     */
    public function handleBulkAction($actionKey)
    {
        try {
            $result = $this->executeBulkAction($actionKey);

            if ($result['success']) {
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
     * Select all visible records
     * Moved from DatatableTrait.php line 547
     */
    public function selectAllVisible()
    {
        $this->selectAllOnPage();
    }

    /**
     * Clear all selections
     * Moved from DatatableTrait.php line 555
     */
    public function clearAllSelections()
    {
        $this->clearSelection();
    }

    /**
     * Refresh component
     * Moved from DatatableTrait.php line 563
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
     * Clear selection - resolves conflict between HasActions and HasBulkActions
     * Uses bulk actions version for enhanced functionality
     * Moved from DatatableTrait.php line 2318
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
     * Moved from DatatableTrait.php line 2334
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
     * Refresh table (legacy method)
     * Moved from DatatableTrait.php line 1237
     */
    public function refreshTable()
    {
        $this->resetPage();
        $this->search = '';
    }

    /**
     * Toggle sort (legacy method)
     * Moved from DatatableTrait.php line 1254
     */
    public function toggleSort($column)
    {
        if (!$this->isAllowedColumn($column)) {
            return;
        }

        // If clicking the same column, toggle direction
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Apply date range filter
     * Moved from DatatableTrait.php line 1294
     */
    public function applyDateRange($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->resetPage();
    }

    /**
     * Clear filter
     * Moved from DatatableTrait.php line 1407
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
     * Get distinct values for a column
     * Moved from DatatableTrait.php line 1304
     */
    public function getDistinctValues($columnKey)
    {
        // For relation, get distinct from related table, else from main table
        $values = isset($this->columns[$columnKey]['relation'])
            ? $this->getRelationDistinctValues($columnKey)
            : $this->getColumnDistinctValues($columnKey);

        // Sort values alphabetically (case-insensitive)
        if (is_array($values)) {
            natcasesort($values);
            $values = array_values($values);
        }

        return $values;
    }

    /**
     * Get distinct values for relation column
     */
    protected function getRelationDistinctValues($columnKey)
    {
        $column = $this->columns[$columnKey];
        $relationString = $column['relation'];
        
        if (!$this->validateRelationString($relationString)) {
            return [];
        }

        [$relationName, $attribute] = explode(':', $relationString, 2);

        try {
            $modelInstance = new ($this->model);
            $relationObj = $modelInstance->$relationName();
            $relatedModel = $relationObj->getRelated();

            return $relatedModel::distinct()
                ->whereNotNull($attribute)
                ->orderBy($attribute)
                ->limit($this->maxDistinctValues ?? 1000)
                ->pluck($attribute)
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Relation distinct values error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get distinct values for regular column
     */
    protected function getColumnDistinctValues($columnKey)
    {
        $column = $this->columns[$columnKey];
        
        if (!isset($column['key']) || !$this->isValidColumn($column['key'])) {
            return [];
        }

        try {
            return $this->model::distinct()
                ->whereNotNull($column['key'])
                ->orderBy($column['key'])
                ->limit($this->maxDistinctValues ?? 1000)
                ->pluck($column['key'])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Column distinct values error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear distinct values cache
     * Moved from DatatableTrait.php line 1376
     */
    public function clearDistinctValuesCache()
    {
        $cachePattern = "datatable_distinct_{$this->tableId}_*";

        // Use targeted cache clearing instead of flushing all cache
        if (method_exists($this, 'clearCacheByPattern')) {
            $this->clearCacheByPattern($cachePattern);
        } else {
            // Fallback to individual cache key clearing
            foreach ($this->columns as $columnKey => $column) {
                $cacheKey = "datatable_distinct_{$this->tableId}_{$columnKey}";
                if (method_exists($this, 'clearCacheKey')) {
                    $this->clearCacheKey($cacheKey);
                }
            }
        }
    }

    /**
     * Handle filter column updates
     * Moved from DatatableTrait.php line 1396
     */
    public function updatedFilterColumn()
    {
        // Only clear the value, keep the column selected
        $this->filterValue = null;
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    /**
     * Handle filter value updates
     * Moved from DatatableTrait.php line 1419
     */
    public function updatedFilterValue()
    {
        // Get the filter type for the current filter column
        $filterType = isset($this->filters[$this->filterColumn]['type'])
            ? $this->filters[$this->filterColumn]['type']
            : 'text';

        // For text filters, only process if minimum 3 characters or empty
        if ($filterType === 'text' && !empty($this->filterValue) && strlen(trim($this->filterValue)) < 3) {
            return;
        }

        $this->resetPage();

        // Emit event for frontend handling
        $this->dispatch('filterValueUpdated', $this->filterColumn, $this->filterValue);
    }

    /**
     * Handle selected column updates for filtering
     * Moved from DatatableTrait.php line 1275
     */
    public function updatedSelectedColumn($column)
    {
        if (!$this->isAllowedColumn($column)) {
            return;
        }
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->filterColumn = $column;
            $this->columnType = $filterDetails['type'] ?? 'text';
            $this->filterOperator = $this->getDefaultOperator($this->columnType);
        }
    }

    /**
     * Handle records per page update (legacy method)
     * Moved from DatatableTrait.php line 1246
     */
    public function updatedrecords()
    {
        $this->resetPage();
    }

    /**
     * Abstract methods that must be implemented in the main class or other traits
     */
    abstract protected function autoSaveState(): void;
    abstract protected function emit(string $event, ...$params): void;
    abstract protected function getExportStats(): array;
    abstract protected function exportWithChunking(string $format, ?string $filename): mixed;
    abstract protected function export(string $format, ?string $filename): mixed;
    abstract protected function triggerErrorEvent(\Exception $e, array $context): void;
    abstract protected function executeBulkAction(string $actionKey): array;
    abstract protected function selectAllOnPage(): void;
    abstract protected function isAllowedColumn($column): bool;
    abstract protected function validateRelationString(string $relationString): bool;
    abstract protected function isValidColumn(string $column): bool;
    abstract protected function getDefaultOperator(string $filterType): string;
}
