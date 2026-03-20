<?php

namespace ArtflowStudio\Table\Traits\Advanced;

trait HasUtilities
{
    /**
     * Get dynamic CSS class for column based on conditions.
     *
     * Accepts a closure condition (preferred) or a string shorthand:
     *   - Closure: fn($row) => $row->active — returns bool
     *   - String shorthand: 'active', '!active', 'status:published' (key:value equality)
     *
     * Example column config:
     *   'classCondition' => [
     *       'text-success' => fn($row) => $row->active,
     *       'text-danger'  => fn($row) => !$row->active,
     *       'fw-bold'      => 'featured',           // truthy check: $row->featured
     *       'badge-red'    => 'status:inactive',    // equality check
     *   ]
     */
    public function getDynamicClass($column, $row): string
    {
        $classes = [];

        if (! isset($column['classCondition']) || ! is_array($column['classCondition'])) {
            return '';
        }

        foreach ($column['classCondition'] as $class => $condition) {
            try {
                $matches = false;

                if ($condition instanceof \Closure) {
                    $matches = (bool) $condition($row);
                } elseif (is_callable($condition)) {
                    $matches = (bool) call_user_func($condition, $row);
                } elseif (is_string($condition)) {
                    // Negation shorthand: '!fieldName'
                    if (str_starts_with($condition, '!')) {
                        $field = substr($condition, 1);
                        $matches = empty($row->{$field} ?? null);
                    } elseif (str_contains($condition, ':')) {
                        // Equality shorthand: 'fieldName:expectedValue'
                        [$field, $expected] = explode(':', $condition, 2);
                        $matches = (string) ($row->{$field} ?? '') === $expected;
                    } else {
                        // Truthy shorthand: 'fieldName'
                        $matches = ! empty($row->{$condition} ?? null);
                    }
                } elseif (is_bool($condition)) {
                    $matches = $condition;
                }

                if ($matches) {
                    $classes[] = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('getDynamicClass condition error: '.$e->getMessage(), [
                    'class' => $class,
                    'row_id' => $row->id ?? 'unknown',
                ]);
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Sanitize search input for utilities (renamed to avoid collision with HasSearch)
     */
    protected function sanitizeUtilitySearch($search)
    {
        if (empty($search)) {
            return '';
        }
        
        // Remove potentially dangerous characters
        $search = strip_tags($search);
        $search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
        
        // Limit length to prevent abuse
        return substr($search, 0, 255);
    }

    /**
     * Format value for display based on column type
     */
    protected function formatValueForDisplay($value, $columnType = null)
    {
        if (is_null($value)) {
            return '';
        }
        
        switch ($columnType) {
            case 'date':
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return $value;
                }
                
            case 'datetime':
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return $value;
                }
                
            case 'currency':
                return number_format((float)$value, 2);
                
            case 'percentage':
                return number_format((float)$value, 2) . '%';
                
            case 'boolean':
                return $value ? 'Yes' : 'No';
                
            default:
                return (string)$value;
        }
    }

    /**
     * Get default operator for filter type
     */
    protected function getDefaultOperator($filterType)
    {
        switch ($filterType) {
            case 'number':
            case 'integer':
            case 'decimal':
                return '=';
                
            case 'date':
            case 'datetime':
                return '>=';
                
            case 'text':
            case 'string':
            default:
                return 'like';
        }
    }

    /**
     * Prepare filter value based on type and operator
     */
    protected function prepareFilterValue($filterType, $operator, $value)
    {
        if (is_null($value) || $value === '') {
            return $value;
        }
        
        switch ($filterType) {
            case 'number':
            case 'integer':
                return (int)$value;
                
            case 'decimal':
                return (float)$value;
                
            case 'date':
            case 'datetime':
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return $value;
                }
                
            case 'text':
            case 'string':
            default:
                if ($operator === 'like') {
                    return '%' . $value . '%';
                }
                return $value;
        }
    }

    /**
     * Check if a column is searchable
     */
    protected function isColumnSearchable($column): bool
    {
        // Column is searchable if:
        // 1. It has a 'key' (database column)
        // 2. It's not explicitly marked as non-searchable
        // 3. It's not a function-based column (unless specifically marked searchable)
        
        if (!isset($column['key'])) {
            return false;
        }
        
        if (isset($column['searchable'])) {
            return (bool)$column['searchable'];
        }
        
        if (isset($column['function'])) {
            return false; // Function columns are not searchable by default
        }
        
        return true; // Regular columns are searchable by default
    }

    /**
     * Get human-readable column label
     */
    protected function getColumnDisplayLabel($column, $columnKey)
    {
        if (isset($column['label'])) {
            return $column['label'];
        }
        
        // Generate label from key
        return ucwords(str_replace(['_', '-'], ' ', $columnKey));
    }

    /**
     * Generate basic cache key for utilities (renamed to avoid collision with HasUnifiedCaching)
     */
    protected function generateBasicCacheKey($suffix = '')
    {
        $modelClass = is_string($this->model) ? $this->model : get_class($this->model);
        $baseKey = 'datatable_' . md5($modelClass . '_' . ($this->tableId ?? 'default'));
        
        return $suffix ? $baseKey . '_' . $suffix : $baseKey;
    }
}
