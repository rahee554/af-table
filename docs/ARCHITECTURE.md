# AF Table Package Architecture

## Trait-Based Modular Design

### Overview
This architecture uses Laravel traits to separate concerns and enable flexible, reusable features for the datatable component. Each trait encapsulates a specific aspect of table functionality, making it easy to extend, test, and maintain.

---

## Core Traits

### 1. `HasColumns`
- Handles column configuration, visibility, and dynamic detection
- Manages column types (key, relation, function, raw)
- Provides methods for column validation and reordering

### 2. `HasFilters`
- Manages filter configuration and application
- Supports all filter types (text, select, distinct, number, date)
- Handles filter caching and distinct value queries

### 3. `HasSorting`
- Implements sorting logic for columns and relations
- Handles default sort selection and direction validation
- Provides graceful fallback for unsupported nested relations

### 4. `HasSearch`
- Implements global and per-column search
- Supports indexed and optimized search patterns
- Handles search sanitization and security

### 5. `HasExport`
- Manages export logic for CSV, Excel, PDF
- Supports chunked and memory-optimized export
- Handles export templates and custom formats

### 6. `HasActions`
- Manages row-level actions and Blade templates
- Detects required columns for actions
- Provides event hooks for custom actions

### 7. `HasPagination`
- Handles pagination logic and configuration
- Supports dynamic records per page and custom pagination views

### 8. `HasCache`
- Manages caching for distinct values, relations, and configuration
- Provides targeted cache clearing and cache timeouts

---

## Extending the Component
- Add new traits for custom features (e.g., inline editing, column grouping)
- Override trait methods in the main component for advanced customization
- Use Laravel's service container for dependency injection and testing

---

## Example Usage
```php
class Datatable extends Component
{
    use HasColumns, HasFilters, HasSorting, HasSearch, HasExport, HasActions, HasPagination, HasCache;
    // ...component logic...
}
```

---

## Benefits
- **Separation of Concerns**: Each trait handles a single responsibility
- **Reusability**: Traits can be reused across multiple components
- **Testability**: Isolated logic makes unit testing easier
- **Extensibility**: New features can be added as traits without modifying core logic
- **Maintainability**: Cleaner codebase, easier upgrades

---

*Created: August 12, 2025 — by GitHub Copilot*
