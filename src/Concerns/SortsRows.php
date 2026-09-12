<?php

namespace ArtflowStudio\Table\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Sorting: the click-to-sort action, direction handling, relation
 * sorting via join, and the header indicator.
 *
 * Consolidated in 1.6.0 from: HasSorting, HasSortingUI.
 */
trait SortsRows
{
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
                if ($relationObj instanceof BelongsTo) {
                    $foreignKey = $relationObj->getForeignKeyName();
                    $ownerKey = $relationObj->getOwnerKeyName();
                    $query->leftJoin($currentTable, $previousTable.'.'.$foreignKey, '=', $currentTable.'.'.$ownerKey);
                } elseif ($relationObj instanceof HasMany ||
                         $relationObj instanceof HasOne) {
                    $foreignKey = $relationObj->getForeignKeyName();
                    $localKey = $relationObj->getLocalKeyName();
                    $query->leftJoin($currentTable, $previousTable.'.'.$localKey, '=', $currentTable.'.'.$foreignKey);
                } elseif ($relationObj instanceof BelongsToMany) {
                    // For many-to-many, join through the pivot table
                    $pivotTable = $relationObj->getTable();
                    $parentKey = $relationObj->getParentKeyName();
                    $relatedKey = $relationObj->getRelatedKeyName();

                    $query->leftJoin($pivotTable, $previousTable.'.'.$parentKey, '=', $pivotTable.'.'.$relationObj->getForeignPivotKeyName());
                    $query->leftJoin($currentTable, $pivotTable.'.'.$relationObj->getRelatedPivotKeyName(), '=', $currentTable.'.'.$relatedKey);
                }

                $previousTable = $currentTable;
                $currentModel = $relatedModel;
            } catch (\Exception $e) {
                logger()->warning('Failed to build nested join for relation: '.$relationName.' - '.$e->getMessage());

                return;
            }
        }

        // Apply eager loading for performance
        $query->with($relationPath);

        // Validate sort direction
        $direction = $this->validateSortDirection($direction);

        // addSelect(), not select(): select() REPLACES the column list and
        // would discard withCount() subselects and any addSelect() the host
        // application added through the `query` closure. See AUDIT.md BUG-22.
        $query->distinct()->orderBy($currentTable.'.'.$attribute, $direction);

        $this->selectParentColumns($query, $previousTable);
    }

    /**
     * Apply sort by JSON column
     */
    protected function applySortByJson(Builder $query, array $columnConfig, string $direction)
    {
        if (! isset($columnConfig['key']) || ! isset($columnConfig['json'])) {
            return;
        }

        $jsonColumn = $columnConfig['key'];
        $jsonPath = $columnConfig['json'];

        if (! $this->validateJsonPath($jsonPath)) {
            return;
        }

        // Use JSON_EXTRACT for MySQL
        $query->orderByRaw("JSON_EXTRACT({$jsonColumn}, '$.{$jsonPath}') {$direction}");
    }

    /**
     * Apply sort by relation
     * Now handles nested relations like 'student.user:name' properly
     */
    protected function applySortByRelation(Builder $query, string $relationString, string $direction)
    {
        if (! $this->validateRelationString($relationString)) {
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
            logger()->warning('Relation sorting failed for '.$relationString.': '.$e->getMessage());
        }
    }

    /**
     * Apply multiple sorts
     */
    public function applySorts(array $sorts)
    {
        // For now, we only support single column sorting
        // This could be extended for multi-column sorting
        if (! empty($sorts)) {
            $firstSort = reset($sorts);
            if (isset($firstSort['column']) && isset($firstSort['direction'])) {
                $this->setSort($firstSort['column'], $firstSort['direction']);
            }
        }
    }

    /**
     * Clear sorting
     */
    public function clearSort()
    {
        $this->sortColumn = null;
        $this->sortDirection = 'asc';
        $this->syncSortAlias();
        $this->resetPage();
    }

    /**
     * Get label for a column
     * Useful for sort header templates
     *
     * @param string $column Column key
     * @return string|null Column label
     */
    public function getColumnLabel(string $column): ?string
    {
        if (! isset($this->columns[$column])) {
            return null;
        }

        $config = $this->columns[$column];

        if (isset($config['label'])) {
            return $config['label'];
        }

        return ucfirst(str_replace('_', ' ', $column));
    }

    /**
     * Get current sort direction for column
     */
    public function getSortDirection($column): ?string
    {
        return $this->isColumnSorted($column) ? $this->sortDirection : null;
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
     * Get sort state
     */
    public function getSortState(): array
    {
        return [
            'column' => $this->sortColumn,
            'direction' => $this->sortDirection,
            'sortable_columns' => array_keys($this->getSortableColumns()),
        ];
    }

    /**
     * Get sortable columns
     */
    public function getSortableColumns(): array
    {
        $sortableColumns = [];

        foreach ($this->columns as $columnKey => $column) {
            // isColumnSortable() takes a column KEY. This used to pass $column
            // (the config array), so every comparison inside failed and the
            // method always returned []. See AUDIT.md BUG-24.
            $lookup = is_array($column) ? ($column['key'] ?? $columnKey) : $columnKey;

            if ($this->isColumnSortable($lookup)) {
                $sortableColumns[$columnKey] = [
                    'key' => $columnKey,
                    'label' => $this->getColumnLabel($columnKey) ?? ucfirst(str_replace('_', ' ', $columnKey)),
                    'current_sort' => $this->isColumnSorted($columnKey),
                    'direction' => $this->getSortDirection($columnKey),
                ];
            }
        }

        return $sortableColumns;
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

        if (! $columnConfig) {
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
     * Check if column is currently sorted
     */
    public function isColumnSorted($column): bool
    {
        return $this->sortColumn === $column;
    }

    /**
     * Check if a column is currently sorted
     * Alias for isColumnSorted from HasSorting trait
     * Useful for displaying sort indicators in templates
     */
    public function isSorted(string $column): bool
    {
        // Delegate to HasSorting::isColumnSorted if available
        if (method_exists($this, 'isColumnSorted')) {
            return $this->isColumnSorted($column);
        }

        return $this->sortColumn === $column ?? false;
    }

    /** Qualify an ORDER BY column when the component knows how. */
    protected function qualifySortColumn(string $column): string
    {
        return method_exists($this, 'qualifyColumn') ? $this->qualifyColumn($column) : $column;
    }

    /**
     * Qualify the select list with `parent.*` after a join, without clobbering
     * anything already selected.
     *
     * Once a join is in play an unqualified `*` pulls the joined table's
     * columns in too and they overwrite the parent's during hydration, so the
     * wildcard has to be table-qualified - but it must be ADDED, not swapped in.
     */
    protected function selectParentColumns(Builder $query, string $parentTable): void
    {
        $existing = $query->getQuery()->columns ?? [];
        $wildcard = $parentTable.'.*';

        foreach ($existing as $column) {
            if ($column === $wildcard) {
                return;
            }
        }

        // A bare '*' is now ambiguous across the join - replace just that entry.
        if (($position = array_search('*', $existing, true)) !== false) {
            $existing[$position] = $wildcard;
            $query->getQuery()->columns = $existing;

            return;
        }

        $query->addSelect($wildcard);
    }

    /**
     * Set sort programmatically
     */
    public function setSort($column, $direction = 'asc')
    {
        if (! $this->isAllowedColumn($column)) {
            return;
        }

        $this->sortColumn = $column;
        $this->sortDirection = $this->validateSortDirection($direction);
        $this->syncSortAlias();
        $this->resetPage();
    }

    /**
     * Mirror $sortColumn onto the $sortBy alias.
     *
     * $sortBy exists purely for template/back-compat reasons; every write to
     * $sortColumn must go through here so the two cannot drift apart.
     */
    protected function syncSortAlias(): void
    {
        if (property_exists($this, 'sortBy')) {
            $this->sortBy = $this->sortColumn;
        }
    }

    /**
     * Toggle sort for a column
     */
    public function toggleSort($column)
    {
        if (! $this->isAllowedColumn($column)) {
            // Silently return - column not allowed
            return;
        }

        // Check if this is actually sortable
        if (! $this->isColumnSortable($column)) {
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

        // The template draws the ↑/↓ indicator from $sortBy, so it has to move
        // with $sortColumn or the arrow stays on the previous column while the
        // rows re-order underneath it. See AUDIT.md BUG-16.
        $this->syncSortAlias();

        $this->resetPage();
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
