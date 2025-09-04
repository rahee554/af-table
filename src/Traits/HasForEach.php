<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

trait HasForEach
{
    /**
     * ForEach configuration
     */
    protected $forEachConfig = [
        'enable_search' => true,
        'enable_filtering' => true,
        'enable_sorting' => true,
        'enable_pagination' => true,
        'items_per_page' => 10,
        'preserve_keys' => false,
        'case_sensitive_search' => false,
        'deep_search' => true, // Search nested array/object properties
    ];

    /**
     * Current foreach data source
     */
    protected $forEachData = null;

    /**
     * Original foreach data (before any processing)
     */
    protected $originalForEachData = null;

    /**
     * ForEach processing statistics
     */
    protected $forEachStats = [
        'total_items' => 0,
        'filtered_items' => 0,
        'search_time' => 0,
        'filter_time' => 0,
        'sort_time' => 0,
        'pagination_time' => 0,
    ];

    /**
     * Legacy properties for backward compatibility
     */
    public $foreachMode = false;
    public $foreachData = null;
    public $foreachChunkSize = 100;
    public $currentForeachItem = null;
    public $foreachCounter = 0;

    /**
     * Set data source for foreach processing
     */
    public function setForEachData($data): self
    {
        if (is_array($data)) {
            $this->originalForEachData = $data;
            $this->forEachData = collect($data);
        } elseif ($data instanceof Collection) {
            $this->originalForEachData = $data->toArray();
            $this->forEachData = $data;
        } elseif (is_iterable($data)) {
            $this->originalForEachData = iterator_to_array($data);
            $this->forEachData = collect($this->originalForEachData);
        } else {
            throw new \InvalidArgumentException('ForEach data must be array, Collection, or iterable');
        }

        $this->forEachStats['total_items'] = $this->forEachData->count();
        
        // Set legacy properties for backward compatibility
        $this->foreachMode = true;
        $this->foreachData = $this->forEachData;
        
        return $this;
    }

    /**
     * Legacy method for backward compatibility
     */
    public function enableForeachMode($data, $chunkSize = 100, $limit = null)
    {
        $this->setForEachData($data);
        $this->foreachChunkSize = $chunkSize;
        $this->foreachCounter = 0;
        
        return $this;
    }

    /**
     * Disable foreach mode
     */
    public function disableForeachMode()
    {
        $this->foreachMode = false;
        $this->foreachData = null;
        $this->currentForeachItem = null;
        $this->foreachCounter = 0;
        
        return $this;
    }

    /**
     * Get data for foreach processing
     */
    public function getForeachData()
    {
        if (!$this->foreachMode || !$this->foreachData) {
            return collect();
        }

        $data = $this->foreachData;

        // Apply search if enabled
        if (!empty($this->search)) {
            $data = $this->applyForeachSearch($data);
        }

        // Apply filters if enabled
        if (!empty($this->filters)) {
            $data = $this->applyForeachFilters($data);
        }

        // Apply sorting if enabled
        if ($this->sortColumn) {
            $data = $this->applyForeachSorting($data);
        }

        return $data;
    }

    /**
     * Apply comprehensive search to foreach data with deep object/array traversal
     */
    protected function applyForeachSearch($data)
    {
        if (empty($this->search)) {
            return $data;
        }

        $startTime = microtime(true);
        $searchTerm = $this->forEachConfig['case_sensitive_search'] 
            ? trim($this->search) 
            : strtolower(trim($this->search));
        
        $filtered = $data->filter(function ($item) use ($searchTerm) {
            return $this->searchInItem($item, $searchTerm, $this->forEachConfig['deep_search']);
        });

        $this->forEachStats['search_time'] = microtime(true) - $startTime;
        $this->forEachStats['filtered_items'] = $filtered->count();
        
        return $filtered;
    }

    /**
     * Search within an item (supports deep search)
     */
    protected function searchInItem($item, $searchTerm, $deep = true, $depth = 0)
    {
        // Prevent infinite recursion
        if ($depth > 10) {
            return false;
        }

        $itemArray = is_object($item) ? (array) $item : (is_array($item) ? $item : [$item]);
        
        foreach ($itemArray as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $haystack = $this->forEachConfig['case_sensitive_search'] 
                    ? (string) $value 
                    : strtolower((string) $value);
                    
                if (str_contains($haystack, $searchTerm)) {
                    return true;
                }
            } elseif ($deep && (is_array($value) || is_object($value))) {
                if ($this->searchInItem($value, $searchTerm, true, $depth + 1)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Apply comprehensive filters to foreach data
     */
    protected function applyForeachFilters($data)
    {
        if (empty($this->filters)) {
            return $data;
        }

        $startTime = microtime(true);
        
        foreach ($this->filters as $column => $filter) {
            if (empty($filter['value']) && $filter['value'] !== '0' && $filter['value'] !== 0) {
                continue;
            }

            $data = $data->filter(function ($item) use ($column, $filter) {
                return $this->evaluateFilterCondition($item, $column, $filter);
            });
        }

        $this->forEachStats['filter_time'] = microtime(true) - $startTime;
        
        return $data;
    }

    /**
     * Evaluate filter condition for an item
     */
    protected function evaluateFilterCondition($item, $column, $filter)
    {
        $value = $this->getItemValue($item, $column);
        $operator = $filter['operator'] ?? '=';
        $filterValue = $filter['value'];

        // Handle null values
        if ($value === null) {
            return in_array($operator, ['!=', 'not_equal', 'is_null']) 
                ? ($operator === 'is_null' ? true : $filterValue !== null)
                : false;
        }

        switch ($operator) {
            case '=':
            case 'equal':
                return $this->compareValues($value, $filterValue, '=');
                
            case '!=':
            case 'not_equal':
                return $this->compareValues($value, $filterValue, '!=');
                
            case '>':
            case 'greater_than':
                return $this->compareValues($value, $filterValue, '>');
                
            case '>=':
            case 'greater_equal':
                return $this->compareValues($value, $filterValue, '>=');
                
            case '<':
            case 'less_than':
                return $this->compareValues($value, $filterValue, '<');
                
            case '<=':
            case 'less_equal':
                return $this->compareValues($value, $filterValue, '<=');
                
            case 'like':
            case 'contains':
                return str_contains(strtolower((string) $value), strtolower((string) $filterValue));
                
            case 'not_like':
            case 'not_contains':
                return !str_contains(strtolower((string) $value), strtolower((string) $filterValue));
                
            case 'starts_with':
                return str_starts_with(strtolower((string) $value), strtolower((string) $filterValue));
                
            case 'ends_with':
                return str_ends_with(strtolower((string) $value), strtolower((string) $filterValue));
                
            case 'in':
                $filterArray = is_array($filterValue) ? $filterValue : explode(',', (string) $filterValue);
                return in_array($value, array_map('trim', $filterArray));
                
            case 'not_in':
                $filterArray = is_array($filterValue) ? $filterValue : explode(',', (string) $filterValue);
                return !in_array($value, array_map('trim', $filterArray));
                
            case 'between':
                if (is_array($filterValue) && count($filterValue) === 2) {
                    return $value >= $filterValue[0] && $value <= $filterValue[1];
                }
                return false;
                
            case 'is_null':
                return $value === null;
                
            case 'is_not_null':
                return $value !== null;
                
            case 'date_equal':
                return $this->compareDates($value, $filterValue, '=');
                
            case 'date_before':
                return $this->compareDates($value, $filterValue, '<');
                
            case 'date_after':
                return $this->compareDates($value, $filterValue, '>');
                
            default:
                return $value == $filterValue;
        }
    }

    /**
     * Get value from item using dot notation
     */
    protected function getItemValue($item, $key)
    {
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = $item;
            
            foreach ($keys as $nestedKey) {
                if (is_object($value)) {
                    $value = $value->$nestedKey ?? null;
                } elseif (is_array($value)) {
                    $value = $value[$nestedKey] ?? null;
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        return is_object($item) ? $item->$key ?? null : ($item[$key] ?? null);
    }

    /**
     * Compare values with type intelligence
     */
    protected function compareValues($value1, $value2, $operator)
    {
        // Type casting for better comparison
        if (is_numeric($value1) && is_numeric($value2)) {
            $value1 = (float) $value1;
            $value2 = (float) $value2;
        }

        switch ($operator) {
            case '=': return $value1 == $value2;
            case '!=': return $value1 != $value2;
            case '>': return $value1 > $value2;
            case '>=': return $value1 >= $value2;
            case '<': return $value1 < $value2;
            case '<=': return $value1 <= $value2;
            default: return $value1 == $value2;
        }
    }

    /**
     * Compare dates
     */
    protected function compareDates($date1, $date2, $operator)
    {
        try {
            $d1 = new \DateTime($date1);
            $d2 = new \DateTime($date2);
            
            switch ($operator) {
                case '=': return $d1->format('Y-m-d') === $d2->format('Y-m-d');
                case '<': return $d1 < $d2;
                case '>': return $d1 > $d2;
                default: return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Apply intelligent sorting to foreach data
     */
    protected function applyForeachSorting($data)
    {
        if (empty($this->sortColumn)) {
            return $data;
        }

        $startTime = microtime(true);
        $column = $this->sortColumn;
        $direction = $this->sortDirection === 'desc';

        $sorted = $data->sortBy(function ($item) use ($column) {
            $value = $this->getItemValue($item, $column);
            
            // Handle different data types for better sorting
            if (is_numeric($value)) {
                return (float) $value;
            } elseif ($this->isDateString($value)) {
                try {
                    return new \DateTime($value);
                } catch (\Exception $e) {
                    return $value;
                }
            } else {
                return strtolower((string) $value);
            }
        }, SORT_REGULAR, $direction);

        $this->forEachStats['sort_time'] = microtime(true) - $startTime;
        
        return $sorted;
    }

    /**
     * Check if string is a date
     */
    protected function isDateString($value)
    {
        if (!is_string($value)) {
            return false;
        }
        
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $value) || 
               (bool) preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value);
    }

    /**
     * Get paginated foreach data
     */
    public function getPaginatedForeachData()
    {
        if (!$this->forEachConfig['enable_pagination']) {
            return $this->getForeachData();
        }

        $startTime = microtime(true);
        $data = $this->getForeachData();
        $perPage = $this->forEachConfig['items_per_page'];
        $currentPage = request()->get('page', 1);
        
        $total = $data->count();
        $items = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        $this->forEachStats['pagination_time'] = microtime(true) - $startTime;
        
        return $paginator;
    }

    /**
     * Export foreach data
     */
    public function exportForeachData($format = 'csv', $filename = null)
    {
        $data = $this->getForeachData();
        $filename = $filename ?: 'foreach_export_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        switch (strtolower($format)) {
            case 'csv':
                return $this->exportForeachToCsv($data, $filename);
            case 'json':
                return $this->exportForeachToJson($data, $filename);
            case 'xlsx':
                return $this->exportForeachToExcel($data, $filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: $format");
        }
    }

    /**
     * Export to CSV
     */
    protected function exportForeachToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            if ($data->isNotEmpty()) {
                $firstItem = $data->first();
                $headers = is_object($firstItem) ? array_keys((array) $firstItem) : array_keys($firstItem);
                fputcsv($file, $headers);
            }
            
            // Write data
            foreach ($data as $item) {
                $row = is_object($item) ? array_values((array) $item) : array_values($item);
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to JSON
     */
    protected function exportForeachToJson($data, $filename)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->json($data->values()->all(), 200, $headers);
    }

    /**
     * Export to Excel (requires additional package)
     */
    protected function exportForeachToExcel($data, $filename)
    {
        // This would require PhpSpreadsheet or similar
        throw new \Exception('Excel export requires additional package installation');
    }

    /**
     * Configure foreach behavior
     */
    public function configureForeEach(array $config): self
    {
        $this->forEachConfig = array_merge($this->forEachConfig, $config);
        return $this;
    }

    /**
     * Get foreach statistics
     */
    public function getForeachStats(): array
    {
        return $this->forEachStats;
    }

    /**
     * Validate foreach data
     */
    public function validateForeachData($data): array
    {
        $errors = [];
        
        if (empty($data)) {
            $errors[] = 'ForEach data cannot be empty';
        }
        
        if (!is_array($data) && !($data instanceof Collection) && !is_iterable($data)) {
            $errors[] = 'ForEach data must be array, Collection, or iterable';
        }
        
        return $errors;
    }

    /**
     * Reset foreach processing
     */
    public function resetForeachProcessing(): self
    {
        $this->forEachStats = [
            'total_items' => 0,
            'filtered_items' => 0,
            'search_time' => 0,
            'filter_time' => 0,
            'sort_time' => 0,
            'pagination_time' => 0,
        ];
        
        $this->currentForeachItem = null;
        $this->foreachCounter = 0;
        
        return $this;
    }

    /**
     * Process foreach item with enhanced functionality
     */
    public function processForeachItem($item, $callback = null)
    {
        $this->currentForeachItem = $item;
        $this->foreachCounter++;

        try {
            $result = $callback ? call_user_func($callback, $item, $this) : $item;
            
            // Validate result if validation is enabled
            if (isset($this->forEachConfig['validate_processed_items']) && 
                $this->forEachConfig['validate_processed_items']) {
                $this->validateProcessedItem($result);
            }
            
            return $result;
        } catch (\Exception $e) {
            \Log::error("ForEach processing error: " . $e->getMessage(), [
                'item' => $item,
                'counter' => $this->foreachCounter
            ]);
            
            return $this->forEachConfig['return_null_on_error'] ?? true ? null : $item;
        }
    }

    /**
     * Validate processed item
     */
    protected function validateProcessedItem($item)
    {
        // Add custom validation logic here
        return true;
    }

    /**
     * Batch process foreach items
     */
    public function batchProcessForeachItems($callback, $batchSize = 100)
    {
        $data = $this->getForeachData();
        $results = collect();
        
        $data->chunk($batchSize)->each(function ($chunk) use ($callback, &$results) {
            $batchResults = $chunk->map(function ($item) use ($callback) {
                return $this->processForeachItem($item, $callback);
            });
            
            $results = $results->merge($batchResults);
        });
        
        return $results;
    }

    /**
     * Check if foreach mode is active
     */
    public function isForeachMode(): bool
    {
        return $this->foreachMode && $this->foreachData !== null;
    }

    /**
     * Get foreach data count
     */
    public function getForeachCount(): int
    {
        return $this->forEachData ? $this->forEachData->count() : 0;
    }

    /**
     * Get processed foreach data count
     */
    public function getProcessedForeachCount(): int
    {
        return $this->getForeachData()->count();
    }
}
