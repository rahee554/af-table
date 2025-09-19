# Blade Template Fixes Required

## 🚨 Critical Issues in datatable-trait.blade.php

### 1. **Missing Column Visibility Implementation**

**Current Code (Broken):**
```blade
<input class="form-check-input h-20px w-30px m-1" type="checkbox"
    {{ $isCurrentlyVisible ? 'checked' : '' }}>
<label class="form-check-label" for="column-{{ $columnKey }}">
</label>
```

**Fixed Code:**
```blade
<input class="form-check-input h-20px w-30px m-1" type="checkbox"
    {{ $isCurrentlyVisible ? 'checked' : '' }}
    wire:click="toggleColumnVisibility('{{ $columnKey }}')"
    id="column-{{ $columnKey }}">
<label class="form-check-label" for="column-{{ $columnKey }}">
    {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
</label>
```

### 2. **Missing Column Data Display**

**Current Code (Broken):**
```blade
@foreach ($columns as $columnKey => $column)
    @if ($isVisible)
    @endif
@endforeach
```

**Fixed Code:**
```blade
@foreach ($columns as $columnKey => $column)
    @if ($isVisible)
        <td class="{{ $column['td_class'] ?? $column['class'] ?? '' }}">
            @if (isset($column['function']))
                {!! $this->renderRawHtml($column['function'], $row) !!}
            @elseif (isset($column['relation']))
                @php
                    [$relation, $attribute] = explode(':', $column['relation']);
                    $value = data_get($row, $relation . '.' . $attribute);
                @endphp
                {{ $value ?? '-' }}
            @elseif (isset($column['json']))
                {{ $this->extractJsonValue($row, $column['key'], $column['json']) ?? '-' }}
            @else
                {{ data_get($row, $column['key']) ?? '-' }}
            @endif
        </td>
    @endif
@endforeach
```

### 3. **Missing Action Buttons**

**Current Code (Broken):**
```blade
@if (!empty($actions))
    <td>
    </td>
@endif
```

**Fixed Code:**
```blade
@if (!empty($actions))
    <td>
        <div class="btn-group" role="group">
            @foreach ($this->getActionsForRecord($row) as $actionKey => $action)
                @if ($action['type'] === 'dropdown')
                    <div class="dropdown">
                        <button class="btn btn-sm {{ $action['class'] ?? 'btn-outline-secondary' }} dropdown-toggle" 
                                type="button" data-bs-toggle="dropdown">
                            @if ($action['icon'])
                                <i class="{{ $action['icon'] }}"></i>
                            @endif
                            {{ $action['label'] }}
                        </button>
                        <ul class="dropdown-menu">
                            @foreach ($action['items'] as $subActionKey => $subAction)
                                <li>
                                    <a class="dropdown-item" 
                                       @if ($subAction['confirm'])
                                           onclick="return confirm('{{ $subAction['confirm'] }}')"
                                       @endif
                                       @if ($subAction['route'])
                                           href="{{ route($subAction['route'], $row->id) }}"
                                       @elseif ($subAction['url'])
                                           href="{{ $subAction['url'] }}"
                                       @else
                                           wire:click="executeAction('{{ $subActionKey }}', {{ $row->id }})"
                                       @endif>
                                        @if ($subAction['icon'])
                                            <i class="{{ $subAction['icon'] }}"></i>
                                        @endif
                                        {{ $subAction['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <button class="btn btn-sm {{ $action['class'] ?? 'btn-outline-secondary' }}"
                            @if ($action['confirm'])
                                onclick="return confirm('{{ $action['confirm'] }}')"
                            @endif
                            @if ($action['route'])
                                onclick="window.location.href='{{ route($action['route'], $row->id) }}'"
                            @elseif ($action['url'])
                                onclick="window.location.href='{{ $action['url'] }}'"
                            @else
                                wire:click="executeAction('{{ $actionKey }}', {{ $row->id }})"
                            @endif>
                        @if ($action['icon'])
                            <i class="{{ $action['icon'] }}"></i>
                        @endif
                        {{ $action['label'] }}
                    </button>
                @endif
            @endforeach
        </div>
    </td>
@endif
```

### 4. **Missing Pagination Property Binding**

**Current Issue:** Using `$records` but component uses `$perPage`

**Current Code:**
```blade
<select wire:model.change="records" id="records" class="form-select form-select-sm">
```

**Fixed Code:**
```blade
<select wire:model.change="perPage" id="perPage" class="form-select form-select-sm">
```

### 5. **Missing Row Index Display**

**Current Code:**
```blade
@if (!isset($index) || $index)
    <td>
    </td>
@endif
```

**Fixed Code:**
```blade
@if (!isset($index) || $index)
    <td>
        {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
    </td>
@endif
```

### 6. **Missing Column Label in Headers**

**Current Code:**
```blade
<span class="d-inline-flex align-items-center">
</span>
```

**Fixed Code:**
```blade
<span class="d-inline-flex align-items-center">
    {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
    @if ($sortColumn == $column['key'])
        <span class="ms-1" style="font-size:1em; line-height:1;">
            {{ $sortDirection == 'asc' ? '↑' : '↓' }}
        </span>
    @endif
</span>
```

### 7. **Missing Bulk Action Checkboxes**

**Current Code:**
```blade
@if ($checkbox)
    <td><input type="checkbox" wire:model="selectedRows" value="{{ $row->id }}" class="form-check-input"></td>
@endif
```

**Fixed Code:**
```blade
@if ($checkbox)
    <td>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" 
                   wire:model="selectedRows" 
                   value="{{ $row->id }}"
                   id="row-{{ $row->id }}">
        </div>
    </td>
@endif
```

### 8. **Missing Export and Bulk Action Buttons**

**Add before the table:**
```blade
@if ($exportable || !empty($this->getAvailableBulkActions()))
    <div class="row mb-3">
        <div class="col">
            @if (!empty($this->getAvailableBulkActions()) && count($selectedRows) > 0)
                <div class="btn-group me-2">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                            type="button" data-bs-toggle="dropdown">
                        Bulk Actions ({{ count($selectedRows) }} selected)
                    </button>
                    <ul class="dropdown-menu">
                        @foreach ($this->getAvailableBulkActions() as $actionKey => $action)
                            <li>
                                <a class="dropdown-item" 
                                   @if ($action['confirm'])
                                       onclick="return confirm('{{ $action['confirm'] }}')"
                                   @endif
                                   wire:click="handleBulkAction('{{ $actionKey }}')">
                                    @if ($action['icon'])
                                        <i class="{{ $action['icon'] }}"></i>
                                    @endif
                                    {{ $action['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if ($exportable)
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                            type="button" data-bs-toggle="dropdown">
                        Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" wire:click="handleExport('csv')">CSV</a></li>
                        <li><a class="dropdown-item" wire:click="handleExport('excel')">Excel</a></li>
                        <li><a class="dropdown-item" wire:click="handleExport('json')">JSON</a></li>
                        <li><a class="dropdown-item" wire:click="handleExport('pdf')">PDF</a></li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
```

### 9. **Missing Loading States**

**Add loading indicators:**
```blade
<div wire:loading.class="opacity-50" wire:target="search,filterValue,sortBy,perPage">
    <!-- Table content -->
</div>

<div wire:loading wire:target="search,filterValue,sortBy,perPage" 
     class="position-absolute top-50 start-50 translate-middle">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
```

### 10. **Missing Error Display**

**Add error handling:**
```blade
@if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

## 📋 Summary of Required Fixes

1. ✅ Column visibility toggle functionality
2. ✅ Column data display implementation  
3. ✅ Action buttons rendering
4. ✅ Pagination property binding fix
5. ✅ Row index display
6. ✅ Column headers with labels
7. ✅ Bulk action checkboxes
8. ✅ Export and bulk action buttons
9. ✅ Loading state indicators
10. ✅ Error and success message display

These fixes will make the datatable fully functional with proper user interactions and data display.
