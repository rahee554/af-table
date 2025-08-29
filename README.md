# ArtflowStudio Laravel Livewire Datatable Package

A comprehensive, trait-based Laravel Livewire datatable package with advanced features for building powerful data tables with minimal configuration.

## 🚀 Features

### Core Features
- **Trait-Based Architecture**: Modular design with 18 specialized traits
- **Advanced Search**: Global and column-specific search capabilities
- **Smart Filtering**: Multiple filter types with caching
- **Flexible Sorting**: Column-based sorting with relation support
- **Dynamic Columns**: Show/hide columns with session persistence
- **Export Functionality**: CSV, JSON, Excel export with chunking
- **Relationship Support**: Eager loading and nested relationships
- **JSON Column Support**: Search and filter JSON data
- **Memory Management**: Automatic optimization for large datasets
- **Session Persistence**: Save and restore table state
- **Query String Support**: Shareable URLs with table state
- **Event System**: Comprehensive event listeners
- **Actions & Bulk Actions**: Row and bulk operations
- **Raw Templates**: Custom HTML templates with placeholders
- **Performance Optimization**: Caching, chunking, eager loading

### Available Traits

1. **HasQueryBuilder** - Core query building functionality
2. **HasDataValidation** - Column and data validation
3. **HasColumnConfiguration** - Column setup and management
4. **HasColumnVisibility** - Show/hide columns dynamically
5. **HasSearch** - Global and column search
6. **HasFiltering** - Advanced filtering capabilities
7. **HasSorting** - Column sorting with relations
8. **HasCaching** - Intelligent caching system
9. **HasEagerLoading** - Optimized relationship loading
10. **HasMemoryManagement** - Memory optimization
11. **HasJsonSupport** - JSON column operations
12. **HasRelationships** - Relationship handling
13. **HasExport** - Data export functionality
14. **HasRawTemplates** - Custom HTML templates
15. **HasSessionManagement** - State persistence
16. **HasQueryStringSupport** - URL-based state
17. **HasEventListeners** - Event system
18. **HasActions** - Row and bulk actions

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

### Actions

```php
// Row actions
$this->addAction('view', [
    'label' => 'View',
    'route' => ['name' => 'users.show', 'params' => ['{id}']],
    'icon' => 'eye',
    'class' => 'btn btn-info'
]);

$this->addAction('edit', [
    'label' => 'Edit',
    'url' => '/users/{id}/edit',
    'condition' => function($record) {
        return auth()->user()->can('update', $record);
    }
]);

// Bulk actions
$this->addBulkAction('activate', [
    'label' => 'Activate Selected',
    'handler' => function($recordIds) {
        User::whereIn('id', $recordIds)->update(['status' => 'active']);
        return ['success' => true, 'message' => 'Users activated'];
    }
]);
```

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
