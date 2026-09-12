<?php

namespace ArtflowStudio\Table\Concerns;

use ArtflowStudio\Table\Support\TableCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Caching of distinct filter values and count aggregations, on top
 * of the Redis-aware layer in Support\TableCache.
 *
 * Consolidated in 1.6.0 from: HasUnifiedCaching, HasCountAggregations.
 */
trait CachesResults
{
    /**
     * Unified cache configuration combining all caching strategies
     */
    protected array $unifiedCacheConfig = [
        'default_ttl' => 300, // 5 minutes
        'tags_enabled' => null, // Will be auto-detected
        'prefix' => 'datatable',
        'cache_duration' => 300,
        'cache_enabled' => true,
        'intelligent_warming' => true,
        'selective_invalidation' => true,
    ];

    /**
     * Cache statistics for monitoring
     */
    protected array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'warmed_keys' => 0,
        'invalidations' => 0,
        'efficiency_score' => 0,
    ];

    /**
     * PUBLIC on purpose: Livewire only persists public properties between
     * requests. As a protected property this reset to [] on the first update
     * after mount, silently dropping every {relation}_count. See AUDIT.md BUG-03.
     */
    public array $countAggregations = [];

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

        if (! empty($withCountRelations)) {
            $query->withCount($withCountRelations);
        }

        return $query;
    }

    /**
     * Store data in cache with unified strategy
     */
    protected function cacheRemember(string $key, $ttl, callable $callback, string $kind = 'default')
    {
        if (! $this->unifiedCacheConfig['cache_enabled']) {
            return $callback();
        }

        $fullKey = $this->getCacheKey($key);

        // Hit/miss accounting used to be meaningless: `misses` incremented
        // inside the closure and `hits` incremented from a Cache::has() call
        // made AFTER remember(), which is always true. Flip a flag inside the
        // closure instead - it runs only on a miss, by definition.
        // See AUDIT.md BUG-26.
        $missed = false;

        $result = TableCache::remember(
            $fullKey,
            $kind,
            function () use ($callback, &$missed) {
                $missed = true;

                return $callback();
            },
            $this->getCacheTags()
        );

        $this->cacheStats[$missed ? 'misses' : 'hits']++;

        return $result;
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

    /**
     * Determine cache duration based on data volatility
     */
    public function determineCacheDuration(string $type = 'default'): int
    {
        $durations = [
            'query_results' => TableCache::ttl('default'),
            'distinct_values' => TableCache::ttl('distinct_values'),
            'static_data' => TableCache::ttl('schema'),
            'user_preferences' => TableCache::ttl('column_metadata'),
            'default' => TableCache::ttl('default'),
        ];

        return $durations[$type] ?? $durations['default'];
    }

    /**
     * Generate distinct values cache key
     */
    public function generateDistinctValuesCacheKey(string $columnKey): string
    {
        $filterHash = md5(serialize($this->filters ?? []));

        return "distinct_values_{$this->tableId}_{$columnKey}_{$filterHash}";
    }

    /**
     * Generate cache key with proper prefixing
     */
    protected function getCacheKey(string $suffix): string
    {
        return "{$this->unifiedCacheConfig['prefix']}:{$this->tableId}:{$suffix}";
    }

    /**
     * Get cache tags for targeted invalidation
     */
    protected function getCacheTags(): array
    {
        // $model can be an object on some call paths; str_replace() on an
        // object is a TypeError, so normalise first.
        $model = is_object($this->model) ? get_class($this->model) : (string) ($this->model ?? 'unknown');
        $modelName = str_replace('\\', '_', $model);

        return [
            "datatable:{$this->tableId}",
            "model:{$modelName}",
            'unified_cache',
        ];
    }

    /**
     * Get cached distinct values (from HasAdvancedCaching)
     */
    protected function getCachedDistinctValues(string $columnKey): array
    {
        $cacheKey = $this->generateDistinctValuesCacheKey($columnKey);
        $cacheDuration = $this->determineCacheDuration('distinct_values');

        return $this->cacheRemember($cacheKey, $cacheDuration, function () use ($columnKey) {
            if (isset($this->columns[$columnKey]['relation'])) {
                return $this->getRelationDistinctValues($columnKey);
            } else {
                return $this->getColumnDistinctValues($columnKey);
            }
        });
    }

    /**
     * Get distinct values for regular columns
     */
    protected function getColumnDistinctValues(string $columnKey): array
    {
        try {
            $column = $this->columns[$columnKey] ?? null;
            if (! $column) {
                return [];
            }

            // CRITICAL FIX: Do NOT apply filters when getting distinct values
            // The dropdown should show ALL available values, not just filtered ones
            $query = $this->model::query();
            // Removed: $this->applyFilters($query);

            if (isset($column['key'])) {
                $columnName = $column['key'];
            } elseif (isset($column['json_column']) && isset($column['json_path'])) {
                $columnName = $column['json_column'];
            } else {
                return [];
            }

            // Cap the result set. A distinct filter on a high-cardinality
            // column (an email, a timestamp) would otherwise pull every row in
            // the table into a <select>. `maxDistinctValues` existed as a
            // property but was never applied anywhere.
            $limit = (int) ($this->maxDistinctValues
                ?? config('aftable.query.max_distinct_values', 1000));

            return $query->select($columnName)
                ->distinct()
                ->limit($limit)
                ->pluck($columnName)
                ->filter()
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::warning("Failed to get distinct values for column {$columnKey}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Get defined count aggregations
     */
    public function getCountAggregations(): array
    {
        return $this->countAggregations;
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
        $countAttribute = $relationName.'_count';

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
     * Get distinct values for relationship columns
     */
    protected function getRelationDistinctValues(string $columnKey): array
    {
        try {
            // First check if relation is defined in columns, then in filters
            $column = $this->columns[$columnKey] ?? null;
            $filter = $this->filters[$columnKey] ?? null;

            $relation = null;
            if ($column && isset($column['relation'])) {
                $relation = $column['relation'];
            } elseif ($filter && isset($filter['relation'])) {
                $relation = $filter['relation'];
            }

            if (! $relation) {
                return [];
            }

            $relationParts = explode(':', $relation);
            $relationPath = $relationParts[0];
            $attribute = $relationParts[1] ?? 'id';

            // CRITICAL FIX: Do NOT apply filters when getting distinct values
            // The dropdown should show ALL available values, not just filtered ones
            $query = $this->model::query();
            // Removed: $this->applyFilters($query);

            // Get all records with their relations loaded
            $records = $query->with($relationPath)->get();

            $distinctValues = [];

            foreach ($records as $record) {
                $relation = $record->{$relationPath};
                if ($relation) {
                    // Use the foreign key as the array key and display value as array value
                    $foreignKeyField = $relationPath.'_id';

                    // Try to get the foreign key value from different possible field names
                    $foreignKeyValue = null;
                    if (isset($record->{$foreignKeyField})) {
                        $foreignKeyValue = $record->{$foreignKeyField};
                    } elseif (isset($record->{$relationPath.'_id'})) {
                        $foreignKeyValue = $record->{$relationPath.'_id'};
                    } elseif (isset($record->{$columnKey})) {
                        $foreignKeyValue = $record->{$columnKey};
                    } else {
                        $foreignKeyValue = $relation->id;
                    }

                    $displayValue = $relation->{$attribute};

                    if ($foreignKeyValue && $displayValue) {
                        $distinctValues[$foreignKeyValue] = $displayValue;
                    }
                }
            }

            return $distinctValues;

        } catch (\Exception $e) {
            Log::warning("Failed to get relation distinct values for column {$columnKey}: {$e->getMessage()}");

            return [];
        }
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
}
