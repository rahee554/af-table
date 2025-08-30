<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasFiltering
{
    /**
     * Update selected column for filtering
     */
    public function updatedSelectedColumn($column)
    {
        if (!$this->isAllowedColumn($column)) {
            return;
        }
        
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->selectedColumn = $column;
            $this->columnType = $filterDetails['type'];

            // Use cached distinct values with memory limits
            $this->distinctValues = $this->getCachedDistinctValues($column);
        }
    }

    /**
     * Update filter column
     */
    public function updatedFilterColumn()
    {
        // Only clear the value, keep the column selected
        $this->filterValue = null;
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    /**
     * Update filter value
     */
    public function updatedFilterValue()
    {
        $this->resetPage();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query)
    {
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            if (!$this->isAllowedColumn($this->filterColumn)) {
                return;
            }

            $isRelation = false;
            $relationDetails = null;
            $relationString = null;

            if (isset($this->columns[$this->filterColumn]['relation'])) {
                $relationString = $this->columns[$this->filterColumn]['relation'];
            } elseif (isset($this->filters[$this->filterColumn]['relation'])) {
                $relationString = $this->filters[$this->filterColumn]['relation'];
            }

            if ($relationString) {
                if ($this->validateRelationString($relationString)) {
                    $isRelation = true;
                    [$relationName, $relatedColumn] = explode(':', $relationString);
                    $relationDetails = ['relation' => $relationName, 'column' => $relatedColumn];
                }
            }

            // Determine filter type and operator
            $filterType = isset($this->filters[$this->filterColumn]['type']) ? $this->filters[$this->filterColumn]['type'] : 'text';
            $operator = $this->filterOperator ?? $this->getDefaultOperator($filterType);
            $value = $this->sanitizeFilterValue($this->prepareFilterValue($filterType, $operator, $this->filterValue));

            if ($isRelation && $relationDetails) {
                $this->applyRelationFilter($query, $relationDetails, $operator, $value);
            } else {
                $this->applyColumnFilter($query, $this->filterColumn, $operator, $value, $filterType);
            }
        }
    }

    /**
     * Apply relation filter
     */
    protected function applyRelationFilter(Builder $query, array $relationDetails, string $operator, $value)
    {
        $relationName = $relationDetails['relation'];
        $relatedColumn = $relationDetails['column'];

        $query->whereHas($relationName, function ($relationQuery) use ($relatedColumn, $operator, $value) {
            $this->applyFilterCondition($relationQuery, $relatedColumn, $operator, $value);
        });
    }

    /**
     * Apply column filter
     */
    protected function applyColumnFilter(Builder $query, string $column, string $operator, $value, string $filterType)
    {
        // Check if it's a JSON column
        if (isset($this->columns[$column]['json'])) {
            $this->applyJsonFilter($query, $column, $operator, $value);
        } else {
            $this->applyFilterCondition($query, $column, $operator, $value);
        }
    }

    /**
     * Apply JSON column filter
     */
    protected function applyJsonFilter(Builder $query, string $column, string $operator, $value)
    {
        $columnConfig = $this->columns[$column];
        $jsonColumn = $columnConfig['key'];
        $jsonPath = $columnConfig['json'];

        if (!$this->validateJsonPath($jsonPath)) {
            return;
        }

        // Use JSON_EXTRACT for MySQL
        $jsonExtract = "JSON_EXTRACT({$jsonColumn}, '$.{$jsonPath}')";
        
        switch ($operator) {
            case 'LIKE':
                $query->whereRaw("{$jsonExtract} LIKE ?", ["%{$value}%"]);
                break;
            case '=':
                $query->whereRaw("{$jsonExtract} = ?", [$value]);
                break;
            case '!=':
                $query->whereRaw("{$jsonExtract} != ?", [$value]);
                break;
            case '>':
                $query->whereRaw("{$jsonExtract} > ?", [$value]);
                break;
            case '<':
                $query->whereRaw("{$jsonExtract} < ?", [$value]);
                break;
            case '>=':
                $query->whereRaw("{$jsonExtract} >= ?", [$value]);
                break;
            case '<=':
                $query->whereRaw("{$jsonExtract} <= ?", [$value]);
                break;
        }
    }

    /**
     * Apply filter condition
     */
    protected function applyFilterCondition(Builder $query, string $column, string $operator, $value)
    {
        switch ($operator) {
            case 'LIKE':
                $query->where($column, 'LIKE', "%{$value}%");
                break;
            case 'NOT LIKE':
                $query->where($column, 'NOT LIKE', "%{$value}%");
                break;
            case 'IN':
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
                break;
            case 'NOT IN':
                if (is_array($value)) {
                    $query->whereNotIn($column, $value);
                } else {
                    $query->where($column, '!=', $value);
                }
                break;
            case 'BETWEEN':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereBetween($column, [$value[0], $value[1]]);
                }
                break;
            case 'IS NULL':
                $query->whereNull($column);
                break;
            case 'IS NOT NULL':
                $query->whereNotNull($column);
                break;
            default:
                $query->where($column, $operator, $value);
                break;
        }
    }

    /**
     * Get default operator for filter type
     */
    protected function getDefaultOperator($filterType)
    {
        switch ($filterType) {
            case 'select':
            case 'distinct':
                return '=';
            case 'integer':
            case 'number':
                return '=';
            case 'date':
                return '=';
            case 'text':
            default:
                return 'LIKE';
        }
    }

    /**
     * Prepare filter value based on type and operator
     */
    protected function prepareFilterValue($filterType, $operator, $value)
    {
        switch ($filterType) {
            case 'integer':
            case 'number':
                return is_numeric($value) ? $value : 0;
            case 'date':
                return date('Y-m-d', strtotime($value));
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'text':
            default:
                return $value;
        }
    }

    /**
     * Apply date range filter
     */
    public function applyDateRange($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->resetPage();
    }

    /**
     * Clear filter
     */
    public function clearFilter()
    {
        // Keep filterColumn, just clear value and operator
        $this->filterValue = null;
        $this->filterOperator = '=';
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    /**
     * Clear all filters
     */
    public function clearAllFilters()
    {
        $this->filterColumn = null;
        $this->filterValue = null;
        $this->filterOperator = '=';
        $this->selectedColumn = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->distinctValues = [];
        $this->clearDistinctValuesCache();
        $this->resetPage();
    }

    /**
     * Set filter programmatically
     */
    public function setFilter($column, $value, $operator = '=')
    {
        if (!$this->isAllowedColumn($column)) {
            return;
        }

        $this->filterColumn = $column;
        $this->filterValue = $value;
        $this->filterOperator = $operator;
        $this->resetPage();
    }

    /**
     * Add multiple filters
     */
    public function addFilters(array $filters)
    {
        foreach ($filters as $column => $filterData) {
            if (is_array($filterData)) {
                $value = $filterData['value'] ?? null;
                $operator = $filterData['operator'] ?? '=';
            } else {
                $value = $filterData;
                $operator = '=';
            }

            $this->setFilter($column, $value, $operator);
        }
    }

    /**
     * Get active filters
     */
    public function getActiveFilters(): array
    {
        $activeFilters = [];

        if ($this->filterColumn && $this->filterValue !== null) {
            $activeFilters[] = [
                'column' => $this->filterColumn,
                'value' => $this->filterValue,
                'operator' => $this->filterOperator,
                'label' => $this->getColumnLabel($this->columns[$this->filterColumn] ?? [], $this->filterColumn)
            ];
        }

        if ($this->startDate && $this->endDate) {
            $activeFilters[] = [
                'column' => $this->dateColumn ?? 'created_at',
                'value' => $this->startDate . ' - ' . $this->endDate,
                'operator' => 'BETWEEN',
                'label' => 'Date Range'
            ];
        }

        return $activeFilters;
    }

    /**
     * Check if any filters are active
     */
    public function hasActiveFilters(): bool
    {
        return !empty($this->getActiveFilters());
    }

    /**
     * Get filter summary
     */
    public function getFilterSummary(): string
    {
        $activeFilters = $this->getActiveFilters();
        
        if (empty($activeFilters)) {
            return 'No filters applied';
        }

        $summary = [];
        foreach ($activeFilters as $filter) {
            $summary[] = $filter['label'] . ': ' . $filter['value'];
        }

        return implode(', ', $summary);
    }

    /**
     * Get filter usage statistics
     */
    public function getFilterStats(): array
    {
        $filters = $this->filters ?? [];
        $activeFilters = array_filter($filters, function($filter) {
            return !empty($filter);
        });
        
        return [
            'total_filters' => count($filters),
            'active_filters' => count($activeFilters),
            'filter_column' => $this->filterColumn,
            'filter_operator' => $this->filterOperator,
            'filter_value' => $this->filterValue,
            'date_column' => $this->dateColumn,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'selected_column' => $this->selectedColumn,
            'number_operator' => $this->numberOperator,
            'column_type' => $this->columnType,
            'distinct_values_count' => count($this->distinctValues ?? []),
            'has_date_filter' => !empty($this->startDate) || !empty($this->endDate),
            'timestamp' => now()->toISOString()
        ];
    }
}
