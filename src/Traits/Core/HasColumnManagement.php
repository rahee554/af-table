<?php

namespace ArtflowStudio\Table\Traits\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Trait HasColumnManagement
 * 
 * Handles all column-related operations including initialization,
 * calculation, validation, and management
 * Consolidates column logic moved from main DatatableTrait
 */
trait HasColumnManagement
{
    /**
     * Initialize columns properly, handling raw-only columns without database keys
     * Moved from DatatableTrait.php line 1443
     */
    protected function initializeColumns($columns): array
    {
        // Re-key columns by 'key' or 'function' with fallback - optimized version
        // For JSON columns, use a unique identifier that includes the JSON path
        $reKeyedColumns = [];
        foreach ($columns as $index => $column) {
            if (isset($column['key'])) {
                $reKeyedColumns[$column['key']] = $column;
            } elseif (isset($column['function'])) {
                $reKeyedColumns['function_' . $index] = $column;
            } elseif (isset($column['json'])) {
                $jsonKey = 'json_' . $column['json'] . '_' . ($column['json_path'] ?? 'data');
                $reKeyedColumns[$jsonKey] = $column;
            } elseif (isset($column['relation'])) {
                $relationKey = 'relation_' . str_replace([':', '.'], '_', $column['relation']);
                $reKeyedColumns[$relationKey] = $column;
            } else {
                // Mark raw-only columns (no key, function, json, or relation) 
                $fallbackKey = 'column_' . $index;
                $column['_raw_only'] = true; // Mark as raw-only for later identification
                $reKeyedColumns[$fallbackKey] = $column;
            }
        }

        return $reKeyedColumns;
    }

    /**
     * Initialize column configuration for performance
     * Moved from DatatableTrait.php line 1469
     */
    protected function initializeColumnConfiguration($columns)
    {
        // Pre-calculate relations and select columns for better performance
        $this->cachedRelations = $this->calculateRequiredRelations($columns);
        $this->cachedSelectColumns = $this->calculateSelectColumns($columns);
    }

    /**
     * Calculate required relations from columns
     * Moved from DatatableTrait.php line 1479
     */
    protected function calculateRequiredRelations($columns): array
    {
        $relations = [];

        foreach ($columns as $columnKey => $column) {
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                if ($this->validateRelationString($relationString)) {
                    $relationParts = explode(':', $relationString);
                    $relationName = $relationParts[0];
                    
                    // Support nested relations (e.g., "user.profile:name")
                    if (str_contains($relationName, '.')) {
                        $nestedRelations = explode('.', $relationName);
                        $currentRelation = '';
                        foreach ($nestedRelations as $i => $nestedRelation) {
                            $currentRelation .= ($i > 0 ? '.' : '') . $nestedRelation;
                            $relations[] = $currentRelation;
                        }
                    } else {
                        $relations[] = $relationName;
                    }
                }
            }
        }

        // Include filter relations
        foreach ($this->filters ?? [] as $filterKey => $filterConfig) {
            if (isset($filterConfig['relation'])) {
                $relationString = $filterConfig['relation'];
                if ($this->validateRelationString($relationString)) {
                    $relationParts = explode(':', $relationString);
                    $relationName = $relationParts[0];
                    
                    if (str_contains($relationName, '.')) {
                        $nestedRelations = explode('.', $relationName);
                        $currentRelation = '';
                        foreach ($nestedRelations as $i => $nestedRelation) {
                            $currentRelation .= ($i > 0 ? '.' : '') . $nestedRelation;
                            $relations[] = $currentRelation;
                        }
                    } else {
                        $relations[] = $relationName;
                    }
                }
            }
        }

        return array_unique($relations);
    }

    /**
     * Calculate select columns for performance
     * Moved from DatatableTrait.php line 1532
     */
    protected function calculateSelectColumns($columns): array
    {
        // Get table name from model to avoid ambiguous column references
        $modelInstance = new ($this->model);
        $tableName = $modelInstance->getTable();
        
        $selects = [$tableName . '.id']; // Always include ID with table prefix

        // DEBUG: Log what we're processing
        Log::info('HasColumnManagement::calculateSelectColumns called', [
            'columns_keys' => array_keys($columns),
        ]);

        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = $tableName . '.updated_at';
        }

        foreach ($columns as $columnKey => $column) {
            // Skip raw-only columns that don't correspond to database columns
            if (isset($column['_raw_only']) && $column['_raw_only'] === true) {
                Log::debug('Skipping raw-only column', ['column_key' => $columnKey]);
                continue;
            }
            
            // For regular database columns
            if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $selects[] = $tableName . '.' . $column['key'];
                Log::debug('Added regular column', ['column' => $column['key']]);
            }
            
            // For JSON columns - include the base JSON column
            if (isset($column['json']) && $this->isValidColumn($column['json'])) {
                $selects[] = $tableName . '.' . $column['json'];
                Log::debug('Added JSON column', ['column' => $column['json']]);
            }

            // For relation columns - add foreign keys if they exist and are valid
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                if ($this->validateRelationString($relationString)) {
                    $relationParts = explode(':', $relationString);
                    $relationName = $relationParts[0];
                    
                    // Add foreign key for this relation if it exists
                    $foreignKey = $relationName . '_id';
                    if ($this->isValidColumn($foreignKey)) {
                        $selects[] = $tableName . '.' . $foreignKey;
                        Log::debug('Added foreign key for relation', ['foreign_key' => $foreignKey]);
                    }
                }
            }
        }

        // Add columns needed for actions and raw templates
        $actionColumns = $this->getColumnsNeededForActions();
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();

        // Filter out invalid columns
        $validActionColumns = array_filter($actionColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        $validRawTemplateColumns = array_filter($rawTemplateColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        // Ensure action columns are always included, even if not in $this->columns
        foreach ($validActionColumns as $col) {
            $selects[] = $tableName . '.' . $col;
        }
        // Same for raw template columns
        foreach ($validRawTemplateColumns as $col) {
            $selects[] = $tableName . '.' . $col;
        }

        return array_unique($selects);
    }

    /**
     * Get columns needed for actions
     * Moved from DatatableTrait.php line 2168
     */
    protected function getColumnsNeededForActions()
    {
        $neededColumns = [];

        foreach ($this->actions as $action) {
            // Extract column names from action conditions or parameters
            if (isset($action['condition'])) {
                // Parse condition string for $row->column references
                preg_match_all('/\$row->([a-zA-Z0-9_]+)/', $action['condition'], $matches);
                if (!empty($matches[1])) {
                    $neededColumns = array_merge($neededColumns, $matches[1]);
                }
            }

            // Check for route parameters that might need specific columns
            if (isset($action['route_params'])) {
                foreach ($action['route_params'] as $param) {
                    if (is_string($param) && str_starts_with($param, 'id')) {
                        $neededColumns[] = 'id';
                    }
                }
            }
        }

        return array_unique($neededColumns);
    }

    /**
     * Get columns needed for raw templates
     * Moved from DatatableTrait.php line 2188
     */
    protected function getColumnsNeededForRawTemplates()
    {
        $neededColumns = [];

        foreach ($this->columns as $column) {
            if (isset($column['raw'])) {
                $rawTemplate = $column['raw'];
                
                // Extract all $row->column references from the raw template
                preg_match_all('/\$row->([a-zA-Z0-9_]+)/', $rawTemplate, $matches);
                if (!empty($matches[1])) {
                    $neededColumns = array_merge($neededColumns, $matches[1]);
                }

                // Also check for nested property access like $row->user->name
                preg_match_all('/\$row->([a-zA-Z0-9_]+)->([a-zA-Z0-9_]+)/', $rawTemplate, $nestedMatches);
                if (!empty($nestedMatches[1])) {
                    // Add the foreign key columns
                    foreach ($nestedMatches[1] as $relation) {
                        $foreignKey = $relation . '_id';
                        $neededColumns[] = $foreignKey;
                    }
                }
            }
        }

        return array_unique($neededColumns);
    }

    /**
     * Get optimal sort column for performance
     * Moved from DatatableTrait.php line 1644
     */
    protected function getOptimalSortColumn(): ?string
    {
        // Prioritize updated_at for index sorting if it exists and index is enabled
        if ($this->index && $this->isValidColumn('updated_at')) {
            return 'updated_at';
        }

        // Find first indexed column for better sort performance
        $indexedColumns = ['id', 'created_at', 'updated_at']; // Common indexed columns

        foreach ($this->columns as $column) {
            if (isset($column['key']) && in_array($column['key'], $indexedColumns) && $this->isValidColumn($column['key'])) {
                return $column['key'];
            }
        }

        // Fallback to first sortable column
        foreach ($this->columns as $column) {
            if (isset($column['key']) && (!isset($column['sortable']) || $column['sortable'] !== false)) {
                if ($this->isValidColumn($column['key'])) {
                    return $column['key'];
                }
            }
        }

        return null;
    }

    /**
     * Get valid select columns based on current visibility
     * Moved from DatatableTrait.php line 1725
     */
    protected function getValidSelectColumns(): array
    {
        $modelInstance = new ($this->model);
        $parentTable = $modelInstance->getTable();

        $selects = [$parentTable . '.id']; // Always include ID with table qualifier

        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = $parentTable . '.updated_at';
        }

        foreach ($this->columns as $columnKey => $column) {
            // Only include visible columns
            if (!($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // For regular database columns
            if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $selects[] = $parentTable . '.' . $column['key'];
            }
            
            // For JSON columns - include the base JSON column
            if (isset($column['json']) && $this->isValidColumn($column['json'])) {
                $selects[] = $parentTable . '.' . $column['json'];
            }

            // For relation columns - add foreign keys if they exist and are valid
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                if ($this->validateRelationString($relationString)) {
                    $relationParts = explode(':', $relationString);
                    $relationName = $relationParts[0];
                    
                    // Add foreign key for this relation if it exists
                    $foreignKey = $relationName . '_id';
                    if ($this->isValidColumn($foreignKey)) {
                        $selects[] = $parentTable . '.' . $foreignKey;
                    }
                }
            }
        }

        // Add columns needed for actions and raw templates (with validation)
        $actionColumns = $this->getColumnsNeededForActions();
        $rawTemplateColumns = $this->getColumnsNeededForRawTemplates();

        // Filter out invalid columns
        $validActionColumns = array_filter($actionColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        $validRawTemplateColumns = array_filter($rawTemplateColumns, function ($col) {
            return $this->isValidColumn($col);
        });

        // Ensure action columns are always included, even if not in $this->columns - with table qualifier
        foreach ($validActionColumns as $col) {
            $selects[] = $parentTable . '.' . $col;
        }
        // Same for raw template columns - with table qualifier
        foreach ($validRawTemplateColumns as $col) {
            $selects[] = $parentTable . '.' . $col;
        }

        return array_unique($selects);
    }

    /**
     * Check if column is valid in database
     * Moved from DatatableTrait.php line 1859
     */
    protected function isValidColumn($column): bool
    {
        try {
            $modelInstance = new ($this->model);
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($modelInstance->getTable());
            return in_array($column, $columns);
        } catch (\Exception $e) {
            Log::error('Column validation error: ' . $e->getMessage(), ['column' => $column]);
            return false;
        }
    }

    /**
     * Check if column is allowed for operations
     * Moved from DatatableTrait.php line 1875
     */
    protected function isAllowedColumn($column)
    {
        // Allow 'updated_at' for index column sorting
        if ($column === 'updated_at') {
            return true;
        }

        return array_key_exists($column, $this->columns);
    }

    /**
     * Clear phantom column cache and reset column configuration
     * Moved from DatatableTrait.php line 1106
     */
    public function clearPhantomColumnCache()
    {
        // Clear session-based column visibility
        $this->clearColumnVisibilitySession();

        // Clear cached column selections
        $this->cachedSelectColumns = null;
        $this->cachedRelations = null;

        // Reset visible columns to default
        $this->visibleColumns = $this->getDefaultVisibleColumns();

        // Clear any cache keys that might contain phantom columns
        if (method_exists($this, 'clearCacheByPattern')) {
            $cachePattern = "datatable_columns_{$this->tableId}_*";
            $this->clearCacheByPattern($cachePattern);
        }

        // Force recalculation of select columns
        if (!empty($this->columns)) {
            $this->initializeColumnConfiguration($this->columns);
        }

        Log::info('Phantom column cache cleared', [
            'visible_columns' => $this->visibleColumns,
        ]);
    }

    /**
     * Get default visible columns
     * Moved from DatatableTrait.php line 1207
     */
    protected function getDefaultVisibleColumns()
    {
        return $this->optimizedMapWithKeys($this->columns, function ($column, $identifier) {
            $isVisible = !isset($column['visible']) || $column['visible'] !== false;
            return [$identifier => $isVisible];
        });
    }

    /**
     * Get validated visible columns from session
     * Moved from DatatableTrait.php line 1220
     */
    protected function getValidatedVisibleColumns($sessionVisibility)
    {
        $validSessionVisibility = [];
        foreach ($this->columns as $columnKey => $columnConfig) {
            if (array_key_exists($columnKey, $sessionVisibility)) {
                $validSessionVisibility[$columnKey] = $sessionVisibility[$columnKey];
            }
        }

        return $validSessionVisibility;
    }

    /**
     * Abstract methods that must be implemented in the main class or other traits
     */
    abstract protected function validateRelationString($relationString): bool;
    abstract protected function optimizedMapWithKeys(array $array, callable $callback): array;
}
