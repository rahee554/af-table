<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

trait HasJsonFile
{
    public $json_file_path;
    public $json_data;
    public $json_error;
    public $json_cache_key;
    public $json_cache_duration = 300; // 5 minutes default
    public $json_mode = false;

    /**
     * Set JSON file as data source
     */
    public function setJsonFile($filePath, $config = [])
    {
        $this->json_file_path = $filePath;
        $this->json_cache_key = 'json_data_' . md5($filePath);
        $this->json_cache_duration = $config['cache_duration'] ?? 300;
        $this->json_mode = true;
        
        $this->loadJsonData();
        return $this;
    }

    /**
     * Initialize JSON file data source
     */
    public function initializeJsonFile($filePath)
    {
        $this->json_file_path = $filePath;
        $this->json_cache_key = 'json_data_' . md5($filePath);
        $this->json_cache_duration = 300;
        $this->json_mode = true;
        
        $this->loadJsonData();
        return $this;
    }

    /**
     * Check if in JSON file mode
     */
    public function isJsonMode(): bool
    {
        return $this->json_mode === true;
    }

    /**
     * Load JSON data from file with caching and optimization
     */
    protected function loadJsonData()
    {
        try {
            // For testing, don't use cache to ensure fresh loading
            // $cached = Cache::get($this->json_cache_key);
            // if ($cached && !$this->shouldRefreshJsonCache()) {
            //     $this->json_data = collect($cached);
            //     return;
            // }

            // Validate file existence
            if (!file_exists($this->json_file_path)) {
                throw new \Exception("JSON file not found: {$this->json_file_path}");
            }

            // Check file size for memory optimization
            $fileSize = filesize($this->json_file_path);
            if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
                throw new \Exception("JSON file too large: " . round($fileSize / 1024 / 1024, 2) . "MB. Maximum 50MB allowed.");
            }

            // Read and decode JSON with error handling
            $content = file_get_contents($this->json_file_path);
            if ($content === false) {
                throw new \Exception("Failed to read JSON file: {$this->json_file_path}");
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON format: " . json_last_error_msg());
            }

            // Normalize data structure for consistency
            $this->json_data = $this->normalizeJsonData($data);

            // Cache the data with timestamp for invalidation (disable for testing)
            // Cache::put($this->json_cache_key, $this->json_data->toArray(), now()->addSeconds($this->json_cache_duration));
            // Cache::put($this->json_cache_key . '_time', time(), now()->addSeconds($this->json_cache_duration));
            
            $this->json_error = null;
        } catch (\Exception $e) {
            $this->json_error = $e->getMessage();
            $this->json_data = collect([]);
        }
    }

    /**
     * Normalize JSON data to consistent collection format
     */
    protected function normalizeJsonData($data): Collection
    {
        if (!is_array($data)) {
            return collect([]);
        }

        // Handle different JSON structures
        if (empty($data)) {
            return collect([]);
        }

        // If it's a numeric array (list of items)
        if (array_keys($data) === range(0, count($data) - 1)) {
            return collect($data);
        }

        // If it's an associative array, check if it contains a data property
        if (isset($data['data']) && is_array($data['data'])) {
            return collect($data['data']);
        }

        // If it's an associative array, wrap it as single item
        return collect([$data]);
    }

    /**
     * Check if JSON cache should be refreshed based on file modification time
     */
    protected function shouldRefreshJsonCache(): bool
    {
        if (!file_exists($this->json_file_path)) {
            return false;
        }

        $cacheTime = Cache::get($this->json_cache_key . '_time');
        $fileTime = filemtime($this->json_file_path);
        
        return !$cacheTime || $fileTime > $cacheTime;
    }

    /**
     * Get processed JSON data with all datatable features
     */
    public function getJsonData()
    {
        if (!$this->json_data || $this->json_data->isEmpty()) {
            return collect([]);
        }

        $data = $this->json_data;

        // Apply search if enabled and search term exists
        if ($this->searchable && !empty($this->search)) {
            $data = $this->searchJsonData($data, $this->search);
        }

        // Apply filters if any exist
        if (!empty($this->filters)) {
            $data = $this->filterJsonData($data, $this->filters);
        }

        // Apply sorting if specified
        if (!empty($this->sortColumn)) {
            $data = $this->sortJsonData($data, $this->sortColumn, $this->sortDirection);
        }

        return $data;
    }

    /**
     * Get paginated JSON data
     */
    public function getPaginatedJsonData()
    {
        $data = $this->getJsonData();
        $total = $data->count();
        
        if ($this->perPage > 0) {
            $page = $this->getPage() ?? 1;
            $offset = ($page - 1) * $this->perPage;
            $items = $data->slice($offset, $this->perPage)->values();
            
            return new LengthAwarePaginator(
                $items,
                $total,
                $this->perPage,
                $page,
                [
                    'path' => '',
                    'pageName' => 'page',
                ]
            );
        }

        return $data;
    }

    /**
     * Optimized search through JSON data
     */
    protected function searchJsonData(Collection $data, $searchTerm): Collection
    {
        if (empty($searchTerm)) {
            return $data;
        }

        $searchLower = strtolower(trim($searchTerm));
        
        return $data->filter(function ($item) use ($searchLower) {
            return $this->searchInJsonItem($item, $searchLower);
        });
    }

    /**
     * Recursive search in JSON item with depth limit for performance
     */
    protected function searchInJsonItem($item, $searchTerm, $depth = 0): bool
    {
        // Prevent infinite recursion and limit depth for performance
        if ($depth > 5) {
            return false;
        }

        if (is_array($item) || is_object($item)) {
            foreach ($item as $value) {
                if ($this->searchInJsonItem($value, $searchTerm, $depth + 1)) {
                    return true;
                }
            }
        } else {
            // Direct string comparison for performance
            $valueStr = strtolower((string)$item);
            return strpos($valueStr, $searchTerm) !== false;
        }

        return false;
    }

    /**
     * Apply filters to JSON data with performance optimization
     */
    protected function filterJsonData(Collection $data, array $filters): Collection
    {
        if (empty($filters)) {
            return $data;
        }

        return $data->filter(function ($item) use ($filters) {
            foreach ($filters as $field => $filter) {
                if (!$this->applyJsonFilter($item, $field, $filter)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Apply single filter with comprehensive operator support
     */
    protected function applyJsonFilter($item, $field, $filter): bool
    {
        $value = Arr::get($item, $field);
        $operator = $filter['operator'] ?? 'equals';
        $filterValue = $filter['value'] ?? '';

        switch ($operator) {
            case 'equals':
            case 'eq':
                return $value == $filterValue;
                
            case 'not_equals':
            case 'neq':
                return $value != $filterValue;
                
            case 'contains':
            case 'like':
                return strpos(strtolower((string)$value), strtolower($filterValue)) !== false;
                
            case 'not_contains':
            case 'not_like':
                return strpos(strtolower((string)$value), strtolower($filterValue)) === false;
                
            case 'starts_with':
                return Str::startsWith(strtolower((string)$value), strtolower($filterValue));
                
            case 'ends_with':
                return Str::endsWith(strtolower((string)$value), strtolower($filterValue));
                
            case 'greater_than':
            case 'gt':
                return is_numeric($value) && is_numeric($filterValue) && $value > $filterValue;
                
            case 'less_than':
            case 'lt':
                return is_numeric($value) && is_numeric($filterValue) && $value < $filterValue;
                
            case 'greater_equal':
            case 'gte':
                return is_numeric($value) && is_numeric($filterValue) && $value >= $filterValue;
                
            case 'less_equal':
            case 'lte':
                return is_numeric($value) && is_numeric($filterValue) && $value <= $filterValue;
                
            case 'in':
                $filterArray = is_array($filterValue) ? $filterValue : explode(',', $filterValue);
                return in_array($value, array_map('trim', $filterArray));
                
            case 'not_in':
                $filterArray = is_array($filterValue) ? $filterValue : explode(',', $filterValue);
                return !in_array($value, array_map('trim', $filterArray));
                
            case 'between':
                if (is_array($filterValue) && count($filterValue) === 2) {
                    return is_numeric($value) && $value >= $filterValue[0] && $value <= $filterValue[1];
                }
                return false;
                
            case 'date_equal':
                return $this->compareJsonDates($value, $filterValue, '=');
                
            case 'date_greater':
                return $this->compareJsonDates($value, $filterValue, '>');
                
            case 'date_less':
                return $this->compareJsonDates($value, $filterValue, '<');
                
            case 'date_between':
                if (is_array($filterValue) && count($filterValue) === 2) {
                    return $this->compareJsonDates($value, $filterValue[0], '>=') && 
                           $this->compareJsonDates($value, $filterValue[1], '<=');
                }
                return false;
                
            case 'empty':
            case 'is_null':
                return empty($value) || is_null($value);
                
            case 'not_empty':
            case 'is_not_null':
                return !empty($value) && !is_null($value);
                
            default:
                return true;
        }
    }

    /**
     * Compare dates for filtering (prefixed to avoid conflicts)
     */
    protected function compareJsonDates($date1, $date2, $operator): bool
    {
        try {
            $d1 = new \DateTime($date1);
            $d2 = new \DateTime($date2);
            
            switch ($operator) {
                case '=':
                    return $d1->format('Y-m-d') === $d2->format('Y-m-d');
                case '>':
                    return $d1 > $d2;
                case '<':
                    return $d1 < $d2;
                case '>=':
                    return $d1 >= $d2;
                case '<=':
                    return $d1 <= $d2;
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sort JSON data with type-aware sorting
     */
    protected function sortJsonData(Collection $data, $sortBy, $direction = 'asc'): Collection
    {
        return $data->sort(function ($a, $b) use ($sortBy, $direction) {
            $valueA = Arr::get($a, $sortBy);
            $valueB = Arr::get($b, $sortBy);
            
            // Handle null values
            if (is_null($valueA) && is_null($valueB)) return 0;
            if (is_null($valueA)) return $direction === 'asc' ? 1 : -1;
            if (is_null($valueB)) return $direction === 'asc' ? -1 : 1;
            
            // Type-aware comparison
            if (is_numeric($valueA) && is_numeric($valueB)) {
                $result = $valueA <=> $valueB;
            } else {
                $result = strcasecmp((string)$valueA, (string)$valueB);
            }
            
            return $direction === 'desc' ? -$result : $result;
        })->values();
    }

    /**
     * Export JSON data to various formats
     */
    public function exportJsonData($format = 'csv', $filename = null)
    {
        $data = $this->getJsonData();
        $filename = $filename ?? 'json_export_' . date('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'csv':
                return $this->exportJsonToCsv($data, $filename);
            case 'json':
                return $this->exportJsonToJson($data, $filename);
            case 'excel':
                return $this->exportJsonToExcel($data, $filename);
            default:
                throw new \Exception("Unsupported export format: {$format}");
        }
    }

    /**
     * Export JSON data to CSV with proper formatting
     */
    protected function exportJsonToCsv(Collection $data, $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            if ($data->isNotEmpty()) {
                // Get headers from first item
                $firstItem = $data->first();
                $headers = $this->flattenArrayKeys($firstItem);
                fputcsv($handle, $headers);

                // Write data rows
                foreach ($data as $item) {
                    $row = [];
                    foreach ($headers as $header) {
                        $value = Arr::get($item, $header, '');
                        $row[] = is_array($value) ? json_encode($value) : $value;
                    }
                    fputcsv($handle, $row);
                }
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export JSON data to JSON format
     */
    protected function exportJsonToJson(Collection $data, $filename)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.json"',
        ];

        return response($data->toJson(JSON_PRETTY_PRINT), 200, $headers);
    }

    /**
     * Export JSON data to Excel (requires additional implementation)
     */
    protected function exportJsonToExcel(Collection $data, $filename)
    {
        // For now, fallback to CSV
        return $this->exportJsonToCsv($data, $filename);
    }

    /**
     * Flatten array keys for CSV headers
     */
    protected function flattenArrayKeys($array, $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value) && !empty($value) && !is_numeric(key($value))) {
                $result = array_merge($result, $this->flattenArrayKeys($value, $newKey));
            } else {
                $result[] = $newKey;
            }
        }
        
        return $result;
    }

    /**
     * Get JSON file statistics and performance metrics
     */
    public function getJsonFileStats(): array
    {
        $stats = [
            'mode' => 'json_file',
            'file_path' => $this->json_file_path,
            'file_exists' => file_exists($this->json_file_path ?? ''),
            'file_size' => 0,
            'file_size_human' => '0 B',
            'last_modified' => null,
            'total_records' => 0,
            'filtered_records' => 0,
            'cache_key' => $this->json_cache_key,
            'cache_enabled' => true,
            'cache_duration' => $this->json_cache_duration,
            'has_error' => !empty($this->json_error),
            'error_message' => $this->json_error,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        if (file_exists($this->json_file_path ?? '')) {
            $fileSize = filesize($this->json_file_path);
            $stats['file_size'] = $fileSize;
            $stats['file_size_human'] = $this->formatBytes($fileSize);
            $stats['last_modified'] = date('Y-m-d H:i:s', filemtime($this->json_file_path));
        }

        if ($this->json_data) {
            $stats['total_records'] = $this->json_data->count();
            $stats['filtered_records'] = $this->getJsonData()->count();
        }

        return $stats;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Refresh JSON data (clear cache and reload)
     */
    public function refreshJsonData()
    {
        Cache::forget($this->json_cache_key);
        Cache::forget($this->json_cache_key . '_time');
        $this->loadJsonData();
        return $this;
    }

    /**
     * Validate JSON file structure and accessibility
     */
    public function validateJsonStructure(): array
    {
        if ($this->json_error) {
            return ['valid' => false, 'error' => $this->json_error];
        }

        if (!$this->json_data || $this->json_data->isEmpty()) {
            return ['valid' => false, 'error' => 'JSON file is empty or contains no valid data'];
        }

        return [
            'valid' => true, 
            'message' => 'JSON structure is valid',
            'record_count' => $this->json_data->count(),
            'file_size' => file_exists($this->json_file_path) ? filesize($this->json_file_path) : 0
        ];
    }

    /**
     * Get total count of filtered JSON data
     */
    public function getJsonDataCount(): int
    {
        return $this->getJsonData()->count();
    }

    /**
     * Clear JSON cache
     */
    public function clearJsonCache()
    {
        Cache::forget($this->json_cache_key);
        Cache::forget($this->json_cache_key . '_time');
        return $this;
    }

    /**
     * Test JSON file accessibility and structure
     */
    public function testJsonFile(): array
    {
        $result = [
            'accessible' => false,
            'valid_json' => false,
            'has_data' => false,
            'record_count' => 0,
            'error' => null,
            'file_info' => []
        ];

        try {
            if (!file_exists($this->json_file_path)) {
                throw new \Exception("JSON file not found: {$this->json_file_path}");
            }

            $result['accessible'] = true;
            $result['file_info'] = [
                'size' => filesize($this->json_file_path),
                'modified' => date('Y-m-d H:i:s', filemtime($this->json_file_path)),
                'readable' => is_readable($this->json_file_path)
            ];

            $content = file_get_contents($this->json_file_path);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $result['valid_json'] = true;
                
                $normalized = $this->normalizeJsonData($data);
                $result['record_count'] = $normalized->count();
                $result['has_data'] = $result['record_count'] > 0;
            } else {
                throw new \Exception("Invalid JSON: " . json_last_error_msg());
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
