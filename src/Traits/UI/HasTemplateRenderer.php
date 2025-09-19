<?php

namespace ArtflowStudio\Table\Traits\UI;

use Illuminate\Support\Facades\Log;

trait HasTemplateRenderer
{
    /**
     * Render a Blade expression with proper context
     */
    protected function renderBladeExpression($expression, $row): string
    {
        try {
            // Create a safe evaluation context
            $context = compact('row');

            // Handle Carbon formatting patterns: \Carbon\Carbon::parse($row->property)->format("...")
            if (preg_match('/\\\\Carbon\\\\Carbon::parse\(\$row->([a-zA-Z0-9_]+)\)->format\(["\']([^"\']+)["\']\)/', $expression, $carbonMatches)) {
                $property = $carbonMatches[1];
                $format = $carbonMatches[2];
                $value = $this->getRowPropertyValue($row, $property);
                
                if ($value) {
                    try {
                        return \Carbon\Carbon::parse($value)->format($format);
                    } catch (\Exception $e) {
                        return $value;
                    }
                }
                return '';
            }

            // Handle other common Blade expressions by evaluating them safely
            // For simple property access: $row->property
            if (preg_match('/^\$row->([a-zA-Z0-9_]+)$/', $expression, $simpleMatches)) {
                $value = $this->getRowPropertyValue($row, $simpleMatches[1]);
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }

            // Handle ternary operators with null checks: $row->user_id !== null ? "Exist" : "Not Exist"
            if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*(===|!==|==|!=)\s*null\s*\?\s*["\']([^"\']*)["\']?\s*:\s*["\']([^"\']*)["\']?/', $expression, $nullTernaryMatches)) {
                $property = $nullTernaryMatches[1];
                $operator = $nullTernaryMatches[2];
                $trueValue = $nullTernaryMatches[3];
                $falseValue = $nullTernaryMatches[4];

                $actualValue = $this->getRowPropertyValue($row, $property);
                $isNull = $actualValue === null || $actualValue === '';

                $result = false;
                switch ($operator) {
                    case '===':
                    case '==':
                        $result = $isNull ? $trueValue : $falseValue;
                        break;
                    case '!==':
                    case '!=':
                        $result = !$isNull ? $trueValue : $falseValue;
                        break;
                }

                return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
            }

            // Replace $row with actual row data in the expression for complex cases
            $processedExpression = preg_replace_callback('/\$row->([a-zA-Z0-9_]+)/', function ($matches) use ($row) {
                $value = $this->getRowPropertyValue($row, $matches[1]);
                return '"' . addslashes($value) . '"';
            }, $expression);

            // Handle method calls like ucfirst(), strtoupper(), etc.
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\((.+)\)$/', $processedExpression, $funcMatches)) {
                $function = $funcMatches[1];
                $params = $funcMatches[2];
                
                if (function_exists($function)) {
                    try {
                        return call_user_func($function, trim($params, '"\''));
                    } catch (\Exception $e) {
                        return $params;
                    }
                }
            }

            // For simple static class calls, evaluate them directly
            if (str_contains($processedExpression, '::')) {
                return htmlspecialchars($processedExpression, ENT_QUOTES, 'UTF-8');
            }

            // Fallback to simple string replacement
            return htmlspecialchars($processedExpression, ENT_QUOTES, 'UTF-8');

        } catch (\Exception $e) {
            Log::error('Blade expression rendering error: ' . $e->getMessage());
            return '[Blade Error]';
        }
    }

    /**
     * Evaluate a complex expression safely
     */
    protected function evaluateExpression($expression, $row): string
    {
        // Handle function calls first: ucfirst($row->status ?? "Unknown")
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\((.+)\)$/', $expression, $functionMatches)) {
            $function = $functionMatches[1];
            $parameters = $functionMatches[2];

            // Parse parameters - handle $row->property ?? "default"
            if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*\?\?\s*["\']([^"\']*)["\']/', $parameters, $paramMatches)) {
                $property = $paramMatches[1];
                $defaultValue = $paramMatches[2];
                $actualValue = $this->getRowPropertyValue($row, $property);
                $value = $actualValue ?: $defaultValue;

                if (function_exists($function)) {
                    try {
                        return htmlspecialchars(call_user_func($function, $value), ENT_QUOTES, 'UTF-8');
                    } catch (\Exception $e) {
                        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                }
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            // Simple parameter: ucfirst($row->property)
            elseif (preg_match('/\$row->([a-zA-Z0-9_]+)/', $parameters, $paramMatches)) {
                $property = $paramMatches[1];
                $value = $this->getRowPropertyValue($row, $property);

                if (function_exists($function)) {
                    try {
                        return htmlspecialchars(call_user_func($function, $value), ENT_QUOTES, 'UTF-8');
                    } catch (\Exception $e) {
                        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                }
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }

        // Handle ternary operators with null checks: $row->user_id !== null ? "Exist" : "Not Exist"
        if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*(===|!==|==|!=)\s*null\s*\?\s*["\']([^"\']*)["\']?\s*:\s*["\']([^"\']*)["\']?/', $expression, $nullTernaryMatches)) {
            $property = $nullTernaryMatches[1];
            $operator = $nullTernaryMatches[2];
            $trueValue = $nullTernaryMatches[3];
            $falseValue = $nullTernaryMatches[4];

            $actualValue = $this->getRowPropertyValue($row, $property);
            $isNull = $actualValue === null || $actualValue === '';

            $result = false;
            switch ($operator) {
                case '===':
                case '==':
                    $result = $isNull ? $trueValue : $falseValue;
                    break;
                case '!==':
                case '!=':
                    $result = !$isNull ? $trueValue : $falseValue;
                    break;
            }

            return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }

        // Handle nested ternary operators: $row->status === "scheduled" ? "light-primary" : ($row->status === "boarding" ? "light-info" : "light-secondary")
        if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*(===|==)\s*["\']([^"\']+)["\']\s*\?\s*["\']([^"\']*)["\']?\s*:\s*\((.+)\)/', $expression, $nestedTernaryMatches)) {
            $property = $nestedTernaryMatches[1];
            $operator = $nestedTernaryMatches[2];
            $checkValue = $nestedTernaryMatches[3];
            $trueValue = $nestedTernaryMatches[4];
            $nestedExpression = $nestedTernaryMatches[5];

            $actualValue = $this->getRowPropertyValue($row, $property);

            if (($operator === '===' && $actualValue === $checkValue) || ($operator === '==' && $actualValue == $checkValue)) {
                return htmlspecialchars($trueValue, ENT_QUOTES, 'UTF-8');
            } else {
                return $this->evaluateExpression($nestedExpression, $row);
            }
        }

        // Handle simple ternary operators: $row->status === "scheduled" ? "Active" : "Inactive"
        if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*(===|==|!=|!==)\s*["\']([^"\']+)["\']\s*\?\s*["\']([^"\']*)["\']?\s*:\s*["\']([^"\']*)["\']?/', $expression, $ternaryMatches)) {
            $property = $ternaryMatches[1];
            $operator = $ternaryMatches[2];
            $checkValue = $ternaryMatches[3];
            $trueValue = $ternaryMatches[4];
            $falseValue = $ternaryMatches[5];

            $actualValue = $this->getRowPropertyValue($row, $property);
            $result = $this->evaluateCondition($actualValue, $operator, $checkValue) ? $trueValue : $falseValue;
            return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }

        // Handle ternary operators with numeric values: $row->active == 1 ? "Active" : "Inactive"
        if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*==\s*(\d+)\s*\?\s*["\']([^"\']*)["\']?\s*:\s*["\']([^"\']*)["\']?/', $expression, $ternaryMatches)) {
            $property = $ternaryMatches[1];
            $checkValue = (int) $ternaryMatches[2];
            $trueValue = $ternaryMatches[3];
            $falseValue = $ternaryMatches[4];

            $actualValue = (int) $this->getRowPropertyValue($row, $property);
            $result = ($actualValue == $checkValue) ? $trueValue : $falseValue;
            return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }

        // Handle ternary operators with numeric comparisons: $row->score >= 80 ? "success" : "warning"
        if (preg_match('/\$row->([a-zA-Z0-9_]+)\s*(>=|<=|>|<)\s*(\d+)\s*\?\s*["\']([^"\']*)["\']?\s*:\s*["\']([^"\']*)["\']?/', $expression, $numericTernaryMatches)) {
            $property = $numericTernaryMatches[1];
            $operator = $numericTernaryMatches[2];
            $checkValue = (int) $numericTernaryMatches[3];
            $trueValue = $numericTernaryMatches[4];
            $falseValue = $numericTernaryMatches[5];

            $actualValue = (int) $this->getRowPropertyValue($row, $property);

            $condition = false;
            switch ($operator) {
                case '>=':
                    $condition = $actualValue >= $checkValue;
                    break;
                case '<=':
                    $condition = $actualValue <= $checkValue;
                    break;
                case '>':
                    $condition = $actualValue > $checkValue;
                    break;
                case '<':
                    $condition = $actualValue < $checkValue;
                    break;
            }

            $result = $condition ? $trueValue : $falseValue;
            return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }

        // Handle simple property access: $row->property
        if (preg_match('/^\$row->([a-zA-Z0-9_]+)$/', $expression, $propertyMatches)) {
            $value = $this->getRowPropertyValue($row, $propertyMatches[1]);
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        // Handle nested property access: $row->customer->first_name
        if (preg_match('/^\$row->([a-zA-Z0-9_]+)->([a-zA-Z0-9_]+)$/', $expression, $nestedMatches)) {
            $value = $this->getNestedPropertyValue($row, $nestedMatches[1] . '.' . $nestedMatches[2]);
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        // Handle method calls: $row->hasFlight()
        if (preg_match('/^\$row->([a-zA-Z0-9_]+)\(\)$/', $expression, $methodMatches)) {
            $methodName = $methodMatches[1];
            if (is_object($row) && method_exists($row, $methodName)) {
                try {
                    $result = $row->$methodName();
                    return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
                } catch (\Exception $e) {
                    Log::error("Method call error: {$methodName}", ['error' => $e->getMessage()]);
                    return '[Method Error]';
                }
            }
            return '[Method Not Found]';
        }

        // Handle static class calls: \Carbon\Carbon::parse($row->date)->format("d M Y")
        if (preg_match('/\\\\([a-zA-Z0-9\\\\]+)::([a-zA-Z0-9_]+)\(\$row->([a-zA-Z0-9_]+)\)->([a-zA-Z0-9_]+)\("([^"]+)"\)/', $expression, $staticMatches)) {
            $className = '\\' . $staticMatches[1];
            $staticMethod = $staticMatches[2];
            $property = $staticMatches[3];
            $chainMethod = $staticMatches[4];
            $formatString = $staticMatches[5];

            if (class_exists($className) && method_exists($className, $staticMethod)) {
                try {
                    $value = $this->getRowPropertyValue($row, $property);
                    $result = $className::$staticMethod($value)->$chainMethod($formatString);
                    return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
                } catch (\Exception $e) {
                    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
                }
            }
            return '[Class Not Found]';
        }

        // Handle concatenation: $row->customer->first_name . " " . $row->customer->last_name
        if (str_contains($expression, ' . ')) {
            $parts = explode(' . ', $expression);
            $result = '';

            foreach ($parts as $part) {
                $part = trim($part);
                if (str_starts_with($part, '"') && str_ends_with($part, '"')) {
                    // String literal
                    $result .= trim($part, '"');
                } else {
                    // Evaluate as expression
                    $result .= $this->evaluateExpression($part, $row);
                }
            }

            return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }

        // Handle complex expressions with multiple parts (fallback for simple cases)
        if (str_contains($expression, '$row->')) {
            // Replace all $row->property references with actual values
            $processedExpression = preg_replace_callback('/\$row->([a-zA-Z0-9_]+)/', function ($matches) use ($row) {
                $value = $this->getRowPropertyValue($row, $matches[1]);
                return is_numeric($value) ? $value : '"' . addslashes($value) . '"';
            }, $expression);

            // Safely evaluate simple expressions (only allow basic comparisons and ternary)
            if (preg_match('/^[0-9"\'\s\?\:=<>!&|()]+$/', $processedExpression)) {
                try {
                    // This is still potentially dangerous - consider replacing with safer evaluation
                    return htmlspecialchars($processedExpression, ENT_QUOTES, 'UTF-8');
                } catch (\Exception $e) {
                    return htmlspecialchars($processedExpression, ENT_QUOTES, 'UTF-8');
                }
            }
        }

        return htmlspecialchars($expression, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Safely evaluate expressions without using eval()
     * SECURITY: Replaces dangerous eval() usage with safe alternatives
     */
    protected function evaluateExpressionSafely($expression, $row)
    {
        // Remove return statement if present
        $expression = preg_replace('/^return\s+/', '', trim($expression));
        $expression = rtrim($expression, ';');

        // For simple ternary operations: condition ? value1 : value2
        if (preg_match('/^(.+?)\s*\?\s*(.+?)\s*:\s*(.+)$/', $expression, $matches)) {
            $condition = trim($matches[1]);
            $trueValue = trim($matches[2], '"\'');
            $falseValue = trim($matches[3], '"\'');

            if ($this->evaluateConditionSafely($condition, $row)) {
                return $trueValue;
            } else {
                return $falseValue;
            }
        }

        // For simple comparisons that return boolean values
        if ($this->evaluateConditionSafely($expression, $row)) {
            return 'true';
        }

        return 'false';
    }

    /**
     * Safely evaluate boolean conditions without eval()
     * SECURITY: Replaces dangerous eval() usage
     */
    protected function evaluateConditionSafely($condition, $row): bool
    {
        // Basic comparison patterns
        if (preg_match('/^"?([^"]+)"?\s*(==|!=|>|<|>=|<=)\s*"?([^"]+)"?$/', $condition, $matches)) {
            $left = trim($matches[1], '"\'');
            $operator = $matches[2];
            $right = trim($matches[3], '"\'');

            // Convert to numbers if both are numeric
            if (is_numeric($left) && is_numeric($right)) {
                $left = (float) $left;
                $right = (float) $right;
            }

            switch ($operator) {
                case '==':
                    return $left == $right;
                case '!=':
                    return $left != $right;
                case '>':
                    return $left > $right;
                case '<':
                    return $left < $right;
                case '>=':
                    return $left >= $right;
                case '<=':
                    return $left <= $right;
            }
        }

        // Simple boolean checks like "active" or "1"
        $condition = trim($condition, '"\'');
        if ($condition === 'true' || $condition === '1') {
            return true;
        }
        if ($condition === 'false' || $condition === '0' || $condition === '') {
            return false;
        }

        // Default to false for safety
        return false;
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
     * Get columns needed for raw templates
     */
    protected function getColumnsNeededForRawTemplates()
    {
        $neededColumns = [];

        foreach ($this->columns as $column) {
            if (isset($column['raw'])) {
                // Extract column references from raw templates
                preg_match_all('/\$row->([a-zA-Z0-9_]+)/', $column['raw'], $matches);
                if (!empty($matches[1])) {
                    $neededColumns = array_merge($neededColumns, $matches[1]);
                }
            }
        }

        return array_unique($neededColumns);
    }
}
