# ArtflowStudio Table Package - Complete Documentation

## Overview

The ArtflowStudio Table package provides a comprehensive, high-performance datatable component for Laravel/Livewire applications. It supports multiple data sources including Eloquent models, arrays/collections (ForEach), API endpoints, and JSON files.

## Table of Contents
1. [Installation & Setup](#installation--setup)
2. [Basic Usage](#basic-usage)
3. [Data Sources](#data-sources)
4. [Configuration Options](#configuration-options)
5. [Advanced Features](#advanced-features)
6. [Performance Optimization](#performance-optimization)
7. [API Reference](#api-reference)
8. [Examples](#examples)

## Installation & Setup

### Package Installation
The package is already integrated into your Laravel application. The main component is located at:
```
vendor/artflow-studio/table/src/Http/Livewire/DatatableTrait.php
```

### Service Provider Registration
Ensure the package service provider is registered in your `config/app.php`:
```php
'providers' => [
    // ...
    ArtflowStudio\Table\TableServiceProvider::class,
],
```

### Blade Component Usage
All data sources use the same consistent pattern:
```blade
@livewire('aftable-trait', [
    'source_type' => 'model|foreach|api|json',
    'source_config' => [...],
    'columns' => [...],
    'options' => [...]
])
```

## Basic Usage

### Simple Model Example
```blade
@livewire('aftable-trait', [
    'source_type' => 'model',
    'source_config' => [
        'model' => App\Models\User::class
    ],
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'searchable' => true],
        ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'searchable' => true],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'format' => 'date']
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 15,
        'exportable' => true
    ]
])
```

## Data Sources

### 1. Eloquent Models
Full support for Laravel Eloquent models with relationships, scopes, and advanced querying.

#### Basic Model Configuration
```blade
'source_config' => [
    'model' => App\Models\FlightDetail::class,
    'with' => ['airline', 'departure_airport', 'arrival_airport'],
    'select' => ['id', 'flight_number', 'departure_time', 'arrival_time'],
    'where' => [
        ['status', '=', 'active'],
        ['departure_time', '>=', now()]
    ],
    'scopes' => ['active', 'scheduled']
]
```

#### Advanced Model Features
- **Eager Loading**: Optimize N+1 queries with `with` relationships
- **Selective Loading**: Use `select` to limit columns for performance
- **Query Scopes**: Apply model scopes with `scopes`
- **Where Conditions**: Add custom where clauses
- **Raw SQL**: Use raw SQL expressions where needed

### 2. ForEach (Arrays/Collections)
Process arrays, collections, or any iterable data with full datatable features.

#### Array Data Example
```blade
'source_config' => [
    'data' => [
        ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
    ]
]
```

#### Collection Processing Example
```php
// In your component
$processedData = collect($rawData)->map(function ($item) {
    $item['calculated_field'] = $item['value1'] * $item['value2'];
    return $item;
});

// In your view
'source_config' => ['collection' => $processedData]
```

#### ForEach Features
- **Deep Search**: Search through nested array structures
- **15+ Filter Operators**: equals, contains, greater_than, in, between, etc.
- **Intelligent Sorting**: Type-aware sorting (numeric, string, date)
- **Memory Efficient**: Optimized for large datasets
- **Export Support**: CSV, JSON, Excel export capabilities

### 3. API Endpoints
Integrate external APIs with caching, authentication, and error handling.

#### Basic API Configuration
```blade
'source_config' => [
    'endpoint' => 'https://api.example.com/users',
    'method' => 'GET',
    'headers' => [
        'Authorization' => 'Bearer ' . config('services.api.token'),
        'Accept' => 'application/json'
    ]
]
```

#### Advanced API Features
```blade
'source_config' => [
    'endpoint' => 'https://api.github.com/repos/laravel/laravel/issues',
    'method' => 'GET',
    'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'Laravel-App'
    ],
    'query_params' => [
        'state' => 'open',
        'sort' => 'updated'
    ],
    'pagination' => [
        'type' => 'query_param',
        'page_param' => 'page',
        'per_page_param' => 'per_page'
    ],
    'data_path' => 'data', // For nested response data
    'cache_duration' => 600, // 10 minutes
    'rate_limit' => 60, // requests per minute
    'timeout' => 30,
    'retry_attempts' => 3
]
```

#### API Features
- **Authentication**: Support for Bearer tokens, API keys, OAuth
- **Intelligent Caching**: Configurable cache duration with smart invalidation
- **Rate Limiting**: Built-in rate limiting to respect API limits
- **Error Handling**: Comprehensive error handling and retry logic
- **Response Processing**: Extract data from nested response structures
- **Pagination**: Support for various API pagination patterns

### 4. JSON Files
Load and process JSON files with caching and optimization.

#### Basic JSON Configuration
```blade
'source_config' => [
    'file_path' => storage_path('app/data/employees.json'),
    'cache_duration' => 300
]
```

#### Test Data Usage
The package includes test JSON files:
```blade
'source_config' => [
    'file_path' => base_path('vendor/artflow-studio/table/src/TestData/employees.json')
]
```

Available test files:
- `employees.json` - 15 employee records with departments, salaries
- `products.json` - 10 product records with categories, prices, specs
- `orders.json` - 8 order records with nested customer and item data

#### JSON Features
- **File Validation**: Comprehensive JSON structure validation
- **Smart Caching**: File modification time-based cache invalidation
- **Memory Management**: Memory usage optimization for large files
- **Nested Data**: Support for complex nested JSON structures
- **Search & Filter**: Deep search through nested objects and arrays
- **Export Capabilities**: Export processed data to various formats

## Configuration Options

### Column Configuration
```blade
'columns' => [
    [
        'key' => 'name',
        'label' => 'Full Name',
        'sortable' => true,
        'searchable' => true,
        'format' => 'text',
        'truncate' => 50,
        'class' => 'font-bold'
    ],
    [
        'key' => 'price',
        'label' => 'Price',
        'sortable' => true,
        'format' => 'currency',
        'currency' => 'USD'
    ],
    [
        'key' => 'created_at',
        'label' => 'Created',
        'sortable' => true,
        'format' => 'datetime',
        'date_format' => 'M j, Y g:i A'
    ],
    [
        'key' => 'status',
        'label' => 'Status',
        'sortable' => true,
        'badge' => true,
        'badge_colors' => [
            'active' => 'green',
            'inactive' => 'red',
            'pending' => 'yellow'
        ]
    ]
]
```

### General Options
```blade
'options' => [
    // Search & Filter
    'searchable' => true,
    'search_placeholder' => 'Search records...',
    'filters' => [...],
    
    // Pagination
    'per_page' => 15,
    'per_page_options' => [10, 15, 25, 50, 100],
    
    // Export
    'exportable' => true,
    'export_formats' => ['csv', 'excel', 'json'],
    'export_filename' => 'data_export',
    
    // UI Features
    'sortable' => true,
    'bulk_actions' => [...],
    'refresh_button' => true,
    'column_visibility' => true,
    
    // Performance
    'cache_duration' => 300,
    'chunk_size' => 1000,
    'memory_limit' => '512M'
]
```

### Filter Configuration
```blade
'filters' => [
    'status' => [
        'type' => 'select',
        'label' => 'Status',
        'options' => ['active', 'inactive', 'pending']
    ],
    'date_range' => [
        'type' => 'date_range',
        'label' => 'Date Range',
        'start_field' => 'start_date',
        'end_field' => 'end_date'
    ],
    'price_range' => [
        'type' => 'range',
        'label' => 'Price Range',
        'min' => 0,
        'max' => 1000,
        'step' => 10
    ],
    'categories' => [
        'type' => 'multi_select',
        'label' => 'Categories',
        'options' => ['tech', 'business', 'health']
    ]
]
```

## Advanced Features

### Performance Optimization
```blade
'options' => [
    // Query Optimization
    'select_optimization' => true,
    'eager_loading_strategy' => 'intelligent',
    'query_caching' => true,
    'index_hints' => true,
    
    // Memory Management
    'chunk_processing' => true,
    'chunk_size' => 1000,
    'memory_threshold' => 80, // Percentage
    'gc_collection' => true,
    
    // UI Performance
    'virtual_scrolling' => true,
    'lazy_loading' => true,
    'debounce_search' => 300 // milliseconds
]
```

### Security Features
```blade
'options' => [
    'sanitize_input' => true,
    'allowed_columns' => ['name', 'email', 'created_at'],
    'sql_injection_protection' => true,
    'xss_protection' => true,
    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 100,
        'per_minutes' => 1
    ]
]
```

### Bulk Actions
```blade
'options' => [
    'bulk_actions' => [
        'delete' => [
            'label' => 'Delete Selected',
            'confirm' => true,
            'confirm_message' => 'Are you sure you want to delete selected items?'
        ],
        'export' => [
            'label' => 'Export Selected',
            'confirm' => false
        ],
        'update_status' => [
            'label' => 'Update Status',
            'confirm' => true,
            'options' => ['active', 'inactive']
        ]
    ]
]
```

## Performance Optimization

### Model Optimization
- **Selective Loading**: Only load required columns
- **Eager Loading**: Prevent N+1 queries with strategic `with` clauses
- **Index Usage**: Ensure proper database indexing for sorted/filtered columns
- **Query Caching**: Enable intelligent query caching
- **Chunk Processing**: Process large datasets in manageable chunks

### API Optimization
- **Caching Strategy**: Implement appropriate cache durations
- **Rate Limiting**: Respect API rate limits
- **Connection Pooling**: Reuse HTTP connections
- **Compression**: Enable gzip compression for responses
- **Pagination**: Use efficient pagination patterns

### JSON File Optimization
- **File Size Limits**: Monitor and limit JSON file sizes
- **Memory Management**: Stream large files when possible
- **Cache Invalidation**: Use file modification time for cache validation
- **Compression**: Consider compressing large JSON files

### Memory Management
```php
// Monitor memory usage
$stats = $component->getMemoryStats();
// Returns: current usage, peak usage, limit, percentage

// Optimize for memory
$component->optimizeQueryForMemory($maxMemoryMB = 256);
```

## API Reference

### Core Methods

#### Data Source Methods
```php
// Model methods
$component->setModel(User::class);
$component->applyEagerLoading(['profile', 'roles']);

// ForEach methods
$component->setForEachData($array);
$component->enableForeachMode();
$component->getForeachStats();

// API methods
$component->setApiEndpoint($url, $config);
$component->configureApi($options);
$component->getApiStats();

// JSON methods
$component->initializeJsonFile($filePath);
$component->validateJsonStructure();
$component->getJsonFileStats();
```

#### Query Methods
```php
$component->search($term);
$component->applyFilters($filters);
$component->sortBy($column, $direction);
$component->setPerPage($count);
```

#### Export Methods
```php
$component->exportToCsv($filename);
$component->exportToJson($filename);
$component->exportToExcel($filename);
$component->exportWithChunking($format, $chunkSize);
```

#### Performance Methods
```php
$component->getCurrentMemoryUsage();
$component->getMemoryStats();
$component->optimizeQueryForMemory($limitMB);
$component->getCacheStatistics();
```

## Examples

### Employee Management System
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => [
        'file_path' => base_path('vendor/artflow-studio/table/src/TestData/employees.json')
    ],
    'columns' => [
        ['key' => 'name', 'label' => 'Employee', 'sortable' => true, 'searchable' => true],
        ['key' => 'department', 'label' => 'Department', 'sortable' => true],
        ['key' => 'salary', 'label' => 'Salary', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'hire_date', 'label' => 'Hire Date', 'sortable' => true, 'format' => 'date'],
        ['key' => 'active', 'label' => 'Status', 'badge' => true]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 20,
        'filters' => [
            'department' => ['type' => 'select'],
            'active' => ['type' => 'boolean'],
            'salary' => ['type' => 'range', 'min' => 40000, 'max' => 100000]
        ],
        'exportable' => true,
        'bulk_actions' => [
            'export' => ['label' => 'Export Selected'],
            'update_status' => ['label' => 'Update Status', 'confirm' => true]
        ]
    ]
])
```

### Product Catalog
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => [
        'file_path' => base_path('vendor/artflow-studio/table/src/TestData/products.json')
    ],
    'columns' => [
        ['key' => 'name', 'label' => 'Product', 'sortable' => true, 'searchable' => true],
        ['key' => 'category', 'label' => 'Category', 'sortable' => true],
        ['key' => 'brand', 'label' => 'Brand', 'sortable' => true],
        ['key' => 'price', 'label' => 'Price', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'rating', 'label' => 'Rating', 'sortable' => true],
        ['key' => 'availability', 'label' => 'Available', 'badge' => true]
    ],
    'options' => [
        'searchable' => true,
        'filters' => [
            'category' => ['type' => 'select'],
            'brand' => ['type' => 'select'],
            'price' => ['type' => 'range', 'min' => 0, 'max' => 2000],
            'availability' => ['type' => 'boolean']
        ]
    ]
])
```

### Order Management
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => [
        'file_path' => base_path('vendor/artflow-studio/table/src/TestData/orders.json')
    ],
    'columns' => [
        ['key' => 'order_id', 'label' => 'Order #', 'sortable' => true, 'searchable' => true],
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true, 'searchable' => true],
        ['key' => 'total_amount', 'label' => 'Total', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'badge' => true],
        ['key' => 'order_date', 'label' => 'Date', 'sortable' => true, 'format' => 'date'],
        ['key' => 'shipping_address.city', 'label' => 'Ship To', 'sortable' => true]
    ],
    'options' => [
        'searchable' => true,
        'filters' => [
            'status' => ['type' => 'select', 'options' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled']],
            'payment_method' => ['type' => 'select'],
            'total_amount' => ['type' => 'range', 'min' => 0, 'max' => 50000]
        ]
    ]
])
```

### GitHub Issues (API Example)
```blade
@livewire('aftable-trait', [
    'source_type' => 'api',
    'source_config' => [
        'endpoint' => 'https://api.github.com/repos/laravel/laravel/issues',
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Laravel-App'
        ],
        'query_params' => ['state' => 'open'],
        'cache_duration' => 600
    ],
    'columns' => [
        ['key' => 'number', 'label' => '#', 'sortable' => true],
        ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'searchable' => true],
        ['key' => 'user.login', 'label' => 'Author', 'sortable' => true],
        ['key' => 'state', 'label' => 'State', 'sortable' => true, 'badge' => true],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'format' => 'datetime']
    ]
])
```

## Testing

### Test Command
Run comprehensive tests using the built-in test command:
```bash
php artisan af-table:test-trait
```

Test categories include:
- Component instantiation
- Trait integration (26 traits)
- ForEach functionality (10 methods)
- API endpoint integration (14 methods)
- JSON file processing (comprehensive testing)
- Performance and memory management
- Security validation
- Export functionality

### Test Files
The package includes test JSON files for development and testing:
- `employees.json` - Employee management data
- `products.json` - Product catalog data  
- `orders.json` - Order management with nested structures

## Troubleshooting

### Common Issues

1. **Memory Issues**: Increase memory limit or enable chunk processing
2. **Performance**: Enable caching and optimize column selection
3. **API Timeouts**: Increase timeout settings and implement retry logic
4. **Large JSON Files**: Use streaming or increase memory limits
5. **Cache Issues**: Clear cache or disable during development

### Debug Mode
Enable debug mode for detailed logging:
```blade
'options' => [
    'debug' => true,
    'log_queries' => true,
    'performance_monitoring' => true
]
```

### Performance Monitoring
Monitor performance with built-in tools:
```php
$stats = $component->getPerformanceStats();
// Returns: query time, memory usage, cache hits, etc.
```

## Conclusion

The ArtflowStudio Table package provides a comprehensive solution for data table needs in Laravel applications. With support for multiple data sources, extensive customization options, and performance optimization features, it can handle everything from simple data display to complex enterprise-level requirements.

For additional support or feature requests, please refer to the package documentation or contact the development team.
