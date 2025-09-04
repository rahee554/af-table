# HasForEach Trait Guide 🔄

## Overview

The `HasForEach` trait transforms the AF Table into a powerful foreach iteration system, allowing you to process and display data collections with all the standard datatable features like search, filtering, sorting, and pagination.

## Table of Contents
1. [Basic Usage](#basic-usage)
2. [Configuration](#configuration)
3. [Advanced Features](#advanced-features)
4. [Integration Examples](#integration-examples)
5. [Performance Optimization](#performance-optimization)
6. [Troubleshooting](#troubleshooting)

---

## Basic Usage

### Enabling Foreach Mode

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class UserList extends Component
{
    use DatatableTrait;
    
    public function mount()
    {
        // Enable foreach mode with a collection
        $users = collect([
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'inactive'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'status' => 'active'],
        ]);
        
        $this->enableForeachMode($users);
    }
    
    public function render()
    {
        return view('livewire.user-list');
    }
}
```

### Basic Blade Template

```blade
{{-- resources/views/livewire/user-list.blade.php --}}
<div>
    {{-- Search and Filter Controls --}}
    <div class="mb-4 flex gap-4">
        <input wire:model.live="foreachSearch" 
               placeholder="Search users..." 
               class="px-3 py-2 border rounded">
        
        <select wire:model.live="foreachFilters.status" class="px-3 py-2 border rounded">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    
    {{-- Data Display --}}
    <div class="space-y-2">
        @foreach($this->getForeachData() as $user)
            <div class="p-4 bg-white border rounded shadow-sm">
                <h3 class="font-semibold">{{ $user['name'] }}</h3>
                <p class="text-gray-600">{{ $user['email'] }}</p>
                <span class="px-2 py-1 text-xs rounded 
                    {{ $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ ucfirst($user['status']) }}
                </span>
            </div>
        @endforeach
    </div>
    
    {{-- Pagination --}}
    @if($this->getForeachPaginationData()['hasPages'])
        <div class="mt-4 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing {{ $this->getForeachPaginationData()['from'] }} to {{ $this->getForeachPaginationData()['to'] }} 
                of {{ $this->getForeachPaginationData()['total'] }} results
            </div>
            
            <div class="flex gap-2">
                @if($this->getForeachPaginationData()['currentPage'] > 1)
                    <button wire:click="previousForeachPage" class="px-3 py-1 bg-blue-500 text-white rounded">
                        Previous
                    </button>
                @endif
                
                @if($this->getForeachPaginationData()['hasMorePages'])
                    <button wire:click="nextForeachPage" class="px-3 py-1 bg-blue-500 text-white rounded">
                        Next
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
```

---

## Configuration

### Foreach Settings

```php
public function mount()
{
    $data = collect($this->getUserData());
    
    // Enable foreach mode with custom configuration
    $this->enableForeachMode($data, [
        'per_page' => 10,              // Items per page
        'searchable_fields' => ['name', 'email', 'description'],
        'sortable_fields' => ['name', 'created_at', 'status'],
        'default_sort_field' => 'name',
        'default_sort_direction' => 'asc',
        'filterable_fields' => ['status', 'category', 'tags'],
        'enable_search' => true,
        'enable_filters' => true,
        'enable_sorting' => true,
        'enable_pagination' => true,
    ]);
}
```

### Custom Data Processing

```php
public function processForeachData($data)
{
    // Custom processing for each item
    return $data->map(function ($item) {
        // Add computed fields
        $item['full_name'] = $item['first_name'] . ' ' . $item['last_name'];
        $item['status_badge'] = $this->getStatusBadge($item['status']);
        
        // Format dates
        if (isset($item['created_at'])) {
            $item['formatted_date'] = Carbon::parse($item['created_at'])->format('M d, Y');
        }
        
        return $item;
    });
}

private function getStatusBadge($status)
{
    $badges = [
        'active' => '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Active</span>',
        'inactive' => '<span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Inactive</span>',
        'pending' => '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>',
    ];
    
    return $badges[$status] ?? '<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">Unknown</span>';
}
```

---

## Advanced Features

### Dynamic Filtering

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class AdvancedUserList extends Component
{
    use DatatableTrait;
    
    public $statusFilter = '';
    public $dateRange = '';
    public $categoryFilter = '';
    
    public function mount()
    {
        $this->enableForeachMode($this->getUserCollection());
        
        // Set up custom filters
        $this->foreachFilters = [
            'status' => '',
            'category' => '',
            'date_range' => '',
        ];
    }
    
    public function applyCustomFilters($data)
    {
        // Status filter
        if (!empty($this->foreachFilters['status'])) {
            $data = $data->where('status', $this->foreachFilters['status']);
        }
        
        // Category filter
        if (!empty($this->foreachFilters['category'])) {
            $data = $data->where('category', $this->foreachFilters['category']);
        }
        
        // Date range filter
        if (!empty($this->foreachFilters['date_range'])) {
            [$start, $end] = explode(' to ', $this->foreachFilters['date_range']);
            $data = $data->whereBetween('created_at', [$start, $end]);
        }
        
        return $data;
    }
    
    public function render()
    {
        return view('livewire.advanced-user-list');
    }
}
```

### Custom Search Logic

```php
public function customForeachSearch($data, $searchTerm)
{
    if (empty($searchTerm)) {
        return $data;
    }
    
    return $data->filter(function ($item) use ($searchTerm) {
        // Search in multiple fields with different weights
        $nameMatch = stripos($item['name'], $searchTerm) !== false ? 3 : 0;
        $emailMatch = stripos($item['email'], $searchTerm) !== false ? 2 : 0;
        $descriptionMatch = stripos($item['description'] ?? '', $searchTerm) !== false ? 1 : 0;
        
        // Return items with a minimum score
        return ($nameMatch + $emailMatch + $descriptionMatch) > 0;
    });
}
```

### Sorting with Custom Logic

```php
public function customForeachSort($data, $field, $direction)
{
    switch ($field) {
        case 'name':
            return $direction === 'asc' 
                ? $data->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                : $data->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE);
                
        case 'status_priority':
            $priority = ['active' => 1, 'pending' => 2, 'inactive' => 3];
            return $direction === 'asc'
                ? $data->sortBy(fn($item) => $priority[$item['status']] ?? 999)
                : $data->sortByDesc(fn($item) => $priority[$item['status']] ?? 999);
                
        case 'created_at':
            return $direction === 'asc'
                ? $data->sortBy(fn($item) => Carbon::parse($item['created_at']))
                : $data->sortByDesc(fn($item) => Carbon::parse($item['created_at']));
                
        default:
            return $direction === 'asc' 
                ? $data->sortBy($field)
                : $data->sortByDesc($field);
    }
}
```

---

## Integration Examples

### Example 1: Product Catalog

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class ProductCatalog extends Component
{
    use DatatableTrait;
    
    public function mount()
    {
        $products = collect([
            [
                'id' => 1,
                'name' => 'Laptop Pro',
                'price' => 1299.99,
                'category' => 'Electronics',
                'stock' => 15,
                'rating' => 4.5,
                'image' => '/images/laptop-pro.jpg'
            ],
            [
                'id' => 2,
                'name' => 'Wireless Headphones',
                'price' => 199.99,
                'category' => 'Audio',
                'stock' => 42,
                'rating' => 4.2,
                'image' => '/images/headphones.jpg'
            ],
            // ... more products
        ]);
        
        $this->enableForeachMode($products, [
            'per_page' => 12,
            'searchable_fields' => ['name', 'category', 'description'],
            'sortable_fields' => ['name', 'price', 'rating', 'stock'],
            'filterable_fields' => ['category', 'price_range', 'rating'],
            'default_sort_field' => 'name',
        ]);
    }
    
    public function render()
    {
        return view('livewire.product-catalog');
    }
}
```

#### Product Catalog Blade Template

```blade
{{-- resources/views/livewire/product-catalog.blade.php --}}
<div>
    {{-- Filter Controls --}}
    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Search</label>
                <input wire:model.live="foreachSearch" 
                       placeholder="Search products..." 
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select wire:model.live="foreachFilters.category" class="w-full px-3 py-2 border rounded">
                    <option value="">All Categories</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Audio">Audio</option>
                    <option value="Accessories">Accessories</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Sort By</label>
                <select wire:model.live="foreachSortField" class="w-full px-3 py-2 border rounded">
                    <option value="name">Name</option>
                    <option value="price">Price</option>
                    <option value="rating">Rating</option>
                    <option value="stock">Stock</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Order</label>
                <select wire:model.live="foreachSortDirection" class="w-full px-3 py-2 border rounded">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                </select>
            </div>
        </div>
    </div>
    
    {{-- Product Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($this->getForeachData() as $product)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <img src="{{ $product['image'] }}" 
                     alt="{{ $product['name'] }}" 
                     class="w-full h-48 object-cover">
                
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2">{{ $product['name'] }}</h3>
                    
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-2xl font-bold text-green-600">${{ number_format($product['price'], 2) }}</span>
                        <span class="text-sm text-gray-500">{{ $product['category'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center">
                            <span class="text-yellow-400">★</span>
                            <span class="ml-1 text-sm text-gray-600">{{ $product['rating'] }}</span>
                        </div>
                        <span class="text-sm {{ $product['stock'] > 10 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $product['stock'] }} in stock
                        </span>
                    </div>
                    
                    <button class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                        Add to Cart
                    </button>
                </div>
            </div>
        @endforeach
    </div>
    
    {{-- Empty State --}}
    @if(empty($this->getForeachData()->toArray()))
        <div class="text-center py-12">
            <div class="text-gray-400 text-6xl mb-4">📦</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
            <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
        </div>
    @endif
    
    {{-- Pagination --}}
    @if($this->getForeachPaginationData()['hasPages'])
        <div class="mt-8 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing {{ $this->getForeachPaginationData()['from'] }} to {{ $this->getForeachPaginationData()['to'] }} 
                of {{ $this->getForeachPaginationData()['total'] }} products
            </div>
            
            <div class="flex gap-2">
                @if($this->getForeachPaginationData()['currentPage'] > 1)
                    <button wire:click="previousForeachPage" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        ← Previous
                    </button>
                @endif
                
                {{-- Page Numbers --}}
                @for($i = max(1, $this->getForeachPaginationData()['currentPage'] - 2); 
                     $i <= min($this->getForeachPaginationData()['lastPage'], $this->getForeachPaginationData()['currentPage'] + 2); 
                     $i++)
                    <button wire:click="setForeachPage({{ $i }})" 
                            class="px-3 py-2 {{ $i == $this->getForeachPaginationData()['currentPage'] ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} rounded hover:bg-blue-500 hover:text-white">
                        {{ $i }}
                    </button>
                @endfor
                
                @if($this->getForeachPaginationData()['hasMorePages'])
                    <button wire:click="nextForeachPage" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Next →
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
```

### Example 2: API Data Display

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ApiDataDisplay extends Component
{
    use DatatableTrait;
    
    public function mount()
    {
        // Fetch data from API with caching
        $apiData = Cache::remember('api_data', 300, function () {
            $response = Http::get('https://jsonplaceholder.typicode.com/users');
            return collect($response->json());
        });
        
        // Transform API data for display
        $transformedData = $apiData->map(function ($user) {
            return [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'website' => $user['website'],
                'company' => $user['company']['name'],
                'city' => $user['address']['city'],
                'street' => $user['address']['street'],
                'zipcode' => $user['address']['zipcode'],
            ];
        });
        
        $this->enableForeachMode($transformedData, [
            'per_page' => 8,
            'searchable_fields' => ['name', 'username', 'email', 'company', 'city'],
            'sortable_fields' => ['name', 'email', 'company', 'city'],
            'filterable_fields' => ['company', 'city'],
        ]);
    }
    
    public function refreshData()
    {
        Cache::forget('api_data');
        $this->mount(); // Re-fetch data
        $this->dispatchBrowserEvent('notify', ['message' => 'Data refreshed successfully!']);
    }
    
    public function render()
    {
        return view('livewire.api-data-display');
    }
}
```

---

## Performance Optimization

### Lazy Loading for Large Datasets

```php
public function mount()
{
    // For very large datasets, implement lazy loading
    $this->enableForeachMode(collect(), [
        'lazy_load' => true,
        'chunk_size' => 100,
        'per_page' => 20,
    ]);
    
    $this->loadInitialData();
}

public function loadInitialData()
{
    $initialData = $this->getDataChunk(0, 100);
    $this->addToForeachData($initialData);
}

public function loadMoreData()
{
    $currentCount = $this->foreachData->count();
    $moreData = $this->getDataChunk($currentCount, 100);
    
    if ($moreData->isNotEmpty()) {
        $this->addToForeachData($moreData);
    }
}

private function getDataChunk($offset, $limit)
{
    // Implement your data fetching logic here
    // This could be database queries, API calls, etc.
    return collect([]); // Return chunk of data
}
```

### Memory Management

```php
public function optimizeMemoryUsage()
{
    // Clear unnecessary data from memory
    if ($this->foreachData->count() > 1000) {
        // Keep only current page data in memory
        $currentPageData = $this->getCurrentPageData();
        $this->foreachData = $currentPageData;
        
        // Force garbage collection
        gc_collect_cycles();
    }
}

public function updatedForeachPage()
{
    $this->optimizeMemoryUsage();
}
```

### Caching Strategies

```php
use Illuminate\Support\Facades\Cache;

public function getCachedForeachData()
{
    $cacheKey = $this->getForeachCacheKey();
    
    return Cache::remember($cacheKey, 300, function () {
        return $this->processForeachData();
    });
}

private function getForeachCacheKey()
{
    return sprintf(
        'foreach_data_%s_%s_%s_%s',
        md5(serialize($this->foreachFilters)),
        $this->foreachSearch,
        $this->foreachSortField,
        $this->foreachSortDirection
    );
}

public function clearForeachCache()
{
    $pattern = 'foreach_data_*';
    Cache::flush(); // or implement more targeted cache clearing
}
```

---

## Troubleshooting

### Common Issues and Solutions

#### 1. Data Not Updating After Changes

**Problem**: Changes to source data don't reflect in the foreach display.

**Solution**:
```php
// Refresh the foreach data after making changes
public function updateUserStatus($userId, $status)
{
    // Update your data source
    $this->updateUserInDatabase($userId, $status);
    
    // Refresh foreach data
    $this->refreshForeachData();
    
    // Or update specific item
    $this->updateForeachItem($userId, ['status' => $status]);
}
```

#### 2. Performance Issues with Large Datasets

**Problem**: Slow rendering with thousands of items.

**Solutions**:
```php
// Option 1: Implement virtual scrolling
public function enableVirtualScrolling()
{
    $this->foreachConfig['virtual_scrolling'] = true;
    $this->foreachConfig['visible_items'] = 20;
}

// Option 2: Use server-side pagination
public function useServerSidePagination()
{
    $this->foreachConfig['server_side'] = true;
    // Load only current page data from database
}

// Option 3: Implement progressive loading
public function loadDataProgressively()
{
    $this->foreachConfig['progressive_load'] = true;
    $this->foreachConfig['load_increment'] = 50;
}
```

#### 3. Search Not Working as Expected

**Problem**: Search doesn't find items that should match.

**Solution**:
```php
// Debug search functionality
public function debugSearch($searchTerm)
{
    $originalData = $this->foreachData;
    $filteredData = $this->applyForeachSearch($originalData, $searchTerm);
    
    logger()->info('Search Debug', [
        'search_term' => $searchTerm,
        'original_count' => $originalData->count(),
        'filtered_count' => $filteredData->count(),
        'searchable_fields' => $this->foreachConfig['searchable_fields'],
    ]);
    
    return $filteredData;
}

// Custom search with better matching
public function enhancedSearch($data, $searchTerm)
{
    if (empty($searchTerm)) {
        return $data;
    }
    
    $searchTerm = strtolower(trim($searchTerm));
    
    return $data->filter(function ($item) use ($searchTerm) {
        foreach ($this->foreachConfig['searchable_fields'] as $field) {
            $value = strtolower((string) data_get($item, $field));
            
            // Exact match
            if ($value === $searchTerm) return true;
            
            // Contains match
            if (str_contains($value, $searchTerm)) return true;
            
            // Word boundary match
            if (preg_match('/\b' . preg_quote($searchTerm) . '\b/', $value)) return true;
        }
        
        return false;
    });
}
```

#### 4. Filters Not Working Correctly

**Problem**: Filters don't properly filter the data.

**Solution**:
```php
// Debug filter functionality
public function debugFilters()
{
    logger()->info('Filter Debug', [
        'active_filters' => $this->foreachFilters,
        'filterable_fields' => $this->foreachConfig['filterable_fields'],
        'data_sample' => $this->foreachData->take(3)->toArray(),
    ]);
}

// Ensure filter values are properly typed
public function sanitizeFilters()
{
    foreach ($this->foreachFilters as $field => $value) {
        if (is_string($value)) {
            $this->foreachFilters[$field] = trim($value);
        }
        
        // Remove empty filters
        if (empty($value)) {
            unset($this->foreachFilters[$field]);
        }
    }
}
```

### Debug Mode

```php
// Enable debug mode for development
public function enableForeachDebug()
{
    $this->foreachConfig['debug_mode'] = true;
}

public function getForeachDebugInfo()
{
    if (!$this->foreachConfig['debug_mode']) {
        return [];
    }
    
    return [
        'total_items' => $this->foreachData->count(),
        'filtered_items' => $this->getFilteredForeachData()->count(),
        'current_page' => $this->foreachPage,
        'per_page' => $this->foreachConfig['per_page'],
        'active_search' => $this->foreachSearch,
        'active_filters' => $this->foreachFilters,
        'sort_field' => $this->foreachSortField,
        'sort_direction' => $this->foreachSortDirection,
        'memory_usage' => memory_get_usage(true),
        'execution_time' => microtime(true) - LARAVEL_START,
    ];
}
```

---

## Best Practices

1. **Data Structure**: Keep your data structure consistent across all items
2. **Performance**: Use pagination for large datasets (>1000 items)
3. **Caching**: Implement caching for expensive data processing
4. **Search**: Make search case-insensitive and support partial matches
5. **Filters**: Provide clear filter options and reset capabilities
6. **User Experience**: Add loading states and empty state messages
7. **Accessibility**: Include proper ARIA labels and keyboard navigation
8. **Mobile**: Ensure responsive design for mobile devices

---

## Conclusion

The `HasForEach` trait provides a powerful and flexible way to display and interact with data collections while maintaining all the advanced features of the AF Table package. By following this guide and implementing the provided examples, you can create rich, interactive data displays that enhance user experience and provide excellent performance.

For more advanced usage and customization options, refer to the main AF Table documentation and explore the other available traits in the package.
