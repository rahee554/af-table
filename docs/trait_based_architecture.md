# AF Table - Trait-Based Architecture

This document outlines the planned trait-based architecture for the AF Table package, designed to improve maintainability, testability, and extensibility while keeping the current functionality intact.

## Current State vs. Target Architecture

### Current Implementation
The AF Table package currently uses a monolithic `Datatable.php` component (1,200+ lines) that handles all functionality in a single class. While this works well, it presents challenges for:

- **Maintainability**: Large file with mixed concerns
- **Testing**: Difficult to test individual features in isolation
- **Extensibility**: Hard to add new features without modifying core class
- **Code Reusability**: Cannot reuse specific functionality in other components

### Target Architecture
The new trait-based architecture will split functionality into focused, reusable traits while maintaining backward compatibility.

---

## Trait Structure Overview

```php
<?php

namespace ArtflowStudio\Table\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use ArtflowStudio\Table\Traits\{
    HasColumns,
    HasFilters,
    HasSorting,
    HasSearch,
    HasExport,
    HasActions,
    HasRealTimeUpdates,
    HasCache,
    HasRelationships,
    HasValidation
};

class Datatable extends Component
{
    use WithPagination,
        HasColumns,
        HasFilters,
        HasSorting,
        HasSearch,
        HasExport,
        HasActions,
        HasRealTimeUpdates,
        HasCache,
        HasRelationships,
        HasValidation;

    // Core properties only
    public $model;
    public $tableId;
    public $query;
    
    // Component lifecycle methods
    public function mount() { ... }
    public function render() { ... }
}
```

---

## Detailed Trait Specifications

### 1. `HasColumns` Trait

**Purpose**: Manage column configuration, visibility, and processing
**File**: `src/Traits/HasColumns.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Session;

trait HasColumns
{
    // Properties
    public array $columns = [];
    public array $visibleColumns = [];
    public bool $colvisBtn = true;
    public bool $index = false;

    // Column Management Methods
    public function initializeColumns(array $columns): void;
    public function getDefaultVisibleColumns(): array;
    public function getValidatedVisibleColumns(array $sessionVisibility): array;
    public function toggleColumnVisibility(string $columnKey): void;
    public function updateColumnVisibility(string $columnKey): void;
    public function clearColumnVisibilitySession(): void;
    
    // Column Processing Methods
    public function calculateSelectColumns(array $columns): array;
    public function getValidSelectColumns(): array;
    public function isValidColumn(string $column): bool;
    public function isAllowedColumn(string $column): bool;
    
    // JSON Column Methods
    public function extractJsonValue(object $row, string $jsonColumn, string $jsonPath): mixed;
    public function processJsonColumn(object $row, array $column): string;
    
    // Function Column Methods
    public function processFunctionColumn(object $row, array $column): string;
    public function executeFunctionWithTemplate(object $row, array $column): string;
    
    // Session Management
    protected function getColumnVisibilitySessionKey(): string;
    
    // Column Type Detection
    protected function getColumnType(array $column): string;
    protected function isJsonColumn(array $column): bool;
    protected function isFunctionColumn(array $column): bool;
    protected function isRelationColumn(array $column): bool;
}
```

### 2. `HasFilters` Trait

**Purpose**: Handle all filtering functionality
**File**: `src/Traits/HasFilters.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    // Properties
    public array $filters = [];
    public ?string $filterColumn = null;
    public string $filterOperator = '=';
    public mixed $filterValue = null;
    public ?string $selectedColumn = null;
    public string $columnType = 'text';
    public array $distinctValues = [];

    // Filter Application Methods
    public function applyFilters(Builder $query): void;
    public function applyDateRange(string $startDate, string $endDate): void;
    public function clearFilter(): void;
    
    // Filter Configuration Methods
    public function updatedSelectedColumn(string $column): void;
    public function updatedFilterColumn(): void;
    public function getDistinctValues(string $columnKey): array;
    
    // Filter Value Processing
    protected function getDefaultOperator(string $filterType): string;
    protected function prepareFilterValue(string $filterType, string $operator, mixed $value): mixed;
    protected function sanitizeFilterValue(mixed $value): mixed;
    
    // Distinct Values Management
    protected function getCachedDistinctValues(string $columnKey): array;
    protected function getRelationDistinctValues(string $columnKey): array;
    protected function getColumnDistinctValues(string $columnKey): array;
}
```

### 3. `HasSorting` Trait

**Purpose**: Manage sorting functionality
**File**: `src/Traits/HasSorting.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSorting
{
    // Properties
    public ?string $sortColumn = null;
    public string $sortDirection = 'asc';
    public string $sort = 'desc';
    public bool $colSort = true;

    // Sorting Methods
    public function toggleSort(string $column): void;
    public function applyOptimizedSorting(Builder $query): void;
    public function applyJoinSorting(Builder $query, $relationObj, string $attribute, string $direction = 'asc'): void;
    
    // Sort Configuration
    protected function getOptimalSortColumn(): ?string;
    protected function validateSortDirection(string $direction): string;
    protected function isSortableColumn(array $column): bool;
    
    // Advanced Sorting
    protected function sortByRelation(Builder $query, array $column, string $direction): void;
    protected function sortByJsonColumn(Builder $query, array $column, string $direction): void;
    protected function sortByFunctionColumn(Builder $query, array $column, string $direction): void;
}
```

### 4. `HasSearch` Trait

**Purpose**: Global and column-specific search functionality
**File**: `src/Traits/HasSearch.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    // Properties
    public string $search = '';
    public bool $searchable = true;

    // Search Methods
    public function updatedSearch(): void;
    public function applyOptimizedSearch(Builder $query): void;
    public function applyColumnSearch(Builder $query, string $columnKey, string $search): void;
    
    // Search Processing
    protected function sanitizeSearch(string $search): string;
    protected function buildSearchQuery(Builder $query, string $search): void;
    protected function searchInColumn(Builder $query, array $column, string $search): void;
    protected function searchInRelation(Builder $query, array $column, string $search): void;
    protected function searchInJsonColumn(Builder $query, array $column, string $search): void;
}
```

### 5. `HasExport` Trait

**Purpose**: Export functionality (CSV, Excel, PDF)
**File**: `src/Traits/HasExport.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

trait HasExport
{
    // Properties
    public bool $exportable = false;
    public bool $printable = false;

    // Export Methods
    public function export(string $format): mixed;
    public function exportPdfChunked(): mixed;
    public function getDataForExport(): mixed;
    
    // Export Configuration
    protected function prepareExportData(): array;
    protected function getExportColumns(): array;
    protected function formatExportValue(mixed $value, array $column): string;
    
    // Memory Optimization
    protected function exportInChunks(int $chunkSize = 1000): mixed;
    protected function streamExport(string $format): mixed;
}
```

### 6. `HasActions` Trait

**Purpose**: Row actions and bulk operations
**File**: `src/Traits/HasActions.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

trait HasActions
{
    // Properties
    public array $actions = [];
    public bool $checkbox = false;
    public array $selectedRows = [];
    public bool $selectAll = false;

    // Action Methods
    public function renderRawHtml(string $rawTemplate, object $row): string;
    public function getDynamicClass(array $column, object $row): string;
    
    // Bulk Operations
    public function toggleSelectAll(): void;
    public function selectAllRows(): void;
    public function deselectAllRows(): void;
    public function getSelectedRowsData(): array;
    
    // Action Processing
    protected function getColumnsNeededForActions(): array;
    protected function processActionTemplate(string $template, object $row): string;
    protected function executeCustomAction(string $action, array $selectedRows): void;
}
```

### 7. `HasRealTimeUpdates` Trait

**Purpose**: Real-time UI updates and WebSocket integration
**File**: `src/Traits/HasRealTimeUpdates.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

trait HasRealTimeUpdates
{
    // Properties
    public bool $refreshBtn = false;
    protected array $listeners = [
        'refreshTable' => '$refresh',
        'updateColumnVisibility' => 'handleColumnVisibilityUpdate',
        'liveDataUpdate' => 'handleLiveDataUpdate'
    ];

    // Real-time Methods
    public function refreshTable(): void;
    public function handleColumnVisibilityUpdate(string $columnKey): void;
    public function handleLiveDataUpdate(array $data): void;
    
    // WebSocket Integration (Future)
    protected function broadcastUpdate(string $event, array $data): void;
    protected function subscribeToChannel(string $channel): void;
    protected function unsubscribeFromChannel(string $channel): void;
    
    // Live Updates
    protected function enableLiveMode(): void;
    protected function disableLiveMode(): void;
    protected function syncWithServer(): void;
}
```

### 8. `HasCache` Trait

**Purpose**: Caching strategies and performance optimization
**File**: `src/Traits/HasCache.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCache
{
    // Properties
    protected ?array $cachedRelations = null;
    protected ?array $cachedSelectColumns = null;
    protected int $distinctValuesCacheTime = 300;
    protected int $maxDistinctValues = 1000;

    // Cache Management
    public function clearDistinctValuesCache(): void;
    public function clearAllTableCache(): void;
    public function warmCache(): void;
    
    // Cache Helpers
    protected function getCacheKey(string $type, string $identifier = ''): string;
    protected function cacheData(string $key, mixed $data, int $ttl = null): void;
    protected function getCachedData(string $key, callable $callback = null): mixed;
    
    // Performance Caching
    protected function cacheSelectColumns(): void;
    protected function cacheRelations(): void;
    protected function cacheDistinctValues(string $column): void;
}
```

### 9. `HasRelationships` Trait

**Purpose**: Advanced relationship handling
**File**: `src/Traits/HasRelationships.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

trait HasRelationships
{
    // Relationship Processing
    public function calculateRequiredRelations(array $columns): array;
    public function processRelationColumn(object $row, array $column): string;
    public function handleNestedRelation(object $row, string $relationPath, string $attribute): mixed;
    
    // Relationship Detection
    protected function detectRelationsInTemplate(string $template): array;
    protected function validateRelationPath(string $path): bool;
    protected function getRelationDepth(string $relationPath): int;
    
    // Optimization
    protected function optimizeRelationLoading(array $relations): array;
    protected function eagerLoadRelations(array $relations): void;
    protected function lazyLoadRelation(string $relation): void;
    
    // Advanced Features
    protected function handlePolymorphicRelation(object $row, string $relation): mixed;
    protected function handleManyToManyRelation(object $row, string $relation, string $attribute): mixed;
    protected function handleThroughRelation(object $row, string $relation, string $attribute): mixed;
}
```

### 10. `HasValidation` Trait

**Purpose**: Input validation and security
**File**: `src/Traits/HasValidation.php`

```php
<?php

namespace ArtflowStudio\Table\Traits;

trait HasValidation
{
    // Validation Methods
    protected function validateColumnConfiguration(array $columns): array;
    protected function validateFilterConfiguration(array $filters): array;
    protected function validateQueryConstraints(array $query): array;
    
    // Security Validation
    protected function sanitizeInput(mixed $input): mixed;
    protected function validateSqlInjection(string $input): bool;
    protected function validateXssAttempt(string $input): bool;
    
    // Column Validation
    protected function validateColumnExists(string $column): bool;
    protected function validateRelationExists(string $relation): bool;
    protected function validateFunctionExists(string $function): bool;
    
    // Error Handling
    protected function handleValidationError(string $error, array $context = []): void;
    protected function logSecurityAttempt(string $type, array $data): void;
}
```

---

## Implementation Strategy

### Phase 1: Foundation Setup (Week 1-2)
1. **Create Trait Directory Structure**
   ```
   src/
   ├── Traits/
   │   ├── HasColumns.php
   │   ├── HasFilters.php
   │   ├── HasSorting.php
   │   ├── HasSearch.php
   │   ├── HasExport.php
   │   ├── HasActions.php
   │   ├── HasRealTimeUpdates.php
   │   ├── HasCache.php
   │   ├── HasRelationships.php
   │   └── HasValidation.php
   ├── Http/Livewire/
   │   └── Datatable.php (refactored)
   └── Tests/Traits/
       ├── HasColumnsTest.php
       ├── HasFiltersTest.php
       └── ... (test for each trait)
   ```

2. **Extract Core Functionality**
   - Move column management to `HasColumns`
   - Move filtering logic to `HasFilters`
   - Move sorting logic to `HasSorting`

### Phase 2: Advanced Features (Week 3-4)
1. **Move Complex Logic**
   - Extract relationship handling to `HasRelationships`
   - Move export functionality to `HasExport`
   - Extract caching to `HasCache`

2. **Add New Features**
   - Implement real-time updates in `HasRealTimeUpdates`
   - Add comprehensive validation in `HasValidation`

### Phase 3: Testing and Optimization (Week 5-6)
1. **Comprehensive Testing**
   - Unit tests for each trait
   - Integration tests for trait combinations
   - Performance testing

2. **Documentation**
   - Update README with new architecture
   - Create trait-specific documentation
   - Migration guide for existing implementations

### Phase 4: Advanced Features (Week 7-8)
1. **Plugin System**
   - Create plugin interface
   - Allow custom traits
   - Plugin marketplace preparation

2. **Performance Optimization**
   - Benchmark each trait
   - Optimize database queries
   - Memory usage optimization

---

## Benefits of Trait-Based Architecture

### 1. **Modularity**
- Each trait has a single responsibility
- Easy to understand and maintain
- Clear separation of concerns

### 2. **Testability**
- Individual traits can be unit tested
- Mock dependencies easily
- Isolated testing of features

### 3. **Extensibility**
- Add new traits for new features
- Compose custom datatable components
- Plugin system ready

### 4. **Reusability**
- Use traits in other components
- Mix and match functionality
- Create specialized components

### 5. **Maintainability**
- Smaller, focused files
- Clear interfaces
- Easier debugging

### 6. **Performance**
- Only load needed functionality
- Optimize individual traits
- Better caching strategies

---

## Migration Strategy

### Backward Compatibility
- Current `Datatable` class will remain functional
- New trait-based implementation will be additive
- Gradual migration path for existing projects

### Migration Steps
1. **Keep Current Implementation**
   - Existing `Datatable.php` remains unchanged initially
   - New trait-based version as `DatatableV2.php`

2. **Gradual Feature Migration**
   - Move features to traits one by one
   - Test each migration thoroughly
   - Maintain API compatibility

3. **Final Migration**
   - Replace monolithic class with trait-based version
   - Provide migration guide
   - Update documentation

### Configuration Changes
```php
// Current usage (unchanged)
@livewire('aftable', [
    'model' => 'App\Models\User',
    'columns' => [...],
    // ... other options
])

// New trait-based usage (same interface)
@livewire('aftable', [
    'model' => 'App\Models\User',
    'columns' => [...],
    'traits' => ['HasAdvancedSearch', 'HasBulkOperations'], // Optional custom traits
])
```

---

## Advanced Features Enabled by Traits

### 1. **Custom Datatable Components**
```php
class CustomReportTable extends Component
{
    use HasColumns, HasFilters, HasExport, HasCustomReporting;
    
    // Custom implementation for specific use case
}
```

### 2. **Plugin System**
```php
class DatatableWithPlugins extends Datatable
{
    use HasPlugins;
    
    protected array $plugins = [
        'chart-integration',
        'advanced-filters',
        'real-time-sync'
    ];
}
```

### 3. **Specialized Components**
```php
class ApiDataTable extends Component
{
    use HasColumns, HasFilters, HasApiIntegration, HasCache;
    
    // Specialized for API data sources
}

class ArrayDataTable extends Component
{
    use HasColumns, HasFilters, HasArrayDataSource;
    
    // Specialized for array/collection data sources
}
```

---

## Testing Strategy

### Unit Testing
Each trait will have comprehensive unit tests:

```php
class HasColumnsTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_column_visibility_toggle(): void
    {
        $component = new DatatableTestComponent();
        $component->toggleColumnVisibility('name');
        
        $this->assertFalse($component->visibleColumns['name']);
    }
    
    public function test_json_column_extraction(): void
    {
        $component = new DatatableTestComponent();
        $value = $component->extractJsonValue($row, 'data', 'contact.email');
        
        $this->assertEquals('test@example.com', $value);
    }
}
```

### Integration Testing
Test trait combinations:

```php
class DatatableIntegrationTest extends TestCase
{
    public function test_column_visibility_with_filters(): void
    {
        // Test interaction between HasColumns and HasFilters
    }
    
    public function test_sorting_with_relationships(): void
    {
        // Test interaction between HasSorting and HasRelationships
    }
}
```

### Performance Testing
Benchmark each trait:

```php
class DatatablePerformanceTest extends TestCase
{
    public function test_column_processing_performance(): void
    {
        $startTime = microtime(true);
        
        // Process 1000 rows with complex columns
        
        $endTime = microtime(true);
        $this->assertLessThan(1.0, $endTime - $startTime); // Under 1 second
    }
}
```

---

## Documentation Structure

```
docs/
├── trait-based-architecture.md (this file)
├── traits/
│   ├── has-columns.md
│   ├── has-filters.md
│   ├── has-sorting.md
│   ├── has-search.md
│   ├── has-export.md
│   ├── has-actions.md
│   ├── has-real-time-updates.md
│   ├── has-cache.md
│   ├── has-relationships.md
│   └── has-validation.md
├── examples/
│   ├── custom-components.md
│   ├── plugin-development.md
│   └── migration-guide.md
└── api/
    ├── trait-reference.md
    └── method-reference.md
```

---

## Conclusion

The trait-based architecture will transform the AF Table package into a modular, extensible, and maintainable solution while preserving all current functionality and maintaining backward compatibility. This architecture sets the foundation for advanced features, plugin systems, and specialized components while improving code quality and developer experience.

The implementation will be done gradually over 8 weeks, ensuring stability and thorough testing at each phase. The end result will be a more powerful, flexible, and future-ready datatable solution.

---

*Document Version: 1.0*  
*Last Updated: January 15, 2025*  
*Architecture Target: AF Table v3.0*
