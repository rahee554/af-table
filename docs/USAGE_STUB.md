# 📖 ArtFlow Table - Complete Usage Stub & Examples

**Version:** 1.5.2  
**Developer Site:** https://artflow.pk  
**Date:** November 23, 2025

---

## 🎯 Quick Reference

This document provides complete method signatures and usage examples for the ArtFlow Table component.

---

## 📌 Basic Method Signature

```php
@livewire('aftable', [
    // Required
    'model' => string|Illuminate\Database\Eloquent\Model,  // Eloquent model
    
    // Columns Configuration
    'columns' => array,                    // Column definitions
    
    // Pagination & Display
    'records' => int,                      // Items per page (default: 50)
    'showPagination' => bool,              // Show pagination (default: true)
    
    // Search & Filter
    'searchable' => bool,                  // Enable search (default: true)
    'showSearch' => bool,                  // Show search box (default: true)
    
    // Sorting (v1.5.2+)
    'sortBy' => string,                    // Default sort column key
    'sortDirection' => string,             // Default sort direction ('asc' or 'desc')
    'sort' => string,                      // Alias for sortDirection (backward compat)
    
    // UI & Display
    'tableClass' => string,                // Custom table CSS classes
    'theadClass' => string,                // Table header CSS classes
    'rowClass' => string|Closure,          // Row CSS classes
    
    // Export & Features
    'showExport' => bool,                  // Show export button (default: true)
    'exportFormats' => array,              // Export types: ['csv','excel','pdf']
    
    // Advanced Options
    'customQuery' => Builder,              // Custom Eloquent query
    'data' => Collection,                  // Pre-loaded data
])
```

---

## 📋 Columns Configuration

### Simple Column
```php
'columns' => [
    [
        'key' => 'title',              // Database column or field name
        'label' => 'Title',            // Display label
    ],
]
```

### Column with Relationship (Auto Eager-Load)
```php
'columns' => [
    [
        'key' => 'category_name',      // Shows: category.name
        'label' => 'Category',
        'relation' => 'category:name', // Eager load category.name
    ],
]
```

### Column with Count Aggregation
```php
'columns' => [
    [
        'key' => 'items_count',        // Shows count of related items
        'label' => 'Items',            // Auto-detected via _count suffix
    ],
]
```

### Column with Actions
```php
'columns' => [
    [
        'key' => 'actions',
        'label' => 'Actions',
        'actions' => [
            [
                'type' => 'button',
                'label' => 'Edit',
                'href' => '/items/{id}/edit',
                'class' => 'btn btn-sm btn-primary',
                'icon' => 'pencil',
            ],
            [
                'type' => 'button',
                'label' => 'Delete',
                'href' => '/items/{id}',
                'method' => 'DELETE',
                'confirm' => 'Delete this item?',
                'class' => 'btn btn-sm btn-danger',
                'icon' => 'trash',
            ],
        ],
    ],
]
```

### Column with Toggle Action
```php
'columns' => [
    [
        'type' => 'toggle',
        'label' => 'Active',
        'href' => '/items/{id}/toggle-status',
        'method' => 'POST',
        'activeExpression' => 'is_active === true',
        'activeClass' => 'bg-green-500',
        'inactiveClass' => 'bg-red-500',
    ],
]
```

### Column with Custom Formatting
```php
'columns' => [
    [
        'key' => 'amount',
        'label' => 'Amount',
        'format' => 'currency',        // Formats as $X,XXX.XX
    ],
    [
        'key' => 'created_at',
        'label' => 'Created',
        'format' => 'date:Y-m-d',      // Formats as date
    ],
    [
        'key' => 'is_featured',
        'label' => 'Featured',
        'format' => 'boolean',         // Shows ✓ or ✗
    ],
]
```

### Column with Advanced Options
```php
'columns' => [
    [
        'key' => 'email',
        'label' => 'Email',
        'sortable' => true,            // Allow sorting
        'searchable' => true,          // Allow searching
        'hidden' => false,             // Show/hide column
        'width' => '200px',            // Set column width
        'class' => 'text-left',        // Cell CSS classes
        'raw' => false,                // Escape HTML (true = allow HTML)
        'header_class' => 'bg-gray-100', // Header CSS
    ],
]
```

### Column - Hidden (Not Displayed)
```php
'columns' => [
    [
        'key' => 'id',
        'label' => 'ID',
        'hidden' => true,              // Don't show but usable for sorting
    ],
]
```

---

## 🔍 Search & Filtering

### Enable Search
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'description', 'label' => 'Description'],
    ],
    'showSearch' => true,              // Show search box
    'searchable' => true,              // Enable searching
])
```

### Search in Relationships
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        // System auto-searches category.name when user searches
    ],
])
```

---

## 📊 Sorting

### Auto-Sorting (All Columns)
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],          // Click to sort
        ['key' => 'status', 'label' => 'Status'],        // Click to sort
        ['key' => 'created_at', 'label' => 'Created'],   // Click to sort
    ],
    // All columns are sortable by default
])
```

### Default Sort (v1.5.2+)
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'sortBy' => 'created_at',          // Sort by this column initially
    'sortDirection' => 'desc',         // 'asc' or 'desc' (default: 'asc')
])
```

### Backward Compatibility
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'sortBy' => 'id',
    'sort' => 'desc',                  // 'sort' still works as an alias for sortDirection
])
```

### Non-Sortable Columns
```blade
'columns' => [
    ['key' => 'avatar', 'label' => 'Avatar', 'sortable' => false], // Disable sorting for this column
]
```

---

## 📄 Pagination

### Set Items Per Page
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'records' => 25,                   // Show 25 items per page
    'showPagination' => true,          // Show pagination controls
])
```

---

## 💾 Export

### Enable Export
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'showExport' => true,              // Show export button
    'exportFormats' => [
        'csv',                         // CSV format
        'excel',                       // Excel spreadsheet
        'pdf',                         // PDF document
    ],
])
```

---

## 🎨 Styling & Display

### Custom CSS Classes
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'tableClass' => 'w-full border-collapse',  // Table wrapper
    'theadClass' => 'bg-gray-100 font-bold',   // Table header
    'rowClass' => 'hover:bg-gray-50 border-b', // Table rows
    'headerClass' => 'px-4 py-2 text-left',    // Header cells
    'cellClass' => 'px-4 py-2',                // Data cells
])
```

### Dynamic Row Styling
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'rowClass' => function($row) {
        if ($row->status === 'error') return 'bg-red-50';
        if ($row->priority === 'high') return 'bg-yellow-50';
        if ($row->is_featured) return 'bg-blue-50';
        return '';
    },
])
```

---

## 🔗 Working with Relationships

### Eager Load Without N+1
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        ['key' => 'author_name', 'label' => 'Author', 'relation' => 'author:name'],
        ['key' => 'status_label', 'label' => 'Status', 'relation' => 'status:label'],
    ],
    // System automatically eager loads all relations!
    // Result: 1 query instead of 51 queries ✨
])
```

### Show Counts
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'comments_count', 'label' => 'Comments'],  // Auto-counted
        ['key' => 'likes_count', 'label' => 'Likes'],        // Auto-counted
        ['key' => 'views_count', 'label' => 'Views'],        // Auto-counted
    ],
    // System auto-detects _count suffix and uses withCount()!
    // Result: Efficient count aggregation ✨
])
```

---

## 🔧 Advanced Usage

### Custom Query Builder
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'customQuery' => Item::where('status', 'active')
                          ->whereHas('author', fn($q) => $q->where('verified', true))
                          ->latest(),
])
```

### Pre-Loaded Data
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'data' => collect([
        ['id' => 1, 'title' => 'Item 1'],
        ['id' => 2, 'title' => 'Item 2'],
    ]),
])
```

### Soft Deletes
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'customQuery' => Item::withTrashed(),  // Include deleted
])
```

### Tenant/Multi-Tenant
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'customQuery' => Item::whereTenantId(auth()->user()->tenant_id),
])
```

### With Authorization
```blade
@livewire('aftable', [
    'model' => 'App\Models\Item',
    'columns' => [...],
    'customQuery' => Item::where(function($q) {
        $q->where('user_id', auth()->id())
          ->orWhere('is_public', true);
    }),
])
```

---

## 🗂️ Complete Real-World Examples

### Example 1: User Management Table
```blade
@livewire('aftable', [
    'model' => 'App\Models\User',
    'records' => 25,
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'hidden' => true],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'department_name', 'label' => 'Department', 'relation' => 'department:name'],
        ['key' => 'role_name', 'label' => 'Role', 'relation' => 'role:name'],
        ['key' => 'is_active', 'label' => 'Status', 'format' => 'boolean'],
        ['key' => 'created_at', 'label' => 'Joined', 'format' => 'date:Y-m-d'],
        [
            'key' => 'actions',
            'label' => 'Actions',
            'actions' => [
                ['type' => 'button', 'label' => 'Edit', 'href' => '/users/{id}/edit', 'class' => 'btn btn-sm btn-primary'],
                ['type' => 'button', 'label' => 'Delete', 'href' => '/users/{id}', 'method' => 'DELETE', 'confirm' => 'Delete user?', 'class' => 'btn btn-sm btn-danger'],
            ],
        ],
    ],
    'showExport' => true,
    'exportFormats' => ['csv', 'excel'],
])
```

### Example 2: Products Inventory Table
```blade
@livewire('aftable', [
    'model' => 'App\Models\Product',
    'records' => 50,
    'sortBy' => 'created_at',
    'sortDirection' => 'desc',
    'columns' => [
        ['key' => 'id', 'label' => 'SKU', 'hidden' => false],
        ['key' => 'name', 'label' => 'Product Name'],
        ['key' => 'category_name', 'label' => 'Category', 'relation' => 'category:name'],
        ['key' => 'supplier_name', 'label' => 'Supplier', 'relation' => 'supplier:name'],
        ['key' => 'price', 'label' => 'Price', 'format' => 'currency'],
        ['key' => 'quantity_in_stock', 'label' => 'Stock'],
        ['key' => 'variants_count', 'label' => 'Variants'],
        ['key' => 'is_available', 'label' => 'Available', 'format' => 'boolean'],
        [
            'key' => 'actions',
            'label' => '',
            'actions' => [
                ['type' => 'button', 'label' => 'View', 'href' => '/products/{id}', 'class' => 'btn btn-sm btn-info'],
                ['type' => 'button', 'label' => 'Edit', 'href' => '/products/{id}/edit', 'class' => 'btn btn-sm btn-primary'],
            ],
        ],
    ],
    'tableClass' => 'w-full border-collapse',
    'showExport' => true,
    'exportFormats' => ['csv', 'excel', 'pdf'],
])
```

### Example 3: Orders Dashboard Table
```blade
@livewire('aftable', [
    'model' => 'App\Models\Order',
    'records' => 30,
    'sortBy' => 'created_at',
    'sortDirection' => 'desc',
    'columns' => [
        ['key' => 'order_number', 'label' => 'Order #'],
        ['key' => 'customer_name', 'label' => 'Customer', 'relation' => 'customer:name'],
        ['key' => 'total_amount', 'label' => 'Total', 'format' => 'currency'],
        ['key' => 'items_count', 'label' => 'Items'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'created_at', 'label' => 'Date', 'format' => 'date:M d, Y'],
        [
            'key' => 'actions',
            'label' => 'Actions',
            'actions' => [
                ['type' => 'button', 'label' => 'View Details', 'href' => '/orders/{id}', 'class' => 'btn btn-sm btn-info'],
                ['type' => 'button', 'label' => 'Print', 'href' => '/orders/{id}/print', 'class' => 'btn btn-sm btn-secondary'],
                ['type' => 'toggle', 'label' => 'Archive', 'href' => '/orders/{id}/toggle-archive', 'method' => 'POST'],
            ],
        ],
    ],
    'rowClass' => function($row) {
        if ($row->status === 'pending') return 'bg-yellow-50';
        if ($row->status === 'cancelled') return 'bg-red-50';
        return '';
    },
    'showSearch' => true,
    'showExport' => true,
    'exportFormats' => ['csv', 'pdf'],
])
```

### Example 4: Comments/Reviews Table
```blade
@livewire('aftable', [
    'model' => 'App\Models\Comment',
    'records' => 20,
    'columns' => [
        ['key' => 'author_name', 'label' => 'Author', 'relation' => 'author:name'],
        ['key' => 'content', 'label' => 'Comment'],
        ['key' => 'rating', 'label' => 'Rating', 'format' => 'number'],
        ['key' => 'item_title', 'label' => 'Item', 'relation' => 'item:title'],
        ['key' => 'is_approved', 'label' => 'Approved', 'format' => 'boolean'],
        ['key' => 'created_at', 'label' => 'Posted', 'format' => 'date:M d, Y h:i A'],
        [
            'key' => 'actions',
            'label' => '',
            'actions' => [
                ['type' => 'toggle', 'label' => 'Approve', 'href' => '/comments/{id}/toggle-approval', 'method' => 'POST'],
                ['type' => 'button', 'label' => 'Delete', 'href' => '/comments/{id}', 'method' => 'DELETE', 'confirm' => 'Delete comment?', 'class' => 'btn btn-sm btn-danger'],
            ],
        ],
    ],
    'tableClass' => 'w-full',
    'rowClass' => function($row) {
        return !$row->is_approved ? 'bg-gray-100 opacity-75' : '';
    },
])
```

---

## 🚀 Getting Started Checklist

- [ ] Identify the Eloquent model to display
- [ ] List columns you want to show (use generic names: name, title, status, etc.)
- [ ] Identify relationships to display (use `relation` parameter)
- [ ] Check for count columns (use `_count` suffix)
- [ ] Add actions if needed (edit, delete, toggle)
- [ ] Set pagination records count
- [ ] Configure sorting (optional)
- [ ] Enable export if needed
- [ ] Test in browser
- [ ] Verify search/sort works
- [ ] Check database queries (should be 1!)

---

## 📞 Common Issues

| Issue | Solution |
|-------|----------|
| Related data shows null | Verify relation exists on model, check column name |
| Slow loading | Use `relation` parameter for related data |
| Many queries | Use `'relation' => 'relationName:columnName'` format |
| Export not working | Set `showExport: true` and check permissions |
| Search returns nothing | Only text columns are searchable |
| `sortBy` not working | Ensure the key matches a column in your `columns` array |
| Undefined variable `$sortBy` | Upgrade to v1.5.2+ (fixed in this version) |

---

## 🔗 More Resources

- **Developer Site:** https://artflow.pk
- **Documentation:** See README.md, AI_USAGE_GUIDE.md, AI_TECHNICAL_REFERENCE.md
- **Examples:** See Real-World Examples in this file
- **Features:** See FEATURE_RECOMMENDATIONS.md

---

**Version:** 1.5.2  
**Last Updated:** November 23, 2025  
**Status:** Production Ready ✅
