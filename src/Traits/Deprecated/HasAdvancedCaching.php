<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasAdvancedCaching
{
    /**
     * Advanced cache duration configuration (memory optimized - lazy initialization)
     */
    protected $advancedCacheConfig = null;

    /**
     * Cache statistics (lazy initialization)
     */
    protected $cacheStats = null;

    /**
     * Get cached query results with intelligent cache strategy
     */
    protected function getCachedQueryResults(Builder $query, array $params = []): mixed
    {
        // Analyze query complexity to determine cache strategy
        $complexity = $this->analyzeQueryComplexity($query);
        $cacheKey = $this->generateQueryCacheKey($query, $params);
        $cacheDuration = $this->determineCacheDuration($complexity, 'query_results');

        return Cache::remember($cacheKey, $cacheDuration, function () use ($query) {
            $this->cacheStats['misses']++;
            return $query->get();
        });
    }

    /**
     * Get cached distinct values with intelligent invalidation
     */
    protected function getCachedDistinctValues(string $columnKey): array
    {
        $cacheKey = $this->generateDistinctValuesCacheKey($columnKey);
        $cacheDuration = $this->advancedCacheConfig['distinct_values'];

        $result = Cache::remember($cacheKey, $cacheDuration, function () use ($columnKey) {
            $this->cacheStats['misses']++;
            
            if (isset($this->columns[$columnKey]['relation'])) {
                return $this->getRelationDistinctValues($columnKey);
            } else {
                return $this->getColumnDistinctValues($columnKey);
            }
        });

        if ($result !== null) {
            $this->cacheStats['hits']++;
        }

        return $result ?: [];
    }

    /**
     * Cache query count results for pagination
     */
    protected function getCachedQueryCount(Builder $query): int
    {
        $cacheKey = $this->generateCountCacheKey($query);
        $cacheDuration = $this->advancedCacheConfig['count_queries'];

        return Cache::remember($cacheKey, $cacheDuration, function () use ($query) {
            $this->cacheStats['misses']++;
            return $query->count();
        });
    }

    /**
     * Cache search results for improved performance
     */
    protected function getCachedSearchResults(string $searchTerm, Builder $query): mixed
    {
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return $query->get();
        }

        $cacheKey = $this->generateSearchCacheKey($searchTerm, $query);
        $cacheDuration = $this->advancedCacheConfig['search_results'];

        return Cache::remember($cacheKey, $cacheDuration, function () use ($query) {
            $this->cacheStats['misses']++;
            return $query->get();
        });
    }

    /**
     * Analyze query complexity to determine optimal cache strategy
     */
    protected function analyzeQueryComplexity(Builder $query): array
    {
        $complexity = [
            'joins' => 0,
            'where_conditions' => 0,
            'subqueries' => 0,
            'relations' => 0,
            'group_by' => 0,
            'order_by' => 0,
            'score' => 0,
        ];

        // Count joins
        $joins = $query->getQuery()->joins ?? [];
        $complexity['joins'] = count($joins);

        // Count where conditions
        $wheres = $query->getQuery()->wheres ?? [];
        $complexity['where_conditions'] = count($wheres);

        // Count group by clauses
        $groups = $query->getQuery()->groups ?? [];
        $complexity['group_by'] = count($groups);

        // Count order by clauses
        $orders = $query->getQuery()->orders ?? [];
        $complexity['order_by'] = count($orders);

        // Count eager loaded relations
        $eagerLoad = $query->getEagerLoads();
        $complexity['relations'] = count($eagerLoad);

        // Calculate complexity score
        $complexity['score'] = ($complexity['joins'] * 3) +
                              ($complexity['where_conditions'] * 1) +
                              ($complexity['subqueries'] * 4) +
                              ($complexity['relations'] * 2) +
                              ($complexity['group_by'] * 2) +
                              ($complexity['order_by'] * 1);

        return $complexity;
    }

    /**
     * Determine cache duration based on query complexity
     */
    protected function determineCacheDuration(array $complexity, string $baseType): int
    {
        $baseDuration = $this->advancedCacheConfig[$baseType];
        $score = $complexity['score'];

        // Higher complexity = longer cache duration (expensive queries benefit more from caching)
        if ($score > 10) {
            return $baseDuration * 2; // Double cache time for complex queries
        } elseif ($score > 5) {
            return intval($baseDuration * 1.5); // 50% longer for moderately complex queries
        }

        return $baseDuration; // Standard duration for simple queries
    }

    /**
     * Generate intelligent cache key for queries
     */
    protected function generateQueryCacheKey(Builder $query, array $params = []): string
    {
        $baseKey = [
            'table_id' => $this->tableId,
            'model' => $this->model,
            'search' => $this->search ?? '',
            'sort_column' => $this->sortColumn ?? '',
            'sort_direction' => $this->sortDirection ?? '',
            'filters' => $this->filters ?? [],
            'per_page' => $this->perPage ?? 10,
            'visible_columns' => array_keys(array_filter($this->visibleColumns ?? [])),
        ];

        // Add custom parameters
        $baseKey = array_merge($baseKey, $params);

        // Add query-specific elements
        $baseKey['query_hash'] = md5($query->toSql() . serialize($query->getBindings()));

        return 'datatable_query_' . md5(serialize($baseKey));
    }

    /**
     * Generate cache key for distinct values
     */
    protected function generateDistinctValuesCacheKey(string $columnKey): string
    {
        return "datatable_distinct_{$this->tableId}_{$columnKey}_" . md5(serialize([
            'model' => $this->model,
            'filters' => $this->filters ?? [],
        ]));
    }

    /**
     * Generate cache key for count queries
     */
    protected function generateCountCacheKey(Builder $query): string
    {
        $baseKey = [
            'table_id' => $this->tableId,
            'model' => $this->model,
            'search' => $this->search ?? '',
            'filters' => $this->filters ?? [],
            'query_hash' => md5($query->toSql() . serialize($query->getBindings())),
        ];

        return 'datatable_count_' . md5(serialize($baseKey));
    }

    /**
     * Generate cache key for search results
     */
    protected function generateSearchCacheKey(string $searchTerm, Builder $query): string
    {
        $baseKey = [
            'table_id' => $this->tableId,
            'model' => $this->model,
            'search' => $searchTerm,
            'searchable_columns' => $this->getSearchableColumnKeys(),
        ];

        return 'datatable_search_' . md5(serialize($baseKey));
    }

    /**
     * Get searchable column keys for cache optimization
     */
    protected function getSearchableColumnKeys(): array
    {
        $searchableColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Only include searchable columns
            if (isset($column['key']) && !isset($column['function'])) {
                $searchableColumns[] = $column['key'];
            }
        }

        return $searchableColumns;
    }

    /**
     * Clear all caches related to this datatable
     */
    protected function clearAllDataTableCaches(): void
    {
        $patterns = [
            "datatable_query_{$this->tableId}_*",
            "datatable_distinct_{$this->tableId}_*",
            "datatable_count_{$this->tableId}_*",
            "datatable_search_{$this->tableId}_*",
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }

        $this->cacheStats['deletes']++;
    }

    /**
     * Clear cache by pattern (implementation depends on cache driver)
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                // For Redis, use pattern-based clearing
                $redis = Cache::getStore()->getRedis();
                $prefix = Cache::getStore()->getPrefix();
                $fullPattern = $prefix . $pattern;
                
                $keys = $redis->keys($fullPattern);
                if (!empty($keys)) {
                    $keysToDelete = array_map(function($key) use ($prefix) {
                        return str_replace($prefix, '', $key);
                    }, $keys);
                    Cache::deleteMultiple($keysToDelete);
                }
            } elseif ($driver === 'file') {
                // For file cache, scan and delete matching files
                $cacheDir = storage_path('framework/cache/data');
                if (is_dir($cacheDir)) {
                    $pattern = str_replace(['*', ':'], ['.*', '\:'], $pattern);
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($cacheDir)
                    );
                    
                    foreach ($iterator as $file) {
                        if ($file->isFile() && preg_match("/{$pattern}/", $file->getFilename())) {
                            unlink($file->getPathname());
                        }
                    }
                }
            } elseif ($driver === 'database') {
                // For database cache, use LIKE query
                $table = config('cache.stores.database.table', 'cache');
                $dbPattern = str_replace('*', '%', $pattern);
                \DB::table($table)->where('key', 'LIKE', $dbPattern)->delete();
            } else {
                // For other drivers (array, null), we can safely flush in testing
                if (app()->environment(['testing', 'local'])) {
                    Cache::flush();
                } else {
                    // In production, log warning and skip aggressive cache clearing
                    \Log::warning("Cache pattern clearing not supported for driver: {$driver}. Skipping cache clear for pattern: {$pattern}");
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Cache clearing failed: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate specific cache entries when data changes
     */
    protected function invalidateRelatedCaches(array $affectedColumns = []): void
    {
        // Clear query caches
        $this->clearCacheByPattern("datatable_query_{$this->tableId}_*");
        $this->clearCacheByPattern("datatable_count_{$this->tableId}_*");

        // Clear distinct value caches for affected columns
        if (!empty($affectedColumns)) {
            foreach ($affectedColumns as $column) {
                $this->clearCacheByPattern("datatable_distinct_{$this->tableId}_{$column}_*");
            }
        } else {
            // Clear all distinct value caches if no specific columns provided
            $this->clearCacheByPattern("datatable_distinct_{$this->tableId}_*");
        }

        $this->cacheStats['deletes']++;
    }

    /**
     * Get cache statistics for monitoring
     */
    protected function getCacheStats(): array
    {
        $hitRate = $this->cacheStats['hits'] + $this->cacheStats['misses'] > 0
            ? ($this->cacheStats['hits'] / ($this->cacheStats['hits'] + $this->cacheStats['misses'])) * 100
            : 0;

        return [
            'hits' => $this->cacheStats['hits'],
            'misses' => $this->cacheStats['misses'],
            'writes' => $this->cacheStats['writes'],
            'deletes' => $this->cacheStats['deletes'],
            'hit_rate_percentage' => round($hitRate, 2),
            'total_operations' => $this->cacheStats['hits'] + $this->cacheStats['misses'],
        ];
    }

    /**
     * Warm up cache with commonly accessed data
     */
    protected function warmUpCache(): void
    {
        // Pre-cache distinct values for filter columns
        foreach ($this->filters ?? [] as $filterKey => $filterConfig) {
            if (isset($this->columns[$filterKey])) {
                $this->getCachedDistinctValues($filterKey);
            }
        }

        // Pre-cache first page of results with default sorting
        if ($this->sortColumn) {
            $query = $this->buildQuery();
            $this->getCachedQueryResults($query->limit($this->perPage ?? 10));
        }

        $this->cacheStats['writes']++;
    }

    /**
     * Check if cache warming is needed
     */
    protected function shouldWarmUpCache(): bool
    {
        $lastWarmUp = Cache::get("datatable_warmup_{$this->tableId}");
        
        // Warm up cache if it hasn't been done in the last hour
        return !$lastWarmUp || (time() - $lastWarmUp) > 3600;
    }

    /**
     * Mark cache as warmed up
     */
    protected function markCacheWarmedUp(): void
    {
        Cache::put("datatable_warmup_{$this->tableId}", time(), 3600);
    }

    /**
     * Get cache configuration with lazy initialization
     */
    protected function getAdvancedCacheConfig(): array
    {
        if ($this->advancedCacheConfig === null) {
            $this->advancedCacheConfig = [
                'query_results' => 300,         // 5 minutes - frequently changing data
                'distinct_values' => 1800,      // 30 minutes - semi-static filter options
                'column_metadata' => 3600,      // 1 hour - structural data
                'relation_data' => 900,         // 15 minutes - relationship data
                'count_queries' => 600,         // 10 minutes - count operations
                'search_results' => 180,        // 3 minutes - search is dynamic
                'export_data' => 120,           // 2 minutes - export preparation
            ];
        }
        return $this->advancedCacheConfig;
    }

    /**
     * Initialize cache statistics (lazy)
     */
    protected function initCacheStats(): void
    {
        if ($this->cacheStats === null) {
            $this->cacheStats = [
                'hits' => 0,
                'misses' => 0,
                'writes' => 0,
                'deletes' => 0,
            ];
        }
    }
}
