<?php

namespace ArtflowStudio\Table\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

/**
 * Assembles the Eloquent query: base constraints, eager loading,
 * column optimisation and the auto-detection that runs at mount.
 *
 * Consolidated in 1.6.0 from: HasQueryBuilding, HasAutoOptimization, HasUnifiedOptimization.
 */
trait BuildsQueries
{
    /**
     * Apply custom query constraints.
     */
    protected function applyCustomQueryConstraints(Builder $query): void
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
                Log::error('Custom query constraint error: '.$e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Apply join sorting for relations
     * Moved from DatatableTrait.php line 1991
     */
    protected function applyJoinSorting(Builder $query, $relationObj, $attribute, $direction = 'asc'): void
    {
        $modelInstance = new ($this->model);
        $relationTable = $relationObj->getRelated()->getTable();
        $parentTable = $modelInstance->getTable();

        // Create unique table alias to prevent join conflicts
        $tableAlias = $relationTable.'_sort_'.uniqid();

        // Get relation name for eager loading (extract from relation object)
        $relationName = null;
        if (method_exists($relationObj, 'getRelationName')) {
            $relationName = $relationObj->getRelationName();
        } else {
            // Fallback: extract from backtrace or use a generic approach
            $relationName = 'relation_'.uniqid();
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

        if (! $joinExists) {
            // Determine join type and keys based on relation type
            if ($relationObj instanceof BelongsTo) {
                $foreignKey = $relationObj->getForeignKeyName();
                $ownerKey = $relationObj->getOwnerKeyName();
                $query->leftJoin($relationTable.' as '.$tableAlias, $parentTable.'.'.$foreignKey, '=', $tableAlias.'.'.$ownerKey);
            } elseif ($relationObj instanceof HasOne) {
                $foreignKey = $relationObj->getForeignKeyName();
                $localKey = $relationObj->getLocalKeyName();
                $query->leftJoin($relationTable.' as '.$tableAlias, $parentTable.'.'.$localKey, '=', $tableAlias.'.'.$foreignKey);
            } else {
                // For other relation types, try to infer keys
                $foreignKey = $relationName.'_id';
                $query->leftJoin($relationTable.' as '.$tableAlias, $parentTable.'.'.$foreignKey, '=', $tableAlias.'.id');
            }
        }

        // Validate sort direction
        $direction = strtolower($direction);
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // Apply sorting with table alias
        $query->orderBy($tableAlias.'.'.$attribute, $direction);

        // Use DISTINCT to prevent duplicate results from joins instead of GROUP BY
        // This is better for MySQL strict mode (ONLY_FULL_GROUP_BY)
        $query->distinct();

        // Only modify select if no columns are currently selected
        // This prevents overriding the carefully constructed select columns
        if (empty($query->getQuery()->columns)) {
            $query->select($parentTable.'.*');
        }
    }

    /**
     * Apply optimized sorting to query
     * Moved from DatatableTrait.php line 1937
     */
    protected function applyOptimizedSorting(Builder $query): void
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
        if (! $sortColumnConfig) {
            $sortColumnConfig = $this->columns[$this->sortColumn] ?? null;
        }

        // Validate sort direction
        $direction = strtolower($this->sortDirection);
        if (! in_array($direction, ['asc', 'desc'])) {
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
    protected function applyRelationSorting(Builder $query, string $relationString, string $direction)
    {
        if (! $this->validateRelationString($relationString)) {
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
            Log::error('Relation sorting error: '.$e->getMessage(), [
                'relation' => $relationName,
                'attribute' => $attribute,
            ]);
            // Fallback to ID sorting
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Apply subquery sorting for nested relations
     * Moved from DatatableTrait.php line 2090
     */
    protected function applySubquerySorting(Builder $query, $relationPath, $attribute, $direction = 'asc'): void
    {
        try {
            $modelInstance = new ($this->model);
            $relations = explode('.', $relationPath);

            // Build the nested relation query
            $currentModel = $modelInstance;
            $subquery = null;

            foreach ($relations as $i => $relationName) {
                if (! method_exists($currentModel, $relationName)) {
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
            Log::error('Subquery sorting error: '.$e->getMessage(), [
                'relation_path' => $relationPath,
                'attribute' => $attribute,
            ]);

            // Fallback to simple ordering
            $query->orderBy('id', $direction);
        }
    }

    /**
     * Automatically apply eager loading for relations
     * Replaces manual eager loading configuration
     */
    protected function autoApplyEagerLoading(Builder $query): Builder
    {
        if (! empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        return $query;
    }

    /**
     * Auto-detect count aggregations from columns
     * Looks for _count suffix in column keys and automatically
     * configures count aggregations for those relations
     */
    protected function autoDetectCountAggregations(): void
    {
        if (empty($this->columns)) {
            return;
        }

        $autoCountAggregations = [];

        foreach ($this->columns as $config) {
            if (! is_array($config) || ! isset($config['key'])) {
                continue;
            }

            $key = $config['key'];

            // Detect _count suffix pattern
            if (str_ends_with($key, '_count')) {
                // Extract relation name (remove _count suffix)
                $relationName = substr($key, 0, -6);

                // Check if this relation exists on the model
                try {
                    $modelInstance = new ($this->model);
                    if (method_exists($modelInstance, $relationName)) {
                        $autoCountAggregations[$relationName] = null;
                    }
                } catch (\Exception $e) {
                    // Silently skip if model instantiation fails
                }
            }
        }

        // Merge with existing count aggregations
        if (! empty($autoCountAggregations)) {
            $existing = $this->countAggregations ?? [];
            $this->countAggregations = array_merge($existing, $autoCountAggregations);
        }
    }

    /**
     * Automatically detect and configure the best column to sort by
     * Prefers: id > created_at > name > first column
     */
    protected function autoDetectOptimalSort(): void
    {
        if (! empty($this->sortColumn)) {
            return; // Already set
        }

        $candidates = ['id', 'created_at', 'updated_at', 'name'];

        foreach ($candidates as $column) {
            foreach ($this->columns as $config) {
                if (isset($config['key']) && $config['key'] === $column) {
                    $this->sortColumn = $column;
                    $this->sortDirection = in_array($column, ['created_at', 'updated_at']) ? 'desc' : 'asc';

                    return;
                }
            }
        }

        // Fallback: use first sortable column
        foreach ($this->columns as $config) {
            if (isset($config['key']) && ($config['sortable'] ?? true) && ! isset($config['function'])) {
                $this->sortColumn = $config['key'];
                $this->sortDirection = 'asc';

                return;
            }
        }
    }

    /**
     * Auto-detect and apply optimizations
     * Analyzes column configuration and automatically:
     * - Detects relation usage
     * - Applies eager loading
     * - Adds count aggregations
     * - Enables sorting and searching
     *
     * This prevents N+1 queries without manual configuration!
     */
    protected function autoOptimizeColumns(): void
    {
        if (empty($this->columns)) {
            return;
        }

        $relationsToEagerLoad = [];
        $countsToAggregate = [];

        foreach ($this->columns as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            // Auto-detect relation usage
            if (isset($config['relation'])) {
                $relationString = $config['relation'];
                [$relationPath] = explode(':', $relationString);
                $relationsToEagerLoad[] = $relationPath;
            }

            // Auto-enable sorting and searching for simple columns
            if (isset($config['key']) && ! isset($config['function']) && ! isset($config['json'])) {
                if (! isset($config['sortable'])) {
                    $this->columns[$key]['sortable'] = true;
                }
                if (! isset($config['searchable']) && ! isset($config['relation'])) {
                    $this->columns[$key]['searchable'] = true;
                }
            }

            // Auto-enable sorting and searching for relations
            if (isset($config['relation'])) {
                if (! isset($config['sortable'])) {
                    $this->columns[$key]['sortable'] = true;
                }
                if (! isset($config['searchable'])) {
                    $this->columns[$key]['searchable'] = true;
                }
            }
        }

        // Auto-apply eager loading relations
        if (! empty($relationsToEagerLoad)) {
            // Ensure cachedRelations is set
            $this->cachedRelations = array_unique(
                array_merge($this->cachedRelations ?? [], $relationsToEagerLoad)
            );
        }
    }

    abstract protected function isColumnSearchable($column): bool;

    abstract protected function sanitizeSearch($search): string;
}
