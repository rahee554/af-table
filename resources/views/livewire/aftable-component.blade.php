{{--
    Package assets (CSS + JS). Set assets=false on the @livewire call to
    disable when your theme covers these.

    Everything the table's own theme (bootstrap/tailwind/ui-flow/a custom
    preset) already renders correctly — table borders, row hover, header
    background — is deliberately NOT re-declared here. This used to hard-code
    Bootstrap-3-era hex colours (#f8f9fa, #dee2e6, #495057, ...) on top of
    whatever theme was active, which is why a `ui-flow`-themed table never
    looked like the rest of a ui-flow app: these rules were fighting it. What
    remains below is only the handful of things no theme provides on its own —
    animations, and small structural hooks scoped to their own aftable-*
    class so they can never leak onto an unrelated .dropdown-menu/.table
    elsewhere on the host page the way the old unscoped `.dropdown-menu`/
    `.table-responsive` rules did.
--}}
@if($assets)
@assets
<style>
.aftable-table-loading { opacity: .5; pointer-events: none; transition: opacity .15s ease; }
.aftable-skeleton .placeholder { animation: aft-pulse 1.4s ease-in-out infinite; }
@keyframes aft-pulse { 0%,100%{ opacity:.35 } 50%{ opacity:.7 } }
.aftable-table-wrap { border-radius: .375rem; }
.aftable-th--sortable { cursor: pointer; user-select: none; }
.aftable-th--sortable:hover { background-color: var(--uf-surface-hover, rgba(0,0,0,.035)); }
.aftable-th--sortable .aftable-icon { opacity: .5; }
.aftable-th--sortable[aria-sort="ascending"] .aftable-icon,
.aftable-th--sortable[aria-sort="descending"] .aftable-icon { opacity: 1; }
/* ui-flow's own .sortable rule does the identical rotate for [aria-sort=descending];
   this covers every other theme, which has no such rule of its own. Both compute
   the same transform, so there is nothing to reconcile where both apply. */
[aria-sort="descending"] svg { transform: rotate(180deg); }
.aftable-toolbar-search { position: relative; }
/* Targets the svg itself, not a wrapper element: under ui-flow the <i
   data-lucide> this icon starts as gets REPLACED by Lucide's own <svg>, so a
   rule scoped to a wrapper class stops matching the instant that swap runs. */
.aftable-toolbar-search svg { position: absolute; inset-inline-start: .625rem; top: 50%;
    transform: translateY(-50%); width: 15px; height: 15px;
    pointer-events: none; color: var(--uf-text-subtle, #6c757d); }
.aftable-toolbar-search input { padding-inline-start: 2rem; }
.aftable-dropdown-menu { max-height: 400px; overflow-y: auto; }
.aftable-dropdown-menu .form-check {
    /* padding-block/-inline-end only — never padding-inline-start (or the
       `padding` shorthand, which sets it too): a switch's own .form-switch
       rule reserves that side via a padding it deliberately matches against
       its input's negative pull-back margin (padding-inline-start: 2.5rem
       paired with margin-inline-start: -2.5rem). A same-specificity rule
       here that also sets padding-inline-start can win the cascade purely
       on load order and shrink that reservation — the negative margin still
       pulls the full 2.5rem regardless, so the switch overshoots past
       wherever the smaller padding actually put the wrapper's edge. */
    padding-block: .5rem; padding-inline-end: 1rem; margin: 0; border-bottom: 1px solid rgba(0,0,0,.06);
    /* ui-flow's own .form-check is flex + flex-wrap: wrap (built for a
       checkbox with a longer description beneath it) — nowrap keeps a
       one-line "switch  label" row from dropping the switch onto its own
       line the moment the menu is narrower than input+label combined. */
    flex-wrap: nowrap;
}
.aftable-dropdown-menu .form-check:last-child { border-bottom: none; }
.aftable-dropdown-menu .form-check-label {
    /* ui-flow's own .form-check-label sizes at the BODY font size — sized
       for a form field's label, not a compact dropdown-menu row. */
    font-size: var(--uf-text-sm, .8125rem);
}
.filter-indicator.active { border-color: var(--uf-accent-600, #0d6efd) !important; background: var(--uf-accent-soft, rgba(13,110,253,.05)); }
.table-fade { animation: aft-table-fade .18s ease; }
@keyframes aft-table-fade { from{ opacity:.5; transform:translateY(2px) } to{ opacity:1; transform:none } }
</style>
@endassets
@endif

@php
    // Resolved theme class map. Slots are stable names ('table', 'th', 'button'…)
    // so a host application can restyle any part without republishing this view:
    //   @livewire('aftable', ['theme' => 'tailwind'])
    //   @livewire('aftable', ['classes' => ['table' => 'table my-theme']])
    // See docs/THEMING.md.
    $ui ??= $this->themeClasses();
    $cls = fn (string $slot, string $extra = '') => trim(($ui[$slot] ?? '').' '.$extra);
@endphp

<div class="{{ $cls('wrapper') }}">
    @if (!empty($filters))
        <div class="mb-3">
            {{-- Main Filter Row --}}
            <div class="{{ $cls('filters.row') }}">
                <div class="{{ $cls('filters.col') }}">
                    <label class="{{ $cls('label') }}">Filter Column</label>
                    <select wire:model.live="filterColumn" class="{{ $cls('select', 'filter-indicator '.($filterColumn ? 'active' : '')) }}">
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
                        <div class="{{ $cls('filters.col.sm') }}">
                            <label class="{{ $cls('label') }}">Operator</label>
                            <select wire:model.live="filterOperator" class="{{ $cls('select') }}">
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
                <div class="{{ $cls('filters.col.value') }}">
                    @php
                        $selectedColumn = $filterColumn ?? null;
                        $filterType =
                            $selectedColumn && isset($filters[$selectedColumn]['type'])
                                ? $filters[$selectedColumn]['type']
                                : null;
                    @endphp
                    @if ($selectedColumn)
                        <label class="{{ $cls('label') }}">Value</label>
                        @if ($filterType === 'select' || $filterType === 'distinct')
                            <select wire:model.live="filterValue" class="{{ $cls('input', 'filter-indicator '.($filterValue ? 'active' : '')) }}">
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
                                class="{{ $cls('input', 'filter-indicator '.($filterValue ? 'active' : '')) }}">
                        @elseif(in_array($filterType, ['integer', 'number']))
                            <input type="number" wire:model.live.debounce.500ms="filterValue" placeholder="Enter number..."
                                class="{{ $cls('input', 'filter-indicator '.($filterValue ? 'active' : '')) }}">
                        @else
                            <input type="text" wire:model.live.debounce.500ms="filterValue" placeholder="Enter at least 3 characters..."
                                class="{{ $cls('input', 'filter-indicator '.($filterValue && strlen($filterValue) >= 3 ? 'active' : '')) }}" minlength="3">
                        @endif
                    @else
                        <label class="{{ $cls('label') }}">Value</label>
                        <input type="text" placeholder="Select a column first" class="{{ $cls('input') }}"
                            disabled>
                    @endif
                </div>
                <div class="{{ $cls('filters.col.auto') }}">
                    <button type="button"
                        wire:click="$set('filterColumn', null); $set('filterValue', null); $set('filterOperator', '=')"
                        class="{{ $cls('button') }}" title="Clear filters">
                        <x-artflow-table::icon name="times" /> Clear
                    </button>
                </div>
                <div class="{{ $cls('filters.col.auto') }}">
                    <button type="button" wire:click="addFilterInstance" class="{{ $cls('button.primary') }}" title="Add another filter">
                        <x-artflow-table::icon name="plus" />
                    </button>
                </div>
            </div>

            {{-- Additional Filter Instances --}}
            @if (!empty($filterInstances))
                @foreach ($filterInstances as $index => $filterInstance)
                    <div class="{{ $cls('filters.instance') }}">
                        <div class="{{ $cls('filters.col') }}">
                            <label class="{{ $cls('label') }}">Filter Column</label>
                            <select wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'column', $event.target.value)" 
                                    class="{{ $cls('select', 'filter-indicator '.($filterInstance['column'] ? 'active' : '')) }}">
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
                                <div class="{{ $cls('filters.col.sm') }}">
                                    <label class="{{ $cls('label') }}">Operator</label>
                                    <select wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'operator', $event.target.value)"
                                            class="{{ $cls('select') }}">
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
                        <div class="{{ $cls('filters.col.value') }}">
                            @if ($filterInstance['column'])
                                <label class="{{ $cls('label') }}">Value</label>
                                @if ($instanceFilterType === 'select' || $instanceFilterType === 'distinct')
                                    <select wire:change="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)" 
                                            class="{{ $cls('input', 'filter-indicator '.($filterInstance['value'] ? 'active' : '')) }}">
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
                                           class="{{ $cls('input', 'filter-indicator '.($filterInstance['value'] ? 'active' : '')) }}">
                                @elseif(in_array($instanceFilterType, ['integer', 'number']))
                                    <input type="number" 
                                           wire:input.debounce.500ms="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)"
                                           value="{{ $filterInstance['value'] }}"
                                           placeholder="Enter number..."
                                           class="{{ $cls('input', 'filter-indicator '.($filterInstance['value'] ? 'active' : '')) }}">
                                @else
                                    <input type="text" 
                                           wire:input.debounce.500ms="updateFilterInstance('{{ $filterInstance['id'] }}', 'value', $event.target.value)"
                                           value="{{ $filterInstance['value'] }}"
                                           placeholder="Enter at least 3 characters..."
                                           class="{{ $cls('input', 'filter-indicator '.($filterInstance['value'] && strlen($filterInstance['value']) >= 3 ? 'active' : '')) }}" 
                                           minlength="3">
                                @endif
                            @else
                                <label class="{{ $cls('label') }}">Value</label>
                                <input type="text" placeholder="Select a column first" class="{{ $cls('input') }}" disabled>
                            @endif
                        </div>
                        <div class="{{ $cls('filters.col.auto') }}">
                            <button type="button" 
                                    wire:click="removeFilterInstance('{{ $filterInstance['id'] }}')" 
                                    class="{{ $cls('button.danger') }}"
                                    title="Remove this filter">
                                <x-artflow-table::icon name="trash" />
                            </button>
                        </div>
                    </div>
                @endforeach
                <div class="row">
                    <div class="col-12">
                        <button type="button" 
                                wire:click="clearAllFilterInstances" 
                                class="{{ $cls('button.warning') }}"
                                title="Clear all additional filters">
                            <x-artflow-table::icon name="broom" /> Clear All Additional Filters
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
    @if (!empty($quickFilters))
        {{--
            Filter bar: the search box, one labelled dropdown per quick filter and a
            clear button in a single row. Replaces the toolbar's own search box (below)
            so there is never a second one. Filters and their options are locked
            server-side (see AftableComponent::$quickFilters); a request that names
            anything else is ignored.
        --}}
        <div class="{{ $cls('quickbar') }}">
            @if ($searchable)
                <div class="{{ $cls('quickbar.search') }}">
                    <label class="{{ $cls('label') }}" for="{{ $this->getId() }}-search">{{ $searchLabel }}</label>
                    <div class="{{ $cls('input.group') }}">
                        <span class="{{ $cls('input.group.text') }}"><x-artflow-table::icon name="search" lucide="search" /></span>
                        <input id="{{ $this->getId() }}-search" type="search" wire:model.live.debounce.300ms="search"
                            class="{{ $cls('input') }}" placeholder="{{ $searchPlaceholder !== '' ? $searchPlaceholder : 'Type at least 3 characters' }}" autocomplete="off">
                    </div>
                </div>
            @endif

            @foreach ($quickFilters as $quickKey => $quickFilter)
                <div class="{{ $cls('quickbar.col') }}" wire:key="quick-{{ $quickKey }}">
                    <label class="{{ $cls('label') }}" for="{{ $this->getId() }}-quick-{{ $quickKey }}">{{ $quickFilter['label'] }}</label>
                    <select id="{{ $this->getId() }}-quick-{{ $quickKey }}" wire:model.live="quick.{{ $quickKey }}"
                        class="{{ $cls('select', 'filter-indicator '.(($quick[$quickKey] ?? '') !== '' ? 'active' : '')) }}">
                        <option value="">{{ $quickFilter['placeholder'] }}</option>
                        @foreach ($quickFilter['options'] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <div class="{{ $cls('quickbar.clear') }}">
                <button type="button" wire:click="clearQuickFilters" class="{{ $cls('button') }}" @disabled(! $this->hasActiveQuickFilters())
                    aria-label="Clear filters" title="Clear filters">
                    <x-artflow-table::icon name="refresh" lucide="rotate-ccw" />
                </button>
            </div>
        </div>
    @endif

    @php
        // With quickFilters set, the search box moves into the quickbar above
        // (see the @if(!empty($quickFilters)) block) and never renders here.
        // If none of colvis/export/refresh/print are enabled either, this
        // toolbar has nothing to show — but its padding + border-bottom still
        // rendered unconditionally, leaving a bare line floating above the
        // table with nothing on it.
        $showToolbar = ($searchable && empty($quickFilters)) || $colvisBtn || $exportable || $refreshBtn || $printable;
    @endphp
    @if ($showToolbar)
    <div class="{{ $cls('toolbar') }}">
        @if ($searchable && empty($quickFilters))
            <div class="{{ $cls('toolbar.search', 'aftable-toolbar-search') }}">
                <x-artflow-table::icon name="search" lucide="search" />
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search (min 3 chars)..."
                    class="{{ $cls('search') }}" minlength="3">

                @if (!empty($search))
                    <span class="position-absolute top-50 end-0 translate-middle-y me-2 cursor-pointer text-muted"
                        style="z-index: 1;" wire:click="$set('search', '')">
                        &times;
                    </span>
                @endif
            </div>
        @endif

        <div class="{{ $cls('toolbar.end') }}">
            @if ($colvisBtn == true)
                <div class="{{ $cls('dropdown') }}" id="columnVisibilityDropdownWrapper">
                    <button class="{{ $cls('dropdown.toggle') }}" type="button" id="columnVisibilityDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <x-artflow-table::icon name="columns" lucide="columns-3" /> <span>Column Visibility</span>
                    </button>
                    {{--
                        width: max-content, not the theme's default dropdown-menu
                        min-width: for a short column list ("Name", "Type"...) that
                        default left acres of dead space to the right of every row.
                        Centering each <li> independently was tried and reverted —
                        it looks fine when every label is the same length, but
                        column labels rarely are ("Transfer ID" vs "Date"), and
                        centering per-row re-centers each switch under ITS OWN
                        label's width, so the switches drift out of a straight
                        column instead of staying aligned. Shrinking the menu to
                        its content removes the dead space without touching each
                        row's (already-correct) left alignment.
                    --}}
                    <ul class="{{ $cls('dropdown.menu', 'aftable-dropdown-menu') }}" style="width: max-content;" aria-labelledby="columnVisibilityDropdown">
                        @foreach ($columns as $columnKey => $column)
                            @php
                                // Determine current visibility state
                                $isCurrentlyVisible = isset($visibleColumns[$columnKey]) ? (bool)$visibleColumns[$columnKey] : !($column['hide'] ?? false);
                            @endphp
                            <li class="px-3 py-1">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox"
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
                <div class="{{ $cls('dropdown') }}">
                    <button class="{{ $cls('dropdown.toggle') }}" type="button" id="exportDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <x-artflow-table::icon name="download" /> Export
                    </button>
                    @php $exportFormats = $this->availableExportFormats(); @endphp
                    <ul class="{{ $cls('dropdown.menu', 'aftable-dropdown-menu') }}" aria-labelledby="exportDropdown">
                        <li>
                            <a class="{{ $cls('dropdown.item') }}" href="#" wire:click.prevent="handleExport('csv')">
                                <x-artflow-table::icon name="file" /> Export as CSV
                            </a>
                        </li>
                        {{-- Excel and PDF need optional packages; only advertise
                             them when the host app actually has one installed. --}}
                        @if ($exportFormats['xlsx'])
                            <li>
                                <a class="{{ $cls('dropdown.item') }}" href="#" wire:click.prevent="handleExport('xlsx')">
                                    <x-artflow-table::icon name="file" /> Export as Excel
                                </a>
                            </li>
                        @endif
                        @if ($exportFormats['pdf'])
                            <li>
                                <a class="{{ $cls('dropdown.item') }}" href="#" wire:click.prevent="handleExport('pdf')">
                                    <x-artflow-table::icon name="file" /> Export as PDF
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            
            @if ($refreshBtn == true)
                <button wire:click="refreshTable" class="{{ $cls('button') }}">
                    <x-artflow-table::icon name="refresh" />
                </button>
            @endif
            
            @if ($printable)
                <button onclick="aftablePrint('{{ $tableId ?? 'myTable' }}', @json($printConfig))"
                    class="{{ $cls('button') }}">
                    <x-artflow-table::icon name="print" /> Print
                </button>
            @endif
        </div>
    </div>
    @endif

    {{-- Single island containing table + pagination footer: when records/sort/page changes inside, the whole island re-renders with fresh data --}}
    {{--
        Data island.

        `always: true` re-renders it on every request, so changing the page size
        or the sort refreshes the table and its pagination footer together.

        `lazy: $deferData` defers the FIRST data query until the island scrolls
        into view, showing the skeleton below in the meantime, while the toolbar
        above renders straight away. Off by default — a single above-the-fold
        table is better served rendered.

        Note the property is `deferData`, not `lazy`: `lazy` is a reserved
        Livewire mount parameter that defers the whole component.
    --}}
    @island(name: 'aftable-data', always: true, lazy: $deferData)
        @placeholder
            <x-artflow-table::skeleton-table
                :rows="min($records ?? 10, 8)"
                :cols="max(count(array_filter($visibleColumns ?: [true])), 3)" />
        @endplaceholder

        @php
            // Island renders bypass render() and don't get the view variables;
            // fall back to the component so both are always available.
            $data ??= $this->tableData;

            // An island renders as its own compiled view, so neither the parent
            // scope's variables nor its closures survive the boundary - both
            // have to be re-established here.
            $ui = $ui ?? $this->themeClasses();
            $cls = fn (string $slot, string $extra = '') => trim(($ui[$slot] ?? '').' '.$extra);
        @endphp

    <div class="{{ $cls('responsive') }}"
         wire:loading.class="aftable-table-loading"
         wire:loading.attr="aria-busy"
    >
        @php
            // Allow @aftable/@endaftable to pass a custom <table> tag with user's theme classes.
            // We extract its attributes and apply them to our table element, keeping all
            // thead/tbody rendering intact. Falls back to the default Bootstrap table.
            $customTableOpen = '<table class="'.e($cls('table')).'" id="' . ($tableId ?? 'myTable') . '">';
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
            <thead class="{{ $cls('thead') }}">
                <tr>
                    @if ($checkbox)
                        <th class="{{ $cls('th') }}">
                            <div class="form-check">
                                <input class="{{ $cls('checkbox') }}" wire:model.live="selectAll" type="checkbox"
                                    data-kt-check-target="#{{ $tableId ?? 'myTable' }} .form-check-input" />
                            </div>
                        </th>
                    @endif

                    @if (!isset($index) || $index)
                        @php $indexSorted = $sortBy == 'updated_at'; @endphp
                        <th class="{{ $cls('th', $cls('th.sortable')) }}"
                            wire:click.preserve-scroll="toggleSort('updated_at')"
                            aria-sort="{{ $indexSorted ? ($sortDirection == 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <span class="d-inline-flex align-items-center">
                                #
                                @if ($indexSorted)
                                    <x-artflow-table::icon name="chevron-up" lucide="chevron-up" class="ms-1" />
                                @else
                                    <x-artflow-table::icon name="chevrons-up-down" lucide="chevrons-up-down" class="ms-1" />
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
                                $declaredUnsortable = ($column['sortable'] ?? null) === false;
                                $isSortable = !isset($column['function']) && isset($column['key']) && !$isNestedRelation && !$isJsonColumn && !$declaredUnsortable;
                                
                                // Count nesting levels for relation complexity
                                $nestingLevel = 0;
                                if (isset($column['relation'])) {
                                    [$relation, $attribute] = explode(':', $column['relation']);
                                    $nestingLevel = substr_count($relation, '.') + substr_count($attribute, '.');
                                }
                            @endphp
                            @php $columnSorted = $isSortable && $sortBy == $column['key']; @endphp
                            <th class="{{ $cls('th', ($column['th_class'] ?? $column['class'] ?? '').($isSortable ? ' '.$cls('th.sortable') : '')) }}"
                                @if($isSortable)
                                    wire:click.preserve-scroll="toggleSort('{{ $column['key'] }}')"
                                    aria-sort="{{ $columnSorted ? ($sortDirection == 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                @endif
                            >
                                <span class="d-inline-flex align-items-center">
                                    {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
                                    {{-- <small class="text-muted ms-1">({{ $columnKey }})</small> --}}
                                    @if ($isSortable)
                                        @if ($columnSorted)
                                            <x-artflow-table::icon name="chevron-up" lucide="chevron-up" class="ms-1" />
                                        @else
                                            <x-artflow-table::icon name="chevrons-up-down" lucide="chevrons-up-down" class="ms-1" />
                                        @endif
                                    @endif
                                    @if($isNestedRelation && ! $declaredUnsortable)
                                        <small class="text-muted ms-1" title="Nested relations are not sortable for performance and stability reasons">(Not Sortable)</small>
                                    @endif
                                    @if($isJsonColumn && ! $declaredUnsortable)
                                        <small class="text-muted ms-1" title="JSON columns are not sortable (computed values)">(Not Sortable)</small>
                                    @endif
                                </span>
                            </th>
                        @endif
                    @endforeach

                    @if (!empty($actions))
                        <th class="{{ $cls('th') }}">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody id="datatable-tbody" class="{{ $cls('tbody') }}">
                @if (!isset($data) || count($data) === 0)
                    <x-artflow-table::empty-state />
                @else
                    @foreach ($data as $row)
                        <tr wire:key="row-{{ $row->id ?? $loop->index }}" class="{{ $cls('tr') }}">
                            @if ($checkbox)
                                <td><input type="checkbox" wire:model.live="selectedRows" value="{{ $row->id }}" class="{{ $cls('checkbox') }}"></td>
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
                                    <td class="{{ $cls('td', $column['td_class'] ?? $column['class'] ?? '') }}">
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
                                                if ($value instanceof \DateTimeInterface) {
                                                    $value = $value->format($column['format'] ?? 'Y-m-d H:i');
                                                } elseif (is_object($value) && method_exists($value, '__toString')) {
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
                                            @php
                                                // A plain column can still hold a non-scalar: an
                                                // array/collection cast, a Carbon date, an enum.
                                                // Echoing those straight into {{ }} throws
                                                // "htmlspecialchars(): Argument #1 must be of type
                                                // string, array given" and kills the whole render.
                                                $cell = $row->{$column['key']} ?? null;

                                                if (is_bool($cell)) {
                                                    $cell = $cell ? 'Yes' : 'No';
                                                } elseif ($cell instanceof \DateTimeInterface) {
                                                    $cell = $cell->format($column['format'] ?? 'Y-m-d H:i');
                                                } elseif ($cell instanceof \BackedEnum) {
                                                    $cell = $cell->value;
                                                } elseif ($cell instanceof \UnitEnum) {
                                                    $cell = $cell->name;
                                                } elseif (is_array($cell) || $cell instanceof \JsonSerializable) {
                                                    $cell = json_encode($cell);
                                                } elseif (is_object($cell)) {
                                                    $cell = method_exists($cell, '__toString')
                                                        ? (string) $cell
                                                        : json_encode($cell);
                                                }
                                            @endphp
                                            {{ $cell ?? '' }}
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
                                                                {{-- class= keeps a literal icon-font class (fa-*, ri-*, bi-*)
                                                                     working exactly as before; data-lucide= is a harmless
                                                                     no-op unless $icon is also a real Lucide name, in which
                                                                     case the host's own lucide.createIcons() call (already
                                                                     run over this component's re-rendered DOM) revives it. --}}
                                                                <i class="{{ $icon }}" data-lucide="{{ $icon }}"></i>
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
                                                            <i class="{{ $icon }}" data-lucide="{{ $icon }}"></i>
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

        {{--
            Pagination footer: inside the same island so records change re-renders both
            table and pagination together.

            border-top is deliberate, not decorative: the theme's own .table zeroes the
            LAST row's bottom border on the assumption the table sits flush against its
            card's own edge (see ui-flow's _table.scss). A host that renders this footer
            inside a PADDED card body (Bootstrap's .card-body, not .card-body-flush) has
            no edge there to borrow — without this, the last row and the footer float
            with nothing between them.
        --}}
        <div class="{{ $cls('pagination.footer') }}">
            @if(isset($data))
                @php
                    // The pagination view is a theme slot, so a host application
                    // can hand us its own paginator markup:
                    //   'classes' => ['pagination' => 'vendor.pagination.bootstrap-5']
                    // Any Laravel paginator view works, including the built-in
                    // 'pagination::tailwind' and 'pagination::bootstrap-5'.
                    $paginationView = $ui['pagination'] ?? 'artflow-table::livewire.pagination';
                @endphp
                {{ $data->links($paginationView) }}
            @endif
            {{-- Always available. This used to be hidden behind total() > 10,
                 so a table with exactly ten rows had no page-size control at
                 all. See AUDIT.md BUG-14. --}}
            @php
                $perPageOptions = config('aftable.query.per_page_options', [10, 25, 50, 100, 500]);
            @endphp
            <div class="d-flex align-items-center gap-2">
                <label for="records-{{ $tableId ?? 'myTable' }}" class="form-label small text-muted mb-0">
                    Rows
                </label>
                <select wire:model.live="records"
                        id="records-{{ $tableId ?? 'myTable' }}"
                        class="{{ $cls('select', 'w-auto') }}">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
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
    // Livewire 4 dispatches no such browser event as the one this block used to
    // listen for, so neither the fade nor the dropdown behaviour below ever ran.
    // The supported hook is `commit`, whose respond() callback fires after the
    // DOM has been patched. See AUDIT.md BUG-15.
    const root = $wire.$el;

    const afterUpdate = () => {
        const tbody = root.querySelector('table tbody');
        if (tbody) {
            tbody.classList.remove('table-fade');
            // Force a reflow so re-adding the class restarts the animation.
            void tbody.offsetWidth;
            tbody.classList.add('table-fade');
        }

        // Keep the column-visibility dropdown open after toggling a checkbox.
        if (window._keepColumnDropdownOpen) {
            const toggle = root.querySelector('#columnVisibilityDropdown');
            toggle?.nextElementSibling?.classList.add('show');
            toggle?.setAttribute('aria-expanded', 'true');
            window._keepColumnDropdownOpen = false;
        }

        // Livewire morphs this component's DOM in place rather than replacing
        // it, so a ui-flow host's own icon/tooltip initialisers never see the
        // patched markup on their own — there is no DOM event for an in-place
        // morph, only this commit hook. refresh() is a safe no-op on any
        // other theme (window.ArtflowAdmin is undefined there) and a correct
        // no-op even under ui-flow when nothing it manages actually changed.
        window.ArtflowAdmin?.refresh();
    };

    Livewire.hook('commit', ({ component, respond }) => {
        if (component.id !== $wire.id) return;
        respond(afterUpdate);
    });

    root.querySelector('#columnVisibilityDropdownWrapper')
        ?.addEventListener('click', (e) => {
            if (e.target?.matches('.form-check-input')) {
                window._keepColumnDropdownOpen = true;
            }
        });
@endscript