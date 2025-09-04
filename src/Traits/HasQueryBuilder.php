<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasQueryBuilder
{
    /**
     * Get the main query for the datatable (alias for query method)
     */
    public function getQuery(): Builder
    {
        return $this->query();
    }

    /**
     * Build the main query for the datatable
     */
    protected function query(): Builder
    {
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            try {
                $this->applyCustomQueryConstraints($query);
            } catch (\Exception $e) {
                // Log error and continue with base query
                logger()->warning('Custom query constraint failed: '.$e->getMessage());
            }
        }

        // Use cached relations for better performance
        if (! empty($this->cachedRelations)) {
            $query->with($this->cachedRelations);
        }

        // Use cached select columns - but recalculate based on current visibility
        $selectColumns = $this->getValidSelectColumns();
        if (! empty($selectColumns)) {
            $query->select($selectColumns);
        }

        // Optimized search with indexed queries
        if ($this->searchable && $this->search) {
            // If a filter column is set, search only that column
            if ($this->filterColumn && $this->isAllowedColumn($this->filterColumn)) {
                $this->applyColumnSearch($query, $this->filterColumn, $this->search);
            } else {
                $this->applyOptimizedSearch($query);
            }
        }

        // Apply filters
        if ($this->filterColumn && $this->filterValue) {
            $this->applyFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $query->whereBetween($this->dateColumn, [$this->startDate, $this->endDate]);
        }

        // Optimized sorting
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        return $query;
    }

    /**
     * Apply custom query constraints
     */
    protected function applyCustomQueryConstraints(Builder $query): void
    {
        if (is_array($this->query)) {
            foreach ($this->query as $column => $value) {
                if ($this->isValidColumn($column)) {
                    $query->where($column, $value);
                }
            }
        }
    }

    /**
     * Get valid select columns for the query
     */
    protected function getValidSelectColumns(): array
    {
        $modelInstance = new ($this->model);
        $parentTable = $modelInstance->getTable();

        $selects = [$parentTable.'.id']; // Always include ID with table qualifier

        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = $parentTable.'.updated_at';
        }

        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns for performance
            $isVisible = $this->visibleColumns[$columnKey] ?? false;
            if (! $isVisible) {
                continue;
            }

            if (isset($column['function'])) {
                continue; // Function columns don't need database columns
            }

            // Handle JSON columns - include the JSON column in SELECT with table qualifier
            if (isset($column['json']) && isset($column['key'])) {
                $qualifiedColumn = $parentTable.'.'.$column['key'];
                if (! in_array($qualifiedColumn, $selects)) {
                    $selects[] = $qualifiedColumn;
                }

                continue;
            }

            if (isset($column['relation'])) {
                [$relationName] = explode(':', $column['relation']);
                $relationParts = explode('.', $relationName);

                // For single-level relations like 'booking:unique_id'
                if (count($relationParts) === 1 && $this->isValidColumn($relationParts[0].'_id')) {
                    $foreignKey = $parentTable.'.'.$relationParts[0].'_id';
                    if (! in_array($foreignKey, $selects)) {
                        $selects[] = $foreignKey;
                    }
                }
                // For nested relations like 'flight.airline:name', we need the base relation foreign key
                elseif (count($relationParts) > 1 && $this->isValidColumn($relationParts[0].'_id')) {
                    $foreignKey = $parentTable.'.'.$relationParts[0].'_id';
                    if (! in_array($foreignKey, $selects)) {
                        $selects[] = $foreignKey;
                    }
                }

                continue;
            }

            if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $qualifiedColumn = $parentTable.'.'.$column['key'];
                if (! in_array($qualifiedColumn, $selects)) {
                    $selects[] = $qualifiedColumn;
                }
            }
        }

        // Add columns needed for actions and raw templates (with validation)
        $actionColumns = $this->getColumnsNeededForActions();
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();

        // Filter out invalid columns and add table qualifiers
        $validActionColumns = array_filter($actionColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        $validRawTemplateColumns = array_filter($rawTemplateColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        // Ensure action columns are always included with table qualifiers
        foreach ($validActionColumns as $col) {
            $qualifiedColumn = $parentTable.'.'.$col;
            if (! in_array($qualifiedColumn, $selects)) {
                $selects[] = $qualifiedColumn;
            }
        }

        // Same for raw template columns
        foreach ($validRawTemplateColumns as $col) {
            $qualifiedColumn = $parentTable.'.'.$col;
            if (! in_array($qualifiedColumn, $selects)) {
                $selects[] = $qualifiedColumn;
            }
        }

        // Check for action columns that are NOT in $this->columns
        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;

            // Ensure template is a string before using preg_match_all
            if (! is_string($template)) {
                continue;
            }

            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
            if (! empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    if ($this->isValidColumn($columnName)) {
                        $qualifiedColumn = $parentTable.'.'.$columnName;
                        if (! in_array($qualifiedColumn, $selects)) {
                            $selects[] = $qualifiedColumn;
                        }
                    }
                }
            }
        }

        return array_unique($selects);
    }

    /**
     * Apply column-specific search
     */
    protected function applyColumnSearch(Builder $query, $columnKey, $search)
    {
        $column = $this->columns[$columnKey] ?? null;
        if (! $column) {
            return;
        }

        $search = $this->sanitizeSearch($search);

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($column, $search) {
            if (isset($column['relation'])) {
                [$relationName, $relatedColumn] = explode(':', $column['relation']);
                $q->whereHas($relationName, function ($relationQuery) use ($relatedColumn, $search) {
                    $relationQuery->where($relatedColumn, 'LIKE', "%{$search}%");
                });
            } elseif (isset($column['key'])) {
                if ($this->isValidColumn($column['key'])) {
                    $q->where($column['key'], 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Get query performance statistics
     */
    public function getQueryStats(): array
    {
        $query = $this->getQuery();

        return [
            'has_query' => ! is_null($query),
            'query_type' => $query ? get_class($query) : null,
            'has_search' => ! empty($this->search),
            'has_filters' => ! empty($this->filters),
            'has_sorting' => ! empty($this->sortColumn),
            'sort_column' => $this->sortColumn,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
            'search_length' => strlen($this->search ?? ''),
            'filter_count' => count($this->filters ?? []),
            'has_custom_query' => ! is_null($this->query),
            'timestamp' => now()->toISOString(),
        ];
    }
}
