<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait HasColumnSelection
{
    /**
     * Column selection configuration
     */
    protected $columnSelectionConfig = [
        'default_select_columns' => ['*'],
        'always_include' => ['id'],
        'exclude_sensitive' => true,
        'optimize_selection' => true,
        'max_columns' => 50,
    ];

    /**
     * Sensitive columns that should be excluded by default
     */
    protected $sensitiveColumns = [
        'password',
        'password_hash',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'private_key',
        'salt',
        'hash',
    ];

    /**
     * Get valid select columns for the query
     */
    public function getValidSelectColumns(?array $requestedColumns = null): array
    {
        $availableColumns = $this->getAvailableColumns();
        $requestedColumns = $requestedColumns ?: $this->getRequestedColumns();

        // If no specific columns requested, use defaults
        if (empty($requestedColumns) || in_array('*', $requestedColumns)) {
            return $this->getDefaultSelectColumns($availableColumns);
        }

        // Validate and filter requested columns
        $validColumns = $this->validateRequestedColumns($requestedColumns, $availableColumns);

        // Ensure always included columns are present
        $validColumns = $this->ensureAlwaysIncludedColumns($validColumns);

        // Optimize column selection
        if ($this->columnSelectionConfig['optimize_selection']) {
            $validColumns = $this->optimizeColumnSelection($validColumns);
        }

        return array_values(array_unique($validColumns));
    }

    /**
     * Get available columns from the model
     */
    protected function getAvailableColumns(): array
    {
        static $cachedColumns = [];

        $modelClass = $this->model;

        if (isset($cachedColumns[$modelClass])) {
            return $cachedColumns[$modelClass];
        }

        try {
            // Get columns from database schema
            $tableName = (new $modelClass)->getTable();
            $columns = Schema::getColumnListing($tableName);

            // Filter out sensitive columns if enabled
            if ($this->columnSelectionConfig['exclude_sensitive']) {
                $columns = array_diff($columns, $this->sensitiveColumns);
            }

            $cachedColumns[$modelClass] = $columns;

            return $columns;
        } catch (\Exception $e) {
            // Fallback to empty array if schema inspection fails
            return [];
        }
    }

    /**
     * Get requested columns from various sources
     */
    protected function getRequestedColumns(): array
    {
        $columns = [];

        // From visible columns
        if (isset($this->visibleColumns) && is_array($this->visibleColumns)) {
            $columns = array_merge($columns, array_keys(array_filter($this->visibleColumns)));
        }

        // From explicit select columns
        if (isset($this->selectColumns) && is_array($this->selectColumns)) {
            $columns = array_merge($columns, $this->selectColumns);
        }

        // From sort column
        if (! empty($this->sortColumn)) {
            $columns[] = $this->getSortColumnName($this->sortColumn);
        }

        // From search columns
        if (isset($this->searchableColumns) && is_array($this->searchableColumns)) {
            $columns = array_merge($columns, $this->searchableColumns);
        }

        // From filter columns
        if (! empty($this->filters)) {
            $columns = array_merge($columns, array_keys($this->filters));
        }

        return array_unique($columns);
    }

    /**
     * Get default select columns
     */
    protected function getDefaultSelectColumns(array $availableColumns): array
    {
        $defaultColumns = $this->columnSelectionConfig['default_select_columns'];

        if (in_array('*', $defaultColumns)) {
            return $availableColumns;
        }

        // Intersect with available columns
        $validDefaults = array_intersect($defaultColumns, $availableColumns);

        // Ensure always included columns
        $validDefaults = $this->ensureAlwaysIncludedColumns($validDefaults);

        return array_values($validDefaults);
    }

    /**
     * Validate requested columns against available columns
     */
    protected function validateRequestedColumns(array $requestedColumns, array $availableColumns): array
    {
        $validColumns = [];

        foreach ($requestedColumns as $column) {
            // Handle relation columns
            if (str_contains($column, '.')) {
                if ($this->isValidRelationColumn($column)) {
                    $validColumns[] = $column;
                }

                continue;
            }

            // Handle direct columns
            if (in_array($column, $availableColumns)) {
                $validColumns[] = $column;
            }
        }

        // Limit the number of columns
        if (count($validColumns) > $this->columnSelectionConfig['max_columns']) {
            $validColumns = array_slice($validColumns, 0, $this->columnSelectionConfig['max_columns']);
        }

        return $validColumns;
    }

    /**
     * Check if relation column is valid
     */
    protected function isValidRelationColumn(string $column): bool
    {
        $parts = explode('.', $column);

        if (count($parts) < 2) {
            return false;
        }

        $relation = $parts[0];
        $relationColumn = implode('.', array_slice($parts, 1));

        try {
            $modelInstance = new $this->model;

            if (! method_exists($modelInstance, $relation)) {
                return false;
            }

            // Get the relation model
            $relationInstance = $modelInstance->$relation();
            $relationModel = $relationInstance->getRelated();

            // If it's a nested relation, validate recursively
            if (str_contains($relationColumn, '.')) {
                return $this->isValidNestedRelationColumn($relationModel, $relationColumn);
            }

            // Check if the column exists in the relation model
            $relationTableName = $relationModel->getTable();
            $relationColumns = Schema::getColumnListing($relationTableName);

            return in_array($relationColumn, $relationColumns);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate nested relation column
     */
    protected function isValidNestedRelationColumn($model, string $nestedColumn): bool
    {
        $parts = explode('.', $nestedColumn, 2);

        if (count($parts) != 2) {
            return false;
        }

        [$nextRelation, $remainingColumn] = $parts;

        try {
            if (! method_exists($model, $nextRelation)) {
                return false;
            }

            $nextRelationInstance = $model->$nextRelation();
            $nextRelationModel = $nextRelationInstance->getRelated();

            if (str_contains($remainingColumn, '.')) {
                return $this->isValidNestedRelationColumn($nextRelationModel, $remainingColumn);
            }

            $tableName = $nextRelationModel->getTable();
            $columns = Schema::getColumnListing($tableName);

            return in_array($remainingColumn, $columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ensure always included columns are present
     */
    protected function ensureAlwaysIncludedColumns(array $columns): array
    {
        $alwaysInclude = $this->columnSelectionConfig['always_include'];

        foreach ($alwaysInclude as $column) {
            if (! in_array($column, $columns)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Optimize column selection based on query needs
     */
    protected function optimizeColumnSelection(array $columns): array
    {
        // Remove duplicates
        $columns = array_unique($columns);

        // Sort columns for consistent queries
        sort($columns);

        // Remove columns that are not actually needed for the current operation
        $columns = $this->removeUnnecessaryColumns($columns);

        return $columns;
    }

    /**
     * Remove columns that are not necessary for current operation
     */
    protected function removeUnnecessaryColumns(array $columns): array
    {
        // Keep only columns that are needed for:
        // 1. Display (visible columns)
        // 2. Sorting
        // 3. Filtering
        // 4. Searching
        // 5. Always included

        $necessaryColumns = [];

        // Always include columns
        $necessaryColumns = array_merge($necessaryColumns, $this->columnSelectionConfig['always_include']);

        // Visible columns
        if (isset($this->visibleColumns)) {
            $visibleColumnNames = array_keys(array_filter($this->visibleColumns));
            $necessaryColumns = array_merge($necessaryColumns, $visibleColumnNames);
        }

        // Sort column
        if (! empty($this->sortColumn)) {
            $necessaryColumns[] = $this->getSortColumnName($this->sortColumn);
        }

        // Filter columns
        if (! empty($this->filters)) {
            $necessaryColumns = array_merge($necessaryColumns, array_keys($this->filters));
        }

        // Search columns
        if (isset($this->searchableColumns)) {
            $necessaryColumns = array_merge($necessaryColumns, $this->searchableColumns);
        }

        // Intersect with requested columns
        $optimizedColumns = array_intersect($columns, array_unique($necessaryColumns));

        // If optimization results in too few columns, use original
        if (count($optimizedColumns) < 2) {
            return $columns;
        }

        return array_values($optimizedColumns);
    }

    /**
     * Get sort column name (handle relation columns)
     */
    protected function getSortColumnName(string $sortColumn): string
    {
        // If it's a relation column, we need the base column for selection
        if (str_contains($sortColumn, '.')) {
            $parts = explode('.', $sortColumn);

            return $parts[0]; // Return the relation name
        }

        return $sortColumn;
    }

    /**
     * Build select clause for query
     */
    public function buildSelectClause(Builder $query, ?array $columns = null): Builder
    {
        $columns = $columns ?: $this->getValidSelectColumns();

        if (empty($columns) || in_array('*', $columns)) {
            return $query;
        }

        // Separate direct columns from relation columns
        $directColumns = [];
        $relationColumns = [];

        foreach ($columns as $column) {
            if (str_contains($column, '.')) {
                $relationColumns[] = $column;
            } else {
                $directColumns[] = $column;
            }
        }

        // Add table prefix to direct columns
        if (! empty($directColumns)) {
            $tableName = $query->getModel()->getTable();
            $prefixedColumns = array_map(function ($column) use ($tableName) {
                return "{$tableName}.{$column}";
            }, $directColumns);

            $query->select($prefixedColumns);
        }

        // Handle relation columns through eager loading
        if (! empty($relationColumns)) {
            $this->addRelationColumnsToSelect($query, $relationColumns);
        }

        return $query;
    }

    /**
     * Add relation columns to select through eager loading
     */
    protected function addRelationColumnsToSelect(Builder $query, array $relationColumns): void
    {
        $relationSelects = [];

        foreach ($relationColumns as $column) {
            $parts = explode('.', $column);
            $relation = $parts[0];
            $relationColumn = implode('.', array_slice($parts, 1));

            if (! isset($relationSelects[$relation])) {
                $relationSelects[$relation] = [];
            }

            $relationSelects[$relation][] = $relationColumn;
        }

        foreach ($relationSelects as $relation => $columns) {
            $query->with([$relation => function ($q) use ($columns) {
                $q->select(array_merge(['id'], $columns));
            }]);
        }
    }

    /**
     * Get columns for export
     */
    public function getExportColumns(): array
    {
        $exportColumns = [];

        if (isset($this->visibleColumns)) {
            foreach ($this->visibleColumns as $column => $visible) {
                if ($visible && $this->isExportableColumn($column)) {
                    $exportColumns[] = $column;
                }
            }
        }

        return $exportColumns;
    }

    /**
     * Check if column is exportable
     */
    protected function isExportableColumn(string $column): bool
    {
        // Exclude sensitive columns from export
        foreach ($this->sensitiveColumns as $sensitive) {
            if (str_contains(strtolower($column), strtolower($sensitive))) {
                return false;
            }
        }

        // Exclude certain relation columns that might be too complex
        if (str_contains($column, '.') && substr_count($column, '.') > 2) {
            return false;
        }

        return true;
    }

    /**
     * Get columns for search
     */
    public function getSearchableColumns(): array
    {
        if (isset($this->searchableColumns) && is_array($this->searchableColumns)) {
            return $this->searchableColumns;
        }

        // Auto-detect searchable columns
        $searchableColumns = [];
        $availableColumns = $this->getAvailableColumns();

        foreach ($availableColumns as $column) {
            if ($this->isSearchableColumn($column)) {
                $searchableColumns[] = $column;
            }
        }

        return $searchableColumns;
    }

    /**
     * Check if column is searchable
     */
    protected function isSearchableColumn(string $column): bool
    {
        // Exclude sensitive columns
        foreach ($this->sensitiveColumns as $sensitive) {
            if (str_contains(strtolower($column), strtolower($sensitive))) {
                return false;
            }
        }

        // Exclude certain column types
        $excludeTypes = ['id', 'created_at', 'updated_at', 'deleted_at'];

        if (in_array($column, $excludeTypes)) {
            return false;
        }

        // Include text-like columns
        $textPatterns = ['name', 'title', 'description', 'email', 'phone', 'address'];

        foreach ($textPatterns as $pattern) {
            if (str_contains(strtolower($column), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Configure column selection
     */
    public function configureColumnSelection(array $config): void
    {
        $this->columnSelectionConfig = array_merge($this->columnSelectionConfig, $config);
    }

    /**
     * Add sensitive column
     */
    public function addSensitiveColumn(string $column): void
    {
        if (! in_array($column, $this->sensitiveColumns)) {
            $this->sensitiveColumns[] = $column;
        }
    }

    /**
     * Remove sensitive column
     */
    public function removeSensitiveColumn(string $column): void
    {
        $this->sensitiveColumns = array_filter($this->sensitiveColumns, function ($col) use ($column) {
            return $col !== $column;
        });
    }

    /**
     * Get sensitive columns
     */
    public function getSensitiveColumns(): array
    {
        return $this->sensitiveColumns;
    }

    /**
     * Get column selection configuration
     */
    public function getColumnSelectionConfig(): array
    {
        return $this->columnSelectionConfig;
    }

    /**
     * Validate column exists in model
     */
    public function columnExists(string $column): bool
    {
        $availableColumns = $this->getAvailableColumns();

        if (str_contains($column, '.')) {
            return $this->isValidRelationColumn($column);
        }

        return in_array($column, $availableColumns);
    }

    /**
     * Get column type information
     */
    public function getColumnSchemaType(string $column): ?string
    {
        if (str_contains($column, '.')) {
            return 'relation';
        }

        try {
            $modelInstance = new $this->model;
            $tableName = $modelInstance->getTable();

            $columnType = Schema::getColumnType($tableName, $column);

            return $columnType;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get optimized select for count queries
     */
    public function getCountSelectColumns(): array
    {
        // For count queries, we only need the primary key
        return $this->columnSelectionConfig['always_include'];
    }
}
