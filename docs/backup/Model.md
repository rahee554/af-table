# Model Integration Documentation

## Overview

The DatatableTrait's model integration provides a powerful, flexible system for working with Eloquent models, offering advanced querying, relationships, JSON column support, and optimization features that make database-driven datatables both performant and feature-rich.

## Purpose

The model integration enables you to:

- Work seamlessly with Eloquent models and relationships
- Handle complex database queries with optimization
- Support JSON columns and nested data structures
- Implement advanced filtering and searching
- Manage memory and query performance
- Handle large datasets efficiently

## Core Features

### 1. Eloquent Model Integration
- **Direct Model Support**: Work with any Eloquent model
- **Query Builder Integration**: Full Laravel query builder support
- **Relationship Loading**: Eager loading for optimal performance
- **Scopes Support**: Use model scopes for business logic

### 2. Advanced Relationships
- **Nested Relationships**: Support for deep relationship chains
- **Dynamic Loading**: Load relationships based on visible columns
- **Optimized Queries**: Prevent N+1 queries automatically
- **Polymorphic Support**: Handle polymorphic relationships

### 3. JSON Column Support
- **Nested JSON Paths**: Query deep into JSON structures
- **Type-aware Searching**: Handle different JSON data types
- **Filtering Support**: Filter by JSON column values
- **Sorting Capabilities**: Sort by JSON column values

### 4. Query Optimization
- **Column Selection**: Load only required columns
- **Memory Management**: Chunked processing for large datasets
- **Index Utilization**: Optimize queries for database indexes
- **Caching Support**: Intelligent query result caching

## Basic Setup

### Simple Model Configuration

```php
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class UserTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        // Set the model
        $this->model = User::class;

        // Configure columns
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
            'created_at' => ['key' => 'created_at', 'label' => 'Created'],
        ];
    }

    public function render()
    {
        return view('livewire.user-table', [
            'users' => $this->getQuery()->paginate($this->perPage)
        ]);
    }
}
```

### Model with Relationships

```php
class UserTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = User::class;

        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Full Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
            
            // Simple relationship
            'profile_name' => ['relation' => 'profile:name', 'label' => 'Profile Name'],
            
            // Nested relationship
            'company_name' => ['relation' => 'profile.company:name', 'label' => 'Company'],
            
            // Relationship with custom display
            'role_name' => ['relation' => 'roles:name', 'label' => 'Role'],
        ];
    }
}
```

### JSON Column Support

```php
class ProductTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = Product::class;

        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Product Name'],
            'price' => ['key' => 'price', 'label' => 'Price'],
            
            // JSON column with nested path
            'color' => ['key' => 'attributes', 'json' => 'color', 'label' => 'Color'],
            'size' => ['key' => 'attributes', 'json' => 'size', 'label' => 'Size'],
            
            // Deep nested JSON path
            'setting_theme' => ['key' => 'settings', 'json' => 'display.theme', 'label' => 'Theme'],
            'user_pref' => ['key' => 'metadata', 'json' => 'user.preferences.language', 'label' => 'Language'],
        ];
    }
}
```

## Advanced Configuration

### Query Customization

```php
class CustomUserTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = User::class;
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
        ];
    }

    // Override the base query
    public function getQuery()
    {
        $query = parent::getQuery();
        
        // Add custom constraints
        $query->where('status', 'active')
              ->where('created_at', '>', now()->subMonth());
        
        // Add custom scopes
        $query->verified();
        
        return $query;
    }

    // Add custom filters
    public function applyFilters($query)
    {
        $query = parent::applyFilters($query);
        
        // Custom filter logic
        if (isset($this->filters['department'])) {
            $query->whereHas('profile', function($q) {
                $q->where('department', $this->filters['department']['value']);
            });
        }
        
        return $query;
    }
}
```

### Relationship Optimization

```php
class OptimizedUserTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = User::class;
        
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
            'profile_name' => ['relation' => 'profile:name', 'label' => 'Profile'],
            'company_name' => ['relation' => 'profile.company:name', 'label' => 'Company'],
            'role_names' => ['relation' => 'roles:name', 'label' => 'Roles'],
        ];
        
        // Enable relationship optimization
        $this->enableRelationshipOptimization = true;
    }

    // Define which relationships to eager load
    public function getEagerLoadRelations()
    {
        return [
            'profile',
            'profile.company',
            'roles'
        ];
    }
}
```

## Column Types and Configuration

### Basic Column Types

```php
$this->columns = [
    // Simple database column
    'id' => ['key' => 'id', 'label' => 'ID'],
    
    // Database column with custom label
    'name' => ['key' => 'name', 'label' => 'Full Name'],
    
    // Column with searchable configuration
    'email' => ['key' => 'email', 'label' => 'Email', 'searchable' => true],
    
    // Column with sortable configuration
    'created_at' => ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
    
    // Non-searchable, non-sortable column
    'actions' => ['key' => 'id', 'label' => 'Actions', 'searchable' => false, 'sortable' => false],
];
```

### Relationship Columns

```php
$this->columns = [
    // Simple relationship (belongsTo)
    'profile_name' => ['relation' => 'profile:name', 'label' => 'Profile Name'],
    
    // Nested relationship
    'company_name' => ['relation' => 'profile.company:name', 'label' => 'Company'],
    
    // HasMany relationship (shows count)
    'orders_count' => ['relation' => 'orders:count', 'label' => 'Orders'],
    
    // Many-to-many relationship
    'role_names' => ['relation' => 'roles:name', 'label' => 'Roles'],
    
    // Polymorphic relationship
    'commentable_name' => ['relation' => 'commentable:name', 'label' => 'Related Item'],
];
```

### JSON Columns

```php
$this->columns = [
    // Simple JSON path
    'theme' => ['key' => 'settings', 'json' => 'theme', 'label' => 'Theme'],
    
    // Nested JSON path
    'language' => ['key' => 'preferences', 'json' => 'locale.language', 'label' => 'Language'],
    
    // Deep nested JSON
    'notification_email' => [
        'key' => 'settings', 
        'json' => 'notifications.email.enabled', 
        'label' => 'Email Notifications'
    ],
    
    // JSON array element
    'first_tag' => ['key' => 'tags', 'json' => '0', 'label' => 'Primary Tag'],
];
```

## Search and Filtering

### Search Configuration

```php
// Enable global search across specific columns
public function getSearchableColumns()
{
    return [
        'name',           // Simple column
        'email',          // Simple column
        'profile:name',   // Relationship column
        'settings->theme' // JSON column
    ];
}

// Custom search logic
public function applySearch($query, $search)
{
    $query = parent::applySearch($query, $search);
    
    // Add custom search logic
    $query->orWhereHas('tags', function($q) use ($search) {
        $q->where('name', 'like', "%{$search}%");
    });
    
    return $query;
}
```

### Advanced Filtering

```php
// Configure filter operators for specific columns
public function getColumnFilters()
{
    return [
        'created_at' => [
            'operators' => ['date_equals', 'date_between', 'date_greater_than'],
            'type' => 'date'
        ],
        'price' => [
            'operators' => ['equals', 'greater_than', 'less_than', 'between'],
            'type' => 'number'
        ],
        'status' => [
            'operators' => ['equals', 'in', 'not_in'],
            'type' => 'select',
            'options' => ['active', 'inactive', 'pending']
        ],
        'profile:department' => [
            'operators' => ['equals', 'like'],
            'type' => 'string'
        ],
        'settings->theme' => [
            'operators' => ['equals', 'in'],
            'type' => 'json'
        ]
    ];
}
```

## Performance Optimization

### Query Optimization

```php
class OptimizedTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = User::class;
        
        // Enable query optimization
        $this->enableQueryOptimization = true;
        $this->enableColumnOptimization = true;
        $this->enableMemoryManagement = true;
    }

    // Optimize column selection
    public function getSelectColumns()
    {
        $columns = [];
        
        foreach ($this->getVisibleColumns() as $key => $column) {
            if (isset($column['key']) && !isset($column['relation'])) {
                $columns[] = $column['key'];
            }
        }
        
        return array_unique($columns);
    }

    // Customize chunking for large datasets
    public function getChunkSize()
    {
        return 1000; // Process in chunks of 1000 for memory efficiency
    }
}
```

### Caching Configuration

```php
class CachedTableComponent extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = User::class;
        
        // Enable caching
        $this->enableCaching = true;
        $this->cacheTimeout = 3600; // 1 hour
    }

    // Generate custom cache key
    public function getCacheKey()
    {
        return 'users_table_' . md5(json_encode([
            'search' => $this->search,
            'filters' => $this->filters,
            'sort' => $this->sortColumn . '_' . $this->sortDirection,
            'visible_columns' => array_keys($this->getVisibleColumns())
        ]));
    }

    // Define cache tags for invalidation
    public function getCacheTags()
    {
        return ['users', 'user_profiles', 'user_roles'];
    }
}
```

## Model Scopes Integration

### Using Model Scopes

```php
// In your User model
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithProfileComplete($query)
    {
        return $query->whereHas('profile', function($q) {
            $q->whereNotNull('name')
              ->whereNotNull('email');
        });
    }
}

// In your component
class UserTableComponent extends Component
{
    use DatatableTrait;

    public function getQuery()
    {
        $query = parent::getQuery();
        
        // Apply model scopes
        $query->active()
              ->withProfileComplete();
        
        return $query;
    }
}
```

### Dynamic Scope Application

```php
class DynamicScopeTableComponent extends Component
{
    use DatatableTrait;

    public $activeScopes = ['active'];

    public function mount()
    {
        $this->model = User::class;
        $this->columns = [
            'id' => ['key' => 'id', 'label' => 'ID'],
            'name' => ['key' => 'name', 'label' => 'Name'],
        ];
    }

    public function getQuery()
    {
        $query = parent::getQuery();
        
        // Apply dynamic scopes
        foreach ($this->activeScopes as $scope) {
            if (method_exists($this->model, 'scope' . ucfirst($scope))) {
                $query->{$scope}();
            }
        }
        
        return $query;
    }

    // Method to toggle scopes
    public function toggleScope($scope)
    {
        if (in_array($scope, $this->activeScopes)) {
            $this->activeScopes = array_diff($this->activeScopes, [$scope]);
        } else {
            $this->activeScopes[] = $scope;
        }
    }
}
```

## Export with Models

### Basic Model Export

```php
public function exportUsers($format = 'csv')
{
    // Get the current query with all filters/search applied
    $query = $this->getQuery();
    
    // Export with specific columns
    return $this->handleExport($format, [
        'filename' => 'users_export_' . date('Y-m-d'),
        'columns' => $this->getVisibleColumns(),
        'query' => $query
    ]);
}
```

### Advanced Export with Custom Data

```php
public function exportUsersWithCustomData($format = 'csv')
{
    $query = $this->getQuery();
    
    // Transform data before export
    $data = $query->get()->map(function ($user) {
        return [
            'ID' => $user->id,
            'Name' => $user->name,
            'Email' => $user->email,
            'Profile' => $user->profile ? $user->profile->name : 'N/A',
            'Roles' => $user->roles->pluck('name')->join(', '),
            'Status' => ucfirst($user->status),
            'Member Since' => $user->created_at->format('M j, Y'),
        ];
    });
    
    return $this->exportCustomData($data, $format);
}
```

## Best Practices

### 1. Performance Guidelines

```php
// Always eager load relationships
public function getEagerLoadRelations()
{
    return ['profile', 'roles', 'department'];
}

// Use column optimization for large tables
$this->enableColumnOptimization = true;

// Implement appropriate indexes in your migrations
Schema::table('users', function (Blueprint $table) {
    $table->index(['status', 'created_at']);
    $table->index('email');
});
```

### 2. Memory Management

```php
// For large datasets, implement chunking
public function getChunkSize()
{
    return 1000;
}

// Monitor memory usage
public function shouldUseChunking()
{
    return $this->getCurrentMemoryUsage()['percentage'] > 80;
}
```

### 3. Security Considerations

```php
// Validate model access
public function getQuery()
{
    $query = parent::getQuery();
    
    // Add tenant scoping
    if (auth()->user()->tenant_id) {
        $query->where('tenant_id', auth()->user()->tenant_id);
    }
    
    // Add permission checks
    if (!auth()->user()->can('view-all-users')) {
        $query->where('department_id', auth()->user()->department_id);
    }
    
    return $query;
}
```

## Troubleshooting

### Common Issues

1. **N+1 Queries**: Ensure proper eager loading with `getEagerLoadRelations()`
2. **Memory Issues**: Enable chunking and column optimization
3. **Slow Queries**: Check database indexes and query optimization
4. **JSON Path Errors**: Validate JSON paths and handle missing keys

### Debug Methods

```php
// Debug query performance
public function debugQuery()
{
    $query = $this->getQuery();
    dd($query->toSql(), $query->getBindings());
}

// Monitor memory usage
public function getMemoryStats()
{
    return [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ];
}
```

This comprehensive model integration provides the foundation for building powerful, efficient database-driven datatables with full Laravel ecosystem support.
