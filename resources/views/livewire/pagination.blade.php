@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-between">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->currentPage() - 1 }})" rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->currentPage() + 1 }})" rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>
        

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <div>
                
                <p class="small text-muted">
                    {!! __('Showing') !!}
                    <span class="fw-semibold">{{ number_format($paginator->firstItem()) }}</span>
                    {!! __('to') !!}
                    <span class="fw-semibold">{{ number_format($paginator->lastItem()) }}</span>
                    {!! __('of') !!}
                    <span class="fw-semibold">
                        @if ($paginator->total() >= 1000000)
                            {{ number_format($paginator->total() / 1000000, 1) }}M
                        @else
                            {{ number_format($paginator->total()) }}
                        @endif
                    </span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->currentPage() - 1 }})" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                        </li>
                    @endif

                    {{-- Display First Page --}}
                    @if ($paginator->currentPage() > 2)
                        <li class="page-item">
                            <a class="page-link" href="#" wire:click.prevent="setPage(1)">1</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @endif

                    {{-- Left Page --}}
                    @if ($paginator->currentPage() > 1)
                        <li class="page-item">
                            <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->currentPage() - 1 }})">{{ $paginator->currentPage() - 1 }}</a>
                        </li>
                    @endif

                    {{-- Current Page --}}
                    <li class="page-item active">
                        <span class="page-link">{{ $paginator->currentPage() }}</span>
                    </li>

                    {{-- Right Page --}}
                    @if ($paginator->currentPage() < $paginator->lastPage())
                        <li class="page-item">
                            <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->currentPage() + 1 }})">{{ $paginator->currentPage() + 1 }}</a>
                        </li>
                    @endif

                    {{-- Display Last Page --}}
                    @if ($paginator->currentPage() < $paginator->lastPage() - 1)
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <li class="page-item">
                            <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->lastPage() }})">{{ $paginator->lastPage() }}</a>
                        </li>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="#" wire:click.prevent="setPage({{ $paginator->currentPage() + 1 }})" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
