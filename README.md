# ArtFlow Table

A Livewire 4 datatable for Laravel. Point it at a model, list your columns, and
you get search, sorting, filtering, pagination, column visibility, export and
print — with eager loading handled for you.

**PHP** 8.2+ · **Laravel** 12 · **Livewire** 4.0+

```bash
composer require artflow-studio/table
```

---

## Quickstart

```blade
@livewire('aftable', [
    'model'   => App\Models\Order::class,
    'columns' => [
        ['key' => 'reference',     'label' => 'Ref'],
        ['key' => 'customer_name', 'label' => 'Customer', 'relation' => 'customer:name'],
        ['key' => 'total',         'label' => 'Total'],
        ['key' => 'status',        'label' => 'Status'],
    ],
    'filters' => [
        'status' => ['type' => 'distinct'],
        'total'  => ['type' => 'number'],
    ],
    'exportable' => true,
])
```

That is two queries — one `COUNT`, one `SELECT` — plus one per relation. It
stays two no matter how many rows are on the page.

Want to see it before you write anything?

```bash
php artisan af-table:migrate --seed --scale=0.05
```

Then open **`/aftable/test`**: every feature rendered against real data, with a
live query count, render time and cache backend at the top.

---

## What it does

| | |
|---|---|
| **Columns** | plain, relation (`a.b:column`), JSON path, model method, raw Blade |
| **Search** | debounced, multi-column, relation-aware, works in any script |
| **Sorting** | click-to-sort, relation sorting via join, direction in the URL |
| **Filtering** | distinct / text / number / date, stackable, operator-aware |
| **Aggregates** | `withCount()` without loading the relation |
| **Export** | CSV always; Excel and PDF when the optional packages are installed |
| **Print** | isolated pop-up, configurable paper size and orientation |
| **Static mode** | feed it a Collection — zero queries |
| **Theming** | Bootstrap, Tailwind, or none at all |
| **Caching** | auto-detects Redis, degrades gracefully without it |

---

## Four ways to mount it

```blade
@livewire('aftable', [...])                  {{-- Livewire's own directive --}}
@AFtable([...])                              {{-- shorthand --}}
<livewire:aftable :model="..." />            {{-- tag syntax --}}

@aftable([...])                              {{-- you supply the table element --}}
    <table class="table table-hover my-grid"></table>
@endaftable
```

---

## Using your own styles

The package ships Bootstrap markup, and nothing forces you to keep it. Every
part of the table is a named slot:

```blade
{{-- change one thing --}}
@livewire('aftable', ['classes' => ['table' => 'table table-sm my-look'], ...])

{{-- switch framework --}}
@livewire('aftable', ['theme' => 'tailwind', ...])

{{-- emit no framework classes at all, style it yourself --}}
@livewire('aftable', ['theme' => 'none', 'assets' => false, ...])
```

Full slot list and app-wide defaults: **[docs/THEMING.md](docs/THEMING.md)**.

---

## Redis, if you have it

The package caches distinct filter values, column metadata and schema lookups.
It auto-detects a **working** Redis cache store — configured, client library
loadable, and a live round-trip that succeeds — and uses it for cache tags, so
invalidation is precise. Without Redis it falls back to your default store and
keeps working.

```php
ArtflowStudio\Table\Support\TableCache::describe();
// ['store' => 'redis', 'redis' => true, 'tags' => true, 'enabled' => true]
```

No configuration required. Details in
**[docs/PERFORMANCE.md](docs/PERFORMANCE.md)**.

---

## Security

Livewire exposes every public method as an action a visitor can invoke, so the
package ships an **action allow-list**: only the 49 deliberate UI actions are
callable, everything else is refused with a 403. Filter and sort columns are validated against your declared configuration,
so a hand-edited query string cannot reach a column you never exposed.

**[docs/SECURITY.md](docs/SECURITY.md)** covers the full posture and what
remains your responsibility (authorisation, mainly).

---

## Writing the snippet for you

Point the generator at a model and it reads the real schema — columns, types,
casts and relations — and hands back a mountable table.

```bash
php artisan af-table:make Order              # prints the snippet
php artisan af-table:make Order --relations  # one column per belongsTo, too
php artisan af-table:make Order -o resources/views/orders/_table.blade.php
```

Or use `/aftable/generator`, which does the same with a live preview, a model
picker and per-column controls.

Both need schema introspection switched on, because both read your schema:

```dotenv
AFTABLE_GENERATOR_ENABLED=true   # local only
```

It is off by default and never registered when `APP_ENV=production`. `$hidden`
attributes and credential-shaped columns (`password`, `api_token`, …) are never
offered, and only models found under `aftable.generator.model_paths` can be
described at all. See [CONFIGURATION.md](docs/CONFIGURATION.md#generator).

---

## Documentation

| | |
|---|---|
| [CONFIGURATION.md](docs/CONFIGURATION.md) | every parameter and config key |
| [RECIPES.md](docs/RECIPES.md) | worked examples |
| [THEMING.md](docs/THEMING.md) | slots, presets, custom styling |
| [PERFORMANCE.md](docs/PERFORMANCE.md) | query costs, caching, N+1 guarantees |
| [SECURITY.md](docs/SECURITY.md) | action guard, input handling, your obligations |
| [CONTRIBUTING.md](CONTRIBUTING.md) | how to work on the package |
| [UPGRADING.md](UPGRADING.md) | 1.5.6 → 1.6.0 |
| [CHANGELOG.md](CHANGELOG.md) | what changed |
| [AUDIT.md](AUDIT.md) | the full technical audit behind 1.6.0 |

---

## Commands

```bash
php artisan af-table:make Order                    # generate a table from a model
php artisan af-table:migrate --seed --scale=0.05   # demo tables + data
php artisan af-table:clear-phantom-columns         # clear this package's caches
```

`--scale=1` seeds the full ~118,600-row performance fixture.
`af-table:clear-phantom-columns` touches only keys and files this package wrote
— your application's config, route and view caches are left alone.

---

## Testing

```bash
composer test
```

486 tests on Orchestra Testbench against SQLite in memory, including N+1 guards
that assert the query count does not change as the row count grows, with
Laravel's `preventLazyLoading()` enabled.

Contributions welcome — see [CONTRIBUTING.md](CONTRIBUTING.md).

---

## License

MIT.
