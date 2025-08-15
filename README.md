# AF Table

A Laravel Livewire "datatable" component that makes it effortless to display, search, filter, sort, paginate, and export your Eloquent model data.

## Why Use AF Table?

- **Zero-boilerplate setup**: Just register the `aftable` component and you're ready.
- **Instant server-powered search** across all visible columns.
- **Column-based sorting** with toggles for ascending/descending.
- **Per-column filters** (text, select, number, and date-range).
- **Dynamic column visibility** so users can choose which columns to view.
- **Column visibility is now stored in the session** so user preferences persist across reloads.
- **Column visibility button**: Toggle column visibility with a built-in button (`$colvisBtn`).
- **Export options**: CSV, Excel, and PDF (Excel with Filtered/All Data selection, see below).
- **Print-friendly view** built in.
- **Row selection** with checkboxes and "select all".
- **Fully customizable columns**: raw Blade views, relation lookups, and conditional CSS classes.
- **Function-based columns**: Display model method results directly without database queries.
- **Index column**: Optional index column (1, 2, 3, ...) that's correct across sorting and pagination. **Disabled by default**.
- **High-Performance**: Optimized queries, caching, memory management, and efficient relation loading.
- **Smart Query Optimization**: Indexed search patterns, consolidated eager loading, and efficient sorting.
- **Memory Management**: Chunked processing for exports and limited distinct value queries.

## Recent Performance Enhancements

- **Query Optimization**: Consolidated eager loading, efficient JOIN strategies, and cached relation detection
- **Memory Management**: Chunked export processing, limited distinct value queries, and lazy loading
- **Search Performance**: Indexed query patterns, numeric search optimization, and reduced LIKE wildcards  
- **Caching Strategy**: Cached distinct values, relation mapping, and column configuration
- **N+1 Prevention**: Single consolidated queries instead of multiple separate calls
- **Index Column Default**: Changed to `false` by default for better performance

## Performance Features

### Query Optimization
- **Consolidated Eager Loading**: Single query loads all required relations
- **Cached Relation Detection**: Pre-calculated relation dependencies  
- **Efficient Column Selection**: Only visible columns included in queries
- **Indexed Search Patterns**: Optimized LIKE queries for better index usage

### Memory Management  
- **Chunked Processing**: Large datasets processed in chunks to prevent memory overflow
- **Limited Distinct Values**: Configurable limits on filter dropdown options (default: 1000)
- **Lazy Collections**: Memory-efficient data processing for exports
- **Cache Management**: Configurable cache timeouts and targeted cache clearing

### Smart Caching
- **Distinct Value Caching**: Filter options cached for 5 minutes by default
- **Relation Mapping Cache**: Pre-calculated relation dependencies
- **Column Configuration Cache**: Optimized column selection and processing

---

## ⚠️ Nested Relationships - Current Limitations & Solutions

AF Table supports both simple and nested relationships, but with some important considerations:

### ✅ Supported Relationship Patterns

#### Simple Relations (Fully Supported)
```php
// Single-level relationships - full support including sorting
['key' => 'category_id', 'label' => 'Category', 'relation' => 'category:name']
['key' => 'user_id', 'label' => 'Author', 'relation' => 'user:email']
```

#### Nested Relations (Display Only - Multi-Level Support)
```php
// Multi-level relationships - display supported, sorting disabled for stability
['key' => 'student_id', 'label' => 'Student Name', 'relation' => 'student.user:name']
['key' => 'order_id', 'label' => 'Customer Company', 'relation' => 'order.customer.company:name']

// Deep nesting with complex attributes (Level 3+)
['key' => 'enrollment_id', 'label' => 'Student Profile Bio', 'relation' => 'student.user.profile:bio']
['key' => 'booking_id', 'label' => 'Traveler Address', 'relation' => 'passenger.user.profile.address:street']

// Multi-level attributes (both relation and attribute can be nested)
['key' => 'order_id', 'label' => 'Billing Address', 'relation' => 'customer.profile:address.street']
```

**Multi-Level Nesting Syntax:**
- **Relation Part**: Use dots to separate relation levels: `student.user.profile`
- **Attribute Part**: Use dots to separate attribute levels: `address.street.name`
- **Full Syntax**: `relation.nested.chain:attribute.nested.chain`

**Examples of Valid Nesting:**
```php
// Level 1: Simple relation
'relation' => 'user:name'

// Level 2: One nested relation
'relation' => 'student.user:email'

// Level 3: Two nested relations
'relation' => 'enrollment.student.user:name'

// Level 4: Three nested relations with nested attribute
'relation' => 'booking.passenger.user.profile:address.street'

// Complex: Both relation and attribute are multi-level
'relation' => 'order.customer.profile:contact.address.city'
```

### 🚫 Current Limitations

1. **Nested Relation Sorting**: Columns with nested relations (e.g., `student.user:name`) cannot be sorted to prevent query errors
2. **Deep Nesting Performance**: Relations deeper than 2 levels may impact performance
3. **Complex Joins**: Very complex nested relations may require custom query optimization

### 💡 Recommended Solutions

#### Option 1: Model Accessors (Recommended)
Create accessors in your model for commonly used nested data:

```php
// In your Enrollment model
public function getUserNameAttribute()
{
    return $this->student?->user?->name;
}

public function getUserEmailAttribute() 
{
    return $this->student?->user?->email;
}

// Then use in your table configuration
['key' => 'user_name', 'label' => 'Student Name']
['key' => 'user_email', 'label' => 'Student Email']
```

**Benefits:**
- ✅ Fully sortable and searchable
- ✅ Better performance with eager loading
- ✅ More maintainable and testable
- ✅ Reusable across your application

#### Option 2: Raw Templates with Relations
For complex display formatting while maintaining performance:

```php
[
    'key' => 'student_id',
    'label' => 'Student Info', 
    'raw' => '<div>
        <strong>{{ $row->student?->user?->name }}</strong><br>
        <small class="text-muted">{{ $row->student?->user?->email }}</small>
    </div>'
]
```

#### Option 3: Custom Query Scopes
For complex filtering needs:

```php
// In your model
public function scopeWithStudentUser($query)
{
    return $query->with(['student.user']);
}

// In your component
'query' => fn($q) => $q->withStudentUser()
```

### 🔮 Future Roadmap

We're actively working on full nested relationship support:

- **Phase 1** (Current): Display support with accessor recommendations
- **Phase 2** (Q2 2025): Full sorting support for nested relations
- **Phase 3** (Q3 2025): Advanced nested filtering and search
- **Phase 4** (Q4 2025): Unlimited nesting depth with performance optimization

For the complete development roadmap, see `AF_TABLE_ROADMAP.md`.

---

## Cache Management

AF Table uses caching to improve performance, especially for filter dropdowns and relation mapping.

- **Distinct Values Cache**: Filter dropdown options (distinct values) are cached for 5 minutes by default (`distinctValuesCacheTime`).
- **Cache Clearing**: When you change filter columns or want to refresh filter options, the cache is automatically cleared. You can also clear all distinct value caches programmatically using:
    ```php
    $this->clearDistinctValuesCache();
    ```
- **Targeted Cache**: The cache is keyed per table instance and column, so clearing one does not affect others.

---

## Column and Relation Validation

AF Table validates all columns and relations before including them in SQL queries to prevent SQL errors and improve security.

- **Column Validation**: Only columns that exist in your model's table (or are common columns like `id`, `created_at`, `updated_at`) are included in SELECT statements and filters.
- **Relation Validation**: Relation columns are checked to ensure the relation exists on your model and the foreign key is valid.
- **Raw Templates**: Any columns or relations referenced in raw Blade templates are also validated before being included in queries.

This validation prevents SQL errors and ensures that only valid columns and relations are queried.

---

## Default Sort Column Logic

AF Table automatically selects the most optimal default sort column for performance and usability:

- **Indexed Columns First**: If your columns include common indexed columns like `id`, `created_at`, or `updated_at`, the first one found is used as the default sort column.
- **Fallback**: If no indexed columns are present, the first sortable column is used.
- **Customizable**: You can override the default by setting the `sortColumn` property or passing a `sort` option.

This logic ensures fast sorting and a sensible default order for your data.

---

## Eager Loading and Sort Direction Validation

- **Eager Loading**: AF Table always uses eager loading (`with()`) for all relations referenced in columns, filters, and sorting, including when sorting by a relation. This prevents N+1 query issues and ensures optimal performance.
- **Sort Direction Validation**: The component validates the sort direction for all sorting operations, only allowing `'asc'` or `'desc'` values. Any invalid value will default to `'asc'` to prevent SQL errors and ensure consistent behavior.

---

## Column Types

### 1. Database Columns (key-based)

For displaying database column values:

```php
[
    'key' => 'name',
    'label' => 'Product Name'
]
```

### 2. Relation Columns

For displaying related model attributes:

```php
[
    'key' => 'category_id',
    'relation' => 'category:name',
    'label' => 'Category'
]
```

### 3. Function-Based Columns (No Key Required)

For displaying model method results without database queries:

```php
[
    'function' => 'isActive',  // No 'key' required for function columns
    'label' => 'Status'
]
```

#### Function-Based Column Features:

- **No Key Required**: Function columns only need the `function` parameter, no `key` needed
- **No Database Query**: Function columns are excluded from SELECT statements
- **No Sorting**: Function-based columns are not sortable (since they're computed)
- **Auto Boolean Conversion**: Boolean results are automatically converted to "Yes/No" for display
- **Raw Template Support**: Can be combined with raw templates for custom formatting
- **Method Validation**: Checks if the method exists before calling it
- **Performance Optimized**: Only visible function columns are processed

#### Function Column Examples:

##### Simple Function Display
```php
[
    'function' => 'isActive',
    'label' => 'Active Status'
]
// Displays: "Yes" or "No" based on the isActive() method result
```

##### Function with Custom Raw Template
```php
[
    'function' => 'getStatusBadge',
    'label' => 'Status',
    'raw' => '<span class="badge bg-{{ $row->getStatusBadge() === "active" ? "success" : "warning" }}">
                {{ ucfirst($row->getStatusBadge()) }}
              </span>'
]
// Displays: Custom badge with conditional styling
```

##### Multiple Function Calls in Raw Template
```php
[
    'function' => 'getOrderStatus',
    'label' => 'Order Details',
    'raw' => '<div>
                <span>Status: {{ $row->getOrderStatus() }}</span><br>
                <small>Shipped: {{ $row->isShipped() ? "Yes" : "No" }}</small>
              </div>'
]
```

##### Complex Business Logic Example
```php
[
    'function' => 'calculateDiscount',
    'label' => 'Discount Available',
    'raw' => '<span class="text-{{ $row->calculateDiscount() > 0 ? "success" : "muted" }}">
                {{ $row->calculateDiscount() > 0 ? $row->calculateDiscount() . "%" : "No Discount" }}
              </span>'
]
```

### Model Method Requirements

Your Eloquent model should have the corresponding methods:

```php
// Example methods in your Eloquent model (e.g., Product, Order, User model)
public function isActive(): bool
{
    return $this->status === 'active';
}

public function getStatusBadge(): string
{
    return $this->status ?? 'pending';
}

public function isShipped(): bool
{
    return !is_null($this->shipped_at);
}

public function calculateDiscount(): float
{
    // Your business logic here
    return $this->total > 100 ? 10.0 : 0.0;
}

public function getOrderStatus(): string
{
    return match($this->status) {
        'pending' => 'Pending Processing',
        'processing' => 'Being Processed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        default => 'Unknown Status'
    };
}
```

### Column Identification

The component now supports flexible column identification:

- **Database columns**: Use `'key' => 'column_name'`
- **Function columns**: Use `'function' => 'methodName'`
- **Auto-generated**: If neither key nor function is provided, an auto-generated identifier is used

## Relation Handling and Raw Templates

### Simple Relation Display

For basic relation display, just use the `relation` key:

```php
[
    'key' => 'category_id',
    'relation' => 'category:name',  // Automatically displays category.name
    'label' => 'Category'
]
```

### Raw Templates with Relations

When you need custom formatting but still want to use relations, combine `relation` and `raw`:

```php
[
    'key' => 'category_id',
    'relation' => 'category:name',
    'label' => 'Category',
    'raw' => '<span class="badge bg-primary">{{ $row->category->name }}</span>'
]
```

### Advanced Raw Templates with Multiple Relation Attributes

For complex displays using multiple attributes from the same relation:

```php
[
    'key' => 'category_id',
    'relation' => 'category:name',  // Still needed for sorting/filtering
    'label' => 'Category',
    'raw' => '<span>
                <img src="{{ asset("icons/" . $row->category->icon) }}" class="me-2">
                {{ $row->category->name }}
              </span>'
]
```

### Concatenated Fields

For concatenating multiple columns from the same table:

```php
[
    'key' => 'first_name',  // Primary column
    'label' => 'Full Name',
    'raw' => '{{ $row->first_name . " " . $row->last_name }}'
    // last_name is automatically detected and included in query
]
```

### Key Features:

- **Automatic Detection**: Columns referenced in raw templates are automatically detected and included in the database query
- **Relation Auto-loading**: Relations used in raw templates are automatically eager loaded
- **Foreign Key Management**: Foreign keys for relations are automatically included in SELECT statements
- **Smart Parsing**: The system distinguishes between direct column references, relation references, and method calls
- **No Manual Column Lists**: You don't need to manually specify every column used in raw templates
- **Function Method Calls**: Model methods in raw templates are automatically detected and called

### Important Notes:

1. **Relation Format**: Always use `"relation:attribute"` format for the `relation` key
2. **Raw Template Relations**: When using `$row->relation->attribute` in raw templates, the relation is automatically loaded
3. **Column Detection**: Columns like `$row->first_name` in raw templates are automatically included in queries
4. **Method Detection**: Method calls like `$row->methodName()` in raw templates are automatically processed
5. **Performance**: Only visible columns and their dependencies are loaded, keeping queries efficient

## Mixed Column Example

Here's an example showing all column types together using a generic e-commerce scenario:

```php
'columns' => [
    // Database column
    ['key' => 'id', 'label' => 'Order ID'],
    
    // Relation column with raw template
    [
        'key' => 'customer_id',
        'relation' => 'customer:first_name',
        'label' => 'Customer',
        'raw' => '{{ $row->customer->first_name . " " . $row->customer->last_name }}'
    ],
    
    // Regular database column with formatting
    [
        'key' => 'total_amount',
        'label' => 'Total',
        'raw' => '<span class="text-success fw-bold">${{ number_format($row->total_amount, 2) }}</span>'
    ],
    
    // Function-based column with simple display
    [
        'function' => 'isShipped',
        'label' => 'Shipped'
    ],
    
    // Function-based column with custom template
    [
        'function' => 'getOrderStatus',
        'label' => 'Status',
        'raw' => '<span class="badge bg-{{ $row->getOrderStatus() === "completed" ? "success" : "warning" }}">
                    {{ ucfirst($row->getOrderStatus()) }}
                  </span>'
    ],
    
    // Function for calculated values
    [
        'function' => 'getDaysToDelivery',
        'label' => 'Delivery ETA',
        'raw' => '<small class="text-muted">{{ $row->getDaysToDelivery() }} days</small>'
    ]
]
```

## Common Function Column Use Cases

### Status Checks
```php
// Check if user has specific permissions
['function' => 'hasAdminAccess', 'label' => 'Admin Access']

// Check payment status
['function' => 'isPaid', 'label' => 'Payment Status']

// Check if item is in stock
['function' => 'isInStock', 'label' => 'Available']
```

### Calculated Values
```php
// Calculate age from birthdate
['function' => 'getAge', 'label' => 'Age']

// Calculate total with taxes
['function' => 'getTotalWithTax', 'label' => 'Total (incl. tax)']

// Get formatted price
['function' => 'getFormattedPrice', 'label' => 'Price']
```

### Dynamic Content
```php
// Get user's full display name
['function' => 'getDisplayName', 'label' => 'Name']

// Get formatted address
['function' => 'getFullAddress', 'label' => 'Address']

// Get relative date (e.g., "2 days ago")
['function' => 'getTimeAgo', 'label' => 'Last Activity']
```

## Installation

```bash
composer require artflow-studio/table

php artisan vendor:publish --provider="ArtflowStudio\Table\TableServiceProvider" --tag="config"
php artisan vendor:publish --provider="ArtflowStudio\Table\TableServiceProvider" --tag="views"
php artisan vendor:publish --provider="ArtflowStudio\Table\TableServiceProvider" --tag="assets"
```

## Registering the Component

In most cases this is automatic via the service provider:

```php
Livewire::component('aftable', \ArtflowStudio\Table\Http\Livewire\Datatable::class);
```

You may also use the Blade directive:

```blade
@AFtable('myTableId', App\Models\MyModel::class, $columns, $filters, $actions)
```

## Usage

### In Blade (Example Usage)

```blade
<div>
    @livewire('aftable',[
        'model' => 'App\\Models\\ExampleModel',
        'columns' => [
            ['key' => 'column1', 'label' => 'Column 1'],
            ['key' => 'column2', 'label' => 'Column 2'],
            ['key' => 'column3', 'label' => 'Column 3', 'raw' => '<span>{{$row->column3}}</span>'],
            ['key' => 'relation_id', 'relation' => 'relation:attribute', 'label' => 'Related Attribute'],
            ['key' => 'hidden_column', 'label' => 'Hidden Column', 'hide' => true],
            // ...add more columns as needed
        ],
        'filters' => [
            'relation_id' => [
                'type' => 'select',
                'relation' => 'relation:attribute'
            ],
            'column2' => [
                'type' => 'text'
            ],
            'date_column' => [
                'type' => 'date'
            ],
            // ...add more filters as needed
        ],
        'actions' => [
            '<div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="actionMenu{{$row->id}}" data-bs-toggle="dropdown" aria-expanded="false">
                Actions
                </button>
                <ul class="dropdown-menu" aria-labelled by="actionMenu{{$row->id}}">
                <li><a class="dropdown-item" href="#">View</a></li>
                <li><a class="dropdown-item" href="#">Edit</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="return confirm(\'Are you sure?\')">Delete</a></li>
                </ul>
            </div>'
        ],
        'exportable' => true,
    ])
</div>
```

### Exporting Excel (Filtered/All Data)

When you click the **Export Excel** button, a modal will appear asking if you want to export "Filtered Data" (current filters/search applied) or "All Data" (entire dataset). Select your option and the Excel file will be generated accordingly.

- **Filtered Data**: Exports only the rows currently visible with filters/search applied.
- **All Data**: Exports the entire dataset for the model, ignoring filters/search.

> **Note:** Only Excel export is enabled by default. PDF and CSV can be enabled/extended as needed.

## Configuration Options

### Public Properties

| Property         | Type           | Default     | Description                                      |
|------------------|----------------|-------------|--------------------------------------------------|
| `model`          | `string`       | _required_  | Fully-qualified Eloquent model class.            |
| `columns`        | `array`        | `[]`        | Column definitions (see below).                  |
| `filters`        | `array`        | `[]`        | Column filter configurations.                    |
| `actions`        | `array`        | `[]`        | Row-action Blade snippets.                       |
| `query`          | `array`        | `[]`        | Custom query constraints applied before table operations. |
| `searchable`     | `bool`         | `true`      | Show global search box.                          |
| `exportable`     | `bool`         | `true`      | Show export menu (Excel, PDF).                   |
| `printable`      | `bool`         | `true`      | Show print button.                               |
| `checkbox`       | `bool`         | `false`     | Enable row-selection checkboxes.                 |
| `records`        | `int`          | `10`        | Rows per page.                                   |
| `dateColumn`     | `string|null`  | `null`      | Enables date-range filter on this column.        |
| `sort`           | `string`       | `'desc'`    | Default sort direction (`'asc'` or `'desc'`).    |
| `colSort`        | `bool`         | `true`      | Allow sorting by clicking on column headers.     |
| `refreshBtn`     | `bool`         | `false`     | Show a manual refresh button to reload the table data. |
| `index`          | `bool`         | `false`     | Show index column as first column. **Changed to false by default for better performance**. |
| `colvisBtn`      | `bool`         | `true`      | Show the column visibility button to let users toggle which columns are visible. |

### Performance Configuration

| Property                    | Type    | Default | Description                                |
|----------------------------|---------|---------|-------------------------------------------|
| `distinctValuesCacheTime`  | `int`   | `300`   | Seconds to cache filter distinct values   |
| `maxDistinctValues`        | `int`   | `1000`  | Maximum distinct values per filter        |

#### Query Parameter Examples

The `query` parameter accepts an array of conditions in the following formats:

```php
// Single condition with operator
'query' => [
    ['active', '=', true]
]

// Multiple conditions
'query' => [
    ['active', '=', true],
    ['cost', '>', 100],
    ['status', 'in', ['published', 'active']]
]

// Shorthand for equality
'query' => [
    ['active', true],  // Equivalent to ['active', '=', true]
    ['published', 1]
]

// Complex conditions with LIKE
'query' => [
    ['name', 'like', '%premium%'],
    ['category', '!=', 'draft']
]

// Date-based filtering
'query' => [
    ['created_at', '>=', '2024-01-01'],
    ['updated_at', '<', now()->toDateString()]
]
```

### Column Definitions

Each entry in `columns` can include:

#### Required (one of these):
- `key` (string): Model attribute or database column name.
- `function` (string): Model method name to call for computed values.

#### Optional:
- `label` (string, **required**): Header text.
- `class` (string): CSS classes applied to both `<th>` and `<td>` elements (legacy support).
- `th_class` (string): CSS classes applied specifically to the header `<th>` element.
- `td_class` (string): CSS classes applied specifically to the table cell `<td>` elements.
- `sortable` (bool): Enable sorting (ignored for function columns).
- `searchable` (bool): Include in global search (ignored for function columns).
- `raw` (string): Raw Blade snippet for custom rendering. **Automatically detects and includes referenced columns/relations/methods**.
- `relation` (string): Use the format `"relation:column"` (e.g. `"category:name"` or `"member:email"`) to display and enable sorting/filtering on a related model field.
- `classCondition` (array): `[ 'css-class' => fn($row)=> condition ]`.
- `hide` (bool): Hide column by default (can be toggled via column visibility).

#### CSS Class Priority

The component supports three class properties with the following priority:

1. **`th_class`**: Specific classes for table headers (`<th>` elements)
2. **`td_class`**: Specific classes for table cells (`<td>` elements)  
3. **`class`**: Fallback classes applied to both if specific classes are not provided

```php
// Example with all class types
[
    'key' => 'status',
    'label' => 'Status',
    'th_class' => 'bg-primary text-white text-center',    // Header styling
    'td_class' => 'text-center fw-bold',                   // Cell styling
    'class' => 'w-100px'                                   // Applied to both if th_class/td_class/
```

## Important Notes on Relation Columns

- **For relation columns, the `key` must be the foreign key column in your main table, not the related attribute.**
    - Example: If you want to show `booking.unique_id` via the `booking` relation, your column config should be:
      ```php
      [
          'key' => 'booking_id', // foreign key in your table
          'label' => 'Booking ID',
          'relation' => 'booking:unique_id' // relation:attribute on related model
      ]
      ```
    - **Do NOT use `'key' => 'unique_id'`** if `unique_id` does not exist in your main table. This will result in empty columns or SQL errors.

- The component will attempt to auto-detect the foreign key if you use a relation, but it is best practice to always specify the correct foreign key in `key`.

- For regular columns (not relations), always use the actual column name from your model's table in `key`.

## Example: Relation Column

```php
[
    'key' => 'booking_id', // foreign key in flight_details table
    'label' => 'Booking ID',
    'relation' => 'booking:unique_id' // will display $row->booking->unique_id
]
```

## What Changed

- **Column selection logic**: The backend now ensures that for relation columns, only the foreign key from the main table is included in the SQL SELECT, not the related attribute.
- **Auto-detection**: If you specify a relation but the `key` is not a valid column, the system tries to guess the foreign key (e.g., `booking_id` for `booking`).
- **Action columns always available**: Any column referenced in the `actions` array (such as `{{$row->uuid}}`) is now automatically detected and included in the SQL SELECT, even if not present in the `columns` array. This ensures that `$row->uuid` and similar fields are always available in your action templates.
- **Documentation**: This README now clarifies that for relation columns, you must use the foreign key as `key`, and the related attribute in `relation`.
- **Error prevention**: This prevents SQL errors and ensures relation columns display data correctly.

---

## Filter Types

- `text`: Free text input, uses `LIKE %value%` for partial matches.
- `select`: Dropdown with values you provide or from a relation.
- `distinct`: Dropdown with all distinct values from the column, auto-populated and sorted A-Z.
- `number`/`integer`: Numeric input.
- `date`: Date picker input.

### Example: Distinct Filter

```php
'filters' => [
    'city' => [
        'type' => 'distinct'
    ],
    // ...
]
```

This will show a dropdown of all unique city values in ascending order.

## Security Notes

- **Input Sanitization**: All search and filter inputs are sanitized to prevent SQL injection and XSS attacks.
- **Virtual Columns**: Some virtual columns (such as function-based columns or those not present in the database) are ignored in SQL queries and filtering for security and performance.

---

## README Improvement Suggestions

- Add a quickstart section for new users with minimal setup steps
- Include a visual diagram of component architecture and data flow
- Add more real-world usage examples (API data, array data, custom actions)
- Document limitations and workarounds for nested relations more clearly
- Add a troubleshooting section for common issues (SQL errors, relation problems)
- Provide migration guides for upgrading from older versions
- Add links to interactive demos or live examples
- Include a section on extensibility: how to add custom filters, actions, or export formats
- Add best practices for performance optimization and security
- Document trait-based architecture and how to extend core features
- Add FAQ for advanced use cases (multi-table joins, dynamic columns, etc.)
