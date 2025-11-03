# ArtflowStudio Laravel Livewire Datatable Package v1.5.1

A comprehensive, trait-based Laravel Livewire datatable package with advanced features for building powerful data tables with minimal configuration.

## ⚠️ Important Usage Note

**This component must be used directly in Blade views, NOT in Livewire component classes.**

✅ **Correct Usage:**
```blade
<!-- In a Blade view file -->
@livewire('aftable', [
    'model' => '\App\Models\User',
    'columns' => [...],
    // ... configuration
])
```

❌ **Incorrect Usage:**
```php
// Don't use inside a Livewire component class
class MyComponent extends Component { ... }
```

## 🚀 Version 1.5.1 Features

### Dynamic Action System
- **Button Actions**: Standard buttons with icons, classes, and HTTP methods
- **Toggle Actions**: Interactive toggle buttons with active state expressions
- **Raw HTML Actions**: Custom HTML content for complex actions
- **HTTP Method Support**: GET, POST, PUT, PATCH, DELETE with CSRF protection
- **Confirmation Dialogs**: Optional confirmation messages for destructive actions

### Enhanced Column Configuration
- **Direct Array Keys**: Simplified column configuration without named arrays
- **Backward Compatibility**: Legacy named array format still supported
- **Improved Generator**: Updated code generator with action type selection

### Package Improvements
- **Updated Documentation**: Comprehensive AGENTS.md with examples
- **Enhanced Generator**: Web-based code generator with dynamic action support
- **Better Error Handling**: Improved validation and debugging

---

### Core Features
- **Trait-Based Architecture**: Modular design with 23+ specialized traits
- **Advanced Search**: Global and column-specific search capabilities
- **Smart Filtering**: Multiple filter types with caching (text, number, date, distinct, relation)
- **Flexible Sorting**: Column-based sorting with relation support
- **Dynamic Columns**: Show/hide columns with session persistence
- **Export Functionality**: CSV, JSON, Excel export with chunking
- **Relationship Support**: Eager loading and nested relationships
- **JSON Column Support**: Search and filter JSON data with dot notation
- **Memory Management**: Automatic optimization for large datasets
- **Session Persistence**: Save and restore table state
- **Query String Support**: Shareable URLs with table state
- **Event System**: Comprehensive event listeners
- **Actions & Bulk Actions**: Row and bulk operations
- **Raw Templates**: Custom HTML templates with Blade syntax
- **Performance Optimization**: Caching, chunking, eager loading

### Available Traits

#### Core Traits (Essential Functionality)
1. **HasUnifiedSearch** - Global and column-specific search with optimization
2. **HasUnifiedValidation** - Input validation and sanitization
3. **HasTemplateRendering** - Cell value rendering with Blade templates
4. **HasActionHandling** - Row and bulk actions management
5. **HasBasicFeatures** - Core datatable features
6. **HasDataValidation** - Data type validation and security
7. **HasSorting** - Column sorting with relation support
8. **HasUnifiedCaching** - Intelligent caching system with distinct values
9. **HasRelationships** - Eloquent relationship handling (simple and nested)
10. **HasJsonSupport** - JSON column extraction with dot notation
11. **HasJsonFile** - JSON file operations
12. **HasColumnManagement** - Column configuration and management
13. **HasQueryBuilding** - Advanced query construction

#### UI Traits (User Interaction)
14. **HasColumnVisibility** - Dynamic column show/hide with session persistence
15. **HasEventListeners** - Livewire event system integration

#### Advanced Traits (Performance & Export)
16. **HasApiEndpoint** - API endpoint generation
17. **HasPerformanceMonitoring** - Performance tracking and statistics
18. **HasQueryOptimization** - Query performance optimization
19. **HasQueryStringSupport** - URL-based state sharing
20. **HasSessionManagement** - State persistence across sessions
21. **HasUnifiedOptimization** - Unified optimization strategies
22. **HasUtilities** - Helper methods and utilities
23. **HasExportFeatures** - CSV/Excel export with chunking

## 📦 Installation

```bash
composer require artflowstudio/table
```

## 🔧 Setup

### 1. Service Provider Registration

The package auto-registers via Laravel's package discovery. For manual registration:

```php
// config/app.php
'providers' => [
    ArtflowStudio\Table\TableServiceProvider::class,
],
```

### 2. Publish Assets (Optional)

```bash
php artisan vendor:publish --provider="ArtflowStudio\Table\TableServiceProvider" --tag=views
php artisan vendor:publish --provider="ArtflowStudio\Table\TableServiceProvider" --tag=assets
```

## 🎯 Usage

### Basic Usage

#### Original Datatable Component

```php
use ArtflowStudio\Table\Http\Livewire\Datatable;

class UserDatatable extends Datatable 
{
    public function mount()
    {
        $this->model = User::class;
        $this->columns = [
            'id' => ['label' => 'ID', 'sortable' => true],
            'name' => ['label' => 'Name', 'searchable' => true, 'sortable' => true],
            'email' => ['label' => 'Email', 'searchable' => true],
            'created_at' => ['label' => 'Created', 'sortable' => true],
        ];
    }
}
```

#### New Trait-Based Component

```php
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class UserTraitDatatable extends DatatableTrait 
{
    public function mount()
    {
        $this->model = User::class;
        $this->columns = [
            'id' => ['label' => 'ID', 'sortable' => true],
            'name' => ['label' => 'Name', 'searchable' => true, 'sortable' => true],
            'email' => ['label' => 'Email', 'searchable' => true],
            'profile_name' => ['label' => 'Profile', 'relation' => 'profile:name'],
            'settings_theme' => ['label' => 'Theme', 'json' => 'theme', 'key' => 'settings'],
            'created_at' => ['label' => 'Created', 'sortable' => true],
        ];
        
        // Configure filters
        $this->filters = [
            'status' => [
                'type' => 'select',
                'options' => ['active', 'inactive', 'pending']
            ],
            'created_at' => [
                'type' => 'date_range'
            ]
        ];
        
        // Setup actions
        $this->addAction('edit', [
            'label' => 'Edit',
            'route' => 'users.edit',
            'class' => 'btn btn-primary'
        ]);
        
        $this->addBulkAction('delete', [
            'label' => 'Delete Selected',
            'confirm' => 'Are you sure?',
            'handler' => [$this, 'bulkDelete']
        ]);
    }
    
    public function bulkDelete($recordIds)
    {
        User::whereIn('id', $recordIds)->delete();
        return ['success' => true, 'message' => 'Users deleted successfully'];
    }
}
```

### Blade Directives

#### Original Component
```blade
@AFtable(['model' => App\Models\User::class, 'columns' => [...]])
```

#### Trait-Based Component
```blade
@AFtableTrait(['model' => App\Models\User::class, 'columns' => [...]])
```

### Advanced Column Configuration

```php
$this->columns = [
    // Basic column
    'name' => [
        'label' => 'Full Name',
        'searchable' => true,
        'sortable' => true,
        'exportable' => true
    ],
    
    // Relationship column
    'user_name' => [
        'label' => 'User',
        'relation' => 'user:name',
        'searchable' => true
    ],
    
    // Nested relationship
    'company_address' => [
        'label' => 'Company Address',
        'relation' => 'user.company:address'
    ],
    
    // JSON column
    'preferences_theme' => [
        'label' => 'Theme Preference',
        'json' => 'theme',
        'key' => 'preferences'
    ],
    
    // Function column
    'full_name' => [
        'label' => 'Full Name',
        'function' => function($record) {
            return $record->first_name . ' ' . $record->last_name;
        }
    ],
    
    // Raw template column
    'status_badge' => [
        'label' => 'Status',
        'raw_template' => '<span class="badge badge-{status}">{status|upper}</span>'
    ]
];
```

### Filtering

```php
$this->filters = [
    'status' => [
        'type' => 'select',
        'options' => ['active', 'inactive', 'pending'],
        'default' => 'active'
    ],
    'category_id' => [
        'type' => 'select',
        'relation' => 'category:name',
        'multiple' => true
    ],
    'created_at' => [
        'type' => 'date_range'
    ],
    'salary' => [
        'type' => 'number_range',
        'min' => 0,
        'max' => 200000
    ]
];
```

### Actions Configuration (v1.5.1)

The new dynamic action system supports multiple action types:

#### Button Actions
```php
'actions' => [
    [
        'type' => 'button',
        'label' => 'Edit',
        'icon' => 'fas fa-edit',
        'class' => 'btn btn-primary btn-sm',
        'method' => 'GET',
        'url' => '/admin/users/{{$row->id}}/edit',
    ],
    [
        'type' => 'button',
        'label' => 'Delete',
        'icon' => 'fas fa-trash',
        'class' => 'btn btn-danger btn-sm',
        'method' => 'DELETE',
        'url' => '/admin/users/{{$row->id}}',
        'confirm' => 'Are you sure you want to delete this user?',
    ],
],
```

#### Toggle Actions
```php
'actions' => [
    [
        'type' => 'toggle',
        'label' => 'Active',
        'icon' => 'fas fa-toggle-on',
        'class' => 'btn btn-success btn-sm',
        'method' => 'PATCH',
        'url' => '/admin/users/{{$row->id}}/toggle-status',
        'active' => '{{$row->status == "active" ? "true" : "false"}}',
    ],
],
```

#### Raw HTML Actions
```php
'actions' => [
    [
        'type' => 'raw',
        'content' => '<a href="/admin/users/{{$row->id}}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View</a>',
    ],
],
```

### Column Configuration (v1.5.1)

Simplified column configuration using direct array keys:

```php
'columns' => [
    [
        'key' => 'id',
        'label' => 'ID',
        'sortable' => true,
    ],
    [
        'key' => 'name',
        'label' => 'Customer Name',
        'searchable' => true,
        'sortable' => true,
    ],
    [
        'key' => 'email',
        'label' => 'Email',
        'searchable' => true,
    ],
],
```

**Legacy Support**: Named arrays are still supported for backward compatibility.

### Event Listeners

```php
public function mount()
{
    // Setup event listeners
    $this->addEventListener('search_performed', function($datatable, $data) {
        Log::info('Search performed: ' . $data['search_term']);
    });
    
    $this->addEventListener('export_performed', function($datatable, $data) {
        // Track export events
        Analytics::track('datatable_export', $data);
    });
}
```

## 🧪 Testing & Development

### Create Test Data

Generate dummy test tables with realistic data:

```bash
# Create test table with 10,000 records
php artisan aftable:create-dummy-table

# Create custom table with specific record count
php artisan aftable:create-dummy-table custom_table --records=50000

# Force recreation of existing table
php artisan aftable:create-dummy-table --force
```

### Test Traits

Run comprehensive trait testing:

```bash
# Test all traits
php artisan aftable:test-traits

# Test specific trait
php artisan aftable:test-traits --trait=Search
```

### Cleanup

Remove test data and models:

```bash
php artisan aftable:cleanup-dummy-tables
```

## 📊 Performance Features

### Memory Management

The package automatically manages memory for large datasets:

```php
// Configure memory settings
$this->setMemoryThreshold(128 * 1024 * 1024); // 128MB
$this->setMaxBatchSize(1000);

// Get memory recommendations
$recommendations = $this->getMemoryOptimizationRecommendations();
```

### Caching

Intelligent caching system for improved performance:

```php
// Cache distinct values for filters
$distinctValues = $this->getCachedDistinctValues('category');

// Warm up cache
$this->warmUpCache();

// Clear caches
$this->clearAllCaches();
```

### Chunked Export

For large datasets, automatic chunking prevents memory issues:

```php
// Export with chunking (automatically applied for >10k records)
return $this->exportWithChunking('csv', 'large_export.csv', 1000);
```

## 📈 Advanced Features

### Query String Support

Share table state via URLs:

```php
// Enable query string support
public $enableQueryStringSupport = true;

// Generate shareable URL
$shareableUrl = $this->getShareableUrl();

// Generate specific URLs
$sortUrl = $this->generateSortUrl('name');
$filterUrl = $this->generateFilterUrl('status', 'active');
```

### Session Persistence

Maintain table state across page loads:

```php
// Enable session persistence
public $enableSessionPersistence = true;

// Manual state management
$this->saveStateToSession();
$this->loadStateFromSession();
$this->clearSessionState();
```

### Raw Templates

Create custom column templates:

```php
'user_info' => [
    'label' => 'User Info',
    'raw_template' => '
        <div class="user-card">
            <img src="/avatars/{id}.jpg" alt="{name}">
            <div>
                <strong>{name}</strong><br>
                <small>{email}</small><br>
                <span class="badge badge-{status}">{status|upper}</span>
            </div>
        </div>
    '
]
```

## 🔧 Configuration

### Component Configuration

```php
public function mount()
{
    $this->tableId = 'unique_table_id';
    $this->enableSessionPersistence = true;
    $this->enableQueryStringSupport = true;
    $this->distinctValuesCacheTime = 3600; // 1 hour
    $this->maxDistinctValues = 100;
    $this->perPage = 25;
}
```

### Global Configuration

Create a base datatable class for shared configuration:

```php
abstract class BaseDatatable extends DatatableTrait
{
    public function mount()
    {
        $this->enableSessionPersistence = config('aftable.session_persistence', true);
        $this->enableQueryStringSupport = config('aftable.query_string_support', true);
        $this->distinctValuesCacheTime = config('aftable.cache_time', 3600);
        
        parent::mount();
    }
}
```

## 📚 API Reference

### Statistics & Debugging

Get comprehensive statistics about your datatable:

```php
// Component statistics
$stats = $this->getComponentStats();

// Debug information
$debug = $this->getDebugInfo();

// Specific trait statistics
$memoryStats = $this->getMemoryStats();
$cacheStats = $this->getCacheStats();
$relationStats = $this->getRelationColumnStats();
$actionStats = $this->getActionStats();
```

### Validation

Validate your datatable configuration:

```php
// Validate columns
$columnValidation = $this->validateColumns();

// Validate relationships
$relationValidation = $this->validateRelationColumns();

// Test specific functionality
$jsonTest = $this->testJsonColumn('preferences_theme');
$relationTest = $this->testRelationColumn('user_name');
```

## 🏗️ Trait-Based Architecture

### Understanding the Architecture

The new trait-based architecture separates functionality into focused, reusable traits. Each trait handles a specific aspect of datatable functionality:

#### Core Traits
- **HasQueryBuilder**: Base query building and model interaction
- **HasDataValidation**: Input validation and security
- **HasColumnConfiguration**: Column setup and management

#### Feature Traits
- **HasSearch**: Global and column-specific search
- **HasFiltering**: Advanced filtering with multiple types
- **HasSorting**: Column sorting with relation support
- **HasColumnVisibility**: Dynamic column show/hide

#### Performance Traits
- **HasCaching**: Intelligent caching system
- **HasEagerLoading**: Optimized relationship loading
- **HasMemoryManagement**: Memory optimization for large datasets

#### Advanced Traits
- **HasJsonSupport**: JSON column operations
- **HasRelationships**: Complex relationship handling
- **HasExport**: Data export functionality
- **HasRawTemplates**: Custom HTML templates
- **HasSessionManagement**: State persistence
- **HasQueryStringSupport**: URL-based state management
- **HasEventListeners**: Event system
- **HasActions**: Row and bulk actions

### Using Individual Traits

You can use individual traits in your own components:

```php
use ArtflowStudio\Table\Traits\HasSearch;
use ArtflowStudio\Table\Traits\HasFiltering;

class CustomComponent extends Component
{
    use HasSearch, HasFiltering;
    
    public function mount()
    {
        $this->initializeSearch();
        $this->initializeFiltering();
    }
}
```

### Creating Custom Traits

Extend the package with your own traits:

```php
trait HasCustomFeature
{
    public function initializeCustomFeature()
    {
        // Initialize custom functionality
    }
    
    public function customMethod()
    {
        // Custom implementation
    }
}
```

## 🔌 Extension Points

### Custom Export Formats

Add new export formats:

```php
public function exportToXml($filename = 'export.xml')
{
    $data = $this->getExportData();
    // Custom XML export logic
    return response()->streamDownload(function() use ($data) {
        echo $this->generateXml($data);
    }, $filename);
}
```

### Custom Filter Types

Create custom filter types:

```php
public function addCustomFilter($column, $config)
{
    $this->customFilters[$column] = $config;
    // Custom filter implementation
}
```

### Custom Actions

Add complex action handlers:

```php
public function addComplexAction($key, $config)
{
    $this->addAction($key, array_merge($config, [
        'handler' => [$this, 'handleComplexAction']
    ]));
}

public function handleComplexAction($record, $params)
{
    // Complex action logic
    return ['success' => true, 'message' => 'Action completed'];
}
```

## 🧪 Comprehensive Testing Suite

### Available Test Commands

The package includes comprehensive testing utilities:

```bash
# Create dummy test data
php artisan aftable:create-dummy-table

# Test all traits
php artisan aftable:test-traits

# Test specific trait
php artisan aftable:test-traits --trait=HasSearch

# Cleanup test data
php artisan aftable:cleanup-dummy-tables
```

### Test Coverage

Each trait is individually tested:

- **Query Building**: Tests query construction and optimization
- **Search Functionality**: Tests global and column search
- **Filtering**: Tests all filter types and combinations
- **Sorting**: Tests single and multi-column sorting
- **Memory Management**: Tests memory optimization features
- **Export Functions**: Tests all export formats
- **Caching**: Tests cache performance and invalidation
- **Relationships**: Tests simple and complex relationships
- **JSON Support**: Tests JSON column extraction
- **Session Management**: Tests state persistence
- **Event System**: Tests event listeners and dispatching

### Performance Testing

Performance tests validate:

- Memory usage with large datasets
- Query optimization effectiveness
- Cache hit rates
- Export performance with chunking
- Relationship loading efficiency

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run tests (`php artisan aftable:test-traits`)
4. Commit your changes (`git commit -am 'Add amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## 📝 Changelog

### Version 1.5.1 (Latest)
- **Dynamic Action System**: Added support for button, toggle, and raw HTML actions
- **Enhanced Column Configuration**: Simplified column setup with direct array keys
- **Updated Generator**: Web-based code generator with action type selection
- **Improved Documentation**: Comprehensive AGENTS.md with examples
- **Backward Compatibility**: Legacy action and column formats still supported

### Version 1.4
- **Real-Time Column Visibility**: Instant updates with wire:model.live
- **Smart Index Column**: Sort-aware indexing with proper pagination
- **Enhanced JSON Support**: Better handling of complex JSON structures
- **Improved Delete Operations**: Better event handling and parent communication
- **Session Persistence**: Column visibility stored across page loads
- **Trait-Based Ready**: Architecture prepared for modular v3.0

### Version 1.3
- **Advanced Search**: Global and column-specific search capabilities
- **Smart Filtering**: Multiple filter types with caching
- **Flexible Sorting**: Column-based sorting with relation support
- **Dynamic Columns**: Show/hide columns with session persistence
- **Export Functionality**: CSV, JSON, Excel export with chunking

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

- **ArtflowStudio** - Package development
- **Laravel Livewire** - Reactive components
- **Laravel Framework** - Foundation

## 🔮 Roadmap

- [ ] Vue.js/React integration
- [ ] Real-time updates with WebSockets
- [ ] Advanced chart integration
- [ ] Data visualization components
- [ ] API endpoint generation
- [ ] GraphQL support

---

**Made with ❤️ by ArtflowStudio**
