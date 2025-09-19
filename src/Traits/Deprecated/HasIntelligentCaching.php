<?php

namespace ArtflowStudio\Table\Traits;

trait HasIntelligentCaching
{
    /**
     * Cache hit rate tracking
     */
    protected array $cacheMetrics = [
        'hits' => 0,
        'misses' => 0,
        'invalidations' => 0,
        'warm_ups' => 0
    ];

    /**
     * Cache warming priorities
     */
    protected array $cacheWarmingPriorities = [
        'query_results' => 10,
        'distinct_values' => 8,
        'aggregations' => 6,
        'relation_data' => 5,
        'filters' => 3
    ];

    /**
     * Memory-efficient cache key generation
     */
    protected function generateIntelligentCacheKey(string $type, array $params = []): string
    {
        // Use fast hashing for better performance
        $baseKey = 'datatable:' . $this->getCacheKeyPrefix() . ':' . $type;
        
        if (empty($params)) {
            return $baseKey;
        }

        // Sort params for consistent key generation
        ksort($params);
        
        // Use hash for long parameter lists to keep keys short
        $paramHash = md5(serialize($params));
        
        return $baseKey . ':' . $paramHash;
    }

    /**
     * Intelligent cache duration based on data volatility
     */
    protected function getIntelligentCacheDuration(string $type, array $metadata = []): int
    {
        $baseDurations = [
            'query_results' => 3600,    // 1 hour
            'distinct_values' => 7200,  // 2 hours
            'aggregations' => 1800,     // 30 minutes
            'relation_data' => 5400,    // 1.5 hours
            'filters' => 2700,          // 45 minutes
            'column_config' => 14400,   // 4 hours
        ];

        $duration = $baseDurations[$type] ?? 1800;

        // Adjust based on data size
        $recordCount = $metadata['record_count'] ?? 0;
        if ($recordCount > 10000) {
            $duration *= 2; // Cache longer for large datasets
        } elseif ($recordCount < 100) {
            $duration /= 2; // Cache shorter for small datasets
        }

        // Adjust based on update frequency
        $updateFrequency = $metadata['update_frequency'] ?? 'medium';
        switch ($updateFrequency) {
            case 'high':
                $duration /= 3;
                break;
            case 'low':
                $duration *= 2;
                break;
        }

        return (int) $duration;
    }

    /**
     * Intelligent cache warming based on usage patterns
     */
    protected function warmIntelligentCache(): void
    {
        $priorities = $this->getWarmingPriorities();
        
        foreach ($priorities as $cacheType => $priority) {
            if ($priority >= 7) { // High priority items
                $this->warmCacheType($cacheType);
            }
        }
    }

    /**
     * Get cache warming priorities based on recent usage
     */
    protected function getWarmingPriorities(): array
    {
        $priorities = $this->cacheWarmingPriorities;
        
        // Adjust based on current page state
        if (!empty($this->searchKey)) {
            $priorities['query_results'] += 3;
            $priorities['filters'] += 2;
        }

        if (!empty($this->activeFilters)) {
            $priorities['distinct_values'] += 2;
            $priorities['aggregations'] += 1;
        }

        if ($this->hasRelationColumns()) {
            $priorities['relation_data'] += 3;
        }

        return $priorities;
    }

    /**
     * Warm specific cache type
     */
    protected function warmCacheType(string $type): void
    {
        try {
            switch ($type) {
                case 'query_results':
                    $this->warmQueryResultsCache();
                    break;
                case 'distinct_values':
                    $this->warmDistinctValuesCache();
                    break;
                case 'aggregations':
                    $this->warmAggregationsCache();
                    break;
                case 'relation_data':
                    $this->warmRelationDataCache();
                    break;
                case 'filters':
                    $this->warmFiltersCache();
                    break;
            }
            
            $this->cacheMetrics['warm_ups']++;
            
        } catch (\Exception $e) {
            \Log::warning("Failed to warm cache for type {$type}: " . $e->getMessage());
        }
    }

    /**
     * Warm query results cache
     */
    protected function warmQueryResultsCache(): void
    {
        $cacheKey = $this->generateIntelligentCacheKey('query_results', [
            'search' => $this->searchKey,
            'filters' => $this->activeFilters,
            'sort' => $this->sortColumn . '_' . $this->sortDirection,
            'page' => 1 // Warm first page
        ]);

        if (!\Cache::has($cacheKey)) {
            $query = $this->buildUnifiedQuery();
            $this->applySorting($query);
            $this->applySearch($query);
            $this->applyFilters($query);
            
            $results = $query->paginate($this->perPage);
            
            $duration = $this->getIntelligentCacheDuration('query_results', [
                'record_count' => $results->total()
            ]);
            
            \Cache::put($cacheKey, $results, $duration);
        }
    }

    /**
     * Warm distinct values cache
     */
    protected function warmDistinctValuesCache(): void
    {
        foreach ($this->columns as $columnKey => $column) {
            if ($this->shouldCacheDistinctValues($column)) {
                $cacheKey = $this->generateIntelligentCacheKey('distinct_values', [
                    'column' => $columnKey
                ]);

                if (!\Cache::has($cacheKey)) {
                    $values = $this->getDistinctValuesForColumn($columnKey);
                    $duration = $this->getIntelligentCacheDuration('distinct_values');
                    \Cache::put($cacheKey, $values, $duration);
                }
            }
        }
    }

    /**
     * Warm aggregations cache
     */
    protected function warmAggregationsCache(): void
    {
        $aggregations = ['count', 'sum', 'avg', 'min', 'max'];
        
        foreach ($this->columns as $columnKey => $column) {
            if ($this->isNumericColumn($column)) {
                foreach ($aggregations as $aggregation) {
                    $cacheKey = $this->generateIntelligentCacheKey('aggregations', [
                        'column' => $columnKey,
                        'type' => $aggregation
                    ]);

                    if (!\Cache::has($cacheKey)) {
                        $result = $this->calculateAggregation($columnKey, $aggregation);
                        $duration = $this->getIntelligentCacheDuration('aggregations');
                        \Cache::put($cacheKey, $result, $duration);
                    }
                }
            }
        }
    }

    /**
     * Warm relation data cache
     */
    protected function warmRelationDataCache(): void
    {
        $relationColumns = $this->relationConfigCache['relation_columns'] ?? [];
        
        foreach ($relationColumns as $relationName => $columns) {
            $cacheKey = $this->generateIntelligentCacheKey('relation_data', [
                'relation' => $relationName,
                'columns' => implode(',', $columns)
            ]);

            if (!\Cache::has($cacheKey)) {
                $data = $this->preloadRelationData($relationName, $columns);
                $duration = $this->getIntelligentCacheDuration('relation_data');
                \Cache::put($cacheKey, $data, $duration);
            }
        }
    }

    /**
     * Warm filters cache
     */
    protected function warmFiltersCache(): void
    {
        foreach ($this->getFilterableColumns() as $columnKey) {
            $cacheKey = $this->generateIntelligentCacheKey('filters', [
                'column' => $columnKey,
                'active_filters' => array_keys($this->activeFilters)
            ]);

            if (!\Cache::has($cacheKey)) {
                $filterData = $this->generateFilterData($columnKey);
                $duration = $this->getIntelligentCacheDuration('filters');
                \Cache::put($cacheKey, $filterData, $duration);
            }
        }
    }

    /**
     * Selective cache invalidation based on data changes
     */
    protected function invalidateIntelligentCache(array $affectedTypes = []): void
    {
        if (empty($affectedTypes)) {
            $affectedTypes = ['query_results', 'distinct_values', 'aggregations', 'relation_data', 'filters'];
        }

        foreach ($affectedTypes as $type) {
            $pattern = 'datatable:' . $this->getCacheKeyPrefix() . ':' . $type . ':*';
            $this->clearCacheByPattern($pattern);
            $this->cacheMetrics['invalidations']++;
        }
    }

    /**
     * Get cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $total = $this->cacheMetrics['hits'] + $this->cacheMetrics['misses'];
        $hitRate = $total > 0 ? ($this->cacheMetrics['hits'] / $total) * 100 : 0;

        return [
            'hit_rate' => round($hitRate, 2),
            'total_requests' => $total,
            'hits' => $this->cacheMetrics['hits'],
            'misses' => $this->cacheMetrics['misses'],
            'invalidations' => $this->cacheMetrics['invalidations'],
            'warm_ups' => $this->cacheMetrics['warm_ups'],
            'cache_size' => $this->estimateCacheSize(),
            'efficiency_score' => $this->calculateCacheEfficiency()
        ];
    }

    /**
     * Calculate cache efficiency score
     */
    protected function calculateCacheEfficiency(): float
    {
        $hitRate = $this->getCacheMetrics()['hit_rate'];
        $warmUpRatio = $this->cacheMetrics['warm_ups'] / max(1, $this->cacheMetrics['misses']);
        $invalidationRatio = $this->cacheMetrics['invalidations'] / max(1, $this->cacheMetrics['hits']);

        // Higher hit rate and warm up ratio = better efficiency
        // Lower invalidation ratio = better efficiency
        $efficiency = ($hitRate + ($warmUpRatio * 10)) - ($invalidationRatio * 20);
        
        return max(0, min(100, $efficiency));
    }

    /**
     * Estimate current cache size
     */
    protected function estimateCacheSize(): string
    {
        // This is an approximation - actual implementation would vary by cache driver
        $estimatedSize = ($this->cacheMetrics['hits'] + $this->cacheMetrics['misses']) * 1024; // Rough estimate
        
        if ($estimatedSize > 1024 * 1024) {
            return round($estimatedSize / (1024 * 1024), 2) . ' MB';
        } elseif ($estimatedSize > 1024) {
            return round($estimatedSize / 1024, 2) . ' KB';
        } else {
            return $estimatedSize . ' B';
        }
    }

    /**
     * Intelligent cache key prefix with user isolation
     */
    protected function getCacheKeyPrefix(): string
    {
        $prefix = get_class($this->model);
        
        // Add user isolation for security
        if (method_exists($this, 'getCurrentUser') && $this->getCurrentUser()) {
            $prefix .= ':user_' . $this->getCurrentUser()->id;
        } else {
            $prefix .= ':guest_' . session()->getId();
        }

        return str_replace('\\', '_', $prefix);
    }

    /**
     * Check if cache should be used for current operation
     */
    protected function shouldUseIntelligentCache(string $type): bool
    {
        // Don't cache during development
        if (app()->environment('local') && config('app.debug')) {
            return false;
        }

        // Don't cache for very small datasets
        if ($this->getEstimatedRecordCount() < 50) {
            return false;
        }

        // Don't cache if memory is too low
        if ($this->isMemoryThresholdExceeded()) {
            return false;
        }

        return true;
    }

    /**
     * Record cache hit
     */
    protected function recordCacheHit(): void
    {
        $this->cacheMetrics['hits']++;
    }

    /**
     * Record cache miss
     */
    protected function recordCacheMiss(): void
    {
        $this->cacheMetrics['misses']++;
    }

    /**
     * Check if column should have distinct values cached
     */
    protected function shouldCacheDistinctValues(array $column): bool
    {
        return isset($column['filterable']) && $column['filterable'] && 
               (!isset($column['relation']) || $this->isLowCardinalityRelation($column['relation']));
    }

    /**
     * Check if relation has low cardinality (good for caching)
     */
    protected function isLowCardinalityRelation(string $relationString): bool
    {
        // For relations, we assume low cardinality if it's a lookup table
        // This is a heuristic - could be improved with actual data analysis
        return true;
    }

    /**
     * Check if column is numeric for aggregation caching
     */
    protected function isNumericColumn(array $column): bool
    {
        return isset($column['type']) && in_array($column['type'], ['integer', 'decimal', 'float', 'double']);
    }

    /**
     * Get filterable columns
     */
    protected function getFilterableColumns(): array
    {
        $filterable = [];
        
        foreach ($this->columns as $key => $column) {
            if (isset($column['filterable']) && $column['filterable']) {
                $filterable[] = $key;
            }
        }
        
        return $filterable;
    }

    /**
     * Check if there are relation columns that need optimization
     */
    protected function hasRelationColumns(): bool
    {
        foreach ($this->columns as $column) {
            if (isset($column['relation'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Clear cache by pattern (implementation depends on cache driver)
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        // This is a simplified implementation
        // Real implementation would depend on the cache driver
        try {
            if (method_exists(\Cache::getStore(), 'flush')) {
                // For drivers that support pattern-based clearing
                \Cache::flush();
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to clear cache pattern {$pattern}: " . $e->getMessage());
        }
    }
}
