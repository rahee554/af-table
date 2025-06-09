# AF Table Package

A Laravel Livewire “datatable” component that makes it effortless to display, search, filter, sort, paginate, and export your Eloquent model data.

## Why Use AF Table?

- **Zero-boilerplate setup**: Just register the `aftable` component and you’re ready.
- **Instant server-powered search** across all visible columns.
- **Column-based sorting** with toggles for ascending/descending.
- **Per-column filters** (text, select, number, and date-range).
- **Dynamic column visibility** so users can choose which columns to view.
- **Export options**: CSV, Excel, and PDF.
- **Print-friendly view** built in.
- **Row selection** with checkboxes and “select all”.
- **Fully customizable columns**: raw Blade views, relation lookups, and conditional CSS classes.

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

### In Blade

```blade
@livewire('aftable', [
    'model'      => \App\Models\Service::class,
    'columns'    => [
        ['key'=>'id',          'label'=>'ID',           'sortable'=>true],
        ['key'=>'name',        'label'=>'Name',         'searchable'=>true],
        ['key'=>'description', 'label'=>'Description'],
        [
            'key'   => 'active',
            'label' => 'Status',
            'raw'   => '<span class="badge badge-{{ $row->active ? "success":"danger" }}">
                          {{ $row->active ? "Active" : "Inactive" }}
                        </span>',
            'classCondition' => [
                'text-muted' => fn($r)=> !$r->active
            ]
        ],
        [
            'key'   => 'cost',
            'label' => 'Actual Cost',
            'raw'   => '{{ number_format($row->cost, 2) }}'
        ],
        [
            'key'   => 'price',
            'label' => 'Customer Price',
            'raw'   => '{{ number_format($row->price, 2) }}'
        ],
    ],
    'filters'    => [
        'active'     => ['type'=>'select','options'=>[true,false]],
        'created_at' => ['type'=>'date'],  // enables date-range on created_at
    ],
    'actions'    => [
        '<a href="/service/{{\$row->id}}/edit" class="btn btn-sm btn-light">Edit</a>'
    ],
    'searchable' => true,
    'exportable' => true,
    'printable'  => true,
    'checkbox'   => true,
    'records'    => 25,
    'dateColumn' => 'created_at',
])
```

## Configuration Options

### Public Properties

| Property         | Type           | Default     | Description                                      |
|------------------|----------------|-------------|--------------------------------------------------|
| `model`          | `string`       | _required_  | Fully-qualified Eloquent model class.            |
| `columns`        | `array`        | `[]`        | Column definitions (see below).                  |
| `filters`        | `array`        | `[]`        | Column filter configurations.                    |
| `actions`        | `array`        | `[]`        | Row-action Blade snippets.                       |
| `searchable`     | `bool`         | `true`      | Show global search box.                          |
| `exportable`     | `bool`         | `true`      | Show export menu (CSV, XLSX, PDF).               |
| `printable`      | `bool`         | `true`      | Show print button.                               |
| `checkbox`       | `bool`         | `false`     | Enable row-selection checkboxes.                 |
| `records`        | `int`          | `10`        | Rows per page.                                   |
| `dateColumn`     | `string|null`  | `null`      | Enables date-range filter on this column.        |
| `sort`           | `string`       | `'desc'`    | Default sort direction (`'asc'` or `'desc'`).    |
| `colSort`        | `bool`         | `true`      | Allow sorting by clicking on column headers.     |
| `refreshBtn` | `bool`   | `false`    | Show a manual refresh button to reload the table data.      |


### Column Definitions

Each entry in `columns` must include:

- `key` (string, **required**): Model attribute or relation key.
- `label` (string, **required**): Header text.
- `sortable` (bool): Enable sorting.
- `searchable` (bool): Include in global search.
- `raw` (string): Raw Blade snippet for custom rendering.
- `relation` (string): e.g. `"category:name"` to display related model field.
- `classCondition` (array): `[ 'css-class' => fn($row)=> condition ]`.

### Filters

Supported filter types:

- **`select`**: Dropdown of values; load `distinctValues` automatically.
- **`number`**: Numeric comparisons (`=, >, <, >=, <=`).
- **`date`**: Date-picker with start/end range.

Example:

```php
'filters' => [
    'status' => [ 'type'=>'select', 'options'=>['Active','Inactive'] ],
    'price'  => [ 'type'=>'number', 'operators'=>['=','>','<'], 'default'=>'=' ],
]
```

### Actions

Raw Blade snippets per row, for example:

```php
'actions' => [
    '<a href="/items/{{$row->id}}/edit" class="btn">Edit</a>',
    '<button wire:click="delete({{$row->id}})">Delete</button>',
]
```

## Core Methods

- `mount($model, $columns, $filters = [], $actions = [])`
- `updatedSearch()`
- `toggleSort($column)`
- `export($format)` – `csv`, `xlsx`, `pdf`
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
