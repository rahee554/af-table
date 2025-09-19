<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Log;

trait HasOptimizedMemory
{
    /**
     * Optimized memory threshold (reduced to 39MB target)
     */
    protected $optimizedMemoryThreshold = 40894464; // 39MB

    /**
     * Memory-optimized column initialization
     * Replaces the memory-intensive collect()->mapWithKeys()->toArray() pattern
     */
    protected function initializeColumnsOptimized(array $columns): array
    {
        $optimizedColumns = [];
        $index = 0;
        
        // Use direct array operations instead of Collection methods
        foreach ($columns as $key => $column) {
            // Determine identifier without creating closures
            if (isset($column['key'])) {
                $identifier = $column['key'];
            } elseif (isset($column['function'])) {
                $identifier = $column['function'];
            } elseif (isset($column['json_column']) && isset($column['json_path'])) {
                $identifier = $column['json_column'] . '->' . $column['json_path'];
            } else {
                $identifier = is_numeric($key) ? "column_{$index}" : $key;
            }
            
            $optimizedColumns[$identifier] = $column;
            $index++;
        }
        
        return $optimizedColumns;
    }

    /**
     * Memory-optimized default visible columns generation
     * Avoids Collection overhead for column visibility logic
     */
    protected function getDefaultVisibleColumnsOptimized(): array
    {
        $visibleColumns = [];
        
        // Direct iteration instead of Collection methods
        foreach ($this->columns as $identifier => $column) {
            $isVisible = !isset($column['hide']) || !$column['hide'];
            $visibleColumns[$identifier] = $isVisible;
        }
        
        return $visibleColumns;
    }

    /**
     * Memory-optimized relation calculation
     * Replaces array_merge operations with direct array operations
     */
    protected function calculateRelationsOptimized(array $columns): array
    {
        $relations = [];
        
        foreach ($columns as $column) {
            if (isset($column['relation'])) {
                $relation = $column['relation'];
                
                // Extract relation parts efficiently
                if (strpos($relation, '.') !== false) {
                    $parts = explode('.', $relation);
                    $currentRelation = '';
                    
                    for ($i = 0; $i < count($parts); $i++) {
                        $currentRelation .= ($i > 0 ? '.' : '') . $parts[$i];
                        if (!isset($relations[$currentRelation])) {
                            $relations[$currentRelation] = true;
                        }
                    }
                } else {
                    $relations[$relation] = true;
                }
            }
            
            // Handle raw templates with relation dependencies
            if (isset($column['raw_template'])) {
                $templateRelations = $this->extractTemplateRelationsOptimized($column['raw_template']);
                foreach ($templateRelations as $rel) {
                    $relations[$rel] = true;
                }
            }
        }
        
        return array_keys($relations);
    }

    /**
     * Memory-optimized template relation extraction
     */
    protected function extractTemplateRelationsOptimized(string $template): array
    {
        $relations = [];
        
        // Use preg_match_all more efficiently
        if (preg_match_all('/\{(\w+(?:\.\w+)*)\}/', $template, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $placeholder = $match[1];
                
                if (strpos($placeholder, '.') !== false) {
                    $parts = explode('.', $placeholder);
                    
                    // Build nested relations efficiently
                    for ($i = 0; $i < count($parts) - 1; $i++) {
                        $relation = implode('.', array_slice($parts, 0, $i + 1));
                        $relations[$relation] = true;
                    }
                }
            }
        }
        
        return array_keys($relations);
    }

    /**
     * Memory-optimized select column calculation
     */
    protected function calculateSelectColumnsOptimized(array $columns): array
    {
        $selectColumns = [];
        $primaryKey = null;
        
        // Get primary key once
        if (is_string($this->model)) {
            $modelInstance = new $this->model;
            $primaryKey = $modelInstance->getKeyName();
        } elseif (is_object($this->model) && method_exists($this->model, 'getKeyName')) {
            $primaryKey = $this->model->getKeyName();
        }
        
        // Always include primary key
        if ($primaryKey && !in_array($primaryKey, $selectColumns)) {
            $selectColumns[] = $primaryKey;
        }
        
        // Add visible columns efficiently
        foreach ($columns as $column) {
            if (isset($column['key']) && !isset($column['relation']) && !isset($column['function'])) {
                $key = $column['key'];
                if (!in_array($key, $selectColumns)) {
                    $selectColumns[] = $key;
                }
            }
            
            // Handle JSON columns
            if (isset($column['json_column'])) {
                $jsonColumn = $column['json_column'];
                if (!in_array($jsonColumn, $selectColumns)) {
                    $selectColumns[] = $jsonColumn;
                }
            }
        }
        
        // Add sort column if needed
        if (!empty($this->sortColumn) && !in_array($this->sortColumn, $selectColumns)) {
            $selectColumns[] = $this->sortColumn;
        }
        
        // Add filter columns
        if (!empty($this->filterColumn) && !in_array($this->filterColumn, $selectColumns)) {
            $selectColumns[] = $this->filterColumn;
        }
        
        return $selectColumns;
    }

    /**
     * Memory-optimized query builder with lazy loading
     */
    protected function buildQueryOptimized()
    {
        // Start with optimized memory usage
        $this->clearMemoryCache();
        
        $query = $this->getBaseQueryOptimized();
        
        // Apply optimized select
        $selectColumns = $this->getCachedSelectColumns();
        if (!empty($selectColumns)) {
            $query->select($selectColumns);
        }
        
        // Apply optimized eager loading
        $relations = $this->getCachedRelations();
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        // Apply search and filters with memory optimization
        $this->applyOptimizedSearch($query);
        $this->applyOptimizedFilters($query);
        $this->applyOptimizedSorting($query);
        
        return $query;
    }

    /**
     * Get base query with memory optimization
     */
    protected function getBaseQueryOptimized()
    {
        if ($this->query) {
            return clone $this->query;
        }
        
        if (is_string($this->model)) {
            return (new $this->model)->newQuery();
        }
        
        if (is_object($this->model) && method_exists($this->model, 'newQuery')) {
            return $this->model->newQuery();
        }
        
        throw new \Exception('Invalid model provided for datatable');
    }

    /**
     * Memory-optimized search application
     */
    protected function applyOptimizedSearch($query): void
    {
        if (empty($this->search) || strlen(trim($this->search)) < 3) {
            return;
        }
        
        $searchTerm = $this->sanitizeSearch($this->search);
        $searchableColumns = $this->getSearchableColumnsOptimized();
        
        if (empty($searchableColumns)) {
            return;
        }
        
        $query->where(function ($subQuery) use ($searchTerm, $searchableColumns) {
            $isFirst = true;
            
            foreach ($searchableColumns as $column) {
                if (strpos($column, '.') !== false) {
                    // Handle relationship search efficiently
                    $this->applyRelationSearchOptimized($subQuery, $column, $searchTerm, $isFirst);
                } else {
                    // Direct column search
                    if ($isFirst) {
                        $subQuery->where($column, 'LIKE', "%{$searchTerm}%");
                        $isFirst = false;
                    } else {
                        $subQuery->orWhere($column, 'LIKE', "%{$searchTerm}%");
                    }
                }
            }
        });
    }

    /**
     * Get searchable columns with memory optimization
     */
    protected function getSearchableColumnsOptimized(): array
    {
        static $cachedSearchableColumns = null;
        
        if ($cachedSearchableColumns !== null) {
            return $cachedSearchableColumns;
        }
        
        $searchableColumns = [];
        
        foreach ($this->columns as $column) {
            if ($this->isColumnSearchableOptimized($column)) {
                if (isset($column['key'])) {
                    $searchableColumns[] = $column['key'];
                } elseif (isset($column['relation']) && isset($column['attribute'])) {
                    $searchableColumns[] = $column['relation'] . '.' . $column['attribute'];
                }
            }
        }
        
        $cachedSearchableColumns = $searchableColumns;
        return $searchableColumns;
    }

    /**
     * Check if column is searchable with optimized logic
     */
    protected function isColumnSearchableOptimized(array $column): bool
    {
        // Quick checks first
        if (isset($column['searchable']) && !$column['searchable']) {
            return false;
        }
        
        if (isset($column['function']) || isset($column['raw_template'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Apply optimized relation search
     */
    protected function applyRelationSearchOptimized($query, string $relationColumn, string $searchTerm, bool $isFirst): void
    {
        $parts = explode('.', $relationColumn);
        $relation = $parts[0];
        $attribute = $parts[1] ?? 'id';
        
        $method = $isFirst ? 'whereHas' : 'orWhereHas';
        
        $query->$method($relation, function ($relationQuery) use ($attribute, $searchTerm) {
            $relationQuery->where($attribute, 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Apply optimized filters
     */
    protected function applyOptimizedFilters($query): void
    {
        // Single filter optimization
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            $this->applyDirectFilter($query, $this->filterColumn, $this->filterValue, $this->filterOperator);
        }
        
        // Additional filters
        if (!empty($this->filters)) {
            foreach ($this->filters as $column => $config) {
                if (isset($config['value']) && $config['value'] !== null && $config['value'] !== '') {
                    $operator = $config['operator'] ?? '=';
                    $this->applyDirectFilter($query, $column, $config['value'], $operator);
                }
            }
        }
    }

    /**
     * Apply single filter with optimization
     */
    protected function applyDirectFilter($query, string $column, $value, string $operator = '='): void
    {
        $filterType = $this->getFilterType($column);
        
        switch ($filterType) {
            case 'date':
                $query->whereDate($column, $operator, $value);
                break;
            case 'number':
            case 'integer':
                $query->where($column, $operator, (float) $value);
                break;
            default:
                if ($operator === '=' && is_string($value)) {
                    $query->where($column, 'LIKE', "%{$value}%");
                } else {
                    $query->where($column, $operator, $value);
                }
        }
    }

    /**
     * Get filter type efficiently
     */
    protected function getFilterType(string $column): string
    {
        if (isset($this->filters[$column]['type'])) {
            return $this->filters[$column]['type'];
        }
        
        return 'text';
    }

    /**
     * Cache management for memory optimization
     */
    protected function getCachedSelectColumns(): array
    {
        if ($this->cachedSelectColumns === null) {
            $this->cachedSelectColumns = $this->calculateSelectColumnsOptimized($this->columns);
        }
        
        return $this->cachedSelectColumns;
    }

    /**
     * Get cached relations
     */
    protected function getCachedRelations(): array
    {
        if ($this->cachedRelations === null) {
            $this->cachedRelations = $this->calculateRelationsOptimized($this->columns);
        }
        
        return $this->cachedRelations;
    }

    /**
     * Clear memory cache when needed
     */
    protected function clearMemoryCache(): void
    {
        // Clear static caches if memory threshold is exceeded
        if ($this->isMemoryThresholdExceeded()) {
            $this->cachedSelectColumns = null;
            $this->cachedRelations = null;
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Check memory threshold with optimized limit
     */
    protected function isMemoryThresholdExceeded(): bool
    {
        return memory_get_usage(true) > $this->optimizedMemoryThreshold;
    }

    /**
     * Get memory optimization statistics
     */
    public function getMemoryOptimizationStats(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'threshold' => $this->optimizedMemoryThreshold,
            'threshold_exceeded' => $this->isMemoryThresholdExceeded(),
            'cached_relations_count' => count($this->getCachedRelations()),
            'cached_select_columns_count' => count($this->getCachedSelectColumns()),
            'memory_percentage' => $this->getMemoryUsagePercentage(),
            'optimization_enabled' => true
        ];
    }

    /**
     * Get memory usage percentage
     */
    protected function getMemoryUsagePercentage(): float
    {
        $current = memory_get_usage(true);
        $limit = $this->getMemoryLimit();

        if ($limit === -1) {
            return 0.0;
        }

        return ($current / $this->convertToBytes($limit)) * 100;
    }

    /**
     * Get memory limit
     */
    protected function getMemoryLimit()
    {
        return ini_get('memory_limit');
    }

    /**
     * Convert memory limit to bytes
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * Sanitize search input
     */
    protected function sanitizeSearch(string $search): string
    {
        return htmlspecialchars(trim($search), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get optimized select columns with relation considerations
     * Works with HasOptimizedRelationships to select only needed columns
     */
    protected function getOptimizedSelectColumns(): array
    {
        $selectColumns = [];
        $relationColumns = $this->relationConfigCache['relation_columns'] ?? [];
        
        // Always include the primary key
        $selectColumns[] = $this->getModelInstance()->getKeyName();
        
        // Add columns needed for current view
        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['key']) && !isset($column['relation'])) {
                // Only add non-relation columns to main select
                if ($this->isValidColumn($column['key'])) {
                    $selectColumns[] = $column['key'];
                }
            }
            
            // Add foreign keys needed for relations
            if (isset($column['relation'])) {
                $relationConfig = $this->parseRelationString($column['relation']);
                if ($relationConfig && $relationConfig['base']) {
                    $foreignKey = $relationConfig['base'] . '_id';
                    if ($this->isValidColumn($foreignKey)) {
                        $selectColumns[] = $foreignKey;
                    }
                }
            }
        }
        
        // Add columns needed for actions
        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                if (isset($action['column_dependencies'])) {
                    foreach ($action['column_dependencies'] as $dependency) {
                        if ($this->isValidColumn($dependency) && !in_array($dependency, $selectColumns)) {
                            $selectColumns[] = $dependency;
                        }
                    }
                }
            }
        }
        
        return array_unique($selectColumns);
    }
}
