<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Log;

trait HasEagerLoading
{
    /**
     * Get eager loading relationships
     */
    protected function getEagerLoads(): array
    {
        $eagerLoads = [];

        // Check each column for relations that need to be eager loaded
        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                
                if ($this->validateRelationString($relationString)) {
                    [$relationName] = explode(':', $relationString);
                    
                    if (!in_array($relationName, $eagerLoads)) {
                        $eagerLoads[] = $relationName;
                    }
                }
            }
        }

        // Add nested relations if configured
        $nestedRelations = $this->getNestedRelations();
        foreach ($nestedRelations as $relation) {
            if (!in_array($relation, $eagerLoads)) {
                $eagerLoads[] = $relation;
            }
        }

        return $eagerLoads;
    }

    /**
     * Get nested relations that should be eager loaded
     */
    protected function getNestedRelations(): array
    {
        $nestedRelations = [];

        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['relation']) && strpos($column['relation'], '.') !== false) {
                $relationString = $column['relation'];
                
                // Handle nested relations like 'user.profile:name'
                if (strpos($relationString, ':') !== false) {
                    [$relationPart] = explode(':', $relationString);
                    $nestedRelations[] = $relationPart;
                } else {
                    $nestedRelations[] = $relationString;
                }
            }
        }

        return array_unique($nestedRelations);
    }

    /**
     * Apply eager loading to query
     */
    protected function applyEagerLoading($query)
    {
        $eagerLoads = $this->getEagerLoads();

        if (!empty($eagerLoads)) {
            try {
                $query->with($eagerLoads);
            } catch (\Exception $e) {
                Log::warning('Eager loading failed: ' . $e->getMessage());
            }
        }

        return $query;
    }

    /**
     * Optimize eager loading for specific columns
     */
    protected function optimizeEagerLoading($query, array $visibleColumns = null)
    {
        if ($visibleColumns === null) {
            $visibleColumns = array_keys(array_filter($this->visibleColumns));
        }

        $optimizedEagerLoads = [];

        // Only eager load relations for visible columns
        foreach ($visibleColumns as $columnKey) {
            if (isset($this->columns[$columnKey]['relation'])) {
                $relationString = $this->columns[$columnKey]['relation'];
                
                if ($this->validateRelationString($relationString)) {
                    [$relationName] = explode(':', $relationString);
                    
                    if (!in_array($relationName, $optimizedEagerLoads)) {
                        $optimizedEagerLoads[] = $relationName;
                    }
                }
            }
        }

        if (!empty($optimizedEagerLoads)) {
            try {
                $query->with($optimizedEagerLoads);
            } catch (\Exception $e) {
                Log::warning('Optimized eager loading failed: ' . $e->getMessage());
            }
        }

        return $query;
    }

    /**
     * Get relation count for statistics
     */
    protected function getRelationCounts($query, array $relations = null)
    {
        if ($relations === null) {
            $relations = $this->getEagerLoads();
        }

        $countRelations = [];

        foreach ($relations as $relation) {
            // Only add count for has-many type relations
            if ($this->isCountableRelation($relation)) {
                $countRelations[] = $relation;
            }
        }

        if (!empty($countRelations)) {
            try {
                $query->withCount($countRelations);
            } catch (\Exception $e) {
                Log::warning('Relation count failed: ' . $e->getMessage());
            }
        }

        return $query;
    }

    /**
     * Check if relation is countable (has-many type)
     */
    protected function isCountableRelation($relationName): bool
    {
        try {
            $modelInstance = new ($this->model);
            $relation = $modelInstance->$relationName();

            return $relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||
                   $relation instanceof \Illuminate\Database\Eloquent\Relations\HasManyThrough ||
                   $relation instanceof \Illuminate\Database\Eloquent\Relations\MorphMany ||
                   $relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate that relations exist on the model
     */
    protected function validateEagerLoads(): array
    {
        $validRelations = [];
        $invalidRelations = [];

        try {
            $modelInstance = new ($this->model);
            
            foreach ($this->getEagerLoads() as $relation) {
                if (method_exists($modelInstance, $relation)) {
                    try {
                        $relationInstance = $modelInstance->$relation();
                        if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                            $validRelations[] = $relation;
                        } else {
                            $invalidRelations[] = $relation;
                        }
                    } catch (\Exception $e) {
                        $invalidRelations[] = $relation;
                    }
                } else {
                    $invalidRelations[] = $relation;
                }
            }

            if (!empty($invalidRelations)) {
                Log::warning('Invalid relations found: ' . implode(', ', $invalidRelations));
            }

        } catch (\Exception $e) {
            Log::warning('Relation validation failed: ' . $e->getMessage());
        }

        return $validRelations;
    }

    /**
     * Get relation loading strategy
     */
    protected function getRelationLoadingStrategy(): string
    {
        $totalRelations = count($this->getEagerLoads());
        $recordCount = $this->getEstimatedRecordCount();

        // Use lazy loading for large datasets with many relations
        if ($recordCount > 1000 && $totalRelations > 5) {
            return 'lazy';
        }

        // Use eager loading for smaller datasets
        if ($recordCount <= 100) {
            return 'eager';
        }

        // Use selective eager loading for medium datasets
        return 'selective';
    }

    /**
     * Get estimated record count for the current query
     */
    protected function getEstimatedRecordCount(): int
    {
        try {
            $query = $this->getQuery();
            return $query->count();
        } catch (\Exception $e) {
            // Fallback to total model count
            return $this->model::count();
        }
    }

    /**
     * Apply loading strategy
     */
    protected function applyLoadingStrategy($query)
    {
        $strategy = $this->getRelationLoadingStrategy();

        switch ($strategy) {
            case 'eager':
                return $this->applyEagerLoading($query);
                
            case 'selective':
                $visibleColumns = array_keys(array_filter($this->visibleColumns));
                return $this->optimizeEagerLoading($query, $visibleColumns);
                
            case 'lazy':
            default:
                // Don't apply eager loading, use lazy loading
                return $query;
        }
    }

    /**
     * Get eager loading statistics
     */
    public function getEagerLoadingStats(): array
    {
        $eagerLoads = $this->getEagerLoads();
        $validRelations = $this->validateEagerLoads();
        $strategy = $this->getRelationLoadingStrategy();

        return [
            'total_relations' => count($eagerLoads),
            'valid_relations' => count($validRelations),
            'invalid_relations' => count($eagerLoads) - count($validRelations),
            'loading_strategy' => $strategy,
            'relations' => $eagerLoads,
            'valid_relations_list' => $validRelations,
            'estimated_records' => $this->getEstimatedRecordCount()
        ];
    }

    /**
     * Preload specific relations
     */
    public function preloadRelations(array $relations)
    {
        try {
            $query = $this->getQuery();
            $query->with($relations);
            return $query;
        } catch (\Exception $e) {
            Log::warning('Manual relation preloading failed: ' . $e->getMessage());
            return $this->getQuery();
        }
    }
}
