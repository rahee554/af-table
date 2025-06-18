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
                    @if ($filterType === 'select')
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
                <select wire:model.change="records" id="records" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                </select>
            </div>
        </div>


        <div class="col d-flex justify-content-end">
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

            @if ($colvisBtn == true)
                
            
            <div class="dropdown me-2" id="columnVisibilityDropdownWrapper">
                <button class="btn btn-outline btn-sm dropdown-toggle" type="button" id="columnVisibilityDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    Column Visibility
                </button>
                <ul class="dropdown-menu" aria-labelledby="columnVisibilityDropdown">
                    @foreach ($columns as $column)
                        <li class="cursor-pointer">
                            <div class="form-check form-switch form-check-custom form-check-solid me-10">
                                <input class="form-check-input h-20px w-30px m-1" type="checkbox"
                                    id="column-{{ $column['key'] }}"
                                    wire:click.prevent="toggleColumnVisibility('{{ $column['key'] }}')"
                                    {{ $visibleColumns[$column['key']] ? 'checked' : '' }}>
                                <label class="form-check-label" for="column-{{ $column['key'] }}">
                                    {{ $column['label'] }}
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
                    {{-- Ensure index column is rendered if $index is true --}}
                    @if (!isset($index) || $index)
                        <th class="fw-bold bg-light">#</th>
                    @endif
                    @foreach ($columns as $column)
                        @if ($visibleColumns[$column['key']])
                            <th class="{{ $column['class'] ?? '' }} fw-bold bg-light position-relative"
                                wire:click="toggleSort('{{ $column['key'] }}')" style="cursor:pointer;">
                                <span class="d-inline-flex align-items-center">
                                    {{ $column['label'] }}
                                    @if ($sortColumn == $column['key'])
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
                @foreach ($data as $row)
                    <tr>
                        @if ($checkbox)
                            <td><input type="checkbox" wire:model="selectedRows" value="{{ $row->id }}"
                                    class="form-check-input"></td>
                        @endif
                        {{-- Ensure index column is rendered if $index is true --}}
                        @if (!isset($index) || $index)
                            <td>
                                {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
                            </td>
                        @endif
                        @foreach ($columns as $column)
                            @if ($visibleColumns[$column['key']])
                                <td class="{{ $column['class'] ?? '' }}">
                                    @if (isset($column['raw']))
                                        {!! $this->renderRawHtml($column['raw'], $row) !!}
                                    @elseif (isset($column['relation']))
                                        @php [$relation, $attribute] = explode(':', $column['relation']); @endphp
                                        {{ $row->$relation->$attribute ?? '' }}
                                    @else
                                        {{ $row->{$column['key']} ?? '' }}
                                    @endif
                                </td>
                            @endif
                        @endforeach
                        @if (!empty($actions))
                            <td>
                                {{-- Render raw HTML for actions, supporting both array and string syntax --}}
                                @foreach ($actions as $action)
                                    {!! $this->renderRawHtml($action, $row) !!}
                                @endforeach
                            </td>
                        @endif
                    </tr>
                @endforeach
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
