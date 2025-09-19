# AF Table Trait API Reference 📚

## Overview

This document provides comprehensive API documentation for all traits in the AF Table package. Each trait is designed to be modular and can be used independently or combined with others for maximum flexibility.

## Table of Contents

1. [Core Traits](#core-traits)
2. [Data Management Traits](#data-management-traits)
3. [UI/UX Traits](#uiux-traits)
4. [Performance Traits](#performance-traits)
5. [Integration Traits](#integration-traits)
6. [New Feature Traits](#new-feature-traits)

---

## Core Traits

### HasQueryBuilder

**Purpose**: Core query building functionality for database operations.

#### Properties
```php
protected $query;                    // Builder instance
protected $model;                    // Eloquent model
protected $baseQuery;               // Original query state
protected $queryModifiers = [];     // Applied query modifications
```

#### Methods
```php
// Initialize query builder
public function initializeQueryBuilder(Builder $query): self

// Add where conditions
public function addWhere(string $column, $operator = null, $value = null): self
public function addWhereIn(string $column, array $values): self
public function addWhereNotIn(string $column, array $values): self
public function addWhereBetween(string $column, array $values): self

// Add joins
public function addJoin(string $table, string $first, string $operator, string $second): self
public function addLeftJoin(string $table, string $first, string $operator, string $second): self

// Query execution
public function executeQuery(): Collection
public function getQuerySql(): string
public function resetQuery(): self

// Query optimization
public function optimizeQuery(): self
public function addQueryHint(string $hint): self
```

#### Usage Example
```php
$this->initializeQueryBuilder(User::query())
     ->addWhere('status', 'active')
     ->addWhereIn('role_id', [1, 2, 3])
     ->addJoin('profiles', 'users.id', '=', 'profiles.user_id')
     ->optimizeQuery();
```

---

### HasDataValidation

**Purpose**: Input validation and sanitization for all data operations.

#### Properties
```php
protected $validationRules = [];     // Validation rules
protected $validationMessages = []; // Custom error messages
protected $sanitizers = [];         // Data sanitizers
protected $validators = [];         // Custom validators
```

#### Methods
```php
// Validation setup
public function setValidationRules(array $rules): self
public function addValidationRule(string $field, $rule): self
public function setValidationMessages(array $messages): self

// Data validation
public function validate(array $data): bool
public function validateField(string $field, $value): bool
public function getValidationErrors(): array

// Data sanitization
public function sanitize(array $data): array
public function sanitizeField(string $field, $value)
public function addSanitizer(string $field, callable $sanitizer): self

// Security validation
public function validateSqlInjection(string $value): bool
public function validateXss(string $value): bool
public function validateFileUpload(array $file): bool

// Custom validators
public function addCustomValidator(string $name, callable $validator): self
public function registerValidators(): void
```

#### Usage Example
```php
$this->setValidationRules([
        'email' => 'required|email',
        'name' => 'required|string|max:255',
        'age' => 'integer|min:18|max:120'
    ])
    ->addSanitizer('name', fn($value) => trim(strip_tags($value)))
    ->validate($inputData);
```

---

## Data Management Traits

### HasColumnConfiguration

**Purpose**: Dynamic column setup and management.

#### Properties
```php
protected $columns = [];            // Column definitions
protected $hiddenColumns = [];     // Hidden column names
protected $columnOrder = [];       // Column display order
protected $columnWidths = [];      // Column width settings
protected $columnTypes = [];       // Column data types
```

#### Methods
```php
// Column setup
public function setColumns(array $columns): self
public function addColumn(string $name, array $config): self
public function removeColumn(string $name): self
public function reorderColumns(array $order): self

// Column configuration
public function setColumnWidth(string $column, string $width): self
public function setColumnType(string $column, string $type): self
public function setColumnFormat(string $column, string $format): self
public function setColumnAlign(string $column, string $align): self

// Column visibility
public function hideColumn(string $column): self
public function showColumn(string $column): self
public function toggleColumnVisibility(string $column): self
public function getVisibleColumns(): array

// Column metadata
public function getColumnConfig(string $column): array
public function getAllColumns(): array
public function getColumnType(string $column): string
public function isColumnSortable(string $column): bool
public function isColumnSearchable(string $column): bool
```

#### Usage Example
```php
$this->setColumns([
        'id' => ['type' => 'integer', 'sortable' => true, 'width' => '80px'],
        'name' => ['type' => 'string', 'searchable' => true, 'align' => 'left'],
        'email' => ['type' => 'email', 'searchable' => true],
        'created_at' => ['type' => 'datetime', 'format' => 'Y-m-d H:i:s']
    ])
    ->hideColumn('id')
    ->setColumnWidth('email', '250px');
```

---

### HasJsonSupport

**Purpose**: Advanced JSON column handling and extraction.

#### Properties
```php
protected $jsonColumns = [];        // JSON column definitions
protected $jsonPaths = [];         // JSON path extractors
protected $jsonIndexes = [];       // JSON field indexes
```

#### Methods
```php
// JSON column setup
public function addJsonColumn(string $column, array $paths): self
public function setJsonPaths(string $column, array $paths): self
public function addJsonPath(string $column, string $path, string $alias): self

// JSON data extraction
public function extractJsonValue(array $data, string $column, string $path)
public function extractAllJsonValues(array $data, string $column): array
public function searchJsonColumn(string $column, string $path, $value): self

// JSON querying
public function whereJson(string $column, string $path, $operator, $value): self
public function whereJsonContains(string $column, string $path, $value): self
public function whereJsonLength(string $column, string $path, $operator, $value): self

// JSON indexing
public function createJsonIndex(string $column, string $path): self
public function getJsonIndexes(): array
public function optimizeJsonQueries(): self
```

#### Usage Example
```php
$this->addJsonColumn('metadata', [
        'user.profile.name' => 'profile_name',
        'user.profile.avatar' => 'avatar_url',
        'user.preferences.theme' => 'theme'
    ])
    ->whereJson('metadata', '$.user.status', '=', 'active')
    ->searchJsonColumn('metadata', '$.user.profile.name', 'John');
```

---

## UI/UX Traits

### HasColumnVisibility

**Purpose**: Dynamic column show/hide functionality.

#### Properties
```php
protected $visibleColumns = [];     // Currently visible columns
protected $columnToggles = [];     // Column toggle states
protected $defaultVisibleColumns = []; // Default column visibility
protected $columnGroups = [];      // Grouped columns
```

#### Methods
```php
// Visibility management
public function setVisibleColumns(array $columns): self
public function addVisibleColumn(string $column): self
public function removeVisibleColumn(string $column): self
public function toggleColumn(string $column): self

// Column groups
public function createColumnGroup(string $name, array $columns): self
public function toggleColumnGroup(string $group): self
public function getColumnGroups(): array

// Persistence
public function saveColumnVisibility(): self
public function loadColumnVisibility(): self
public function resetColumnVisibility(): self

// Utilities
public function getHiddenColumns(): array
public function getColumnVisibilityState(): array
public function isColumnVisible(string $column): bool
public function canHideColumn(string $column): bool
```

#### Usage Example
```php
$this->createColumnGroup('personal', ['name', 'email', 'phone'])
    ->createColumnGroup('professional', ['company', 'position', 'salary'])
    ->toggleColumnGroup('personal')
    ->saveColumnVisibility();
```

---

### HasActions

**Purpose**: Row-level actions and selection management.

#### Properties
```php
protected $actions = [];            // Available actions
protected $selectedRows = [];      // Selected row IDs
protected $actionPermissions = [];  // Action permissions
protected $actionConfigs = [];     // Action configurations
```

#### Methods
```php
// Action management
public function addAction(string $name, array $config): self
public function removeAction(string $name): self
public function getActions(): array
public function canPerformAction(string $action, $row): bool

// Row selection
public function selectRow($id): self
public function deselectRow($id): self
public function selectAllRows(): self
public function clearActionSelection(): self  // Aliased method
public function toggleRowSelection($id): self

// Action execution
public function performAction(string $action, $rowId, array $params = []): mixed
public function performBulkAction(string $action, array $rowIds, array $params = []): mixed

// Action configuration
public function setActionPermission(string $action, callable $permission): self
public function setActionConfig(string $action, array $config): self

// Selection utilities
public function getSelectedRows(): array
public function getActionSelectedCount(): int  // Aliased method
public function isRowSelected($id): bool
public function hasSelectedRows(): bool
```

#### Usage Example
```php
$this->addAction('edit', [
        'label' => 'Edit',
        'icon' => 'pencil',
        'permission' => fn($row) => auth()->user()->can('update', $row)
    ])
    ->addAction('delete', [
        'label' => 'Delete',
        'icon' => 'trash',
        'confirm' => true,
        'permission' => fn($row) => auth()->user()->can('delete', $row)
    ]);
```

---

## Performance Traits

### HasCaching

**Purpose**: Advanced caching layer for performance optimization.

#### Properties
```php
protected $cacheConfig = [];        // Cache configuration
protected $cacheKeys = [];         // Generated cache keys
protected $cacheTags = [];         // Cache tags
protected $cacheTimeout = 3600;    // Default cache timeout
```

#### Methods
```php
// Cache configuration
public function setCacheConfig(array $config): self
public function setCacheTimeout(int $seconds): self
public function addCacheTag(string $tag): self
public function setCacheTags(array $tags): self

// Cache operations
public function cacheQuery(string $key, callable $callback, int $timeout = null)
public function invalidateCache(string $key = null): self
public function clearAllCache(): self
public function warmupCache(): self

// Cache keys
public function generateCacheKey(array $params): string
public function getCacheKey(): string
public function setCacheKey(string $key): self

// Cache statistics
public function getCacheStats(): array
public function getCacheHitRate(): float
public function isCacheEnabled(): bool

// Advanced caching
public function cacheWithTags(array $tags, callable $callback)
public function invalidateCacheByTag(string $tag): self
public function setCacheDriver(string $driver): self
```

#### Usage Example
```php
$this->setCacheConfig(['driver' => 'redis', 'prefix' => 'datatable'])
    ->setCacheTimeout(1800)
    ->addCacheTag('users')
    ->cacheQuery('user_list', function() {
        return $this->executeQuery();
    });
```

---

### HasMemoryManagement

**Purpose**: Memory optimization for large datasets.

#### Properties
```php
protected $memoryLimit = '256M';    // Memory limit
protected $memoryUsage = 0;        // Current memory usage
protected $memoryThresholds = [];  // Memory thresholds
protected $memoryOptimizations = []; // Optimization settings
```

#### Methods
```php
// Memory monitoring
public function getMemoryUsage(): int
public function getMemoryLimit(): int
public function getMemoryUsagePercent(): float
public function isMemoryLimitReached(): bool

// Memory optimization
public function optimizeMemory(): self
public function clearMemoryCache(): self
public function enableMemoryOptimization(): self
public function setMemoryLimit(string $limit): self

// Chunk processing
public function processInChunks(callable $callback, int $chunkSize = 1000): self
public function getOptimalChunkSize(): int
public function setChunkSize(int $size): self

// Memory-efficient operations
public function streamData(callable $callback): Generator
public function lazyLoad(callable $callback): LazyCollection
public function forceGarbageCollection(): self

// Memory alerts
public function setMemoryThreshold(float $percent, callable $callback): self
public function checkMemoryThresholds(): self
```

#### Usage Example
```php
$this->setMemoryLimit('512M')
    ->setMemoryThreshold(80, function() {
        $this->optimizeMemory();
    })
    ->processInChunks(function($chunk) {
        return $this->processData($chunk);
    }, 500);
```

---

## Integration Traits

### HasExport

**Purpose**: Comprehensive data export functionality.

#### Properties
```php
protected $exportFormats = [];     // Available export formats
protected $exportConfig = [];      // Export configuration
protected $exportFilters = [];     // Export-specific filters
protected $exportHeaders = [];     // Custom export headers
```

#### Methods
```php
// Export configuration
public function setExportFormats(array $formats): self
public function addExportFormat(string $format, array $config): self
public function setExportConfig(array $config): self
public function setExportHeaders(array $headers): self

// Export operations
public function export(string $format, array $options = []): mixed
public function exportToCsv(array $options = []): string
public function exportToExcel(array $options = []): mixed
public function exportToPdf(array $options = []): mixed
public function exportToJson(array $options = []): string

// Export data preparation
public function prepareExportData(): Collection
public function formatExportData(Collection $data, string $format): mixed
public function applyExportFilters(Collection $data): Collection

// Export utilities
public function getExportFormats(): array
public function canExport(string $format): bool
public function getExportFilename(string $format): string
public function downloadExport(mixed $export, string $filename): Response
```

#### Usage Example
```php
$this->setExportFormats(['csv', 'excel', 'pdf'])
    ->setExportHeaders(['ID', 'Name', 'Email', 'Created At'])
    ->export('excel', [
        'filename' => 'users_export.xlsx',
        'include_headers' => true,
        'date_format' => 'Y-m-d'
    ]);
```

---

### HasEventListeners

**Purpose**: Comprehensive event handling system.

#### Properties
```php
protected $eventListeners = [];    // Registered event listeners
protected $events = [];           // Event definitions
protected $eventQueue = [];       // Queued events
protected $eventConfig = [];      // Event configuration
```

#### Methods
```php
// Event registration
public function addEventListener(string $event, callable $listener): self
public function removeEventListener(string $event, callable $listener): self
public function onEvent(string $event, callable $listener): self

// Event dispatching
public function dispatchEvent(string $event, array $data = []): self
public function fireEvent(string $event, array $data = []): mixed
public function queueEvent(string $event, array $data = []): self

// Event management
public function getEventListeners(string $event = null): array
public function hasEventListeners(string $event): bool
public function clearEventListeners(string $event = null): self

// Built-in events
public function onDataLoaded(callable $callback): self
public function onRowSelected(callable $callback): self
public function onActionPerformed(callable $callback): self
public function onSearchPerformed(callable $callback): self
public function onFilterApplied(callable $callback): self

// Event utilities
public function enableEvents(): self
public function disableEvents(): self
public function pauseEvents(): self
public function resumeEvents(): self
```

#### Usage Example
```php
$this->onDataLoaded(function($data) {
        logger()->info('Data loaded', ['count' => $data->count()]);
    })
    ->onRowSelected(function($rowId) {
        $this->dispatchBrowserEvent('row-selected', ['id' => $rowId]);
    })
    ->addEventListener('custom_event', function($data) {
        // Custom event handling
    });
```

---

## New Feature Traits

### HasForEach

**Purpose**: Complete foreach iteration functionality with datatable features.

#### Properties
```php
protected $foreachData;            // Data collection for iteration
protected $foreachConfig = [];     // Foreach configuration
protected $foreachFilters = [];    // Active filters
protected $foreachSearch = '';     // Search term
protected $foreachPage = 1;        // Current page
protected $foreachSortField = '';  // Sort field
protected $foreachSortDirection = 'asc'; // Sort direction
```

#### Methods
```php
// Foreach setup
public function enableForeachMode(Collection $data, array $config = []): self
public function disableForeachMode(): self
public function setForeachConfig(array $config): self
public function addForeachData(Collection $data): self

// Data retrieval
public function getForeachData(): Collection
public function getFilteredForeachData(): Collection
public function getCurrentPageData(): Collection
public function getForeachPaginationData(): array

// Search and filtering
public function setForeachSearch(string $search): self
public function applyForeachSearch(Collection $data, string $search): Collection
public function setForeachFilters(array $filters): self
public function applyForeachFilters(Collection $data): Collection

// Sorting
public function setForeachSort(string $field, string $direction = 'asc'): self
public function applyForeachSort(Collection $data, string $field, string $direction): Collection

// Pagination
public function setForeachPage(int $page): self
public function nextForeachPage(): self
public function previousForeachPage(): self
public function setForeachPerPage(int $perPage): self

// Data processing
public function processForeachItem(array $item): array
public function transformForeachData(Collection $data): Collection
public function refreshForeachData(): self

// Utilities
public function getForeachItemCount(): int
public function hasMoreForeachPages(): bool
public function isForeachModeEnabled(): bool
```

#### Usage Example
```php
$collection = collect($this->getUsers());

$this->enableForeachMode($collection, [
        'per_page' => 10,
        'searchable_fields' => ['name', 'email'],
        'sortable_fields' => ['name', 'created_at'],
        'filterable_fields' => ['status', 'role']
    ])
    ->setForeachSearch('john')
    ->setForeachFilters(['status' => 'active'])
    ->setForeachSort('name', 'asc');
```

---

### HasBulkActions

**Purpose**: Comprehensive bulk operations on selected rows.

#### Properties
```php
protected $bulkActions = [];       // Available bulk actions
protected $selectedForBulk = [];   // Selected items for bulk operations
protected $bulkActionConfig = [];  // Bulk action configuration
protected $bulkActionResults = []; // Results of bulk operations
```

#### Methods
```php
// Bulk action management
public function addBulkAction(string $name, array $config): self
public function removeBulkAction(string $name): self
public function getBulkActions(): array
public function canPerformBulkAction(string $action): bool

// Selection management
public function toggleRowSelection($id): self
public function selectAllForBulk(): self
public function clearBulkSelection(): self  // Aliased method
public function selectRowsRange(array $ids): self

// Bulk operations
public function performBulkAction(string $action, array $params = []): array
public function performBulkDelete(): array
public function performBulkUpdate(array $data): array
public function performBulkExport(string $format): mixed

// Configuration
public function setBulkActionConfig(string $action, array $config): self
public function setBulkSelectionLimit(int $limit): self
public function enableBulkActionConfirmation(string $action): self

// Utilities
public function getSelectedForBulk(): array
public function getBulkSelectedCount(): int
public function hasBulkSelection(): bool
public function getBulkActionResults(): array
public function clearBulkActionResults(): self
```

#### Usage Example
```php
$this->addBulkAction('delete', [
        'label' => 'Delete Selected',
        'icon' => 'trash',
        'confirm' => true,
        'permission' => 'delete_users'
    ])
    ->addBulkAction('export', [
        'label' => 'Export Selected',
        'icon' => 'download',
        'formats' => ['csv', 'excel']
    ])
    ->setBulkSelectionLimit(100);
```

---

### HasAdvancedFiltering

**Purpose**: Advanced filter operators and complex filtering logic.

#### Properties
```php
protected $advancedFilters = [];   // Advanced filter definitions
protected $filterOperators = [];   // Available filter operators
protected $filterGroups = [];     // Filter groups
protected $filterConditions = []; // Complex filter conditions
```

#### Methods
```php
// Filter setup
public function addAdvancedFilter(string $field, array $config): self
public function setFilterOperators(array $operators): self
public function createFilterGroup(string $name, array $filters): self
public function addFilterCondition(string $condition, array $params): self

// Filter operators
public function addFilterOperator(string $name, callable $callback): self
public function getFilterOperators(): array
public function applyFilterOperator(string $operator, $value, $fieldValue): bool

// Advanced filtering
public function applyAdvancedFilters(Collection $data): Collection
public function buildFilterQuery(): array
public function addRangeFilter(string $field, $min, $max): self
public function addDateRangeFilter(string $field, string $start, string $end): self

// Filter groups
public function applyFilterGroup(string $group, Collection $data): Collection
public function toggleFilterGroup(string $group): self
public function getActiveFilterGroups(): array

// Filter utilities
public function getAdvancedFilters(): array
public function clearAdvancedFilters(): self
public function saveFilterPreset(string $name): self
public function loadFilterPreset(string $name): self
public function getFilterPresets(): array
```

#### Usage Example
```php
$this->addAdvancedFilter('price', [
        'type' => 'range',
        'operators' => ['between', 'gt', 'lt', 'gte', 'lte'],
        'data_type' => 'numeric'
    ])
    ->addAdvancedFilter('created_at', [
        'type' => 'date_range',
        'operators' => ['between', 'before', 'after'],
        'data_type' => 'date'
    ])
    ->createFilterGroup('price_filters', ['price', 'discount'])
    ->addDateRangeFilter('created_at', '2024-01-01', '2024-12-31');
```

---

## Trait Combination Examples

### Complete E-commerce Product Listing

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class ProductListing extends Component
{
    use DatatableTrait;
    
    public function mount()
    {
        // Initialize with all features
        $this->initializeQueryBuilder(Product::with(['category', 'brand']))
            ->setColumns([
                'name' => ['searchable' => true, 'sortable' => true],
                'price' => ['sortable' => true, 'type' => 'currency'],
                'category.name' => ['searchable' => true, 'label' => 'Category'],
                'stock' => ['sortable' => true, 'type' => 'integer'],
                'status' => ['filterable' => true]
            ])
            ->addAction('edit', ['icon' => 'edit', 'permission' => 'edit_products'])
            ->addAction('delete', ['icon' => 'trash', 'confirm' => true])
            ->addBulkAction('bulk_delete', ['label' => 'Delete Selected'])
            ->addBulkAction('bulk_export', ['label' => 'Export Selected'])
            ->addAdvancedFilter('price', ['type' => 'range', 'data_type' => 'numeric'])
            ->addAdvancedFilter('created_at', ['type' => 'date_range'])
            ->setExportFormats(['csv', 'excel', 'pdf'])
            ->setCacheTimeout(1800)
            ->enableMemoryOptimization();
    }
    
    public function render()
    {
        return view('livewire.product-listing');
    }
}
```

---

## Migration Guide

### Upgrading from Single Component to Traits

```php
// Before (single component)
class OldDatatable extends Component
{
    public $search = '';
    public $sortField = 'id';
    // ... all methods in one class
}

// After (trait-based)
class NewDatatable extends Component
{
    use DatatableTrait;
    // Automatically includes all 21 traits with resolved conflicts
    
    public function mount()
    {
        // Configuration instead of manual implementation
        $this->setColumns($columns)
            ->enableForeachMode($data)
            ->addBulkAction('delete', $config);
    }
}
```

### Trait Conflict Resolution

When using multiple traits, method conflicts are automatically resolved:

```php
// Conflict resolution is handled automatically
use HasActions {
    clearSelection as clearActionSelection;
    getSelectedCount as getActionSelectedCount;
}
use HasBulkActions {
    clearSelection as clearBulkSelection;
}

// Unified methods available
public function clearSelection()
{
    $this->clearActionSelection();
    $this->clearBulkSelection();
}

public function getSelectedCount()
{
    return $this->getActionSelectedCount();
}
```

---

## Performance Guidelines

1. **Memory Management**: Use `HasMemoryManagement` for large datasets
2. **Caching**: Implement `HasCaching` for frequently accessed data
3. **Chunking**: Process large datasets in chunks using memory-efficient methods
4. **Eager Loading**: Use `HasEagerLoading` to prevent N+1 queries
5. **Query Optimization**: Leverage `HasQueryBuilder` optimization methods

---

## Security Considerations

1. **Input Validation**: Always use `HasDataValidation` for user inputs
2. **SQL Injection**: Built-in protection in `HasQueryBuilder`
3. **XSS Prevention**: Automatic sanitization in data validation
4. **Permission Checks**: Implement proper authorization in actions and bulk actions
5. **Export Security**: Validate export permissions and data access

---

## Conclusion

This API reference provides comprehensive documentation for all traits in the AF Table package. Each trait is designed to be modular, performant, and secure while providing maximum flexibility for different use cases.

For implementation examples and advanced usage patterns, refer to the specific trait guides and the main package documentation.
