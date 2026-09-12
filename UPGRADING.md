# Upgrading

## 1.5.6 → 1.6.0

**Most applications need to change nothing.** The public API — `@livewire('aftable', …)`,
column definitions, filters, actions — is unchanged, and the Blade directives
that never worked now do.

Read on only if one of the headings below applies to you.

---

### Requirements

`1.6.0` requires **Livewire 4.0+**, Laravel 12 and PHP 8.2+.

If you are on Livewire 3, stay on 1.5.x. The package now uses islands,
`#[Computed]`, `@assets` / `@script`, the `commit` hook and component hooks,
none of which exist in 3.x.

---

### If you used `aftable-simple` or `aftable-legacy`

They still work. Both aliases now resolve to the maintained `aftable` component,
so your markup keeps rendering — and silently picks up every fix the forks never
received.

Move to `aftable` when convenient; the aliases go away in 2.0.

```diff
- @livewire('aftable-legacy', [...])
+ @livewire('aftable', [...])
```

**You do need to act if** you extended `ArtflowStudio\Table\Http\Livewire\Datatable`
or `...\DatatableTrait` directly. Those classes are gone. Extend
`ArtflowStudio\Table\Http\Livewire\AftableComponent` instead — it is a superset.

---

### If you used the `af-table:test*` commands

`af-table:test`, `af-table:test-trait` and `af-table:test-enhanced` were
removed. They tested the legacy forks, asserted mostly via `method_exists()`,
and 8 of one command's 13 suites reported PASS without running anything.

Use the real suite instead:

```bash
cd vendor/artflow-studio/table && composer install && composer test
```

`af-table:migrate` and `af-table:clear-phantom-columns` are unchanged in name.
`af-table:migrate` gained `--scale`; `--tenant` was dropped from
`af-table:clear-phantom-columns` because it never scoped anything.

---

### If you imported a trait directly

`ArtflowStudio\Table\Traits\**` is gone, replaced by
`ArtflowStudio\Table\Concerns\**` — 26 traits consolidated into 11 concerns
with no duplicate method names.

| Old | New |
|---|---|
| `Traits\Core\HasQueryBuilding`, `Traits\Advanced\HasAutoOptimization`, `Traits\Advanced\HasUnifiedOptimization` | `Concerns\BuildsQueries` |
| `Traits\Core\HasSorting`, `Traits\UI\HasSortingUI` | `Concerns\SortsRows` |
| `Traits\Core\HasUnifiedSearch` | `Concerns\SearchesRows` |
| `Traits\Core\HasColumnManagement`, `Traits\UI\HasColumnVisibility` | `Concerns\ManagesColumns` |
| `Traits\Core\HasUnifiedCaching`, `Traits\Advanced\HasCountAggregations` | `Concerns\CachesResults` |
| `Traits\Advanced\HasSessionManagement` | `Concerns\PersistsState` |
| `Traits\Core\HasDataValidation`, `HasUnifiedValidation`, `HasRelationships`, `Traits\Advanced\HasUtilities` | `Concerns\ValidatesInput` |
| `Traits\Core\HasTemplateRendering`, `HasJsonSupport` | `Concerns\RendersCells` |
| `Traits\Core\HasActionHandling` | `Concerns\HandlesActions` |
| `Traits\Advanced\HasExportFeatures` | `Concerns\ExportsData` |
| `Traits\UI\HasEventListeners` | `Concerns\DispatchesEvents` |

**Removed with no replacement** — none of these was reachable:
`HasApiEndpoint`, `HasJsonFile`, `HasQueryStringSupport`, `HasQueryOptimization`,
`HasPerformanceMonitoring`, `HasBasicFeatures`.

These are internal composition units, not a public API. If you were relying on
one, open an issue and describe the use case.

---

### If you call component methods from your own code

The client-callable surface is now an allow-list of ~49 UI actions
(`Support\GuardsActions`). Calls from **PHP** are unaffected — the guard only
inspects requests that originate in the browser.

If your JavaScript calls a method that is not on the list, add it rather than
disabling the guard:

```php
// config/aftable.php
'security' => [
    'extra_actions' => ['myCustomAction'],
],
```

`renderRawHtml()` is now `protected`. It compiles and evaluates Blade, so it was
never safe to expose.

**Four export methods are gone**: `export()`, `exportToExcel()`,
`exportWithChunking()` and `exportInChunks()`. They formed a second export path
that nothing reached and that did not work — `exportInChunks()` chunked the
query and then buffered every row anyway, and wrote CSV whatever format it was
given, so `exportToExcel()` returned a CSV named `.xlsx`. Call
`handleExport('csv'|'xlsx'|'pdf')`, which is the path the toolbar uses and the
only one that was ever correct.

---

### If you relied on search stripping characters

Search no longer removes punctuation. `O'Brien`, `50%`, `C++` and `#tag` now
match, and non-Latin scripts work at all — previously an Arabic or Urdu term was
stripped to the empty string and the table returned **every** row.

Values were always bound as query parameters, so this changes no security
property. If a search now returns more rows than before, that is the bug being
fixed.

Going the other way: `%` and `_` are now **escaped**, so they are searched for
literally. A bound LIKE value is still a pattern, and until 1.6.0 that leaked —
`%%%` matched every row, `____` matched every row of four or more characters,
and `Ali%ce` matched `Alice`. If you were (deliberately or not) relying on
wildcards in the search box, use a `like` filter on a specific column instead.

---

### If you set a large `records` or `perPage`

Page size is now clamped to the largest value in
`aftable.query.per_page_options`, which defaults to `500`. `records` and
`perPage` are query-string properties, so before this an edited URL
(`?records=999999`) could make the server hydrate and serialise your whole
table on every request.

If you legitimately render more than 500 rows at once, raise the config value:

```php
// config/aftable.php
'query' => [
    'per_page_options' => [10, 25, 50, 100, 500, 2000],
],
```

---

### If you exported a static (`data =>`) table

It used to fatal with `Class "__static__" not found`; it now works. Nothing to
change — but if you worked around it, you can drop the workaround.

---

### If you published the views

```bash
php artisan vendor:publish --tag=views --force
```

`publishes()` previously pointed at `src/resources/views` — a stale duplicate
tree that was never the one being rendered. If you published before 1.6.0 you
have unmaintained templates; re-publish, or delete your copies and use the
theming system instead (`docs/THEMING.md`), which is almost always the better
answer.

---

### If you styled the table with CSS overrides

Nothing breaks — the default markup is byte-for-byte the same Bootstrap output.

But you probably no longer need the overrides:

```blade
{{-- instead of !important-ing over .table-bordered --}}
@livewire('aftable', ['classes' => ['table' => 'table table-sm my-look'], ...])
```

---

### New things worth turning on

```php
// config/aftable.php — publish with: vendor:publish --tag=aftable-config

'theme' => ['preset' => 'tailwind'],        // if you are not on Bootstrap

'cache' => ['store' => null],               // null auto-detects a working Redis

'security' => ['guard_actions' => true],    // on by default; leave it on

'routes' => ['enabled' => false],           // if you do not want the demo pages
```

---

### Verifying the upgrade

Switch on Laravel's strict mode in a non-production environment:

```php
// AppServiceProvider::boot()
Model::preventLazyLoading(! app()->isProduction());
```

If a table throws a `LazyLoadingViolationException`, that is a bug in the
package — please report it. The suite has dedicated N+1 guards, so it should
not happen.
