<?php

namespace ArtflowStudio\Table\Traits;

trait HasDataValidation
{
    /**
     * Check if a column is valid in the model
     */
    protected function isValidColumn($column): bool
    {
        try {
            $modelInstance = new ($this->model);
            return in_array($column, $modelInstance->getFillable()) ||
                $modelInstance->getConnection()->getSchemaBuilder()->hasColumn($modelInstance->getTable(), $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a column is allowed for operations
     */
    protected function isAllowedColumn($column)
    {
        // Allow 'updated_at' for index column sorting
        if ($column === 'updated_at') {
            return $this->isValidColumn($column);
        }
        
        return array_key_exists($column, $this->columns);
    }

    /**
     * Sanitize search input
     */
    protected function sanitizeSearch($search)
    {
        $search = trim($search);
        // Limit length to prevent abuse
        return mb_substr($search, 0, 100);
    }

    /**
     * Sanitize filter value
     */
    protected function sanitizeFilterValue($value)
    {
        if (is_string($value)) {
            $value = trim($value);
            // Remove potentially dangerous characters
            $value = preg_replace('/[<>"\']/', '', $value);
            // Limit length
            return mb_substr($value, 0, 255);
        }
        return $value;
    }

    /**
     * Validate sort direction
     */
    protected function validateSortDirection($direction): string
    {
        $direction = strtolower($direction);
        return in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
    }

    /**
     * Validate and sanitize column key
     */
    protected function validateColumnKey($columnKey): ?string
    {
        if (!is_string($columnKey)) {
            return null;
        }

        // Remove special characters except underscore
        $columnKey = preg_replace('/[^a-zA-Z0-9_]/', '', $columnKey);
        
        // Must start with letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $columnKey)) {
            return null;
        }

        return $columnKey;
    }

    /**
     * Validate pagination parameters
     */
    protected function validatePaginationParams(): void
    {
        // Ensure records is within valid range
        if ($this->records < 1) {
            $this->records = 10;
        } elseif ($this->records > 1000) {
            $this->records = 1000; // Prevent memory issues
        }

        // Ensure page is valid
        if ($this->page < 1) {
            $this->page = 1;
        }
    }

    /**
     * Validate filter type
     */
    protected function validateFilterType($filterType): string
    {
        $validTypes = ['text', 'select', 'distinct', 'date', 'integer', 'number', 'boolean'];
        return in_array($filterType, $validTypes) ? $filterType : 'text';
    }

    /**
     * Validate relation string format
     */
    protected function validateRelationString($relationString): bool
    {
        if (!is_string($relationString)) {
            return false;
        }

        // Should be in format: relation:attribute or relation.nested:attribute
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*:[a-zA-Z_][a-zA-Z0-9_]*$/', $relationString) === 1;
    }

    /**
     * Validate JSON path format
     */
    protected function validateJsonPath($jsonPath): bool
    {
        if (!is_string($jsonPath)) {
            return false;
        }

        // Should be alphanumeric with dots for nesting
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $jsonPath) === 1;
    }

    /**
     * Validate export format
     */
    protected function validateExportFormat($format): string
    {
        $validFormats = ['csv', 'xlsx', 'pdf'];
        return in_array(strtolower($format), $validFormats) ? strtolower($format) : 'csv';
    }

    /**
     * Sanitize HTML content for raw templates
     */
    protected function sanitizeHtmlContent($content): string
    {
        if (!is_string($content)) {
            return '';
        }

        // Allow basic HTML tags but escape dangerous ones
        $allowedTags = '<p><br><strong><em><span><div><a><img><ul><ol><li>';
        return strip_tags($content, $allowedTags);
    }

    /**
     * Validate array structure for column configuration
     */
    protected function validateColumnConfiguration(array $column): bool
    {
        // Must have either 'key' or 'function'
        if (!isset($column['key']) && !isset($column['function'])) {
            return false;
        }

        // If relation is set, it must be valid
        if (isset($column['relation']) && !$this->validateRelationString($column['relation'])) {
            return false;
        }

        // If JSON path is set, it must be valid
        if (isset($column['json']) && !$this->validateJsonPath($column['json'])) {
            return false;
        }

        return true;
    }
}
