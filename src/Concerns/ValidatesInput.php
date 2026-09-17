<?php

namespace ArtflowStudio\Table\Concerns;

use ArtflowStudio\Table\Support\RelationPath;
use ArtflowStudio\Table\Support\SchemaCache;

/**
 * Validates the client-controlled inputs that are actually checked:
 * column names, relation strings and sort direction.
 *
 * Consolidated in 1.6.0 from: HasDataValidation, HasUnifiedValidation, HasUtilities, HasRelationships.
 * The Laravel-Validator clone, the action-authorization chain
 * (validateAction/getActionConfig/userCanPerformAction/...) and assorted
 * dead helpers that lived alongside these were removed — none had a live
 * caller once HandlesActions::executeAction()/performAction(), their only
 * callers, were removed in audit finding 3.
 *
 * NOTE: validateJsonPath() is also dead (its only caller,
 * SortsRows::applySortByJson(), is dead too), but is kept here until that
 * caller is removed in audit finding 8 — deleting it earlier leaves a
 * dangling $this-> call that UndefinedMethodGuardTest correctly flags, even
 * though the call is unreachable.
 */
trait ValidatesInput
{
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
