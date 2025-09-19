<?php

namespace ArtflowStudio\Table\Traits\Core;

trait HasColumnConfiguration
{
    /**
     * Initialize column configuration for performance
     */
    protected function initializeColumnConfiguration($columns)
    {
        // Pre-calculate relations and select columns for better performance
        $this->cachedRelations = $this->calculateRequiredRelations($columns);
        $this->cachedSelectColumns = $this->calculateSelectColumns($columns);
    }

    /**
     * Calculate required relations for eager loading
     */
    protected function calculateRequiredRelations($columns): array
    {
        $relations = [];

        foreach ($columns as $columnKey => $column) {
            // Skip if column is not visible to avoid loading unnecessary relations
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            if (isset($column['relation'])) {
                [$relationName] = explode(':', $column['relation']);
                
                // Handle nested relations (e.g., 'user.profile')
                $relationParts = explode('.', $relationName);
                $currentRelation = '';
                
                foreach ($relationParts as $part) {
                    $currentRelation = $currentRelation ? $currentRelation . '.' . $part : $part;
                    $relations[] = $currentRelation;
                }
            }

            // Scan raw templates for relation references
            if (isset($column['raw']) && is_string($column['raw'])) {
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)/', $column['raw'], $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $relationName) {
                        if (!in_array($relationName, $relations)) {
                            $relations[] = $relationName;
                        }
                    }
                }
            }
        }

        // Include filter relations
        foreach ($this->filters ?? [] as $filterKey => $filterConfig) {
            if (isset($filterConfig['relation']) && isset($columns[$filterKey]['relation'])) {
                [$relationName] = explode(':', $columns[$filterKey]['relation']);
                $relations[] = $relationName;
            }
        }

        return array_unique($relations);
    }

    /**
     * Calculate select columns for query optimization
     */
    protected function calculateSelectColumns($columns): array
    {
        $selects = ['id']; // Always include ID
        
        // Always include updated_at for index sorting if it exists
        if ($this->isValidColumn('updated_at')) {
            $selects[] = 'updated_at';
        }

        foreach ($columns as $columnKey => $column) {
            // Skip non-visible columns for performance
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }

            // Function columns don't need database columns - skip them
            if (isset($column['function'])) {
                continue;
            }

            if (isset($column['relation'])) {
                [$relationName] = explode(':', $column['relation']);
                $relationParts = explode('.', $relationName);
                
                // For simple relations, include foreign key
                if (count($relationParts) === 1 && $this->isValidColumn($relationParts[0] . '_id')) {
                    $foreignKey = $relationParts[0] . '_id';
                    if (!in_array($foreignKey, $selects)) {
                        $selects[] = $foreignKey;
                    }
                }
                continue;
            }

            // Handle JSON columns - always include the base JSON column when json is specified
            if (isset($column['json']) && isset($column['key'])) {
                if (!in_array($column['key'], $selects)) {
                    $selects[] = $column['key'];
                }
                continue;
            }

            // Only add database columns if they have a valid key
            if (isset($column['key']) && !in_array($column['key'], $selects)) {
                if ($this->isValidColumn($column['key'])) {
                    $selects[] = $column['key'];
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

        // Ensure action columns are always included
        foreach ($validActionColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }
        
        // Same for raw template columns
        foreach ($validRawTemplateColumns as $col) {
            if (!in_array($col, $selects)) {
                $selects[] = $col;
            }
        }

        // Check for action columns that are NOT in $this->columns
        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            
            // Ensure template is a string before using preg_match_all
            if (!is_string($template)) {
                continue;
            }
            
            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $columnName) {
                    if ($this->isValidColumn($columnName) && !in_array($columnName, $selects)) {
                        $selects[] = $columnName;
                    }
                }
            }
        }

        return array_unique($selects);
    }

    /**
     * Get optimal sort column for performance
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
            if (isset($column['key']) && !isset($column['function'])) {
                if (in_array($column['key'], $indexedColumns) && $this->isValidColumn($column['key'])) {
                    return $column['key'];
                }
            }
        }

        // Fallback to first sortable column
        foreach ($this->columns as $column) {
            if (isset($column['key']) && !isset($column['function'])) {
                if ($this->isValidColumn($column['key'])) {
                    return $column['key'];
                }
            }
        }

        return null;
    }

    /**
     * Configure column structure from array
     */
    protected function configureColumns($columns): array
    {
        $configured = [];
        
        foreach ($columns as $index => $column) {
            // Validate column configuration
            if (!$this->validateColumnConfiguration($column)) {
                continue;
            }

            // Priority: function > key+json > key > auto-generated
            if (isset($column['function'])) {
                $identifier = $column['function'];
            } elseif (isset($column['key']) && isset($column['json'])) {
                $identifier = $column['key'] . '.' . $column['json'];
            } elseif (isset($column['key'])) {
                $identifier = $column['key'];
            } else {
                $identifier = 'col_' . $index;
            }

            $configured[$identifier] = $column;
        }
        
        return $configured;
    }

    /**
     * Get columns needed for actions
     */
    protected function getColumnsNeededForActions()
    {
        $neededColumns = [];

        foreach ($this->actions as $action) {
            $template = is_array($action) && isset($action['raw']) ? $action['raw'] : $action;
            
            // Ensure template is a string before using preg_match_all
            if (!is_string($template)) {
                continue;
            }
            
            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $template, $matches);
            if (!empty($matches[1])) {
                $neededColumns = array_merge($neededColumns, $matches[1]);
            }
        }

        return array_unique($neededColumns);
    }

    /**
     * Get columns needed for raw templates
     */
    protected function getColumnsNeededForRawTemplates()
    {
        $neededColumns = [];

        foreach ($this->columns as $column) {
            if (isset($column['raw']) && is_string($column['raw'])) {
                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)/', $column['raw'], $matches);
                if (!empty($matches[1])) {
                    $neededColumns = array_merge($neededColumns, $matches[1]);
                }
            }
        }

        return array_unique($neededColumns);
    }

    /**
     * Get column label with fallback
     */
    protected function getColumnLabel($column, $key): string
    {
        if (isset($column['label'])) {
            return $column['label'];
        }

        // Generate label from key
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Check if column is sortable
     */
    protected function isColumnSortable($column): bool
    {
        // Function columns are not sortable
        if (isset($column['function'])) {
            return false;
        }

        // Explicitly disabled
        if (isset($column['sortable']) && $column['sortable'] === false) {
            return false;
        }

        // Nested relations are complex to sort
        if (isset($column['relation'])) {
            [$relationName] = explode(':', $column['relation']);
            return !str_contains($relationName, '.');
        }

        return true;
    }

    /**
     * Check if column is searchable
     */
    protected function isColumnSearchable($column): bool
    {
        // Function columns are not searchable
        if (isset($column['function'])) {
            return false;
        }

        // Explicitly disabled
        if (isset($column['searchable']) && $column['searchable'] === false) {
            return false;
        }

        return true;
    }

    /**
     * Check if column is exportable
     */
    protected function isColumnExportable($column): bool
    {
        // Explicitly set
        if (isset($column['exportable'])) {
            return $column['exportable'] === true;
        }

        // Default to true for most columns
        return true;
    }

    /**
     * Get column configuration statistics
     */
    public function getColumnStats(): array
    {
        $columns = $this->columns ?? [];
        $visibleColumns = $this->visibleColumns ?? [];
        
        $searchableCount = 0;
        $sortableCount = 0;
        $exportableCount = 0;
        $relationCount = 0;
        $rawCount = 0;
        
        foreach ($columns as $column) {
            if (is_array($column)) {
                if ($this->isColumnSearchable($column)) $searchableCount++;
                if ($this->isColumnSortable($column)) $sortableCount++;
                if ($this->isColumnExportable($column)) $exportableCount++;
                if (isset($column['relation'])) $relationCount++;
                if (isset($column['raw'])) $rawCount++;
            }
        }
        
        return [
            'total_columns' => count($columns),
            'visible_columns' => count($visibleColumns),
            'hidden_columns' => count($columns) - count($visibleColumns),
            'searchable_columns' => $searchableCount,
            'sortable_columns' => $sortableCount,
            'exportable_columns' => $exportableCount,
            'relation_columns' => $relationCount,
            'raw_columns' => $rawCount,
            'timestamp' => now()->toISOString()
        ];
    }
}
