# ForEach Functionality Documentation

## Overview

The `HasForEach` trait enables the DatatableTrait to work with array and collection data sources, providing all the powerful features of the datatable system (search, filtering, sorting, pagination, export) for foreach operations on in-memory data.

## Purpose

When you need to use foreach loops with data collections but want to maintain the rich functionality of the datatable system, the ForEach functionality allows you to:

- Apply search functionality to array/collection data
- Use advanced filtering with multiple operators
- Sort data by any field with type-aware sorting
- Paginate through large datasets
- Export processed data in multiple formats
- Maintain performance with memory-efficient processing

## Key Features

### 1. Deep Search Capabilities
- **Recursive Search**: Searches through nested arrays and objects
- **Multi-field Search**: Searches across all configured columns
- **Type-aware Search**: Handles strings, numbers, dates, and booleans intelligently
- **Case-insensitive**: Flexible search matching

### 2. Advanced Filtering
- **15+ Filter Operators**: equals, like, greater_than, less_than, between, in, not_in, starts_with, ends_with, contains, not_contains, is_null, is_not_null, date_equal, date_between
- **Type-aware Filtering**: Automatically handles different data types
- **Multiple Filters**: Apply multiple filters simultaneously
- **Date/Time Filtering**: Specialized date and time comparison operators

### 3. Intelligent Sorting
- **Type-aware Sorting**: Sorts strings, numbers, dates, and booleans correctly
- **Nested Path Support**: Sort by nested object properties
- **Null Handling**: Properly handles null values in sorting
- **Custom Sort Orders**: Ascending and descending with proper type handling

### 4. Memory-efficient Pagination
- **Lazy Loading**: Processes only the required page of data
- **Configurable Page Sizes**: Support for different pagination sizes
- **Statistics**: Provides total count and pagination information

### 5. Export Capabilities
- **Multiple Formats**: CSV, JSON, Excel support
- **Filtered Export**: Export only filtered/searched data
- **Chunked Export**: Memory-efficient export for large datasets

## Basic Usage

### Setting Up ForEach Mode

```php
// In your Livewire component
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class MyComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        // Configure columns
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
            'status' => ['key' => 'status', 'label' => 'Status'],
            'created_at' => ['key' => 'created_at', 'label' => 'Created'],
        ];

        // Set your array/collection data
        $data = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'created_at' => '2024-01-15'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'inactive', 'created_at' => '2024-01-10'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'status' => 'active', 'created_at' => '2024-01-20'],
        ];

        $this->setForEachData($data);
    }

    public function render()
    {
        $data = $this->getForeachData();
        
        return view('livewire.my-component', [
            'data' => $data,
            'stats' => $this->getForeachStats()
        ]);
    }
}
```

### Working with Collection Data

```php
// With Laravel Collections
$collection = collect([
    ['name' => 'Product A', 'price' => 100, 'category' => 'Electronics'],
    ['name' => 'Product B', 'price' => 200, 'category' => 'Clothing'],
    ['name' => 'Product C', 'price' => 150, 'category' => 'Electronics'],
]);

$this->setForEachData($collection);
```

### Working with Nested Data

```php
// With nested object data
$nestedData = [
    [
        'id' => 1,
        'user' => ['name' => 'John', 'email' => 'john@example.com'],
        'profile' => ['age' => 30, 'country' => 'USA'],
        'settings' => ['theme' => 'dark', 'notifications' => true]
    ],
    [
        'id' => 2,
        'user' => ['name' => 'Jane', 'email' => 'jane@example.com'],
        'profile' => ['age' => 25, 'country' => 'Canada'],
        'settings' => ['theme' => 'light', 'notifications' => false]
    ]
];

// Configure columns for nested data
$this->columns = [
    'id' => ['key' => 'id', 'label' => 'ID'],
    'user_name' => ['key' => 'user.name', 'label' => 'Name'],
    'user_email' => ['key' => 'user.email', 'label' => 'Email'],
    'profile_age' => ['key' => 'profile.age', 'label' => 'Age'],
    'settings_theme' => ['key' => 'settings.theme', 'label' => 'Theme'],
];

$this->setForEachData($nestedData);
```

## Advanced Features

### 1. Search Functionality

The search automatically works across all visible columns:

```php
// Users can search and it will look through all configured columns
// Search is applied automatically when $this->search is set

// In your component, search is already handled by the trait
// Users just need to use the search input in the view
```

### 2. Filtering

```php
// Apply filters programmatically
$this->filters = [
    'status' => ['operator' => 'equals', 'value' => 'active'],
    'created_at' => ['operator' => 'date_between', 'value' => ['2024-01-01', '2024-01-31']],
    'price' => ['operator' => 'greater_than', 'value' => 100]
];
```

### 3. Sorting

```php
// Set sorting programmatically
$this->sortColumn = 'created_at';
$this->sortDirection = 'desc';

// Or let users sort by clicking column headers in the view
```

### 4. Export Data

```php
// Export filtered/searched data
public function exportData($format = 'csv')
{
    return $this->exportForeachData($format);
}
```

## Configuration Options

### Configure ForEach Behavior

```php
public function configureForeEach()
{
    return [
        'enable_deep_search' => true,         // Enable recursive search in nested objects
        'search_recursion_limit' => 10,      // Prevent infinite recursion
        'case_sensitive_search' => false,    // Case-insensitive search
        'enable_type_aware_sorting' => true, // Sort by data type
        'null_sort_order' => 'last',        // 'first' or 'last'
        'enable_memory_optimization' => true, // Enable memory-efficient processing
        'chunk_size' => 1000,               // Process data in chunks for large datasets
    ];
}
```

## View Integration

### Basic View Template

```blade
<div>
    {{-- Search and Controls --}}
    <div class="mb-4">
        <input 
            type="text" 
            wire:model.live="search" 
            placeholder="Search..." 
            class="border rounded px-3 py-2"
        >
        
        <select wire:model.live="perPage" class="border rounded px-3 py-2 ml-2">
            <option value="10">10 per page</option>
            <option value="25">25 per page</option>
            <option value="50">50 per page</option>
        </select>
    </div>

    {{-- Data Table --}}
    <table class="w-full border-collapse border">
        <thead>
            <tr>
                @foreach($this->getVisibleColumns() as $key => $column)
                    <th class="border px-4 py-2 cursor-pointer" 
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
            @foreach($data as $item)
                <tr>
                    @foreach($this->getVisibleColumns() as $key => $column)
                        <td class="border px-4 py-2">
                            {{ data_get($item, $column['key']) }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $data->links() }}
    </div>

    {{-- Statistics --}}
    @if($stats)
        <div class="mt-4 text-sm text-gray-600">
            Total: {{ $stats['total'] }} | 
            Filtered: {{ $stats['filtered'] }} | 
            Current Page: {{ $stats['current_page'] }} of {{ $stats['total_pages'] }}
        </div>
    @endif
</div>
```

## Available Methods

### Core Methods

- `setForEachData($data)` - Set the array/collection data
- `getForeachData()` - Get processed and paginated data
- `enableForeachMode()` - Enable ForEach mode
- `disableForeachMode()` - Disable ForEach mode
- `isForeachMode()` - Check if in ForEach mode

### Processing Methods

- `processForeachItem($item)` - Process individual item
- `batchProcessForeachItems($items)` - Process items in batches
- `configureForeEach()` - Configure ForEach behavior

### Statistics and Export

- `getForeachStats()` - Get statistics about the data
- `exportForeachData($format)` - Export data in specified format

## Performance Considerations

1. **Memory Usage**: For large datasets, the trait automatically chunks data processing
2. **Search Performance**: Deep search is optimized with recursion limits
3. **Sorting Efficiency**: Type-aware sorting is optimized for different data types
4. **Lazy Loading**: Only processes the data needed for the current page

## Best Practices

1. **Data Structure**: Use consistent data structures for better performance
2. **Column Configuration**: Configure only the columns you need to display
3. **Memory Management**: For very large datasets (>10,000 items), consider pagination at the data source level
4. **Type Consistency**: Ensure consistent data types within columns for optimal sorting

## Troubleshooting

### Common Issues

1. **Nested Data Not Searchable**: Ensure you're using dot notation in column keys
2. **Sorting Not Working**: Check that data types are consistent within columns
3. **Memory Issues**: Enable memory optimization and adjust chunk size
4. **Search Too Slow**: Disable deep search for very nested data structures

### Debug Information

```php
// Get debug information
$stats = $this->getForeachStats();
dd($stats); // Shows total items, filtered count, memory usage, etc.
```

## Examples

### E-commerce Product Listing

```php
$products = [
    ['id' => 1, 'name' => 'Laptop', 'price' => 999.99, 'category' => 'Electronics', 'stock' => 50],
    ['id' => 2, 'name' => 'T-Shirt', 'price' => 19.99, 'category' => 'Clothing', 'stock' => 100],
    ['id' => 3, 'name' => 'Phone', 'price' => 699.99, 'category' => 'Electronics', 'stock' => 30],
];

$this->columns = [
    'name' => ['key' => 'name', 'label' => 'Product Name'],
    'price' => ['key' => 'price', 'label' => 'Price'],
    'category' => ['key' => 'category', 'label' => 'Category'],
    'stock' => ['key' => 'stock', 'label' => 'Stock'],
];

$this->setForEachData($products);

// Users can now search for "laptop", filter by category, sort by price, etc.
```

### User Management with Nested Data

```php
$users = [
    [
        'id' => 1,
        'personal' => ['name' => 'John Doe', 'email' => 'john@example.com'],
        'role' => ['name' => 'Admin', 'permissions' => ['read', 'write', 'delete']],
        'status' => 'active',
        'last_login' => '2024-01-15 10:30:00'
    ]
];

$this->columns = [
    'name' => ['key' => 'personal.name', 'label' => 'Name'],
    'email' => ['key' => 'personal.email', 'label' => 'Email'],
    'role' => ['key' => 'role.name', 'label' => 'Role'],
    'status' => ['key' => 'status', 'label' => 'Status'],
    'last_login' => ['key' => 'last_login', 'label' => 'Last Login'],
];

$this->setForEachData($users);
```

This documentation provides comprehensive guidance on using the ForEach functionality to bring the full power of the datatable system to array and collection data processing.
