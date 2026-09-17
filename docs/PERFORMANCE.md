# Performance

The short version: a plain table is **two queries** — one `COUNT` for the
paginator, one `SELECT` for the page — and that number does not change when you
add rows, relation columns, JSON columns or raw columns.

If you are seeing more than that, this page explains where it comes from.

---

## The guarantee

`tests/Feature/NPlusOneTest.php` renders the same table at 5 rows and at 40 rows
and requires an **identical** query count. A budget ("under 10 queries") rots as
a package grows; a growth assertion cannot. The suite also runs the relation
tests under Laravel's own `Model::preventLazyLoading()`, so an accidental lazy
load throws rather than quietly costing a query.

Covered: single-level relations, nested relations, several relations at once,
`countAggregations`, JSON and raw columns, search, relation sorting, export, and
repeated interactions.

---

## What it costs

| Scenario | Queries |
|---|---|
| Plain columns | 2 (COUNT + SELECT) |
| + one relation column | 3 |
| + a nested `a.b:column` | 4 (one per level) |
| + `countAggregations` | 2 — `withCount()` is a subselect |
| + JSON / raw / function columns | 2 — rendered from loaded data |
| + a `distinct` filter, first load | 3 (one `SELECT DISTINCT`, then cached) |
| + a `distinct` filter, thereafter | 2 |
| Static (`data =>`) mode | **0** |

Measured on the bench (`/aftable/test`): 12 tables, 77,000 rows, 43 queries,
~330 ms, 40 MB.

---

## Making it faster

### Let the cache do its job

Distinct filter values and schema lookups are cached. Defaults live in
`config/aftable.php`:

```php
'cache' => [
    'ttl' => [
        'default'         => 300,
        'distinct_values' => 600,   // filter dropdowns
        'schema'          => 3600,  // column listings
    ],
],
```

If your reference data barely moves, raise `distinct_values` and `schema`. The
cost of a stale dropdown is usually much lower than the cost of the query.

### Use Redis if you have it

The package auto-detects a working Redis cache store and uses it (see
[CONFIGURATION.md](CONFIGURATION.md#cache)). Redis brings **cache tags**, which
means invalidation is precise — clearing one model's distinct values leaves
every other table's cache alone. On file or database stores there is no portable
way to enumerate keys by prefix, so the package has to be conservative.

Check what you are actually on:

```php
ArtflowStudio\Table\Support\TableCache::describe();
// ['store' => 'redis', 'redis' => true, 'tags' => true, 'enabled' => true]
```

The bench shows the same information in its diagnostics strip.

### Cap distinct filters

A `distinct` filter on a high-cardinality column (an email, a timestamp) means
"put every value in this column into a `<select>`". The cap stops that:

```php
'query' => ['max_distinct_values' => 1000],
```

Better still: use a `text` filter for high-cardinality columns and reserve
`distinct` for genuine enumerations.

### Index what you sort and filter on

The package cannot conjure an index. If you sort by `created_at` on a million
rows, that column needs one. Relation sorting adds a `leftJoin`, so the join key
needs one too.

### Prefer `key` columns over `raw` for plain values

A `raw` column compiles a Blade template. It is cached by content hash and
compiled once per template — not per row — but a plain `key` column skips the
work entirely. Reach for `raw` when you need markup, not to format a number.

### Keep the page size sane

`perPage` above a few hundred means serialising that many rows into the Livewire
snapshot on every request. `per_page_options` defaults to
`[10, 25, 50, 100, 500]` for that reason.

### Defer the rows on heavy pages

`deferData => true` splits the response in two. The first request returns the
toolbar, the filters and an empty table shell; the rows arrive on a second
round trip from a lazy [island](https://livewire.laravel.com/docs/islands),
with `x-artflow-table::skeleton-table` standing in until they do.

```blade
@livewire('aftable', [
    'model' => \App\Models\Order::class,
    'columns' => $columns,
    'deferData' => true,
])
```

The query does not run at all on the first request — `render()` skips
`$this->tableData` entirely while deferring — so the page's time to first paint
stops being the sum of every table on it. With three heavy tables on one screen
you get one fast shell and three fills in parallel instead of three queries in
series.

It costs an extra round trip, so it is off by default and it is the wrong
choice for a page whose only content *is* the table: there, deferring just
shows a skeleton for 200 ms before showing the same thing.

> `lazy` and `defer` are reserved Livewire mount parameters — Livewire strips
> both before the component sees them and uses them to defer the *whole*
> component. That is why this one is called `deferData`. If you want the entire
> table lazy, including the toolbar, pass Livewire's own `lazy` instead.

### Static mode for small, fixed sets

If the data is already in memory and small, hand it over directly:

```blade
@livewire('aftable', ['data' => $rows, 'columns' => [...]])
```

Search, sort, filter and pagination then run in memory with **zero** queries.
Keep it to a few hundred rows — everything is serialised into the snapshot.

---

## Things that used to be slow

Recorded because the symptoms are worth recognising.

**Schema introspection per column, per request.** `isColumnSearchable()` called
`Schema::getColumnListing()` once per column, from several call sites. A
five-column table issued **20 schema queries on every search, sort and page
change**:

```
before   update: search   22 queries   218 ms
after    update: search    4 queries    15 ms
before   update: page      22 queries   372 ms
after    update: page       2 queries    14 ms
```

**Eager loading lost after the first request.** The relation list was computed
in `mount()` and stored in a `protected` property. Livewire only persists
*public* properties, so from the second request onward the list was empty, no
`with()` was applied, and every relation column lazy-loaded once per row. It
looked fine on first paint and degraded the moment you clicked anything —
86 queries for 40 rows.

**A cache that was never read.** The distinct-value "cached" methods were
aliases pointing at the *uncached* implementations, so the whole caching layer
sat unused for its single most valuable case.

All three are covered by tests now.

---

## Measuring your own tables

```php
DB::enableQueryLog();
// … render …
dd(DB::getQueryLog());
```

Or switch on Laravel's strict mode in `AppServiceProvider::boot()` and let it
tell you:

```php
Model::preventLazyLoading(! app()->isProduction());
```

If that throws inside a table, open an issue — it is a bug in the package, not
in your configuration.
