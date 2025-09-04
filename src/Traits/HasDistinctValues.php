<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasDistinctValues
{
    /**
     * Cache configuration for distinct values
     */
    protected $distinctValuesConfig = [
        'cache_ttl' => 300, // 5 minutes
        'max_values' => 500, // Maximum values to cache
        'cache_prefix' => 'datatable_distinct',
    ];

    /**
     * Get cached distinct values for a column
     */
    public function getCachedDistinctValues(string $column, ?Builder $query = null): Collection
    {
        // Generate cache key
        $cacheKey = $this->generateDistinctValuesCacheKey($column, $query);

        return Cache::remember($cacheKey, $this->distinctValuesConfig['cache_ttl'], function () use ($column, $query) {
            return $this->fetchDistinctValues($column, $query);
        });
    }

    /**
     * Fetch distinct values from database
     */
    protected function fetchDistinctValues(string $column, ?Builder $query = null): Collection
    {
        $query = $query ?: $this->getQuery();

        // Handle relation columns
        if (str_contains($column, '.')) {
            return $this->fetchDistinctRelationValues($column, $query);
        }

        // Handle direct columns
        return $this->fetchDistinctDirectValues($column, $query);
    }

    /**
     * Fetch distinct values for direct columns
     */
    protected function fetchDistinctDirectValues(string $column, Builder $query): Collection
    {
        $values = $query->clone()
            ->select($column)
            ->distinct()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->limit($this->distinctValuesConfig['max_values'])
            ->pluck($column);

        return $this->formatDistinctValues($values, $column);
    }

    /**
     * Fetch distinct values for relation columns
     */
    protected function fetchDistinctRelationValues(string $column, Builder $query): Collection
    {
        [$relation, $relationColumn] = explode('.', $column, 2);

        // Get the relation model
        $relationModel = $this->getRelationModel($relation);

        if (! $relationModel) {
            return collect();
        }

        // Check if this is a nested relation
        if (str_contains($relationColumn, '.')) {
            return $this->fetchNestedRelationValues($relation, $relationColumn, $query);
        }

        // Get distinct values from the related table
        $values = $relationModel::query()
            ->select($relationColumn)
            ->distinct()
            ->whereNotNull($relationColumn)
            ->where($relationColumn, '!=', '')
            ->orderBy($relationColumn)
            ->limit($this->distinctValuesConfig['max_values'])
            ->pluck($relationColumn);

        return $this->formatDistinctValues($values, $relationColumn);
    }

    /**
     * Fetch distinct values for nested relations
     */
    protected function fetchNestedRelationValues(string $relation, string $nestedColumn, Builder $query): Collection
    {
        $relationModel = $this->getRelationModel($relation);

        if (! $relationModel) {
            return collect();
        }

        [$nextRelation, $finalColumn] = explode('.', $nestedColumn, 2);

        $nextRelationModel = $this->getRelationModel($nextRelation, $relationModel);

        if (! $nextRelationModel) {
            return collect();
        }

        $values = $nextRelationModel::query()
            ->select($finalColumn)
            ->distinct()
            ->whereNotNull($finalColumn)
            ->where($finalColumn, '!=', '')
            ->orderBy($finalColumn)
            ->limit($this->distinctValuesConfig['max_values'])
            ->pluck($finalColumn);

        return $this->formatDistinctValues($values, $finalColumn);
    }

    /**
     * Get relation model instance
     */
    protected function getRelationModel(string $relation, ?string $baseModel = null): ?string
    {
        $model = $baseModel ?: $this->model;

        if (! class_exists($model)) {
            return null;
        }

        try {
            $instance = new $model;

            if (! method_exists($instance, $relation)) {
                return null;
            }

            $relationInstance = $instance->$relation();

            return $relationInstance->getRelated()::class;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format distinct values for display
     */
    protected function formatDistinctValues(Collection $values, string $column): Collection
    {
        return $values->filter(function ($value) {
            return ! is_null($value) && $value !== '';
        })->map(function ($value) use ($column) {
            return [
                'value' => $value,
                'label' => $this->formatDistinctValueLabel($value, $column),
                'display' => $this->formatDisplayValue($value, $column),
            ];
        })->values();
    }

    /**
     * Format label for distinct value
     */
    protected function formatDistinctValueLabel($value, string $column): string
    {
        // Handle boolean values
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        // Handle dates
        if ($this->isDateColumn($column) && $this->isValidDate($value)) {
            return \Carbon\Carbon::parse($value)->format('M j, Y');
        }

        // Handle status columns
        if ($this->isStatusColumn($column)) {
            return ucwords(str_replace('_', ' ', $value));
        }

        // Handle numeric values
        if (is_numeric($value)) {
            return $this->formatNumericValue($value, $column);
        }

        // Default string formatting
        return strlen($value) > 50 ? substr($value, 0, 47).'...' : $value;
    }

    /**
     * Format display value for distinct value
     */
    protected function formatDisplayValue($value, string $column): string
    {
        return $this->formatDistinctValueLabel($value, $column);
    }

    /**
     * Check if column is a date column
     */
    protected function isDateColumn(string $column): bool
    {
        $dateColumns = ['created_at', 'updated_at', 'date', 'birth_date', 'expired_at'];

        return in_array($column, $dateColumns) || str_contains($column, '_date') || str_contains($column, '_at');
    }

    /**
     * Check if column is a status column
     */
    protected function isStatusColumn(string $column): bool
    {
        return str_contains($column, 'status') || str_contains($column, 'type') || str_contains($column, 'state');
    }

    /**
     * Check if value is a valid date
     */
    protected function isValidDate($value): bool
    {
        try {
            \Carbon\Carbon::parse($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format numeric value
     */
    protected function formatNumericValue($value, string $column): string
    {
        if (str_contains($column, 'price') || str_contains($column, 'amount') || str_contains($column, 'cost')) {
            return number_format($value, 2);
        }

        if (is_float($value)) {
            return number_format($value, 2);
        }

        return (string) $value;
    }

    /**
     * Generate cache key for distinct values
     */
    protected function generateDistinctValuesCacheKey(string $column, ?Builder $query = null): string
    {
        $keyParts = [
            $this->distinctValuesConfig['cache_prefix'],
            $this->tableId,
            $column,
            md5($this->model),
        ];

        // Include query modifications in cache key
        if ($query) {
            $keyParts[] = md5($query->toSql().serialize($query->getBindings()));
        }

        // Include filters in cache key
        if (! empty($this->filters)) {
            $keyParts[] = md5(serialize($this->filters));
        }

        return implode(':', $keyParts);
    }

    /**
     * Get distinct values for multiple columns
     */
    public function getMultipleDistinctValues(array $columns, ?Builder $query = null): array
    {
        $result = [];

        foreach ($columns as $column) {
            $result[$column] = $this->getCachedDistinctValues($column, $query);
        }

        return $result;
    }

    /**
     * Get distinct count for a column
     */
    public function getDistinctCount(string $column, ?Builder $query = null): int
    {
        $cacheKey = $this->generateDistinctValuesCacheKey($column.'_count', $query);

        return Cache::remember($cacheKey, $this->distinctValuesConfig['cache_ttl'], function () use ($column, $query) {
            $query = $query ?: $this->getQuery();

            if (str_contains($column, '.')) {
                return $this->getDistinctRelationCount($column, $query);
            }

            return $query->clone()
                ->distinct()
                ->count($column);
        });
    }

    /**
     * Get distinct count for relation column
     */
    protected function getDistinctRelationCount(string $column, Builder $query): int
    {
        [$relation, $relationColumn] = explode('.', $column, 2);

        $relationModel = $this->getRelationModel($relation);

        if (! $relationModel) {
            return 0;
        }

        return $relationModel::query()
            ->distinct()
            ->count($relationColumn);
    }

    /**
     * Clear distinct values cache
     */
    public function clearDistinctValuesCache(?string $column = null): void
    {
        if ($column) {
            $cacheKey = $this->generateDistinctValuesCacheKey($column);
            Cache::forget($cacheKey);
        } else {
            // Clear all distinct values cache for this table
            $pattern = $this->distinctValuesConfig['cache_prefix'].':'.$this->tableId.':*';
            $this->clearDistinctCacheByPattern($pattern);
        }
    }

    /**
     * Clear cache by pattern for distinct values
     */
    protected function clearDistinctCacheByPattern(string $pattern): void
    {
        // This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        $cacheStore = Cache::getStore();

        if (method_exists($cacheStore, 'flush')) {
            // If using array cache or similar, just flush
            // In real applications, implement proper pattern-based clearing
        }
    }

    /**
     * Get distinct values with search
     */
    public function searchDistinctValues(string $column, string $search, ?Builder $query = null, int $limit = 50): Collection
    {
        $query = $query ?: $this->getQuery();

        if (str_contains($column, '.')) {
            return $this->searchDistinctRelationValues($column, $search, $query, $limit);
        }

        $values = $query->clone()
            ->select($column)
            ->distinct()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, 'LIKE', "%{$search}%")
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column);

        return $this->formatDistinctValues($values, $column);
    }

    /**
     * Search distinct values in relation columns
     */
    protected function searchDistinctRelationValues(string $column, string $search, Builder $query, int $limit): Collection
    {
        [$relation, $relationColumn] = explode('.', $column, 2);

        $relationModel = $this->getRelationModel($relation);

        if (! $relationModel) {
            return collect();
        }

        $values = $relationModel::query()
            ->select($relationColumn)
            ->distinct()
            ->whereNotNull($relationColumn)
            ->where($relationColumn, '!=', '')
            ->where($relationColumn, 'LIKE', "%{$search}%")
            ->orderBy($relationColumn)
            ->limit($limit)
            ->pluck($relationColumn);

        return $this->formatDistinctValues($values, $relationColumn);
    }

    /**
     * Configure distinct values settings
     */
    public function configureDistinctValues(array $config): void
    {
        $this->distinctValuesConfig = array_merge($this->distinctValuesConfig, $config);
    }

    /**
     * Get distinct values configuration
     */
    public function getDistinctValuesConfig(): array
    {
        return $this->distinctValuesConfig;
    }

    /**
     * Preload distinct values for common columns
     */
    public function preloadDistinctValues(array $columns = []): void
    {
        if (empty($columns)) {
            $columns = $this->getCommonFilterColumns();
        }

        foreach ($columns as $column) {
            // Preload in background if possible
            $this->getCachedDistinctValues($column);
        }
    }

    /**
     * Get common columns that are typically used for filtering
     */
    protected function getCommonFilterColumns(): array
    {
        $commonColumns = [];

        // Add columns that are typically used for filtering
        if (isset($this->visibleColumns)) {
            foreach ($this->visibleColumns as $column => $visible) {
                if ($visible && $this->isFilterableColumn($column)) {
                    $commonColumns[] = $column;
                }
            }
        }

        return $commonColumns;
    }

    /**
     * Check if column is typically filterable
     */
    protected function isFilterableColumn(string $column): bool
    {
        // Exclude certain types of columns from preloading
        $excludePatterns = ['id', 'password', 'token', 'hash', 'description', 'content', 'notes'];

        foreach ($excludePatterns as $pattern) {
            if (str_contains(strtolower($column), $pattern)) {
                return false;
            }
        }

        return true;
    }
}
