<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCaching
{
    /**
     * Get cached distinct values for a column
     */
    protected function getCachedDistinctValues($columnKey): array
    {
        $cacheKey = "datatable_distinct_{$this->tableId}_{$columnKey}";

        return Cache::remember($cacheKey, $this->distinctValuesCacheTime, function () use ($columnKey) {
            if (isset($this->columns[$columnKey]['relation'])) {
                return $this->getRelationDistinctValues($columnKey);
            } else {
                return $this->getColumnDistinctValues($columnKey);
            }
        });
    }

    /**
     * Get distinct values for relation columns
     */
    protected function getRelationDistinctValues($columnKey): array
    {
        $relationString = $this->columns[$columnKey]['relation'];
        
        if (!$this->validateRelationString($relationString)) {
            return [];
        }

        [$relationName, $relatedColumn] = explode(':', $relationString);

        try {
            $modelInstance = new ($this->model);
            $relationObj = $modelInstance->$relationName();
            $relatedModel = $relationObj->getRelated();
            $relatedTable = $relatedModel->getTable();
            $relatedColumnFull = $relatedTable . '.' . $relatedColumn;

            // Determine join keys
            if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $parentTable = $modelInstance->getTable();
                $foreignKey = $relationObj->getForeignKeyName();
                $ownerKey = $relationObj->getOwnerKeyName();

                return $this->model::query()
                    ->join($relatedTable, $parentTable . '.' . $foreignKey, '=', $relatedTable . '.' . $ownerKey)
                    ->distinct()
                    ->whereNotNull($relatedColumnFull)
                    ->limit($this->maxDistinctValues)
                    ->pluck($relatedColumnFull)
                    ->values()
                    ->toArray();
            } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
                      $relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                $parentTable = $modelInstance->getTable();
                $foreignKey = $relationObj->getForeignKeyName();
                $localKey = $relationObj->getLocalKeyName();

                return $this->model::query()
                    ->join($relatedTable, $parentTable . '.' . $localKey, '=', $relatedTable . '.' . $foreignKey)
                    ->distinct()
                    ->whereNotNull($relatedColumnFull)
                    ->limit($this->maxDistinctValues)
                    ->pluck($relatedColumnFull)
                    ->values()
                    ->toArray();
            } else {
                // Fallback: try to join on guessed keys
                return [];
            }
        } catch (\Exception $e) {
            \Log::warning('Relation distinct values failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get distinct values for regular columns
     */
    protected function getColumnDistinctValues($columnKey): array
    {
        try {
            return $this->model::query()
                ->select($columnKey)
                ->distinct()
                ->whereNotNull($columnKey)
                ->limit($this->maxDistinctValues)
                ->pluck($columnKey)
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            \Log::warning('Column distinct values failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get distinct values with sorting
     */
    public function getDistinctValues($columnKey)
    {
        // For relation, get distinct from related table, else from main table
        $values = isset($this->columns[$columnKey]['relation'])
            ? $this->getRelationDistinctValues($columnKey)
            : $this->getColumnDistinctValues($columnKey);

        // Sort values alphabetically (case-insensitive)
        if (is_array($values)) {
            natcasesort($values);
            $values = array_values($values);
        }
        
        return $values;
    }

    /**
     * Clear distinct values cache
     */
    public function clearDistinctValuesCache()
    {
        $cachePattern = "datatable_distinct_{$this->tableId}_*";
        // Clear related cache entries
        $this->clearCacheByPattern($cachePattern);
    }

    /**
     * Clear cache by pattern
     */
    protected function clearCacheByPattern($pattern)
    {
        try {
            // For file cache driver
            if (config('cache.default') === 'file') {
                $files = glob(storage_path('framework/cache/data/*'));
                foreach ($files as $file) {
                    $key = basename($file, '.cache');
                    if (fnmatch($pattern, $key)) {
                        unlink($file);
                    }
                }
            } else {
                // For other cache drivers, you might need specific implementation
                Cache::flush(); // Fallback - clears all cache
            }
        } catch (\Exception $e) {
            \Log::warning('Cache clearing failed: ' . $e->getMessage());
        }
    }

    /**
     * Cache table statistics
     */
    protected function getCachedTableStats(): array
    {
        $cacheKey = "datatable_stats_{$this->tableId}";

        return Cache::remember($cacheKey, 300, function () { // 5 minutes cache
            return [
                'total_records' => $this->model::count(),
                'columns_count' => count($this->columns),
                'visible_columns' => count(array_filter($this->visibleColumns)),
                'cached_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Cache column metadata
     */
    protected function getCachedColumnMetadata(): array
    {
        $cacheKey = "datatable_metadata_{$this->tableId}";

        return Cache::remember($cacheKey, 3600, function () { // 1 hour cache
            $metadata = [];
            
            foreach ($this->columns as $columnKey => $column) {
                $metadata[$columnKey] = [
                    'type' => $this->getColumnType($column),
                    'sortable' => $this->isColumnSortable($column),
                    'searchable' => $this->isColumnSearchable($column),
                    'exportable' => $this->isColumnExportable($column),
                    'has_relation' => isset($column['relation']),
                    'has_json' => isset($column['json']),
                    'is_function' => isset($column['function'])
                ];
            }

            return $metadata;
        });
    }

    /**
     * Get column type for metadata
     */
    protected function getColumnType($column): string
    {
        if (isset($column['function'])) {
            return 'function';
        }
        
        if (isset($column['relation'])) {
            return 'relation';
        }
        
        if (isset($column['json'])) {
            return 'json';
        }

        // Try to determine from database
        if (isset($column['key'])) {
            try {
                $modelInstance = new ($this->model);
                $schema = $modelInstance->getConnection()->getSchemaBuilder();
                $columnType = $schema->getColumnType($modelInstance->getTable(), $column['key']);
                return $columnType;
            } catch (\Exception $e) {
                return 'unknown';
            }
        }

        return 'unknown';
    }

    /**
     * Warm up cache for common operations
     */
    public function warmUpCache()
    {
        // Cache table stats
        $this->getCachedTableStats();
        
        // Cache column metadata
        $this->getCachedColumnMetadata();
        
        // Cache distinct values for filterable columns
        foreach ($this->filters as $columnKey => $filterConfig) {
            if (in_array($filterConfig['type'], ['select', 'distinct'])) {
                $this->getCachedDistinctValues($columnKey);
            }
        }
    }

    /**
     * Clear all caches for this table
     */
    public function clearAllCaches()
    {
        $patterns = [
            "datatable_distinct_{$this->tableId}_*",
            "datatable_stats_{$this->tableId}",
            "datatable_metadata_{$this->tableId}"
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $distinctCaches = 0;
        $totalCacheSize = 0;

        foreach ($this->columns as $columnKey => $column) {
            $cacheKey = "datatable_distinct_{$this->tableId}_{$columnKey}";
            if (Cache::has($cacheKey)) {
                $distinctCaches++;
                // Estimate cache size (not exact, but gives an idea)
                $cachedData = Cache::get($cacheKey);
                $totalCacheSize += is_array($cachedData) ? count($cachedData) : 1;
            }
        }

        return [
            'distinct_caches' => $distinctCaches,
            'estimated_items' => $totalCacheSize,
            'cache_driver' => config('cache.default'),
            'cache_ttl' => $this->distinctValuesCacheTime
        ];
    }
}
