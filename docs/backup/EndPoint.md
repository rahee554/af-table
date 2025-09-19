# API Endpoint Integration Documentation

## Overview

The `HasApiEndpoint` trait enables the DatatableTrait to fetch and display data from external API endpoints while maintaining all the powerful features of the datatable system (search, filtering, sorting, pagination, export). This allows you to work with external APIs just like you would with Eloquent models.

## Purpose

The API endpoint integration allows you to:

- Fetch data from external REST APIs
- Apply datatable features (search, filter, sort, paginate) to API data
- Handle authentication with various methods (Bearer tokens, API keys, Basic auth)
- Implement intelligent caching for performance
- Manage rate limiting and error handling
- Export API data in multiple formats
- Transform and normalize API responses

## Key Features

### 1. Flexible Authentication
- **Bearer Token Authentication**: For OAuth and JWT tokens
- **API Key Authentication**: Header-based or query parameter
- **Basic Authentication**: Username/password combinations
- **Custom Headers**: Support for custom authentication headers

### 2. Intelligent Caching
- **Response Caching**: Cache API responses to reduce requests
- **Smart Cache Keys**: Generate cache keys based on request parameters
- **Cache Invalidation**: Automatic and manual cache management
- **Configurable TTL**: Set cache timeouts per endpoint

### 3. Rate Limiting & Performance
- **Request Rate Limiting**: Prevent API quota exhaustion
- **Retry Logic**: Automatic retry on transient failures
- **Timeout Management**: Configurable request timeouts
- **Connection Pooling**: Efficient HTTP connection management

### 4. Data Processing
- **Response Transformation**: Transform API responses to consistent format
- **Nested Data Handling**: Extract data from nested API responses
- **Pagination Support**: Handle various API pagination patterns
- **Error Handling**: Graceful error handling and fallbacks

## Basic Setup

### Simple API Configuration

```php
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class ApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        // Configure the API endpoint
        $this->setApiEndpoint('https://jsonplaceholder.typicode.com/users', [
            'method' => 'GET',
            'timeout' => 30,
            'cache_enabled' => true,
            'cache_ttl' => 3600, // 1 hour
        ]);

        // Configure columns for API data
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
            'website' => ['key' => 'website', 'label' => 'Website'],
            'company' => ['key' => 'company.name', 'label' => 'Company'],
        ];
    }

    public function render()
    {
        $data = $this->getApiData();
        
        return view('livewire.api-table', [
            'data' => $data,
            'stats' => $this->getApiStats()
        ]);
    }
}
```

### API with Authentication

```php
class AuthenticatedApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        // API with Bearer token authentication
        $this->setApiEndpoint('https://api.example.com/users', [
            'method' => 'GET',
            'auth_type' => 'bearer',
            'auth_token' => config('services.api.token'),
            'timeout' => 30,
            'cache_enabled' => true,
        ]);

        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
        ];
    }
}
```

### API with Custom Headers

```php
class CustomHeaderApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->configureApi([
            'url' => 'https://api.example.com/data',
            'method' => 'GET',
            'headers' => [
                'X-API-Key' => config('services.api.key'),
                'Accept' => 'application/json',
                'User-Agent' => 'MyApp/1.0',
            ],
            'timeout' => 30,
            'rate_limit' => [
                'requests' => 100,
                'per_minutes' => 60,
            ],
        ]);

        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'title' => ['key' => 'title', 'label' => 'Title'],
            'status' => ['key' => 'status', 'label' => 'Status'],
        ];
    }
}
```

## Authentication Methods

### Bearer Token Authentication

```php
$this->setApiEndpoint('https://api.example.com/users', [
    'auth_type' => 'bearer',
    'auth_token' => 'your-bearer-token-here',
]);

// Or with dynamic token
$this->setApiEndpoint('https://api.example.com/users', [
    'auth_type' => 'bearer',
    'auth_token' => auth()->user()->api_token,
]);
```

### API Key Authentication

```php
// API key in header
$this->setApiEndpoint('https://api.example.com/users', [
    'auth_type' => 'api_key',
    'auth_key' => 'X-API-Key',
    'auth_value' => 'your-api-key-here',
]);

// API key in query parameter
$this->setApiEndpoint('https://api.example.com/users', [
    'auth_type' => 'api_key',
    'auth_key' => 'api_key',
    'auth_location' => 'query',
    'auth_value' => 'your-api-key-here',
]);
```

### Basic Authentication

```php
$this->setApiEndpoint('https://api.example.com/users', [
    'auth_type' => 'basic',
    'auth_username' => 'your-username',
    'auth_password' => 'your-password',
]);
```

### Custom Authentication

```php
$this->configureApi([
    'url' => 'https://api.example.com/users',
    'headers' => [
        'Authorization' => 'Custom ' . base64_encode('custom:auth:string'),
        'X-Custom-Header' => 'custom-value',
    ],
]);
```

## Data Transformation

### Basic Response Transformation

```php
class TransformApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->setApiEndpoint('https://api.example.com/users');
        
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
        ];
    }

    // Transform API response data
    protected function transformApiResponse($response)
    {
        // Handle nested response structure
        if (isset($response['data']['users'])) {
            return $response['data']['users'];
        }

        // Handle different response format
        if (isset($response['items'])) {
            return collect($response['items'])->map(function ($item) {
                return [
                    'id' => $item['user_id'],
                    'name' => $item['full_name'],
                    'email' => $item['email_address'],
                ];
            })->toArray();
        }

        return $response;
    }
}
```

### Complex Data Transformation

```php
class ComplexApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->setApiEndpoint('https://api.complex-service.com/data');
        
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'title' => ['key' => 'title', 'label' => 'Title'],
            'author' => ['key' => 'author', 'label' => 'Author'],
            'published' => ['key' => 'published_date', 'label' => 'Published'],
            'category' => ['key' => 'category', 'label' => 'Category'],
        ];
    }

    protected function transformApiResponse($response)
    {
        // Extract data from complex nested structure
        $items = data_get($response, 'result.items', []);
        
        return collect($items)->map(function ($item) {
            return [
                'id' => $item['id'],
                'title' => $item['attributes']['title'],
                'author' => data_get($item, 'relationships.author.data.attributes.name', 'Unknown'),
                'published_date' => $item['attributes']['published_at'],
                'category' => data_get($item, 'attributes.category.name', 'Uncategorized'),
            ];
        })->toArray();
    }
}
```

## Advanced Features

### Pagination Support

```php
class PaginatedApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->setApiEndpoint('https://api.example.com/users');
        
        // Configure pagination
        $this->apiPaginationConfig = [
            'page_param' => 'page',
            'per_page_param' => 'limit',
            'total_key' => 'total',
            'data_key' => 'data',
        ];
    }

    // Get paginated API data
    public function getPaginatedApiData()
    {
        $params = [
            'page' => $this->page,
            'limit' => $this->perPage,
            'search' => $this->search,
            'sort' => $this->sortColumn,
            'direction' => $this->sortDirection,
        ];

        // Add filters to API request
        foreach ($this->filters as $key => $filter) {
            $params["filter[{$key}]"] = $filter['value'];
        }

        return $this->fetchApiData($params);
    }
}
```

### Search and Filtering

```php
class SearchableApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->setApiEndpoint('https://api.example.com/search');
    }

    // Custom search implementation for API
    public function searchApiData($search = null)
    {
        $search = $search ?? $this->search;
        
        if (empty($search)) {
            return $this->getApiData();
        }

        $params = [
            'q' => $search,
            'fields' => implode(',', $this->getSearchableFields()),
        ];

        return $this->fetchApiData($params);
    }

    // Custom filtering for API
    public function filterApiData($filters = null)
    {
        $filters = $filters ?? $this->filters;
        
        $params = [];
        foreach ($filters as $key => $filter) {
            switch ($filter['operator']) {
                case 'equals':
                    $params[$key] = $filter['value'];
                    break;
                case 'like':
                    $params[$key . '_like'] = $filter['value'];
                    break;
                case 'greater_than':
                    $params[$key . '_gt'] = $filter['value'];
                    break;
                case 'less_than':
                    $params[$key . '_lt'] = $filter['value'];
                    break;
            }
        }

        return $this->fetchApiData($params);
    }

    private function getSearchableFields()
    {
        return collect($this->columns)
            ->filter(function ($column) {
                return $column['searchable'] ?? true;
            })
            ->pluck('key')
            ->toArray();
    }
}
```

### Caching Strategies

```php
class CachedApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->setApiEndpoint('https://api.slow-service.com/data', [
            'cache_enabled' => true,
            'cache_ttl' => 3600, // 1 hour
            'cache_strategy' => 'intelligent', // 'simple', 'intelligent', 'tags'
        ]);
    }

    // Generate intelligent cache key
    public function generateApiCacheKey($params = [])
    {
        $key_parts = [
            'api_data',
            md5($this->apiUrl),
            $this->search ?? 'no_search',
            md5(json_encode($this->filters)),
            $this->sortColumn . '_' . $this->sortDirection,
            $this->page,
            $this->perPage,
        ];

        return implode('_', $key_parts);
    }

    // Warm the cache
    public function warmApiCache()
    {
        $popular_searches = ['user', 'admin', 'active'];
        
        foreach ($popular_searches as $search) {
            $this->search = $search;
            $this->getApiData(); // This will cache the result
        }
        
        $this->search = ''; // Reset search
    }

    // Clear specific cache
    public function clearApiCache($pattern = null)
    {
        if ($pattern) {
            Cache::forget($this->generateApiCacheKey());
        } else {
            // Clear all API cache for this component
            $prefix = 'api_data_' . md5($this->apiUrl);
            Cache::flush(); // Or use tags if available
        }
    }
}
```

### Error Handling and Resilience

```php
class ResilientApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->configureApi([
            'url' => 'https://api.unreliable-service.com/data',
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 1000, // milliseconds
            'fallback_data' => [],
            'error_handling' => 'graceful',
        ]);
    }

    // Handle API errors gracefully
    protected function handleApiError($exception, $attempt = 1)
    {
        Log::warning('API request failed', [
            'url' => $this->apiUrl,
            'attempt' => $attempt,
            'error' => $exception->getMessage(),
        ]);

        // Return cached data if available
        $cacheKey = $this->generateApiCacheKey();
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Return fallback data
        return $this->getFallbackData();
    }

    protected function getFallbackData()
    {
        return [
            [
                'id' => 'error',
                'name' => 'API Service Unavailable',
                'email' => 'Please try again later',
                'status' => 'error',
            ]
        ];
    }

    // Test API connection
    public function testApiConnection()
    {
        try {
            $response = Http::timeout(10)->get($this->apiUrl);
            
            if ($response->successful()) {
                $this->addFlash('success', 'API connection successful');
                return true;
            } else {
                $this->addFlash('error', 'API returned status: ' . $response->status());
                return false;
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'API connection failed: ' . $e->getMessage());
            return false;
        }
    }
}
```

## Rate Limiting and Performance

### Rate Limiting Configuration

```php
class RateLimitedApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->configureApi([
            'url' => 'https://api.rate-limited-service.com/data',
            'rate_limit' => [
                'requests' => 100,        // Max requests
                'per_minutes' => 60,      // Per time period
                'strategy' => 'sliding',  // 'fixed' or 'sliding'
            ],
            'rate_limit_headers' => [
                'limit' => 'X-RateLimit-Limit',
                'remaining' => 'X-RateLimit-Remaining',
                'reset' => 'X-RateLimit-Reset',
            ],
        ]);
    }

    // Check if rate limit allows request
    public function canMakeApiRequest()
    {
        $key = 'api_rate_limit_' . md5($this->apiUrl);
        $requests = Cache::get($key, 0);
        
        return $requests < $this->getRateLimit();
    }

    // Track API request for rate limiting
    protected function trackApiRequest()
    {
        $key = 'api_rate_limit_' . md5($this->apiUrl);
        $ttl = 60; // 1 minute
        
        $requests = Cache::get($key, 0);
        Cache::put($key, $requests + 1, now()->addMinutes($ttl));
    }

    protected function getRateLimit()
    {
        return $this->apiConfig['rate_limit']['requests'] ?? 100;
    }
}
```

### Performance Optimization

```php
class OptimizedApiTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->configureApi([
            'url' => 'https://api.example.com/data',
            'cache_enabled' => true,
            'cache_ttl' => 1800, // 30 minutes
            'compression' => true,
            'connection_pooling' => true,
            'parallel_requests' => false, // Enable for independent requests
        ]);
    }

    // Optimize API requests by batching
    public function batchApiRequests($requests)
    {
        $responses = [];
        
        // If parallel requests are enabled
        if ($this->apiConfig['parallel_requests'] ?? false) {
            $responses = Http::pool(function ($pool) use ($requests) {
                foreach ($requests as $key => $request) {
                    $pool->as($key)->get($request['url'], $request['params']);
                }
            });
        } else {
            // Sequential requests
            foreach ($requests as $key => $request) {
                $responses[$key] = Http::get($request['url'], $request['params']);
            }
        }
        
        return $responses;
    }

    // Preload API data
    public function preloadApiData()
    {
        // Preload common search terms
        $common_searches = $this->getCommonSearchTerms();
        
        foreach ($common_searches as $search) {
            $params = ['search' => $search];
            $this->fetchApiData($params);
        }
    }

    protected function getCommonSearchTerms()
    {
        return ['user', 'admin', 'active', 'pending'];
    }
}
```

## Export API Data

### Basic API Export

```php
public function exportApiData($format = 'csv')
{
    // Get all API data (not just current page)
    $allData = $this->getAllApiData();
    
    return $this->handleExport($format, [
        'filename' => 'api_export_' . date('Y-m-d'),
        'data' => $allData,
        'columns' => $this->getVisibleColumns(),
    ]);
}

protected function getAllApiData()
{
    $allData = [];
    $page = 1;
    
    do {
        $response = $this->fetchApiData(['page' => $page, 'per_page' => 100]);
        $pageData = $this->transformApiResponse($response);
        
        $allData = array_merge($allData, $pageData);
        $page++;
        
        // Check if there are more pages
        $hasMore = count($pageData) === 100; // Adjust based on API
        
    } while ($hasMore);
    
    return $allData;
}
```

### Streaming API Export for Large Datasets

```php
public function streamApiExport($format = 'csv')
{
    $filename = 'api_export_' . date('Y-m-d') . '.' . $format;
    
    return response()->streamDownload(function () use ($format) {
        $handle = fopen('php://output', 'w');
        
        // Write headers for CSV
        if ($format === 'csv') {
            fputcsv($handle, array_keys($this->getVisibleColumns()));
        }
        
        $page = 1;
        
        do {
            $response = $this->fetchApiData(['page' => $page, 'per_page' => 100]);
            $pageData = $this->transformApiResponse($response);
            
            foreach ($pageData as $row) {
                if ($format === 'csv') {
                    fputcsv($handle, $row);
                }
            }
            
            $page++;
            $hasMore = count($pageData) === 100;
            
        } while ($hasMore);
        
        fclose($handle);
    }, $filename);
}
```

## View Integration

### Basic API Table View

```blade
<div>
    {{-- API Status Indicator --}}
    <div class="mb-4">
        @if($this->isApiMode())
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                API Mode
            </span>
            
            {{-- API Connection Test --}}
            <button wire:click="testApiConnection" class="ml-2 px-3 py-1 text-xs bg-blue-500 text-white rounded">
                Test Connection
            </button>
            
            {{-- Refresh API Data --}}
            <button wire:click="refreshApiData" class="ml-2 px-3 py-1 text-xs bg-gray-500 text-white rounded">
                Refresh Data
            </button>
        @endif
    </div>

    {{-- Search and Controls --}}
    <div class="mb-4 flex gap-4">
        <input 
            type="text" 
            wire:model.live="search" 
            placeholder="Search API data..." 
            class="border rounded px-3 py-2 flex-1"
        >
        
        <select wire:model.live="perPage" class="border rounded px-3 py-2">
            <option value="10">10 per page</option>
            <option value="25">25 per page</option>
            <option value="50">50 per page</option>
        </select>
        
        <button wire:click="exportApiData('csv')" class="px-4 py-2 bg-green-500 text-white rounded">
            Export CSV
        </button>
    </div>

    {{-- Data Table --}}
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border">
            <thead>
                <tr class="bg-gray-50">
                    @foreach($this->getVisibleColumns() as $key => $column)
                        <th class="border px-4 py-2 text-left cursor-pointer" 
                            wire:click="sortBy('{{ $key }}')">
                            {{ $column['label'] }}
                            @if($sortColumn === $key)
                                @if($sortDirection === 'asc') ↑ @else ↓ @endif
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                    <tr class="hover:bg-gray-50">
                        @foreach($this->getVisibleColumns() as $key => $column)
                            <td class="border px-4 py-2">
                                {{ data_get($item, $column['key']) }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($this->getVisibleColumns()) }}" class="border px-4 py-8 text-center text-gray-500">
                            No data available from API
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- API Statistics --}}
    @if($stats)
        <div class="mt-4 p-4 bg-gray-100 rounded">
            <h4 class="font-semibold mb-2">API Statistics</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="font-medium">Total Records:</span>
                    {{ $stats['total'] ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Filtered:</span>
                    {{ $stats['filtered'] ?? 'N/A' }}
                </div>
                <div>
                    <span class="font-medium">Cache Status:</span>
                    {{ $stats['cache_hit'] ? 'Hit' : 'Miss' }}
                </div>
                <div>
                    <span class="font-medium">Response Time:</span>
                    {{ $stats['response_time'] ?? 'N/A' }}ms
                </div>
            </div>
        </div>
    @endif
</div>
```

## Available Methods

### Core API Methods

- `setApiEndpoint($url, $config)` - Configure API endpoint
- `configureApi($config)` - Advanced API configuration
- `getApiData()` - Get processed API data
- `fetchApiData($params)` - Fetch raw API data
- `isApiMode()` - Check if in API mode

### Data Processing Methods

- `transformApiResponse($response)` - Transform API response
- `searchApiData($search)` - Search API data
- `filterApiData($filters)` - Filter API data
- `sortApiData($column, $direction)` - Sort API data

### Cache Management

- `generateApiCacheKey($params)` - Generate cache key
- `clearApiCache($pattern)` - Clear API cache
- `refreshApiData()` - Refresh cached data

### Performance and Monitoring

- `getApiStats()` - Get API statistics
- `testApiConnection()` - Test API connectivity
- `trackApiRequest()` - Track API usage

This comprehensive API endpoint integration provides a robust foundation for building datatables that work seamlessly with external APIs while maintaining all the powerful features of the datatable system.
