<?php

namespace ArtflowStudio\Table\Traits\Core;

/**
 * Trait HasValidation
 * 
 * Handles all validation operations including configuration validation,
 * data sanitization, and format validation
 * Enhanced with methods moved from main DatatableTrait
 */
trait HasValidation
{
    /**
     * Validate the complete configuration
     * Enhanced from existing method
     */
    protected function validateConfiguration()
    {
        $errors = [];

        // Validate model
        if (!$this->model) {
            $errors[] = 'Model is required';
        } elseif (!class_exists($this->model)) {
            $errors[] = 'Model class does not exist: ' . $this->model;
        }

        // Validate columns
        if (empty($this->columns)) {
            $errors[] = 'Columns configuration is required';
        } else {
            $columnValidation = $this->validateColumns();
            if (!empty($columnValidation['errors'])) {
                $errors = array_merge($errors, $columnValidation['errors']);
            }
        }

        // Validate relations
        $relationValidation = $this->validateRelationColumns();
        if (!empty($relationValidation['invalid'])) {
            foreach ($relationValidation['invalid'] as $column => $error) {
                $errors[] = "Invalid relation in column '{$column}': {$error}";
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datatable configuration errors: ' . implode(', ', $errors));
        }
    }

    /**
     * Sanitize filter value
     * Enhanced from existing method
     */
    protected function sanitizeFilterValue($value)
    {
        if (is_string($value)) {
            return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * Validate JSON path format
     * Enhanced from existing method
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
     * Validate relation string format
     * Enhanced from existing method
     */
    protected function validateRelationString($relationString): bool
    {
        if (empty($relationString) || !is_string($relationString)) {
            return false;
        }

        // Should contain at least relation:column format
        if (!str_contains($relationString, ':')) {
            return false;
        }

        [$relationName, $column] = explode(':', $relationString, 2);

        return !empty($relationName) && !empty($column);
    }

    /**
     * Validate export format
     * Enhanced from existing method
     */
    protected function validateExportFormat($format): string
    {
        $validFormats = ['csv', 'xlsx', 'pdf'];

        return in_array(strtolower($format), $validFormats) ? strtolower($format) : 'csv';
    }

    /**
     * Validate filter value
     * Enhanced from existing method
     */
    public function validateFilterValue($value, string $type): bool
    {
        return $this->isValidFilterValue($value, $type);
    }

    /**
     * Check if a column is searchable
     * Moved from DatatableTrait.php line 2347
     */
    protected function isColumnSearchable($column): bool
    {
        // Skip if explicitly marked as non-searchable
        if (isset($column['searchable']) && !$column['searchable']) {
            return false;
        }

        // Skip function columns (computed values)
        if (isset($column['function'])) {
            return false;
        }

        // Skip JSON columns that don't have a searchable path
        if (isset($column['json']) && !isset($column['searchable_json_path'])) {
            return false;
        }

        // Allow relation columns if they have valid relation string
        if (isset($column['relation'])) {
            return $this->validateRelationString($column['relation']);
        }

        // Allow regular columns with valid keys
        if (isset($column['key'])) {
            return $this->isValidColumn($column['key']);
        }

        return false;
    }

    /**
     * Get default operator for filter type
     * Enhanced from existing method with more cases
     */
    protected function getDefaultOperator($filterType)
    {
        switch ($filterType) {
            case 'text':
            case 'search':
                return 'like';
            case 'select':
            case 'boolean':
                return '=';
            case 'date':
            case 'datetime':
                return '>=';
            case 'number':
            case 'range':
                return '=';
            default:
                return '=';
        }
    }

    /**
     * Prepare filter value
     * Enhanced from existing method
     */
    protected function prepareFilterValue($filterType, $operator, $value)
    {
        // For text, we now always use the raw value (LIKE %value%)
        return $value;
    }

    /**
     * Abstract methods that must be implemented in the main class or other traits
     */
    abstract protected function validateColumns(): array;
    abstract protected function validateRelationColumns(): array;
    abstract protected function isValidFilterValue($value, string $type): bool;
    abstract protected function isValidColumn(string $column): bool;
}
