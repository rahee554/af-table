# 🔧 ArtFlow Table - AI Technical Reference

> **For AI Agents & Developers: Complete technical architecture and implementation guide**

---

## 📋 Package Overview

| Property | Value |
|----------|-------|
| **Package** | `artflow-studio/table` |
| **Type** | Livewire Component (Trait-Based) |
| **Version** | 1.5.2 |
| **Developer Site** | https://artflow.pk |
| **Location** | `vendor/artflow-studio/table/src/` |
| **Main Component** | `DatatableTrait` (Livewire Component) |
| **Blade Directive** | `@livewire('aftable', [...])` |
| **Registration** | `TableServiceProvider::class` |
| **Auto-Discovery** | ✅ Yes (Laravel 5.5+) |

---

## 🏗️ Architecture Overview

### Component Stack

```
@livewire('aftable', [...])
    ↓
DatatableTrait.php (Main Livewire Component)
    ├── Uses 18 Core Traits
    ├── Mounted Properties
    ├── Reactive Methods
    └── Blade Rendering
        ↓
    aftable.blade.php (Template)
    ├── Search Box
    ├── Column Headers
    ├── Table Rows
    ├── Pagination
    └── Export/Filters
```

### Trait Organization

```
app/Http/Livewire/
└── DatatableTrait.php (Main Component)

vendor/artflow-studio/table/src/Traits/
├── Core/
│   ├── HasDataProcessing.php        # Data transformation
│   ├── HasQueryBuilding.php         # Query construction
│   ├── HasPagination.php            # Pagination logic
│   ├── HasSearch.php                # Search functionality
│   ├── HasSorting.php               # Sort handling
│   ├── HasAutoOptimization.php      # Auto-detection
│   ├── HasCountAggregations.php     # N+1 prevention
│   ├── HasRelationships.php         # Relation handling
│   ├── HasColumnValidation.php      # Column checking
│   ├── HasColumnInitialization.php  # Column setup
│   └── HasUtilities.php             # Helper methods
│
├── UI/
│   ├── HasSortingUI.php             # Sort UI elements
│   ├── HasColumnVisibility.php      # Show/hide columns
│   ├── HasActions.php               # Action buttons
│   └── HasExport.php                # Export features
│
└── Advanced/
    ├── HasAdvancedFiltering.php     # Complex filters
    ├── HasPerformanceOptimization.php # Cache handling
    └── HasSessionManagement.php     # Session isolation
```

---

## 📝 Usage Pattern

### Blade Integration

```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        ['key' => 'subitems_count', 'label' => 'Sub-items'],
    ],
    'records' => 50,
])
```

### What Happens Behind the Scenes

1. **Component Mount**
   ```
   mount() called
   → initializeComponent()
   → initializeColumns()
   → setDefaultSort()
   ```

2. **Data Initialization**
   ```
   autoOptimizeColumns()
   → Detects relations
   → Detects count columns
   → Enables sorting/searching
   
   autoDetectCountAggregations()
   → Finds _count columns
   → Extracts relation names
   → Queues for withCount()
   ```

3. **Query Building**
   ```
   buildUnifiedQuery()
   → applyEagerLoading()
   → applyCountAggregations()
   → applySearch()
   → applySorting()
   → paginate()
   ```

4. **Rendering**
   ```
   render()
   → Load aftable.blade.php
   → Pass $records, $columns, etc.
   → Display to user
   ```

---

## 🔑 Key Traits Explained

### 1. **HasAutoOptimization** (Core Innovation)
**Purpose:** Automatically detect and configure columns

```php
// What it does:
protected function autoOptimizeColumns(): void
{
    foreach ($this->columns as &$column) {
        // Detect if it's a relation column
        if (isset($column['relation'])) {
            $relation = extractRelationName($column['relation']);
            $this->relationsToLoad[] = $relation;
            $column['searchable'] = true;  // Relations are searchable
            $column['sortable'] = true;
        }
        
        // Detect if it's a count column
        if (str_ends_with($column['key'], '_count')) {
            $relation = str_replace('_count', '', $column['key']);
            $this->countAggregations[$relation] = $relation;
            $column['sortable'] = true;  // Counts are sortable
        }
        
        // Default text columns are searchable
        if (isTextColumn($column)) {
            $column['searchable'] = true;
        }
    }
}
```

**Result:** All optimization decisions made automatically - users don't need to configure anything!

### 2. **HasCountAggregations** (N+1 Prevention)
**Purpose:** Prevent N+1 queries when showing relationship counts

```php
// What it does:
protected function autoDetectCountAggregations(): void
{
    foreach ($this->columns as $column) {
        if (str_ends_with($column['key'], '_count')) {
            $relation = str_replace('_count', '', $column['key']);
            
            // Verify relation exists on model
            if (hasRelation($this->model, $relation)) {
                $this->countAggregations[$relation] = true;
            }
        }
    }
}

protected function applyCountAggregations(Builder $query): Builder
{
    if (!empty($this->countAggregations)) {
        return $query->withCount(array_keys($this->countAggregations));
    }
    return $query;
}
```

**Result:** Single query loads all counts! No N+1!

### 3. **HasSortingUI** (User Experience)
**Purpose:** Display sort indicators and manage sort state

```php
// What it does:
public function getSortIcon(string $columnKey): string
{
    if ($this->sortBy === $columnKey) {
        return $this->sortDirection === 'asc' ? '↑' : '↓';
    }
    return ''; // No icon if not sorted
}

public function updateSort(string $columnKey): void
{
    if ($this->sortBy === $columnKey) {
        // Toggle direction
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        // New sort column
        $this->sortBy = $columnKey;
        $this->sortDirection = 'asc';
    }
    $this->resetPage();
}
```

**Result:** Beautiful sort UI with up/down arrows that users understand!

### 4. **HasQueryBuilding** (Optimization)
**Purpose:** Build optimized Eloquent query

```php
// What it does:
protected function buildUnifiedQuery(): Builder
{
    $query = $this->model::query();
    
    // Step 1: Eager load relations (prevent N+1)
    foreach ($this->relationsToLoad as $relation) {
        $query->with($relation);
    }
    
    // Step 2: Add count aggregations (show counts efficiently)
    $query = $this->applyCountAggregations($query);
    
    // Step 3: Apply search filters
    $query = $this->applySearch($query);
    
    // Step 4: Apply sorting
    $query = $this->applySorting($query);
    
    // Step 5: Paginate results
    return $query;
}
```

**Result:** Single optimized query that handles everything!

### 5. **HasSearch** (Searching)
**Purpose:** Search across multiple columns efficiently

```php
// What it does:
protected function applySearch(Builder $query): Builder
{
    if (empty($this->search)) {
        return $query;
    }
    
    return $query->where(function ($q) {
        foreach ($this->searchableColumns as $column) {
            if ($column['type'] === 'relation') {
                // Search in related table
                $relation = extractRelationName($column['relation']);
                $field = extractColumnName($column['relation']);
                $q->orWhereHas($relation, fn($sub) => 
                    $sub->where($field, 'like', "%{$this->search}%")
                );
            } else {
                // Search in main table
                $q->orWhere($column['key'], 'like', "%{$this->search}%");
            }
        }
    });
}
```

**Result:** Smart search that works on all column types!

---

## 🔄 Data Flow Diagram

```
USER INTERACTION
       ↓
┌──────────────────────────────────┐
│ User Action (sort/search/page)   │
├──────────────────────────────────┤
│ - Click column header → sortBy()  │
│ - Type search → search()          │
│ - Click page → gotoPage()         │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│ Livewire Reactivity              │
├──────────────────────────────────┤
│ wire:click, wire:model, etc.     │
│ Triggers component method         │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│ Component Method Executes         │
├──────────────────────────────────┤
│ sortBy = 'name'                  │
│ sortDirection = 'asc'            │
│ resetPage() → page = 1           │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│ Livewire Renders                  │
├──────────────────────────────────┤
│ render() method called            │
│ Blade template updated            │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│ Build Query (OPTIMIZE STEP)      │
├──────────────────────────────────┤
│ 1. with(relations)        [eager]│
│ 2. withCount(counts)      [N+1]  │
│ 3. where(search)          [find] │
│ 4. orderBy(sort)          [order]│
│ 5. paginate()             [page] │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│ Execute Single Query             │
├──────────────────────────────────┤
│ SELECT ... FROM products         │
│   with category, brand           │
│   withCount variants, reviews    │
│   WHERE name LIKE '%search%'     │
│   ORDER BY created_at ASC        │
│   LIMIT 50                       │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│ Render Table                      │
├──────────────────────────────────┤
│ Display sorted/filtered results   │
│ Show page numbers                │
│ Highlight current sort column    │
└──────────────────────────────────┘
```

---

## 🎯 Column Configuration Format

### Minimal (Most Common)
```php
['key' => 'title', 'label' => 'Item Name']
```
- Auto-sorted: ✅
- Auto-searched: ✅
- Displayed: ✅

### With Relationship
```php
['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name']
```
- Auto-eager loads `category` relation
- Searches in `category.name`
- Sorts by `category.name`
- No N+1 query!

### With Count
```php
['key' => 'subitems_count', 'label' => 'Sub-items']
```
- Auto-detected count column
- Uses `withCount('subitems')`
- Shows count without loading items
- Single query!

### With Actions
```php
[
    'key' => 'actions',
    'label' => 'Actions',
    'actions' => [
        ['type' => 'button', 'label' => 'Edit', 'href' => '/items/{id}/edit'],
        ['type' => 'button', 'label' => 'Delete', 'href' => '/items/{id}', 'method' => 'DELETE'],
    ]
]
```

### Advanced Options
```php
[
    'key' => 'amount',
    'label' => 'Amount',
    'sortable' => true,        # Allow sorting (auto-enabled)
    'searchable' => true,      # Allow searching (auto-enabled)
    'hidden' => false,         # Show/hide column
    'value_type' => 'price',   # Format: price, date, boolean, etc.
    'class' => 'text-right',   # Cell CSS classes
    'width' => '120px',        # Column width
    'raw' => false,            # Escape HTML (false = escape)
]
```

---

## ⚡ Performance Optimizations

### 1. **Eager Loading** (Prevents N+1 Query on Relations)

**Before (N+1 Problem):**
```
Initial Query: SELECT * FROM items → 50 rows
For Each Row: SELECT * FROM categories WHERE id = ? → 50 queries!
TOTAL: 51 queries! ❌
```

**After (Eager Loading):**
```
Query: SELECT * FROM items WITH categories → 1 query ✅
Result: Items have categories pre-loaded
TOTAL: 1 query! ✅
```

**Code:**
```php
// Automatically done:
$query->with(['category', 'department', 'supplier']);
```

### 2. **Count Aggregations** (Prevents N+1 Query on Counts)

**Before (N+1 Problem):**
```
Initial Query: SELECT * FROM items → 50 rows
For Each Row: SELECT COUNT(*) FROM subitems WHERE item_id = ? → 50 queries!
TOTAL: 51 queries! ❌
```

**After (withCount):**
```
Query: SELECT * FROM items, COUNT(*) as subitems_count → 1 query ✅
Result: Each item has subitems_count pre-calculated
TOTAL: 1 query! ✅
```

**Code:**
```php
// Automatically done:
$query->withCount(['subitems', 'related', 'attachments']);
```

### 3. **Chunked Export** (Prevents Memory Issues)

**Code:**
```php
// Export processes in chunks:
$query->chunk(500, function($records) {
    foreach ($records as $record) {
        // Process each record
        $this->exportRow($record);
    }
});
// Handles 1M+ records without memory crash ✅
```

### 4. **Session Isolation** (Prevents Cross-User Data Leaks)

**Code:**
```php
// Each session stores its own state:
$sessionKey = 'aftable_' . md5($this->componentId);
session([$sessionKey => $this->state]);

// Other users can't see another user's table state
```

---

## 🧪 Testing Guide

### Test Auto-Optimization
```php
// Test that relations are auto-detected
public function testAutoDetectsRelations()
{
    $component = Livewire::test(DatatableTrait::class, [
        'model' => 'App\Models\Product',
        'columns' => [
            ['key' => 'category_name', 'relation' => 'category:name'],
        ],
    ]);
    
    $this->assertContains('category', $component->instance()->relationsToLoad);
}
```

### Test Count Aggregations
```php
// Test that _count columns work
public function testCountAggregations()
{
    $component = Livewire::test(DatatableTrait::class, [
        'model' => 'App\Models\Product',
        'columns' => [
            ['key' => 'variants_count', 'label' => 'Variants'],
        ],
    ]);
    
    // Query should include withCount
    $this->assertStringContainsString('count', $component->instance()->buildUnifiedQuery()->toSql());
}
```

### Test Search
```php
// Test search filtering
public function testSearch()
{
    $component = Livewire::test(DatatableTrait::class, [
        'model' => 'App\Models\Product',
        'columns' => [
            ['key' => 'name', 'label' => 'Name'],
        ],
    ]);
    
    $component->set('search', 'Test Product');
    $results = $component->instance()->records;
    
    $this->assertTrue($results->every(fn($r) => str_contains($r->name, 'Test Product')));
}
```

### Test Sorting
```php
// Test sort functionality
public function testSorting()
{
    $component = Livewire::test(DatatableTrait::class, [
        'model' => 'App\Models\Product',
        'columns' => [
            ['key' => 'name', 'label' => 'Name'],
        ],
    ]);
    
    $component->call('updateSort', 'name');
    
    $this->assertEquals('name', $component->instance()->sortBy);
    $this->assertEquals('asc', $component->instance()->sortDirection);
}
```

---

## 🐛 Debugging Tips

### 1. **Check Database Queries**
```php
// Enable query logging in tinker:
DB::enableQueryLog();

// Then render component...

// Check queries:
echo DB::getQueryLog();
```

**Expected Output:**
```
Query 1: SELECT * FROM products WITH category, brand, supplier withCount(variants)...
Total: 1 query ✅
```

### 2. **Check Auto-Optimization**
```php
// In tinker:
$component = app(DatatableTrait::class);
$component->model = 'App\Models\Product';
$component->columns = [
    ['key' => 'category_name', 'relation' => 'category:name'],
    ['key' => 'variants_count', 'label' => 'Variants'],
];

$component->autoOptimizeColumns();

dd([
    'relationsToLoad' => $component->relationsToLoad,
    'countAggregations' => $component->countAggregations,
    'columns' => $component->columns,
]);
```

### 3. **Check Blade Variables**
```blade
<!-- In aftable.blade.php template -->
<pre>{{ print_r($columns, true) }}</pre>
<pre>{{ print_r($records, true) }}</pre>
<pre>{{ "Queries: " . DB::getQueryLog() }}</pre>
```

---

## 🔌 Extending the Component

### Add Custom Column Type
```php
// In your component or trait:
protected function formatColumnValue($value, $column)
{
    return match($column['value_type'] ?? null) {
        'price' => '$' . number_format($value, 2),
        'date' => \Carbon\Carbon::parse($value)->format('M d, Y'),
        'boolean' => $value ? '✓' : '✗',
        'custom' => $this->customFormat($value, $column),
        default => $value,
    };
}
```

### Add Custom Filter
```php
// In your component:
public function addCustomFilter(string $key, \Closure $callback)
{
    $this->customFilters[$key] = $callback;
}

// Then in query building:
protected function applyCustomFilters(Builder $query): Builder
{
    foreach ($this->customFilters as $callback) {
        $query = $callback($query);
    }
    return $query;
}
```

### Add Custom Action
```php
// In column config:
['key' => 'custom_action', 'raw' => 'handleCustomAction()']

// Add method:
public function handleCustomAction()
{
    // Your logic here
}
```

---

## 📊 Trait Dependencies

```
DatatableTrait.php
├── HasDataProcessing
├── HasQueryBuilding
│   ├── HasSearch
│   ├── HasSorting
│   ├── HasPagination
│   ├── HasAutoOptimization
│   ├── HasCountAggregations
│   └── HasRelationships
├── HasColumnInitialization
├── HasColumnValidation
├── HasUtilities
├── HasSortingUI
├── HasColumnVisibility
├── HasActions
├── HasExport
├── HasAdvancedFiltering
├── HasPerformanceOptimization
└── HasSessionManagement
```

**Order Matters:** Traits are loaded in dependency order to avoid conflicts!

---

## 🚨 Critical Rules for AI

### ✅ DO
- Use in Blade views only: `@livewire('aftable', [...])`
- Pass Eloquent model: `'model' => 'App\Models\Product'`
- Use `'relation' => 'relationName:columnName'` format
- Use `columnName_count` for count aggregations
- Test with actual data before deploying
- Check database query count (should be 1!)

### ❌ DON'T
- Don't instantiate component directly in PHP
- Don't put complex logic in column configuration
- Don't mix automatic and manual sorting config
- Don't use raw HTML without escaping
- Don't load 50+ columns (performance)
- Don't assume eager loading without using 'relation'
- Don't ignore N+1 query warnings

---

## 📚 File Structure Reference

```
vendor/artflow-studio/table/
├── src/
│   ├── Http/
│   │   └── Livewire/
│   │       └── DatatableTrait.php         [Main Component]
│   ├── Traits/
│   │   ├── Core/
│   │   │   ├── HasDataProcessing.php
│   │   │   ├── HasQueryBuilding.php
│   │   │   ├── HasPagination.php
│   │   │   ├── HasSearch.php
│   │   │   ├── HasSorting.php
│   │   │   ├── HasAutoOptimization.php
│   │   │   ├── HasCountAggregations.php
│   │   │   ├── HasRelationships.php
│   │   │   ├── HasColumnValidation.php
│   │   │   ├── HasColumnInitialization.php
│   │   │   └── HasUtilities.php
│   │   ├── UI/
│   │   │   ├── HasSortingUI.php
│   │   │   ├── HasColumnVisibility.php
│   │   │   ├── HasActions.php
│   │   │   └── HasExport.php
│   │   └── Advanced/
│   │       ├── HasAdvancedFiltering.php
│   │       ├── HasPerformanceOptimization.php
│   │       └── HasSessionManagement.php
│   ├── resources/
│   │   └── views/
│   │       └── aftable.blade.php          [Template]
│   └── Providers/
│       └── TableServiceProvider.php        [Registration]
├── composer.json
├── README.md
└── DOCUMENTATION_FILES (guides, references, etc.)
```

---

## 🎓 Learning Path for AI

1. **Start Here:** Read `AI_USAGE_GUIDE.md` (non-technical)
2. **Understand Structure:** Read this file's "Architecture" section
3. **Study Examples:** Look at "Real-World Examples" in usage guide
4. **Understand Traits:** Read each trait's documentation in source code
5. **Test Query:** Use tinker to test query building
6. **Debug Issues:** Use debugging tips in this guide
7. **Extend:** Add custom filters/actions as needed

---

## 🔗 Integration Examples

### With Laravel Policy (Authorization)
```blade
<!-- Only show if user can edit -->
@if(auth()->user()->can('edit', \App\Models\Product::class))
    @livewire('aftable', [...])
@endif
```

### With Soft Deletes
```blade
@livewire('aftable', [
    'model' => 'App\Models\Product',
    'customQuery' => Product::withTrashed(),  # Include deleted
])
```

### With Scopes
```blade
@livewire('aftable', [
    'model' => 'App\Models\Product',
    'customQuery' => Product::active(),  # Custom scope
])
```

### With Tenant/Multi-Tenant
```blade
@livewire('aftable', [
    'model' => 'App\Models\Product',
    'customQuery' => Product::whereTenantId(auth()->user()->tenant_id),
])
```

---

**Version:** v1.6 Optimized  
**Last Updated:** November 23, 2025  
**For:** AI Agents, Developers, Architects  
**Status:** Production Ready ✅
