# Recipes

Working examples for the things people actually build. Each one renders on the
bench at `/aftable/test`.

---

## The basic table

```blade
@livewire('aftable', [
    'model'   => App\Models\Order::class,
    'columns' => [
        ['key' => 'reference', 'label' => 'Ref'],
        ['key' => 'customer',  'label' => 'Customer'],
        ['key' => 'total',     'label' => 'Total'],
        ['key' => 'created_at','label' => 'Placed'],
    ],
])
```

Two queries: a `COUNT` for the paginator and a `SELECT` for the page.

---

## Four ways to mount it

All four are equivalent; pick whichever reads best where you are.

```blade
{{-- 1. Livewire's own directive --}}
@livewire('aftable', ['model' => Order::class, 'columns' => [...]])

{{-- 2. Shorthand --}}
@AFtable(['model' => Order::class, 'columns' => [...]])

{{-- 3. Tag syntax --}}
<livewire:aftable :model="Order::class" :columns="$columns" />

{{-- 4. Paired — the body becomes the outer table element --}}
@aftable(['model' => Order::class, 'columns' => [...]])
    <table class="table table-hover data-grid"></table>
@endaftable
```

---

## Relations

```php
'columns' => [
    ['key' => 'reference',     'label' => 'Ref'],
    ['key' => 'customer_name', 'label' => 'Customer', 'relation' => 'customer:name'],
    ['key' => 'country',       'label' => 'Country',  'relation' => 'customer.address:country'],
],
```

Every level is eager loaded automatically — `customer` and `customer.address`
become one query each, regardless of how many rows are on the page.

Single-level relations are sortable (the package joins). Nested ones are not,
by design: the join cost is rarely worth it.

---

## Counting related rows without loading them

```php
@livewire('aftable', [
    'model' => App\Models\Category::class,
    'columns' => [
        ['key' => 'name',            'label' => 'Category'],
        ['key' => 'products_count',  'label' => 'Products', 'sortable' => false],
    ],
    'countAggregations' => ['products' => null],
])
```

`withCount()` is a subselect, so this adds no queries at all.

---

## JSON columns

```php
['key' => 'settings', 'json' => 'theme',            'label' => 'Theme'],
['key' => 'settings', 'json' => 'notify.email',     'label' => 'Email alerts'],
```

Works with an Eloquent `array`/`json` cast or a raw JSON string column. Dot
notation walks nested documents. Two paths out of the same column are fine —
the second becomes `settings_notify_email` internally.

---

## Formatting a cell

```php
// currency
['key' => 'total', 'label' => 'Total', 'raw' =>
    '@{{ $currency }} @{{ number_format($row->total, 2) }}'],

// conditional badge
['key' => 'status', 'label' => 'Status', 'raw' =>
    '<span class="badge bg-@{{ $row->status === "paid" ? "success" : "warning" }}">'
    .'@{{ ucfirst($row->status) }}</span>'],

// arithmetic across columns
['key' => 'line_total', 'label' => 'Line total', 'sortable' => false, 'raw' =>
    '@{{ number_format($row->price * $row->qty, 2) }}'],

// a date
['key' => 'created_at', 'label' => 'Placed', 'raw' =>
    '@{{ \Carbon\Carbon::parse($row->created_at)->diffForHumans() }}'],
```

Pass shared values through `vars`:

```blade
@livewire('aftable', [
    'vars' => ['currency' => 'PKR', 'taxRate' => 0.15],
    ...
])
```

Use `@{{ }}`, not `{{ }}` — see [CONFIGURATION.md](CONFIGURATION.md#columns).

### Full Blade inside a raw column

A `raw` template is not limited to `@if`/`@else`/`{{ }}`/`@php` — it compiles
through Blade's own `compileString()`, so a Blade component tag, `@include`,
`@each` and `@slot` all work too:

```php
['key' => 'status', 'label' => 'Status', 'raw' =>
    '<x-app::status-badge :status="$row->status" />'],
```

`$__env` — the Blade view factory that every component tag, `@include`,
`@each` and `@slot` compile down to — is injected into the template's render
scope alongside `$row` and `$vars`, so this renders exactly as it would inside
an ordinary `.blade.php` file.

**A sharp edge if you document this in your own Blade comments:** Blade finds
component tags with a regex over the raw file text, not by respecting comment
boundaries — a `<x-.../>` written inside an HTML `<!-- -->` or a Blade
`{{-- --}}` comment is still a candidate match. Blade's own comment-closing
regex is also non-greedy, so if that comment's text happens to contain a
literal `{{--` or `--}}` (easy to do while explaining this exact rule), the
comment can close early and expose whatever follows it as real markup. Keep an
example raw template with a component tag out of a `{{-- --}}` block, or keep
that block free of literal `{{--`/`--}}` text.

---

## Scoping what the table can see

**Per table:**

```blade
@livewire('aftable', [
    'model' => App\Models\Invoice::class,
    'query' => fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id),
    ...
])
```

Also accepts array shorthand:

```php
'query' => ['status' => 'active'],
'query' => [['total', '>', 1000], ['status', 'paid']],
```

The constraint applies to the page, the count, the search, and the export.

**Better, when it is a rule rather than a view:** put a global scope on the
model so it cannot be forgotten at a call site.

---

## Row actions

```php
'actions' => [
    'view' => [
        'label'  => 'View',
        'route'  => 'orders.show',
        'params' => ['id'],
        'class'  => 'btn btn-sm btn-outline-secondary',
    ],
    'delete' => [
        'label'   => 'Delete',
        'route'   => 'orders.destroy',
        'params'  => ['id'],
        'method'  => 'DELETE',
        'confirm' => 'Delete this order?',
        'class'   => 'btn btn-sm btn-outline-danger',
    ],
],
```

Authorise the target route yourself — rendering a link is not permission to
follow it.

---

## In-memory data (no database)

```blade
@php
$rows = collect([
    ['sku' => 'WID-1', 'name' => 'Widget', 'price' => 10.00, 'qty' => 3],
    ['sku' => 'GAD-2', 'name' => 'Gadget', 'price' => 25.50, 'qty' => 1],
]);
@endphp

@livewire('aftable', [
    'data'    => $rows,
    'columns' => [
        ['key' => 'sku',   'label' => 'SKU'],
        ['key' => 'name',  'label' => 'Product'],
        ['key' => 'total', 'label' => 'Total', 'sortable' => false,
         'raw' => '@{{ number_format($row->price * $row->qty, 2) }}'],
    ],
])
```

Search, sort, filter and pagination all run in memory — **zero** queries.
Accepts arrays, Collections and Eloquent Collections. Keep it to a few hundred
rows; everything is serialised into the Livewire snapshot.

---

## Exporting

```blade
@livewire('aftable', ['exportable' => true, ...])
```

CSV always works and is written with a UTF-8 BOM so Excel opens accented and
non-Latin text correctly. The export respects the active search, filters and
column visibility, and renders relation, JSON and raw columns as text.

Excel and PDF need optional packages, and the menu only offers a format the
installation can actually produce:

```bash
composer require maatwebsite/excel     # .xlsx
composer require barryvdh/laravel-dompdf   # .pdf
```

Without them, choosing Excel or PDF falls back to CSV **and says so** rather
than handing over a mislabelled file.

---

## Printing

```php
'printable'   => true,
'printConfig' => [
    'title'       => 'Order register',
    'orientation' => 'landscape',
    'paperSize'   => 'A4',
    'fontSize'    => 10,
],
```

Opens a pop-up containing only the table — no surrounding layout, no action
column, no Livewire attributes.

---

## Several tables on one page

Give each a `tableId`. It scopes the DOM id, the session keys for column
visibility, and the Livewire component key.

```blade
@livewire('aftable', ['tableId' => 'open-orders',   ...], key('open-orders'))
@livewire('aftable', ['tableId' => 'closed-orders', ...], key('closed-orders'))
```

---

## Reacting to the table

```php
// in another component
protected $listeners = ['selectionCleared' => 'onSelectionCleared'];
```

Dispatched events: `selectionCleared`, `showAlert`.
Listened for: `dateRangeSelected`, `refreshTable`.

```js
Livewire.dispatch('refreshTable');
```

---

## Restyling

See [THEMING.md](THEMING.md). The short version:

```blade
{{-- one slot --}}
@livewire('aftable', ['classes' => ['table' => 'table table-sm my-look'], ...])

{{-- a whole preset --}}
@livewire('aftable', ['theme' => 'tailwind', ...])

{{-- nothing but your own CSS --}}
@livewire('aftable', ['theme' => 'none', 'assets' => false, ...])
```

---

## Trying it out

```bash
php artisan af-table:migrate --seed --scale=0.05
```

Then open `/aftable/test`. `--scale=1` is the full ~118,600-row performance
fixture; `0.05` gives a few thousand rows, which is enough for everything except
the scale section.
