<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Log;

trait HasUnifiedOptimization
{
    /**
     * Memory optimization configuration
     */
    protected array $optimizationConfig = [
        'memory_threshold' => 40894464, // 39MB
        'max_batch_size' => 500,
        'enable_collection_optimization' => true,
        'enable_memory_management' => true,
        'enable_relationship_optimization' => true,
    ];

    // =================== MEMORY MANAGEMENT ===================

    /**
     * Get current memory usage
     */
    public function getCurrentMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getMemoryLimit(),
            'percentage' => $this->getMemoryUsagePercentage()
        ];
    }

    /**
     * Get memory limit in bytes
     */
    public function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === -1) {
            return PHP_INT_MAX;
        }

        return $this->convertToBytes($memoryLimit);
    }

    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $value = trim($memoryLimit);
        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        switch ($last) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }

        return $number;
    }

    /**
     * Get memory usage percentage
     */
    public function getMemoryUsagePercentage(): float
    {
        $current = memory_get_usage(true);
        $limit = $this->getMemoryLimit();
        
        if ($limit === PHP_INT_MAX) {
            return 0.0;
        }
        
        return ($current / $limit) * 100;
    }

    /**
     * Check if memory threshold is exceeded
     */
    public function isMemoryThresholdExceeded(): bool
    {
        return memory_get_usage(true) > $this->optimizationConfig['memory_threshold'];
    }

    /**
     * Optimize query for memory usage
     */
    public function optimizeQueryForMemory($query)
    {
        if ($this->isMemoryThresholdExceeded()) {
            // Apply chunking for large datasets
            $query = $this->applyChunking($query);
            
            // Limit select columns
            $query = $this->limitSelectColumns($query);
        }
        
        return $query;
    }

    /**
     * Apply chunking to query
     */
    protected function applyChunking($query)
    {
        // Implementation depends on context - this is a basic approach
        return $query->limit($this->optimizationConfig['max_batch_size']);
    }

    /**
     * Limit select columns for memory optimization
     */
    protected function limitSelectColumns($query)
    {
        $selectColumns = $this->getOptimalSelectColumns();
        
        if (!empty($selectColumns)) {
            return $query->select($selectColumns);
        }
        
        return $query;
    }

    /**
     * Get optimal select columns
     */
    protected function getOptimalSelectColumns(): array
    {
        $columns = [];

        // Always include primary key
        if (is_string($this->model)) {
            $modelInstance = new $this->model;
            $primaryKey = $modelInstance->getKeyName();
            if ($primaryKey) {
                $columns[] = $primaryKey;
            }
        }

        // Include updated_at when present to favor indexed sorting and prevent ambiguity
        if (method_exists($this, 'isValidColumn') && $this->isValidColumn('updated_at')) {
            $columns[] = 'updated_at';
        }

        // Add only valid, non-function, non-relation base table columns that are visible
        foreach ($this->visibleColumns ?? [] as $columnKey => $visible) {
            if (! $visible) {
                continue;
            }

            $config = $this->columns[$columnKey] ?? null;
            if (! is_array($config)) {
                continue;
            }

            // Skip computed/function columns entirely
            if (isset($config['function'])) {
                continue;
            }

            // Skip relation display columns here (handled via eager loading/sorting separately)
            if (isset($config['relation'])) {
                continue;
            }

            // Handle JSON: include base JSON column if it exists
            if (isset($config['json']) && isset($config['key'])) {
                $jsonBase = $config['key'];
                if ($this->isValidColumn($jsonBase)) {
                    $columns[] = $jsonBase;
                }
                continue;
            }

            // Regular columns: include only if truly present on the model's table
            if (isset($config['key']) && method_exists($this, 'isValidColumn') && $this->isValidColumn($config['key'])) {
                $columns[] = $config['key'];
            }
        }

        // Also include columns referenced by actions/raw templates (validated)
        if (method_exists($this, 'getColumnsNeededForActions')) {
            foreach (($this->getColumnsNeededForActions() ?? []) as $col) {
                if ($this->isValidColumn($col)) {
                    $columns[] = $col;
                }
            }
        }

        if (method_exists($this, 'getColumnsNeededForRawTemplates')) {
            foreach (($this->getColumnsNeededForRawTemplates() ?? []) as $col) {
                if ($this->isValidColumn($col)) {
                    $columns[] = $col;
                }
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * Get memory statistics
     */
    public function getMemoryStats(): array
    {
        return [
            'current_usage' => $this->getCurrentMemoryUsage(),
            'threshold' => $this->optimizationConfig['memory_threshold'],
            'threshold_exceeded' => $this->isMemoryThresholdExceeded(),
            'optimization_enabled' => $this->optimizationConfig['enable_memory_management'],
            'batch_size' => $this->optimizationConfig['max_batch_size'],
        ];
    }

    // =================== COLLECTION OPTIMIZATION ===================

    /**
     * Memory-optimized map operation
     */
    public function optimizedMap(array $items, callable $callback): array
    {
        $result = [];
        foreach ($items as $key => $item) {
            $result[$key] = $callback($item, $key);
        }
        return $result;
    }

    /**
     * Memory-optimized filter operation
     */
    public function optimizedFilter(array $items, callable $callback = null): array
    {
        if ($callback === null) {
            return array_filter($items);
        }
        
        $result = [];
        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                $result[$key] = $item;
            }
        }
        return $result;
    }

    /**
     * Memory-optimized mapWithKeys operation
     */
    public function optimizedMapWithKeys(array $items, callable $callback): array
    {
        $result = [];
        foreach ($items as $key => $item) {
            $mapped = $callback($item, $key);
            if (is_array($mapped) && count($mapped) === 1) {
                $mappedKey = array_keys($mapped)[0];
                $result[$mappedKey] = $mapped[$mappedKey];
            }
        }
        return $result;
    }

    /**
     * Memory-optimized pluck operation
     */
    public function optimizedPluck(array $items, string $key, string $keyBy = null): array
    {
        $result = [];
        foreach ($items as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : (is_object($item) ? $item->$key : null);
            
            if ($keyBy) {
                $keyValue = is_array($item) ? ($item[$keyBy] ?? null) : (is_object($item) ? $item->$keyBy : null);
                $result[$keyValue] = $value;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Memory-optimized reduce operation
     */
    public function optimizedReduce(array $items, callable $callback, $initial = null)
    {
        $result = $initial;
        foreach ($items as $key => $item) {
            $result = $callback($result, $item, $key);
        }
        return $result;
    }

    /**
     * Memory-optimized first operation
     */
    public function optimizedFirst(array $items, callable $callback = null)
    {
        if ($callback === null) {
            return reset($items) ?: null;
        }
        
        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }
        
        return null;
    }

    /**
     * Memory-optimized count operation
     */
    public function optimizedCount(array $items, callable $callback = null): int
    {
        if ($callback === null) {
            return count($items);
        }
        
        $count = 0;
        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Memory-optimized sum operation
     */
    public function optimizedSum(array $items, string $key = null)
    {
        $sum = 0;
        foreach ($items as $item) {
            if ($key) {
                $value = is_array($item) ? ($item[$key] ?? 0) : (is_object($item) ? ($item->$key ?? 0) : 0);
            } else {
                $value = is_numeric($item) ? $item : 0;
            }
            $sum += $value;
        }
        return $sum;
    }

    /**
     * Memory-optimized sortBy operation
     */
    public function optimizedSortBy(array $items, string $key, bool $descending = false): array
    {
        uasort($items, function($a, $b) use ($key, $descending) {
            $valueA = is_array($a) ? ($a[$key] ?? null) : (is_object($a) ? ($a->$key ?? null) : null);
            $valueB = is_array($b) ? ($b[$key] ?? null) : (is_object($b) ? ($b->$key ?? null) : null);
            
            $comparison = $valueA <=> $valueB;
            return $descending ? -$comparison : $comparison;
        });
        
        return $items;
    }

    /**
     * Memory-optimized groupBy operation
     */
    public function optimizedGroupBy(array $items, string $key): array
    {
        $groups = [];
        foreach ($items as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? 'default') : (is_object($item) ? ($item->$key ?? 'default') : 'default');
            $groups[$groupKey][] = $item;
        }
        return $groups;
    }

    /**
     * Process items in chunks to manage memory
     */
    public function chunkProcess(array $items, int $chunkSize, callable $callback): array
    {
        $result = [];
        $chunks = array_chunk($items, $chunkSize);
        
        foreach ($chunks as $chunk) {
            $chunkResult = $callback($chunk);
            if (is_array($chunkResult)) {
                $result = array_merge($result, $chunkResult);
            }
            
            // Trigger garbage collection for large chunks
            if ($chunkSize > 100) {
                gc_collect_cycles();
            }
        }
        
        return $result;
    }

    // =================== RELATIONSHIP OPTIMIZATION ===================

    /**
     * Optimize eager loading for relationships
     */
    public function optimizeEagerLoading(array $relations = []): array
    {
        if (empty($relations)) {
            // Get relations from columns configuration if not provided
            $relations = $this->getOptimizedWith();
        }
        
        return $this->optimizedFilter($relations, function($relation) {
            return !empty($relation) && is_string($relation);
        });
    }

    /**
     * Apply optimized eager loading to query
     * Alias for optimizeEagerLoading with query application
     */
    public function applyOptimizedEagerLoading($query)
    {
        // Get the relations that need to be eager loaded
        $relations = $this->getOptimizedWith();
        
        if (!empty($relations)) {
            // Optimize the relations array
            $optimizedRelations = $this->optimizeEagerLoading($relations);
            
            if (!empty($optimizedRelations)) {
                $query->with($optimizedRelations);
            }
        }
        
        return $query;
    }

    /**
     * Batch load relations efficiently
     */
    public function batchLoadRelations($query, array $relations): void
    {
        if (!empty($relations)) {
            $optimizedRelations = $this->optimizeEagerLoading($relations);
            if (!empty($optimizedRelations)) {
                $query->with($optimizedRelations);
            }
        }
    }

    /**
     * Get optimized with clauses
     */
    public function getOptimizedWith(): array
    {
        if (!isset($this->columns)) {
            return [];
        }
        
        $relations = [];
        foreach ($this->columns as $column) {
            if (isset($column['relation'])) {
                $relationParts = explode(':', $column['relation']);
                $relations[] = $relationParts[0];
            }
        }
        
        return array_unique($relations);
    }

    /**
     * Analyze relationship depth for optimization
     */
    public function analyzeRelationshipDepth(): int
    {
        $maxDepth = 0;
        
        foreach ($this->columns ?? [] as $column) {
            if (isset($column['relation'])) {
                $depth = substr_count($column['relation'], '.');
                $maxDepth = max($maxDepth, $depth);
            }
        }
        
        return $maxDepth;
    }

    /**
     * Get full relationship analysis
     */
    public function getRelationshipAnalysis(): array
    {
        $analysis = [
            'max_depth' => 0,
            'relations' => [],
            'complexity_score' => 0
        ];
        
        foreach ($this->columns ?? [] as $column) {
            if (isset($column['relation'])) {
                $depth = substr_count($column['relation'], '.');
                $analysis['max_depth'] = max($analysis['max_depth'], $depth);
                $analysis['relations'][] = [
                    'relation' => $column['relation'],
                    'depth' => $depth
                ];
            }
        }
        
        $analysis['complexity_score'] = count($analysis['relations']) * ($analysis['max_depth'] + 1);
        
        return $analysis;
    }

    /**
     * Cache relation parsing for performance
     */
    public function cacheRelationParsing(string $relation): array
    {
        static $cache = [];
        
        if (isset($cache[$relation])) {
            return $cache[$relation];
        }
        
        $parts = explode(':', $relation);
        $parsed = [
            'path' => $parts[0],
            'attribute' => $parts[1] ?? 'id',
            'nested' => strpos($parts[0], '.') !== false
        ];
        
        $cache[$relation] = $parsed;
        return $parsed;
    }

    /**
     * Get relation cache key
     */
    public function getRelationCacheKey(string $relation): string
    {
        return "relation_cache_{$this->tableId}_" . md5($relation);
    }

    /**
     * Parse optimized relation
     */
    public function parseOptimizedRelation(string $relation): array
    {
        return $this->cacheRelationParsing($relation);
    }

    /**
     * Selective column loading for relations
     */
    public function selectiveColumnLoading(array $relations): array
    {
        return $this->optimizedMap($relations, function($relation) {
            $parsed = $this->parseOptimizedRelation($relation);
            return $parsed['path'] . ':id,' . $parsed['attribute'];
        });
    }

    /**
     * Preload critical relations
     */
    public function preloadCriticalRelations($query): void
    {
        $criticalRelations = $this->getCriticalRelations();
        if (!empty($criticalRelations)) {
            $query->with($criticalRelations);
        }
    }

    /**
     * Get critical relations that should be preloaded
     */
    protected function getCriticalRelations(): array
    {
        // Relations used in visible columns
        $critical = [];
        foreach ($this->visibleColumns ?? [] as $columnKey => $visible) {
            if ($visible && isset($this->columns[$columnKey]['relation'])) {
                $parsed = $this->parseOptimizedRelation($this->columns[$columnKey]['relation']);
                $critical[] = $parsed['path'];
            }
        }
        
        return array_unique($critical);
    }

    /**
     * Get relationship statistics
     */
    public function getRelationshipStats(): array
    {
        $analysis = $this->getRelationshipAnalysis();
        
        return [
            'total_relations' => count($analysis['relations']),
            'max_depth' => $analysis['max_depth'],
            'complexity_score' => $analysis['complexity_score'],
            'critical_relations' => $this->getCriticalRelations(),
            'optimization_enabled' => $this->optimizationConfig['enable_relationship_optimization'],
        ];
    }

    // =================== QUERY OPTIMIZATION ===================

    /**
     * Get query method for compatibility
     */
    public function getQuery()
    {
        return $this->buildUnifiedQuery();
    }

    /**
     * Apply loading strategy based on data size
     */
    public function applyLoadingStrategy($query)
    {
        $count = $query->count();
        
        if ($count > 1000) {
            // Use chunking for large datasets
            return $query->limit($this->optimizationConfig['max_batch_size']);
        }
        
        return $query;
    }

    /**
     * Lazy load query for memory efficiency
     */
    public function lazyLoadQuery($query)
    {
        return $query->lazy($this->optimizationConfig['max_batch_size']);
    }

    /**
     * Optimize select columns
     */
    public function optimizeSelectColumns($query)
    {
        $optimizedColumns = $this->getOptimalSelectColumns();
        
        if (!empty($optimizedColumns)) {
            return $query->select($optimizedColumns);
        }
        
        return $query;
    }

    /**
     * Get optimized select columns
     * Alias for getOptimalSelectColumns for consistency
     */
    public function getOptimizedSelectColumns(): array
    {
        return $this->getOptimalSelectColumns();
    }

    /**
     * Trigger garbage collection
     */
    public function triggerGarbageCollection(): void
    {
        if ($this->isMemoryThresholdExceeded()) {
            gc_collect_cycles();
        }
    }

    // =================== CONFIGURATION ===================

    /**
     * Configure optimization settings
     */
    public function configureOptimization(array $config): void
    {
        $this->optimizationConfig = array_merge($this->optimizationConfig, $config);
    }

    /**
     * Set memory threshold
     */
    public function setMemoryThreshold(int $threshold): void
    {
        $this->optimizationConfig['memory_threshold'] = $threshold;
    }

    /**
     * Set max batch size
     */
    public function setMaxBatchSize(int $size): void
    {
        $this->optimizationConfig['max_batch_size'] = $size;
    }

    // =================== COLUMN OPTIMIZATION ===================

    /**
     * Initialize columns with memory optimization
     * Replaces regular column initialization with memory-efficient approach
     */
    public function initializeColumnsOptimized(array $columns): array
    {
        $optimizedColumns = [];
        
        // Use direct array operations instead of Collection for memory efficiency
        foreach ($columns as $index => $column) {
            if (is_string($column)) {
                // Simple string column
                $key = $column;
                $optimizedColumns[$key] = [
                    'key' => $key,
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'sortable' => true,
                    'searchable' => true,
                ];
            } elseif (is_array($column) && isset($column['key'])) {
                // Array column configuration with 'key'
                $key = $column['key'];
                $optimizedColumns[$key] = array_merge([
                    'key' => $key,
                    'label' => $column['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'sortable' => $column['sortable'] ?? true,
                    'searchable' => $column['searchable'] ?? true,
                ], $column);
            } elseif (is_array($column) && isset($column['function'])) {
                // Function-based column configuration
                $key = $column['function'];
                $optimizedColumns[$key] = array_merge([
                    'function' => $key,
                    'label' => $column['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'sortable' => false, // Function columns are not sortable by default
                    'searchable' => false, // Function columns are not searchable by default
                ], $column);
            } elseif (is_array($column) && isset($column['relation']) && !isset($column['key'])) {
                // Relation-only column configuration (no key specified)
                // Generate meaningful key from relation and label
                $relationParts = explode(':', $column['relation']);
                $relationPath = $relationParts[0] ?? '';
                $relationKey = str_replace('.', '_', $relationPath);
                
                // Use label as key if available, otherwise use relation path
                $key = $column['label'] ?? ucfirst(str_replace(['_', '.'], [' ', ' '], $relationKey));
                $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
                $key = trim($key, '_');
                
                $optimizedColumns[$key] = array_merge([
                    'label' => $column['label'] ?? ucfirst(str_replace(['_', '.'], [' ', ' '], $relationKey)),
                    'sortable' => $column['sortable'] ?? false, // Relation columns are not sortable by default
                    'searchable' => $column['searchable'] ?? false, // Relation columns are not searchable by default
                ], $column);
                // Note: We intentionally do NOT add a 'key' property for relation-only columns
            } elseif (is_array($column) && isset($column['json_column']) && isset($column['json_path'])) {
                // JSON column configuration
                $key = $column['json_column'] . '->' . $column['json_path'];
                $optimizedColumns[$key] = array_merge([
                    'json_column' => $column['json_column'],
                    'json_path' => $column['json_path'],
                    'label' => $column['label'] ?? ucfirst(str_replace(['_', '->'], [' ', ' '], $key)),
                    'sortable' => $column['sortable'] ?? false,
                    'searchable' => $column['searchable'] ?? false,
                ], $column);
            } else {
                // Fallback for other column types - use a meaningful identifier
                if (is_array($column)) {
                    // Try to determine a meaningful key from the column config
                    $key = $column['label'] ?? $column['raw'] ?? "column_{$index}";
                    // Sanitize the key to be valid
                    $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
                    $key = trim($key, '_');
                    if (empty($key) || is_numeric($key)) {
                        $key = "column_{$index}";
                    }
                } else {
                    $key = is_numeric($index) ? "column_{$index}" : $index;
                }
                
                $optimizedColumns[$key] = array_merge([
                    'key' => $key,
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'sortable' => true,
                    'searchable' => true,
                ], is_array($column) ? $column : []);
            }
        }
        
        return $optimizedColumns;
    }

    /**
     * Get default visible columns optimized
     * Memory-efficient alternative to getDefaultVisibleColumns
     */
    public function getDefaultVisibleColumnsOptimized(): array
    {
        $defaultVisibility = [];
        
        // Direct array iteration instead of Collection operations
        foreach ($this->columns as $key => $column) {
            // Default all columns to visible unless explicitly hidden
            $defaultVisibility[$key] = !isset($column['hidden']) || !$column['hidden'];
        }
        
        return $defaultVisibility;
    }
}
