<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasSmartCaching
{
    /**
     * Cache duration for different types of data
     */
    protected $cacheConfig = [
        'query_results' => 300,     // 5 minutes
        'distinct_values' => 1800,  // 30 minutes
        'column_metadata' => 3600,  // 1 hour
        'relation_data' => 900,     // 15 minutes
        'count_queries' => 600,     // 10 minutes
    ];

    /**
     * Get cached query results or execute and cache
     */
    protected function getCachedQueryResults(Builder $query, array $params = []): mixed
    {
        $cacheKey = $this->generateQueryCacheKey($query, $params);
        
        return Cache::remember($cacheKey, $this->cacheConfig['query_results'], function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Get cached distinct values with intelligent invalidation
     */
    protected function getCachedDistinctValues(string $columnKey): array
    {
        $cacheKey = $this->generateDistinctValuesCacheKey($columnKey);

        return Cache::remember($cacheKey, $this->cacheConfig['distinct_values'], function () use ($columnKey) {
            if (isset($this->columns[$columnKey]['relation'])) {
                return $this->getRelationDistinctValues($columnKey);
            } else {
                return $this->getColumnDistinctValues($columnKey);
            }
        });
    }

    /**
     * Get cached column metadata
     */
    protected function getCachedColumnMetadata(): array
    {
        $cacheKey = $this->generateColumnMetadataCacheKey();

        return Cache::remember($cacheKey, $this->cacheConfig['column_metadata'], function () {
            $modelInstance = new ($this->model);
            $table = $modelInstance->getTable();
            
            try {
                $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
                $columnTypes = [];
                
                foreach ($columns as $column) {
                    $columnTypes[$column] = \Illuminate\Support\Facades\Schema::getColumnType($table, $column);
                }
                
                return [
                    'columns' => $columns,
                    'types' => $columnTypes,
                    'table' => $table,
                ];
            } catch (\Exception $e) {
                return ['columns' => [], 'types' => [], 'table' => $table];
            }
        });
    }

    /**
     * Get cached relation metadata
     */
    protected function getCachedRelationMetadata(): array
    {
        $cacheKey = $this->generateRelationMetadataCacheKey();

        return Cache::remember($cacheKey, $this->cacheConfig['relation_data'], function () {
            $relations = [];
            
            foreach ($this->columns as $columnKey => $column) {
                if (isset($column['relation'])) {
                    $relations[$columnKey] = $this->analyzeRelationStructure($column['relation']);
                }
            }
            
            return $relations;
        });
    }

    /**
     * Get cached count for pagination optimization
     */
    protected function getCachedCount(Builder $query): int
    {
        $cacheKey = $this->generateCountCacheKey($query);

        return Cache::remember($cacheKey, $this->cacheConfig['count_queries'], function () use ($query) {
            return $query->count();
        });
    }

    /**
     * Generate cache key for query results
     */
    protected function generateQueryCacheKey(Builder $query, array $params = []): string
    {
        $baseKey = "datatable_query_{$this->tableId}";
        
        $keyParts = [
            $baseKey,
            md5($query->toSql()),
            md5(serialize($query->getBindings())),
            md5(serialize($params)),
            $this->search ?? '',
            $this->sortColumn ?? '',
            $this->sortDirection ?? 'asc',
            serialize($this->filters ?? []),
            $this->perPage ?? 10,
            $this->page ?? 1,
        ];

        return implode('_', array_filter($keyParts));
    }

    /**
     * Generate cache key for distinct values
     */
    protected function generateDistinctValuesCacheKey(string $columnKey): string
    {
        return "datatable_distinct_{$this->tableId}_{$columnKey}_" . md5(serialize($this->filters ?? []));
    }

    /**
     * Generate cache key for column metadata
     */
    protected function generateColumnMetadataCacheKey(): string
    {
        return "datatable_columns_{$this->model}_" . md5(serialize($this->columns));
    }

    /**
     * Generate cache key for relation metadata
     */
    protected function generateRelationMetadataCacheKey(): string
    {
        $relationKeys = [];
        foreach ($this->columns as $key => $column) {
            if (isset($column['relation'])) {
                $relationKeys[] = $key . ':' . $column['relation'];
            }
        }
        
        return "datatable_relations_{$this->model}_" . md5(serialize($relationKeys));
    }

    /**
     * Generate cache key for count queries
     */
    protected function generateCountCacheKey(Builder $query): string
    {
        return "datatable_count_{$this->tableId}_" . md5($query->toSql() . serialize($query->getBindings()));
    }

    /**
     * Analyze relation structure for caching optimization
     */
    protected function analyzeRelationStructure(string $relationString): array
    {
        [$relationPath, $attribute] = explode(':', $relationString);
        
        $structure = [
            'path' => $relationPath,
            'attribute' => $attribute,
            'is_nested' => str_contains($relationPath, '.'),
            'parts' => explode('.', $relationPath),
            'cacheable' => true,
        ];

        // Analyze if this relation is expensive to compute
        if ($structure['is_nested'] && count($structure['parts']) > 2) {
            $structure['cacheable'] = false; // Deep nesting might be too expensive to cache
        }

        return $structure;
    }

    /**
     * Invalidate related caches when data changes
     */
    public function invalidateRelatedCaches(): void
    {
        $patterns = [
            "datatable_query_{$this->tableId}_*",
            "datatable_distinct_{$this->tableId}_*",
            "datatable_count_{$this->tableId}_*",
        ];

        foreach ($patterns as $pattern) {
            $this->invalidateCachePattern($pattern);
        }
    }

    /**
     * Invalidate cache by pattern (depends on cache driver)
     */
    protected function invalidateCachePattern(string $pattern): void
    {
        try {
            // For Redis/Memcached this would use pattern deletion
            // For file cache, we'd need to scan directory
            // For now, we'll use a simple approach
            
            $driver = config('cache.default');
            
            if (in_array($driver, ['redis', 'memcached'])) {
                // Use pattern-based deletion if supported
                Cache::tags([$this->tableId])->flush();
            } else {
                // For other drivers, we'll rely on TTL expiration
                // In production, consider implementing pattern-based cache clearing
            }
        } catch (\Exception $e) {
            // Continue silently if cache invalidation fails
        }
    }

    /**
     * Warm up frequently used caches
     */
    public function warmUpCaches(): void
    {
        try {
            // Warm up column metadata
            $this->getCachedColumnMetadata();
            
            // Warm up relation metadata
            $this->getCachedRelationMetadata();
            
            // Warm up distinct values for filterable columns
            foreach ($this->columns as $columnKey => $column) {
                if (isset($column['filterable']) && $column['filterable']) {
                    $this->getCachedDistinctValues($columnKey);
                }
            }
        } catch (\Exception $e) {
            // Continue silently if cache warming fails
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'query_cache_hits' => 0,
            'query_cache_misses' => 0,
            'distinct_cache_hits' => 0,
            'distinct_cache_misses' => 0,
            'cache_size_estimate' => 0,
        ];

        // Try to get cache statistics if available
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                $redis = Cache::getRedis();
                $info = $redis->info('memory');
                $stats['cache_size_estimate'] = $info['used_memory_human'] ?? 'Unknown';
            }
        } catch (\Exception $e) {
            // Stats not available
        }

        return $stats;
    }

    /**
     * Check if caching is enabled and appropriate
     */
    protected function shouldUseCache(): bool
    {
        // Don't cache in certain conditions
        if (app()->environment('testing')) {
            return false;
        }

        if (config('app.debug') && !config('datatable.cache_in_debug', false)) {
            return false;
        }

        return true;
    }

    /**
     * Get cache configuration
     */
    public function getCacheConfig(): array
    {
        return $this->cacheConfig;
    }

    /**
     * Update cache configuration
     */
    public function setCacheConfig(array $config): void
    {
        $this->cacheConfig = array_merge($this->cacheConfig, $config);
    }

    /**
     * Smart cache selection based on query complexity
     */
    protected function selectOptimalCacheStrategy(Builder $query): string
    {
        $complexity = $this->analyzeQueryComplexity($query);
        
        if ($complexity['score'] > 80) {
            return 'long'; // 1 hour cache for complex queries
        } elseif ($complexity['score'] > 50) {
            return 'medium'; // 15 minutes cache
        } else {
            return 'short'; // 5 minutes cache
        }
    }

    /**
     * Analyze query complexity for cache strategy selection
     */
    protected function analyzeQueryComplexity(Builder $query): array
    {
        $score = 0;
        $factors = [];

        // Check for JOINs
        $joins = $query->getQuery()->joins ?? [];
        if (count($joins) > 0) {
            $score += count($joins) * 20;
            $factors[] = 'joins: ' . count($joins);
        }

        // Check for nested WHERE clauses
        $sql = $query->toSql();
        $nestedWhereCount = substr_count(strtolower($sql), 'where') + substr_count(strtolower($sql), 'and');
        if ($nestedWhereCount > 3) {
            $score += $nestedWhereCount * 5;
            $factors[] = 'where_clauses: ' . $nestedWhereCount;
        }

        // Check for ORDER BY on non-indexed columns
        if ($this->sortColumn && !$this->isColumnIndexed($this->getTableName(), $this->sortColumn)) {
            $score += 30;
            $factors[] = 'non_indexed_sort';
        }

        // Check for LIKE searches
        if ($this->search && strlen($this->search) > 0) {
            $score += 25;
            $factors[] = 'text_search';
        }

        return [
            'score' => $score,
            'factors' => $factors,
            'complexity' => $score > 80 ? 'high' : ($score > 50 ? 'medium' : 'low')
        ];
    }

    /**
     * Get table name for current model
     */
    protected function getTableName(): string
    {
        $modelInstance = new ($this->model);
        return $modelInstance->getTable();
    }
}
