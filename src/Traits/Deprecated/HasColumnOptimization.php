<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HasColumnOptimization
{
    /**
     * Column optimization configuration (memory optimized)
     */
    protected $columnOptimizationConfig = [
        'enable_selective_loading' => true,
        'always_include_columns' => ['id'], // Always include these columns
        'exclude_heavy_columns' => ['description', 'content', 'notes', 'body'], // Heavy text columns to exclude by default
        'relation_optimization' => true,
        'lazy_load_relations' => true,
        'cache_column_analysis' => false, // Disabled by default to save memory
    ];

    /**
     * Column analysis cache (lazy initialization)
     */
    protected $columnAnalysisCache = null;

    /**
     * Currently selected columns for optimization (lazy initialization)
     */
    protected $optimizedColumns = null;

    /**
     * Get column optimization configuration value
     */
    protected function getColumnOptimizationConfig(string $key, $default = null)
    {
        return $this->columnOptimizationConfig[$key] ?? $default;
    }

    /**
     * Apply column optimization to query
     */
    protected function applyColumnOptimization(Builder $query): Builder
    {
        if (!$this->getColumnOptimizationConfig('enable_selective_loading', true)) {
            return $query;
        }

        $optimizedColumns = $this->getOptimizedColumns();
        
        if (!empty($optimizedColumns)) {
            // Get table prefix for proper column selection
            $table = $query->getModel()->getTable();
            $prefixedColumns = $this->addTablePrefix($optimizedColumns, $table);
            
            $query->select($prefixedColumns);
        }

        return $query;
    }

    /**
     * Get optimized columns based on current view requirements (with lazy initialization)
     */
    protected function getOptimizedColumns(): array
    {
        if ($this->optimizedColumns !== null) {
            return $this->optimizedColumns;
        }

        $this->optimizedColumns = [];
        
        // Always include required columns
        $alwaysInclude = $this->getColumnOptimizationConfig('always_include_columns', ['id']);
        $this->optimizedColumns = array_merge($this->optimizedColumns, $alwaysInclude);

        // Add visible columns only
        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Skip function-based columns (they don't need database columns)
            if (isset($column['function'])) {
                continue;
            }

            // Skip relation columns for now (will be handled separately)
            if (isset($column['relation'])) {
                continue;
            }

            // Skip heavy columns unless specifically needed
            $columnKey = $column['key'] ?? $columnKey;
            if ($this->isHeavyColumn($columnKey) && !$this->isColumnRequired($columnKey)) {
                continue;
            }

            $columns[] = $columnKey;
        }

        // Add sorting column if not already included
        if ($this->sortColumn && !in_array($this->sortColumn, $columns)) {
            $columns[] = $this->sortColumn;
        }

        // Add filter columns
        $filterColumns = $this->getFilterColumns();
        foreach ($filterColumns as $filterColumn) {
            if (!in_array($filterColumn, $columns)) {
                $columns[] = $filterColumn;
            }
        }

        // Add search columns
        if (!empty($this->search)) {
            $searchColumns = $this->getSearchColumns();
            foreach ($searchColumns as $searchColumn) {
                if (!in_array($searchColumn, $columns)) {
                    $columns[] = $searchColumn;
                }
            }
        }

        // Remove duplicates and cache result
        $this->optimizedColumns = array_unique($columns);
        
        return $this->optimizedColumns;
    }

    /**
     * Check if column is considered heavy (large text fields)
     */
    protected function isHeavyColumn(string $columnKey): bool
    {
        // Check configured heavy columns
        $excludeHeavy = $this->getColumnOptimizationConfig('exclude_heavy_columns', ['description', 'content', 'notes', 'body']);
        foreach ($excludeHeavy as $heavyColumn) {
            if (Str::contains($columnKey, $heavyColumn)) {
                return true;
            }
        }

        // Check column patterns that are typically heavy
        $heavyPatterns = ['_text', '_html', '_content', '_description', '_notes', '_body', '_data'];
        
        foreach ($heavyPatterns as $pattern) {
            if (Str::endsWith($columnKey, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if column is required for current operation
     */
    protected function isColumnRequired(string $columnKey): bool
    {
        // Always required columns
        $alwaysInclude = $this->getColumnOptimizationConfig('always_include_columns', ['id']);
        if (in_array($columnKey, $alwaysInclude)) {
            return true;
        }

        // Required for sorting
        if ($this->sortColumn === $columnKey) {
            return true;
        }

        // Required for filtering
        if (isset($this->filters[$columnKey])) {
            return true;
        }

        // Required for search
        if (!empty($this->search) && $this->isColumnOptimizationSearchable($columnKey)) {
            return true;
        }

        return false;
    }

    /**
     * Get columns used in current filters
     */
    protected function getFilterColumns(): array
    {
        $filterColumns = [];

        foreach ($this->filters ?? [] as $filterKey => $filterValue) {
            if (empty($filterValue)) {
                continue;
            }

            $column = $this->columns[$filterKey] ?? null;
            if (!$column) {
                continue;
            }

            // Skip relation columns
            if (isset($column['relation'])) {
                continue;
            }

            $columnKey = $column['key'] ?? $filterKey;
            $filterColumns[] = $columnKey;
        }

        return $filterColumns;
    }

    /**
     * Get columns used in search
     */
    protected function getSearchColumns(): array
    {
        $searchColumns = [];

        foreach ($this->columns as $columnKey => $column) {
            // Skip function-based columns
            if (isset($column['function'])) {
                continue;
            }

            // Skip relation columns
            if (isset($column['relation'])) {
                continue;
            }

            // Only include searchable columns
            if (!$this->isColumnOptimizationSearchable($columnKey)) {
                continue;
            }

            $columnKey = $column['key'] ?? $columnKey;
            $searchColumns[] = $columnKey;
        }

        return $searchColumns;
    }

    /**
     * Check if column is searchable for optimization purposes
     */
    protected function isColumnOptimizationSearchable(string $columnKey): bool
    {
        $column = $this->columns[$columnKey] ?? null;
        
        if (!$column) {
            return false;
        }

        // Explicitly marked as non-searchable
        if (isset($column['searchable']) && !$column['searchable']) {
            return false;
        }

        // Function-based columns are not directly searchable
        if (isset($column['function'])) {
            return false;
        }

        // Relation columns require special handling
        if (isset($column['relation'])) {
            return false;
        }

        // Exclude certain column types from search
        $nonSearchablePatterns = ['_id', '_at', 'password', 'token', 'hash'];
        $columnKey = $column['key'] ?? $columnKey;
        
        foreach ($nonSearchablePatterns as $pattern) {
            if (Str::endsWith($columnKey, $pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add table prefix to columns
     */
    protected function addTablePrefix(array $columns, string $table): array
    {
        return array_map(function ($column) use ($table) {
            // Don't prefix if already prefixed or if it's a function
            if (Str::contains($column, '.') || Str::contains($column, '(')) {
                return $column;
            }
            
            return "{$table}.{$column}";
        }, $columns);
    }

    /**
     * Optimize relation loading
     */
    protected function optimizeRelationLoading(Builder $query): Builder
    {
        if (!$this->getColumnOptimizationConfig('relation_optimization', true)) {
            return $query;
        }

        $requiredRelations = $this->getRequiredRelations();
        
        if (empty($requiredRelations)) {
            return $query;
        }

        if ($this->getColumnOptimizationConfig('lazy_load_relations', true)) {
            // Use lazy eager loading for better memory management
            $query->with($requiredRelations);
        } else {
            // Load all relations at once
            $query->with(array_keys($requiredRelations));
        }

        return $query;
    }

    /**
     * Get required relations based on visible columns
     */
    protected function getRequiredRelations(): array
    {
        $relations = [];

        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Only process relation columns
            if (!isset($column['relation'])) {
                continue;
            }

            $relation = $column['relation'];
            $relationColumn = $column['relation_column'] ?? 'name';

            // Optimize relation query to select only needed columns
            $relations[$relation] = function ($query) use ($relationColumn) {
                $query->select(['id', $relationColumn]);
            };
        }

        return $relations;
    }

    /**
     * Analyze table structure for optimization
     */
    protected function analyzeTableStructure(): array
    {
        $cacheKey = "table_analysis_{$this->tableId}";
        
        if ($this->getColumnOptimizationConfig('cache_column_analysis', true) && isset($this->columnAnalysisCache[$cacheKey])) {
            return $this->columnAnalysisCache[$cacheKey];
        }

        $analysis = [
            'total_columns' => 0,
            'heavy_columns' => [],
            'indexed_columns' => [],
            'relationship_columns' => [],
            'estimated_row_size' => 0,
        ];

        try {
            // Get table schema information
            $modelClass = $this->model;
            $model = new $modelClass();
            $table = $model->getTable();
            
            // Get column information
            $columns = DB::select("SHOW COLUMNS FROM {$table}");
            $analysis['total_columns'] = count($columns);
            
            foreach ($columns as $column) {
                $columnName = $column->Field;
                
                // Identify heavy columns
                if ($this->isHeavyColumn($columnName)) {
                    $analysis['heavy_columns'][] = $columnName;
                }
                
                // Estimate size based on type
                $analysis['estimated_row_size'] += $this->estimateColumnSize($column->Type);
            }
            
            // Get index information
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if (!in_array($index->Column_name, $analysis['indexed_columns'])) {
                    $analysis['indexed_columns'][] = $index->Column_name;
                }
            }
            
        } catch (\Exception $e) {
            // If analysis fails, provide basic fallback
            $analysis['error'] = $e->getMessage();
        }

        // Cache the analysis
        if ($this->getColumnOptimizationConfig('cache_column_analysis', true)) {
            $this->columnAnalysisCache[$cacheKey] = $analysis;
        }

        return $analysis;
    }

    /**
     * Estimate column size in bytes
     */
    protected function estimateColumnSize(string $columnType): int
    {
        $type = strtolower($columnType);
        
        // Extract numeric values from type
        preg_match('/\((\d+)\)/', $type, $matches);
        $size = isset($matches[1]) ? (int)$matches[1] : 0;
        
        if (Str::contains($type, ['varchar', 'char'])) {
            return $size;
        }
        
        if (Str::contains($type, ['text', 'longtext'])) {
            return 65535; // Average text size
        }
        
        if (Str::contains($type, ['int', 'integer'])) {
            return 4;
        }
        
        if (Str::contains($type, ['bigint'])) {
            return 8;
        }
        
        if (Str::contains($type, ['decimal', 'float', 'double'])) {
            return 8;
        }
        
        if (Str::contains($type, ['datetime', 'timestamp'])) {
            return 8;
        }
        
        if (Str::contains($type, ['date'])) {
            return 3;
        }
        
        return 10; // Default estimate
    }

    /**
     * Get optimization statistics
     */
    public function getColumnOptimizationStats(): array
    {
        $allColumns = $this->getAllAvailableColumns();
        $optimizedColumns = $this->getOptimizedColumns();
        $tableAnalysis = $this->analyzeTableStructure();
        
        return [
            'total_available_columns' => count($allColumns),
            'selected_columns' => count($optimizedColumns),
            'optimization_percentage' => count($allColumns) > 0 ? round((1 - count($optimizedColumns) / count($allColumns)) * 100, 2) : 0,
            'excluded_heavy_columns' => array_intersect($tableAnalysis['heavy_columns'] ?? [], $allColumns),
            'estimated_data_reduction' => $this->estimateDataReduction($allColumns, $optimizedColumns, $tableAnalysis),
            'table_analysis' => $tableAnalysis,
        ];
    }

    /**
     * Get all available columns from model
     */
    protected function getAllAvailableColumns(): array
    {
        try {
            $modelClass = $this->model;
            $model = new $modelClass();
            $table = $model->getTable();
            
            $columns = DB::select("SHOW COLUMNS FROM {$table}");
            return array_map(function ($column) {
                return $column->Field;
            }, $columns);
            
        } catch (\Exception $e) {
            // Fallback to configured columns
            return array_keys($this->columns);
        }
    }

    /**
     * Estimate data reduction percentage
     */
    protected function estimateDataReduction(array $allColumns, array $selectedColumns, array $tableAnalysis): array
    {
        $totalSize = $tableAnalysis['estimated_row_size'] ?? 0;
        $selectedSize = 0;
        
        foreach ($selectedColumns as $column) {
            if (in_array($column, $allColumns)) {
                $selectedSize += $this->estimateColumnSize('varchar(255)'); // Default estimate
            }
        }
        
        $reductionPercentage = $totalSize > 0 ? round((1 - $selectedSize / $totalSize) * 100, 2) : 0;
        
        return [
            'total_estimated_size' => $totalSize,
            'selected_estimated_size' => $selectedSize,
            'reduction_percentage' => $reductionPercentage,
            'excluded_columns' => array_diff($allColumns, $selectedColumns),
        ];
    }

    /**
     * Force include specific columns in optimization
     */
    public function includeColumns(array $columns): void
    {
        $this->optimizedColumns = array_unique(array_merge($this->optimizedColumns, $columns));
    }

    /**
     * Force exclude specific columns from optimization
     */
    public function excludeColumns(array $columns): void
    {
        $this->optimizedColumns = array_diff($this->optimizedColumns, $columns);
    }

    /**
     * Reset column optimization
     */
    public function resetColumnOptimization(): void
    {
        $this->optimizedColumns = [];
        $this->columnAnalysisCache = [];
    }

    /**
     * Enable/disable column optimization
     */
    public function setColumnOptimization(bool $enabled): void
    {
        $this->columnOptimizationConfig['enable_selective_loading'] = $enabled;
        
        if (!$enabled) {
            $this->resetColumnOptimization();
        }
    }
}
