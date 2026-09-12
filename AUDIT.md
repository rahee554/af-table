# AUDIT — `artflow-studio/table`

Technical audit of 1.5.6, and the record of the remediation that became 1.6.0.

| | |
|---|---|
| **Audit opened** | 2026-08-24 |
| **Closed** | 2026-08-24 |
| **Baseline** | `1.5.6` (git `de75498`, branch `main`) |
| **Result** | `1.6.0` — see `CHANGELOG.md` |
| **Verified against** | PHP 8.4.5 · Laravel 12.67 · Livewire 4.4.1 · Blaze 1.0.17 |
| **Host application** | SignyCast, `http://127.0.0.1:8989` (MySQL 8) |

---

## 1. Outcome

```
composer test    486 passing, 789 assertions
pint --test      passing
open findings    0
```

**All 64 findings are fixed.** Every one keeps a test in the default suite as a
permanent guard, and each was re-verified in a real browser against the running
application.

| | 1.5.6 | 1.6.0 |
|---|---|---|
| Open findings | 64 | **0** |
| Tests | 0 | **486** |
| `src/` | 30,459 lines | **8,700** (−71%) |
| Source files | 88 | **32** |
| Traits composed | 27 | **11 concerns** |
| `insteadof` clauses | 40+ | **0** |
| Duplicated method names | 14 | **0** |
| Public methods on the component | 400 | 204 (**49 client-callable**) |
| PHP 8.4 deprecations | 9 | **0** |
| Queries per interaction (5 cols) | 22 | **2** |
| Search latency | 218 ms | **15 ms** |
| Paging latency | 372 ms | **14 ms** |
| Docs | 14 files, 6,823 lines | **5 files, 1,117 lines** |

---

## 2. What was wrong

Three defects made the package unusable outside its own demo, and all three were
invisible *from* that demo.

**Every Blade directive was dead.** `Livewire::mount()` returns a string in
Livewire 4; all four directives called `->html()` on it. Only
`@livewire('aftable', …)` worked — which is why host applications had quietly
standardised on the one form the README did not lead with. Reported as "raw HTML
doesn't render"; the page had never rendered at all.

**Remote code execution.** `renderRawHtml()` compiles its argument as Blade and
evaluates it, and it was `public` — which in Livewire means any visitor can call
it with any argument. Reproduced: `{{ 6*7 }}-{{ strtoupper("pwn") }}` → `42-PWN`.

**Eight methods were called but defined nowhere.** Each a guaranteed 500 on a
user-reachable path. The shipped template happened to route around most of them.

Underneath all of it: 27 traits, 40+ `insteadof` clauses, 14 method names
declared by more than one trait. The compiler cannot tell you when two such
implementations disagree — and one pair did: `getNestedValue()` existed twice
with the arguments in opposite orders, the conflict resolution picked the wrong
one, and **every JSON column fataled**.

That single structural fact explains most of the register.

---

## 3. Findings — all closed

### Critical

| ID | Finding |
|---|---|
| SEC-01 ✅ | `renderRawHtml()` was a public Livewire action → arbitrary Blade/PHP execution from the browser |
| BUG-32 ✅ | All four Blade directives fataled on Livewire 4 |
| BUG-02 ✅ | Any JSON-path column fataled the whole table |
| BUG-09 ✅ | 12 methods called but defined nowhere; 8 user-reachable |

### High

| ID | Finding |
|---|---|
| BUG-01 ✅ | Provider called the Blaze *facade* behind `class_exists()` on the facade |
| BUG-03 ✅ | `countAggregations` wiped by a `select('*')`; also lost after the first request |
| BUG-04 ✅ | Searching never reset to page 1 — the `updated*` guard was always true |
| BUG-08 ✅ | Excel export threw `Array to string conversion` |
| BUG-11 ✅ | `af-table:clear-phantom-columns` flushed the whole app cache and cleared config/route/view caches |
| BUG-13 ✅ | Static mode always rendered page 1 |
| BUG-16 ✅ | Sort arrow never moved |
| BUG-18 ✅ | Non-Latin search terms silently returned the **unfiltered** table |
| BUG-33 ✅ | The distinct-value "cache" aliases pointed at the uncached implementations |
| BUG-34 ✅ | Two columns reading the same DB column overwrote each other |
| BUG-37 ✅ | Schema introspection ran per column per request — 20 queries per interaction |
| BUG-38 ✅ | Eager loading lost after the first request → N+1 on every relation column |
| SEC-04 ✅ | `filterColumn` not validated against declared filters |
| SEC-05 ✅ | 400 client-callable actions → 49, behind an allow-list |

### Medium

BUG-06 ✅ · BUG-07 ✅ · BUG-10 ✅ · BUG-12 ✅ · BUG-14 ✅ · BUG-15 ✅ · BUG-17 ✅ ·
BUG-19 ✅ · BUG-20 ✅ · BUG-21 ✅ · BUG-22 ✅ · BUG-23 ✅ · BUG-24 ✅ · BUG-26 ✅ ·
BUG-27 ✅ · BUG-31 ✅ · BUG-35 ✅ · BUG-36 ✅ · BUG-39 ✅ · BUG-40 ✅ · SEC-02 ✅ ·
SEC-06 ✅ — see `CHANGELOG.md` for the one-line description of each.

### Found by the post-remediation sweep

Twelve further defects surfaced after the register above was closed: six while
building the §12 roadmap items, and six more from a deliberate hunt for
**silent wrongness** rather than crashes — hostile input on every query-string
property, and a reachability audit of every shipped file, view and method.

| ID | Finding |
|---|---|
| BUG-41 ✅ | The action guard refused Livewire's own internal actions (`__lazyLoad`, `__lazyLoadIsland`, `__dispatch`, `__runtimeWith`). Every client call routes through the guard, so any lazily-loaded island — the feature being added — would have 403'd in production. Fixed with a `LIVEWIRE_INTERNAL` allow-list kept separate from the UI surface. |
| BUG-42 ✅ | `lazy` and `defer` are **reserved Livewire mount parameters** (`SupportLazyLoading` strips both before the component sees them), so the documented `lazy => true` was swallowed and Livewire instead deferred the *whole* component — toolbar included. The property is now `deferData`, which defers only the data island. |
| BUG-43 ✅ | `render()` read `$this->tableData` unconditionally, so the query ran even when the island was only going to emit its placeholder — the deferral saved nothing. It is now read only when `deferData` is off. |
| BUG-44 ✅ | `x-artflow-table::skeleton-table` referenced `$loop` inside an `@for`. `$loop` exists only in `@foreach`; the placeholder threw `Undefined variable $loop` the first time it rendered. |
| BUG-45 ✅ | `routes/test.php` still registered `POST /aftable/test/run-performance`, which called `af-table:test-trait` — one of the three commands this release deletes. A guaranteed 500 on a route the package registers itself. |
| BUG-46 ✅ | `aftable.routes.prefix` and `aftable.routes.middleware` were documented config keys that nothing read: the route file hard-coded `prefix('aftable')` and `middleware(['web'])`, so a host application could not move or protect these pages. |
| BUG-47 ✅ | **Exporting a static (foreach-mode) table was a fatal.** `handleExport()` called `buildUnifiedQuery()` unconditionally, which does `$this->model::query()` — and in static mode `$this->model` is the `'__static__'` sentinel, so the export died with `Class "__static__" not found`. The surrounding `catch (\Exception)` did not catch it either, because an unknown class raises `\Error`. |
| BUG-48 ✅ | **The search box was a pattern box.** Binding a LIKE value makes it injection-safe but leaves it a *pattern*: `%%%` matched every row, `____` matched every row of four or more characters, and `Ali%ce` matched `Alice`. Terms are now escaped and applied with an explicit `ESCAPE` clause, portably across MySQL, PostgreSQL, SQLite and SQL Server. |
| BUG-49 ✅ | **The page size was unbounded and user-controlled.** `records` and `perPage` are query-string properties, and `getPerPageValue()` returned whichever was set verbatim — so `?records=999999` made the server select, hydrate and serialise the whole table into the Livewire snapshot on every request. `per_page_options` existed but only the dropdown read it. |
| BUG-50 ✅ | **1,381 lines of unreachable Blade were shipped and published.** The two legacy component forks were deleted in 1.6.0 but their templates stayed, alongside a stray PDF layout still carrying an absolute path from an unrelated project. `publishes()` copies the whole view tree, so these landed in host repositories. |
| BUG-51 ✅ | **A second, broken export API.** `export()` → `exportWithChunking()` → `exportInChunks()` chunked the query and then appended every row to one in-memory array, so it bought none of the memory safety its name promised, and wrote CSV whatever format it was given — making `exportToExcel()` return a CSV named `.xlsx`, the defect BUG-10 closed on the other path. None of it was reachable. |
| BUG-52 ✅ | **Searching a table with two columns on one source column was a 500.** Rendering two JSON paths out of one document is ordinary configuration; the component disambiguates the second into the identifier `preferences_language` (BUG-34). But `getSearchableColumns()` returned that *identifier* rather than the column, so the search referenced a column that does not exist. The shipped test bench was configured this way. |

### Structure

| ID | Finding |
|---|---|
| STRUCT-10 ✅ | Unreachable Blade templates shipped and published — see BUG-50. Now guarded: every `resources/views` template must be referenced by something. |
| STRUCT-11 ✅ | **Fifteen concern methods were shadowed by the component and never ran.** PHP resolves a trait/class collision in favour of the class, silently and without a signature check. Fourteen of the fifteen pairs had *diverged*, so the package carried two implementations of `handleExport()`, `getSearchableColumns()`, `applyRelationFilter()` and twelve others — with the trait copy dead. This is STRUCT-05's "three forks" problem surviving at method level. 430 lines removed; now guarded. |
| STRUCT-01 ✅ | 4,713 lines of `.old`/`.backup` shipped in `src/` |
| STRUCT-02 ✅ | `src/resources/` shadowed the real `resources/` |
| STRUCT-03 ✅ | `src/routes/web.php` + `TestController` unreachable |
| STRUCT-04 ✅ | Loose procedural scripts under `src/Testing/` |
| STRUCT-05 ✅ | Three near-duplicate components registered |
| STRUCT-06 ✅ | 27 traits, 40+ `insteadof` |
| STRUCT-07 ✅ | 14 method names declared by more than one trait |
| STRUCT-08 ✅ | 9 PHP 8.4 implicit-nullable deprecations |
| STRUCT-09 ✅ | Demo routes registered unconditionally, including in production |

---

## 4. The trait consolidation

The largest single change, and the one that makes the rest maintainable.

### What the analysis found

A reachability pass — transitive closure from the component, the views, the
tests and the action allow-list — showed that of **520 trait methods across
11,256 lines, only 112 were live**. 22%.

Whole traits were unreachable:

| Trait | Lines | Live methods | Why it was dead |
|---|---|---|---|
| `HasApiEndpoint` | 857 | 0 | HTTP datasource — no mount parameter, no view, no docs. Pure SSRF surface. |
| `HasJsonFile` | 652 | 0 | JSON-file datasource — same story |
| `HasQueryStringSupport` | 431 | 0 | Livewire's own `$queryString` already does this |
| `HasQueryOptimization` | 153 | 0 | MySQL-only `USE INDEX` hints, never called |
| `HasPerformanceMonitoring` | 423 | 1 | 422 lines to produce one stats array |
| `HasBasicFeatures` | 279 | 0 | superseded by static mode |

### What replaced them

**26 traits → 11 concerns**, in `ArtflowStudio\Table\Concerns`, named for what
they do rather than what they have:

| Concern | Absorbed |
|---|---|
| `BuildsQueries` | HasQueryBuilding, HasAutoOptimization, HasUnifiedOptimization |
| `SortsRows` | HasSorting, HasSortingUI |
| `SearchesRows` | HasUnifiedSearch |
| `ManagesColumns` | HasColumnManagement, HasColumnVisibility |
| `CachesResults` | HasUnifiedCaching, HasCountAggregations |
| `PersistsState` | HasSessionManagement |
| `ValidatesInput` | HasDataValidation, HasUnifiedValidation, HasUtilities, HasRelationships |
| `RendersCells` | HasTemplateRendering, HasJsonSupport |
| `HandlesActions` | HasActionHandling |
| `ExportsData` | HasExportFeatures |
| `DispatchesEvents` | HasEventListeners |

No `insteadof`. No `as` aliasing. **No method name declared by more than one
concern** — enforced by
`CodeHealthGuardTest::no_method_name_is_defined_by_more_than_one_trait`, because
that invariant is what BUG-02 violated.

The two legacy component forks (`Datatable`, `DatatableTrait` — 3,522 lines) went
with them. Their `aftable-simple` / `aftable-legacy` aliases now resolve to the
maintained component, so consumers keep working and silently gain every fix the
forks never received.

### Also removed

`af-table:test`, `af-table:test-trait`, `af-table:test-enhanced` and
`src/Testing/` (9,022 lines). They tested the legacy forks, asserted mostly via
`method_exists()`, and 8 of one command's 13 suites printed
"Test implementation in progress", returned 0, and were reported as **PASS**.

---

## 5. Performance

Two defects dominated everything else.

**Schema introspection per column, per request.** `isColumnSearchable()` called
`Schema::getColumnListing()` from several call sites, once per column:

```
before   search  22 queries  218 ms      page  22 queries  372 ms
after    search   4 queries   15 ms      page   2 queries   14 ms
```

**Eager loading lost after the first request.** The relation list was computed
in `mount()` into a `protected` property, and Livewire persists only public
ones. From request two onward no `with()` was applied and every relation column
lazy-loaded once per row — 86 queries for 40 rows. It looked correct on first
paint and degraded the moment anyone clicked.

### The N+1 guarantee

`tests/Feature/NPlusOneTest.php` does not assert a query budget — budgets rot.
It renders the same table at 5 rows and at 40 and requires an **identical**
count, with `Model::preventLazyLoading()` enabled so an accidental lazy load
throws rather than quietly costing a query.

| Scenario | Queries |
|---|---|
| Plain columns | 2 (COUNT + SELECT) |
| + one relation | 3 |
| + nested `a.b:column` | 4 (one per level) |
| + `countAggregations` | 2 (subselect) |
| + JSON / raw / function columns | 2 |
| Static (`data =>`) mode | **0** |

---

## 6. Security

**The action surface.** Livewire treats every public method as an action a
visitor can invoke with arbitrary arguments. Demoting 400 methods individually
was not safe while 27 traits were entangled — PHP will not warn you that a trait
400 lines away called the method you just made protected. So the surface is
closed at the single point every client call passes through:
`Support\GuardsActions`, a Livewire `call` hook permitting the 49 deliberate UI
actions and refusing the rest with a 403.

The load-bearing test parses the shipped Blade template and asserts every
`wire:click` / `wire:change` target is allowed, so the guard cannot silently
break the table's own UI.

**Input.** `filterColumn`, filter instances, `dateColumn` and `sortColumn` are
validated against the declared configuration. Relation strings must be plain
identifier chains (`Support\RelationPath`). Every column in a WHERE or ORDER BY
is table-qualified, which also fixed `ambiguous column name` when relation
sorting met search.

**Search is deliberately not sanitised beyond stripping markup.** Laravel binds
`LIKE` values as parameters, so it was already injection-safe; the old
`preg_replace('/[^\w\s\-_@.]/i')` bought nothing and broke `O'Brien`, `50%`,
`C++` and every non-Latin script.

**Compiled raw templates** live under `storage/framework/cache/aftable`, written
atomically — not the world-writable OS temp directory, where the predictable
`md5()` filename could be pre-created by another local user.

Full posture and the parts that remain the host application's responsibility
(authorisation, mainly): `docs/SECURITY.md`.

---

## 7. Theming

The markup hard-coded Bootstrap classes; the only escape hatches were
`assets => false` (removes the CSS, not the classes) and the `@aftable`
directive's custom `<table>` tag (one element).

Every styleable part is now a named **slot**, resolved through `Support\Theme`:

1. `classes` passed to the component — per-table, per-slot
2. the named preset from `theme`
3. `aftable.theme.overrides` — app-wide, per-slot
4. the app-wide preset
5. the built-in `bootstrap` preset

Presets: `bootstrap` (default, byte-identical to before), `tailwind`, `none`
(no framework classes at all — only structural `aft-*` hooks).
`Theme::register()` adds your own, optionally extending an existing one.

**Pagination is a slot too**, so a host application can use its own paginator
view. The `tailwind` preset points at Laravel's `pagination::tailwind` and
`none` at `pagination::simple-default`.

`docs/THEMING.md`. §10 of the bench renders the custom-classes and `none` cases
against real data.

---

## 8. Caching and Redis

`Support\CacheStore` prefers Redis **only when it actually works** — configured
as a cache store, a client library loadable, and a live round-trip succeeding.
That third check matters: `config('cache.stores.redis')` exists in a stock
Laravel app whether or not a server is running, so a config-only check would
route the package at a store that throws on every call.

The probe is memoised per process and cached for 60 s, so a dead Redis costs one
attempt a minute. Every path is wrapped: a cache outage degrades to computing
the value.

Redis is preferred for **tags** — invalidating one model's distinct values
leaves every other table's cache intact. Without tags the package says so
plainly rather than reaching for `Cache::flush()`.

---

## 9. Layout

```
table/
├── AUDIT.md  CHANGELOG.md  CONTRIBUTING.md  README.md  UPGRADING.md
├── composer.json  phpunit.xml
├── config/aftable.php          cache · query · theme · security · routes
├── database/                   migration (loadMigrationsFrom) + scalable seeder
├── docs/                       CONFIGURATION · RECIPES · THEMING · PERFORMANCE · SECURITY
├── resources/
│   ├── assets/
│   └── views/{components,exports,livewire,test}
├── routes/test.php             config-gated, never in production
├── src/                        32 files, 8,700 lines
│   ├── Concerns/               the 11 traits the component composes
│   ├── Exports/ArrayExport.php
│   ├── Http/Livewire/AftableComponent.php
│   ├── Support/
│   │   ├── CacheStore.php              Redis auto-detection
│   │   ├── TableCache.php              namespaced, tagged, memoised cache
│   │   ├── SchemaCache.php             cached column listings
│   │   ├── RelationPath.php            one parser for 'a.b:column'
│   │   ├── Theme.php                   slot → class resolution
│   │   ├── GuardsActions.php           client-callable allow-list
│   │   └── ActionNotAllowedException.php
│   ├── Console/Commands/       migrate · clear-phantom-columns
│   ├── Tests/Models/           demo models
│   └── TableServiceProvider.php
└── tests/                      Feature · Feature/Guards · Support · Unit
```

`Support/RelationPath` alone replaced **40+ scattered `explode(':')` call
sites**, most of them unguarded list-destructuring that emitted
`Undefined array key 1` on any malformed value.

---

## 10. The test suite

```bash
composer install
composer test        # 486 tests, 789 assertions
composer lint        # vendor/bin/pint --test
```

```
tests/
├── TestCase.php                     Testbench bootstrap
├── Support/                         DatasetSeeder · ColumnSets · SourceScanner
├── Unit/RelationPathTest.php
└── Feature/
    ├── ActionGuardTest              callable surface + "the guard can't break the UI"
    ├── BladeDirectiveTest           all four syntaxes, through real Blade
    ├── BootOrderTest                provider order independence
    ├── CacheBackendTest             Redis detection, tags, TTLs, graceful failure
    ├── ColumnKeyTest                key derivation and disambiguation
    ├── ColumnVisibilityTest         toggle, session, per-table isolation
    ├── ConsoleCommandTest           the two surviving commands
    ├── ExportAndSelectionTest       CSV content, filters, row selection
    ├── FilteringTest                operators, distinct values, stacked instances
    ├── JsonColumnTest               paths, casts, nesting, edge cases
    ├── ConfigurationSurfaceTest     themes, phantom columns, cache scoping, commands
    ├── DuplicateColumnKeyTest       two columns on one source column, end to end
    ├── GeneratorSchemaTest          introspection gate, allow-list, suggestions
    ├── HostileInputTest             garbage on every query-string property
    ├── InteractionStateTest         selection, visibility, session, directives
    ├── LazyLoadingTest              deferData, island placeholder, guard passthrough
    ├── MakeTableCommandTest         af-table:make output and its gate
    ├── SearchCorrectnessTest        search and filters must never silently widen
    ├── MountAndConfigurationTest    the three config shapes
    ├── NPlusOneTest                 query count vs row count + preventLazyLoading
    ├── PaginationTest               page size, navigation, resets
    ├── QueryConstraintTest          base constraints
    ├── RenderingTest                every column type, toolbar, actions, eager loading
    ├── SearchTest                   3-char floor, coverage, unicode
    ├── SortingTest                  toggle/set/clear, ordering, relations
    ├── StaticModeTest               in-memory mode, zero queries
    ├── ThemeTest                    slots, presets, overrides, host pagination
    ├── UndefinedMethodGuardTest     static check: every call site resolves
    └── Guards/                      one file per closed finding, kept as guards
```

Four of these are invariants rather than feature tests, and they are the reason
the consolidation was safe to attempt:

- **`UndefinedMethodGuardTest`** — resolves every `$this->method()` call site
  against the methods that exist. Makes BUG-09 impossible to reintroduce.
- **`CodeHealthGuardTest`** — no duplicate method names, no `insteadof`, no dead
  files, no PHP 8.4 deprecations.
- **`NPlusOneTest`** — query count must not grow with row count.
- **`ActionGuardTest`** — the allow-list must cover everything the template binds.
- **`CodeHealthGuardTest`** also now proves that no concern method is shadowed
  by the component (STRUCT-11) and that no shipped template is unreachable
  (STRUCT-10). Both were found by asking "what does this file actually reach?"
  rather than by any test failing.

### Continuous integration

`.github/workflows/tests.yml` runs the suite on PHP 8.2, 8.3 and 8.4 against
Livewire `^4.0`, with the `sqlite3` and `redis` extensions so the Redis
detection path is exercised for real rather than only through its fallback.

`.github/workflows/lint.yml` runs two jobs: `pint --test` against the committed
`pint.json` (Laravel preset, `concat_space: none`, alphabetical imports, no
unused imports), and a PHP 8.4 job that fails the build if the suite emits a
single `Deprecated` notice — STRUCT-08 was nine of them, and a linter is the
only thing that keeps that number at zero.

---

## 11. The test bench

`/aftable/test` — one scroll, eleven sections, deliberately plain: system fonts,
one neutral palette, no icon CDN, follows the OS light/dark setting. **Every
table mounts through the public API**, never an internal helper, so the page
fails the way a host application would. The old page documented `@aftable` and
then quietly used `@livewire` instead.

| § | Exercises |
|---|---|
| 1 Core | search, sort, paginate, page-size, column visibility, row index, checkboxes, CSV |
| 2 Relations | `relation:column`, nested, join sorting, `withCount`, N+1 check |
| 3 Computed | JSON paths, raw HTML, conditionals, arithmetic, function columns, `extraVars`, XSS |
| 4 Filters | distinct, text, number + operators, date, stacked instances, allow-list |
| 5 Static | array/Collection source, in-memory ops, zero queries |
| 6 Directives | all four Blade entry points, custom table shell |
| 7 Actions | link actions, raw actions, bulk select, print, gated export menu |
| 8 Scale | 50,000-row table: deep paging, indexed sort, cached distinct values |
| 9 Edge cases | empty state, chrome disabled, base constraint, hidden column |
| 10 Theming | per-slot overrides and `theme => none`, both against real data |
| 11 Security | live posture: guard state, callable count, storage path, route gating |

Measured: 14 components, 120 rows, 0 console errors, ~3 s cold.

---

## 12. What comes next

The 1.5.6 audit closed with four roadmap items, none of them defects. Three are
now delivered; one is still open.

### Delivered in 1.6.0

**The code generator.** `Support\SchemaIntrospector` reads the host
application's models, columns, casts and relations. `/aftable/generator` offers
a model picker and fills the column builder from the real table; the new
`af-table:make` command does the same from the CLI and prints a mountable
snippet. Gated three ways — `routes.enabled`, not-production, and
`generator.enabled` (default **false**) — with `$hidden` attributes and
credential-shaped columns never offered, and `describe()` restricted to an
allow-list rather than a `class_exists()` check, because it constructs the
class it is handed. Two defects came out of the wiring: BUG-45, BUG-46.

**Lazy islands.** `deferData => true` renders the toolbar immediately and fills
the data island on a second round trip, with `x-artflow-table::skeleton-table`
as the placeholder. Four defects came out of the wiring: BUG-41 to BUG-44.

**CI.** `.github/workflows/tests.yml` (PHP 8.2/8.3/8.4 × Livewire ^4.0, with
`sqlite3` and `redis`) and `.github/workflows/lint.yml` (`pint --test` plus a
job that fails on any `Deprecated` notice).

### Still open

**Livewire 4 single-file components.** Co-locating class and template is
exactly what would have prevented BUG-16, where the template read `$sortBy` and
the code wrote `$sortColumn`. The 800-line template wants splitting into
toolbar / filters / header / body / footer sub-components with islands. This is
a structural change to the render path, so it is deliberately not in 1.6.0 —
the release already rewrites enough.

---

## Appendix — environment

```
Baseline    artflow-studio/table 1.5.6 (de75498, main)
PHP         8.4.5      Laravel 12.67.0    Livewire 4.4.1
Blaze       1.0.17     Testbench 10.11    PHPUnit 11.5.56
Host app    SignyCast @ http://127.0.0.1:8989 (MySQL 8)
Test DB     SQLite :memory:
OS          Windows 11 Pro 26100
```

Browser verification used Playwright throughout: all four directives rendering,
raw HTML arriving as markup, search filtering and syncing to the query string,
sort ordering with the arrow tracking the column, count aggregations populated,
both theming modes, and zero console errors.
