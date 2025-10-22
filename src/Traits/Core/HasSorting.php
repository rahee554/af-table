<?php

namespace ArtflowStudio\Table\Traits\Core;

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
            // Silently return - column not allowed
            return;
        }

        // Check if this is actually sortable
        if (!$this->isColumnSortable($column)) {
            // Silently ignore - column is not sortable (nested relation, JSON, etc.)
            return;
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
     * Check if a column is actually sortable
     * Returns false for nested relations, JSON columns, and function columns
     */
    protected function isColumnSortable($column): bool
    {
        // Find the column configuration
        $columnConfig = null;
        foreach ($this->columns as $col) {
            if ((isset($col['key']) && $col['key'] === $column) || (isset($col['relation']) && str_starts_with($col['relation'], $column))) {
                $columnConfig = $col;
                break;
            }
        }

        if (!$columnConfig) {
            // Column not found in configuration
            return false;
        }

        // Nested relations are not sortable
        if (isset($columnConfig['relation']) && strpos($columnConfig['relation'], '.') !== false && strpos($columnConfig['relation'], ':') !== false) {
            return false;
        }

        // JSON columns are not sortable
        if (isset($columnConfig['json'])) {
            return false;
        }

        // Function columns are not sortable
        if (isset($columnConfig['function'])) {
            return false;
        }

        // Must have a key or relation to be sortable
        return isset($columnConfig['key']) || isset($columnConfig['relation']);
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
     * Now handles nested relations like 'student.user:name' properly
     */
    protected function applySortByRelation(Builder $query, string $relationString, string $direction)
    {
        if (!$this->validateRelationString($relationString)) {
            return;
        }

        [$relationPath, $attribute] = explode(':', $relationString);

        try {
            $modelInstance = new ($this->model);
            
            // Handle nested relations
            if (strpos($relationPath, '.') !== false) {
                // Nested relation like 'student.user:name'
                $this->applyNestedJoinSorting($query, $relationPath, $attribute, $direction);
            } else {
                // Simple relation like 'user:name'
                $relationObj = $modelInstance->$relationPath();
                $this->applyJoinSorting($query, $relationObj, $attribute, $direction);
            }
        } catch (\Exception $e) {
            // Fallback to basic sorting if relation fails
            logger()->warning('Relation sorting failed for ' . $relationString . ': ' . $e->getMessage());
        }
    }

    /**
     * Apply nested join sorting for multi-level relations
     * e.g., 'student.user:name' => JOIN student, JOIN user, ORDER BY user.name
     * 
     * Uses DISTINCT to avoid duplicates instead of GROUP BY to comply with ONLY_FULL_GROUP_BY
     */
    protected function applyNestedJoinSorting(Builder $query, string $relationPath, string $attribute, string $direction): void
    {
        $parts = explode('.', $relationPath);
        $modelInstance = new ($this->model);
        $currentModel = $modelInstance;
        $previousTable = $modelInstance->getTable();
        
        // Build joins progressively
        foreach ($parts as $index => $relationName) {
            try {
                $relationObj = $currentModel->$relationName();
                $relatedModel = $relationObj->getRelated();
                $currentTable = $relatedModel->getTable();
                
                // Determine the join condition based on relation type
                if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $foreignKey = $relationObj->getForeignKeyName();
                    $ownerKey = $relationObj->getOwnerKeyName();
                    $query->leftJoin($currentTable, $previousTable . '.' . $foreignKey, '=', $currentTable . '.' . $ownerKey);
                } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany || 
                         $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                    $foreignKey = $relationObj->getForeignKeyName();
                    $localKey = $relationObj->getLocalKeyName();
                    $query->leftJoin($currentTable, $previousTable . '.' . $localKey, '=', $currentTable . '.' . $foreignKey);
                } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                    // For many-to-many, join through the pivot table
                    $pivotTable = $relationObj->getTable();
                    $parentKey = $relationObj->getParentKeyName();
                    $relatedKey = $relationObj->getRelatedKeyName();
                    
                    $query->leftJoin($pivotTable, $previousTable . '.' . $parentKey, '=', $pivotTable . '.' . $relationObj->getForeignPivotKeyName());
                    $query->leftJoin($currentTable, $pivotTable . '.' . $relationObj->getRelatedPivotKeyName(), '=', $currentTable . '.' . $relatedKey);
                }
                
                $previousTable = $currentTable;
                $currentModel = $relatedModel;
            } catch (\Exception $e) {
                logger()->warning('Failed to build nested join for relation: ' . $relationName . ' - ' . $e->getMessage());
                return;
            }
        }
        
        // Apply eager loading for performance
        $query->with($relationPath);
        
        // Validate sort direction
        $direction = $this->validateSortDirection($direction);
        
        // Use DISTINCT to avoid duplicates from joins
        // This is better than GROUP BY when ONLY_FULL_GROUP_BY is enabled
        $query->distinct()
              ->orderBy($currentTable . '.' . $attribute, $direction)
              ->select($previousTable . '.*');
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
     * 
     * Uses DISTINCT to avoid duplicates from joins instead of GROUP BY
     * to comply with ONLY_FULL_GROUP_BY SQL mode
     */
    protected function applyJoinSorting(Builder $query, $relationObj, $attribute, $direction = 'asc'): void
    {
        $modelInstance = new ($this->model);
        $parentTable = $modelInstance->getTable();
        $relationTable = $relationObj->getRelated()->getTable();

        // Always eager load the relation for performance
        $relationName = method_exists($relationObj, 'getRelationName') ? $relationObj->getRelationName() : null;
        if ($relationName) {
            $query->with($relationName);
        }

        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $foreignKey = $relationObj->getForeignKeyName();
            $ownerKey = $relationObj->getOwnerKeyName();

            $query->leftJoin($relationTable, $parentTable . '.' . $foreignKey, '=', $relationTable . '.' . $ownerKey);
        } elseif (
            $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
            $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne
        ) {
            $foreignKey = $relationObj->getForeignKeyName();
            $localKey = $relationObj->getLocalKeyName();

            $query->leftJoin($relationTable, $parentTable . '.' . $localKey, '=', $relationTable . '.' . $foreignKey);
        }

        // Validate sort direction
        $direction = $this->validateSortDirection($direction);

        // Use DISTINCT to avoid duplicates from joins
        // This is better than GROUP BY when ONLY_FULL_GROUP_BY is enabled
        $query->distinct()
              ->orderBy($relationTable . '.' . $attribute, $direction)
              ->select($parentTable . '.*');
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
