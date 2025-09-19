<?php

namespace ArtflowStudio\Table\Traits;

trait HasOptimizedCollections
{
    /**
     * Optimized alternative to collect()->mapWithKeys()->toArray()
     * Reduces memory usage by avoiding Collection objects
     */
    protected function optimizedMapWithKeys(array $data, callable $callback): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            $mapped = $callback($value, $key);
            if (is_array($mapped) && count($mapped) === 1) {
                $mappedKey = array_keys($mapped)[0];
                $mappedValue = array_values($mapped)[0];
                $result[$mappedKey] = $mappedValue;
            }
        }
        
        return $result;
    }

    /**
     * Optimized alternative to collect()->filter()->toArray()
     * Uses direct array operations for better memory efficiency
     */
    protected function optimizedFilter(array $data, callable $callback = null): array
    {
        if ($callback === null) {
            return array_filter($data);
        }
        
        $result = [];
        
        foreach ($data as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Optimized alternative to collect()->map()->toArray()
     * Avoids Collection overhead for simple transformations
     */
    protected function optimizedMap(array $data, callable $callback): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        
        return $result;
    }

    /**
     * Optimized alternative to collect()->pluck()->toArray()
     * Uses direct array operations for better performance
     */
    protected function optimizedPluck(array $data, string $key): array
    {
        $result = [];
        
        foreach ($data as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $result[] = $item->$key;
            }
        }
        
        return $result;
    }

    /**
     * Optimized alternative to collect()->chunk()
     * Returns a generator instead of loading all chunks into memory
     */
    protected function optimizedChunk(array $data, int $size): \Generator
    {
        $chunks = array_chunk($data, $size, true);
        
        foreach ($chunks as $chunk) {
            yield $chunk;
        }
    }

    /**
     * Memory-efficient array merge for large datasets
     * Prevents memory duplication during merge operations
     */
    protected function optimizedArrayMerge(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            $target[$key] = $value;
        }
    }

    /**
     * Optimized alternative to collect()->unique()->toArray()
     * Uses array_unique with better memory management
     */
    protected function optimizedUnique(array $data, ?string $key = null): array
    {
        if ($key === null) {
            return array_unique($data);
        }
        
        $seen = [];
        $result = [];
        
        foreach ($data as $index => $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : (is_object($item) ? ($item->$key ?? null) : null);
            
            if ($value !== null && !isset($seen[$value])) {
                $seen[$value] = true;
                $result[$index] = $item;
            }
        }
        
        return $result;
    }

    /**
     * Optimized alternative to collect()->flatten()->toArray()
     * Flattens nested arrays with minimal memory overhead
     */
    protected function optimizedFlatten(array $data, int $depth = PHP_INT_MAX): array
    {
        $result = [];
        
        foreach ($data as $item) {
            if (is_array($item) && $depth > 0) {
                $flattened = $this->optimizedFlatten($item, $depth - 1);
                $this->optimizedArrayMerge($result, $flattened);
            } else {
                $result[] = $item;
            }
        }
        
        return $result;
    }

    /**
     * Optimized alternative to collect()->sortBy()->toArray()
     * Uses native PHP sorting with memory optimization
     */
    protected function optimizedSortBy(array $data, string $key, bool $descending = false): array
    {
        usort($data, function ($a, $b) use ($key, $descending) {
            $aValue = is_array($a) ? ($a[$key] ?? null) : (is_object($a) ? ($a->$key ?? null) : null);
            $bValue = is_array($b) ? ($b[$key] ?? null) : (is_object($b) ? ($b->$key ?? null) : null);
            
            if ($aValue == $bValue) {
                return 0;
            }
            
            $result = $aValue < $bValue ? -1 : 1;
            return $descending ? -$result : $result;
        });
        
        return $data;
    }

    /**
     * Optimized alternative to collect()->groupBy()->toArray()
     * Groups data with minimal memory allocation
     */
    protected function optimizedGroupBy(array $data, string $key): array
    {
        $groups = [];
        
        foreach ($data as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? 'null') : (is_object($item) ? ($item->$key ?? 'null') : 'null');
            
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }
            
            $groups[$groupKey][] = $item;
        }
        
        return $groups;
    }

    /**
     * Memory-efficient replacement for collect($columns)->filter(visible)
     * Used specifically for column visibility calculations in views
     */
    protected function getVisibleColumnsCount(array $columns, array $visibleColumns): int
    {
        $count = 0;
        
        foreach ($columns as $key => $column) {
            if (isset($visibleColumns[$key]) && $visibleColumns[$key]) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Memory-efficient data transformation for JSON responses
     * Replaces collect($data)->map() patterns in API responses
     */
    protected function transformDataForJson(array $data, callable $transformer): array
    {
        $transformed = [];
        
        // Process in smaller batches to prevent memory spikes
        $batchSize = 100;
        $batches = array_chunk($data, $batchSize, true);
        
        foreach ($batches as $batch) {
            foreach ($batch as $key => $item) {
                $transformed[$key] = $transformer($item, $key);
            }
            
            // Force garbage collection after each batch if memory is tight
            if ($this->isMemoryThresholdExceeded()) {
                gc_collect_cycles();
            }
        }
        
        return $transformed;
    }

    /**
     * Optimized column configuration processing
     * Replaces memory-intensive Collection operations in column setup
     */
    protected function processColumnsOptimized(array $columns): array
    {
        $processed = [];
        $relations = [];
        $selectColumns = [];
        
        foreach ($columns as $key => $column) {
            // Process column directly without Collection overhead
            $processedColumn = $this->processColumnConfiguration($column);
            $processed[$key] = $processedColumn;
            
            // Extract relations efficiently
            if (isset($column['relation'])) {
                $relations[] = $column['relation'];
            }
            
            // Extract select columns efficiently
            if (isset($column['key']) && !isset($column['relation'])) {
                $selectColumns[] = $column['key'];
            }
        }
        
        return [
            'columns' => $processed,
            'relations' => array_unique($relations),
            'select_columns' => array_unique($selectColumns)
        ];
    }

    /**
     * Process individual column configuration
     */
    protected function processColumnConfiguration(array $column): array
    {
        // Add any default processing here
        if (!isset($column['searchable'])) {
            $column['searchable'] = !isset($column['function']) && !isset($column['raw_template']);
        }
        
        if (!isset($column['sortable'])) {
            $column['sortable'] = isset($column['key']) && !isset($column['relation']);
        }
        
        return $column;
    }

    /**
     * Memory-efficient distinct values extraction
     * Replaces Collection operations in filter value generation
     */
    protected function extractDistinctValuesOptimized($query, string $column): array
    {
        // Use database-level DISTINCT for better performance
        $values = $query->distinct()->pluck($column)->take(1000); // Limit to prevent memory issues
        
        // Convert to array without Collection overhead
        $distinctValues = [];
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                $distinctValues[] = $value;
            }
        }
        
        return $distinctValues;
    }

    /**
     * Check if current operation should use memory-optimized version
     */
    protected function shouldUseOptimizedCollections(): bool
    {
        return $this->isMemoryThresholdExceeded() || count($this->columns) > 20;
    }

    /**
     * Get collection processing statistics
     */
    public function getCollectionOptimizationStats(): array
    {
        return [
            'optimization_enabled' => $this->shouldUseOptimizedCollections(),
            'column_count' => count($this->columns),
            'memory_usage' => memory_get_usage(true),
            'memory_threshold' => $this->optimizedMemoryThreshold ?? 40894464,
            'processing_mode' => $this->shouldUseOptimizedCollections() ? 'optimized' : 'standard'
        ];
    }
}
