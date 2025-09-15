<?php

namespace ArtflowStudio\Table\Traits;

trait HasOptimizedRelationships
{
    /**
     * Cache for parsed relation configurations
     */
    protected array $relationConfigCache = [];

    /**
     * Cache for relationship existence checks
     */
    protected array $relationExistsCache = [];

    /**
     * Optimized relation loading with smart selection
     * Reduces N+1 queries by batching relation loads and selecting only needed columns
     */
    protected function getOptimizedEagerLoads(): array
    {
        if (!empty($this->relationConfigCache)) {
            return $this->relationConfigCache['eager_loads'] ?? [];
        }

        $eagerLoads = [];
        $relationColumns = [];

        // Parse all columns once and cache the results
        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['relation'])) {
                $relationConfig = $this->parseRelationString($column['relation']);
                
                if ($relationConfig) {
                    $relationName = $relationConfig['name'];
                    
                    // Build eager load with specific columns to reduce memory usage
                    if (!isset($eagerLoads[$relationName])) {
                        $eagerLoads[$relationName] = [];
                    }
                    
                    // Add the specific column we need from this relation
                    if (!in_array($relationConfig['column'], $eagerLoads[$relationName])) {
                        $eagerLoads[$relationName][] = $relationConfig['column'];
                    }
                    
                    // Store for caching
                    $relationColumns[$relationName][] = $relationConfig['column'];
                }
            }
        }

        // Convert to with() format with column selection
        $withRelations = [];
        foreach ($eagerLoads as $relationName => $columns) {
            // Always include the relation's primary key
            $columns = array_unique(array_merge(['id'], $columns));
            $withRelations[$relationName] = function ($query) use ($columns) {
                $query->select($columns);
            };
        }

        // Cache the results
        $this->relationConfigCache = [
            'eager_loads' => $withRelations,
            'relation_columns' => $relationColumns,
            'parsed_at' => time()
        ];

        return $withRelations;
    }

    /**
     * Parse relation string with caching
     * Format: "relationName:columnName" or "relation.nested:columnName"
     */
    protected function parseRelationString(string $relationString): ?array
    {
        $cacheKey = "parsed_{$relationString}";
        
        if (isset($this->relationConfigCache[$cacheKey])) {
            return $this->relationConfigCache[$cacheKey];
        }

        if (!str_contains($relationString, ':')) {
            return null;
        }

        [$relationPath, $column] = explode(':', $relationString, 2);
        
        // Handle nested relations like "user.profile:name"
        $relationParts = explode('.', $relationPath);
        $baseRelation = $relationParts[0];
        
        $config = [
            'name' => $relationPath,
            'base' => $baseRelation,
            'column' => $column,
            'nested' => count($relationParts) > 1,
            'parts' => $relationParts
        ];

        // Cache the result
        $this->relationConfigCache[$cacheKey] = $config;
        
        return $config;
    }

    /**
     * Optimized relation existence check with caching
     */
    protected function relationExistsOptimized(string $relationName): bool
    {
        if (isset($this->relationExistsCache[$relationName])) {
            return $this->relationExistsCache[$relationName];
        }

        $modelInstance = $this->getModelInstance();
        if (!$modelInstance) {
            $this->relationExistsCache[$relationName] = false;
            return false;
        }

        $exists = method_exists($modelInstance, $relationName);
        $this->relationExistsCache[$relationName] = $exists;
        
        return $exists;
    }

    /**
     * Apply optimized eager loading to query
     */
    protected function applyOptimizedEagerLoading($query)
    {
        $optimizedLoads = $this->getOptimizedEagerLoads();
        
        if (!empty($optimizedLoads)) {
            $query->with($optimizedLoads);
        }

        return $query;
    }

    /**
     * Get relation value with optimized access
     * Reduces property access overhead for relation data
     */
    protected function getRelationValueOptimized($record, string $relationString)
    {
        $relationConfig = $this->parseRelationString($relationString);
        
        if (!$relationConfig) {
            return null;
        }

        try {
            // Navigate through nested relations efficiently
            $current = $record;
            
            foreach ($relationConfig['parts'] as $part) {
                if (is_null($current)) {
                    return null;
                }
                
                // Use isset for better performance than property_exists
                if (is_object($current) && isset($current->$part)) {
                    $current = $current->$part;
                } elseif (is_array($current) && isset($current[$part])) {
                    $current = $current[$part];
                } else {
                    return null;
                }
            }
            
            // Get the final column value
            $column = $relationConfig['column'];
            
            if (is_object($current) && isset($current->$column)) {
                return $current->$column;
            } elseif (is_array($current) && isset($current[$column])) {
                return $current[$column];
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::warning("Error accessing relation {$relationString}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Preload relations for a collection of records
     * Reduces N+1 queries when processing multiple records
     */
    protected function preloadRelationsOptimized($records): void
    {
        if (empty($records) || !is_iterable($records)) {
            return;
        }

        $relationColumns = $this->relationConfigCache['relation_columns'] ?? [];
        
        if (empty($relationColumns)) {
            return;
        }

        // Group records by missing relations to batch load them
        $missingRelations = [];
        
        foreach ($records as $record) {
            foreach ($relationColumns as $relationName => $columns) {
                if (!$record->relationLoaded($relationName)) {
                    if (!isset($missingRelations[$relationName])) {
                        $missingRelations[$relationName] = [];
                    }
                    $missingRelations[$relationName][] = $record;
                }
            }
        }

        // Batch load missing relations
        foreach ($missingRelations as $relationName => $recordsToLoad) {
            $this->batchLoadRelation($recordsToLoad, $relationName, $relationColumns[$relationName]);
        }
    }

    /**
     * Batch load a specific relation for multiple records
     */
    protected function batchLoadRelation(array $records, string $relationName, array $columns): void
    {
        try {
            $recordIds = array_map(fn($record) => $record->getKey(), $records);
            
            // Create a single query to load all relation data
            $firstRecord = $records[0];
            $relation = $firstRecord->$relationName();
            
            // Add necessary columns including foreign keys
            $allColumns = array_unique(array_merge(['id'], $columns, [$relation->getForeignKeyName()]));
            
            $relationData = $relation->getRelated()
                ->whereIn($relation->getOwnerKeyName(), $recordIds)
                ->select($allColumns)
                ->get()
                ->keyBy($relation->getOwnerKeyName());

            // Assign loaded data to records
            foreach ($records as $record) {
                $relationRecord = $relationData->get($record->getKey());
                $record->setRelation($relationName, $relationRecord);
            }
            
        } catch (\Exception $e) {
            \Log::warning("Failed to batch load relation {$relationName}: " . $e->getMessage());
        }
    }

    /**
     * Clear relation configuration cache
     */
    protected function clearRelationCache(): void
    {
        $this->relationConfigCache = [];
        $this->relationExistsCache = [];
    }

    /**
     * Get memory-efficient relation data for exports
     * Returns only essential data to reduce memory usage during large exports
     */
    protected function getRelationDataForExport($records): array
    {
        $relationData = [];
        $relationColumns = $this->relationConfigCache['relation_columns'] ?? [];
        
        foreach ($records as $recordKey => $record) {
            $relationData[$recordKey] = [];
            
            foreach ($relationColumns as $relationName => $columns) {
                if ($record->relationLoaded($relationName)) {
                    $relationRecord = $record->getRelation($relationName);
                    
                    if ($relationRecord) {
                        $relationData[$recordKey][$relationName] = [];
                        
                        foreach ($columns as $column) {
                            $relationData[$recordKey][$relationName][$column] = $relationRecord->$column ?? null;
                        }
                    }
                }
            }
        }
        
        return $relationData;
    }

    /**
     * Get relation loading statistics for performance monitoring
     */
    public function getRelationLoadingStats(): array
    {
        return [
            'cached_relations' => count($this->relationConfigCache),
            'existence_cache_size' => count($this->relationExistsCache),
            'configured_relations' => $this->relationConfigCache['relation_columns'] ?? [],
            'cache_created_at' => $this->relationConfigCache['parsed_at'] ?? null,
            'memory_usage' => memory_get_usage(true)
        ];
    }

    /**
     * Optimize relation queries for sorting and filtering
     */
    protected function optimizeRelationQuery($query, string $relationString, string $operation = 'select'): void
    {
        $relationConfig = $this->parseRelationString($relationString);
        
        if (!$relationConfig) {
            return;
        }

        try {
            $relationName = $relationConfig['base'];
            $modelInstance = $this->getModelInstance();
            
            if (!$this->relationExistsOptimized($relationName)) {
                return;
            }

            $relationObj = $modelInstance->$relationName();
            
            // Add index hints for better performance on large datasets
            if ($operation === 'sort' && $this->shouldUseIndexHints()) {
                $relatedTable = $relationObj->getRelated()->getTable();
                $sortColumn = $relationConfig['column'];
                
                if ($this->isColumnIndexed($relatedTable, $sortColumn)) {
                    $query->from(\DB::raw("{$relatedTable} USE INDEX ({$sortColumn})"));
                }
            }
            
        } catch (\Exception $e) {
            \Log::warning("Failed to optimize relation query for {$relationString}: " . $e->getMessage());
        }
    }

    /**
     * Check if we should use database index hints
     */
    protected function shouldUseIndexHints(): bool
    {
        return config('database.default') === 'mysql' && 
               ($this->getEstimatedRecordCount() > 1000);
    }

    /**
     * Get estimated record count for optimization decisions
     */
    protected function getEstimatedRecordCount(): int
    {
        try {
            return $this->model::query()->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
