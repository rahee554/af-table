@push('styles')
    <!-- Check if Livewire styles are already pushed -->
    @unless(Blade::hasStack('styles'))
        @livewireStyles
    @endunless

    <!-- Your custom CSS for this component -->
    <link rel="stylesheet" href="{{ asset('vendor/artflow-studio/table/assets/style.css') }}">
@endpush

@push('scripts')
    <!-- Check if Livewire scripts are already pushed -->
    @unless(Blade::hasStack('scripts'))
        @livewireScripts
    @endunless

    <!-- Your custom JS for this component -->
    <script src="{{ asset('vendor/artflow-studio/table/assets/scripts.js') }}"></script>
@endpush

<div>
    <div class="row mb-2">
        <div class="col-3">
            @if ($searchable)
                <input type="text" wire:model.lazy="search" placeholder="Search..." class="form-control form-control-sm">
            @endif
        </div>

        <div class="col-6 row">
            <div class="col">
                <select wire:model="filterColumn" class="form-select form-select-sm" data-control="select2" data-hide-search="true">
                    <option value="">Filter by Column</option>
                    @foreach ($columns as $column)
                        <option value="{{ $column['key'] }}">{{ $column['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col">
                <select wire:model="filterOperator" class="form-select form-select-sm" data-control="select2" data-hide-search="true">
                    <option value="=">Equal</option>
                    <option value="!=">Not Equal</option>
                    <option value="<">Less Than</option>
                    <option value=">">Greater Than</option>
                    <option value="<=">Less than Equal</option>
                    <option value=">=">Greater than Equal</option>
                    <option value="LIKE">LIKE</option>
                </select>
            </div>
            <div class="col">
                <input type="text" wire:model.lazy="filterValue" placeholder="Value" class="form-control form-control-sm">
            </div>
        </div>


        
        <div class="col-3">
            <input class="form-control form-control-sm" placeholder="Pick date range" id="daterangePickr" />
        </div>

        <div class="mb-2 w-200px">
            <label for="recordsPerPage" class="form-label">Records per Page:</label>
            <select wire:model.change="recordsPerPage" id="recordsPerPage" class="form-select form-select-sm" >
                <option value="10">10</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
            </select>
        </div>

        <div class="col d-flex">
            @if ($exportable)
                <div class="dropdown me-2">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Export
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="#" wire:click.prevent="export('csv')">Export CSV</a></li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="export('xlsx')">Export Excel</a></li>
                        <li><a class="dropdown-item" href="#" wire:click.prevent="exportPdf">Export PDF</a></li>
                    </ul>
                </div>
            @endif
            @if ($printable)
                <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
            @endif
        </div>

        <div class="col d-flex">
            <div class="dropdown me-2">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="columnVisibilityDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                   Column Visibility
                </button>
                <ul class="dropdown-menu" aria-labelledby="columnVisibilityDropdown">
                    @foreach ($columns as $column)
                        <li>
                            <div class="form-check form-switch form-check-custom form-check-solid me-10">
                                <input class="form-check-input h-20px w-30px" type="checkbox" id="column-{{ $column['key'] }}" wire:click="toggleColumnVisibility('{{ $column['key'] }}')" {{ $visibleColumns[$column['key']] ? 'checked' : '' }}>
                                <label class="form-check-label" for="column-{{ $column['key'] }}">
                                    {{ $column['label'] }}
                                </label>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped" id="myTable">
            <thead>
                <tr>
                    @if ($checkbox)
                        <th>
                            <div class="form-check">
                                <input class="form-check-input" wire:model="selectAll" type="checkbox" data-kt-check-target="#myTable .form-check-input" />
                            </div>
                        </th>
                    @endif
                    @foreach ($columns as $column)
                        @if ($visibleColumns[$column['key']])
                            <th class="{{ $column['class'] ?? '' }}" wire:click="toggleSort('{{ $column['key'] }}')">
                                {{ $column['label'] }}
                                @if ($sortColumn == $column['key'])
                                    <span>{{ $sortDirection == 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr>
                        @if ($checkbox)
                            <td><input type="checkbox" wire:model="selectedRows" value="{{ $row->id }}" class="form-check-input"></td>
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

    <div>
        {{ $data->links('pagination::bootstrap') }}
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
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);

            cb(start, end);
        });
    </script>
@endpush
