<?php

namespace ArtflowStudio\Table\Traits\Core;

use Illuminate\Support\Facades\Log;

/**
 * Trait HasQueryBuilding
 * 
 * Handles all query building operations including custom constraints,
 * filters, search, sorting, and query optimization
 * Consolidates query logic moved from main DatatableTrait
 */
trait HasQueryBuilding
{
    /**
     * Build query (DEPRECATED - use buildUnifiedQuery)
     * Moved from DatatableTrait.php line 333
     * @deprecated Use buildUnifiedQuery() instead for better performance
     */
    protected function buildQuery()
    {
        return $this->buildUnifiedQuery();
    }

    /**
     * Get query builder instance (DEPRECATED - use buildUnifiedQuery)
     * Moved from DatatableTrait.php line 1717
     * @deprecated Use buildUnifiedQuery() instead for better performance
     */
    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->buildUnifiedQuery();
    }

    /**
     * Apply custom query constraints
     * Moved from DatatableTrait.php line 1823
     */
    protected function applyCustomQueryConstraints(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if (is_array($this->query)) {
            // Apply array-based constraints
            foreach ($this->query as $key => $value) {
                if (is_string($key)) {
                    // Handle key-value pairs: ['status' => 'active']
                    $query->where($key, $value);
                } elseif (is_array($value) && count($value) >= 2) {
                    // Handle operator arrays: [['status', '!=', 'inactive'], ['date', '>', '2023-01-01']]
                    if (count($value) === 2) {
                        $query->where($value[0], $value[1]);
                    } elseif (count($value) === 3) {
                        $query->where($value[0], $value[1], $value[2]);
                    }
                }
            }
        } elseif (is_callable($this->query)) {
            // Apply callable constraints
            try {
                call_user_func($this->query, $query);
            } catch (\Exception $e) {
                Log::error('Custom query constraint error: ' . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Apply filters to query
     * Moved from DatatableTrait.php line 1675
     */
    protected function applyFilters(\Illuminate\Database\Eloquent\Builder $query)
    {
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            // Get the filter type for the current filter column
            $filterType = isset($this->filters[$this->filterColumn]['type'])
                ? $this->filters[$this->filterColumn]['type']
                : 'text';

            // For text filters, only apply if 3+ characters or empty
            if ($filterType === 'text') {
                if (empty($this->filterValue) || strlen(trim($this->filterValue)) >= 3) {
                    $this->applyColumnFilter($query, $this->filterColumn, $this->filterOperator, $this->filterValue);
                }
            } else {
                // For non-text filters, always apply
                $this->applyColumnFilter($query, $this->filterColumn, $this->filterOperator, $this->filterValue);
            }
        }
    }

    /**
     * Apply column-specific filter
     */
    protected function applyColumnFilter(\Illuminate\Database\Eloquent\Builder $query, $column, $operator, $value)
    {
        // Find the column configuration
        $columnConfig = null;
        foreach ($this->columns as $key => $config) {
            if ($key === $column || (isset($config['key']) && $config['key'] === $column)) {
                $columnConfig = $config;
                break;
            }
        }

        if (!$columnConfig) {
            return;
        }

        // Apply filter based on column type
        if (isset($columnConfig['relation'])) {
            $this->applyRelationFilter($query, $columnConfig['relation'], $operator, $value);
        } elseif (isset($columnConfig['json'])) {
            $this->applyJsonFilter($query, $columnConfig['json'], $columnConfig['json_path'] ?? null, $operator, $value);
        } elseif (isset($columnConfig['key'])) {
            $this->applyRegularFilter($query, $columnConfig['key'], $operator, $value);
        }
    }

    /**
     * Apply relation filter
     */
    protected function applyRelationFilter(\Illuminate\Database\Eloquent\Builder $query, string $relationString, string $operator, $value)
    {
        if (!$this->validateRelationString($relationString)) {
            return;
        }

        [$relationName, $column] = explode(':', $relationString, 2);

        $query->whereHas($relationName, function ($q) use ($column, $operator, $value) {
            if ($operator === 'like' || $operator === 'LIKE') {
                $q->where($column, 'LIKE', '%' . $value . '%');
            } else {
                $q->where($column, $operator, $value);
            }
        });
    }

    /**
     * Apply JSON filter
     */
    protected function applyJsonFilter(\Illuminate\Database\Eloquent\Builder $query, string $jsonColumn, ?string $jsonPath, string $operator, $value)
    {
        $path = $jsonPath ?? 'data';
        $fullPath = $jsonColumn . '->' . $path;

        if ($operator === 'like' || $operator === 'LIKE') {
            $query->where($fullPath, 'LIKE', '%' . $value . '%');
        } else {
            $query->where($fullPath, $operator, $value);
        }
    }

    /**
     * Apply regular column filter
     */
    protected function applyRegularFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, string $operator, $value)
    {
        if ($operator === 'like' || $operator === 'LIKE') {
            $query->where($column, 'LIKE', '%' . $value . '%');
        } else {
            $query->where($column, $operator, $value);
        }
    }

    /**
     * Apply optimized search to query
     * Moved from DatatableTrait.php line 1888
     */
    protected function applyOptimizedSearch(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $search = $this->sanitizeSearch($this->search);

        $query->where(function ($query) use ($search) {
            $hasSearchableColumns = false;

            foreach ($this->columns as $columnKey => $column) {
                if ($this->isColumnSearchable($column)) {
                    $hasSearchableColumns = true;
                    $this->applyColumnSearch($query, $columnKey, $search);
                }
            }

            // If no searchable columns found, search on default columns
            if (!$hasSearchableColumns) {
                $this->applyDefaultSearch($query, $search);
            }
        });
    }

    /**
     * Apply column-specific search
     * Moved from DatatableTrait.php line 1906
     */
    protected function applyColumnSearch(\Illuminate\Database\Eloquent\Builder $query, $columnKey, $search)
    {
        $column = $this->columns[$columnKey] ?? null;
        if (!$column) {
            return;
        }

        $search = $this->sanitizeSearch($search);

        // Remove 3-character limit, always search with LIKE %search%
        if ($search === '') {
            return;
        }

        $query->orWhere(function ($q) use ($column, $search) {
            if (isset($column['relation'])) {
                $this->applyRelationSearch($q, $column['relation'], $search);
            } elseif (isset($column['json'])) {
                $this->applyJsonSearch($q, $column['json'], $column['json_path'] ?? null, $search);
            } elseif (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $q->where($column['key'], 'LIKE', '%' . $search . '%');
            }
        });
    }

    /**
     * Apply relation search
     */
    protected function applyRelationSearch(\Illuminate\Database\Eloquent\Builder $query, string $relationString, string $search)
    {
        if (!$this->validateRelationString($relationString)) {
            return;
        }

        [$relationName, $column] = explode(':', $relationString, 2);

        $query->whereHas($relationName, function ($q) use ($column, $search) {
            $q->where($column, 'LIKE', '%' . $search . '%');
        });
    }

    /**
     * Apply JSON search
     */
    protected function applyJsonSearch(\Illuminate\Database\Eloquent\Builder $query, string $jsonColumn, ?string $jsonPath, string $search)
    {
        $path = $jsonPath ?? 'data';
        $fullPath = $jsonColumn . '->' . $path;

        $query->where($fullPath, 'LIKE', '%' . $search . '%');
    }

    /**
     * Apply default search on common columns
     */
    protected function applyDefaultSearch(\Illuminate\Database\Eloquent\Builder $query, string $search)
    {
        $defaultSearchColumns = ['name', 'title', 'description', 'email'];

        foreach ($defaultSearchColumns as $column) {
            if ($this->isValidColumn($column)) {
                $query->orWhere($column, 'LIKE', '%' . $search . '%');
            }
        }
    }

    /**
     * Apply optimized sorting to query
     * Moved from DatatableTrait.php line 1937
     */
    protected function applyOptimizedSorting(\Illuminate\Database\Eloquent\Builder $query): void
    {
        // First try to find by key (backward compatibility)
        $sortColumnConfig = null;
        foreach ($this->columns as $col) {
            if (isset($col['key']) && $col['key'] === $this->sortColumn) {
                $sortColumnConfig = $col;
                break;
            }
        }

        // If not found by key, try to find by the full column identifier (for JSON columns)
        if (!$sortColumnConfig) {
            $sortColumnConfig = $this->columns[$this->sortColumn] ?? null;
        }

        // Validate sort direction
        $direction = strtolower($this->sortDirection);
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        if ($sortColumnConfig && isset($sortColumnConfig['relation'])) {
            $this->applyRelationSorting($query, $sortColumnConfig['relation'], $direction);
        } elseif ($sortColumnConfig && isset($sortColumnConfig['key']) && $this->isValidColumn($sortColumnConfig['key'])) {
            $query->orderBy($sortColumnConfig['key'], $direction);
        } elseif ($this->sortColumn === 'updated_at' && $this->isValidColumn('updated_at')) {
            $query->orderBy('updated_at', $direction);
        } else {
            // Fallback to ID sorting for consistency
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Apply relation sorting
     */
    protected function applyRelationSorting(\Illuminate\Database\Eloquent\Builder $query, string $relationString, string $direction)
    {
        if (!$this->validateRelationString($relationString)) {
            return;
        }

        [$relationName, $attribute] = explode(':', $relationString, 2);

        try {
            $modelInstance = new ($this->model);
            $relationObj = $modelInstance->$relationName();

            if (str_contains($relationName, '.')) {
                // Nested relation - use subquery sorting
                $this->applySubquerySorting($query, $relationName, $attribute, $direction);
            } else {
                // Simple relation - use join sorting
                $this->applyJoinSorting($query, $relationObj, $attribute, $direction);
            }
        } catch (\Exception $e) {
            Log::error('Relation sorting error: ' . $e->getMessage(), [
                'relation' => $relationName,
                'attribute' => $attribute,
            ]);
            // Fallback to ID sorting
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Apply join sorting for relations
     * Moved from DatatableTrait.php line 1991
     */
    protected function applyJoinSorting(\Illuminate\Database\Eloquent\Builder $query, $relationObj, $attribute, $direction = 'asc'): void
    {
        $modelInstance = new ($this->model);
        $relationTable = $relationObj->getRelated()->getTable();
        $parentTable = $modelInstance->getTable();

        // Create unique table alias to prevent join conflicts
        $tableAlias = $relationTable . '_sort_' . uniqid();

        // Get relation name for eager loading (extract from relation object)
        $relationName = null;
        if (method_exists($relationObj, 'getRelationName')) {
            $relationName = $relationObj->getRelationName();
        } else {
            // Fallback: extract from backtrace or use a generic approach
            $relationName = 'relation_' . uniqid();
        }

        // Check if this join already exists to prevent duplicates
        $joinExists = false;
        $existingJoins = $query->getQuery()->joins ?? [];
        foreach ($existingJoins as $join) {
            if (str_contains($join->table, $relationTable)) {
                $joinExists = true;
                $tableAlias = $join->table; // Use existing alias
                break;
            }
        }

        if (!$joinExists) {
            // Determine join type and keys based on relation type
            if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $foreignKey = $relationObj->getForeignKeyName();
                $ownerKey = $relationObj->getOwnerKeyName();
                $query->leftJoin($relationTable . ' as ' . $tableAlias, $parentTable . '.' . $foreignKey, '=', $tableAlias . '.' . $ownerKey);
            } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                $foreignKey = $relationObj->getForeignKeyName();
                $localKey = $relationObj->getLocalKeyName();
                $query->leftJoin($relationTable . ' as ' . $tableAlias, $parentTable . '.' . $localKey, '=', $tableAlias . '.' . $foreignKey);
            } else {
                // For other relation types, try to infer keys
                $foreignKey = $relationName . '_id';
                $query->leftJoin($relationTable . ' as ' . $tableAlias, $parentTable . '.' . $foreignKey, '=', $tableAlias . '.id');
            }
        }

        // Validate sort direction
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // Apply sorting with table alias
        $query->orderBy($tableAlias . '.' . $attribute, $direction);

        // Use DISTINCT to prevent duplicate results from joins instead of GROUP BY
        // This is better for MySQL strict mode (ONLY_FULL_GROUP_BY)
        $query->distinct();

        // Only modify select if no columns are currently selected
        // This prevents overriding the carefully constructed select columns
        if (empty($query->getQuery()->columns)) {
            $query->select($parentTable . '.*');
        }
    }

    /**
     * Apply subquery sorting for nested relations
     * Moved from DatatableTrait.php line 2090
     */
    protected function applySubquerySorting(\Illuminate\Database\Eloquent\Builder $query, $relationPath, $attribute, $direction = 'asc'): void
    {
        try {
            $modelInstance = new ($this->model);
            $relations = explode('.', $relationPath);
            
            // Build the nested relation query
            $currentModel = $modelInstance;
            $subquery = null;
            
            foreach ($relations as $i => $relationName) {
                if (!method_exists($currentModel, $relationName)) {
                    throw new \Exception("Relation {$relationName} not found");
                }
                
                $relationObj = $currentModel->$relationName();
                $currentModel = $relationObj->getRelated();
                
                if ($i === 0) {
                    // First relation
                    $subquery = $relationObj->getQuery();
                } elseif ($subquery !== null) {
                    // Nested relations - use whereHas
                    $remainingPath = implode('.', array_slice($relations, $i));
                    $subquery->whereHas($remainingPath, function ($q) use ($attribute, $direction) {
                        $q->orderBy($attribute, $direction);
                    });
                    break;
                }
            }
            
            // Apply the subquery ordering
            if ($subquery) {
                $query->orderBy(
                    $subquery->select($attribute)->limit(1),
                    $direction
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Subquery sorting error: ' . $e->getMessage(), [
                'relation_path' => $relationPath,
                'attribute' => $attribute,
            ]);
            
            // Fallback to simple ordering
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Abstract methods that must be implemented in the main class or other traits
     */
    abstract protected function validateRelationString($relationString): bool;
    abstract protected function isColumnSearchable($column): bool;
    abstract protected function isValidColumn($column): bool;
    abstract protected function sanitizeSearch($search): string;
}
