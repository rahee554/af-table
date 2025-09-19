<?php

namespace ArtflowStudio\Table\Traits\UI;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

trait HasRawTemplates
{
    /**
     * Render raw HTML content (SECURED) - Enhanced to support Laravel functions
     */
    public function renderRawHtml($rawTemplate, $row)
    {
        try {
            return $this->renderSecureTemplate($rawTemplate, $row);
        } catch (\Exception $e) {
            Log::warning('Raw template rendering error: ' . $e->getMessage(), [
                'template' => $rawTemplate,
                'row_id' => $row->id ?? 'unknown'
            ]);
            
            // Return safe fallback - show the literal template for debugging
            return htmlspecialchars($rawTemplate, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Secure template rendering using Laravel's native Blade engine
     */
    protected function renderSecureTemplate($template, $row): string
    {
        if (empty($template) || !is_string($template)) { 
            return '';
        }

        try {
            // Use Laravel's native Blade engine for proper template rendering
            // This is the same approach used in raw-render.blade.php
            return Blade::render($template, compact('row'));
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::warning('Datatable raw template rendering error: ' . $e->getMessage(), [
                'template' => $template,
                'row_id' => $row->id ?? 'unknown'
            ]);
            
            // Return safe fallback - show the literal template for debugging
            return htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Get nested property value (supports relations like customer.first_name)
     */
    protected function getNestedPropertyValue($row, $propertyPath): string
    {
        $parts = explode('.', $propertyPath);
        $value = $row;
        
        foreach ($parts as $part) {
            if (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } elseif (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return '';
            }
        }
        
        return (string)$value;
    }

    /**
     * Get row property value safely
     */
    protected function getRowPropertyValue($row, $property): string
    {
        $value = '';
        if (is_object($row) && isset($row->$property)) {
            $value = $row->$property;
        } elseif (is_array($row) && isset($row[$property])) {
            $value = $row[$property];
        }
        
        return (string)$value;
    }
    /**
     * Process raw template for a column - Enhanced to support Blade syntax
     */
    protected function processRawTemplate($record, $template): string
    {
        if (empty($template)) {
            return '';
        }

        $processedTemplate = $template;
        
        // FIRST: Handle Blade-style syntax {{ $row->property }}
        preg_match_all('/\{\{\s*\$row->([a-zA-Z0-9_]+)\s*\}\}/', $template, $bladeMatches);
        
        if (!empty($bladeMatches[1])) {
            foreach ($bladeMatches[1] as $index => $property) {
                $value = $this->getRecordPropertyValue($record, $property);
                $fullMatch = $bladeMatches[0][$index];
                $processedTemplate = str_replace($fullMatch, (string)$value, $processedTemplate);
            }
        }
        
        // SECOND: Handle ternary operator syntax {{ $row->active == 1 ? "success" : "danger" }}
        preg_match_all('/\{\{\s*\$row->([a-zA-Z0-9_]+)\s*==\s*(\d+)\s*\?\s*["\']([^"\']+)["\']\s*:\s*["\']([^"\']+)["\']\s*\}\}/', $template, $ternaryMatches);
        
        if (!empty($ternaryMatches[1])) {
            foreach ($ternaryMatches[1] as $index => $property) {
                $checkValue = (int)$ternaryMatches[2][$index];
                $trueValue = $ternaryMatches[3][$index];
                $falseValue = $ternaryMatches[4][$index];
                
                $actualValue = (int)$this->getRecordPropertyValue($record, $property);
                $result = ($actualValue == $checkValue) ? $trueValue : $falseValue;
                
                $fullMatch = $ternaryMatches[0][$index];
                $processedTemplate = str_replace($fullMatch, $result, $processedTemplate);
            }
        }
        
        // THIRD: Handle simple placeholder syntax {column_name} or {relation:column} - for backward compatibility
        preg_match_all('/\{([^}]+)\}/', $processedTemplate, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $placeholder) {
                $value = $this->getPlaceholderValue($record, $placeholder);
                $processedTemplate = str_replace('{' . $placeholder . '}', $value, $processedTemplate);
            }
        }
        
        return $processedTemplate;
    }

    /**
     * Get property value from record
     */
    protected function getRecordPropertyValue($record, $property): string
    {
        if (is_object($record) && isset($record->$property)) {
            return (string)$record->$property;
        } elseif (is_array($record) && isset($record[$property])) {
            return (string)$record[$property];
        }
        
        return '';
    }

    /**
     * Get value for a placeholder
     */
    protected function getPlaceholderValue($record, $placeholder): string
    {
        // Check if it's a relation placeholder
        if (str_contains($placeholder, ':')) {
            return (string) $this->getRelationValue($record, $placeholder);
        }
        
        // Check if it's a direct column
        if (isset($record->$placeholder)) {
            return (string) $record->$placeholder;
        }
        
        // Check if it's defined in columns configuration
        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['key']) && $column['key'] === $placeholder) {
                return (string) $this->getColumnValue($record, $columnKey);
            }
        }
        
        return '';
    }

    /**
     * Parse raw template to find dependencies
     */
    protected function parseTemplateDependencies($template): array
    {
        $dependencies = [];
        
        preg_match_all('/\{([^}]+)\}/', $template, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $placeholder) {
                if (str_contains($placeholder, ':')) {
                    // It's a relation
                    [$relation] = explode(':', $placeholder);
                    $dependencies['relations'][] = $relation;
                } else {
                    // It's a column
                    $dependencies['columns'][] = $placeholder;
                }
            }
        }
        
        return [
            'relations' => array_unique($dependencies['relations'] ?? []),
            'columns' => array_unique($dependencies['columns'] ?? [])
        ];
    }

    /**
     * Validate raw template syntax
     */
    protected function validateRawTemplate($template): array
    {
        $errors = [];
        $warnings = [];
        
        // Check for basic syntax
        $openBraces = substr_count($template, '{');
        $closeBraces = substr_count($template, '}');
        
        if ($openBraces !== $closeBraces) {
            $errors[] = 'Mismatched braces in template';
        }
        
        // Check for empty placeholders
        if (preg_match('/\{\s*\}/', $template)) {
            $errors[] = 'Empty placeholders found';
        }
        
        // Check for nested placeholders
        if (preg_match('/\{[^}]*\{/', $template)) {
            $errors[] = 'Nested placeholders are not supported';
        }
        
        // Validate dependencies
        $dependencies = $this->parseTemplateDependencies($template);
        
        // Check if relations exist
        foreach ($dependencies['relations'] as $relation) {
            if (!$this->relationExists($relation)) {
                $warnings[] = "Relation '{$relation}' may not exist on model";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'dependencies' => $dependencies
        ];
    }

    /**
     * Get all raw template columns
     */
    protected function getRawTemplateColumns(): array
    {
        $rawTemplateColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['raw_template'])) {
                $rawTemplateColumns[$columnKey] = [
                    'template' => $column['raw_template'],
                    'validation' => $this->validateRawTemplate($column['raw_template']),
                    'dependencies' => $this->parseTemplateDependencies($column['raw_template'])
                ];
            }
        }
        
        return $rawTemplateColumns;
    }

    /**
     * Render raw template with custom functions
     */
    protected function renderTemplateWithFunctions($record, $template): string
    {
        $processedTemplate = $this->processRawTemplate($record, $template);
        
        // Apply custom functions like |upper, |lower, |date, etc.
        $processedTemplate = $this->applyTemplateFunctions($processedTemplate);
        
        return $processedTemplate;
    }

    /**
     * Apply template functions (filters)
     */
    protected function applyTemplateFunctions($content): string
    {
        // This is a basic implementation - you could extend it with more functions
        
        // Date formatting: {created_at|date:Y-m-d}
        $content = preg_replace_callback(
            '/\{([^|]+)\|date:([^}]+)\}/',
            function ($matches) {
                $value = $matches[1];
                $format = $matches[2];
                
                try {
                    $date = new \DateTime($value);
                    return $date->format($format);
                } catch (\Exception $e) {
                    return $value;
                }
            },
            $content
        );
        
        // Uppercase: {name|upper}
        $content = preg_replace_callback(
            '/\{([^|]+)\|upper\}/',
            function ($matches) {
                return strtoupper($matches[1]);
            },
            $content
        );
        
        // Lowercase: {name|lower}
        $content = preg_replace_callback(
            '/\{([^|]+)\|lower\}/',
            function ($matches) {
                return strtolower($matches[1]);
            },
            $content
        );
        
        // Truncate: {description|truncate:50}
        $content = preg_replace_callback(
            '/\{([^|]+)\|truncate:(\d+)\}/',
            function ($matches) {
                $value = $matches[1];
                $length = (int) $matches[2];
                
                return strlen($value) > $length ? substr($value, 0, $length) . '...' : $value;
            },
            $content
        );
        
        return $content;
    }

    /**
     * Create template with HTML elements
     */
    protected function createHtmlTemplate($record, $template): string
    {
        $processedTemplate = $this->renderTemplateWithFunctions($record, $template);
        
        // Basic HTML encoding for safety
        // You might want to be more selective about what gets encoded
        return $processedTemplate;
    }

    /**
     * Get template performance statistics
     */
    public function getTemplateStats(): array
    {
        $rawTemplateColumns = $this->getRawTemplateColumns();
        $totalTemplates = count($rawTemplateColumns);
        $validTemplates = 0;
        $templatesWithWarnings = 0;
        $totalDependencies = 0;
        
        foreach ($rawTemplateColumns as $columnData) {
            if ($columnData['validation']['valid']) {
                $validTemplates++;
            }
            
            if (!empty($columnData['validation']['warnings'])) {
                $templatesWithWarnings++;
            }
            
            $totalDependencies += count($columnData['dependencies']['columns']) + count($columnData['dependencies']['relations']);
        }
        
        return [
            'total_templates' => $totalTemplates,
            'valid_templates' => $validTemplates,
            'invalid_templates' => $totalTemplates - $validTemplates,
            'templates_with_warnings' => $templatesWithWarnings,
            'total_dependencies' => $totalDependencies,
            'average_dependencies_per_template' => $totalTemplates > 0 ? round($totalDependencies / $totalTemplates, 2) : 0,
            'templates' => $rawTemplateColumns
        ];
    }

    /**
     * Test template rendering
     */
    public function testTemplate($columnKey, $testData = null): array
    {
        if (!isset($this->columns[$columnKey]['raw_template'])) {
            return [
                'error' => 'Column does not have raw template configuration',
                'column_key' => $columnKey
            ];
        }
        
        $template = $this->columns[$columnKey]['raw_template'];
        $validation = $this->validateRawTemplate($template);
        
        $result = [
            'column_key' => $columnKey,
            'template' => $template,
            'validation' => $validation
        ];
        
        if (!$validation['valid']) {
            return $result;
        }
        
        // Test with actual data if available
        if ($testData) {
            try {
                $rendered = $this->renderTemplateWithFunctions($testData, $template);
                $result['test_render'] = [
                    'success' => true,
                    'output' => $rendered
                ];
            } catch (\Exception $e) {
                $result['test_render'] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $result;
    }

    /**
     * Get template suggestions for a column
     */
    public function getTemplateSuggestions($columnKey): array
    {
        $suggestions = [];
        
        // Basic column value
        $suggestions[] = '{' . $columnKey . '}';
        
        // With formatting functions
        $suggestions[] = '{' . $columnKey . '|upper}';
        $suggestions[] = '{' . $columnKey . '|lower}';
        $suggestions[] = '{' . $columnKey . '|truncate:50}';
        
        // If it's a date field
        if (str_contains(strtolower($columnKey), 'date') || str_contains(strtolower($columnKey), 'created') || str_contains(strtolower($columnKey), 'updated')) {
            $suggestions[] = '{' . $columnKey . '|date:Y-m-d}';
            $suggestions[] = '{' . $columnKey . '|date:d/m/Y H:i}';
        }
        
        // Common HTML templates
        $suggestions[] = '<span class="badge">{' . $columnKey . '}</span>';
        $suggestions[] = '<a href="/edit/{id}">{' . $columnKey . '}</a>';
        
        return $suggestions;
    }
}
