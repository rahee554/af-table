<?php

namespace ArtflowStudio\Table\Traits\Core;

use Illuminate\Support\Facades\Log;

trait HasJsonSupport
{
    /**
     * Extract value from JSON column
     */
    protected function extractJsonValue($record, $columnKey, $jsonPath)
    {
        if (!isset($this->columns[$columnKey]['key'])) {
            return null;
        }

        $columnName = $this->columns[$columnKey]['key'];
        $jsonData = $record->$columnName;

        if (is_null($jsonData)) {
            return null;
        }

        // If it's already an array, use it directly
        if (is_array($jsonData)) {
            return $this->getNestedValue($jsonData, $jsonPath);
        }

        // Try to decode JSON string
        if (is_string($jsonData)) {
            $decodedData = json_decode($jsonData, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->getNestedValue($decodedData, $jsonPath);
            }
        }

        return null;
    }

    /**
     * Get nested value from array using dot notation
     */
    protected function getNestedValue($data, $path)
    {
        if (!is_array($data)) {
            return null;
        }

        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Search within JSON columns
     */
    protected function searchJsonColumn($query, $columnKey, $searchTerm)
    {
        if (!isset($this->columns[$columnKey]['key'], $this->columns[$columnKey]['json'])) {
            return $query;
        }

        $columnName = $this->columns[$columnKey]['key'];
        $jsonPath = $this->columns[$columnKey]['json'];

        try {
            // For MySQL 5.7+ and other databases that support JSON functions
            if ($this->supportsJsonSearch()) {
                $query->whereRaw("JSON_EXTRACT(`{$columnName}`, '$.{$jsonPath}') LIKE ?", ["%{$searchTerm}%"]);
            } else {
                // Fallback for older databases
                $query->where($columnName, 'LIKE', "%\"{$jsonPath}\":%\"{$searchTerm}\"%");
            }
        } catch (\Exception $e) {
            Log::warning('JSON search failed: ' . $e->getMessage());
            
            // Simple fallback search
            $query->where($columnName, 'LIKE', "%{$searchTerm}%");
        }

        return $query;
    }

    /**
     * Check if database supports JSON search functions
     */
    protected function supportsJsonSearch(): bool
    {
        try {
            $connection = app('db')->connection();
            $driverName = $connection->getDriverName();

            // MySQL 5.7+, PostgreSQL 9.3+, SQLite 3.38+ support JSON functions
            switch ($driverName) {
                case 'mysql':
                    $version = $connection->getPdo()->query('SELECT VERSION()')->fetchColumn();
                    return version_compare($version, '5.7.0', '>=');
                    
                case 'pgsql':
                    $version = $connection->getPdo()->query('SELECT version()')->fetchColumn();
                    preg_match('/PostgreSQL (\d+\.\d+)/', $version, $matches);
                    return isset($matches[1]) && version_compare($matches[1], '9.3', '>=');
                    
                case 'sqlite':
                    $version = $connection->getPdo()->query('SELECT sqlite_version()')->fetchColumn();
                    return version_compare($version, '3.38.0', '>=');
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Filter JSON column by value
     */
    protected function filterJsonColumn($query, $columnKey, $filterValue)
    {
        if (!isset($this->columns[$columnKey]['key'], $this->columns[$columnKey]['json'])) {
            return $query;
        }

        $columnName = $this->columns[$columnKey]['key'];
        $jsonPath = $this->columns[$columnKey]['json'];

        if (empty($filterValue)) {
            return $query;
        }

        try {
            if ($this->supportsJsonSearch()) {
                if (is_array($filterValue)) {
                    // Multiple values
                    $query->where(function ($subQuery) use ($columnName, $jsonPath, $filterValue) {
                        foreach ($filterValue as $value) {
                            $subQuery->orWhereRaw("JSON_EXTRACT(`{$columnName}`, '$.{$jsonPath}') = ?", [$value]);
                        }
                    });
                } else {
                    // Single value
                    $query->whereRaw("JSON_EXTRACT(`{$columnName}`, '$.{$jsonPath}') = ?", [$filterValue]);
                }
            } else {
                // Fallback for older databases
                if (is_array($filterValue)) {
                    $query->where(function ($subQuery) use ($columnName, $jsonPath, $filterValue) {
                        foreach ($filterValue as $value) {
                            $subQuery->orWhere($columnName, 'LIKE', "%\"{$jsonPath}\":\"{$value}\"%");
                        }
                    });
                } else {
                    $query->where($columnName, 'LIKE', "%\"{$jsonPath}\":\"{$filterValue}\"%");
                }
            }
        } catch (\Exception $e) {
            Log::warning('JSON filter failed: ' . $e->getMessage());
        }

        return $query;
    }

    /**
     * Sort by JSON column
     */
    protected function sortJsonColumn($query, $columnKey, $direction = 'asc')
    {
        if (!isset($this->columns[$columnKey]['key'], $this->columns[$columnKey]['json'])) {
            return $query;
        }

        $columnName = $this->columns[$columnKey]['key'];
        $jsonPath = $this->columns[$columnKey]['json'];

        try {
            if ($this->supportsJsonSearch()) {
                $query->orderByRaw("JSON_EXTRACT(`{$columnName}`, '$.{$jsonPath}') {$direction}");
            } else {
                // Simple fallback - might not work perfectly for all cases
                $query->orderBy($columnName, $direction);
            }
        } catch (\Exception $e) {
            Log::warning('JSON sort failed: ' . $e->getMessage());
            
            // Fallback to no sorting
        }

        return $query;
    }

    /**
     * Get distinct values from JSON column
     */
    protected function getJsonDistinctValues($columnKey): array
    {
        if (!isset($this->columns[$columnKey]['key'], $this->columns[$columnKey]['json'])) {
            return [];
        }

        $columnName = $this->columns[$columnKey]['key'];
        $jsonPath = $this->columns[$columnKey]['json'];

        try {
            if ($this->supportsJsonSearch()) {
                $results = $this->model::query()
                    ->selectRaw("DISTINCT JSON_EXTRACT(`{$columnName}`, '$.{$jsonPath}') as json_value")
                    ->whereNotNull($columnName)
                    ->whereRaw("JSON_EXTRACT(`{$columnName}`, '$.{$jsonPath}') IS NOT NULL")
                    ->limit($this->maxDistinctValues ?? 100)
                    ->pluck('json_value')
                    ->filter()
                    ->map(function ($value) {
                        // Remove JSON quotes if present
                        return is_string($value) ? trim($value, '"') : $value;
                    })
                    ->values()
                    ->toArray();

                return $results;
            } else {
                // Fallback: get all records and extract JSON values manually
                $records = $this->model::query()
                    ->select($columnName)
                    ->whereNotNull($columnName)
                    ->limit(1000) // Limit to prevent memory issues
                    ->get();

                $distinctValues = [];
                
                foreach ($records as $record) {
                    $value = $this->extractJsonValue($record, $columnKey, $jsonPath);
                    if (!is_null($value) && !in_array($value, $distinctValues)) {
                        $distinctValues[] = $value;
                        
                        // Limit distinct values
                        if (count($distinctValues) >= ($this->maxDistinctValues ?? 100)) {
                            break;
                        }
                    }
                }

                return $distinctValues;
            }
        } catch (\Exception $e) {
            Log::warning('JSON distinct values failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate JSON column configuration
     */
    protected function validateJsonColumn($columnKey): bool
    {
        if (!isset($this->columns[$columnKey])) {
            return false;
        }

        $column = $this->columns[$columnKey];

        // Must have both key and json path
        if (!isset($column['key']) || !isset($column['json'])) {
            return false;
        }

        // JSON path should be valid
        $jsonPath = $column['json'];
        if (empty($jsonPath) || !is_string($jsonPath)) {
            return false;
        }

        return true;
    }

    /**
     * Format JSON value for display
     */
    protected function formatJsonValue($value, $columnKey): string
    {
        if (is_null($value)) {
            return '';
        }

        // If it's already a string, return it
        if (is_string($value)) {
            return $value;
        }

        // If it's an array or object, convert to JSON string
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        // For other types, cast to string
        return (string) $value;
    }

    /**
     * Get JSON column statistics
     */
    public function getJsonColumnStats(): array
    {
        $jsonColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            if (isset($column['json'])) {
                $isValid = $this->validateJsonColumn($columnKey);
                $distinctCount = 0;
                
                if ($isValid) {
                    try {
                        $distinctValues = $this->getJsonDistinctValues($columnKey);
                        $distinctCount = count($distinctValues);
                    } catch (\Exception $e) {
                        // Ignore errors for statistics
                    }
                }
                
                $jsonColumns[$columnKey] = [
                    'column_name' => $column['key'] ?? 'unknown',
                    'json_path' => $column['json'],
                    'is_valid' => $isValid,
                    'distinct_values_count' => $distinctCount,
                    'supports_json_search' => $this->supportsJsonSearch()
                ];
            }
        }

        return [
            'total_json_columns' => count($jsonColumns),
            'database_supports_json' => $this->supportsJsonSearch(),
            'columns' => $jsonColumns
        ];
    }

    /**
     * Test JSON column functionality
     */
    public function testJsonColumn($columnKey): array
    {
        $results = [
            'column_key' => $columnKey,
            'validation' => $this->validateJsonColumn($columnKey),
            'database_support' => $this->supportsJsonSearch(),
            'errors' => []
        ];

        if (!$results['validation']) {
            $results['errors'][] = 'Column configuration is invalid';
            return $results;
        }

        try {
            // Test distinct values
            $distinctValues = $this->getJsonDistinctValues($columnKey);
            $results['distinct_values_count'] = count($distinctValues);
            $results['sample_values'] = array_slice($distinctValues, 0, 5);
        } catch (\Exception $e) {
            $results['errors'][] = 'Distinct values test failed: ' . $e->getMessage();
        }

        try {
            // Test search
            $query = $this->model::query();
            $this->searchJsonColumn($query, $columnKey, 'test');
            $results['search_test'] = 'passed';
        } catch (\Exception $e) {
            $results['errors'][] = 'Search test failed: ' . $e->getMessage();
        }

        try {
            // Test sorting
            $query = $this->model::query();
            $this->sortJsonColumn($query, $columnKey, 'asc');
            $results['sort_test'] = 'passed';
        } catch (\Exception $e) {
            $results['errors'][] = 'Sort test failed: ' . $e->getMessage();
        }

        return $results;
    }
}
