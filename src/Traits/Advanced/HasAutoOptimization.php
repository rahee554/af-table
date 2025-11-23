<?php

namespace ArtflowStudio\Table\Traits\Advanced;

use Illuminate\Database\Eloquent\Builder;

trait HasAutoOptimization
{
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
            if (!is_array($config)) {
                continue;
            }

            // Auto-detect relation usage
            if (isset($config['relation'])) {
                $relationString = $config['relation'];
                [$relationPath] = explode(':', $relationString);
                $relationsToEagerLoad[] = $relationPath;
            }

            // Auto-enable sorting and searching for simple columns
            if (isset($config['key']) && !isset($config['function']) && !isset($config['json'])) {
                if (!isset($config['sortable'])) {
                    $this->columns[$key]['sortable'] = true;
                }
                if (!isset($config['searchable']) && !isset($config['relation'])) {
                    $this->columns[$key]['searchable'] = true;
                }
            }

            // Auto-enable sorting and searching for relations
            if (isset($config['relation'])) {
                if (!isset($config['sortable'])) {
                    $this->columns[$key]['sortable'] = true;
                }
                if (!isset($config['searchable'])) {
                    $this->columns[$key]['searchable'] = true;
                }
            }
        }

        // Auto-apply eager loading relations
        if (!empty($relationsToEagerLoad)) {
            // Ensure cachedRelations is set
            $this->cachedRelations = array_unique(
                array_merge($this->cachedRelations ?? [], $relationsToEagerLoad)
            );
        }
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
            if (!is_array($config) || !isset($config['key'])) {
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
        if (!empty($autoCountAggregations)) {
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
        if (!empty($this->sortColumn)) {
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
            if (isset($config['key']) && ($config['sortable'] ?? true) && !isset($config['function'])) {
                $this->sortColumn = $config['key'];
                $this->sortDirection = 'asc';
                return;
            }
        }
    }

    /**
     * Automatically apply eager loading for relations
     * Replaces manual eager loading configuration
     */
    protected function autoApplyEagerLoading(Builder $query): Builder
    {
        if (!empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        return $query;
    }

    /**
     * Check if column has relation and return relation string
     */
    protected function getColumnRelation(string $columnKey): ?string
    {
        if (!isset($this->columns[$columnKey])) {
            return null;
        }

        $config = $this->columns[$columnKey];
        return $config['relation'] ?? null;
    }

    /**
     * Get all relations used in columns
     */
    protected function getAllColumnRelations(): array
    {
        $relations = [];

        if (empty($this->columns)) {
            return $relations;
        }

        foreach ($this->columns as $config) {
            if (!is_array($config) || !isset($config['relation'])) {
                continue;
            }

            [$relation] = explode(':', $config['relation']);
            $relations[] = $relation;
        }

        return array_unique($relations);
    }
}
