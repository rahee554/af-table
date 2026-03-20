<?php

namespace ArtflowStudio\Table\Traits\Advanced;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasQueryOptimization
{
    /**
     * Apply optimized column sorting with index hints
     */
    protected function applyOptimizedColumnSorting(Builder $query, string $column, string $direction): void
    {
        $modelInstance = new ($this->model);
        $table = $modelInstance->getTable();
        
        // Add index hint for better performance if column is indexed
        if ($this->isColumnIndexed($table, $column)) {
            $query->orderBy($column, $direction);
        } else {
            // For non-indexed columns, consider adding a note for DBA
            Log::info("Sorting on non-indexed column: {$table}.{$column}");
            $query->orderBy($column, $direction);
        }
    }

    /**
     * Check if a column is indexed for optimization hints
     */
    protected function isColumnIndexed(string $table, string $column): bool
    {
        try {
            $indexResults = DB::select("SHOW INDEX FROM {$table}");
            $columnNames = [];
            
            foreach ($indexResults as $index) {
                $columnNames[] = strtolower($index->Column_name);
            }

            return in_array(strtolower($column), $columnNames);
        } catch (\Exception $e) {
            return false; // Assume not indexed if we can't check
        }
    }

    /**
     * Apply query hints for performance optimization
     */
    protected function applyQueryHints(Builder $query): void
    {
        $modelInstance = new ($this->model);
        $table = $modelInstance->getTable();

        // Apply appropriate index hints based on query pattern
        if ($this->search && !empty($this->search)) {
            // Hint for search queries
            $query->from(DB::raw("{$table} USE INDEX FOR ORDER BY (PRIMARY)"));
        } elseif ($this->sortColumn) {
            // Hint for sorted queries
            $sortIndex = $this->findBestIndexForSort($table, $this->sortColumn);
            if ($sortIndex) {
                $query->from(DB::raw("{$table} USE INDEX ({$sortIndex})"));
            }
        }
    }

    /**
     * Find the best index for sorting a column
     */
    protected function findBestIndexForSort(string $table, string $column): ?string
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = ?", [$column]);
            
            if (!empty($indexes)) {
                // Prefer unique indexes, then regular indexes - optimized version
                $bestIndex = null;
                $bestScore = 999;
                
                foreach ($indexes as $index) {
                    $score = $index->Non_unique === 0 ? 0 : 1; // Unique indexes first
                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $bestIndex = $index;
                    }
                }

                return $bestIndex->Key_name ?? null;
            }
        } catch (\Exception $e) {
            // Ignore index lookup errors
        }

        return null;
    }

    /**
     * Optimize query based on result size estimation
     */
    protected function optimizeQueryBySize(Builder $query): void
    {
        try {
            // Get estimated row count
            $estimatedRows = $this->getEstimatedRowCount($query);

            if ($estimatedRows > 10000) {
                // For large datasets, use different optimization strategy
                $this->applyLargeDatasetOptimizations($query);
            } elseif ($estimatedRows < 100) {
                // For small datasets, we can be more liberal with JOINs
                $this->applySmallDatasetOptimizations($query);
            }
        } catch (\Exception $e) {
            // Continue without optimization if estimation fails
        }
    }

    /**
     * Get estimated row count for query optimization
     */
    protected function getEstimatedRowCount(Builder $query): int
    {
        try {
            $modelInstance = new ($this->model);
            return $modelInstance->count();
        } catch (\Exception $e) {
            return 1000; // Default assumption
        }
    }

    /**
     * Apply optimizations for large datasets
     */
    protected function applyLargeDatasetOptimizations(Builder $query): void
    {
        // Use LIMIT for sorting optimization
        if ($this->sortColumn && !$this->search) {
            // For pure sorting without search, we can optimize further
            $query->limit(1000); // Reasonable limit for sorting
        }
    }

    /**
     * Apply optimizations for small datasets
     */
    protected function applySmallDatasetOptimizations(Builder $query): void
    {
        // For small datasets, we can afford more complex operations
        // This is where we might allow some JOINs if really needed
    }
}
