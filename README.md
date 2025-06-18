# AF Table

A Laravel Livewire "datatable" component that makes it effortless to display, search, filter, sort, paginate, and export your Eloquent model data.

## Why Use AF Table?

- **Zero-boilerplate setup**: Just register the `aftable` component and you're ready.
- **Instant server-powered search** across all visible columns.
- **Column-based sorting** with toggles for ascending/descending.
- **Per-column filters** (text, select, number, and date-range).
- **Dynamic column visibility** so users can choose which columns to view.
- **Column visibility is now stored in the session** so user preferences persist across reloads.
- **Export options**: CSV, Excel, and PDF (Excel with Filtered/All Data selection, see below).
- **Print-friendly view** built in.
- **Row selection** with checkboxes and "select all".
- **Fully customizable columns**: raw Blade views, relation lookups, and conditional CSS classes.
- **Index column**: By default, every table shows an index column as the first column (1, 2, 3, ...), which is always correct across sorting and pagination. Can be disabled.
- **Performance improvements**: Only visible column relations are eager loaded, and distinct values for filters are fetched efficiently.
- **Table animation**: Smooth fade animation on pagination, sorting, and refresh for better UX.
- **Column visibility dropdown persists**: When toggling columns, the dropdown stays open for quick multi-toggle.
- **Session-based column visibility**: Each user/table/model combination remembers its own column visibility.
- **Efficient filter value fetching**: Distinct values for filters are cached for performance.
- **Minimal data transfer**: Only visible columns and paginated data are sent to the frontend.
- **Parent component event integration**: Inline select/dropdown actions can trigger parent Livewire methods directly.
- **Debounced search**: Reduces server requests for fast typing.
- **Auto-debounced filters**: Filter inputs automatically apply with 500ms debounce for smooth UX.
- **Query string state**: Pagination, sorting, filtering, and search state are reflected in the URL for shareable/filterable links.
- **Smart relation handling**: Automatically detects and loads relations used in raw templates and column definitions.
- **Automatic column detection**: Intelligently includes database columns referenced in raw templates without manual specification.
- **Custom Query Constraints**: Apply additional where clauses and complex filtering before table processing.

## Recent Enhancements

- **Smart Relation & Raw Template Handling**: The component now automatically detects relations used in raw templates and ensures proper eager loading. No need to manually specify all columns - the system intelligently detects what's needed from your raw templates.
- **Flexible Column Configuration**: You can use either simple relation syntax (`'relation' => 'category:name'`) for automatic display, or combine it with raw templates for custom formatting while maintaining relation functionality.
- **Auto-detection of Template Dependencies**: Raw templates that reference `$row->relation->attribute` automatically trigger relation loading and include necessary foreign keys in queries.

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
- **Smart Parsing**: The system distinguishes between direct column references and relation references
- **No Manual Column Lists**: You don't need to manually specify every column used in raw templates

### Important Notes:

1. **Relation Format**: Always use `"relation:attribute"` format for the `relation` key
2. **Raw Template Relations**: When using `$row->relation->attribute` in raw templates, the relation is automatically loaded
3. **Column Detection**: Columns like `$row->first_name` in raw templates are automatically included in queries
4. **Performance**: Only visible columns and their dependencies are loaded, keeping queries efficient

## Custom Query Constraints

The `query` parameter allows you to apply additional constraints to the base query before any table operations (search, filters, sorting) are applied. This is useful for:

- Filtering data by user permissions
- Showing only active/published records
- Applying tenant-specific filters
- Complex business logic filtering

### Usage Examples

#### Using Array of Constraints (Recommended for Livewire)

```php
@livewire('aftable', [
    'model' => App\Models\Service::class,
    'columns' => [...],
    'query' => [
        ['active', '=', true],        // WHERE active = true
        ['cost', '>', 0],             // AND cost > 0
        ['name', 'like', '%premium%'], // AND name LIKE '%premium%'
    ],
])
```

#### Simple Single Condition

```php
@livewire('aftable', [
    'model' => App\Models\Service::class,
    'columns' => [...],
    'query' => [['active', true]], // WHERE active = true
])
```

### Common Use Cases

#### Status-Based Filtering
```php
'query' => [
    ['active', '=', true]
]
```

#### Multiple Conditions
```php
'query' => [
    ['status', 'in', ['active', 'pending']],
    ['created_at', '>=', '2024-01-01']
]
```

#### User-Specific Data
```php
'query' => [
    ['user_id', '=', auth()->id()]
]
```

### Important Notes

1. **Applied First**: Custom query constraints are applied before all other table operations (search, filters, sorting).

2. **Livewire Compatible**: Use array format for Livewire compatibility. Closures cannot be serialized.

3. **Array Format**: Each constraint should be an array with `[column, operator, value]` or `[column, value]` format.

4. **Export Compatibility**: Custom query constraints are automatically applied to exported data.

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
| `index`          | `bool`         | `true`      | Show index column as first column. Set to `false` to hide. |

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

Each entry in `columns` must include:

- `key` (string, **required**): Model attribute or relation key.
- `label` (string, **required**): Header text.
- `sortable` (bool): Enable sorting.
- `searchable` (bool): Include in global search.
- `raw` (string): Raw Blade snippet for custom rendering. **Automatically detects and includes referenced columns/relations**.
- `relation` (string): Use the format `"relation:column"` (e.g. `"category:name"` or `"member:email"`) to display and enable sorting/filtering on a related model field.
- `classCondition` (array): `[ 'css-class' => fn($row)=> condition ]`.

> **Smart Column Detection:**  
> When using raw templates, you don't need to manually specify every column. The system automatically:
> - Detects `$row->column_name` references and includes them in the query
> - Identifies `$row->relation->attribute` patterns and eager loads the relations
> - Includes necessary foreign keys for relation columns
> - Excludes relation names from being treated as database columns

> **Relation Sorting/Filtering:**  
> To enable sorting or filtering on a related field, you **must** define the `relation` key using the `"relation:column"` format.  
> For example: `['key' => 'category_id', 'relation' => 'category:name', 'label' => 'Category']`

### Filters

The new dynamic filtering system supports multiple filter types with auto-debouncing and smart operator selection:

#### Supported Filter Types:

- **`text`**: Text input with LIKE operator for partial matching.
- **`select`**: Dropdown with distinct values from the column, uses exact matching (=).
- **`integer`/`number`**: Number input with operator selection (=, !=, <, >, <=, >=).
- **`date`**: Date picker with operator selection (=, !=, <, >, <=, >=).

#### Filter Configuration:

```php
'filters' => [
    // Text filter - auto LIKE operator
    'name' => [
        'type' => 'text'
    ],
    
    // Select filter - shows dropdown with distinct values
    'status' => [
        'type' => 'select'
    ],
    
    // Select filter with relation
    'district_id' => [
        'type' => 'select',
        'relation' => 'district:district'  // relation:column format
    ],
    
    // Number filter - shows operator dropdown
    'price' => [
        'type' => 'number'
    ],
    
    // Date filter - shows operator dropdown
    'created_at' => [
        'type' => 'date'
    ],
]
```

#### Key Features:

- **Auto-debouncing**: All filter inputs automatically apply with 500ms debounce.
- **Conditional Display**: Filter UI only appears when `filters` array is provided.
- **Smart Operators**: Operators are automatically chosen based on filter type.
- **Relation Support**: Use `"relation:column"` format for filtering related model fields.
- **Session Compatibility**: Works seamlessly with session-stored column visibility.

Example:

```php
'filters' => [
    'status' => [ 'type'=>'select' ],
    'price'  => [ 'type'=>'number' ],
    'created_at' => [ 'type'=>'date' ],
]
```

### Actions

Raw Blade snippets per row, for example:

```php
'actions' => [
    '<a href="/service/{{$row->id}}/edit" class="btn btn-sm btn-light">Edit</a>',
    '<button wire:click="delete({{$row->id}})">Delete</button>',
]
```

## Core Methods

- `mount($model, $columns, $filters = [], $actions = [], $index = true)`
- `updatedSearch()`
- `toggleSort($column)`
- `toggleColumnVisibility($columnKey)` (now persists to session)
- `export($format, $scope = 'filtered')` – Excel export with modal for Filtered/All Data
- `applyColumnFilter($columnKey)`
- `applyDateRange($start, $end)`
- `renderRawHtml($template, $row)`
- `getDistinctValues($column)`
- `render()` – returns Blade view with paginated data.

Protected helpers:

- `query(): Builder` – builds the base query.
- `applyFilters(Builder $query)` – applies all filters.

## Example

See the **Usage** section above for a complete `Service` model example.

## License

MIT
