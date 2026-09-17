<?php

namespace ArtflowStudio\Table\Concerns;

use ArtflowStudio\Table\Support\RelationPath;
use ArtflowStudio\Table\Support\SchemaCache;

/**
 * Validates the client-controlled inputs that are actually checked:
 * column names, relation strings and sort direction.
 *
 * Consolidated in 1.6.0 from: HasDataValidation, HasUnifiedValidation, HasUtilities, HasRelationships.
 * The Laravel-Validator clone and assorted dead helpers that lived alongside
 * these were removed — none had a live caller (see audit finding 2).
 *
 * NOTE: the action-authorization chain (validateAction/getActionConfig/...)
 * and validateJsonPath() are also dead (their only callers live in the
 * also-dead HandlesActions::executeAction()/performAction() and
 * SortsRows::applySortByJson()), but are kept here until those callers are
 * removed in audit findings 3 and 8 respectively — deleting them earlier
 * leaves a dangling $this-> call that UndefinedMethodGuardTest correctly
 * flags, even though the call is unreachable.
 */
trait ValidatesInput
{
    protected $validationErrors = [];

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
     * Get current status for status-based validation
     */
    protected function getCurrentStatus(): string
    {
        // Override in specific implementations
        return 'active';
    }

    /**
     * Is this an action the host application declared?
     */
    protected function isActionValid($action): bool
    {
        return $this->getActionConfig($action) !== null;
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

    /**
     * Validate sort direction
     */
    protected function validateSortDirection($direction): string
    {
        $direction = strtolower($direction);

        return in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
    }
}
