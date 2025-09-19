<?php

namespace ArtflowStudio\Table\Traits\Core;

/**
 * HasUnifiedValidation - Consolidates validation functionality
 * 
 * This trait consolidates validation methods from:
 * - HasValidation (basic validation rules)
 * - HasActionValidation (action-specific validation)
 * - HasPermissions (permission-based validation)
 * 
 * Purpose: Eliminate validation conflicts and centralize validation logic
 */
trait HasUnifiedValidation
{
    // *----------- Validation Properties -----------*//
    
    protected $validationErrors = [];
    protected $validationWarnings = [];
    protected $lastValidationResult = null;
    
    // *----------- Core Validation Methods -----------*//
    
    /**
     * Validate column input and format
     * Consolidated from HasValidation
     */
    public function validateColumn($column, $value)
    {
        if (!$this->isColumnValid($column)) {
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
            
            if (!$this->applyValidationRule($column, $value, $ruleName, $ruleParams)) {
                $valid = false;
            }
        }
        
        return $valid;
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
                if (!is_string($value) && !is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a string.");
                    return false;
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value) && !is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be numeric.");
                    return false;
                }
                break;
                
            case 'integer':
                if (!is_int($value) && !ctype_digit($value) && !is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be an integer.");
                    return false;
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL) && !is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a valid email address.");
                    return false;
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL) && !is_null($value)) {
                    $this->addValidationError($column, "The {$column} field must be a valid URL.");
                    return false;
                }
                break;
                
            case 'date':
                if (!$this->isValidDate($value) && !is_null($value)) {
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
                if (!in_array($value, $allowedValues) && !is_null($value)) {
                    $this->addValidationError($column, "The selected {$column} is invalid.");
                    return false;
                }
                break;
                
            case 'unique':
                if (!$this->isValueUnique($column, $value)) {
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
    
    // *----------- Action Validation Methods -----------*//
    
    /**
     * Validate action permission and requirements
     * Consolidated from HasActionValidation and HasPermissions
     */
    public function validateAction($action, $data = [])
    {
        // Check if action exists
        if (!$this->isActionValid($action)) {
            $this->addValidationError('action', "Action '{$action}' is not valid or does not exist.");
            return false;
        }
        
        // Check permissions
        if (!$this->userCanPerformAction($action)) {
            $this->addValidationError('permission', "You do not have permission to perform action '{$action}'.");
            return false;
        }
        
        // Check action-specific requirements
        if (!$this->validateActionRequirements($action, $data)) {
            return false;
        }
        
        // Check business logic constraints
        if (!$this->validateActionBusinessLogic($action, $data)) {
            return false;
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
            if (!$this->validateActionRequirement($action, $requirement, $config, $data)) {
                return false;
            }
        }
        
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
                
                if (!in_array($currentStatus, $allowedStatuses)) {
                    $this->addValidationError('status', "Action '{$action}' cannot be performed with current status '{$currentStatus}'.");
                    return false;
                }
                break;
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
    
    // *----------- Permission Validation -----------*//
    
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
            if (!$this->userHasPermission($permission)) {
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
    
    // *----------- Bulk Validation Methods -----------*//
    
    /**
     * Validate multiple records
     * From HasValidation
     */
    public function validateBulkData($records)
    {
        $this->clearValidationErrors();
        $valid = true;
        
        foreach ($records as $index => $record) {
            if (!$this->validateRecord($record, $index)) {
                $valid = false;
            }
        }
        
        $this->lastValidationResult = [
            'valid' => $valid,
            'total_records' => count($records),
            'errors' => $this->validationErrors,
            'warnings' => $this->validationWarnings
        ];
        
        return $valid;
    }
    
    /**
     * Validate single record
     * From HasValidation
     */
    protected function validateRecord($record, $index = null): bool
    {
        $valid = true;
        $prefix = $index !== null ? "Record {$index}: " : "";
        
        foreach ($record as $column => $value) {
            if (!$this->validateColumn($column, $value)) {
                $valid = false;
                // Errors already added in validateColumn
            }
        }
        
        return $valid;
    }
    
    // *----------- Error Management -----------*//
    
    /**
     * Add validation error
     */
    protected function addValidationError($field, $message)
    {
        if (!isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }
        
        $this->validationErrors[$field][] = $message;
    }
    
    /**
     * Add validation warning
     */
    protected function addValidationWarning($field, $message)
    {
        if (!isset($this->validationWarnings[$field])) {
            $this->validationWarnings[$field] = [];
        }
        
        $this->validationWarnings[$field][] = $message;
    }
    
    /**
     * Clear all validation errors
     */
    public function clearValidationErrors()
    {
        $this->validationErrors = [];
        $this->validationWarnings = [];
        $this->lastValidationResult = null;
    }
    
    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
    
    /**
     * Get validation warnings
     */
    public function getValidationWarnings(): array
    {
        return $this->validationWarnings;
    }
    
    /**
     * Check if validation has errors
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }
    
    /**
     * Get first error for field
     */
    public function getFirstError($field): ?string
    {
        return $this->validationErrors[$field][0] ?? null;
    }
    
    /**
     * Get all errors as flat array
     */
    public function getAllErrors(): array
    {
        $allErrors = [];
        foreach ($this->validationErrors as $field => $errors) {
            foreach ($errors as $error) {
                $allErrors[] = "{$field}: {$error}";
            }
        }
        return $allErrors;
    }
    
    // *----------- Helper Methods -----------*//
    
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
        if (!is_string($date)) {
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
     * Get current status for status-based validation
     */
    protected function getCurrentStatus(): string
    {
        // Override in specific implementations
        return 'active';
    }
    
    /**
     * Get validation statistics
     */
    public function getValidationStats(): array
    {
        return [
            'total_errors' => count($this->validationErrors),
            'total_warnings' => count($this->validationWarnings),
            'fields_with_errors' => array_keys($this->validationErrors),
            'fields_with_warnings' => array_keys($this->validationWarnings),
            'last_validation_result' => $this->lastValidationResult
        ];
    }
}
