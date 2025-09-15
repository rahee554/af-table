<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasQueryOptimization
{
    /**
     * Apply optimized sorting to query using subqueries instead of expensive JOINs
     */
    protected function applyOptimizedSorting(Builder $query): void
    {
        // Find sort column configuration - optimized version
        $sortColumnConfig = null;
        foreach ($this->columns as $col) {
            if (isset($col['key']) && $col['key'] === $this->sortColumn) {
                $sortColumnConfig = $col;
                break;
            }
        }

        if (!$sortColumnConfig) {
            $sortColumnConfig = $this->columns[$this->sortColumn] ?? null;
        }

        $direction = $this->validateSortDirection($this->sortDirection);

        if ($sortColumnConfig && isset($sortColumnConfig['relation'])) {
            $this->applyOptimizedRelationSorting($query, $sortColumnConfig['relation'], $direction);
        } elseif ($sortColumnConfig && isset($sortColumnConfig['key']) && $this->isValidColumn($sortColumnConfig['key'])) {
            $this->applyOptimizedColumnSorting($query, $sortColumnConfig['key'], $direction);
        } elseif ($this->sortColumn === 'updated_at' && $this->isValidColumn('updated_at')) {
            $query->orderBy('updated_at', $direction);
        }
    }

    /**
     * Apply optimized relation sorting using correlated subqueries instead of JOINs
     */
    protected function applyOptimizedRelationSorting(Builder $query, string $relationString, string $direction): void
    {
        try {
            [$relationPath, $attribute] = explode(':', $relationString);
            
            if (str_contains($relationPath, '.')) {
                // Nested relation - use optimized subquery
                $this->applyNestedRelationSubquery($query, $relationPath, $attribute, $direction);
            } else {
                // Simple relation - use optimized single subquery
                $this->applySimpleRelationSubquery($query, $relationPath, $attribute, $direction);
            }
        } catch (\Exception $e) {
            Log::warning("Optimized relation sorting failed for {$relationString}: " . $e->getMessage());
            // Fallback to no sorting rather than expensive JOINs
        }
    }

    /**
     * Apply optimized sorting for simple relations using efficient subqueries
     */
    protected function applySimpleRelationSubquery(Builder $query, string $relationName, string $attribute, string $direction): void
    {
        $modelInstance = new ($this->model);
        $relationObj = $modelInstance->$relationName();
        $relatedModel = $relationObj->getRelated();
        $relatedTable = $relatedModel->getTable();
        $parentTable = $modelInstance->getTable();

        if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $foreignKey = $relationObj->getForeignKeyName();
            $ownerKey = $relationObj->getOwnerKeyName();

            // Use correlated subquery instead of JOIN
            $subquery = DB::table($relatedTable)
                ->select($attribute)
                ->whereColumn("{$relatedTable}.{$ownerKey}", "{$parentTable}.{$foreignKey}")
                ->limit(1);

            $query->orderBy($subquery, $direction);
        }
    }

    /**
     * Apply optimized sorting for nested relations using efficient subqueries
     */
    protected function applyNestedRelationSubquery(Builder $query, string $relationPath, string $attribute, string $direction): void
    {
        $relationParts = explode('.', $relationPath);
        $modelInstance = new ($this->model);
        $parentTable = $modelInstance->getTable();

        // Build nested subquery step by step
        $currentModel = $modelInstance;
        $previousTable = $parentTable;
        $whereConditions = [];

        foreach ($relationParts as $index => $relationName) {
            $relationObj = $currentModel->$relationName();
            $relatedModel = $relationObj->getRelated();
            $relatedTable = $relatedModel->getTable();

            if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $foreignKey = $relationObj->getForeignKeyName();
                $ownerKey = $relationObj->getOwnerKeyName();
                
                if ($index === 0) {
                    // First relation: connect to parent table
                    $whereConditions[] = "{$relatedTable}.{$ownerKey} = {$previousTable}.{$foreignKey}";
                } else {
                    // Subsequent relations: connect to previous relation
                    $whereConditions[] = "{$relatedTable}.{$ownerKey} = {$previousTable}.{$foreignKey}";
                }
            }

            $previousTable = $relatedTable;
            $currentModel = $relatedModel;
        }

        // Build the final optimized subquery
        $finalTable = $previousTable;
        $subqueryBuilder = DB::table($relationParts[0] === 'flight' ? 'flights' : $finalTable);
        
        // Add all the necessary joins for nested relations
        for ($i = 1; $i < count($relationParts); $i++) {
            $currentRelation = $relationParts[$i];
            $tableName = $this->getTableNameForRelation($currentRelation);
            if ($tableName) {
                $subqueryBuilder->join($tableName, function($join) use ($relationParts, $i) {
                    // Dynamic join conditions based on relation
                    if ($relationParts[$i] === 'airline') {
                        $join->on('flights.airline_id', '=', 'airlines.id');
                    } elseif (in_array($relationParts[$i], ['fromAirport', 'toAirport'])) {
                        $column = $relationParts[$i] === 'fromAirport' ? 'from_airport_id' : 'to_airport_id';
                        $join->on("flights.{$column}", '=', 'airports.id');
                    }
                });
            }
        }

        $subqueryBuilder->select($attribute)
            ->whereColumn($this->buildWhereCondition($relationParts, $parentTable))
            ->limit(1);

        $query->orderBy($subqueryBuilder, $direction);
    }

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
     * Get table name for a relation name
     */
    protected function getTableNameForRelation(string $relationName): ?string
    {
        $tableMap = [
            'airline' => 'airlines',
            'fromAirport' => 'airports',
            'toAirport' => 'airports',
            'flight' => 'flights',
            'booking' => 'bookings',
        ];

        return $tableMap[$relationName] ?? null;
    }

    /**
     * Build optimized where condition for nested relations
     */
    protected function buildWhereCondition(array $relationParts, string $parentTable): string
    {
        if (count($relationParts) === 2 && $relationParts[0] === 'flight') {
            return "flights.id = {$parentTable}.flight_id";
        }

        // Default fallback
        return "1 = 1";
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
