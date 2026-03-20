# 📋 ArtFlow Table — Changelog v1.8.0

**Release Date:** July 2025  
**Status:** ✅ Production Ready  
**Requires:** PHP 8.2+ | Laravel 12+ | Livewire 4.2+ | livewire/blaze ^1.0

---

## 🆕 v1.8.0 — Livewire 4 Native Performance & Blaze Integration

This release focuses exclusively on **performance and rendering speed** for applications that embed the table inside other Livewire components. No breaking changes to public configuration API.

---

## ⚡ Performance Improvements

### `#[Computed]` Replaces Manual Hash Caching

The internal query result cache was previously implemented with a manual hash-based system using `$cachedQueryResults`, `$cachedQueryHash`, and `generateQueryHash()`. This has been completely replaced with Livewire 4's native `#[Computed]` attribute.

**Before (v1.7.0):**
```php
protected ?Collection $cachedQueryResults = null;
protected ?string $cachedQueryHash = null;

protected function generateQueryHash(): string { ... }

public function getData(): mixed
{
    $hash = $this->generateQueryHash();
    if ($this->cachedQueryResults !== null && $this->cachedQueryHash === $hash) {
        return $this->cachedQueryResults;
    }
    $this->cachedQueryHash = $hash;
    return $this->cachedQueryResults = $this->buildQuery()->paginate($this->records);
}

public function invalidateQueryCache(): void
{
    $this->cachedQueryResults = null;
    $this->cachedQueryHash = null;
}
```

**After (v1.8.0):**
```php
use Livewire\Attributes\Computed;

#[Computed]
public function tableData(): mixed
{
    return $this->getData();
}

public function invalidateQueryCache(): void
{
    unset($this->tableData);
}
```

**Benefits:**
- Livewire 4 memoizes computed properties for the full request lifecycle automatically
- No double-evaluation risk; invalidation is a single `unset()` call
- Computed properties are not serialized to component state, reducing payload size
- Cleaner, idiomatic Livewire 4 code

---

### `@island(defer: true)` Pagination Footer

The pagination section of the table view is now wrapped in a **deferred Livewire island**. This means the pagination controls render after the main table body, preventing them from blocking the initial paint.

```blade
@island(defer: true, name: 'pagination-footer')
    @placeholder
        {{-- Bootstrap skeleton shown while island loads --}}
        <div class="d-flex justify-content-between align-items-center p-3">
            <div class="placeholder-glow col-4">
                <span class="placeholder col-12 rounded"></span>
            </div>
            <div class="placeholder-glow col-5">
                <span class="placeholder col-12 rounded"></span>
            </div>
        </div>
    @endplaceholder
    {{-- actual pagination markup --}}
@endisland
```

The skeleton is immediately visible; the real pagination controls are streamed in by Livewire after the main response, keeping perceived load time low even on large datasets.

---

### `livewire/blaze ^1.0` — Blade Component Compilation

`livewire/blaze` is now a **hard dependency** (moved from `suggest` to `require`).

Blaze intercepts the Blade compilation pipeline via `earliestPreCompilationHook()` and **folds child Blade components into their parent templates at compile time**. This eliminates the per-component rendering overhead that occurs when the table is embedded inside other Livewire components.

**No configuration needed.** Blaze activates automatically once installed. Set `BLAZE_ENABLED=false` in your `.env` to disable if needed.

**Impact on table usage inside parent Livewire components:**
- Child `<x-*>` components inside the table's Blade view are compiled into the parent at build time
- No runtime overhead for resolving and rendering child components
- Confirmed working: Debugbar shows `__components::*` hashed entries indicating Blaze-compiled components

---

## 🔧 Dependency Change

| Package | Before | After |
|---------|--------|-------|
| `livewire/blaze` | `suggest` | `require ^1.0` |

Updated `composer.json`:
```json
"require": {
    "php": "*",
    "livewire/livewire": "^4.0",
    "illuminate/view": "*",
    "livewire/blaze": "^1.0"
},
"suggest": {
    "maatwebsite/excel": "For Excel export support (CSV/Excel download)",
    "barryvdh/laravel-snappy": "For PDF export support"
}
```

---

## 🧹 Dead Code Removed

| Removed | Reason |
|---------|--------|
| `$cachedQueryResults` property | Replaced by `#[Computed]` memoization |
| `$cachedQueryHash` property | No longer needed |
| `generateQueryHash()` method | No longer needed |

---

## 📦 New Imports in `AftableComponent`

```php
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
```

> **Note on `#[Url]`:** The `$queryString` array syntax is still used instead of `#[Url]` on individual properties because those properties are defined in traits. Livewire 4's `#[Url]` attribute requires the property to be declared directly on the component class.

---

## ✅ Verified Features (GUI Test Pass — July 2025)

All features tested against a live demo with datasets of 10,000 / 2,000 / 15,000 / 50,000 records:

| Feature | Status |
|---------|--------|
| Initial page load (Employees — 10,000 records) | ✅ |
| Search (URL parameter `?search=…`) | ✅ |
| Sort (click column header, URL `?sortColumn=…`) | ✅ |
| Clear search (× button) | ✅ |
| Filter column selection | ✅ |
| Filter value selection (URL `?filterValue=…`) | ✅ |
| Clear filter | ✅ |
| Pagination (URL `?page=2`, @island defer skeleton visible then resolved) | ✅ |
| Projects tab (2,000 records) | ✅ |
| Tasks tab (15,000 records) | ✅ |
| Timesheets tab (50,000 records, stress test) | ✅ |
| Foreach / Static Data mode | ✅ |
| Custom Template (`@aftable` / `@endaftable`) tab | ✅ |
| Print Demo (popup blocked by browser = expected; alert shown) | ✅ |
| Performance Tests tab (4 categories rendered) | ✅ |
| Export dropdown (CSV & Excel options expand correctly) | ✅ |
| Column Visibility toggle (hide/show individual columns) | ✅ |

---

## ⬆️ Upgrade Notes

**From v1.7.0 → v1.8.0:**

1. **Run `composer update`** to pull in `livewire/blaze ^1.0` (now a required dependency).
2. No changes to your Blade files or component configuration.
3. No changes to the public API (`@livewire('aftable', [...])` syntax unchanged).
4. If you previously required `livewire/blaze` yourself, you can remove it from your own `composer.json` — it is now managed by this package.
5. Optionally set `BLAZE_ENABLED=false` in `.env` if you want to opt out of Blaze compilation.
