# 📋 ArtFlow Table — Changelog v1.6.0 / v1.7.0

**v1.6.0 Release Date:** July 2025  
**v1.7.0 Release Date:** July 2025  
**Status:** ✅ Production Ready

---

## 🆕 v1.7.0 — AftableComponent, Print System, Multi-Syntax Generator

---

## 🆕 New Features

### Static / Foreach Data Mode

Pass any PHP array, plain array, or Laravel Collection directly — Eloquent model not required.

```blade
@livewire('aftable', [
    'data'    => $myArray,
    'vars'    => ['currency' => 'USD'],
    'columns' => [...],
])
```

- Fully reactive: in-memory search, sort, and pagination  
- `vars` array is shared across all `raw` column templates  
- Ideal for API responses, computed/aggregated data, small datasets  

### `@aftable` / `@endaftable` Blade Directives

New paired directives capture inner HTML to apply your app's custom table styling:

```blade
@aftable(['model' => App\Models\User::class, 'columns' => [...]])
    <table class="table table-hover my-theme">
        <thead class="bg-dark text-white"><tr></tr></thead>
        <tbody></tbody>
    </table>
@endaftable
```

The component extracts `<table>` tag attributes from your markup and applies them to the rendered table. All standard AF Table features (search, sort, pagination, filters) still work.

### Closure / Callable Support in `raw` Columns

Use PHP closures instead of Blade strings for raw HTML columns — faster (zero Blade overhead), better IDE support, and cleaner syntax:

```php
['key' => 'status', 'label' => 'Status',
 'raw' => fn($row) => $row->active
     ? '<span class="badge bg-success">Active</span>'
     : '<span class="badge bg-danger">Inactive</span>'],

// With extra vars
['key' => 'price', 'label' => 'Price',
 'raw' => fn($row, $vars) => e($vars['currency']).' '.number_format($row->price, 2)],
```

### `customTemplate` Mount Parameter

Pass a `<table>` HTML string directly to override the table's opening tag — useful when not using the `@aftable` directive:

```blade
@livewire('aftable', [
    'model'          => App\Models\User::class,
    'columns'        => [...],
    'customTemplate' => '<table class="table table-hover my-theme" id="myTable">',
])
```

### `vars` Mount Parameter

Share variables across all `raw` column templates:

```blade
@livewire('aftable', [
    'model' => App\Models\Product::class,
    'vars'  => ['currency' => 'USD', 'symbol' => '$'],
    'columns' => [
        ['key' => 'price', 'label' => 'Price',
         'raw' => '{{ $symbol }}{{ number_format($row->price, 2) }} {{ $currency }}'],
    ],
])
```

---

## 🔒 Security Fixes

### `eval()` Removed from `getDynamicClass()`

`classCondition` in the `HasUtilities` trait previously used `eval()` to evaluate class conditions — a significant security risk.

**v1.6.0 replacement — no `eval()`, supports:**
- Closure conditions: `fn($row) => $row->active`
- Boolean literals: `true` / `false`
- Truthy shorthand: `'fieldName'` → `!!$row->fieldName`
- Negation shorthand: `'!fieldName'` → `!$row->fieldName`
- Equality shorthand: `'field:value'` → `$row->field === 'value'`

### `renderRawHtml()` Hardened

- Accepts Closure/callable: fast path, skips Blade entirely
- Strips accidental PHP open tags (`<?php`/`?>`) from Blade strings automatically
- Better error fallback: shows escaped template preview with error title (not a silent empty string or a raw exception)
- Uses `\Throwable` instead of `\Exception` to catch all errors

---

## 🧹 Dead Code Removal

### 7 Entire Trait Files Deleted

| File | Reason |
|------|--------|
| `Core/HasBladeRendering.php` | Unused — rendering moved to DatatableTrait |
| `Core/HasColumnConfiguration.php` | Unused — columns handled inline |
| `UI/HasActions.php` | Unused — replaced by actions array handling |
| `UI/HasBulkActions.php` | Unused |
| `UI/HasRawTemplates.php` | Unused — raw handled in HasUtilities + DatatableTrait |
| `UI/HasTemplateRenderer.php` | Unused |
| `UI/HasUserActions.php` | Unused |

### Dead Methods Removed from Used Traits

**`HasUtilities`** (5 removed): `sanitizeHtmlContent`, `validateJsonPath`, `validateRelationString`, `validateExportFormat`, `sanitizeFilterValue`

**`HasSortingUI`** (3 removed): `getSortIcon`, `getSortableColumns`, `getSortState` (all shadowed by `HasSorting` via `insteadof`)

**`HasQueryBuilding`** (7 removed): `buildQuery()` deprecated stub, `query()` deprecated stub, `applyOptimizedSearch`, `applyColumnSearch`, `applyRelationSearch`, `applyJsonSearch`, `sanitizeSearch` (all overridden by `HasUnifiedSearch`)

**`HasQueryOptimization`** (6+ removed): `applyOptimizedSorting`, `applyOptimizedRelationSorting`, `applySimpleRelationSubquery`, `applyNestedRelationSubquery`, `applyOptimizedColumnSorting` (dead chain shadowed by `HasQueryBuilding`), `getTableNameForRelation` (had hardcoded airline/airport/flight table map), `buildWhereCondition` (had hardcoded "flight" check)

---

## 🔧 Fixed Blade Directives

The `@AFtable` and `@AFtableTrait` directives were using the Livewire 2 `app('livewire')->mount()` API. Updated to `\Livewire\Livewire::mount()` for Livewire 4 compatibility.

---

## 📦 Code Generator Updated (index.html v1.6.0)

- Added **Data Mode** selector: Eloquent vs Static/Foreach
- Added `staticVar` and `extraVars` form fields
- Added **"Show Usage Snippets"** button with 4 ready-to-copy snippets
- Updated `generateCode()` to handle both data modes and extra vars
- Added comprehensive **Usage Guide** section with 5 code examples and parameter reference table
- Added "Static Mode" and "Custom Template" version badges

---

## ⬆️ Upgrade Notes

**From v1.5.2 → v1.6.0:**

- No breaking changes to existing `@livewire('aftable', [...])` usage
- If you used `classCondition` with string expressions that relied on `eval()`, update them to closures (see `classCondition` section in COMPLETE_GUIDE.md)
- The `@AFtable` / `@AFtableTrait` fix is transparent — no syntax changes needed
- New `data`, `vars`, and `customTemplate` parameters are entirely optional

---

---

## v1.7.0 — AftableComponent, Isolated Print, Multi-Syntax Code Generator

### `AftableComponent` — Renamed from `DatatableTrait`

The primary Livewire component has been **renamed** for clarity and convention alignment:

| Item | Before | After |
|------|--------|-------|
| PHP class file | `DatatableTrait.php` | `AftableComponent.php` |
| Blade view file | `datatable-trait.blade.php` | `aftable-component.blade.php` |
| Class name | `DatatableTrait` | `AftableComponent` |
| Livewire alias | `'aftable'` | `'aftable'` (unchanged) |

The old class is kept registered as `'aftable-legacy'` for backward compatibility. **No change to your Blade files required.**

---

### Isolated Print Window System

`printable: true` now opens an **isolated pop-up window** containing only the table — not the full browser page.

#### `printConfig` Array Reference

```php
@livewire('aftable', [
    'model'       => App\Models\User::class,
    'columns'     => [...],
    'printable'   => true,
    'printConfig' => [
        'title'       => 'User Report',        // shown in print header
        'header'      => '<p>Confidential</p>',// custom HTML above table
        'footer'      => 'HR Department',      // text/HTML in footer
        'fontSize'    => 11,                   // points (default: 11)
        'orientation' => 'landscape',          // portrait | landscape
        'paperSize'   => 'A4',                 // A4 | Letter | Legal | …
        'showDate'    => true,                 // print current date in header
        'showPageNum' => true,                 // page number in footer
        'tableClass'  => 'table-striped',      // extra class on printed table
    ],
])
```

**How it works:** Clicking Print creates a new pop-up window (`window.open`) populated with a clean HTML document containing only the table. Livewire/Alpine attributes are stripped from the DOM clone. `window.print()` fires automatically after 400 ms.

> **Requires pop-ups allowed** in the browser for the origin.

---

### Three-Syntax Code Generator (`index.html`)

The live code generator now has an **Output Syntax** radio selector:

| Option | Output |
|--------|--------|
| `@livewire('aftable', [...])` | PHP array in a Blade helper (default) |
| `<livewire:aftable … />` | Livewire tag syntax with `@php` config block |
| `@aftable([...]) … @endaftable` | Paired Blade directive with optional custom table |

---

### New Test Page Tabs

Two new tabs added to `/aftable/test`:

| Tab | Demonstrates |
|-----|-------------|
| **Custom Template** | `@aftable`/`@endaftable` with custom `<table class="">` |
| **Print Demo** | Isolated print window, landscape A4, full `printConfig` |

---

### `TestTraitCommand` — All Missing Methods Fixed

Fixed `BadMethodCallException` on `php artisan af-table:test-trait --suite=performance`.

Added 14+ methods including:
- `runQueryBuildingTest()`, `runColumnManagementTest()`, `runSearchFilterTest()`
- `runPropertyValidationTest()` — checks all public properties including `printConfig`
- `runPerformanceTests()` — real DB benchmarks (page-load, search, sort, distinct values timing against 10K employee rows)
- `runRelationshipTests()`, `runPerformanceOptimizationTests()`
- `displayEnhancedFinalResults()` — shows pass/fail table with % score

---

## ⬆️ Upgrade Notes (v1.6.0 → v1.7.0)

- **No breaking changes.** All existing `@livewire('aftable', [...])` calls continue to work.
- `printable: true` now uses an isolated window instead of `window.print()` — ensure pop-ups are allowed.
- `printConfig` is optional; if omitted, defaults are used (11pt, portrait A4, date shown, no title/footer).
� AftableComponent, Isolated Print, Multi-Syntax Code Generator

### AftableComponent � Class Renamed from DatatableTrait

The primary Livewire component has been **renamed** from DatatableTrait to AftableComponent (more accurate, follows Laravel/Livewire conventions).

| File | Before | After |
|------|--------|-------|
| PHP class | DatatableTrait.php | AftableComponent.php |
| Blade view | datatable-trait.blade.php | ftable-component.blade.php |
| Class name | class DatatableTrait extends Component | class AftableComponent extends Component |
| Livewire tag | @livewire('aftable', [...]) | same (no change) |

The old DatatableTrait class is kept as ftable-legacy for backward compatibility:

`'php
// Still works � 'aftable' now resolves to AftableComponent
@livewire('aftable', [...])
<livewire:aftable .../>
@aftable([...]) ... @endaftable
`'

### Isolated Print Window System

printable: true now opens an **isolated pop-up window** containing only the table � not the full browser page.

#### printConfig Array

`'php
@livewire('aftable', [
    'model'       => App\Models\User::class,
    'columns'     => [...],
    'printable'   => true,
    'printConfig' => [
        'title'       => 'User Report',
        'header'      => '<p>Confidential</p>',   // custom HTML above table
        'footer'      => 'HR Department',          // text/HTML below table
        'fontSize'    => 11,                       // points (default: 11)
        'orientation' => 'landscape',              // portrait | landscape
        'paperSize'   => 'A4',                     // A4 | Letter | Legal | �
        'showDate'    => true,                     // print date in header
        'showPageNum' => true,                     // page number in footer
        'tableClass'  => 'table-striped',          // extra class on printed table
    ],
])
`'

The print window:
- Reads visible DOM rows (respects current search/filter/pagination)
- Strips Livewire/Alpine attributes from cloned table
- Applies configurable CSS (@page size, font, coloring)
- Triggers window.print() automatically after 400 ms

> **Requires pop-ups allowed** in the browser for the origin.

### Three-Syntax Code Generator (index.html)

The live code generator now has an **Output Syntax** radio selector:

| Option | Output |
|--------|--------|
| @livewire('aftable', [...]) | PHP array in a Blade helper (default) |
| <livewire:aftable � /> | Livewire tag syntax with @php config block |
| @aftable([...]) � @endaftable | Paired Blade directive with optional custom table |

### New Test Page Tabs

Two new tabs on /aftable/test:

| Tab | Purpose |
|-----|---------|
| **Custom Template** | Shows @aftable/@endaftable with custom <table class=""> |
| **Print Demo** | Shows isolated print window with landscape A4 printConfig |

### TestTraitCommand � All Missing Methods Added

Fixed BadMethodCallException on php artisan af-table:test-trait --suite=performance. Added:
- 
unQueryBuildingTest() � tests uildUnifiedQuery(), pplyCustomQueryConstraints()
- 
unColumnManagementTest() � tests initializeColumns(), getVisibleColumns(), 	oggleColumnVisibility()
- 
unSearchFilterTest() � tests static search via getStaticPaginatedData()
- 
unPropertyValidationTest() � checks all public properties including printConfig
- 
unPerformanceTests() � real DB benchmarks (page-load, search, sort, distinct values timing)
- 
unRelationshipTests() � tests detectRelations(), parseRelationDefinition()
- 
unPerformanceOptimizationTests() � checks optimization methods
- displayEnhancedFinalResults() � shows pass/fail table with % score
