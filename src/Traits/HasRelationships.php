<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Log;

trait HasRelationships
{
    /**
     * Validate relation string format
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
     * Parse relation string into components
     */
    protected function parseRelationString($relationString): array
    {
        if (!$this->validateRelationString($relationString)) {
            return [
                'valid' => false,
                'relation' => null,
                'column' => null,
                'nested' => false
            ];
        }

        [$relationPart, $column] = explode(':', $relationString, 2);
        
        // Check if it's a nested relation (contains dots)
        $isNested = str_contains($relationPart, '.');
        
        return [
            'valid' => true,
            'relation' => $relationPart,
            'column' => $column,
            'nested' => $isNested,
            'relation_chain' => $isNested ? explode('.', $relationPart) : [$relationPart]
        ];
    }

    /**
     * Get relation value from a record
     */
    protected function getRelationValue($record, $relationString)
    {
        $parsed = $this->parseRelationString($relationString);
        
        if (!$parsed['valid']) {
            return null;
        }

        try {
            $current = $record;
            
            // Navigate through the relation chain
            foreach ($parsed['relation_chain'] as $relationName) {
                if (!$current || !method_exists($current, $relationName)) {
                    return null;
                }
                
                $current = $current->$relationName;
                
                if (!$current) {
                    return null;
                }
            }
            
            // Get the final column value
            $column = $parsed['column'];
            
            if (is_object($current) && isset($current->$column)) {
                return $current->$column;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::warning('Relation value extraction failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Search in relation columns
     */
    protected function searchRelationColumn($query, $columnKey, $searchTerm)
    {
        if (!isset($this->columns[$columnKey]['relation'])) {
            return $query;
        }

        $relationString = $this->columns[$columnKey]['relation'];
        $parsed = $this->parseRelationString($relationString);

        if (!$parsed['valid']) {
            return $query;
        }

        try {
            $modelInstance = new ($this->model);
            
            if ($parsed['nested']) {
                // Handle nested relations
                $query->whereHas($parsed['relation'], function ($subQuery) use ($parsed, $searchTerm) {
                    $subQuery->where($parsed['column'], 'LIKE', "%{$searchTerm}%");
                });
            } else {
                // Handle single level relations
                $relationName = $parsed['relation'];
                $column = $parsed['column'];
                
                $query->whereHas($relationName, function ($subQuery) use ($column, $searchTerm) {
                    $subQuery->where($column, 'LIKE', "%{$searchTerm}%");
                });
            }
        } catch (\Exception $e) {
            Log::warning('Relation search failed: ' . $e->getMessage());
        }

        return $query;
    }

    /**
     * Filter by relation column
     */
    protected function filterRelationColumn($query, $columnKey, $filterValue)
    {
        if (!isset($this->columns[$columnKey]['relation']) || empty($filterValue)) {
            return $query;
        }

        $relationString = $this->columns[$columnKey]['relation'];
        $parsed = $this->parseRelationString($relationString);

        if (!$parsed['valid']) {
            return $query;
        }

        try {
            if (is_array($filterValue)) {
                // Multiple values
                if ($parsed['nested']) {
                    $query->whereHas($parsed['relation'], function ($subQuery) use ($parsed, $filterValue) {
                        $subQuery->whereIn($parsed['column'], $filterValue);
                    });
                } else {
                    $relationName = $parsed['relation'];
                    $column = $parsed['column'];
                    
                    $query->whereHas($relationName, function ($subQuery) use ($column, $filterValue) {
                        $subQuery->whereIn($column, $filterValue);
                    });
                }
            } else {
                // Single value
                if ($parsed['nested']) {
                    $query->whereHas($parsed['relation'], function ($subQuery) use ($parsed, $filterValue) {
                        $subQuery->where($parsed['column'], $filterValue);
                    });
                } else {
                    $relationName = $parsed['relation'];
                    $column = $parsed['column'];
                    
                    $query->whereHas($relationName, function ($subQuery) use ($column, $filterValue) {
                        $subQuery->where($column, $filterValue);
                    });
                }
            }
        } catch (\Exception $e) {
            Log::warning('Relation filter failed: ' . $e->getMessage());
        }

        return $query;
    }

    /**
     * Sort by relation column
     */
    protected function sortRelationColumn($query, $columnKey, $direction = 'asc')
    {
        if (!isset($this->columns[$columnKey]['relation'])) {
            return $query;
        }

        $relationString = $this->columns[$columnKey]['relation'];
        $parsed = $this->parseRelationString($relationString);

        if (!$parsed['valid']) {
            return $query;
        }

        try {
            $modelInstance = new ($this->model);
            
            if ($parsed['nested']) {
                // For nested relations, we might need a different approach
                // This is a simplified version that might not work for all cases
                Log::warning('Nested relation sorting is not fully supported: ' . $relationString);
                return $query;
            } else {
                // Handle single level relations with joins
                $relationName = $parsed['relation'];
                $column = $parsed['column'];
                
                $relationObj = $modelInstance->$relationName();
                $relatedModel = $relationObj->getRelated();
                $relatedTable = $relatedModel->getTable();
                $relatedColumn = $relatedTable . '.' . $column;

                // Determine join keys based on relation type
                if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $parentTable = $modelInstance->getTable();
                    $foreignKey = $relationObj->getForeignKeyName();
                    $ownerKey = $relationObj->getOwnerKeyName();

                    $query->leftJoin($relatedTable, $parentTable . '.' . $foreignKey, '=', $relatedTable . '.' . $ownerKey)
                          ->orderBy($relatedColumn, $direction);
                          
                } elseif ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                    $parentTable = $modelInstance->getTable();
                    $foreignKey = $relationObj->getForeignKeyName();
                    $localKey = $relationObj->getLocalKeyName();

                    $query->leftJoin($relatedTable, $parentTable . '.' . $localKey, '=', $relatedTable . '.' . $foreignKey)
                          ->orderBy($relatedColumn, $direction);
                } else {
                    // For HasMany and other relations, use subquery
                    $subQuery = $relatedModel::query()
                        ->select($column)
                        ->whereColumn($relatedTable . '.' . $relationObj->getForeignKeyName(), $modelInstance->getTable() . '.' . $relationObj->getLocalKeyName())
                        ->orderBy($column, $direction)
                        ->limit(1);

                    $query->orderBy($subQuery, $direction);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Relation sort failed: ' . $e->getMessage());
        }

        return $query;
    }

    /**
     * Check if relation exists on model
     */
    protected function relationExists($relationName): bool
    {
        try {
            $modelInstance = new ($this->model);
            
            if (!method_exists($modelInstance, $relationName)) {
                return false;
            }

            $relation = $modelInstance->$relationName();
            
            return $relation instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (\Exception $e) {
            return false;
        }
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
     * Validate all relation columns
     */
    protected function validateRelationColumns(): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'warnings' => []
        ];

        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['relation'])) {
                $relationString = $column['relation'];
                $parsed = $this->parseRelationString($relationString);

                if (!$parsed['valid']) {
                    $results['invalid'][$columnKey] = 'Invalid relation string format';
                    continue;
                }

                // Check if relation exists
                $firstRelation = $parsed['relation_chain'][0];
                if (!$this->relationExists($firstRelation)) {
                    $results['invalid'][$columnKey] = "Relation '{$firstRelation}' does not exist on model";
                    continue;
                }

                $results['valid'][$columnKey] = [
                    'relation_string' => $relationString,
                    'parsed' => $parsed,
                    'relation_type' => $this->getRelationType($firstRelation)
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
     * Get relation column statistics
     */
    public function getRelationColumnStats(): array
    {
        $validation = $this->validateRelationColumns();
        $relationTypes = [];

        foreach ($validation['valid'] as $columnKey => $info) {
            $type = $info['relation_type'];
            if (!isset($relationTypes[$type])) {
                $relationTypes[$type] = 0;
            }
            $relationTypes[$type]++;
        }

        return [
            'total_relation_columns' => count($validation['valid']) + count($validation['invalid']),
            'valid_relations' => count($validation['valid']),
            'invalid_relations' => count($validation['invalid']),
            'warnings' => count($validation['warnings']),
            'relation_types' => $relationTypes,
            'validation_details' => $validation
        ];
    }

    /**
     * Test relation column functionality
     */
    public function testRelationColumn($columnKey): array
    {
        $results = [
            'column_key' => $columnKey,
            'has_relation' => isset($this->columns[$columnKey]['relation']),
            'tests' => []
        ];

        if (!$results['has_relation']) {
            $results['error'] = 'Column does not have relation configuration';
            return $results;
        }

        $relationString = $this->columns[$columnKey]['relation'];
        $parsed = $this->parseRelationString($relationString);

        $results['relation_string'] = $relationString;
        $results['parsed'] = $parsed;

        // Test relation string validation
        $results['tests']['validation'] = $parsed['valid'] ? 'passed' : 'failed';

        if (!$parsed['valid']) {
            return $results;
        }

        // Test relation existence
        $firstRelation = $parsed['relation_chain'][0];
        $relationExists = $this->relationExists($firstRelation);
        $results['tests']['relation_exists'] = $relationExists ? 'passed' : 'failed';

        if (!$relationExists) {
            return $results;
        }

        try {
            // Test search functionality
            $query = $this->model::query();
            $this->searchRelationColumn($query, $columnKey, 'test');
            $results['tests']['search'] = 'passed';
        } catch (\Exception $e) {
            $results['tests']['search'] = 'failed: ' . $e->getMessage();
        }

        try {
            // Test filter functionality
            $query = $this->model::query();
            $this->filterRelationColumn($query, $columnKey, 'test');
            $results['tests']['filter'] = 'passed';
        } catch (\Exception $e) {
            $results['tests']['filter'] = 'failed: ' . $e->getMessage();
        }

        try {
            // Test sort functionality
            $query = $this->model::query();
            $this->sortRelationColumn($query, $columnKey, 'asc');
            $results['tests']['sort'] = 'passed';
        } catch (\Exception $e) {
            $results['tests']['sort'] = 'failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get all unique relations used in columns
     */
    public function getUsedRelations(): array
    {
        $relations = [];

        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['relation'])) {
                $parsed = $this->parseRelationString($column['relation']);
                
                if ($parsed['valid']) {
                    foreach ($parsed['relation_chain'] as $relation) {
                        if (!in_array($relation, $relations)) {
                            $relations[] = $relation;
                        }
                    }
                }
            }
        }

        return $relations;
    }
}
