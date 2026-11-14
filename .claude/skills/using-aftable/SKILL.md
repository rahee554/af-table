---
name: using-aftable
description: Use whenever building or editing a data table / list view in a Laravel app that has artflow-studio/table installed, whenever the person mentions "aftable", "AFTable", "@livewire('aftable'...)", or asks how to add sorting/filtering/search/export/column-visibility/pagination to a table, or how to theme one (Bootstrap/Tailwind/ui-flow/none/a custom preset).
---

# Using AFTable (artflow-studio/table)

The full reference lives in this package's own `docs/` — read the relevant
file before answering anything non-trivial, don't re-derive facts from
source when a doc already states them:

| Doc | Read it for |
|---|---|
| [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md) | Every mount parameter, every column key, filters, actions, print config, `config/aftable.php` reference |
| [`docs/THEMING.md`](../../docs/THEMING.md) | Presets, per-slot overrides, registering a house style, the full slot table |
| [`docs/RECIPES.md`](../../docs/RECIPES.md) | Worked examples: relations, JSON columns, raw-HTML cells, row actions, in-memory data, multiple tables per page |
| [`docs/PERFORMANCE.md`](../../docs/PERFORMANCE.md) | Cache behaviour, when a table is slow and why, `deferData`, static mode |
| [`docs/SECURITY.md`](../../docs/SECURITY.md) | The `guard_actions` allow-list, input handling, what you're responsible for |

This file is the decision checklist that gets you to the right doc fast —
not a copy of any of them.

## What this package is

A Livewire 4 datatable component. One `@livewire('aftable', [...])` call
gets you: sortable/searchable columns, per-column and global filters,
pagination with a page-size picker, column visibility toggling, CSV/Excel/PDF
export, row actions (link or form), a print view, relation and JSON columns,
and query-result caching — all server-driven, no separate JS datatable
library.

## Mounting it — four ways, same component underneath

```blade
{{-- 1. Plain Livewire --}}
@livewire('aftable', [
    'model'   => App\Models\Order::class,
    'columns' => [['key' => 'id', 'label' => 'ID'], ['key' => 'total', 'label' => 'Total']],
])

{{-- 2. Shorthand directive — identical to #1 --}}
@AFtable(['model' => App\Models\Order::class, 'columns' => [...]])

{{-- 3. Custom outer <table> tag — you own the element, the component fills it --}}
@aftable(['model' => App\Models\Order::class, 'columns' => [...]])
    <table class="table table-hover my-theme" data-testid="orders"></table>
@endaftable

{{-- 4. In-memory rows, no query at all --}}
@livewire('aftable', ['data' => $collection, 'columns' => [...]])
```

Full parameter list (data source, structure, behaviour, presentation flags):
`docs/CONFIGURATION.md` → "Mount parameters".

## Columns — the one thing to get right

```php
'columns' => [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'dept', 'label' => 'Department', 'relation' => 'department:name'],   // eager-loaded automatically
    ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],                // JSON column path
    ['key' => 'full_name', 'function' => 'getFullNameAttribute', 'label' => 'Name'], // model method
    ['key' => 'total', 'label' => 'Total', 'sortable' => false,
     'raw' => '@{{ number_format($row->price * $row->qty, 2) }}'],                 // arbitrary Blade
]
```

`raw` templates use `@{{ }}`, not `{{ }}` — plain `{{ }}` gets compiled by the
*parent* Blade file before the component ever receives the string. Full key
reference (`hide`, `required`, `class`/`th_class`/`td_class`, `format`, …):
`docs/CONFIGURATION.md` → "Columns".

Row actions (structured, not raw HTML, unless you need something the
built-in shape can't express):

```php
'actions' => [
    'view' => ['label' => 'View', 'route' => 'orders.show', 'params' => ['id'], 'icon' => 'eye'],
    'delete' => ['label' => 'Delete', 'route' => 'orders.destroy', 'params' => ['id'],
        'method' => 'DELETE', 'confirm' => 'Delete this order?'],
]
```

## Theming — how a host app gets its own look

Every styleable part of the table is a named **slot** (`table`, `th`,
`button`, `dropdown.toggle`, `toolbar.search`, …), resolved through a small
number of layers, most specific first:

1. `classes` passed to this specific `@livewire('aftable', [...])` call
2. the named `theme` preset passed to this call
3. `aftable.theme.overrides` in `config/aftable.php` — app-wide, per-slot
4. `aftable.theme.preset` in `config/aftable.php` — app-wide default preset
5. the built-in `bootstrap` preset — unchanged fallback

**Built-in presets:** `bootstrap` (the historical default — real Bootstrap 5
markup), `tailwind` (utility classes, dark-mode aware, no build step), `none`
(zero framework opinion — only stable `aft-*` hooks, for a host that styles
everything itself), and **`ui-flow`** (Artflow Studio's own admin theme —
same Bootstrap 5 markup as `bootstrap`, but soft buttons instead of outline,
a borderless hover table, `table-light` heads, and the real `.toolbar`/
`.toolbar-search`/`.sortable` classes ui-flow itself ships, so a table
dropped into a ui-flow page blends in rather than looking like a
foreign widget).

**To make a table use this project's own design** — three levels of
commitment, pick the narrowest that does the job:

```blade
{{-- A. One table, one override --}}
@livewire('aftable', ['classes' => ['table' => 'table table-sm my-look'], ...])

{{-- B. One table, a whole preset --}}
@livewire('aftable', ['theme' => 'ui-flow', ...])

{{-- C. Every table in the app, by default --}}
{{-- config/aftable.php --}}
'theme' => ['preset' => 'ui-flow', 'overrides' => []],
```

**Important — the current state in this app:** no table call passes
`'theme' => 'ui-flow'` and `config/aftable.php` is not published here, so
every `@livewire('aftable', ...)` call in this project renders with the
**`bootstrap`** preset today, not `ui-flow`, even though ui-flow's own CSS
is loaded app-wide (which is why it still looks acceptable — ui-flow reskins
plain Bootstrap classes via CSS variables, it doesn't require its own preset
to render correctly). To get the ui-flow-specific refinements (soft buttons,
borderless hover table, native `.toolbar`/`.sortable` classes and their
correctly-sized icons), either add `'theme' => 'ui-flow'` per call, or
publish the config (`php artisan vendor:publish --tag=aftable-config`) and
set `'preset' => 'ui-flow'` there once for the whole app.

**A house style beyond the four built-ins** — register it from a service
provider's `boot()`, once:

```php
use ArtflowStudio\Table\Support\Theme;

Theme::register('acme', [
    'table' => 'acme-table',
    'button.primary' => 'acme-btn acme-btn-primary',
], extends: 'ui-flow');   // only declare what differs from the base preset
```

Then `'theme' => 'acme'` anywhere, or make it the app default in config.

Full slot table, the "replace the pagination view" escape hatch, and the
`assets => false` / `@aftable` custom-`<table>` escape hatches for when
classes alone aren't enough: `docs/THEMING.md`.

**Icons inside the table** (sort chevrons, the column-visibility icon, the
search icon) come from a small dependency-free inline-SVG component
(`x-artflow-table::icon`) — it never needs Font Awesome or Lucide to render
something. When a `lucide` prop is also passed, the icon progressively
upgrades to the host theme's real Lucide icon under ui-flow specifically
(via `data-lucide`), and silently stays the inline SVG under every other
theme — never a blank icon either way.

## Security defaults — do not turn off casually

This component composes ~11 traits and exposes 400+ public methods, almost
all internal helpers. Livewire treats every public method as a browser-
callable action, so `config('aftable.security.guard_actions')` (on by
default) restricts the browser to a deliberate allow-list
(`Support\GuardsActions::ALLOWED`). If your own Blade calls a method that
isn't on it, add the method name to `aftable.security.extra_actions` in
config rather than disabling the guard. Full detail: `docs/SECURITY.md`.

## Console commands

- `php artisan af-table:migrate [--seed] [--scale=]` — the package's own demo tables (`/aftable/test` bench)
- `php artisan af-table:make` — code generator (reads your app's real models/schema; also at `/aftable/generator`, off in production)
- `php artisan af-table:clear-phantom-columns` — drop cached column metadata that no longer matches the database

## Verifying a change

Run this package's own test suite (`vendor/bin/phpunit` from the package
root, 487 tests as of this writing) before trusting a theming or markup
change, then visually confirm at `/aftable/test` (non-production only) —
it renders every feature, including a side-by-side theme comparison
section, against real seeded data.
