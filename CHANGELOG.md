# Changelog

All notable changes to `artflow-studio/table`.

This project follows [Semantic Versioning](https://semver.org/).

---

## [1.6.1] — unreleased

### Fixed — security

- **The browser could rewrite what a table reads.** `model`, `columns`, `filters`,
  `query`, `actions`, `tableId`, `theme`, `classes`, `extraVars`, `customTemplate`,
  `staticRows` and `isStaticMode` were ordinary public properties, and Livewire lets
  the browser overwrite any public property that is not `#[Locked]`. A signed-in
  visitor could point a table at another model, drop the `query` scope the host had
  applied, or add columns the host never exposed. They are now locked
  (`ConfigurationLockTest`). Server-side changes are unaffected.

### Fixed

- **`perPage` was ignored.** The page-size dropdown binds `records`, and `records` always
  won over `perPage` (default 10 for both), so `'perPage' => 25` did nothing.
- **A relation column's `format` was ignored for dates.** The cell was cast to a string
  before the format was applied, printing `2019-04-27 00:00:00`; a relation date now uses
  the column's `format` like a plain column.
- **Searching a phone number failed unless typed exactly as stored.** `0313-9089173`, `0313 9089173` and `+92 313 9089173` now find `923139089173`: a phone-like term is also searched as bare digits, and without its leading zero.
- **`'sortable' => false` was ignored on plain columns** (the header still drew sort
  arrows). The "(Not Sortable)" tag is now shown only for nested relations and JSON
  columns that did not say so themselves.

### Added

- **Filter bar (`quickFilters`).** The search box, a labelled dropdown per filter and a
  clear button in one row — the layout the LMS enrollments page had built by hand —
  instead of the generic "pick a column, then a value" filter. Options are declared,
  locked server-side and can filter on a column or on the existence of a relation.
  New mount parameters `quickFilters`, `searchLabel`, `searchPlaceholder`; new theme
  slots `quickbar`, `quickbar.search`, `quickbar.col`, `quickbar.clear`, `input.group`,
  `input.group.text`; new allowed action `clearQuickFilters`.
- **Model scopes in `query`.** `'query' => ['scope:forInstitute' => 3]` calls the named
  scope on the model (`scopeForInstitute`). Unlike a closure — which Livewire cannot
  carry between requests, so a closure `query` was lost after the first render — it
  survives the whole request cycle. A name the model does not define as a scope is
  refused.

---

## [1.6.0] — unreleased

The first release after a full audit of 1.5.6. **61 defects fixed**, four of
them critical, plus a consolidation that removed roughly two thirds of the
codebase without losing a single working feature.

### Requirements

`1.6.0` requires **Livewire 4.0+**, Laravel 12, and PHP 8.2+. Livewire 3 is not
supported: the package uses islands, `#[Computed]`, `@assets`/`@script`, the
`commit` hook and component hooks, none of which exist in 3.x.

### Fixed — critical

- **The Blade directives were dead.** `Livewire::mount()` returns a string in
  Livewire 4, but `@AFtable`, `@AFtableTrait` and `@aftable`/`@endaftable` all
  called `->html()` on it, so every one of them threw
  `Call to a member function html() on string`. Only `@livewire('aftable', …)`
  worked. *(BUG-32)*
- **Remote code execution.** `renderRawHtml()` compiles its first argument as
  Blade and evaluates it, and it was `public` — which in Livewire means any
  visitor could call it with any argument. It is now `protected` and refused by
  the action guard. *(SEC-01)*
- **JSON columns fataled the whole table.** Two traits declared
  `getNestedValue()` with the arguments in opposite orders; the `insteadof`
  picked the wrong one and every JSON cell threw a `TypeError`. *(BUG-02)*
- **Searching a table with two columns on one source column was a 500.**
  Rendering two JSON paths out of one document — the shipped test bench does
  exactly this — produced
  `Unknown column 'test_employees.preferences_language'`. The component
  disambiguates the second column into a rendering identifier, and the search
  path used that identifier as though it were a database column. *(BUG-52)*
- **Exporting a static (foreach-mode) table was a fatal.** `handleExport()`
  always built an Eloquent query, and static mode has no model — only the
  `'__static__'` sentinel — so the export died with
  `Class "__static__" not found`. The surrounding `catch (\Exception)` did not
  catch it either: an unknown class raises `\Error`. *(BUG-47)*
- **Twelve methods were called but defined nowhere**, eight of them on
  user-reachable paths: `clearFilters`, `clearSearchInput`,
  `applyDateRangeFilter`, three session-persistence helpers and two debug
  helpers. Each was a guaranteed 500. *(BUG-09)*

### Fixed — security

- **The search box was a pattern box.** Binding a LIKE value makes it
  injection-safe, but it is still a *pattern*: `%%%` matched every row, `____`
  matched every row of four or more characters, and `Ali%ce` matched `Alice`.
  Terms are now escaped and applied with an explicit `ESCAPE` clause that
  behaves the same on MySQL, PostgreSQL, SQLite and SQL Server. *(BUG-48)*
- **The page size was unbounded and user-controlled.** `records` and `perPage`
  are query-string properties, so `?records=999999` made the server select,
  hydrate and serialise the entire table into the Livewire snapshot on every
  request. Page size is now clamped to the largest configured
  `per_page_options` value — which existed, but only the dropdown read it.
  *(BUG-49)*

### Fixed — high

- Eager loading was lost after the first request, causing an N+1 on every
  relation column from the second interaction onward — 86 queries for 40 rows.
  The relation list lived in a `protected` property, which Livewire does not
  persist. *(BUG-38)*
- Schema introspection ran once per column per request: 20 queries on every
  search, sort and page change. *(BUG-37)*
- The distinct-value cache was never read — the "cached" aliases pointed at the
  uncached implementations. *(BUG-33)*
- `countAggregations` was wiped by a `select('*')` two lines after
  `withCount()`, and lost entirely after the first request. *(BUG-03)*
- Searching never reset to page 1: the `updated*` hook's guard was always true,
  so its whole body was dead code. *(BUG-04)*
- Non-Latin search terms silently returned the **unfiltered** table. The
  sanitiser's `\w` had no `/u` modifier, so Arabic, Urdu, Chinese, Cyrillic and
  Greek terms were stripped to nothing. *(BUG-18)*
- Static (foreach) mode always rendered page 1. *(BUG-13)*
- The sort arrow never moved — `toggleSort()` did not sync `$sortBy`, which is
  what the template reads. *(BUG-16)*
- Excel export threw `Array to string conversion`. *(BUG-08)*
- `af-table:clear-phantom-columns` called `Cache::flush()` five times and
  cleared the host application's config, route and view caches. *(BUG-11)*
- The service provider called the Blaze *facade* behind `class_exists()` on the
  facade, which is always true — fatal if it booted before Blaze. *(BUG-01)*
- `POST /aftable/test/run-performance` shipped as a registered route and called
  `af-table:test-trait`, a command this release removes — a guaranteed 500 on a
  route the package registers itself. Removed. *(BUG-45)*
- **A second export API that did not work.** `export()` →
  `exportWithChunking()` → `exportInChunks()` chunked the query and then
  appended every row to one in-memory array, so it bought none of the memory
  safety its name promised, and wrote CSV whatever format it was handed —
  making `exportToExcel()` return a CSV named `.xlsx`, the defect BUG-10 closed
  on the other path. None of it was reachable; all of it is gone. *(BUG-51)*
- The action guard refused Livewire's own internal actions — `__lazyLoad`,
  `__lazyLoadIsland`, `__dispatch`, `__runtimeWith`. Every client call routes
  through the guard, so a lazily-loaded island would have been rejected in
  production. *(BUG-41)*
- `lazy` and `defer` are reserved Livewire mount parameters: `SupportLazyLoading`
  strips them before the component ever sees them, so the documented
  `lazy => true` was swallowed and Livewire deferred the *whole* component,
  toolbar included. The property is now `deferData`. *(BUG-42)*
- `filterColumn` was not validated, so a hand-edited query string could filter
  on any column of the table. *(SEC-04)*
- 400 public methods were client-callable. *(SEC-05)*

### Fixed — medium

Relation columns without an explicit `key` (BUG-06) · closure `raw` columns
documented but impossible (BUG-07) · PDF export silently producing a `.csv`
(BUG-10) · page-size dropdown hidden at ≤10 rows (BUG-14) · `@script` listening
for an event Livewire never fires (BUG-15) · migrations not registered with the
framework (BUG-17) · `--force` advertised but never read (BUG-19) · `--fresh`
deleting any `migrations` row matching `%aftable%` (BUG-20) · the seeder's
hard-coded 118,600 rows (BUG-21) · relation sorting discarding the select list
(BUG-22) · `removeFilterInstance()` leaving a non-sequential array (BUG-23) ·
`getSortableColumns()` always returning `[]` (BUG-24) · meaningless cache
statistics (BUG-26) · `publishes()` shipping the stale view tree (BUG-27) ·
`--fresh` being MySQL-only (BUG-31) · two columns on the same source column
overwriting each other (BUG-34) · array/enum/date casts fatalling the render
(BUG-35) · an undeclared Font Awesome dependency (BUG-36) · `routes.prefix` and
`routes.middleware` documented but ignored — the route file hard-coded
`aftable` and `['web']` (BUG-46) · `render()` running
the query even when the data island would only emit its placeholder (BUG-43) ·
`$loop` referenced inside an `@for` in the skeleton component (BUG-44) · relation sort +
search producing `ambiguous column name` (BUG-39) · compiled templates written
to the world-writable OS temp directory (SEC-02) · client-callable HTTP helpers
(SEC-06) · the `BOOTSTRAP` preset's header cells and the loading skeleton used
`bg-light`, a literal Bootstrap colour Bootstrap itself never remaps for dark
mode — every header rendered near-white on any dark theme, ui-flow included,
regardless of the surrounding page. Both now use `bg-body-secondary`, one of
Bootstrap 5.3's theme-relative surface utilities, matching what the `UI_FLOW`
preset already did by leaving the header bare (BUG-53).

### Added

- **Theming.** Every styleable part of the table is a named *slot*. Presets:
  `bootstrap` (default, unchanged), `tailwind`, `none`. Override one slot, a
  whole preset, or the app-wide default; register your own with
  `Theme::register()`. The **pagination view is a slot too**, so a host
  application can use its own paginator — including Laravel's
  `pagination::tailwind` and `pagination::bootstrap-5`. See `docs/THEMING.md`.
- **Redis, auto-detected.** The package uses Redis for its own caches when the
  application has a *working* one — configured, client library loadable, and a
  live round-trip that succeeds — and falls back silently otherwise. Redis
  brings cache tags, so invalidation is precise. Never a dependency.
  `TableCache::describe()` reports the live backend.
- **An action allow-list.** `Support\GuardsActions` restricts the methods a
  browser may invoke to the ~49 deliberate UI actions, at the single point every
  client call passes through. Configurable via `aftable.security`.
- `config/aftable.php`, publishable with
  `vendor:publish --tag=aftable-config`: cache, query, theme, security and
  route settings.
- `Support\RelationPath` — one parser for `'a.b:column'`, replacing 40+
  scattered `explode(':')` call sites.
- `Support\SchemaCache` — cached column listings.
- `Support\TableCache` / `Support\CacheStore` — namespaced, tagged, memoised
  caching with graceful degradation.
- **A generator that reads your schema.** `/aftable/generator` now offers a
  model picker and fills the column builder from the real table — types, casts
  and relations included — instead of asking you to type every key by hand.
  `php artisan af-table:make Order` does the same from the command line and
  prints a mountable snippet.

  Because it reads your schema it is off by default (`aftable.generator`),
  gated again on the demo-route flag, and never registered in production.
  `$hidden` attributes and credential-shaped column names are never offered,
  and only models discovered under `model_paths` can be described at all.
  See `docs/SECURITY.md`.
- **Deferred data loading.** `deferData => true` returns the toolbar and table
  shell immediately and fills the rows from a lazy `@island` on a second round
  trip, showing `x-artflow-table::skeleton-table` in the meantime. On a page
  carrying several heavy tables this turns one slow response into a fast shell
  plus parallel fills. Off by default. See `docs/PERFORMANCE.md`.
- Inline SVG icons, so the toolbar no longer silently depends on Font Awesome.
- A real test suite: **486 tests**, including N+1 guards that assert the query
  count does not change as the row count grows, with
  `Model::preventLazyLoading()` enabled.
- **CI and a code style.** `.github/workflows/tests.yml` runs the suite on PHP
  8.2/8.3/8.4 with the `sqlite3` and `redis` extensions; `lint.yml` runs
  `pint --test` against the committed `pint.json` and fails the build on any
  `Deprecated` notice. `composer lint` runs the same check locally.
- A rebuilt test bench at `/aftable/test`: eleven sections covering every
  feature, with a live diagnostics strip (queries, render time, cache backend).

### Changed

- **26 traits → 11 concerns**, in `ArtflowStudio\Table\Concerns`. No
  `insteadof`, no aliases, and no method name declared by more than one concern
  — enforced by a test. Names are verb-first and describe what they do:
  `BuildsQueries`, `SortsRows`, `SearchesRows`, `ManagesColumns`,
  `CachesResults`, `PersistsState`, `ValidatesInput`, `RendersCells`,
  `HandlesActions`, `ExportsData`, `DispatchesEvents`.
- Demo routes (`/aftable/test`, `/aftable/docs`, `/aftable/generator`) are now
  behind `config('aftable.routes.enabled')` **and** never registered when
  `APP_ENV=production`.
- `af-table:migrate` was rewritten on Laravel's own migrator: `ConfirmableTrait`
  for `--force`, `migrate:rollback --path` instead of a raw `DELETE`, portable
  foreign-key handling, and a new `--scale` option for the seeder.
- CSV exports are written with a UTF-8 BOM so Excel opens accented and
  non-Latin text correctly, and now include relation, JSON and raw columns
  instead of blank cells.
- The export menu only offers formats the installation can actually produce.
- Documentation consolidated from 14 files (6,823 lines) to five.
- **1,381 lines of unreachable Blade removed.** The two legacy component forks
  went in 1.6.0 but their templates stayed, alongside a stray PDF layout still
  carrying an absolute path from an unrelated project. `publishes()` copies the
  whole view tree, so these were landing in host repositories. *(BUG-50)*
- **430 lines of shadowed concern code removed.** PHP resolves a trait/class
  method collision in favour of the class, silently and without a signature
  check. Fifteen concern methods were shadowed by the component; fourteen of
  the pairs had diverged, so the package carried two implementations of
  `handleExport()`, `getSearchableColumns()`, `applyRelationFilter()` and
  twelve others, with the trait copy dead. A test now fails if it happens
  again. *(STRUCT-11)*
- `livewire/blaze` is now an explicit dev dependency. It stays a `suggest` for
  host applications, but the suite boots it deliberately to keep BUG-01 from
  coming back, so it must be installed for `composer test` to mean anything.

### Removed

- **The two legacy component forks.** `Datatable` and `DatatableTrait` were
  separate copies of the same feature set, so every fix had to be made three
  times and in practice landed in one. The `aftable-simple` and `aftable-legacy`
  aliases still work — they now resolve to the maintained component. See
  `UPGRADING.md`.
- `af-table:test`, `af-table:test-trait` and `af-table:test-enhanced`. They
  exercised the legacy forks, asserted mostly via `method_exists()`, and 8 of
  one command's 13 suites were stubs that printed "Test implementation in
  progress" and reported PASS. `composer test` replaces them.
- The API-endpoint datasource (`HasApiEndpoint`, 857 lines) and the JSON-file
  datasource (`HasJsonFile`, 652 lines). Neither was reachable: no mount
  parameter, no view usage, no documentation. The former was pure SSRF surface.
- `HasQueryStringSupport` (Livewire's own `$queryString` does this),
  `HasQueryOptimization` (MySQL-only index hints, never called),
  `HasPerformanceMonitoring`, `HasBasicFeatures`, and 221 other unreachable
  methods.
- `index.html` — a second, diverged copy of the code generator.
- 4,713 lines of `.old` / `.backup` files shipped inside `src/`.

### Deprecated

- The `aftable-simple` and `aftable-legacy` component aliases. They resolve to
  `aftable` and will be removed in 2.0.

---

## [1.5.6] and earlier

See git history. Release notes before 1.6.0 were not maintained consistently;
`docs/` previously carried changelogs for versions (1.7.0, 1.8.0) that were
never tagged.
