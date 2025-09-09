<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait HasAdvancedFiltering
{
    /**
     * Supported filter operators for advanced filtering
     */
    protected $advancedFilterOperators = [
        'equals' => '=',
        'not_equals' => '!=',
        'contains' => 'LIKE',
        'not_contains' => 'NOT LIKE',
        'starts_with' => 'LIKE',
        'ends_with' => 'LIKE',
        'greater_than' => '>',
        'greater_than_equal' => '>=',
        'less_than' => '<',
        'less_than_equal' => '<=',
        'between' => 'BETWEEN',
        'not_between' => 'NOT BETWEEN',
        'in' => 'IN',
        'not_in' => 'NOT IN',
        'is_null' => 'IS NULL',
        'is_not_null' => 'IS NOT NULL',
        'is_empty' => '=',
        'is_not_empty' => '!=',
        'regex' => 'REGEXP',
        'date_equals' => '=',
        'date_before' => '<',
        'date_after' => '>',
        'date_between' => 'BETWEEN',
        'this_week' => 'WEEK',
        'this_month' => 'MONTH',
        'this_year' => 'YEAR',
        'last_week' => 'LAST_WEEK',
        'last_month' => 'LAST_MONTH',
        'last_year' => 'LAST_YEAR',
    ];

    /**
     * Filter configuration with type validation
     */
    protected $filterConfig = [
        'string' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_null', 'is_not_null', 'is_empty', 'is_not_empty', 'regex'],
        'number' => ['equals', 'not_equals', 'greater_than', 'greater_than_equal', 'less_than', 'less_than_equal', 'between', 'not_between', 'is_null', 'is_not_null'],
        'date' => ['date_equals', 'date_before', 'date_after', 'date_between', 'this_week', 'this_month', 'this_year', 'last_week', 'last_month', 'last_year', 'is_null', 'is_not_null'],
        'boolean' => ['equals', 'not_equals', 'is_null', 'is_not_null'],
        'select' => ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'],
        'multi_select' => ['in', 'not_in', 'contains', 'not_contains'],
    ];

    /**
     * Initialize advanced filtering
     */
    public function initializeAdvancedFiltering()
    {
        $this->advancedFilters = [];
        $this->advancedFilterValues = [];
    }

    /**
     * Set advanced filters configuration
     */
    public function setAdvancedFilters(array $filters)
    {
        $this->advancedFilters = $filters;
        return $this;
    }

    /**
     * Add advanced filter
     */
    public function addAdvancedFilter($key, $config)
    {
        $this->advancedFilters[$key] = array_merge([
            'label' => ucfirst(str_replace('_', ' ', $key)),
            'type' => 'text',
            'operator' => 'equals',
            'options' => [],
            'multiple' => false,
            'required' => false,
        ], $config);
        
        return $this;
    }

    /**
     * Update advanced filter value
     */
    public function updateAdvancedFilter($key, $value, $operator = null)
    {
        // For text filters, only process if minimum 3 characters or empty
        $filterConfig = $this->advancedFilters[$key] ?? [];
        $filterType = $filterConfig['type'] ?? 'text';
        
        if ($filterType === 'text' && !empty($value) && strlen(trim($value)) < 3) {
            // Don't update filter for text with less than 3 characters
            return;
        }
        
        $this->advancedFilterValues[$key] = [
            'value' => $value,
            'operator' => $operator ?? $this->advancedFilters[$key]['operator'] ?? 'contains' // Default to contains for text
        ];
        
        $this->resetPage();
        $this->dispatch('advancedFilterUpdated', $key, $value, $operator);
    }

    /**
     * Clear advanced filter
     */
    public function clearAdvancedFilter($key)
    {
        unset($this->advancedFilterValues[$key]);
        $this->resetPage();
        $this->dispatch('advancedFilterCleared', $key);
    }

    /**
     * Clear all advanced filters
     */
    public function clearAllAdvancedFilters()
    {
        $this->advancedFilterValues = [];
        $this->resetPage();
        $this->dispatch('allAdvancedFiltersCleared');
    }

    /**
     * Apply advanced filters to query
     */
    protected function applyAdvancedFilters($query)
    {
        foreach ($this->advancedFilterValues as $key => $filter) {
            if (!isset($this->advancedFilters[$key])) {
                continue;
            }
            
            $config = $this->advancedFilters[$key];
            $value = $filter['value'];
            $operator = $filter['operator'];
            
            // Skip empty values for most operators
            if (empty($value) && !in_array($operator, ['is_null', 'is_not_null'])) {
                continue;
            }
            
            // For text filters, enforce minimum 3 characters
            $filterType = $config['type'] ?? 'text';
            if ($filterType === 'text' && !empty($value) && strlen(trim($value)) < 3) {
                continue;
            }
            
            $this->applyAdvancedFilterToQuery($query, $key, $value, $operator, $config);
        }
        
        return $query;
    }

    /**
     * Apply individual advanced filter to query
     */
    protected function applyAdvancedFilterToQuery($query, $key, $value, $operator, $config)
    {
        $column = $config['column'] ?? $key;
        
        switch ($operator) {
            case 'equals':
                $query->where($column, '=', $value);
                break;
            case 'not_equals':
                $query->where($column, '!=', $value);
                break;
            case 'contains':
                $query->where($column, 'LIKE', '%' . $value . '%');
                break;
            case 'not_contains':
                $query->where($column, 'NOT LIKE', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($column, 'LIKE', $value . '%');
                break;
            case 'ends_with':
                $query->where($column, 'LIKE', '%' . $value);
                break;
            case 'greater_than':
                $query->where($column, '>', $value);
                break;
            case 'greater_than_equal':
                $query->where($column, '>=', $value);
                break;
            case 'less_than':
                $query->where($column, '<', $value);
                break;
            case 'less_than_equal':
                $query->where($column, '<=', $value);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($column, $value);
                }
                break;
            case 'not_between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereNotBetween($column, $value);
                }
                break;
            case 'in':
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->whereIn($column, explode(',', $value));
                }
                break;
            case 'not_in':
                if (is_array($value)) {
                    $query->whereNotIn($column, $value);
                } else {
                    $query->whereNotIn($column, explode(',', $value));
                }
                break;
            case 'is_null':
                $query->whereNull($column);
                break;
            case 'is_not_null':
                $query->whereNotNull($column);
                break;
            default:
                // Custom operator handling
                if (method_exists($this, 'handleCustomFilterOperator')) {
                    $this->handleCustomFilterOperator($query, $column, $operator, $value, $config);
                }
                break;
        }
    }

    /**
     * Get available operators for filter type
     */
    public function getAvailableOperators($filterType)
    {
        switch ($filterType) {
            case 'text':
                return ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_null', 'is_not_null'];
            case 'number':
                return ['equals', 'not_equals', 'greater_than', 'greater_than_equal', 'less_than', 'less_than_equal', 'between', 'not_between', 'is_null', 'is_not_null'];
            case 'date':
                return ['equals', 'not_equals', 'greater_than', 'greater_than_equal', 'less_than', 'less_than_equal', 'between', 'not_between', 'is_null', 'is_not_null'];
            case 'select':
                return ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'];
            case 'boolean':
                return ['equals', 'not_equals', 'is_null', 'is_not_null'];
            default:
                return ['equals', 'not_equals', 'is_null', 'is_not_null'];
        }
    }

    /**
     * Get filter input type for operator
     */
    public function getFilterInputType($operator, $filterConfig)
    {
        switch ($operator) {
            case 'between':
            case 'not_between':
                return 'range';
            case 'in':
            case 'not_in':
                return $filterConfig['multiple'] ? 'multiselect' : 'text';
            case 'is_null':
            case 'is_not_null':
                return 'none';
            default:
                return $filterConfig['type'] ?? 'text';
        }
    }

    /**
     * Export current filters
     */
    public function exportFilters()
    {
        return [
            'basic_filters' => [
                'search' => $this->search ?? '',
                'filter_column' => $this->filterColumn ?? '',
                'filter_value' => $this->filterValue ?? '',
            ],
            'advanced_filters' => $this->advancedFilterValues,
            'sorting' => [
                'column' => $this->sortColumn ?? '',
                'direction' => $this->sortDirection ?? 'asc',
            ]
        ];
    }

    /**
     * Import filters
     */
    public function importFilters(array $filters)
    {
        // Import basic filters
        if (isset($filters['basic_filters'])) {
            $this->search = $filters['basic_filters']['search'] ?? '';
            $this->filterColumn = $filters['basic_filters']['filter_column'] ?? '';
            $this->filterValue = $filters['basic_filters']['filter_value'] ?? '';
        }
        
        // Import advanced filters
        if (isset($filters['advanced_filters'])) {
            $this->advancedFilterValues = $filters['advanced_filters'];
        }
        
        // Import sorting
        if (isset($filters['sorting'])) {
            $this->sortColumn = $filters['sorting']['column'] ?? '';
            $this->sortDirection = $filters['sorting']['direction'] ?? 'asc';
        }
        
        $this->resetPage();
        $this->dispatch('filtersImported', $filters);
    }

    /**
     * Get active filters count
     */
    public function getActiveFiltersCount(): int
    {
        $count = 0;
        
        // Count basic filters
        if (!empty($this->search)) $count++;
        if (!empty($this->filterValue)) $count++;
        
        // Count advanced filters
        $count += count($this->advancedFilterValues);
        
        return $count;
    }

    /**
     * Get filters summary
     */
    public function getFiltersSummary(): array
    {
        return [
            'active_count' => $this->getActiveFiltersCount(),
            'basic_filters' => [
                'search' => !empty($this->search),
                'column_filter' => !empty($this->filterValue),
            ],
            'advanced_filters' => array_keys($this->advancedFilterValues),
            'has_sorting' => !empty($this->sortColumn),
        ];
    }
}
