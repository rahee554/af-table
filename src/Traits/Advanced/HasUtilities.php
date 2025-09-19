<?php

namespace ArtflowStudio\Table\Traits\Advanced;

trait HasUtilities
{
    /**
     * Get dynamic CSS class for column based on conditions
     */
    public function getDynamicClass($column, $row)
    {
        $classes = [];
        
        if (isset($column['classCondition']) && is_array($column['classCondition'])) {
            foreach ($column['classCondition'] as $class => $condition) {
                try {
                    if (eval("return $condition;")) {
                        $classes[] = $class;
                    }
                } catch (\Exception $e) {
                    // Log error and continue
                    \Illuminate\Support\Facades\Log::warning('Dynamic class condition error: ' . $e->getMessage(), [
                        'condition' => $condition,
                        'column' => $column,
                        'row_id' => $row->id ?? 'unknown'
                    ]);
                }
            }
        }
        
        return implode(' ', $classes);
    }

    /**
     * Sanitize HTML content for safe output
     */
    protected function sanitizeHtmlContent($content): string
    {
        if (empty($content)) {
            return '';
        }
        
        // Allow basic HTML tags that are commonly used in datatables
        $allowedTags = '<a><span><div><strong><b><em><i><u><br><p><small>';
        
        return strip_tags($content, $allowedTags);
    }

    /**
     * Validate JSON path syntax
     */
    protected function validateJsonPath($jsonPath): bool
    {
        if (empty($jsonPath)) {
            return false;
        }
        
        // Check for basic path format (alphanumeric, dots, underscores)
        return preg_match('/^[a-zA-Z0-9_.]+$/', $jsonPath) === 1;
    }

    /**
     * Validate relation string format
     */
    protected function validateRelationString($relationString): bool
    {
        if (empty($relationString)) {
            return false;
        }
        
        // Check for proper relation format (relation:attribute)
        return strpos($relationString, ':') !== false;
    }

    /**
     * Validate export format
     */
    protected function validateExportFormat($format): string
    {
        $allowedFormats = ['csv', 'xlsx', 'pdf', 'json'];
        $format = strtolower(trim($format));
        
        return in_array($format, $allowedFormats) ? $format : 'csv';
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
     * Sanitize filter value
     */
    protected function sanitizeFilterValue($value)
    {
        if (is_null($value) || $value === '') {
            return $value;
        }
        
        if (is_string($value)) {
            // Remove HTML tags and escape special characters
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
            // Limit length
            return substr($value, 0, 255);
        }
        
        return $value;
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
