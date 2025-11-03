# ArtflowStudio Laravel Livewire Datatable Package - AI Agent Documentation

## 📋 Package Overview

**Package Name**: `artflowstudio/table`  
**Type**: Laravel Livewire Datatable Component  
**Architecture**: Trait-based modular system  
**Primary Component**: `DatatableTrait` (Livewire Component)  
**Blade Directive**: `@livewire('aftable', [...])`

### Purpose
This package provides a powerful, trait-based datatable component for Laravel Livewire applications. It handles complex data display, filtering, searching, sorting, pagination, and export functionality with minimal configuration.

---

## ⚠️ CRITICAL USAGE RULES FOR AI AGENTS

### 1. **ALWAYS USE IN BLADE VIEWS - NEVER IN COMPONENTS DIRECTLY**

❌ **WRONG - DO NOT DO THIS:**
```php
// In a Livewire component class
class MyComponent extends Component
{
    public function render()
    {
        // DON'T use @livewire or component logic here
        return view('my-view');
    }
}
```

✅ **CORRECT - DO THIS:**
```blade
<!-- In a Blade view file (e.g., resources/views/users/index.blade.php) -->
@livewire('aftable', [
    'model' => '\App\Models\User',
    'columns' => [...],
    'filters' => [...],
    // ... other options
])
```

### 2. **Component Name**
- Always use: `@livewire('aftable', [...])`
- Alternative (deprecated): `@AFtableTrait([...])`
- The component name is `'aftable'` (registered in `TableServiceProvider`)

### 3. **Direct View Rendering Only**
This component is designed to be embedded directly in Blade views, NOT instantiated or called from within other Livewire components.

---

## 🏗️ Core Architecture

### Main Component: `DatatableTrait`
Location: `vendor/artflow-studio/table/src/Http/Livewire/DatatableTrait.php`

The component uses **18+ traits** organized in three categories:

#### **Core Traits (Essential Functionality)**
1. `HasUnifiedSearch` - Global and column-specific search
2. `HasUnifiedValidation` - Input validation and sanitization
3. `HasTemplateRendering` - Cell value rendering with templates
4. `HasActionHandling` - Row and bulk actions
5. `HasBasicFeatures` - Core datatable features
6. `HasDataValidation` - Data type validation
7. `HasSorting` - Column sorting with relations
8. `HasUnifiedCaching` - Intelligent caching system
9. `HasRelationships` - Eloquent relationship handling
10. `HasJsonSupport` - JSON column extraction
11. `HasJsonFile` - JSON file operations
12. `HasColumnManagement` - Column configuration
13. `HasQueryBuilding` - Query construction

#### **UI Traits (User Interaction)**
14. `HasColumnVisibility` - Dynamic column show/hide
15. `HasEventListeners` - Livewire event system

#### **Advanced Traits (Performance & Export)**
16. `HasApiEndpoint` - API endpoint generation
17. `HasPerformanceMonitoring` - Performance tracking
18. `HasQueryOptimization` - Query performance optimization
19. `HasQueryStringSupport` - URL-based state sharing
20. `HasSessionManagement` - State persistence
21. `HasUnifiedOptimization` - Unified optimization strategies
22. `HasUtilities` - Helper methods
23. `HasExportFeatures` - CSV/Excel export

---

## 📝 Configuration Parameters

### Required Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `model` | string | **YES** | Fully qualified model class name (e.g., `'\App\Models\User'`) |
| `columns` | array | **YES** | Column definitions (see Column Configuration below) |

### Optional Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `filters` | array | `[]` | Filter definitions for columns |
| `actions` | array | `[]` | Row-level action buttons |
| `query` | array | `null` | Custom query constraints (WHERE conditions) |
| `records` | int | `10` | Records per page |
| `tableId` | string | auto | Unique table identifier |
| `dateColumn` | string | `null` | Column for date range filtering |
| `printable` | bool | `false` | Enable print functionality |
| `exportable` | bool | `false` | Enable export functionality |
| `searchable` | bool | `true` | Enable search functionality |
| `sort` | string | `'desc'` | Default sort direction (`'asc'` or `'desc'`) |
| `sortColumn` | string | `null` | Default sort column |
| `colvisBtn` | bool | `true` | Show column visibility button |
| `colSort` | bool | `true` | Enable column sorting |
| `refreshBtn` | bool | `false` | Show refresh button |
| `checkbox` | bool | `false` | Enable row checkboxes for bulk actions |
| `index` | bool | `false` | Show index/row number column |

---

## 🗂️ Column Configuration

### Column Structure

Each column is defined as an array with the following properties:

```php
'columns' => [
    'column_key' => [
        'key' => 'database_column',        // Database column name (optional if using 'function')
        'label' => 'Display Label',        // Column header text
        'th_class' => 'css-classes',       // Header cell CSS classes
        'td_class' => 'css-classes',       // Data cell CSS classes
        'relation' => 'relationName:attribute', // For relationship columns
        'json' => 'path.to.value',         // For JSON column extraction
        'raw' => '<span>{{$row->name}}</span>', // Raw HTML template
        'function' => 'methodName',        // Model method name
        'searchable' => true,              // Enable search (default: true)
        'sortable' => true,                // Enable sorting (default: true)
        'exportable' => true,              // Include in export (default: true)
        'hide' => false,                   // Initially hide column (default: false)
    ]
]
```

### Column Types

#### 1. **Database Column (Simple)**
```php
'name' => [
    'key' => 'name',
    'label' => 'Full Name',
]
```

#### 2. **Relationship Column**
```php
'user_name' => [
    'key' => 'user_name',
    'label' => 'User',
    'relation' => 'user:name', // relationName:attribute
    'searchable' => true,
]
```

#### 3. **Nested Relationship Column**
```php
'company_address' => [
    'key' => 'company_address',
    'label' => 'Company Address',
    'relation' => 'user.company:address', // nested.relation:attribute
]
```

#### 4. **JSON Column**
```php
'user_email' => [
    'key' => 'data',              // Database column containing JSON
    'label' => 'Email',
    'json' => 'contact.email',    // JSON path (dot notation)
]
```

**Important JSON Notes:**
- `key`: The actual database column name containing JSON data
- `json`: The path within the JSON to extract (supports nested paths like `preferences.theme`)
- JSON columns are automatically searchable if the database supports JSON operations

#### 5. **Function/Computed Column**
```php
'full_name' => [
    'label' => 'Full Name',
    'function' => 'getFullNameAttribute', // Model method or accessor
    'searchable' => false,  // Function columns cannot be searched in DB
    'sortable' => false,    // Function columns cannot be sorted
]
```

**Critical Rule for Function Columns:**
- Do NOT include a `key` parameter for function-based columns
- They cannot be searched or sorted (set both to `false`)
- The `function` value should match a model method or accessor

#### 6. **Raw HTML Template Column**
```php
'status_badge' => [
    'key' => 'status',
    'label' => 'Status',
    'raw' => '<span class="badge badge-{{ $row->status }}">{{ $row->status }}</span>',
]
```

**Raw Template Variables:**
- `$row`: Access to the current row model instance
- Can use any Blade syntax
- Supports conditionals, loops, and Laravel helpers

---

## 🔍 Filter Configuration

Filters allow users to narrow down data by specific column values.

### Filter Structure

```php
'filters' => [
    'column_name' => [
        'type' => 'text',              // Filter input type
        'relation' => 'relation:attr', // Optional: for relationship columns
    ]
]
```

### Filter Types

| Type | Description | Input |
|------|-------------|-------|
| `text` | Text search (requires 3+ chars) | Text input |
| `number` | Numeric comparison | Number input with operators |
| `integer` | Integer comparison | Number input with operators |
| `date` | Date comparison | Date picker with operators |
| `select` | Dropdown selection | Select dropdown |
| `distinct` | Unique values dropdown | Auto-populated select |

### Filter Examples

#### Text Filter
```php
'filters' => [
    'name' => [
        'type' => 'text',
    ]
]
```

#### Number Filter (with operators)
```php
'filters' => [
    'age' => [
        'type' => 'number',
    ]
]
```
Supports operators: `=`, `!=`, `<`, `>`, `<=`, `>=`

#### Distinct Filter (Auto-populated dropdown)
```php
'filters' => [
    'status' => [
        'type' => 'distinct', // Automatically fetches unique values
    ]
]
```

#### Relation Filter
```php
'filters' => [
    'user_id' => [
        'type' => 'distinct',
        'relation' => 'user:name', // Show user names, filter by user_id
    ]
]
```

**Important Filter Rules:**
- Function columns **CANNOT** be filtered
- Only database columns can have filters
- Text filters require minimum 3 characters
- Distinct filters automatically query unique values from database

---

## 🔗 Query Constraints

Apply custom WHERE conditions to the base query before table operations.

### Query Structure

```php
'query' => [
    ['column', 'value'],           // Equals condition
    ['column', '!=', 'value'],     // Not equals
    ['column', '>', 100],          // Greater than
    ['column', 'LIKE', '%search%'], // Like condition
]
```

### Query Examples

```php
'query' => [
    ['status', 'active'],              // WHERE status = 'active'
    ['age', '>=', 18],                 // AND age >= 18
    ['role', '!=', 'guest'],           // AND role != 'guest'
]
```

**Query Rules:**
- Applied BEFORE any search/filter/sort operations
- Use valid SQL operators: `=`, `!=`, `<`, `>`, `<=`, `>=`, `LIKE`, `IN`, etc.
- Can reference relationships using dot notation (e.g., `user.status`)

---

## 🎬 Actions Configuration

Actions are buttons displayed for each row, allowing custom operations.

### Action Structure

```php
'actions' => [
    'action_key' => [
        'label' => 'Button Label',
        'route' => 'route.name',       // Laravel route name
        'params' => ['id'],            // Route parameters (e.g., ['id', 'slug'])
        'class' => 'btn btn-primary',  // CSS classes
        'icon' => 'fas fa-edit',       // Icon class (optional)
        'method' => 'GET',             // HTTP method (default: GET)
        'confirm' => 'Are you sure?',  // Confirmation message (optional)
    ]
]
```

### Action Examples

#### Simple Link Action
```php
'actions' => [
    'view' => [
        'label' => 'View',
        'route' => 'users.show',
        'params' => ['id'],
        'class' => 'btn btn-sm btn-info',
    ]
]
```

#### Edit Action with Icon
```php
'edit' => [
    'label' => 'Edit',
    'route' => 'users.edit',
    'params' => ['id'],
    'class' => 'btn btn-sm btn-primary',
    'icon' => 'fas fa-edit',
]
```

#### Delete Action with Confirmation
```php
'delete' => [
    'label' => 'Delete',
    'route' => 'users.destroy',
    'params' => ['id'],
    'method' => 'DELETE',
    'class' => 'btn btn-sm btn-danger',
    'icon' => 'fas fa-trash',
    'confirm' => 'Are you sure you want to delete this user?',
]
```

**Action Rules:**
- `params` array values should match column keys from your data
- Routes must exist in your Laravel `routes/web.php`
- The component automatically passes model instance to generate URLs

---

## 📊 Complete Example

```blade
@livewire('aftable', [
    'model' => '\App\Models\User',
    
    'columns' => [
        'id' => [
            'key' => 'id',
            'label' => '#',
            'sortable' => true,
        ],
        'name' => [
            'key' => 'name',
            'label' => 'Full Name',
            'searchable' => true,
        ],
        'email' => [
            'key' => 'email',
            'label' => 'Email Address',
            'searchable' => true,
        ],
        'role_name' => [
            'key' => 'role_name',
            'label' => 'Role',
            'relation' => 'role:name',
            'searchable' => true,
        ],
        'company_address' => [
            'key' => 'company_address',
            'label' => 'Company Address',
            'relation' => 'profile.company:address',
        ],
        'preferences_theme' => [
            'key' => 'preferences',
            'label' => 'Theme',
            'json' => 'settings.theme',
        ],
        'status_badge' => [
            'key' => 'status',
            'label' => 'Status',
            'raw' => '<span class="badge badge-{{ $row->status }}">{{ ucfirst($row->status) }}</span>',
        ],
        'created_at' => [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
        ],
    ],
    
    'filters' => [
        'name' => [
            'type' => 'text',
        ],
        'status' => [
            'type' => 'distinct',
        ],
        'role_id' => [
            'type' => 'distinct',
            'relation' => 'role:name',
        ],
        'created_at' => [
            'type' => 'date',
        ],
    ],
    
    'query' => [
        ['deleted_at', null],
        ['status', '!=', 'suspended'],
    ],
    
    'actions' => [
        'view' => [
            'label' => 'View',
            'route' => 'users.show',
            'params' => ['id'],
            'class' => 'btn btn-sm btn-info',
            'icon' => 'fas fa-eye',
        ],
        'edit' => [
            'label' => 'Edit',
            'route' => 'users.edit',
            'params' => ['id'],
            'class' => 'btn btn-sm btn-primary',
            'icon' => 'fas fa-edit',
        ],
        'delete' => [
            'label' => 'Delete',
            'route' => 'users.destroy',
            'params' => ['id'],
            'method' => 'DELETE',
            'class' => 'btn btn-sm btn-danger',
            'icon' => 'fas fa-trash',
            'confirm' => 'Are you sure?',
        ],
    ],
    
    'records' => 25,
    'tableId' => 'users-table',
    'printable' => true,
    'exportable' => true,
    'searchable' => true,
    'sort' => 'desc',
    'sortColumn' => 'created_at',
    'checkbox' => true,
    'index' => true,
])
```

---

## 🚫 Common Mistakes to Avoid

### 1. Using Component in Livewire Class
❌ **WRONG:**
```php
class UserIndex extends Component
{
    public function render()
    {
        @livewire('aftable', [...]) // Don't do this
    }
}
```

### 2. Missing Model Namespace
❌ **WRONG:**
```php
'model' => 'User', // Missing namespace
```

✅ **CORRECT:**
```php
'model' => '\App\Models\User', // Full namespace with leading backslash
```

### 3. Function Column with Key
❌ **WRONG:**
```php
'full_name' => [
    'key' => 'full_name',  // Don't include key for function columns
    'function' => 'getFullNameAttribute',
]
```

✅ **CORRECT:**
```php
'full_name' => [
    'label' => 'Full Name',
    'function' => 'getFullNameAttribute',
    'searchable' => false,
    'sortable' => false,
]
```

### 4. Filtering Function Columns
❌ **WRONG:**
```php
'filters' => [
    'full_name' => [  // full_name is a function column, can't be filtered
        'type' => 'text',
    ]
]
```

### 5. Invalid Relation Format
❌ **WRONG:**
```php
'relation' => 'user.name', // Missing colon separator
```

✅ **CORRECT:**
```php
'relation' => 'user:name', // Format: relationName:attribute
```

### 6. JSON Key Confusion
❌ **WRONG:**
```php
'user_email' => [
    'key' => 'email',        // Wrong - 'email' is the JSON path, not the column
    'json' => 'contact.email',
]
```

✅ **CORRECT:**
```php
'user_email' => [
    'key' => 'data',         // The actual database column with JSON
    'label' => 'Email',
    'json' => 'contact.email', // The path within the JSON
]
```

---

## 🔧 Advanced Features

### Session Persistence
State is automatically saved and restored across page loads when enabled:
```php
public $enableSessionPersistence = true; // Default
```

### Query String Support
Table state is reflected in URL for sharing:
```php
public $enableQueryStringSupport = true; // Default
```

### Performance Optimization
- Automatic eager loading of relationships
- Query result caching
- Chunked exports for large datasets
- Distinct value caching for filters

### Export Functionality
When `exportable => true`:
- CSV export with current filters applied
- Excel export support
- Chunked processing for large datasets

---

## 🎯 Quick Decision Tree for AI Agents

```
START
  |
  └─ Creating a datatable?
       |
       ├─ YES → Use in Blade view with @livewire('aftable', [...])
       |         |
       |         └─ Define model, columns, filters, actions
       |
       └─ NO  → This package is not needed

Column Configuration?
  |
  ├─ Database column → Use 'key' property
  ├─ Relationship → Use 'relation' => 'relationName:attribute'
  ├─ JSON data → Use 'key' => 'column', 'json' => 'path.to.value'
  ├─ Computed value → Use 'function' => 'methodName' (NO key!)
  └─ Custom HTML → Use 'raw' => '<span>{{$row->field}}</span>'

Need filtering?
  |
  └─ Only for database columns (NOT function columns)
      └─ Use 'filters' array with appropriate 'type'

Need actions?
  |
  └─ Define in 'actions' array with route/params/class
```

---

## 📚 Additional Resources

- **Generator Tool**: `vendor/artflow-studio/table/index.html` (Code generator UI)
- **View Template**: `vendor/artflow-studio/table/src/resources/views/livewire/datatable-trait.blade.php`
- **Service Provider**: `vendor/artflow-studio/table/src/TableServiceProvider.php`

---

## 🔍 Debugging Tips

1. **Check Livewire registration**: Component should be registered as `'aftable'`
2. **Verify model exists**: Ensure full namespace path is correct
3. **Test relations separately**: Verify relationships work in model directly
4. **Check column keys**: Ensure database columns exist
5. **Validate filter types**: Use correct type for data type
6. **Test actions routes**: Ensure routes exist and accept correct parameters

---

## ✅ Validation Checklist for AI Agents

Before generating datatable code, verify:

- [ ] Using `@livewire('aftable', [...])` in Blade view (NOT in component)
- [ ] Model has full namespace with leading backslash
- [ ] All column keys exist in database OR are function columns
- [ ] Function columns have NO 'key' parameter
- [ ] Function columns have searchable/sortable set to false
- [ ] Relation columns use 'relationName:attribute' format
- [ ] JSON columns have both 'key' (database column) and 'json' (path)
- [ ] Filters only defined for database columns
- [ ] Action routes exist in application
- [ ] Query constraints use valid SQL operators

---

## 🔄 Correcting the User's Example

The user provided this example:
```blade
@livewire('aftable', [
    'model' => '\App\Models\MyModel',
    'columns' => [
        ['key' => 'Et molestias aute as', 'label' => 'Beatae voluptate dic', ...],
        // ... more columns
    ],
    'filters' => [...],
    'query' => [...],
    'actions' => ['Rerum ut amet ipsum'],
    'records' => 27,
    'tableId' => 'Veniam minus volupt',
    'dateColumn' => '30-May-2003',
    'printable' => true,
    'sort' => 'asc'
])
```

### Issues Found:
1. ❌ Columns array uses numeric indexes with arrays - should use associative keys
2. ❌ Actions defined as simple array - should be associative array with configuration
3. ❌ dateColumn has invalid date value - should be column name or null

### Corrected Version:
```blade
@livewire('aftable', [
    'model' => '\App\Models\MyModel',
    
    'columns' => [
        'id' => [
            'key' => 'id',
            'label' => 'ID',
            'sortable' => true,
        ],
        'name' => [
            'key' => 'name',
            'label' => 'Name',
            'searchable' => true,
            'sortable' => true,
        ],
        'status' => [
            'key' => 'status',
            'label' => 'Status',
            'searchable' => true,
        ],
    ],
    
    'filters' => [
        'status' => [
            'type' => 'distinct',
        ],
        'name' => [
            'type' => 'text',
        ],
    ],
    
    'query' => [
        ['deleted_at', null],
        ['status', '!=', 'inactive'],
    ],
    
    'actions' => [
        'view' => [
            'label' => 'View',
            'route' => 'mymodels.show',
            'params' => ['id'],
            'class' => 'btn btn-sm btn-info',
        ],
        'edit' => [
            'label' => 'Edit',
            'route' => 'mymodels.edit',
            'params' => ['id'],
            'class' => 'btn btn-sm btn-primary',
        ],
    ],
    
    'records' => 27,
    'tableId' => 'mymodels-table',
    'dateColumn' => null, // or 'created_at' if you have that column
    'printable' => true,
    'exportable' => true,
    'searchable' => true,
    'sort' => 'asc',
    'sortColumn' => 'id',
    'index' => true,
])
```

---

## 📖 Quick Reference Card

### Minimum Required Configuration
```blade
@livewire('aftable', [
    'model' => '\App\Models\User',
    'columns' => [
        'id' => ['key' => 'id', 'label' => 'ID'],
        'name' => ['key' => 'name', 'label' => 'Name'],
    ],
])
```

### Column Types Quick Reference
| Type | Example | Notes |
|------|---------|-------|
| Database | `'name' => ['key' => 'name', 'label' => 'Name']` | Direct DB column |
| Relation | `'user_name' => ['key' => 'user_name', 'label' => 'User', 'relation' => 'user:name']` | relationName:attribute |
| Nested Relation | `'address' => ['key' => 'address', 'relation' => 'user.company:address']` | nested.relation:attr |
| JSON | `'email' => ['key' => 'data', 'json' => 'contact.email', 'label' => 'Email']` | key=column, json=path |
| Function | `'full_name' => ['label' => 'Full Name', 'function' => 'getFullName', 'searchable' => false, 'sortable' => false]` | NO key! |
| Raw HTML | `'badge' => ['key' => 'status', 'raw' => '<span>{{$row->status}}</span>', 'label' => 'Status']` | Blade syntax |

### Filter Types Quick Reference
| Type | Usage | Min Chars |
|------|-------|-----------|
| text | `['type' => 'text']` | 3 |
| number | `['type' => 'number']` | - |
| date | `['type' => 'date']` | - |
| distinct | `['type' => 'distinct']` | - |
| relation | `['type' => 'distinct', 'relation' => 'model:attr']` | - |

### Boolean Options Quick Reference
| Option | Default | Description |
|--------|---------|-------------|
| searchable | true | Enable global search |
| exportable | false | Enable CSV/Excel export |
| printable | false | Enable print button |
| colSort | true | Enable column sorting |
| colvisBtn | true | Show column visibility button |
| refreshBtn | false | Show refresh button |
| checkbox | false | Enable row checkboxes |
| index | false | Show row number column |

---

**Last Updated**: November 2025  
**Package Version**: 1.4+  
**Laravel Compatibility**: 10.x, 11.x  
**Livewire Version**: 3.x
