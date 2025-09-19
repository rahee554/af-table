<?php

namespace ArtflowStudio\Table\Traits\Core;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait HasQueryBuilder
{
    /**
     * Build the complete unified query (replaces both buildQuery and query methods)
     */
    protected function buildUnifiedQuery(): Builder
    {
        // Start with base model query
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            $this->applyCustomQueryConstraints($query);
        }

        // Apply column optimization for selective loading
        $query = $this->applyColumnOptimization($query);

        // Load relations using cached relations (like in working Datatable.php)
        if (!empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        // Use optimized relation loading with selective column loading
        $query = $this->applyOptimizedEagerLoading($query);

        // Apply optimized column selection for reduced memory usage
        $selectColumns = $this->calculateSelectColumns($this->columns);
        if (!empty($selectColumns)) {
            // Qualify columns with table name to prevent ambiguous column errors
            $tableName = $this->model::make()->getTable();
            $qualifiedColumns = array_map(function ($column) use ($tableName) {
                return $tableName . '.' . $column;
            }, $selectColumns);

            $query->select($qualifiedColumns);
        }

        // Apply search - optimized with minimum character threshold
        if ($this->searchable && $this->search && strlen(trim($this->search)) >= 3) {
            $this->applyOptimizedSearch($query);
        }

        // Apply filters with improved logic
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            // Get filter type for the current filter column
            $filterType = isset($this->filters[$this->filterColumn]['type'])
                ? $this->filters[$this->filterColumn]['type']
                : 'text';

            // For text filters, only apply if 3+ characters
            if ($filterType === 'text') {
                if (strlen(trim($this->filterValue)) >= 3) {
                    $this->applyFilters($query);
                }
            } else {
                $this->applyFilters($query);
            }
        }

        // Apply additional filters from $this->filters array
        if (!empty($this->filters)) {
            $this->applyFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $query->whereBetween($this->dateColumn, [$this->startDate, $this->endDate]);
        }

        // Apply sorting with optimization
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        // Return the built query
        return $query;
    }

    /**
     * Build the complete query (DEPRECATED - use buildUnifiedQuery)
     *
     * @deprecated Use buildUnifiedQuery() instead for better performance
     */
    protected function buildQuery()
    {
        return $this->buildUnifiedQuery();
    }

    /**
     * Get query builder instance (DEPRECATED - use buildUnifiedQuery)
     *
     * @deprecated Use buildUnifiedQuery() instead for better performance
     */
    protected function query(): Builder
    {
        return $this->buildUnifiedQuery();
    }

    /**
     * Apply custom query constraints
     */
    protected function applyCustomQueryConstraints(Builder $query): void
    {
        if (is_array($this->query)) {
            foreach ($this->query as $constraint) {
                if (is_callable($constraint)) {
                    $constraint($query);
                }
            }
        } elseif (is_callable($this->query)) {
            ($this->query)($query);
        }
    }

    /**
     * Get valid select columns based on current visibility
     */
    protected function getValidSelectColumns(): array
    {
        $modelInstance = new ($this->model);
        $parentTable = $modelInstance->getTable();

        $selects = [$parentTable . '.id']; // Always include ID with table qualifier

        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = $parentTable . '.updated_at';
        }

        foreach ($this->columns as $columnKey => $column) {
            // Skip if column is not visible
            if (!($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Add database columns to select
            if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $selects[] = $parentTable . '.' . $column['key'];
            }
        }

        // Add columns needed for actions and raw templates (with validation)
        $actionColumns = $this->getColumnsNeededForActions();
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();

        // Filter out invalid columns
        $validActionColumns = array_filter($actionColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        $validRawTemplateColumns = array_filter($rawTemplateColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        // Ensure action columns are always included, even if not in $this->columns - with table qualifier
        foreach ($validActionColumns as $col) {
            $selects[] = $parentTable . '.' . $col;
        }
        // Same for raw template columns - with table qualifier
        foreach ($validRawTemplateColumns as $col) {
            $selects[] = $parentTable . '.' . $col;
        }

        return array_unique($selects);
    }

    /**
     * Check if column is valid in database
     */
    protected function isValidColumn($column): bool
    {
        try {
            $modelInstance = new ($this->model);
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($modelInstance->getTable());
            return in_array($column, $columns);
        } catch (\Exception $e) {
            Log::warning("Column validation failed for: {$column}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if column is allowed for operations
     */
    protected function isAllowedColumn($column)
    {
        // Allow 'updated_at' for index column sorting
        if ($column === 'updated_at') {
            return true;
        }

        return array_key_exists($column, $this->columns);
    }
}
