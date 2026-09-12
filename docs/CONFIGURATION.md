# Configuration reference

Every key the component accepts, and every key in `config/aftable.php`.

```bash
php artisan vendor:publish --tag=aftable-config
```

---

## Mount parameters

```blade
@livewire('aftable', [ ...parameters... ])
```

### Data source — pick one

| Parameter | Type | Description |
|---|---|---|
| `model` | class-string | Eloquent model to query |
| `data` | array \| Collection | In-memory rows ("static" / foreach mode). No queries. |

### Structure

| Parameter | Type | Default | Description |
|---|---|---|---|
| `columns` | array | `[]` | Column definitions — see below |
| `filters` | array | `[]` | Filter definitions — see below |
| `actions` | array | `[]` | Row actions — see below |
| `countAggregations` | array | `[]` | `['relation' => null]` adds a `{relation}_count` value |
| `query` | array \| Closure | `null` | Base constraints applied to every page |
| `tableId` | string | model class | Identity for the DOM id and session keys |

### Behaviour

| Parameter | Type | Default | Description |
|---|---|---|---|
| `searchable` | bool | `true` | Show the search box |
| `exportable` | bool | `false` | Show the export menu |
| `printable` | bool | `false` | Show the print button |
| `colvisBtn` | bool | `true` | Show the column-visibility menu |
| `refreshBtn` | bool | `false` | Show the refresh button |
| `checkbox` | bool | `false` | Row selection checkboxes |
| `index` | bool | `false` | Leading row-number column |
| `perPage` / `records` | int | `10` | Rows per page |
| `sortBy` | string | auto | Initial sort column |
| `sortDirection` | `asc` \| `desc` | `asc` | Initial sort direction |
| `dateColumn` | string | `null` | Column the date-range filter applies to |
| `vars` | array | `[]` | Extra variables available inside `raw` templates |
| `deferData` | bool | `false` | Render the shell immediately and load rows from a lazy island — see [PERFORMANCE.md](PERFORMANCE.md#defer-the-rows-on-heavy-pages) |

### Presentation

| Parameter | Type | Default | Description |
|---|---|---|---|
| `theme` | string | config | `bootstrap`, `tailwind`, `none`, or a registered preset |
| `classes` | array | `[]` | Per-slot class overrides — see [THEMING.md](THEMING.md) |
| `assets` | bool | `true` | Inject the package's own style/script blocks |
| `customTemplate` | string | `null` | Outer table markup (set by the `@aftable` directive) |
| `printConfig` | array | see below | Print-window options |

---

## Columns

Three equivalent shapes:

```php
'columns' => ['id', 'first_name']                 // shorthand
'columns' => [['key' => 'id', 'label' => 'ID']]   // direct (recommended)
'columns' => ['id' => ['label' => 'ID']]          // legacy named
```

### Keys

| Key | Type | Description |
|---|---|---|
| `key` | string | Database column. Also the array key unless disambiguated. |
| `label` | string | Header text. Defaults to a humanised `key`. |
| `relation` | string | `relation:column` or `a.b:column`. Eager loaded automatically. |
| `json` | string | Path inside a JSON/array column. Dot notation supported. |
| `function` | string | Method on the model to call for the value. |
| `raw` | string | Blade template. `$row` and `$vars` are in scope. |
| `hide` | bool | Start hidden (still toggleable). |
| `required` | bool | Cannot be hidden by "hide all". |
| `sortable` | bool | Defaults to true for real database columns. |
| `searchable` | bool | Defaults to true for real database columns. |
| `class`, `th_class`, `td_class` | string | Extra classes, appended to the theme slot. |
| `format` | string | Date format for `DateTimeInterface` values. |

Two columns may read the same underlying column — the array key is
disambiguated automatically (`preferences`, then `preferences_language`).

### Examples

```php
['key' => 'total', 'label' => 'Total', 'sortable' => false,
 'raw' => '@{{ number_format($row->price * $row->qty, 2) }}'],

['key' => 'dept', 'label' => 'Department', 'relation' => 'department:name'],
['key' => 'firm', 'label' => 'Company',    'relation' => 'department.company:name'],

['key' => 'preferences', 'json' => 'theme',      'label' => 'Theme'],
['key' => 'preferences', 'json' => 'ui.sidebar', 'label' => 'Sidebar'],

['key' => 'full_name', 'function' => 'getFullNameAttribute', 'label' => 'Name'],
```

Use `@{{ }}` inside `raw` templates. Plain `{{ }}` is compiled by the *parent*
Blade file before the component ever sees the string. Both work; `@{{ }}` is the
unambiguous one.

---

## Filters

```php
'filters' => [
    'status'     => ['type' => 'distinct'],
    'name'       => ['type' => 'text'],
    'amount'     => ['type' => 'number'],
    'created_at' => ['type' => 'date'],
],
```

| Type | UI | Notes |
|---|---|---|
| `distinct` | select of existing values | Cached; capped by `query.max_distinct_values` |
| `select` | same as `distinct` | |
| `text` | text input | Applies at 3+ characters |
| `number` | number input + operator | `=`, `!=`, `<`, `>`, `<=`, `>=` |
| `date` | date input + operator | |

Only columns named here (or in `columns`) can be filtered on — a hand-edited
query string cannot reach anything else.

---

## Actions

```php
'actions' => [
    'edit' => [
        'label'   => 'Edit',
        'route'   => 'orders.edit',
        'params'  => ['id'],
        'class'   => 'btn btn-sm btn-primary',
        'icon'    => 'fas fa-pen',
        'method'  => 'GET',              // GET | POST | PUT | PATCH | DELETE
        'confirm' => 'Are you sure?',    // adds a confirm() guard
    ],

    // raw HTML action
    '<button class="btn btn-sm">Custom</button>',
],
```

Non-GET methods render a CSRF-protected form. The package does not authorise the
target route — do that in the controller or policy.

---

## Print

```php
'printConfig' => [
    'title'       => 'Orders',
    'header'      => '<custom html>',
    'footer'      => '<custom html>',
    'fontSize'    => 11,               // pt
    'orientation' => 'portrait',       // portrait | landscape
    'paperSize'   => 'A4',
    'showDate'    => true,
    'showPageNum' => true,
    'tableClass'  => '',
],
```

---

## config/aftable.php

### cache

| Key | Default | Description |
|---|---|---|
| `store` | `null` | `null` auto-detects Redis, else the app default. Or name a store. |
| `enabled` | `true` | Master switch for every package cache |
| `prefix` | `aftable` | Key namespace |
| `ttl.default` | `300` | Seconds |
| `ttl.distinct_values` | `600` | Filter dropdown values |
| `ttl.column_metadata` | `1800` | |
| `ttl.schema` | `3600` | Column listings |
| `redis.autodetect` | `true` | Probe for a working Redis |
| `redis.probe_ttl` | `60` | How long a probe result is trusted |
| `redis.connection` | `cache` | Connection used for the probe |

Redis is used **only when it actually works** — the store must be configured, a
client library loadable, and a live round-trip must succeed. See
[PERFORMANCE.md](PERFORMANCE.md).

### query

| Key | Default | Description |
|---|---|---|
| `search_min_chars` | `3` | Characters before search hits the database |
| `max_distinct_values` | `1000` | Cap on a distinct filter's dropdown |
| `per_page` | `10` | Default page size |
| `per_page_options` | `[10,25,50,100,500]` | Page-size dropdown |

### theme

| Key | Default | Description |
|---|---|---|
| `preset` | `bootstrap` | App-wide preset |
| `overrides` | `[]` | App-wide per-slot overrides |

See [THEMING.md](THEMING.md).

### security

| Key | Default | Description |
|---|---|---|
| `guard_actions` | `true` | Restrict client-callable methods to the allow-list |
| `extra_actions` | `[]` | Additional method names you call from the browser |

See [SECURITY.md](SECURITY.md).

### routes

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Register the demo/docs/generator routes |
| `prefix` | `aftable` | URL prefix |
| `middleware` | `['web']` | |

Never registered when `APP_ENV=production`, whatever `enabled` says.

### raw_templates

| Key | Default | Description |
|---|---|---|
| `path` | `storage/framework/cache/aftable` | Compiled `raw` templates |

### generator

Lets `/aftable/generator` and `af-table:make` read your own schema, so they can
offer real models and real columns instead of asking you to type them.

| Key | Default | Description |
|---|---|---|
| `enabled` | `false` | Master switch — see the warning below |
| `model_paths` | `[app_path('Models')]` | Where to look for models. A plain list, or `namespace => path` pairs |
| `model_namespace` | `App\Models` | Namespace applied to entries in `model_paths` given without one |

```php
'model_paths' => [
    'App\Models'   => app_path('Models'),
    'Modules\Blog' => base_path('modules/Blog/src'),
],
```

> **This reads your schema.** With it on, anyone who can reach the page gets
> your table names, your column names and your relation graph. It is off by
> default, gated again on `routes.enabled`, and never registered when
> `APP_ENV=production`. Turn it on locally, generate your Blade, turn it off.

What it will not hand back, even when enabled:

- anything the model lists in `$hidden`;
- columns named `password`, `remember_token`, `api_token`, `secret`,
  `private_key`, `two_factor_*` — reported as existing, never suggested;
- any class that is not an Eloquent model found under `model_paths`.
  `describe()` constructs the class it is given, so the allow-list is the
  security boundary, not a convenience.

---

## Environment variables

```dotenv
AFTABLE_CACHE_STORE=                  # blank = auto-detect
AFTABLE_CACHE_ENABLED=true
AFTABLE_CACHE_PREFIX=aftable
AFTABLE_CACHE_AUTODETECT_REDIS=true
AFTABLE_REDIS_CONNECTION=cache
AFTABLE_THEME=bootstrap
AFTABLE_GUARD_ACTIONS=true
AFTABLE_ROUTES_ENABLED=true
AFTABLE_GENERATOR_ENABLED=false       # schema introspection — local only
```
