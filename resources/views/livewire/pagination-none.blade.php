{{--
    The 'none' preset's pagination view: structural aft-* hooks only, no
    framework classes. Laravel ships no such view of its own — every built-in
    (`pagination::simple-bootstrap-5`, `pagination::simple-tailwind`, …) is
    branded to a specific framework, which is exactly what 'none' promises not
    to emit.
--}}
@if ($paginator->hasPages())
    <nav class="aft-pagination" aria-label="Pagination">
        <ul class="aft-pagination-list">
            @if ($paginator->onFirstPage())
                <li class="aft-pagination-item is-disabled" aria-disabled="true">
                    <span class="aft-pagination-link">@lang('pagination.previous')</span>
                </li>
            @else
                <li class="aft-pagination-item">
                    <a class="aft-pagination-link" href="#" wire:click.prevent.preserve-scroll="setPage({{ $paginator->currentPage() - 1 }})" rel="prev">@lang('pagination.previous')</a>
                </li>
            @endif

            <li class="aft-pagination-item is-current">
                <span class="aft-pagination-link">{{ $paginator->currentPage() }}</span>
            </li>

            @if ($paginator->hasMorePages())
                <li class="aft-pagination-item">
                    <a class="aft-pagination-link" href="#" wire:click.prevent.preserve-scroll="setPage({{ $paginator->currentPage() + 1 }})" rel="next">@lang('pagination.next')</a>
                </li>
            @else
                <li class="aft-pagination-item is-disabled" aria-disabled="true">
                    <span class="aft-pagination-link">@lang('pagination.next')</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
