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
            if (!class_exists($this->model)) {
                return false;
            }
            
            $modelInstance = new ($this->model);
            
            // Check if it's a standard column
            if (in_array($column, ['id', 'created_at', 'updated_at'])) {
                return true;
            }
            
            // Check if it's fillable
            if (in_array($column, $modelInstance->getFillable())) {
                return true;
            }
            
            // Try to check if column exists in database table
            try {
                return $modelInstance->getConnection()->getSchemaBuilder()->hasColumn($modelInstance->getTable(), $column);
            } catch (\Exception $dbException) {
                // If database check fails, assume column exists if it's configured
                return array_key_exists($column, $this->columns ?? []);
            }
        } catch (\Exception $e) {
            // If model instantiation fails, check if column is configured
            return array_key_exists($column, $this->columns ?? []);
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
     * Validate basic relation string format (validation-specific)
     */
    protected function validateBasicRelationString($relationString): bool
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
        // Column configuration is valid if it's an array with at least a label
        // The key is provided as the array key when columns are defined
        if (!is_array($column)) {
            return false;
        }

        // If relation is set, it must be valid
        if (isset($column['relation']) && !$this->validateBasicRelationString($column['relation'])) {
            return false;
        }

        // If JSON path is set, it must be valid
        if (isset($column['json']) && !$this->validateJsonPath($column['json'])) {
            return false;
        }

        return true;
    }

    /**
     * Validate all columns configuration
     */
    protected function validateColumns(): array
    {
        $result = [
            'valid' => [],
            'invalid' => [],
            'errors' => []
        ];

        if (empty($this->columns)) {
            $result['errors'][] = 'No columns defined';
            return $result;
        }

        foreach ($this->columns as $columnKey => $column) {
            if (!is_array($column)) {
                $result['invalid'][$columnKey] = 'Column configuration must be an array';
                $result['errors'][] = "Column {$columnKey}: Configuration must be an array";
                continue;
            }

            if ($this->validateColumnConfiguration($column)) {
                $result['valid'][$columnKey] = $column;
            } else {
                $result['invalid'][$columnKey] = 'Invalid column configuration';
                $result['errors'][] = "Column {$columnKey}: Invalid configuration";
            }
        }

        return $result;
    }

    /**
     * Validate relation columns specifically (basic validation)
     */
    protected function validateBasicRelationColumns(): array
    {
        $result = [
            'valid' => [],
            'invalid' => [],
            'errors' => []
        ];

        foreach ($this->columns as $columnKey => $column) {
            if (!isset($column['relation'])) {
                continue; // Skip non-relation columns
            }

            if (!$this->validateBasicRelationString($column['relation'])) {
                $result['invalid'][$columnKey] = 'Invalid relation string format';
                $result['errors'][] = "Column {$columnKey}: Invalid relation string format '{$column['relation']}'";
                continue;
            }

            // Further validation: check if relation exists on model
            try {
                [$relationName, $attribute] = explode(':', $column['relation']);
                $baseRelation = explode('.', $relationName)[0]; // Get base relation for nested relations

                if (class_exists($this->model)) {
                    $modelInstance = new ($this->model);
                    if (!method_exists($modelInstance, $baseRelation)) {
                        $result['invalid'][$columnKey] = "Relation '{$baseRelation}' does not exist on model";
                        $result['errors'][] = "Column {$columnKey}: Relation '{$baseRelation}' does not exist on model";
                        continue;
                    }
                }

                $result['valid'][$columnKey] = $column;
            } catch (\Exception $e) {
                $result['invalid'][$columnKey] = 'Error validating relation: ' . $e->getMessage();
                $result['errors'][] = "Column {$columnKey}: Error validating relation - " . $e->getMessage();
            }
        }

        return $result;
    }
}
