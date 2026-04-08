@push('styles')
    @once
    <style>
        .aftable-table-loading { opacity: 0.5; pointer-events: none; transition: opacity 0.15s ease; }
    </style>
    @endonce
@endpush

{{-- Package assets (CSS + JS). Set assets=false on the @livewire call to disable when your theme covers these. --}}
@if($assets)
@assets
<style>
/* ── AF Table Package styles ─────────────────────────────────────── */
.aftable-skeleton .placeholder { animation: aft-pulse 1.4s ease-in-out infinite; }
@keyframes aft-pulse { 0%,100%{ opacity:.35 } 50%{ opacity:.7 } }
.table-responsive { border-radius:.375rem; }
#myTable { margin-bottom:0; background:#fff; }
#myTable th { background:#f8f9fa !important; border-top:1px solid #dee2e6; font-weight:600;
              font-size:.875rem; color:#495057; white-space:nowrap; }
#myTable th[style*="cursor:pointer"] { user-select:none; transition:background-color .15s ease-in-out; }
#myTable th[style*="cursor:pointer"]:hover { background:#e9ecef !important; }
#myTable th span { display:inline-flex; align-items:center; gap:.25rem; }
#myTable tbody tr { transition:background-color .15s ease-in-out; }
#myTable tbody tr:hover { background:#f8f9fa; }
.filter-indicator.active { border-color:#0d6efd !important; background:rgba(13,110,253,.05); }
.table-fade { animation:aft-table-fade .18s ease; }
@keyframes aft-table-fade { from{ opacity:.5; transform:translateY(2px) } to{ opacity:1; transform:none } }
.dropdown-menu { max-height:400px; overflow-y:auto; }
.dropdown-menu .form-check { padding:.5rem 1rem; margin:0; border-bottom:1px solid #f1f3f4; }
</style>
@endassets
@endif

<div class="aftable-wrapper">
    @if (!empty($filters))
        <div class="mb-3">
            {{-- Main Filter Row --}}
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small text-muted">Filter Column</label>
                    <select wire:model.live="filterColumn" class="form-select form-select-sm filter-indicator {{ $filterColumn ? 'active' : '' }}">
                        <option value="">Select Column</option>
                        @foreach ($filters as $filterKey => $filterConfig)
                            @php
                                $columnLabel = isset($columns[$filterKey])
                                    ? $columns[$filterKey]['label']
                                    : ucfirst(str_replace('_', ' ', $filterKey));
                            @endphp
                            <option value="{{ $filterKey }}">{{ $columnLabel }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($filterColumn)
                    @php
                        $filterType = isset($filters[$filterColumn]['type']) ? $filters[$filterColumn]['type'] : 'text';
                    @endphp
                    @if (in_array($filterType, ['integer', 'number', 'date']))
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small text-muted">Operator</label>
                            <select wire:model.live="filterOperator" class="form-select form-select-sm">
                                <option value="=">Equal (=)</option>
                                <option value="!=">Not Equal (≠)</option>
                                <option value="<">Less Than (<)</option>
                                <option value=">">Greater Than (>)</option>
                                <option value="<=">Less or Equal (≤)</option>
                                <option value=">=">Greater or Equal (≥)</option>
                            </select>
                        </div>
                    @endif
                @endif
                <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                    @php
                        $selectedColumn = $filterColumn ?? null;
                        $filterType =
                            $selectedColumn && isset($filters[$selectedColumn]['type'])
                                ? $filters[$selectedColumn]['type']
                                : null;
                    @endphp
                    @if ($selectedColumn)
                        <label class="form-label small text-muted">Value</label>
                        @if ($filterType === 'select' || $filterType === 'distinct')
                            <select wire:model.live="filterValue" class="form-control form-control-sm filter-indicator {{ $filterValue ? 'active' : '' }}">
                                <option value="">Select Value</option>
                                @php
                                    $distinctValues = $this->getDistinctValues($selectedColumn);
                                @endphp
                                @if (is_array($distinctValues) && count($distinctValues) > 0)
                                    @if (array_keys($distinctValues) === range(0, count($distinctValues) - 1))
                                        {{-- Simple indexed array - use value as both key and display --}}
                                        @foreach ($distinctValues as $val)
                                            <option value="{{ $val }}">{{ $val }}</option>
                                        @endforeach
                                    @else
                                        {{-- Associative array - use key as value and value as display --}}
                                        @foreach ($distinctValues as $key => $val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    @endif
                                @endif
                            </select>
                        @elseif($filterType === 'date')
                            <input type="date" wire:model.live.debounce.500ms="filterValue"
                                class="form-control form-control-sm filter-indicator {{ $filterValue ? 'active' : '' }}">
                        @elseif(in_array($filterType, ['integer', 'number']))
                            <input type="number" wire:model.live.debounce.500ms="filterValue" placeholder="Enter number..."
                                class="form-control form-control-sm filter-indicator {{ $filterValue ? 'active' : '' }}">
                        @else
                            <input type="text" wire:model.live.debounce.500ms="filterValue" placeholder="Enter at least 3 characters..."
                                class="form-control form-control-sm filter-indicator {{ $filterValue && strlen($filterValue) >= 3 ? 'active' : '' }}" minlength="3">
                        @endif
                    @else
                        <label class="form-label small text-muted">Value</label>
                        <input type="text" placeholder="Select a column first" class="form-control form-control-sm"
                            disabled>
                    @endif
                </div>
                <div class="col-auto">
                    <button type="button"
                        wire:click="$set('filterColumn', null); $set('filterValue', null); $set('filterOperator', '=')"
                        class="btn btn-sm btn-outline-secondary" title="Clear filters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
                <div class="col-auto">
                    <button type="button" wire:click="addFilterInstance" class="btn btn-sm btn-outline-primary" title="Add another filter">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            {{-- Additional Filter Instances --}}
            @if (!empty($filterInstances))
                @foreach ($filterInstances as $index => $filterInstance)
                    <div class="row g-2 mb-2 align-items-end border-start border-3 border-primary ps-3" style="margin-left: 10px;">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small text-muted">Filter Column</label>
                            <select wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'column', $event.target.value)" 
                                    class="form-select form-select-sm filter-indicator {{ $filterInstance['column'] ? 'active' : '' }}">
                                <option value="">Select Column</option>
                                @foreach ($filters as $filterKey => $filterConfig)
                                    @php
                                        $columnLabel = isset($columns[$filterKey])
                                            ? $columns[$filterKey]['label']
                                            : ucfirst(str_replace('_', ' ', $filterKey));
                                    @endphp
                                    <option value="{{ $filterKey }}" {{ $filterInstance['column'] === $filterKey ? 'selected' : '' }}>
                                        {{ $columnLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($filterInstance['column'])
                            @php
                                $instanceFilterType = isset($filters[$filterInstance['column']]['type']) ? $filters[$filterInstance['column']]['type'] : 'text';
                            @endphp
                            @if (in_array($instanceFilterType, ['integer', 'number', 'date']))
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label class="form-label small text-muted">Operator</label>
                                    <select wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'operator', $event.target.value)" 
                                            class="form-select form-select-sm">
                                        <option value="=" {{ $filterInstance['operator'] === '=' ? 'selected' : '' }}>Equal (=)</option>
                                        <option value="!=" {{ $filterInstance['operator'] === '!=' ? 'selected' : '' }}>Not Equal (≠)</option>
                                        <option value="<" {{ $filterInstance['operator'] === '<' ? 'selected' : '' }}>Less Than (<)</option>
                                        <option value=">" {{ $filterInstance['operator'] === '>' ? 'selected' : '' }}>Greater Than (>)</option>
                                        <option value="<=" {{ $filterInstance['operator'] === '<=' ? 'selected' : '' }}>Less or Equal (≤)</option>
                                        <option value=">=" {{ $filterInstance['operator'] === '>=' ? 'selected' : '' }}>Greater or Equal (≥)</option>
                                    </select>
                                </div>
                            @endif
                        @endif
                        <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                            @if ($filterInstance['column'])
                                <label class="form-label small text-muted">Value</label>
                                @if ($instanceFilterType === 'select' || $instanceFilterType === 'distinct')
                                    <select wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)" 
                                            class="form-control form-control-sm filter-indicator {{ $filterInstance['value'] ? 'active' : '' }}">
                                        <option value="">Select Value</option>
                                        @php
                                            $distinctValues = $this->getDistinctValues($filterInstance['column']);
                                        @endphp
                                        @if (is_array($distinctValues) && count($distinctValues) > 0)
                                            @if (array_keys($distinctValues) === range(0, count($distinctValues) - 1))
                                                @foreach ($distinctValues as $val)
                                                    <option value="{{ $val }}" {{ $filterInstance['value'] === $val ? 'selected' : '' }}>
                                                        {{ $val }}
                                                    </option>
                                                @endforeach
                                            @else
                                                @foreach ($distinctValues as $key => $val)
                                                    <option value="{{ $key }}" {{ $filterInstance['value'] === $key ? 'selected' : '' }}>
                                                        {{ $val }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        @endif
                                    </select>
                                @elseif($instanceFilterType === 'date')
                                    <input type="date" 
                                           wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)"
                                           value="{{ $filterInstance['value'] }}"
                                           class="form-control form-control-sm filter-indicator {{ $filterInstance['value'] ? 'active' : '' }}">
                                @elseif(in_array($instanceFilterType, ['integer', 'number']))
                                    <input type="number" 
                                           wire:input.debounce.500ms="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)"
                                           value="{{ $filterInstance['value'] }}"
                                           placeholder="Enter number..."
                                           class="form-control form-control-sm filter-indicator {{ $filterInstance['value'] ? 'active' : '' }}">
                                @else
                                    <input type="text" 
                                           wire:input.debounce.500ms="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)"
                                           value="{{ $filterInstance['value'] }}"
                                           placeholder="Enter at least 3 characters..."
                                           class="form-control form-control-sm filter-indicator {{ $filterInstance['value'] && strlen($filterInstance['value']) >= 3 ? 'active' : '' }}" 
                                           minlength="3">
                                @endif
                            @else
                                <label class="form-label small text-muted">Value</label>
                                <input type="text" placeholder="Select a column first" class="form-control form-control-sm" disabled>
                            @endif
                        </div>
                        <div class="col-auto">
                            <button type="button" 
                                    wire:click="removeFilterInstance('{{ $filterInstance['id'] }}')" 
                                    class="btn btn-sm btn-outline-danger" 
                                    title="Remove this filter">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
                <div class="row">
                    <div class="col-12">
                        <button type="button" 
                                wire:click="clearAllFilterInstances" 
                                class="btn btn-sm btn-outline-warning" 
                                title="Clear all additional filters">
                            <i class="fas fa-broom"></i> Clear All Additional Filters
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
    <div class="row mb-2">
        <div class="col">
            <div class="w-100px">
              @if ($searchable)
                <div class="position-relative w-md-250px me-2">
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search (min 3 chars)..."
                        class="form-control form-control-sm border-0 p-2 pe-4" minlength="3">

                    @if (!empty($search))
                        <span class="position-absolute top-50 end-0 translate-middle-y me-2 cursor-pointer text-muted"
                            style="z-index: 1;" wire:click="$set('search', '')">
                            &times;
                        </span>
                    @endif
                </div>
            @endif
            </div>
        </div>


        <div class="col d-flex justify-content-end">
          

            @if ($colvisBtn == true)
                <div class="dropdown me-2" id="columnVisibilityDropdownWrapper">
                    <button class="btn btn-outline btn-sm dropdown-toggle" type="button" id="columnVisibilityDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Column Visibility
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="columnVisibilityDropdown">
                        @foreach ($columns as $columnKey => $column)
                            @php
                                // Determine current visibility state
                                $isCurrentlyVisible = isset($visibleColumns[$columnKey]) ? (bool)$visibleColumns[$columnKey] : !($column['hide'] ?? false);
                            @endphp
                            <li class="cursor-pointer">
                                <div class="form-check form-switch form-check-custom form-check-solid me-10">
                                    <input class="form-check-input h-20px w-30px m-1" type="checkbox"
                                        id="column-{{ $columnKey }}"
                                        wire:click="toggleColumnVisibility('{{ $columnKey }}')"
                                        {{ $isCurrentlyVisible ? 'checked' : '' }}>
                                    <label class="form-check-label" for="column-{{ $columnKey }}">
                                        {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
                                        {{-- //** Key of the Field **//
                                        
                                        @if(!isset($column['json']))
                                            <small class="text-muted">({{ $columnKey }})</small>
                                        @endif --}}
                                    </label>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if ($exportable)
                <div class="dropdown me-2">
                    <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" id="exportDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li>
                            <a class="dropdown-item" href="#" wire:click.prevent="handleExport('csv')">
                                <i class="fas fa-file-csv"></i> Export as CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" wire:click.prevent="handleExport('xlsx')">
                                <i class="fas fa-file-excel"></i> Export as Excel
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" wire:click.prevent="handleExport('pdf')">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
            
            @if ($refreshBtn == true)
                <button wire:click="refreshTable" class="btn btn-sm btn-light mb-2">
                    <i class="fas fa-refresh"></i>
                </button>
            @endif
            
            @if ($printable)
                <button onclick="aftablePrint('{{ $tableId ?? 'myTable' }}', @json($printConfig))"
                    class="btn btn-sm btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
            @endif
        </div>
    </div>

    {{-- Single island containing table + pagination footer: when records/sort/page changes inside, the whole island re-renders with fresh data --}}
    @island(name: 'aftable-data', always: true)
        @php
            // Island renders bypass render() and don't get the $data view variable;
            // fall back to the #[Computed] property so data is always available.
            $data ??= $this->tableData;
        @endphp

    <div class="table-responsive rounded aftable-table-wrap {{ (isset($data) && count($data) < 5) ? 'h-500px' : '' }}"
         wire:loading.class="aftable-table-loading"
         wire:loading.attr="aria-busy"
    >
        @php
            // Allow @aftable/@endaftable to pass a custom <table> tag with user's theme classes.
            // We extract its attributes and apply them to our table element, keeping all
            // thead/tbody rendering intact. Falls back to the default Bootstrap table.
            $customTableOpen = '<table class="table table-bordered p-2" id="' . ($tableId ?? 'myTable') . '">';
            if (! empty($customTemplate)) {
                if (preg_match('/<table([^>]*)>/i', $customTemplate, $_m)) {
                    $_attrs = trim($_m[1] ?? '');
                    if (! str_contains($_attrs, 'id=')) {
                        $_attrs .= ' id="' . ($tableId ?? 'myTable') . '"';
                    }
                    $customTableOpen = '<table'.(strlen($_attrs) ? ' '.$_attrs : '').'>';
                }
                unset($_m, $_attrs);
            }
        @endphp
        {!! $customTableOpen !!}
            <thead>
                <tr>
                    @if ($checkbox)
                        <th class="fw-bold bg-light">
                            <div class="form-check">
                                <input class="form-check-input" wire:model.live="selectAll" type="checkbox"
                                    data-kt-check-target="#{{ $tableId ?? 'myTable' }} .form-check-input" />
                            </div>
                        </th>
                    @endif

                    @if (!isset($index) || $index)
                        <th class="fw-bold bg-light" wire:click.preserve-scroll="toggleSort('updated_at')" style="cursor:pointer;">
                            <span class="d-inline-flex align-items-center">
                                #
                                @if ($sortBy == 'updated_at')
                                    <span class="ms-1" style="font-size:1em; line-height:1;">{{ $sortDirection == 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </span>
                        </th>
                    @endif

                    @foreach ($columns as $columnKey => $column)
                        @php
                            // Determine if column should be visible - default to true unless explicitly hidden
                            $isVisible = isset($visibleColumns[$columnKey]) ? (bool)$visibleColumns[$columnKey] : !($column['hide'] ?? false);
                        @endphp
                        @if ($isVisible)
                            @php
                                // Check if this is a nested relation that shouldn't be sortable
                                $isNestedRelation = isset($column['relation']) && 
                                    (strpos($column['relation'], '.') !== false && strpos($column['relation'], ':') !== false);
                                // JSON columns are never sortable (computed values)
                                $isJsonColumn = isset($column['json']);
                                // Function columns are never sortable, regular columns with keys are sortable unless nested or JSON
                                $isSortable = !isset($column['function']) && isset($column['key']) && !$isNestedRelation && !$isJsonColumn;
                                
                                // Count nesting levels for relation complexity
                                $nestingLevel = 0;
                                if (isset($column['relation'])) {
                                    [$relation, $attribute] = explode(':', $column['relation']);
                                    $nestingLevel = substr_count($relation, '.') + substr_count($attribute, '.');
                                }
                            @endphp
                            <th class="fw-bold bg-light position-relative {{ $column['th_class'] ?? $column['class'] ?? '' }}"
                                @if($isSortable)
                                    wire:click.preserve-scroll="toggleSort('{{ $column['key'] }}')" style="cursor:pointer;"
                                @endif
                            >
                                <span class="d-inline-flex align-items-center">
                                    {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
                                    {{-- <small class="text-muted ms-1">({{ $columnKey }})</small> --}}
                                    @if ($isSortable && $sortBy == $column['key'])
                                        <span class="ms-1"
                                            style="font-size:1em; line-height:1;">{{ $sortDirection == 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                    @if($isNestedRelation)
                                        <small class="text-muted ms-1" title="Nested relations are not sortable for performance and stability reasons">(Not Sortable)</small>
                                    @endif
                                    @if($isJsonColumn)
                                        <small class="text-muted ms-1" title="JSON columns are not sortable (computed values)">(Not Sortable)</small>
                                    @endif
                                </span>
                            </th>
                        @endif
                    @endforeach

                    @if (!empty($actions))
                        <th class="fw-bold bg-light">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody id="datatable-tbody">
                @if (!isset($data) || count($data) === 0)
                    <x-artflow-table::empty-state />
                @else
                    @foreach ($data as $row)
                        <tr wire:key="row-{{ $row->id ?? $loop->index }}">
                            @if ($checkbox)
                                <td><input type="checkbox" wire:model.live="selectedRows" value="{{ $row->id }}" class="form-check-input"></td>
                            @endif

                            @if (!isset($index) || $index)
                                <td>
                                    @php
                                        // Calculate index based on sorting order
                                        $baseIndex = ($data->currentPage() - 1) * $data->perPage() + $loop->iteration;
                                        
                                        // If sorting by updated_at DESC or any DESC order, reverse the index calculation
                                        if ($sortDirection === 'desc') {
                                            // Get total count for proper reverse indexing
                                            $totalCount = $data->total();
                                            $displayIndex = $totalCount - (($data->currentPage() - 1) * $data->perPage()) - ($loop->iteration - 1);
                                        } else {
                                            // Normal ascending order
                                            $displayIndex = $baseIndex;
                                        }
                                    @endphp
                                    {{ $displayIndex }}
                                </td>
                            @endif

                            @foreach ($columns as $columnKey => $column)
                                @php
                                    // Determine if column should be visible - default to true unless explicitly hidden
                                    $isVisible = isset($visibleColumns[$columnKey]) ? (bool)$visibleColumns[$columnKey] : !($column['hide'] ?? false);
                                @endphp
                                @if ($isVisible)
                                    <td class="{{ $column['td_class'] ?? $column['class'] ?? '' }}">
                                        @if (isset($column['json']))
                                            {{-- Handle JSON column with json - this takes priority over other column types --}}
                                            @php
                                                $jsonColumn = $column['key'];
                                                $jsonPath = $column['json'];
                                                $value = $this->extractJsonValue($row, $jsonColumn, $jsonPath);
                                                
                                                // Format value for display
                                                if (is_bool($value)) {
                                                    $value = $value ? 'Yes' : 'No';
                                                } elseif (is_array($value) || is_object($value)) {
                                                    $value = json_encode($value);
                                                }
                                            @endphp
                                            {{ $value ?? '' }}
                                        @elseif (isset($column['function']))
                                            {{-- Handle function-based columns --}}
                                            @if (isset($column['raw']))
                                                {{-- Function with custom raw template --}}
                                                {!! $this->renderRawHtml($column['raw'], $row) !!}
                                            @else
                                                {{-- Function without raw template - display result directly --}}
                                                @php
                                                    $functionName = $column['function'];
                                                    if (method_exists($row, $functionName)) {
                                                        $result = $row->$functionName();
                                                        echo is_bool($result) ? ($result ? 'Yes' : 'No') : $result;
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                @endphp
                                            @endif
                                        @elseif (isset($column['raw']))
                                            {{-- Handle regular raw templates --}}
                                            {!! $this->renderRawHtml($column['raw'], $row) !!}
                                        @elseif (isset($column['relation']))
                                            {{-- Handle relationship columns with multi-level nested support --}}
                                            @php 
                                                [$relation, $attribute] = explode(':', $column['relation']); 
                                                
                                                // Handle multi-level nested relationships and attributes
                                                $relationParts = explode('.', $relation);
                                                $attributeParts = explode('.', $attribute);
                                                
                                                $value = $row;
                                                
                                                // Traverse through relation parts with null safety
                                                foreach ($relationParts as $relationPart) {
                                                    if ($value && method_exists($value, $relationPart)) {
                                                        $value = $value->$relationPart ?? null;
                                                    } else {
                                                        $value = null;
                                                        break;
                                                    }
                                                }
                                                
                                                // Traverse through attribute parts with null safety
                                                if ($value) {
                                                    foreach ($attributeParts as $attributePart) {
                                                        if (is_object($value) && isset($value->$attributePart)) {
                                                            $value = $value->$attributePart ?? null;
                                                        } elseif (is_array($value) && isset($value[$attributePart])) {
                                                            $value = $value[$attributePart] ?? null;
                                                        } else {
                                                            $value = null;
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                // Format value for display
                                                if (is_object($value) && method_exists($value, '__toString')) {
                                                    $value = (string) $value;
                                                } elseif (is_array($value)) {
                                                    $value = json_encode($value);
                                                } elseif (is_bool($value)) {
                                                    $value = $value ? 'Yes' : 'No';
                                                }
                                            @endphp
                                            {{ $value ?? '' }}
                                        @elseif (isset($column['key']))
                                            {{-- Handle regular database columns --}}
                                            {{ $row->{$column['key']} ?? '' }}
                                        @else
                                            {{-- Fallback for columns without key, function, or relation --}}
                                            N/A
                                        @endif
                                    </td>
                                @endif
                            @endforeach

                            @if (!empty($actions))
                                <td>
                                    @foreach ($actions as $actionKey => $action)
                                        @if (is_array($action))
                                            {{-- Handle structured button actions --}}
                                            @if (isset($action['raw']))
                                                {{-- Raw HTML action --}}
                                                {!! $this->renderRawHtml($action['raw'], $row) !!}
                                            @else
                                                {{-- Button action with route/params --}}
                                                @php
                                                    $label = $action['label'] ?? ucfirst($actionKey);
                                                    $route = $action['route'] ?? null;
                                                    $params = $action['params'] ?? [];
                                                    $method = $action['method'] ?? 'GET';
                                                    $class = $action['class'] ?? 'btn btn-sm btn-secondary';
                                                    $icon = $action['icon'] ?? null;
                                                    $confirm = $action['confirm'] ?? null;
                                                    
                                                    // Build route parameters
                                                    $routeParams = [];
                                                    foreach ($params as $param) {
                                                        $routeParams[] = $row->$param ?? null;
                                                    }
                                                    
                                                    // Generate URL
                                                    $url = $route ? route($route, $routeParams) : '#';
                                                    
                                                    // Generate unique ID for the button
                                                    $buttonId = 'action-' . $actionKey . '-' . $row->id;
                                                @endphp
                                                
                                                @if ($method === 'DELETE' || $method === 'POST' || $method === 'PUT' || $method === 'PATCH')
                                                    {{-- Form-based action for DELETE/POST/PUT/PATCH --}}
                                                    <form action="{{ $url }}" method="POST" style="display: inline-block;" 
                                                          @if($confirm) onsubmit="return confirm('{{ $confirm }}')" @endif>
                                                        @csrf
                                                        @if($method !== 'POST')
                                                            @method($method)
                                                        @endif
                                                        <button type="submit" class="{{ $class }}">
                                                            @if($icon)
                                                                <i class="{{ $icon }}"></i>
                                                            @endif
                                                            {{ $label }}
                                                        </button>
                                                    </form>
                                                @else
                                                    {{-- Link-based action for GET --}}
                                                    <a href="{{ $url }}" 
                                                       class="{{ $class }}"
                                                       @if($confirm) onclick="return confirm('{{ $confirm }}')" @endif
                                                       wire:navigate>
                                                        @if($icon)
                                                            <i class="{{ $icon }}"></i>
                                                        @endif
                                                        {{ $label }}
                                                    </a>
                                                @endif
                                            @endif
                                        @else
                                            {{-- Legacy: string-based raw HTML action --}}
                                            {!! $this->renderRawHtml($action, $row) !!}
                                        @endif
                                    @endforeach
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

        {{-- Pagination footer: inside the same island so records change re-renders both table and pagination together --}}
        <div>
            @if(isset($data))
                {{ $data->links('artflow-table::livewire.pagination') }}
            @endif
            @if(isset($data) && $data->total() > 10)
                <div class="w-100px">
                    <select wire:model.live="records" id="records" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                    </select>
                </div>
            @endif
        </div>
    @endisland
</div>

@assets
<script>
/**
 * AftableComponent — isolated print window
 *
 * Opens a child window containing only the table (no surrounding layout).
 * Reads the current DOM so the printed output always matches what the user sees.
 *
 * @param {string} tableId     – id attribute of the <table> element
 * @param {object} cfg         – printConfig from the Livewire component
 */
function aftablePrint(tableId, cfg) {
    cfg = Object.assign({
        title: '', header: '', footer: '',
        fontSize: 11, orientation: 'portrait', paperSize: 'A4',
        showDate: true, showPageNum: true, tableClass: ''
    }, cfg || {});

    var table = document.getElementById(tableId);
    if (!table) { alert('Table not found: ' + tableId); return; }

    // Clone the visible table so we don't disturb the live DOM
    var clone = table.cloneNode(true);
    // Remove action-column cells (last column if it contains links/buttons)
    clone.querySelectorAll('th:last-child, td:last-child').forEach(function (cell) {
        if (cell.querySelector('a,button,form')) { cell.remove(); }
    });
    // Strip wire: and x- attributes from the clone to keep HTML clean
    clone.querySelectorAll('[wire\\:click],[wire\\:model],[x-data]').forEach(function (el) {
        Array.from(el.attributes).forEach(function (attr) {
            if (attr.name.startsWith('wire:') || attr.name.startsWith('x-')) {
                el.removeAttribute(attr.name);
            }
        });
    });

    var dateStr = cfg.showDate ? new Date().toLocaleDateString() : '';
    var titleHtml = cfg.title
        ? '<h2 style="margin:0 0 4px 0;font-size:14pt;">' + cfg.title + '</h2>'
        : '';
    var headerHtml = cfg.header
        ? '<div class="print-custom-header">' + cfg.header + '</div>'
        : '';
    var footerHtml = cfg.footer
        ? '<div class="print-custom-footer">' + cfg.footer + '</div>'
        : '';

    var tableHtml = clone.outerHTML.replace(/<table/, '<table class="aft-print-table ' + (cfg.tableClass || '') + '"');

    var doc = [
        '<!DOCTYPE html><html><head>',
        '<meta charset="utf-8">',
        '<title>' + (cfg.title || 'Table Print') + '</title>',
        '<style>',
        '  @page { size: ' + cfg.paperSize + ' ' + cfg.orientation + '; margin: 1.5cm; }',
        '  * { box-sizing: border-box; }',
        '  body { font-family: Arial, Helvetica, sans-serif; font-size: ' + cfg.fontSize + 'pt; color: #111; margin: 0; padding: 0; }',
        '  .print-header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }',
        '  .print-header-left {}',
        '  .print-header-date { font-size: 9pt; color: #555; }',
        '  .print-footer { border-top: 1px solid #aaa; margin-top: 12px; padding-top: 6px; font-size: 9pt; color: #555; display: flex; justify-content: space-between; }',
        '  .print-custom-header, .print-custom-footer { margin: 8px 0; }',
        '  .aft-print-table { width: 100%; border-collapse: collapse; margin-top: 8px; page-break-inside: auto; }',
        '  .aft-print-table th, .aft-print-table td { border: 1px solid #bbb; padding: 5px 8px; text-align: left; vertical-align: top; }',
        '  .aft-print-table thead tr { background: #f0f0f0; }',
        '  .aft-print-table tr { page-break-inside: avoid; page-break-after: auto; }',
        '  .aft-print-table tbody tr:nth-child(even) { background: #fafafa; }',
        '  /* hide non-table UI elements that may end up in the clone */',
        '  .dropdown, .btn, .form-check, [wire\\:click], [x-data] { display: none !important; }',
        '  @media print {',
        '    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
        '    .no-print { display: none !important; }',
        '  }',
        '<' + '/style>',
        '<' + '/head><body>',
        '<div class="print-header">',
        '  <div class="print-header-left">' + titleHtml + headerHtml + '</div>',
        '  <div class="print-header-date">' + dateStr + '</div>',
        '</div>',
        tableHtml,
        '<div class="print-footer">',
        '  <div>' + footerHtml + '</div>',
        cfg.showPageNum ? '  <div class="page-num">Page <span id="pn"></span></div>' : '<div></div>',
        '</div>',
        cfg.showPageNum ? '<script>document.getElementById("pn").textContent=1;<\/script>' : '',
        '<' + '/body><' + '/html>'
    ].join('\n');

    var win = window.open('', '_blank', 'width=960,height=720,scrollbars=yes,resizable=yes');
    if (!win) { alert('Pop-up blocked — please allow pop-ups for this page to use the print feature.'); return; }
    win.document.open();
    win.document.write(doc);
    win.document.close();
    win.focus();
    setTimeout(function () { win.print(); }, 400);
}
</script>
@endassets

@script
    // Re-trigger table fade animation after every Livewire update
    window.addEventListener('livewire:update', function () {
        const tbody = document.querySelector('#myTable tbody');
        if (tbody) {
            tbody.classList.remove('table-fade');
            setTimeout(() => { tbody.classList.add('table-fade'); }, 10);
        }

        // Keep the column visibility dropdown open after toggling a checkbox
        const dropdownToggle = document.getElementById('columnVisibilityDropdown');
        const dropdownMenu = dropdownToggle?.nextElementSibling;
        if (window._keepColumnDropdownOpen) {
            dropdownMenu?.classList.add('show');
            dropdownToggle?.setAttribute('aria-expanded', 'true');
            window._keepColumnDropdownOpen = false;
        }
    });

    // Flag set so the dropdown stays open when a visibility checkbox is clicked
    document.getElementById('columnVisibilityDropdownWrapper')?.addEventListener('click', function (e) {
        if (e.target && e.target.matches('.form-check-input')) {
            window._keepColumnDropdownOpen = true;
        }
    });
@endscript