# ArtflowStudio Table Package - Usage Examples

## Overview

The ArtflowStudio Table package provides powerful datatable functionality for Laravel/Livewire applications with support for multiple data sources: Eloquent models, arrays/collections (ForEach), API endpoints, and JSON files.

## Basic Usage Pattern

All data sources follow the same Livewire component pattern:

```blade
@livewire('aftable-trait', [
    'source_type' => 'model|foreach|api|json',
    'source_config' => [...],
    'columns' => [...],
    'options' => [...]
])
```

## 1. Eloquent Model Data Source

### Basic Model Usage
```blade
@livewire('aftable-trait', [
    'source_type' => 'model',
    'source_config' => [
        'model' => App\Models\FlightDetail::class,
        'with' => ['airline', 'departure_airport', 'arrival_airport']
    ],
    'columns' => [
        ['key' => 'flight_number', 'label' => 'Flight Number', 'sortable' => true],
        ['key' => 'airline.name', 'label' => 'Airline', 'sortable' => true],
        ['key' => 'departure_airport.city', 'label' => 'From', 'sortable' => true],
        ['key' => 'arrival_airport.city', 'label' => 'To', 'sortable' => true],
        ['key' => 'departure_time', 'label' => 'Departure', 'sortable' => true],
        ['key' => 'arrival_time', 'label' => 'Arrival', 'sortable' => true]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 15,
        'sortable' => true,
        'exportable' => true
    ]
])
```

### Advanced Model Usage with Scopes
```blade
@livewire('aftable-trait', [
    'source_type' => 'model',
    'source_config' => [
        'model' => App\Models\FlightDetail::class,
        'with' => ['airline', 'departure_airport', 'arrival_airport'],
        'scopes' => ['active', 'scheduled'],
        'where' => [
            ['status', '=', 'scheduled'],
            ['departure_time', '>=', now()]
        ]
    ],
    'columns' => [
        ['key' => 'flight_number', 'label' => 'Flight #', 'sortable' => true, 'searchable' => true],
        ['key' => 'airline.name', 'label' => 'Airline', 'sortable' => true],
        ['key' => 'route_display', 'label' => 'Route', 'sortable' => false],
        ['key' => 'departure_time', 'label' => 'Departure', 'sortable' => true, 'format' => 'datetime'],
        ['key' => 'duration_display', 'label' => 'Duration', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'badge' => true]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 20,
        'filters' => [
            'airline_id' => ['type' => 'select', 'options' => 'airlines'],
            'status' => ['type' => 'select', 'options' => ['scheduled', 'delayed', 'cancelled']]
        ]
    ]
])
```

## 2. ForEach (Array/Collection) Data Source

### Basic Array Usage
```blade
@livewire('aftable-trait', [
    'source_type' => 'foreach',
    'source_config' => [
        'data' => [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 35]
        ]
    ],
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'searchable' => true],
        ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'searchable' => true],
        ['key' => 'age', 'label' => 'Age', 'sortable' => true]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 10
    ]
])
```

### Collection with Custom Processing
```php
// In your Livewire component
public function mount()
{
    $customData = collect([
        ['product' => 'iPhone', 'sales' => 1500, 'revenue' => 1499500],
        ['product' => 'Samsung', 'sales' => 1200, 'revenue' => 1079400],
        ['product' => 'Google', 'sales' => 800, 'revenue' => 639200]
    ])->map(function ($item) {
        $item['avg_price'] = $item['revenue'] / $item['sales'];
        return $item;
    });

    $this->setForEachData($customData);
}
```

```blade
@livewire('aftable-trait', [
    'source_type' => 'foreach',
    'source_config' => [
        'collection' => $customData
    ],
    'columns' => [
        ['key' => 'product', 'label' => 'Product', 'sortable' => true],
        ['key' => 'sales', 'label' => 'Units Sold', 'sortable' => true, 'format' => 'number'],
        ['key' => 'revenue', 'label' => 'Revenue', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'avg_price', 'label' => 'Avg Price', 'sortable' => true, 'format' => 'currency']
    ],
    'options' => [
        'searchable' => true,
        'exportable' => true,
        'filters' => [
            'sales' => ['type' => 'range', 'min' => 0, 'max' => 2000]
        ]
    ]
])
```

## 3. API Endpoint Data Source

### Basic API Usage
```blade
@livewire('aftable-trait', [
    'source_type' => 'api',
    'source_config' => [
        'endpoint' => 'https://api.example.com/users',
        'method' => 'GET',
        'headers' => [
            'Authorization' => 'Bearer ' . config('services.api.token')
        ]
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
        'cache_duration' => 300
    ]
])
```

### Advanced API with Authentication and Pagination
```blade
@livewire('aftable-trait', [
    'source_type' => 'api',
    'source_config' => [
        'endpoint' => 'https://api.github.com/repos/laravel/laravel/issues',
        'method' => 'GET',
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Laravel-App'
        ],
        'query_params' => [
            'state' => 'open',
            'sort' => 'updated',
            'direction' => 'desc'
        ],
        'pagination' => [
            'type' => 'query_param',
            'page_param' => 'page',
            'per_page_param' => 'per_page'
        ],
        'data_path' => null, // Root level array
        'rate_limit' => 60, // requests per minute
        'cache_duration' => 600 // 10 minutes
    ],
    'columns' => [
        ['key' => 'number', 'label' => '#', 'sortable' => true],
        ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'searchable' => true],
        ['key' => 'user.login', 'label' => 'Author', 'sortable' => true],
        ['key' => 'state', 'label' => 'State', 'sortable' => true, 'badge' => true],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'format' => 'datetime'],
        ['key' => 'comments', 'label' => 'Comments', 'sortable' => true]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 25,
        'exportable' => true,
        'refresh_button' => true
    ]
])
```

## 4. JSON File Data Source

### Basic JSON File Usage
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => [
        'file_path' => storage_path('app/data/employees.json'),
        'cache_duration' => 300
    ],
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'searchable' => true],
        ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'searchable' => true],
        ['key' => 'department', 'label' => 'Department', 'sortable' => true],
        ['key' => 'salary', 'label' => 'Salary', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'hire_date', 'label' => 'Hire Date', 'sortable' => true, 'format' => 'date']
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 15,
        'exportable' => true,
        'filters' => [
            'department' => ['type' => 'select', 'options' => ['Engineering', 'Marketing', 'HR', 'Finance']],
            'active' => ['type' => 'boolean'],
            'salary' => ['type' => 'range', 'min' => 40000, 'max' => 100000]
        ]
    ]
])
```

### Package Test Data Usage
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => [
        'file_path' => base_path('vendor/artflow-studio/table/src/TestData/employees.json')
    ],
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'searchable' => true],
        ['key' => 'email', 'label' => 'Email', 'sortable' => true],
        ['key' => 'age', 'label' => 'Age', 'sortable' => true],
        ['key' => 'city', 'label' => 'City', 'sortable' => true],
        ['key' => 'department', 'label' => 'Department', 'sortable' => true],
        ['key' => 'salary', 'label' => 'Salary', 'sortable' => true, 'format' => 'currency']
    ]
])
```

### Complex JSON with Nested Data
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => [
        'file_path' => base_path('vendor/artflow-studio/table/src/TestData/orders.json')
    ],
    'columns' => [
        ['key' => 'order_id', 'label' => 'Order ID', 'sortable' => true, 'searchable' => true],
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true, 'searchable' => true],
        ['key' => 'order_date', 'label' => 'Date', 'sortable' => true, 'format' => 'date'],
        ['key' => 'total_amount', 'label' => 'Total', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'badge' => true],
        ['key' => 'shipping_address.city', 'label' => 'Ship To', 'sortable' => true],
        ['key' => 'payment_method', 'label' => 'Payment', 'sortable' => true]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 10,
        'filters' => [
            'status' => ['type' => 'select', 'options' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled']],
            'payment_method' => ['type' => 'select', 'options' => ['credit_card', 'paypal', 'bank_transfer', 'apple_pay']],
            'total_amount' => ['type' => 'range', 'min' => 0, 'max' => 50000]
        ]
    ]
])
```

## 5. Advanced Common Options

### Custom Column Formatting
```blade
'columns' => [
    ['key' => 'price', 'label' => 'Price', 'format' => 'currency', 'currency' => 'USD'],
    ['key' => 'created_at', 'label' => 'Created', 'format' => 'datetime', 'date_format' => 'M j, Y g:i A'],
    ['key' => 'status', 'label' => 'Status', 'badge' => true, 'badge_colors' => [
        'active' => 'green',
        'inactive' => 'red',
        'pending' => 'yellow'
    ]],
    ['key' => 'description', 'label' => 'Description', 'truncate' => 50],
    ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'custom' => true]
]
```

### Bulk Actions
```blade
'options' => [
    'bulk_actions' => [
        'delete' => ['label' => 'Delete Selected', 'confirm' => true],
        'export' => ['label' => 'Export Selected', 'confirm' => false],
        'activate' => ['label' => 'Activate Selected', 'confirm' => true]
    ]
]
```

### Advanced Filtering
```blade
'options' => [
    'filters' => [
        'date_range' => [
            'type' => 'date_range',
            'label' => 'Date Range',
            'start_field' => 'start_date',
            'end_field' => 'end_date'
        ],
        'category' => [
            'type' => 'multi_select',
            'label' => 'Categories',
            'options' => ['tech', 'business', 'health', 'education']
        ],
        'price_range' => [
            'type' => 'range',
            'label' => 'Price Range',
            'min' => 0,
            'max' => 1000,
            'step' => 10
        ]
    ]
]
```

### Export Configuration
```blade
'options' => [
    'exportable' => true,
    'export_formats' => ['csv', 'excel', 'pdf'],
    'export_filename' => 'custom_export',
    'export_columns' => ['name', 'email', 'created_at'] // specific columns only
]
```

## 6. Performance Optimization

### Model Optimization
```blade
'source_config' => [
    'model' => App\Models\User::class,
    'select' => ['id', 'name', 'email', 'created_at'], // Only needed columns
    'with' => ['profile:id,user_id,avatar'], // Selective eager loading
    'chunk_size' => 1000, // For large datasets
    'cache_duration' => 600 // Cache query results
]
```

### API Optimization
```blade
'source_config' => [
    'endpoint' => 'https://api.example.com/data',
    'cache_duration' => 1800, // 30 minutes
    'rate_limit' => 120, // requests per minute
    'timeout' => 30, // seconds
    'retry_attempts' => 3
]
```

### JSON File Optimization
```blade
'source_config' => [
    'file_path' => storage_path('app/large_dataset.json'),
    'cache_duration' => 3600, // 1 hour for large files
    'memory_limit' => '512M', // Increase if needed
    'streaming' => true // For very large files
]
```

## 7. Real-World Examples

### Employee Management
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => ['file_path' => base_path('vendor/artflow-studio/table/src/TestData/employees.json')],
    'columns' => [
        ['key' => 'name', 'label' => 'Employee', 'sortable' => true, 'searchable' => true],
        ['key' => 'department', 'label' => 'Department', 'sortable' => true],
        ['key' => 'salary', 'label' => 'Salary', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'hire_date', 'label' => 'Hire Date', 'sortable' => true, 'format' => 'date'],
        ['key' => 'active', 'label' => 'Status', 'badge' => true, 'badge_colors' => ['1' => 'green', '0' => 'red']]
    ],
    'options' => [
        'searchable' => true,
        'per_page' => 20,
        'filters' => [
            'department' => ['type' => 'select'],
            'active' => ['type' => 'boolean'],
            'salary' => ['type' => 'range', 'min' => 40000, 'max' => 100000]
        ],
        'exportable' => true
    ]
])
```

### Order Management
```blade
@livewire('aftable-trait', [
    'source_type' => 'json',
    'source_config' => ['file_path' => base_path('vendor/artflow-studio/table/src/TestData/orders.json')],
    'columns' => [
        ['key' => 'order_id', 'label' => 'Order #', 'sortable' => true, 'searchable' => true],
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true, 'searchable' => true],
        ['key' => 'total_amount', 'label' => 'Total', 'sortable' => true, 'format' => 'currency'],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'badge' => true],
        ['key' => 'order_date', 'label' => 'Date', 'sortable' => true, 'format' => 'date']
    ],
    'options' => [
        'searchable' => true,
        'filters' => [
            'status' => ['type' => 'select', 'options' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled']]
        ],
        'bulk_actions' => [
            'export' => ['label' => 'Export Orders'],
            'update_status' => ['label' => 'Update Status', 'confirm' => true]
        ]
    ]
])
```

This documentation provides comprehensive examples for all data source types with the consistent `@livewire('aftable-trait', [...])` pattern that matches your existing model usage.
