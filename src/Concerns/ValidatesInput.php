<?php

namespace ArtflowStudio\Table\Concerns;

use ArtflowStudio\Table\Support\RelationPath;
use ArtflowStudio\Table\Support\SchemaCache;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Validates everything that arrives from the client: column names,
 * filter values, sort directions, relation strings and actions.
 *
 * Consolidated in 1.6.0 from: HasDataValidation, HasUnifiedValidation, HasUtilities, HasRelationships.
 */
trait ValidatesInput
{
    protected $validationErrors = [];

    protected $validationWarnings = [];

    protected $lastValidationResult = null;

    /**
     * Add validation error
     */
    protected function addValidationError($field, $message)
    {
        if (! isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }

        $this->validationErrors[$field][] = $message;
    }

    /**
     * Add validation warning
     */
    protected function addValidationWarning($field, $message)
    {
        if (! isset($this->validationWarnings[$field])) {
            $this->validationWarnings[$field] = [];
        }

        $this->validationWarnings[$field][] = $message;
    }

    /**
     * Apply specific validation rule
     * From HasValidation
     */
    protected function applyValidationRule($column, $value, $ruleName, $params = []): bool
    {
        switch ($ruleName) {
            case 'required':
                if ($this->isEmpty($value)) {
                    $this->addValidationError($column, "The {$column} field is required.");

                    return false;
                }
                break;

            case 'string':
                if (! is_string($value) && ! is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a string.");

                    return false;
                }
                break;

            case 'numeric':
                if (! is_numeric($value) && ! is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be numeric.");

                    return false;
                }
                break;

            case 'integer':
                if (! is_int($value) && ! ctype_digit($value) && ! is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be an integer.");

                    return false;
                }
                break;

            case 'email':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL) && ! is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a valid email address.");

                    return false;
                }
                break;

            case 'url':
                if (! filter_var($value, FILTER_VALIDATE_URL) && ! is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a valid URL.");

                    return false;
                }
                break;

            case 'date':
                if (! $this->isValidDate($value) && ! is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a valid date.");

                    return false;
                }
                break;

            case 'min':
                $minValue = $params[0] ?? 0;
                if (is_string($value) && strlen($value) < $minValue) {
                    $this->addValidationError($column, "The {$column} field must be at least {$minValue} characters.");

                    return false;
                } elseif (is_numeric($value) && $value < $minValue) {
                    $this->addValidationError($column, "The {$column} field must be at least {$minValue}.");

                    return false;
                }
                break;

            case 'max':
                $maxValue = $params[0] ?? 255;
                if (is_string($value) && strlen($value) > $maxValue) {
                    $this->addValidationError($column, "The {$column} field may not be greater than {$maxValue} characters.");

                    return false;
                } elseif (is_numeric($value) && $value > $maxValue) {
                    $this->addValidationError($column, "The {$column} field may not be greater than {$maxValue}.");

                    return false;
                }
                break;

            case 'in':
                $allowedValues = $params;
                if (! in_array($value, $allowedValues) && ! is_null($value)) {
                    $this->addValidationError($column, "The selected {$column} is invalid.");

                    return false;
                }
                break;

            case 'unique':
                if (! $this->isValueUnique($column, $value)) {
                    $this->addValidationError($column, "The {$column} has already been taken.");

                    return false;
                }
                break;

            default:
                // Unknown rule, add warning
                $this->addValidationWarning($column, "Unknown validation rule: {$ruleName}");
                break;
        }

        return true;
    }

    /**
     * Generate basic cache key for utilities (renamed to avoid collision with HasUnifiedCaching)
     */
    protected function generateBasicCacheKey($suffix = '')
    {
        $modelClass = is_string($this->model) ? $this->model : get_class($this->model);
        $baseKey = 'datatable_'.md5($modelClass.'_'.($this->tableId ?? 'default'));

        return $suffix ? $baseKey.'_'.$suffix : $baseKey;
    }

    /**
     * Resolve an action's configuration array, or null when it is not declared.
     *
     * Actions may be declared either keyed (`'edit' => [...]`) or as a bare list
     * of raw HTML strings; only the former has a config to return.
     */
    protected function getActionConfig($action): ?array
    {
        if (! is_string($action) || $action === '') {
            return null;
        }

        $config = ($this->actions ?? [])[$action] ?? null;

        return is_array($config) ? $config : null;
    }

    /**
     * Resolve a column's configuration array, or null when it is not declared.
     */
    protected function getColumnConfig($column): ?array
    {
        if (! is_string($column) || $column === '') {
            return null;
        }

        $columns = $this->columns ?? [];

        if (isset($columns[$column]) && is_array($columns[$column])) {
            return $columns[$column];
        }

        foreach ($columns as $config) {
            if (is_array($config) && ($config['key'] ?? null) === $column) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Get current status for status-based validation
     */
    protected function getCurrentStatus(): string
    {
        // Override in specific implementations
        return 'active';
    }

    /**
     * Get relation type
     */
    protected function getRelationType($relationName): ?string
    {
        try {
            $modelInstance = new ($this->model);
            $relation = $modelInstance->$relationName();

            $relationType = class_basename(get_class($relation));

            return $relationType;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Is this an action the host application declared?
     */
    protected function isActionValid($action): bool
    {
        return $this->getActionConfig($action) !== null;
    }

    /**
     * Is this a column the host application declared?
     *
     * Matches on the array key or on an explicit `key` entry, because the two
     * differ for relation/computed columns.
     */
    protected function isColumnValid($column): bool
    {
        return $this->getColumnConfig($column) !== null;
    }

    /**
     * Check if value is empty for validation purposes
     */
    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Check if date string is valid
     */
    protected function isValidDate($date): bool
    {
        if (! is_string($date)) {
            return false;
        }

        return (bool) strtotime($date);
    }

    /**
     * Check if value is unique in database (placeholder)
     */
    protected function isValueUnique($column, $value): bool
    {
        // In real implementation, this would check database
        // For now, return true to avoid breaking existing functionality
        return true;
    }

    /**
     * Check if relation exists on model
     */
    protected function relationExists($relationName): bool
    {
        try {
            $modelInstance = new ($this->model);

            if (! method_exists($modelInstance, $relationName)) {
                return false;
            }

            $relation = $modelInstance->$relationName();

            return $relation instanceof Relation;
        } catch (\Exception $e) {
            return false;
        }
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
     * Sanitize HTML content for raw templates
     */
    protected function sanitizeHtmlContent($content): string
    {
        if (! is_string($content)) {
            return '';
        }

        // Allow basic HTML tags but escape dangerous ones
        $allowedTags = '<p><br><strong><em><span><div><a><img><ul><ol><li>';

        return strip_tags($content, $allowedTags);
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
     * Check if user can perform action
     * Consolidated from HasPermissions
     */
    public function userCanPerformAction($action): bool
    {
        $actionConfig = $this->getActionConfig($action);
        $requiredPermissions = $actionConfig['permissions'] ?? [];

        if (empty($requiredPermissions)) {
            return true; // No specific permissions required
        }

        foreach ($requiredPermissions as $permission) {
            if (! $this->userHasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has specific permission
     * From HasPermissions
     */
    protected function userHasPermission($permission): bool
    {
        // In real application, this would check against user's actual permissions
        // For now, return true to avoid breaking existing functionality
        return true;
    }

    /**
     * Validate action permission and requirements
     * Consolidated from HasActionValidation and HasPermissions
     */
    public function validateAction($action, $data = [])
    {
        // Check if action exists
        if (! $this->isActionValid($action)) {
            $this->addValidationError('action', "Action '{$action}' is not valid or does not exist.");

            return false;
        }

        // Check permissions
        if (! $this->userCanPerformAction($action)) {
            $this->addValidationError('permission', "You do not have permission to perform action '{$action}'.");

            return false;
        }

        // Check action-specific requirements
        if (! $this->validateActionRequirements($action, $data)) {
            return false;
        }

        // Check business logic constraints
        if (! $this->validateActionBusinessLogic($action, $data)) {
            return false;
        }

        return true;
    }

    /**
     * Validate business logic for action
     * From HasActionValidation
     */
    protected function validateActionBusinessLogic($action, $data): bool
    {
        // Override in specific implementations for custom business logic
        return true;
    }

    /**
     * Validate specific action requirement
     * From HasActionValidation
     */
    protected function validateActionRequirement($action, $requirement, $config, $data): bool
    {
        switch ($requirement) {
            case 'selected_items':
                $minItems = $config['min'] ?? 1;
                $maxItems = $config['max'] ?? null;
                $selectedCount = count($this->selectedRows ?? []);

                if ($selectedCount < $minItems) {
                    $this->addValidationError('selection', "You must select at least {$minItems} item(s) to perform '{$action}'.");

                    return false;
                }

                if ($maxItems && $selectedCount > $maxItems) {
                    $this->addValidationError('selection', "You can select at most {$maxItems} item(s) to perform '{$action}'.");

                    return false;
                }
                break;

            case 'confirmation':
                if (empty($data['confirmed'])) {
                    $this->addValidationError('confirmation', "Action '{$action}' requires confirmation.");

                    return false;
                }
                break;

            case 'fields':
                $requiredFields = $config['required'] ?? [];
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        $this->addValidationError($field, "The {$field} field is required for action '{$action}'.");

                        return false;
                    }
                }
                break;

            case 'status':
                $allowedStatuses = $config['allowed'] ?? [];
                $currentStatus = $this->getCurrentStatus();

                if (! in_array($currentStatus, $allowedStatuses)) {
                    $this->addValidationError('status', "Action '{$action}' cannot be performed with current status '{$currentStatus}'.");

                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Validate action requirements
     * From HasActionValidation
     */
    protected function validateActionRequirements($action, $data): bool
    {
        $actionConfig = $this->getActionConfig($action);
        $requirements = $actionConfig['requirements'] ?? [];

        foreach ($requirements as $requirement => $config) {
            if (! $this->validateActionRequirement($action, $requirement, $config, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate column input and format
     * Consolidated from HasValidation
     */
    public function validateColumn($column, $value)
    {
        if (! $this->isColumnValid($column)) {
            $this->addValidationError($column, "Column '{$column}' is not valid or does not exist.");

            return false;
        }

        $columnConfig = $this->getColumnConfig($column);
        $validationRules = $columnConfig['validation'] ?? [];

        if (empty($validationRules)) {
            return true; // No validation rules defined
        }

        return $this->validateValueAgainstRules($column, $value, $validationRules);
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
     * Validate JSON path format
     */
    protected function validateJsonPath($jsonPath): bool
    {
        if (! is_string($jsonPath)) {
            return false;
        }

        // Should be alphanumeric with dots for nesting
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $jsonPath) === 1;
    }

    /**
     * Validate all relation columns
     */
    protected function validateRelationColumns(): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'warnings' => [],
        ];

        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                $parsed = $this->parseRelationString($relationString);

                if (! $parsed['valid']) {
                    $results['invalid'][$columnKey] = 'Invalid relation string format';

                    continue;
                }

                // Check if relation exists
                $firstRelation = $parsed['relation_chain'][0];
                if (! $this->relationExists($firstRelation)) {
                    $results['invalid'][$columnKey] = "Relation '{$firstRelation}' does not exist on model";

                    continue;
                }

                $results['valid'][$columnKey] = [
                    'relation_string' => $relationString,
                    'parsed' => $parsed,
                    'relation_type' => $this->getRelationType($firstRelation),
                ];

                // Add warnings for nested relations
                if ($parsed['nested']) {
                    $results['warnings'][$columnKey] = 'Nested relations may have limited functionality';
                }
            }
        }

        return $results;
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
     * Validate value against specific rules
     * From HasValidation
     */
    protected function validateValueAgainstRules($column, $value, $rules): bool
    {
        $valid = true;

        foreach ($rules as $rule => $ruleConfig) {
            if (is_numeric($rule)) {
                // Simple rule like ['required', 'string']
                $ruleName = $ruleConfig;
                $ruleParams = [];
            } else {
                // Complex rule like ['max' => 255]
                $ruleName = $rule;
                $ruleParams = is_array($ruleConfig) ? $ruleConfig : [$ruleConfig];
            }

            if (! $this->applyValidationRule($column, $value, $ruleName, $ruleParams)) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Does this column exist on the model?
     *
     * Standard timestamps and the primary key always pass; otherwise the
     * column must be fillable or physically present on the table. The schema
     * lookup is cached — it sits on a hot validation path.
     */
    protected function isValidColumn($column): bool
    {
        if (! is_string($column) || $column === '') {
            return false;
        }

        try {
            if (! is_string($this->model) || ! class_exists($this->model)) {
                return array_key_exists($column, $this->columns ?? []);
            }

            if (in_array($column, ['id', 'created_at', 'updated_at'], true)) {
                return true;
            }

            $modelInstance = new ($this->model);

            if (in_array($column, $modelInstance->getFillable(), true)) {
                return true;
            }

            return SchemaCache::hasColumn($modelInstance, $column);
        } catch (\Throwable) {
            // An unresolvable model must not fail the render; fall back to
            // "is it a column the developer declared?".
            return array_key_exists($column, $this->columns ?? []);
        }
    }

    /**
     * Is this a well-formed `relation:column` (or `a.b:column`) string?
     *
     * Delegates to the single parser so every caller agrees on what is valid —
     * the package previously had three separate implementations of this check.
     */
    protected function validateRelationString($relationString): bool
    {
        return RelationPath::isValid(
            is_string($relationString) ? $relationString : null
        );
    }
}
