# AF Table

A Laravel Livewire “datatable” component that makes it effortless to display, search, filter, sort, paginate, and export your Eloquent model data.

## Why Use AF Table?

- **Zero-boilerplate setup**: Just register the `aftable` component and you’re ready.
- **Instant server-powered search** across all visible columns.
- **Column-based sorting** with toggles for ascending/descending.
- **Per-column filters** (text, select, number, and date-range).
- **Dynamic column visibility** so users can choose which columns to view.
- **Column visibility is now stored in the session** so user preferences persist across reloads.
- **Export options**: CSV, Excel, and PDF (Excel with Filtered/All Data selection, see below).
- **Print-friendly view** built in.
- **Row selection** with checkboxes and “select all”.
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

## Recent Enhancements

- **Excel Export Modal**: When clicking the Export Excel button, users can now choose to export either "Filtered Data" (current filters/search applied) or "All Data" (entire dataset). This is handled via a modal dialog.
- **Dynamic Filtering System**: Complete overhaul of the filtering system with auto-debouncing, smart operators, and conditional display.
- **Enhanced Filter Types**: Support for text (LIKE), select (exact match), number/integer (with operators), and date (with operators) filters.
- **Session-aware Filtering**: Filters work seamlessly with session-stored column visibility, handling edge cases gracefully.
- **Auto-debouncing**: All filter inputs automatically apply with 500ms debounce for smooth user experience.
- **Smart Operator Selection**: Automatic operator assignment based on filter type - no manual operator selection needed for most cases.
- **Conditional Filter UI**: Filter interface only appears when filters are defined, keeping the UI clean.
- **Index Column**: Added automatic index column as the first column in every table. Can be disabled by passing `'index' => false` to the component.
- **Column Visibility Persistence**: Column visibility toggles are now stored in the session, so user preferences persist across reloads and navigation. Each table/model has its own session key.
- **Performance**: Only relations for visible columns are eager loaded, reducing unnecessary queries. Distinct values for filters are fetched using efficient queries and cached.
- **Table Animation**: Table body animates on pagination, sorting, and refresh for a modern user experience.
- **Dropdown Persistence**: Column visibility dropdown remains open after toggling, making it easy to show/hide multiple columns quickly.
- **Parent Event Integration**: Inline actions (like select dropdowns) can call parent Livewire methods directly, enabling advanced workflows.
- **Cleaner API**: No need to specify `'index' => true` (it's the default). Only set `'index' => false` if you want to hide the index column.

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

### Column Definitions

Each entry in `columns` must include:

- `key` (string, **required**): Model attribute or relation key.
- `label` (string, **required**): Header text.
- `sortable` (bool): Enable sorting.
- `searchable` (bool): Include in global search.
- `raw` (string): Raw Blade snippet for custom rendering.
- `relation` (string): Use the format `"relation:column"` (e.g. `"category:name"` or `"member:email"`) to display and enable sorting/filtering on a related model field.
- `classCondition` (array): `[ 'css-class' => fn($row)=> condition ]`.

> **Important:**  
> If you want to enable sorting or filtering on a related field, you **must** define the `relation` key using the `"relation:column"` format.  
> For example, to sort/filter by a member's name, use:  
> `['key' => 'name', 'label' => 'Name', 'relation' => 'member:name']`  
> If `relation` is not defined, sorting and filtering will only work for direct columns on the main table.

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
