<div class="grid">
    <div class="card card-grid min-w-full">
        <div class="card-header py-5 flex-wrap">
            <h3 class="card-title">
                {{ $tableName = 'Table Name' }}
            </h3>

            <div>
                @if ($exportable)
                    <div class="dropdown" data-dropdown="true" data-dropdown-trigger="click">
                        <button class="dropdown-toggle btn btn-light btn-icon-xs">
                            Show Dropdown
                            <i class="ki-outline ki-down dropdown-open:hidden">
                            </i>
                            <i class="ki-outline ki-up hidden dropdown-open:block">
                            </i>
                        </button>
                        <div class="dropdown-content w-full max-w-56 p-4">
                            <div class="menu-item">
                                <a class="menu-link" href="#" wire:click.prevent="export('csv')">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-badge">
                                        </i>
                                    </span>
                                    <span class="menu-title">
                                        Export CSV
                                    </span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="#" wire:click.prevent="export('xlsx')">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-badge">
                                        </i>
                                    </span>
                                    <span class="menu-title">
                                        Export CSV
                                    </span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link" href="#" wire:click.prevent="export('exportPdf')">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-badge">
                                        </i>
                                    </span>
                                    <span class="menu-title">
                                        Export CSV
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown me-2">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="exportDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" wire:click.prevent="export('csv')">Export
                                    CSV</a>
                            </li>
                            <li><a class="dropdown-item" href="#" wire:click.prevent="export('xlsx')">Export
                                    Excel</a>
                            </li>
                            <li><a class="dropdown-item" href="#" wire:click.prevent="exportPdf">Export PDF</a>
                            </li>
                        </ul>
                    </div>
                @endif
                @if ($printable)
                    <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
                @endif
            </div>

            <div class="filter-container">
                @if (isset($filters) && count($filters) > 0)
                    <select class="select w-80" wire:model.change="selectedColumn">
                        <option value="">Select Column</option>
                        @foreach ($filters as $column => $details)
                            <option value="{{ $column }}">{{ $details['label'] }}</option>
                        @endforeach
                    </select>
            
                    @if ($selectedColumn)
                        @switch($columnType)
                            @case('number')
                                <div class="filter-number">
                                    <select wire:model="numberOperator" class="select w-32">
                                        <option value="=">Equals</option>
                                        <option value=">">Greater than</option>
                                        <option value="<">Less Than</option>
                                        <option value=">=">Greater than Equal >=</option>
                                        <option value="<=">Less than Equal <=</option>
                                    </select>
                                    <input type="number" wire:model="filterValue" class="input w-32" placeholder="Enter value">
                                </div>
                                @break
            
                            @case('date')
                                <div class="filter-date">
                                    <input type="date" wire:model.debounce.300ms="filterValue" class="input w-32" placeholder="Select date">
                                </div>
                                @break
            
                            @case('select')
                                <div class="filter-select">
                                    <select wire:model="filterValue" class="select w-80">
                                        <option value="">Select value</option>
                                        @foreach ($distinctValues as $value)
                                            <option value="{{ $value }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @break
                        @endswitch
                    @endif
                @endif
            </div>
            

            <div class="col d-flex">
                <div class="dropdown me-2">
                    <div class="dropdown" data-dropdown="true" data-dropdown-trigger="click">
                        <button class="dropdown-toggle btn btn-sm btn-light">
                            Column Visibility
                        </button>
                        <div class="dropdown-content w-full max-w-56 py-2">
                            <div class="menu menu-default flex flex-col w-full">

                                @foreach ($columns as $column)
                                    <div class="menu-item p-2">


                                        <label class="switch switch-sm">
                                            <input class="order-2 form-check-input h-20px w-30px" type="checkbox"
                                                id="column-{{ $column['key'] }}"
                                                wire:click="toggleColumnVisibility('{{ $column['key'] }}')"
                                                {{ $visibleColumns[$column['key']] ? 'checked' : '' }}>

                                            <div class="switch-label flex items-center gap-2 order-2">
                                                {{ $column['label'] }}

                                            </div>


                                        </label>

                                        {{-- 
                                        <li>
                                            <div class="form-check form-switch form-check-custom form-check-solid me-10">
                                                <input class="form-check-input h-20px w-30px" type="checkbox"
                                                    id="column-{{ $column['key'] }}"
                                                    wire:click="toggleColumnVisibility('{{ $column['key'] }}')"
                                                    {{ $visibleColumns[$column['key']] ? 'checked' : '' }}>
                                                <label class="form-check-label" for="column-{{ $column['key'] }}">
                                                    {{ $column['label'] }}
                                                </label>
                                            </div>
                                        </li> --}}
                                    </div>
                                @endforeach


                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-6">
                <div class="relative">
                    <i
                        class="ki-outline ki-magnifier leading-none text-md text-gray-500 absolute top-1/2 left-0 -translate-y-1/2 ml-3">
                    </i>
                    <input class="input input-sm pl-8" placeholder="Search..." type="text"
                        wire:model.lazy="search" />
                </div>
            </div>
        </div>


        <div class="card-body">
            <div data-datatable="true" data-datatable-page-size="10">
                <div class="scrollable-x-auto">
                    <table class="table table-auto table-border" data-datatable-table="true" id="grid_table">
                        <thead>
                            <tr>
                                @if ($checkbox)
                                    <th class="w-[60px]">
                                        <input class="checkbox checkbox-sm" data-datatable-check="true"
                                            type="checkbox" />
                                    </th>
                                @endif
                                @foreach ($columns as $column)
                                    @if ($visibleColumns[$column['key']])
                                        <th class="{{ $column['class'] ?? '' }}"
                                            wire:click="toggleSort('{{ $column['key'] }}')">
                                            <span class="flex">
                                                <span class="sort-label"> {{ $column['label'] }}</span>

                                                @if ($sortColumn == $column['key'])
                                                    <span
                                                        class="sort-icon">{{ $sortDirection == 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </span>
                                        </th>
                                    @endif
                                @endforeach
                                {{-- 


                                @foreach ($columns as $column)
                                    <th class="{{ $column['class'] ?? '' }}">
                                        <span class="sort asc">
                                            <span class="sort-label">
                                                {{ $column['label'] }}
                                            </span>
                                            <span class="sort-icon">
                                            </span>
                                        </span>
                                    </th>
                                @endforeach --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $row)
                                <tr>
                                    <td>
                                        @if ($checkbox)
                                            <input class="checkbox checkbox-sm" data-datatable-row-check="true"
                                                type="checkbox" value="1" />
                                        @endif
                                    </td>

                                    @foreach ($columns as $column)
                                        @if ($visibleColumns[$column['key']])
                                            <td class="p-2 {{ $column['class'] ?? '' }}">
                                                @if (isset($column['raw']))
                                                    {!! $this->renderRawHtml($column['raw'], $row) !!}
                                                @elseif (isset($column['relation']))
                                                    @php [$relation, $attribute] = explode(':', $column['relation']); @endphp
                                                    {{ $row->$relation->$attribute ?? '' }}
                                                @else
                                                    {{ $row->{$column['key']} }}
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach


                        </tbody>
                    </table>
                </div>
                <div
                    class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                    <div class="flex items-center gap-2">
                        Show
                        <select class="select select-sm w-16" wire:model.change="recordsPerPage">
                            <option value="10">10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>

                        </select>
                        per page
                    </div>
                    <div class="flex items-center gap-4">
                        {{-- <span data-datatable-info="true">
                        </span>
                        <!-- Pagination links -->
                        <div>
                            {{ $data->links('pagination::tailwind') }}
                        </div> --}}
                        {{ $data->links('vendor.pagination.tailwind') }}


                    </div>
                </div>
            </div>
        </div>
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
        });
    </script>
@endpush
