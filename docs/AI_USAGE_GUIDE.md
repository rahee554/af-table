# 🤖 ArtFlow Table - AI Usage Guide (Non-Technical)

> **For AI Agents: Simple, practical guide to using ArtFlow Table in Blade views**

---

## ⚡ Quick Summary for AI

The ArtFlow Table is a **Livewire datatable component** that displays data with automatic:
- Sorting
- Searching  
- Pagination
- Eager loading (no N+1 queries)
- Count aggregations

**Version:** 1.5.2 | **Site:** https://artflow.pk

**Use it in Blade views with:** `@livewire('aftable', [...])`

---

## 🎯 Basic Usage Pattern

### Minimal Example
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Item Name'],
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'amount', 'label' => 'Amount'],
    ],
])
```

### What This Does
1. Fetches all items from database
2. Shows them in a table with pagination
3. Allows clicking column headers to sort
4. Allows searching in the search box
5. Everything is automatic! ✨

---

## 🔧 Common Configuration Options

### Model & Data
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',             # Your Eloquent model
    'records' => 25,                          # Items per page (default: 50)
    'customQuery' => $query,                  # Optional: custom query builder
    'data' => $collection,                    # Optional: pre-fetched data
])
```

### Columns Configuration

#### Simple Column (Automatic Everything!)
```php
['key' => 'title', 'label' => 'Item Name']
```
- Sorting: ✅ Enabled
- Searching: ✅ Enabled (text only)
- Eager loading: ✅ Enabled

#### Relationship Column (Shows Related Data)
```php
['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name']
```
- Fetches `category.name` for each item
- Auto eager loads (no N+1!)
- Fully sortable and searchable

#### Count Column (Shows Count of Related Items)
```php
['key' => 'subitems_count', 'label' => 'Sub-items']
```
- Uses `Subitem` relationship auto-detected from model
- Shows count of sub-items per item
- **Must match relation name + `_count`**
- Example: If model has `subitems()` relation, use `subitems_count`

#### Hidden Column (Not Displayed, But Sortable)
```php
['key' => 'id', 'hidden' => true]
```
- Column exists for data but not shown
- Can still sort by it if needed

#### Raw/HTML Column
```php
['key' => 'actions', 'raw' => '<button onclick="...">Edit</button>']
```
- Display custom HTML (buttons, links, etc.)
- Use carefully - HTML is not escaped

#### Column with Custom Formatter
```php
[
    'key' => 'price',
    'label' => 'Price',
    'value_type' => 'price'  # Formats as $X,XXX.XX
]
```

---

## 🎨 Styling & Display

### Set Custom CSS Classes
```blade
@livewire('aftable', [
    'tableClass' => 'w-full border-collapse',
    'theadClass' => 'bg-gray-100',
    'rowClass' => 'hover:bg-gray-50',
])
```

### Show/Hide Elements
```blade
@livewire('aftable', [
    'showPagination' => true,        # Show pagination controls
    'showSearch' => true,            # Show search box
    'showColumnVisibility' => true,  # Show column toggle
    'showExport' => true,            # Show export button
])
```

---

## 🔍 Filtering & Searching

### Search Box
```blade
@livewire('aftable', [
    'searchable' => true,  # Enable search (default: true)
])
```
- User types in search box
- Automatically searches text columns
- Automatically searches relationship columns (if configured with `relation`)

### Multiple Columns Example
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'description', 'label' => 'Description'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        ['key' => 'department_name', 'label' => 'Department', 'relation' => 'department:name'],
    ],
])
```
- Search searches ALL these columns
- Includes relationship columns
- All automatic!

---

## 🔗 Working with Relationships

### Eager Load Related Data (No N+1!)
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        ['key' => 'department_name', 'label' => 'Department', 'relation' => 'department:name'],
        ['key' => 'supplier_name', 'label' => 'Supplier', 'relation' => 'supplier:name'],
    ],
])
```

**What Happens:**
1. System detects 3 relationships
2. Auto eager loads all 3 (single query!)
3. No N+1 problem - shows 100+ items with 1 query

**Relationship Format:** `'relation' => 'relationName:columnName'`
- `relationName` = method name on model (e.g., `category()`)
- `columnName` = column to display (e.g., `name`)

### Count Aggregations (Show Related Count)
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'subitems_count', 'label' => 'Sub-items'],
        ['key' => 'related_count', 'label' => 'Related Items'],
        ['key' => 'attachments_count', 'label' => 'Attachments'],
    ],
])
```

**What Happens:**
1. System detects `_count` suffix
2. Extracts relation name: `subitems`, `related`, `attachments`
3. Auto adds `withCount()` to query
4. Shows count without loading all related items
5. Single query! No N+1!

**Rules:**
- Column key must be: `relationName_count`
- Model must have the relationship defined
- Example: Model has `subitems()` → use `subitems_count`

---

## 💾 Actions (Buttons)

### Simple Button Action
```php
[
    'key' => 'actions',
    'label' => 'Actions',
    'actions' => [
        [
            'type' => 'button',
            'label' => 'Edit',
            'href' => '/items/{id}/edit',
            'class' => 'btn btn-primary btn-sm',
        ],
        [
            'type' => 'button',
            'label' => 'Delete',
            'href' => '/items/{id}',
            'method' => 'DELETE',
            'confirm' => 'Are you sure?',
            'class' => 'btn btn-danger btn-sm',
        ],
    ]
]
```

### Toggle Action
```php
[
    'type' => 'toggle',
    'label' => 'Active',
    'href' => '/items/{id}/toggle-active',
    'method' => 'POST',
    'activeExpression' => 'is_active === true',
    'activeClass' => 'bg-green-500',
    'inactiveClass' => 'bg-red-500',
]
```

### Raw HTML Action (Custom)
```php
[
    'type' => 'raw',
    'content' => '<span class="badge">{{ status }}</span>',
]
```

---

## 📊 Export Data

### Enable Export
```blade
@livewire('aftable', [
    'showExport' => true,
    'exportFormats' => ['csv', 'excel', 'pdf'],
])
```

### Export Formats
- **CSV** - Plain text, opens in Excel
- **Excel** - Formatted spreadsheet
- **PDF** - Printable document

**User clicks Export button → Downloads file → Done!**

---

## 🎮 Advanced Examples

### Items Inventory Table
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'records' => 50,
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'hidden' => true],
        ['key' => 'title', 'label' => 'Item Name'],
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        ['key' => 'supplier_name', 'label' => 'Supplier', 'relation' => 'supplier:name'],
        ['key' => 'amount', 'label' => 'Amount', 'value_type' => 'price'],
        ['key' => 'subitems_count', 'label' => 'Sub-items'],
        ['key' => 'quantity', 'label' => 'Quantity'],
        [
            'key' => 'actions',
            'label' => 'Actions',
            'actions' => [
                ['type' => 'button', 'label' => 'Edit', 'href' => '/items/{id}/edit', 'class' => 'btn btn-sm'],
                ['type' => 'button', 'label' => 'Delete', 'href' => '/items/{id}', 'method' => 'DELETE', 'confirm' => 'Sure?'],
            ]
        ],
    ],
])
```

### User Management Table
```blade
@livewire('aftable', [
    'model' => 'App\Models\User',
    'records' => 25,
    'columns' => [
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'department_name', 'label' => 'Department', 'relation' => 'department:name'],
        ['key' => 'organization_name', 'label' => 'Organization', 'relation' => 'organization:name'],
        ['key' => 'is_active', 'label' => 'Status', 'value_type' => 'boolean'],
        ['key' => 'created_at', 'label' => 'Joined', 'value_type' => 'date'],
        [
            'key' => 'actions',
            'label' => '',
            'actions' => [
                ['type' => 'toggle', 'label' => 'Active', 'href' => '/users/{id}/toggle', 'method' => 'POST', 'activeExpression' => 'is_active === true'],
            ]
        ],
    ],
])
```

---

## ❓ Troubleshooting

### "Component not found" Error
**Problem:** `@livewire('aftable', [...]` doesn't work
**Solution:** Make sure package is installed: `composer require artflow-studio/table`

### Sorting Doesn't Work
**Problem:** Column headers not sortable
**Solution:** Ensure column is a database field (not a formula). Sorting only works on actual database columns.

### Search Doesn't Find Anything
**Problem:** Search returns no results
**Solution:** Make sure you're searching text columns. Numbers, dates, and boolean fields won't search.

### N+1 Query Problem (Slow Page)
**Problem:** Slow loading, many queries in DevTools
**Solution:** Use `'relation'` for related data (e.g., `'relation' => 'category:name'`)

### Related Data Shows "null"
**Problem:** Relationship column shows blank/null
**Solution:** 
1. Ensure relation name is correct (e.g., `category()` method exists on model)
2. Ensure column name exists (e.g., `category.name` exists)
3. Use format: `'relation' => 'category:name'`

### Count Shows 0 or Wrong Value
**Problem:** Counts wrong
**Solution:**
1. Use correct column name with `_count` suffix
2. Column must match relationship name
3. Example: If model has `variants()` relation, column must be `variants_count`

---

## 💡 Best Practices

### ✅ DO
- Use `'relation' => 'relationName:columnName'` for related data
- Use `columnName_count` for relationship counts
- Keep column keys simple (no spaces, use snake_case)
- Use `'hidden' => true` for ID columns you don't need to show
- Test on sample data first

### ❌ DON'T
- Don't use complex formulas in `'key'` (must be database fields)
- Don't mix manual sorting config with auto-sort (system handles it)
- Don't use `'raw'` HTML unless you control the content (XSS risk)
- Don't put component logic in Blade (only configuration)
- Don't overload with 50+ columns (keep it to 10-15)

---

## 🔄 Real-World Workflow for AI

**When an AI needs to use ArtFlow Table:**

1. **Identify the Model**
   - What Eloquent model? (e.g., `Product`, `User`)
   
2. **List Needed Fields**
   - What fields to display? (e.g., `name`, `email`)

3. **Identify Relationships**
   - What related data? (e.g., `category`, `brand`)
   - Format: `'relation' => 'relationName:columnName'`

4. **Check for Counts**
   - Any relationship counts? (e.g., variants, reviews)
   - Format: `columnName_count` (e.g., `variants_count`)

5. **Add Actions**
   - Any buttons? (edit, delete, toggle)
   - Format: Simple button config

6. **Render in Blade**
   ```blade
   @livewire('aftable', [
       'model' => 'App\Models\YourModel',
       'columns' => [...all columns...],
   ])
   ```

---

## 📞 Common Questions

**Q: Can I use this in a Livewire component?**
A: No, only in Blade views. The component IS the Livewire component.

**Q: How many records can it handle?**
A: Tested with 1M+ records. Pagination keeps it fast.

**Q: Can I customize the appearance?**
A: Yes, use Tailwind CSS classes. See "Styling" section.

**Q: Is it real-time?**
A: Yes! Livewire makes it reactive. Changes update instantly.

**Q: Can I add custom filters?**
A: Yes, advanced filters available. See advanced guide.

**Q: How do I handle soft deletes?**
A: Use `withTrashed()` or `onlyTrashed()` in custom query.

---

## 🚀 Next Steps

1. **Copy a working example** from "Advanced Examples" section
2. **Replace model name** with your Eloquent model
3. **Add your columns** (start simple)
4. **Test in browser** - should work immediately
5. **Add more columns** as needed
6. **Customize styling** with CSS classes

**That's it!** You now have a fully-featured datatable with sorting, searching, pagination, and more!

---

**Version:** v1.6 Optimized  
**Last Updated:** November 23, 2025  
**For:** AI Agents & Developers  
**Status:** Production Ready ✅
