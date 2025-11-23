<?php

namespace ArtflowStudio\Table\Traits\Advanced;

use Illuminate\Database\Eloquent\Builder;

trait HasCountAggregations
{
    /**
     * Define count aggregations for relations
     * Example: ['variants' => 'ProductVariant', 'images' => 'ProductImage']
     */
    protected array $countAggregations = [];

    /**
     * Add count aggregation for a relation
     * 
     * @param string $relationName The relation name (e.g., 'variants')
     * @param string $relationClass The related model class or null for auto-detection
     */
    public function addCountAggregation(string $relationName, ?string $relationClass = null): void
    {
        $this->countAggregations[$relationName] = $relationClass;
    }

    /**
     * Set multiple count aggregations
     * 
     * @param array $aggregations ['relationName' => 'RelatedClass', ...]
     */
    public function setCountAggregations(array $aggregations): void
    {
        $this->countAggregations = $aggregations;
    }

    /**
     * Get defined count aggregations
     */
    public function getCountAggregations(): array
    {
        return $this->countAggregations;
    }

    /**
     * Apply count aggregations to query
     * This prevents N+1 queries by loading counts via withCount()
     * 
     * @param Builder $query The query builder instance
     */
    public function applyCountAggregations(Builder $query): Builder
    {
        if (empty($this->countAggregations)) {
            return $query;
        }

        $withCountRelations = [];
        foreach ($this->countAggregations as $relationName => $relationClass) {
            $withCountRelations[] = $relationName;
        }

        if (!empty($withCountRelations)) {
            $query->withCount($withCountRelations);
        }

        return $query;
    }

    /**
     * Get count for a relation on a model
     * Uses the withCount() aggregation if available, otherwise falls back to relation count
     * 
     * @param object $model The model instance
     * @param string $relationName The relation name
     * @return int The count
     */
    public function getRelationCount(object $model, string $relationName): int
    {
        $countAttribute = $relationName . '_count';

        // Check if withCount() was applied
        if (isset($model->$countAttribute)) {
            return (int) $model->$countAttribute;
        }

        // Fallback: execute count query (will cause N+1)
        if (method_exists($model, $relationName)) {
            return $model->$relationName()->count();
        }

        return 0;
    }

    /**
     * Check if a relation has count aggregation enabled
     */
    public function hasCountAggregation(string $relationName): bool
    {
        return isset($this->countAggregations[$relationName]);
    }

    /**
     * Get count from model attributes (after withCount)
     * Recommended method for templates
     * 
     * @param object $model The model instance
     * @param string $relationName The relation name
     * @return int The count
     */
    public function count(object $model, string $relationName): int
    {
        return $this->getRelationCount($model, $relationName);
    }
}
