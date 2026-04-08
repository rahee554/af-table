{{--
    aftable::skeleton-table
    Loading placeholder shown while the table data is fetching.
    Used as the @placeholder inside @island(lazy) blocks.
    Blaze will fold this into the parent template at compile time.
--}}
@props(['rows' => 5, 'cols' => 5])

<div class="aftable-skeleton" aria-hidden="true">
    {{-- Toolbar skeleton --}}
    <div class="d-flex justify-content-between mb-2 placeholder-glow">
        <span class="placeholder rounded" style="width: 200px; height: 32px;"></span>
        <div class="d-flex gap-2">
            <span class="placeholder rounded" style="width: 80px; height: 32px;"></span>
            <span class="placeholder rounded" style="width: 100px; height: 32px;"></span>
        </div>
    </div>

    {{-- Table skeleton --}}
    <div class="table-responsive rounded">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    @for ($i = 0; $i < $cols; $i++)
                        <th class="bg-light">
                            <span class="placeholder-glow d-block">
                                <span class="placeholder col-{{ $loop->first ? 1 : ($loop->last ? 3 : 6) }} rounded"></span>
                            </span>
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $rows; $i++)
                    <tr>
                        @for ($j = 0; $j < $cols; $j++)
                            <td>
                                <span class="placeholder-glow d-block">
                                    <span class="placeholder col-{{ ($i + $j) % 2 === 0 ? 8 : 6 }} rounded"></span>
                                </span>
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    {{-- Pagination skeleton --}}
    <div class="d-flex justify-content-between mt-2 placeholder-glow">
        <span class="placeholder rounded" style="width: 160px; height: 24px;"></span>
        <div class="d-flex gap-1">
            @for ($i = 0; $i < 5; $i++)
                <span class="placeholder rounded" style="width: 32px; height: 32px;"></span>
            @endfor
        </div>
    </div>
</div>
