<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSorting
{
    /**
     * Apply sorting to query (alias for applyOptimizedSorting)
     */
    public function applySortingToQuery(Builder $query, string $column, string $direction): void
    {
        // Store the sort parameters temporarily
        $originalColumn = $this->sortColumn ?? null;
        $originalDirection = $this->sortDirection ?? 'asc';
        
        $this->sortColumn = $column;
        $this->sortDirection = $direction;
        
        // Apply the sorting
        $this->applyOptimizedSorting($query);
        
        // Restore original values if they were different
        if ($originalColumn !== $column || $originalDirection !== $direction) {
            $this->sortColumn = $originalColumn;
            $this->sortDirection = $originalDirection;
        }
    }

    /**
     * Toggle sort for a column
     */
    public function toggleSort($column)
    {
        if (!$this->isAllowedColumn($column)) {
            return; // Ignore invalid column
        }
        
        // If clicking the same column, toggle direction
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, start with asc
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    /**
     * Apply optimized sorting to query
     */
    protected function applyOptimizedSorting(Builder $query): void
    {
        // First try to find by key (backward compatibility)
        $sortColumnConfig = null;
        foreach ($this->columns as $column) {
            if (isset($column['key']) && $column['key'] === $this->sortColumn) {
                $sortColumnConfig = $column;
                break;
            }
        }

        // If not found by key, try to find by the full column identifier (for JSON columns)
        if (!$sortColumnConfig) {
            $sortColumnConfig = $this->columns[$this->sortColumn] ?? null;
        }

        // Validate sort direction
        $direction = $this->validateSortDirection($this->sortDirection);

        if ($sortColumnConfig && isset($sortColumnConfig['relation'])) {
            $this->applySortByRelation($query, $sortColumnConfig['relation'], $direction);
        } elseif ($sortColumnConfig && isset($sortColumnConfig['json'])) {
            $this->applySortByJson($query, $sortColumnConfig, $direction);
        } elseif ($sortColumnConfig && isset($sortColumnConfig['key']) && $this->isValidColumn($sortColumnConfig['key'])) {
            $query->orderBy($sortColumnConfig['key'], $direction);
        } elseif ($this->sortColumn === 'updated_at' && $this->isValidColumn('updated_at')) {
            $query->orderBy('updated_at', $direction);
        }
    }

    /**
     * Apply sort by relation
     */
    protected function applySortByRelation(Builder $query, string $relationString, string $direction)
    {
        if (!$this->validateRelationString($relationString)) {
            return;
        }

        [$relation, $attribute] = explode(':', $relationString);

        try {
            $modelInstance = new ($this->model);
            $relationObj = $modelInstance->$relation();
            $this->applyJoinSorting($query, $relationObj, $attribute, $direction);
        } catch (\Exception $e) {
            // Fallback to basic sorting if relation fails
            logger()->warning('Relation sorting failed: ' . $e->getMessage());
        }
    }

    /**
     * Apply sort by JSON column
     */
    protected function applySortByJson(Builder $query, array $columnConfig, string $direction)
    {
        if (!isset($columnConfig['key']) || !isset($columnConfig['json'])) {
            return;
        }

        $jsonColumn = $columnConfig['key'];
        $jsonPath = $columnConfig['json'];

        if (!$this->validateJsonPath($jsonPath)) {
            return;
        }

        // Use JSON_EXTRACT for MySQL
        $query->orderByRaw("JSON_EXTRACT({$jsonColumn}, '$.{$jsonPath}') {$direction}");
    }

    /**
     * Apply join sorting for relations
     */
    protected function applyJoinSorting(Builder $query, $relationObj, $attribute, $direction = 'asc'): void
    {
        $modelInstance = new ($this->model);
        $relationTable = $relationObj->getRelated()->getTable();

        // Always eager load the relation for performance
        $relationName = method_exists($relationObj, 'getRelationName') ? $relationObj->getRelationName() : null;
        if ($relationName) {
            $query->with($relationName);
        }

        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $parentTable = $modelInstance->getTable();
            $foreignKey = $relationObj->getForeignKeyName();
            $ownerKey = $relationObj->getOwnerKeyName();

            $query->leftJoin($relationTable, $parentTable . '.' . $foreignKey, '=', $relationTable . '.' . $ownerKey);
        } elseif (
            $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
            $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne
        ) {
            $parentTable = $modelInstance->getTable();
            $foreignKey = $relationObj->getForeignKeyName();
            $localKey = $relationObj->getLocalKeyName();

            $query->leftJoin($relationTable, $parentTable . '.' . $localKey, '=', $relationTable . '.' . $foreignKey);
        }

        // Validate sort direction
        $direction = $this->validateSortDirection($direction);

        $query->orderBy($relationTable . '.' . $attribute, $direction)
              ->select($modelInstance->getTable() . '.*'); // Ensure we select from main table
    }

    /**
     * Set sort programmatically
     */
    public function setSort($column, $direction = 'asc')
    {
        if (!$this->isAllowedColumn($column)) {
            return;
        }

        $this->sortColumn = $column;
        $this->sortDirection = $this->validateSortDirection($direction);
        $this->resetPage();
    }

    /**
     * Clear sorting
     */
    public function clearSort()
    {
        $this->sortColumn = null;
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /**
     * Get sort icon class
     */
    public function getSortIcon($column): string
    {
        if ($this->sortColumn !== $column) {
            return 'fas fa-sort'; // Neutral sort icon
        }

        return $this->sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }

    /**
     * Check if column is currently sorted
     */
    public function isColumnSorted($column): bool
    {
        return $this->sortColumn === $column;
    }

    /**
     * Get current sort direction for column
     */
    public function getSortDirection($column): ?string
    {
        return $this->isColumnSorted($column) ? $this->sortDirection : null;
    }

    /**
     * Get sortable columns
     */
    public function getSortableColumns(): array
    {
        $sortableColumns = [];

        foreach ($this->columns as $columnKey => $column) {
            if ($this->isColumnSortable($column)) {
                $sortableColumns[$columnKey] = [
                    'key' => $columnKey,
                    'label' => $this->getColumnLabel($column, $columnKey),
                    'current_sort' => $this->isColumnSorted($columnKey),
                    'direction' => $this->getSortDirection($columnKey)
                ];
            }
        }

        return $sortableColumns;
    }

    /**
     * Apply multiple sorts
     */
    public function applySorts(array $sorts)
    {
        // For now, we only support single column sorting
        // This could be extended for multi-column sorting
        if (!empty($sorts)) {
            $firstSort = reset($sorts);
            if (isset($firstSort['column']) && isset($firstSort['direction'])) {
                $this->setSort($firstSort['column'], $firstSort['direction']);
            }
        }
    }

    /**
     * Get sort state
     */
    public function getSortState(): array
    {
        return [
            'column' => $this->sortColumn,
            'direction' => $this->sortDirection,
            'sortable_columns' => array_keys($this->getSortableColumns())
        ];
    }

    /**
     * Reset sort to default
     */
    public function resetSortToDefault()
    {
        $this->sortColumn = $this->getOptimalSortColumn();
        $this->sortDirection = $this->sort ?? 'desc';
        $this->resetPage();
    }

    /**
     * Check if sorting is enabled
     */
    public function isSortingEnabled(): bool
    {
        return $this->colSort ?? true;
    }

    /**
     * Toggle sort direction for current column
     */
    public function toggleSortDirection()
    {
        if ($this->sortColumn) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            $this->resetPage();
        }
    }
}
