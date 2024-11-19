<div>
    @if($searchable)
        <input wire:model.debounce.300ms="search" type="text" placeholder="Search...">
    @endif

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th wire:click="sortBy('{{ $column['key'] }}')">
                        {{ $column['label'] }}
                        @if($sortColumn === $column['key'])
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($columns as $column)
                        <td>{{ $row->{$column['key']} }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $data->links() }}

    @if($exportable)
        <button wire:click="export('csv')">Export CSV</button>
        <button wire:click="export('xlsx')">Export Excel</button>
    @endif

    @if($printable)
        <button onclick="window.print()">Print</button>
    @endif
</div>

@push('styles')
    <link href="{{ asset('vendor/aftable/css/aftable.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/aftable/js/aftable.js') }}"></script>
@endpush