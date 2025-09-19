<?php

namespace ArtflowStudio\Table\Traits\Advanced;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Client\RequestException;

trait HasApiEndpoint
{
    /**
     * API endpoint configuration
     */
    protected $apiConfig = [
        'base_url' => null,
        'endpoint' => null,
        'method' => 'GET',
        'headers' => [],
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
        'cache_duration' => 300, // 5 minutes
        'cache_enabled' => true,
        'rate_limiting' => [
            'enabled' => false,
            'max_requests' => 60,
            'per_minutes' => 1,
        ],
        'authentication' => [
            'type' => null, // bearer, basic, api_key, custom
            'token' => null,
            'username' => null,
            'password' => null,
            'api_key_name' => 'X-API-Key',
            'custom_headers' => [],
        ],
        'pagination' => [
            'enabled' => true,
            'type' => 'page', // page, offset, cursor
            'page_param' => 'page',
            'per_page_param' => 'per_page',
            'size_param' => 'size',
            'offset_param' => 'offset',
            'limit_param' => 'limit',
            'cursor_param' => 'cursor',
            'default_per_page' => 50,
            'max_per_page' => 1000,
        ],
        'data_path' => null, // JSON path to data array (e.g., 'data', 'results.items')
        'meta_path' => null, // JSON path to metadata (e.g., 'meta', 'pagination')
        'transform_response' => true,
        'validate_response' => true,
    ];

    /**
     * Clear API-related cache
     */
    public function clearApiCache(): self
    {
        $pattern = 'api_datatable_*';
        
        // Use targeted cache clearing instead of flushing all cache
        if (method_exists($this, 'clearCacheByPattern')) {
            $this->clearCacheByPattern($pattern);
        } else {
            // Fallback: only flush in development/testing environments
            if (app()->environment(['testing', 'local'])) {
                Cache::flush();
            } else {
                Log::warning("Unable to clear API cache pattern: {$pattern}. clearCacheByPattern method not available.");
            }
        }
        
        return $this;
    }

    /**
     * API endpoint properties
     */
    protected $apiEndpoint = null;
    protected $apiData = null;
    protected $apiMeta = null;
    protected $apiErrors = [];
    protected $apiStats = [
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'cached_requests' => 0,
        'average_response_time' => 0,
        'last_request_time' => null,
        'last_response_time' => 0,
    ];

    /**
     * Rate limiting storage
     */
    protected $rateLimitCache = [];

    /**
     * Set API endpoint configuration
     */
    public function setApiEndpoint($endpoint, array $config = []): self
    {
        $this->apiEndpoint = $endpoint;
        $this->apiConfig = array_merge($this->apiConfig, $config);
        
        // Validate configuration
        $this->validateApiConfig();
        
        return $this;
    }

    /**
     * Configure API settings
     */
    public function configureApi(array $config): self
    {
        $this->apiConfig = array_merge($this->apiConfig, $config);
        $this->validateApiConfig();
        
        return $this;
    }

    /**
     * Fetch data from API endpoint
     */
    public function fetchApiData(array $params = [])
    {
        if (!$this->apiEndpoint) {
            throw new \InvalidArgumentException('API endpoint not configured');
        }

        // Check rate limiting
        if ($this->isRateLimited()) {
            throw new \Exception('Rate limit exceeded. Please try again later.');
        }

        $cacheKey = $this->generateApiCacheKey($params);
        
        // Return cached data if available and enabled
        if ($this->apiConfig['cache_enabled'] && Cache::has($cacheKey)) {
            $this->apiStats['cached_requests']++;
            return $this->processApiCachedData(Cache::get($cacheKey));
        }

        $startTime = microtime(true);
        
        try {
            $response = $this->makeApiRequest($params);
            $responseTime = microtime(true) - $startTime;
            
            $this->updateApiStats(true, $responseTime);
            
            // Process and validate response
            $data = $this->processApiResponse($response);
            
            // Cache the response if enabled
            if ($this->apiConfig['cache_enabled']) {
                Cache::put($cacheKey, $data, $this->apiConfig['cache_duration']);
            }
            
            return $data;
            
        } catch (\Exception $e) {
            $responseTime = microtime(true) - $startTime;
            $this->updateApiStats(false, $responseTime);
            $this->logApiError($e, $params);
            
            throw $e;
        }
    }

    /**
     * Make HTTP request to API
     */
    protected function makeApiRequest(array $params = [])
    {
        $url = $this->buildApiUrl();
        $method = strtoupper($this->apiConfig['method']);
        
        // Build HTTP client
        $client = Http::timeout($this->apiConfig['timeout'])
                     ->withHeaders($this->buildHeaders())
                     ->retry(
                         $this->apiConfig['retry_attempts'],
                         $this->apiConfig['retry_delay']
                     );

        // Add authentication
        $client = $this->addAuthentication($client);

        // Add search and filter parameters
        $queryParams = $this->buildQueryParameters($params);

        switch ($method) {
            case 'GET':
                return $client->get($url, $queryParams);
                
            case 'POST':
                return $client->post($url, $queryParams);
                
            case 'PUT':
                return $client->put($url, $queryParams);
                
            case 'PATCH':
                return $client->patch($url, $queryParams);
                
            case 'DELETE':
                return $client->delete($url, $queryParams);
                
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: $method");
        }
    }

    /**
     * Build API URL
     */
    protected function buildApiUrl(): string
    {
        $baseUrl = rtrim($this->apiConfig['base_url'] ?? '', '/');
        $endpoint = ltrim($this->apiEndpoint, '/');
        
        return $baseUrl ? "$baseUrl/$endpoint" : $endpoint;
    }

    /**
     * Build HTTP headers
     */
    protected function buildHeaders(): array
    {
        $headers = $this->apiConfig['headers'] ?? [];
        
        // Add default headers
        $headers['Accept'] = $headers['Accept'] ?? 'application/json';
        $headers['User-Agent'] = $headers['User-Agent'] ?? 'ArtflowStudio-Datatable/1.0';
        
        return $headers;
    }

    /**
     * Add authentication to HTTP client
     */
    protected function addAuthentication($client)
    {
        $auth = $this->apiConfig['authentication'];
        
        switch ($auth['type']) {
            case 'bearer':
                if ($auth['token']) {
                    $client = $client->withToken($auth['token']);
                }
                break;
                
            case 'basic':
                if ($auth['username'] && $auth['password']) {
                    $client = $client->withBasicAuth($auth['username'], $auth['password']);
                }
                break;
                
            case 'api_key':
                if ($auth['token'] && $auth['api_key_name']) {
                    $client = $client->withHeaders([
                        $auth['api_key_name'] => $auth['token']
                    ]);
                }
                break;
                
            case 'custom':
                if (!empty($auth['custom_headers'])) {
                    $client = $client->withHeaders($auth['custom_headers']);
                }
                break;
        }
        
        return $client;
    }

    /**
     * Build query parameters including search, filters, and pagination
     */
    protected function buildQueryParameters(array $params = []): array
    {
        $queryParams = $params;
        
        // Add search parameter
        if (!empty($this->search)) {
            $queryParams['search'] = $this->search;
            $queryParams['q'] = $this->search; // Common alternative
        }
        
        // Add filter parameters
        if (!empty($this->filters)) {
            foreach ($this->filters as $key => $filter) {
                if (!empty($filter['value'])) {
                    $queryParams["filter[$key]"] = $filter['value'];
                    $queryParams[$key] = $filter['value']; // Alternative format
                }
            }
        }
        
        // Add sorting parameters
        if (!empty($this->sortColumn)) {
            $queryParams['sort'] = $this->sortColumn;
            $queryParams['order'] = $this->sortDirection;
            $queryParams['sort_by'] = $this->sortColumn; // Alternative format
            $queryParams['sort_dir'] = $this->sortDirection;
        }
        
        // Add pagination parameters
        if ($this->apiConfig['pagination']['enabled']) {
            $pagination = $this->apiConfig['pagination'];
            $perPage = $this->perPage ?? $pagination['default_per_page'];
            $currentPage = $this->getCurrentApiPage();
            
            switch ($pagination['type']) {
                case 'page':
                    $queryParams[$pagination['page_param']] = $currentPage;
                    $queryParams[$pagination['per_page_param']] = $perPage;
                    break;
                    
                case 'offset':
                    $offset = ($currentPage - 1) * $perPage;
                    $queryParams[$pagination['offset_param']] = $offset;
                    $queryParams[$pagination['limit_param']] = $perPage;
                    break;
                    
                case 'cursor':
                    if (request()->has('cursor')) {
                        $queryParams[$pagination['cursor_param']] = request('cursor');
                    }
                    $queryParams[$pagination['size_param']] = $perPage;
                    break;
            }
        }
        
        return $queryParams;
    }

    /**
     * Process API response
     */
    protected function processApiResponse($response)
    {
        if (!$response->successful()) {
            throw new RequestException($response);
        }
        
        $data = $response->json();
        
        if ($this->apiConfig['validate_response']) {
            $this->validateApiResponse($data);
        }
        
        // Extract data from specified path
        $extractedData = $this->extractDataFromPath($data, $this->apiConfig['data_path']);
        
        // Extract metadata if specified
        $this->apiMeta = $this->extractDataFromPath($data, $this->apiConfig['meta_path']);
        
        // Transform response if enabled
        if ($this->apiConfig['transform_response']) {
            $extractedData = $this->transformApiData($extractedData);
        }
        
        $this->apiData = collect($extractedData);
        
        return $this->apiData;
    }

    /**
     * Extract data from JSON path
     */
    protected function extractDataFromPath($data, $path)
    {
        if (!$path) {
            return $data;
        }
        
        $keys = explode('.', $path);
        $current = $data;
        
        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } elseif (is_object($current) && isset($current->$key)) {
                $current = $current->$key;
            } else {
                return null;
            }
        }
        
        return $current;
    }

    /**
     * Transform API data to standardized format
     */
    protected function transformApiData($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        $transformed = [];
        foreach ($data as $item) {
            // Convert objects to arrays for consistent handling
            if (is_object($item)) {
                $item = (array) $item;
            }
            
            // Add any custom transformations here
            $transformed[] = $this->transformApiItem($item);
        }
        
        return $transformed;
    }

    /**
     * Transform individual API item
     */
    protected function transformApiItem($item)
    {
        // Override this method in your implementation for custom transformations
        return $item;
    }

    /**
     * Get API data for datatable
     */
    public function getApiData()
    {
        if (!$this->apiData) {
            $this->fetchApiData();
        }
        
        return $this->apiData;
    }

    /**
     * Get paginated API data
     */
    public function getPaginatedApiData()
    {
        $data = $this->getApiData();
        
        // If API handles pagination, return the data as-is
        if ($this->apiConfig['pagination']['enabled'] && $this->apiMeta) {
            return $this->buildApiPaginator($data);
        }
        
        // Otherwise, paginate locally
        return $this->paginateApiDataLocally($data);
    }

    /**
     * Build paginator from API metadata
     */
    protected function buildApiPaginator($data)
    {
        $meta = $this->apiMeta;
        
        // Extract pagination info from metadata
        $total = $meta['total'] ?? $meta['count'] ?? $data->count();
        $perPage = $meta['per_page'] ?? $meta['page_size'] ?? $this->perPage;
        $currentPage = $meta['current_page'] ?? $meta['page'] ?? $this->getCurrentApiPage();
        
        return new LengthAwarePaginator(
            $data,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Paginate API data locally
     */
    protected function paginateApiDataLocally($data)
    {
        $perPage = $this->perPage ?? $this->apiConfig['pagination']['default_per_page'];
        $currentPage = $this->getCurrentApiPage();
        $total = $data->count();
        
        $items = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Search API data locally (if not handled by API)
     */
    public function searchApiData($data, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $data;
        }
        
        return $data->filter(function ($item) use ($searchTerm) {
            $itemArray = is_object($item) ? (array) $item : $item;
            
            foreach ($itemArray as $value) {
                if (is_string($value) || is_numeric($value)) {
                    if (str_contains(strtolower((string) $value), strtolower($searchTerm))) {
                        return true;
                    }
                }
            }
            
            return false;
        });
    }

    /**
     * Filter API data locally (if not handled by API)
     */
    public function filterApiData($data, $filters)
    {
        foreach ($filters as $column => $filter) {
            if (empty($filter['value'])) {
                continue;
            }
            
            $data = $data->filter(function ($item) use ($column, $filter) {
                $value = is_object($item) ? $item->$column ?? null : ($item[$column] ?? null);
                $operator = $filter['operator'] ?? '=';
                $filterValue = $filter['value'];
                
                switch ($operator) {
                    case '=':
                        return $value == $filterValue;
                    case 'like':
                        return str_contains(strtolower((string) $value), strtolower((string) $filterValue));
                    case '>':
                        return $value > $filterValue;
                    case '<':
                        return $value < $filterValue;
                    default:
                        return $value == $filterValue;
                }
            });
        }
        
        return $data;
    }

    /**
     * Sort API data locally (if not handled by API)
     */
    public function sortApiData($data, $sortColumn, $sortDirection)
    {
        if (empty($sortColumn)) {
            return $data;
        }
        
        $direction = $sortDirection === 'desc';
        
        return $data->sortBy(function ($item) use ($sortColumn) {
            return is_object($item) ? $item->$sortColumn ?? null : ($item[$sortColumn] ?? null);
        }, SORT_REGULAR, $direction);
    }

    /**
     * Generate cache key for API request
     */
    protected function generateApiCacheKey(array $params = []): string
    {
        $keyData = [
            'endpoint' => $this->apiEndpoint,
            'params' => $params,
            'search' => $this->search ?? '',
            'filters' => $this->filters ?? [],
            'sort' => $this->sortColumn ?? '',
            'direction' => $this->sortDirection ?? '',
            'page' => $this->getCurrentApiPage(),
            'per_page' => $this->perPage ?? 10,
        ];
        
        return 'api_datatable_' . md5(json_encode($keyData));
    }

    /**
     * Check if rate limited
     */
    protected function isRateLimited(): bool
    {
        if (!$this->apiConfig['rate_limit']['enabled']) {
            return false;
        }
        
        $maxRequests = $this->apiConfig['rate_limit']['max_requests'];
        $perMinutes = $this->apiConfig['rate_limit']['per_minutes'];
        $cacheKey = 'api_rate_limit_' . md5($this->apiEndpoint);
        
        $requests = Cache::get($cacheKey, []);
        $now = now();
        
        // Remove old requests
        $requests = array_filter($requests, function ($timestamp) use ($now, $perMinutes) {
            return $now->diffInMinutes($timestamp) < $perMinutes;
        });
        
        if (count($requests) >= $maxRequests) {
            return true;
        }
        
        // Add current request
        $requests[] = $now;
        Cache::put($cacheKey, $requests, $perMinutes * 60);
        
        return false;
    }

    /**
     * Update API statistics
     */
    protected function updateApiStats(bool $successful, float $responseTime)
    {
        $this->apiStats['total_requests']++;
        $this->apiStats['last_request_time'] = now();
        $this->apiStats['last_response_time'] = $responseTime;
        
        if ($successful) {
            $this->apiStats['successful_requests']++;
        } else {
            $this->apiStats['failed_requests']++;
        }
        
        // Calculate average response time
        $totalRequests = $this->apiStats['total_requests'];
        $currentAverage = $this->apiStats['average_response_time'];
        $this->apiStats['average_response_time'] = (($currentAverage * ($totalRequests - 1)) + $responseTime) / $totalRequests;
    }

    /**
     * Validate API configuration
     */
    protected function validateApiConfig()
    {
        $errors = [];
        
        if (empty($this->apiConfig['base_url']) && !filter_var($this->apiEndpoint, FILTER_VALIDATE_URL)) {
            $errors[] = 'Either base_url must be set or endpoint must be a valid URL';
        }
        
        if (!in_array(strtoupper($this->apiConfig['method']), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            $errors[] = 'Invalid HTTP method specified';
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException('API configuration errors: ' . implode(', ', $errors));
        }
    }

    /**
     * Validate API response
     */
    protected function validateApiResponse($data)
    {
        // Add custom validation logic here
        if (!is_array($data) && !is_object($data)) {
            throw new \Exception('Invalid API response format');
        }
    }

    /**
     * Process cached API data
     */
    protected function processApiCachedData($cachedData)
    {
        $this->apiData = collect($cachedData);
        return $this->apiData;
    }

    /**
     * Log API error
     */
    protected function logApiError(\Exception $e, array $params = [])
    {
        $this->apiErrors[] = [
            'message' => $e->getMessage(),
            'params' => $params,
            'timestamp' => now(),
        ];
        
        Log::error('API Datatable Error', [
            'endpoint' => $this->apiEndpoint,
            'error' => $e->getMessage(),
            'params' => $params,
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Get current page number for API requests
     */
    protected function getCurrentApiPage(): int
    {
        return request()->get('page', 1);
    }

    /**
     * Get API statistics
     */
    public function getApiStats(): array
    {
        return $this->apiStats;
    }

    /**
     * Get API errors
     */
    public function getApiErrors(): array
    {
        return $this->apiErrors;
    }

    /**
     * Test API connection
     */
    public function testApiConnection(): array
    {
        try {
            $startTime = microtime(true);
            $response = $this->makeApiRequest(['test' => true]);
            $responseTime = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'status_code' => $response->status(),
                'response_time' => $responseTime,
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time' => microtime(true) - $startTime,
            ];
        }
    }

    /**
     * Export API data
     */
    public function exportApiData($format = 'csv', $filename = null)
    {
        $data = $this->getApiData();
        $filename = $filename ?: 'api_export_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        switch (strtolower($format)) {
            case 'csv':
                return $this->exportApiToCsv($data, $filename);
            case 'json':
                return $this->exportApiToJson($data, $filename);
            case 'xlsx':
                return $this->exportApiToExcel($data, $filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: $format");
        }
    }

    /**
     * Export API data to CSV
     */
    protected function exportApiToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            if ($data->isNotEmpty()) {
                $firstItem = $data->first();
                $headers = is_object($firstItem) ? array_keys((array) $firstItem) : array_keys($firstItem);
                fputcsv($file, $headers);
            }
            
            foreach ($data as $item) {
                $row = is_object($item) ? array_values((array) $item) : array_values($item);
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export API data to JSON
     */
    protected function exportApiToJson($data, $filename)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->json($data->values()->all(), 200, $headers);
    }

    /**
     * Export API data to Excel
     */
    protected function exportApiToExcel($data, $filename)
    {
        throw new \Exception('Excel export requires additional package installation');
    }

    /**
     * Check if using API mode
     */
    public function isApiMode(): bool
    {
        return !empty($this->apiEndpoint);
    }

    /**
     * Refresh API data
     */
    public function refreshApiData(): self
    {
        $this->clearApiCache();
        $this->apiData = null;
        $this->apiMeta = null;
        $this->apiErrors = [];
        
        return $this;
    }
}
