@push('styles')
    @once
        <!-- Livewire Styles -->
        @livewireStyles
    @endonce
    <link rel="stylesheet" href="{{ asset('vendor/artflow-studio/table/assets/style.css') }}">
@endpush

@push('scripts')
    @once
        <!-- Livewire Scripts -->
        @livewireScripts
    @endonce

    <!-- Your custom JS for this component -->
    <script src="{{ asset('vendor/artflow-studio/table/assets/scripts.js') }}"></script>
@endpush

<div>
    @if (!empty($filters))

        <div class="row">
            <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                <select wire:model.live="filterColumn" class="form-select form-select-sm">
                    <option value="">Filter by Column</option>
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
                    <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                        <select wire:model.live="filterOperator" class="form-select form-select-sm">
                            <option value="=">Equal (=)</option>
                            <option value="!=">Not Equal (≠)</option>
                            <option value="<">Less Than (<)< /option>
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
                    @if ($filterType === 'select' || $filterType === 'distinct')
                        <select wire:model.live="filterValue" class="form-control form-control-sm">
                            <option value="">Select Value</option>
                            @foreach ($this->getDistinctValues($selectedColumn) as $val)
                                <option value="{{ $val }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    @elseif($filterType === 'date')
                        <input type="date" wire:model.live.debounce.500ms="filterValue"
                            class="form-control form-control-sm">
                    @elseif(in_array($filterType, ['integer', 'number']))
                        <input type="number" wire:model.live.debounce.500ms="filterValue" placeholder="Enter number..."
                            class="form-control form-control-sm">
                    @else
                        <input type="text" wire:model.live.debounce.500ms="filterValue" placeholder="Enter value..."
                            class="form-control form-control-sm">
                    @endif
                @else
                    <input type="text" placeholder="Select a column first" class="form-control form-control-sm"
                        disabled>
                @endif
            </div>
            <div class="col-auto">
                <button type="button"
                    wire:click="$set('filterColumn', null); $set('filterValue', null); $set('filterOperator', '=')"
                    class="btn btn-sm btn-light">Clear</button>
            </div>
        </div>


    @endif
    <div class="row mb-2">
        <div class="col">
            <div class="w-100px">
              @if ($searchable)
                <div class="position-relative w-md-250px me-2">
                    <input type="text" wire:model.lazy="search" placeholder="Search..."
                        class="form-control form-control-sm border-0 p-2 pe-4">

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
                            <li class="cursor-pointer">
                                <div class="form-check form-switch form-check-custom form-check-solid me-10">
                                    <input class="form-check-input h-20px w-30px m-1" type="checkbox"
                                        id="column-{{ $columnKey }}"
                                        wire:click.prevent="toggleColumnVisibility('{{ $columnKey }}')"
                                        {{ ($visibleColumns[$columnKey] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="column-{{ $columnKey }}">
                                        {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
                                    </label>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($refreshBtn == true)
                <button wire:click="refreshTable" class="btn btn-sm btn-light mb-2">
                    Refresh
                </button>
            @endif

        </div>
        {{-- <div class="col d-flex">
            @if ($exportable)
                <div class="dropdown me-2">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="exportDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Export
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="#" wire:click.prevent="export('csv')">Export CSV</a>
                        </li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="export('xlsx')">Export Excel</a>
                        </li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="exportPdf">Export PDF</a></li>
                    </ul>
                </div>
            @endif
            @if ($printable)
                <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
            @endif
        </div> --}}
    </div>

    <div class="table-responsive rounded {{ count($data) < 5 ? 'h-500px' : '' }}">
        <table class="table table-bordered p-2 " id="myTable">
            <thead>
                <tr>
                    @if ($checkbox)
                        <th class="fw-bold bg-light">
                            <div class="form-check">
                                <input class="form-check-input" wire:model="selectAll" type="checkbox"
                                    data-kt-check-target="#myTable .form-check-input" />
                            </div>
                        </th>
                    @endif

                    @if (!isset($index) || $index)
                        <th class="fw-bold bg-light">#</th>
                    @endif

                    @foreach ($columns as $columnKey => $column)
                        @if ($visibleColumns[$columnKey] ?? false)
                            <th class="fw-bold bg-light position-relative {{ $column['th_class'] ?? $column['class'] ?? '' }}"
                                @if(!isset($column['function']) && isset($column['key']))
                                    wire:click="toggleSort('{{ $column['key'] }}')" style="cursor:pointer;"
                                @endif
                            >
                                <span class="d-inline-flex align-items-center">
                                    {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}
                                    @if (!isset($column['function']) && isset($column['key']) && $sortColumn == $column['key'])
                                        <span class="ms-1"
                                            style="font-size:1em; line-height:1;">{{ $sortDirection == 'asc' ? '↑' : '↓' }}</span>
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
                @if (count($data) === 0)
                    <tr>
                        <td colspan="{{ 
                            ($checkbox ? 1 : 0) +
                            ((!isset($index) || $index) ? 1 : 0) +
                            collect($columns)->filter(fn($col, $key) => $visibleColumns[$key] ?? false)->count() +
                            (!empty($actions) ? 1 : 0)
                        }}" class="text-center align-middle py-5">
                            <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 200px;">
                                <svg width="64" height="64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="64" height="64" rx="12" fill="#F3F6F9"/>
                                    <path d="M20 28h24v16H20z" fill="#7E8299"/>
                                    <path d="M20 20h8l4 4h12a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H20a2 2 0 0 1-2-2V22a2 2 0 0 1 2-2z" fill="#FFA800"/>
                                </svg>
                                <div class="mt-3 fs-5 text-muted">No data found</div>
                            </div>
                        </td>
                    </tr>
                @else
                    @foreach ($data as $row)
                        <tr>
                            @if ($checkbox)
                                <td><input type="checkbox" wire:model="selectedRows" value="{{ $row->id }}" class="form-check-input"></td>
                            @endif

                            @if (!isset($index) || $index)
                                <td>
                                    {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
                                </td>
                            @endif

                            @foreach ($columns as $columnKey => $column)
                                @if ($visibleColumns[$columnKey] ?? false)
                                    <td class="{{ $column['td_class'] ?? $column['class'] ?? '' }}">
                                        @if (isset($column['function']))
                                            {{-- Handle function-based columns --}}
                                            @if (isset($column['raw']))
                                                {{-- Function with custom raw template --}}
                                                @php
                                                    $rawTemplate = $column['raw'];
                                                    $functionName = $column['function'];

                                                    // Replace function calls in template
                                                    if (method_exists($row, $functionName)) {
                                                        $functionResult = $row->$functionName();
                                                        $rawTemplate = str_replace(
                                                            '$row->' . $functionName . '()', 
                                                            is_bool($functionResult) ? ($functionResult ? 'true' : 'false') : $functionResult, 
                                                            $rawTemplate
                                                        );
                                                    }
                                                @endphp
                                                {!! $this->renderRawHtml($rawTemplate, $row) !!}
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
                                            @php
                                                $rawTemplate = $column['raw'];

                                                // Handle method calls in raw templates
                                                preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)\(\)/', $rawTemplate, $methodMatches);

                                                if (!empty($methodMatches[0])) {
                                                    foreach ($methodMatches[0] as $index => $fullMatch) {
                                                        $methodName = $methodMatches[1][$index];
                                                        if (method_exists($row, $methodName)) {
                                                            $methodResult = $row->$methodName();
                                                            if (is_bool($methodResult)) {
                                                                $methodResult = $methodResult ? 'true' : 'false';
                                                            }
                                                            $rawTemplate = str_replace($fullMatch, $methodResult, $rawTemplate);
                                                        }
                                                    }
                                                }
                                            @endphp
                                            {!! $this->renderRawHtml($rawTemplate, $row) !!}
                                        @elseif (isset($column['relation']))
                                            {{-- Handle relationship columns --}}
                                            @php [$relation, $attribute] = explode(':', $column['relation']); @endphp
                                            {{ $row->$relation->$attribute ?? '' }}
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
                                    @foreach ($actions as $action)
                                        @php
                                            $actionTemplate = $action;

                                            // Handle method calls in actions
                                            preg_match_all('/\$row->([a-zA-Z_][a-zA-Z0-9_]*)\(\)/', $actionTemplate, $methodMatches);

                                            if (!empty($methodMatches[0])) {
                                                foreach ($methodMatches[0] as $index => $fullMatch) {
                                                    $methodName = $methodMatches[1][$index];
                                                    if (method_exists($row, $methodName)) {
                                                        $methodResult = $row->$methodName();
                                                        if (is_bool($methodResult)) {
                                                            $methodResult = $methodResult ? 'true' : 'false';
                                                        }
                                                        $actionTemplate = str_replace($fullMatch, $methodResult, $actionTemplate);
                                                    }
                                                }
                                            }
                                        @endphp
                                        {!! $this->renderRawHtml($actionTemplate, $row) !!}
                                    @endforeach
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div>
        {{ $data->links('artflow-studio.table::pagination') }}
        @if ($records > 10)
            <div class="w-100px">
                <select wire:model.change="records" id="records" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                </select>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:load', function() {
            var start = moment().subtract(29, 'days');
            var end = moment();

            function cb(start, end) {
                $('#daterangePickr').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                Livewire.emit('dateRangeSelected', start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            }

            $('#daterangePickr').daterangepicker({
                startDate: start,
                endDate: end,
                opens: 'left',
                autoUpdateInput: true,
                locale: {
                    cancelLabel: 'Clear'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                }
            }, cb);

            cb(start, end);

            // Animate table body on update
            Livewire.hook('message.processed', (message, component) => {
                const tbody = document.getElementById('datatable-tbody');
                if (tbody) {
                    // Remove and re-add the class for animation
                    tbody.classList.remove('table-fade');
                    setTimeout(() => {
                        tbody.classList.add('table-fade');
                    }, 10);
                }

                // Keep the column visibility dropdown open after toggling
                const dropdownToggle = document.getElementById('columnVisibilityDropdown');
                const dropdownMenu = dropdownToggle?.nextElementSibling;
                if (window._keepColumnDropdownOpen) {
                    // Bootstrap 5: show dropdown menu manually
                    dropdownMenu?.classList.add('show');
                    dropdownToggle?.setAttribute('aria-expanded', 'true');
                    // Remove the flag
                    window._keepColumnDropdownOpen = false;
                }
            });

            // Intercept column visibility checkbox clicks to keep dropdown open
            document.getElementById('columnVisibilityDropdownWrapper')?.addEventListener('click', function(e) {
                if (e.target && e.target.matches('.form-check-input')) {
                    window._keepColumnDropdownOpen = true;
                }
            });
        });
    </script>
@endpush
