# Theming

The table ships with Bootstrap 5 markup because that is what it has always
emitted. Nothing forces you to keep it.

Every styleable part of the table is a named **slot**. A theme is a flat map of
slot → class string. You can swap the whole map, override one slot, or turn all
of them off and style the table entirely from your own CSS.

---

## Quick answers

| I want to… | Do this |
|---|---|
| Change one thing (say the `<table>` tag) | `'classes' => ['table' => 'table table-sm my-look']` |
| Use Tailwind instead of Bootstrap | `'theme' => 'tailwind'` |
| Use my own CSS, no framework classes | `'theme' => 'none'` |
| Change the default for the whole app | `config/aftable.php` → `theme.preset` |
| Restyle one slot app-wide | `config/aftable.php` → `theme.overrides` |
| Ship a reusable house style | `Theme::register('acme', [...])` |
| Replace the markup itself, not just classes | `vendor:publish --tag=views` |
| Wrap the table in my own `<table>` element | `@aftable(...) <table class="…"></table> @endaftable` |

---

## 1. Override individual slots

The narrowest tool, and usually the right one. Name only what you want to
change; every other slot keeps the preset's value.

```blade
@livewire('aftable', [
    'model'   => App\Models\Order::class,
    'columns' => [...],
    'classes' => [
        'table'      => 'table table-sm align-middle shadow-sm',
        'th'         => 'text-uppercase small text-body-secondary',
        'tr'         => 'order-row',
        'responsive' => 'rounded-3 border',
    ],
])
```

## 2. Pick a preset

```blade
@livewire('aftable', ['theme' => 'tailwind', ...])
```

| Preset | What it emits |
|---|---|
| `bootstrap` | Bootstrap 5 (default; the historical markup) |
| `tailwind` | Tailwind 3/4 utilities, dark-mode aware, no build step |
| `ui-flow` | Artflow Studio's ui-flow admin theme — same Bootstrap 5 markup, soft buttons and a borderless hover table instead of outline buttons and a bordered one |
| `none` | No framework classes — only structural `aft-*` hooks |

Presets and per-slot overrides compose: `'theme' => 'tailwind'` with
`'classes' => ['table' => '…']` gives you Tailwind everywhere except the table
tag.

## 3. Set the app-wide default

```php
// config/aftable.php
'theme' => [
    'preset' => 'tailwind',

    // Applied on top of the preset, for every table in the app.
    'overrides' => [
        'button' => 'btn btn-sm btn-ghost',
        'th'     => 'text-xs uppercase tracking-wide text-slate-500',
    ],
],
```

## 4. Register a house style

From a service provider's `boot()`:

```php
use ArtflowStudio\Table\Support\Theme;

Theme::register('acme', [
    'table' => 'acme-table',
    'th'    => 'acme-table__head',
    'td'    => 'acme-table__cell',
], extends: 'none');   // start from `none` so nothing Bootstrap leaks in
```

Then `'theme' => 'acme'`, or make it the app default in config.

`extends:` merges your map over an existing preset, so you only declare the
slots that differ.

## 5. Style it entirely yourself

`'theme' => 'none'` emits no framework classes at all — just stable structural
hooks:

```blade
@livewire('aftable', ['theme' => 'none', 'model' => …, 'columns' => […]])
```

```css
.aft-table { width: 100%; border-collapse: collapse }
.aft-th    { text-align: left; font-weight: 600; padding: .5rem }
.aft-td    { padding: .5rem; border-top: 1px solid #e5e7eb }
.aft-tr:hover > * { background: #f9fafb }
```

Pair it with `'assets' => false` to stop the package injecting its own `<style>`
block as well:

```blade
@livewire('aftable', ['theme' => 'none', 'assets' => false, …])
```

At that point the package contributes **zero** CSS and zero classes you did not
ask for.

---

## Slot reference

| Slot | Element |
|---|---|
| `wrapper` | outermost `<div>` |
| `toolbar`, `toolbar.search`, `toolbar.end` | the search / buttons row |
| `filters`, `filters.row`, `filters.instance` | filter area and each stacked filter |
| `filters.col`, `filters.col.sm`, `filters.col.value`, `filters.col.auto` | filter grid cells |
| `label` | every `<label>` |
| `input`, `select`, `checkbox`, `search` | form controls |
| `button`, `button.primary`, `button.danger`, `button.warning`, `button.success` | toolbar and filter buttons |
| `button.action` | row action buttons |
| `dropdown`, `dropdown.toggle`, `dropdown.menu`, `dropdown.item` | column-visibility and export menus |
| `responsive` | the horizontal scroll container |
| `table`, `thead`, `tbody`, `th`, `tr`, `td` | the table itself |
| `th.sortable` | a sortable column's `<th>` — applied in addition to `th`, not instead of it |
| `empty` | the "no data" cell |
| `badge.muted` | the small "(Not Sortable)" hints |
| `pagination` | **view name**, not a class — see below |
| `pagination.footer` | wrapper around the pagination links |

### Per-column classes still work

Slot classes are the baseline; `class`, `th_class` and `td_class` on a column
are appended to it:

```php
['key' => 'total', 'label' => 'Total', 'td_class' => 'text-end font-monospace']
```

### Replacing the pagination markup

`pagination` is a Blade **view name**, so point it at your own:

```php
'classes' => ['pagination' => 'components.my-pagination']
```

It receives a standard `LengthAwarePaginator`, so Laravel's own
`pagination::tailwind` works too.

---

## Icons

Sort chevrons, the column-visibility icon and the search icon come from a
dependency-free inline-SVG component (`x-artflow-table::icon`) — nothing 404s
even if the host application never loads an icon library.

Under the `ui-flow` preset specifically, the same icon also progressively
upgrades to a real Lucide icon: passing a `lucide` name renders the inline SVG
inside an `<i data-lucide="...">` wrapper, and a ui-flow host's own
`createIcons()` call — wired to `window.ArtflowAdmin.refresh()`, run after
every Livewire commit — swaps that wrapper for its own Lucide `<svg>`. Every
other theme never calls `ArtflowAdmin.refresh()`, so the `<i>` keeps its
inline SVG child and renders exactly as if `lucide` had never been passed —
never a blank icon either way.

`width`/`height` are set directly on the `<i data-lucide>` wrapper itself, not
only on the inline SVG nested inside it. `createIcons()` copies attributes
from the element it is replacing, so without size on the wrapper a
ui-flow-rendered icon silently falls back to Lucide's own default (24px)
instead of the size actually requested.

---

## When classes are not enough

Three escape hatches, in increasing order of commitment.

**Custom table shell** — you control the `<table>` element, the package fills it:

```blade
@aftable(['model' => App\Models\Order::class, 'columns' => [...]])
    <table class="table table-hover data-grid" data-testid="orders"></table>
@endaftable
```

**Raw columns** — you control a cell completely:

```php
['key' => 'status', 'label' => 'Status', 'raw' =>
    '<span class="chip chip--@{{ $row->status }}">@{{ $row->status }}</span>'],
```

Note the `@{{ }}`. Inside a Blade file, plain `{{ }}` is compiled by the *parent*
view before the component ever sees the string. Both work, but `@{{ }}` is the
predictable one — it leaves compilation to the component.

**Publish the views** — total control, at the cost of owning the merge on every
upgrade:

```bash
php artisan vendor:publish --tag=views
```

Files land in `resources/views/vendor/artflow-table/`.

---

## Notes

- Themes are resolved once per request and memoised, so a per-slot lookup on
  every row costs nothing.
- `Theme::available()` lists every registered preset, which is handy in a
  settings UI.
- The bench at `/aftable/test` §10 renders the custom-classes and `none` cases
  side by side against real data.
