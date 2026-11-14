{{--
    aftable::icon — dependency-free inline SVG, with an optional Lucide upgrade.

    The component markup used to reference Font Awesome classes
    (<i class="fas fa-times">) even though the package neither ships nor
    declares Font Awesome, so every toolbar button rendered as an empty box in
    any host application that does not already load it.

    These are 16×16 currentColor paths, so they inherit the button's colour and
    size and cannot 404. Blaze folds this component into the parent template at
    compile time, so the indirection costs nothing at runtime.

    Pass `lucide` to progressively enhance under a ui-flow host, without ever
    depending on it: the inline SVG renders as the icon's actual content
    inside an `<i data-lucide="...">` wrapper. A ui-flow host's `createIcons()`
    (running via `window.ArtflowAdmin.refresh()`) finds the `[data-lucide]`
    attribute and replaces the WHOLE element with its own Lucide svg — our
    inline one included. Any other host never runs that scan, so the `<i>`
    keeps its inline-SVG child and renders exactly as if `lucide` were never
    passed. Same icon either way, never a blank square.

    `createIcons()` copies every attribute from the `<i>` onto the svg it
    produces, so `width`/`height` are set explicitly on the `<i>` itself, not
    just on our own fallback svg nested inside it — without that, a
    ui-flow-rendered icon carries no size at all and Lucide falls back to its
    own default (24px), not the 14px this component asked for.
--}}
@props(['name', 'size' => 14, 'lucide' => null])

@php
    $paths = [
        'times'            => '<path d="M18 6 6 18M6 6l12 12"/>',
        'plus'             => '<path d="M12 5v14M5 12h14"/>',
        'trash'            => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
        'broom'            => '<path d="M19 5 13 11M16 8l-9 9-4-1 1-4 9-9zM13 15l4 4"/>',
        'download'         => '<path d="M12 3v12M7 11l5 5 5-5M4 21h16"/>',
        'file'             => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7zM14 2v5h5"/>',
        'refresh'          => '<path d="M21 12a9 9 0 1 1-2.6-6.4M21 4v5h-5"/>',
        'print'            => '<path d="M6 9V3h12v6M6 18H4v-6h16v6h-2M8 14h8v7H8z"/>',
        'search'           => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'chevron-up'       => '<path d="m18 15-6-6-6 6"/>',
        'chevron-down'     => '<path d="m6 9 6 6 6-6"/>',
        'chevrons-up-down' => '<path d="m7 15 5 5 5-5M7 9l5-5 5 5"/>',
        'columns'          => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M15 3v18"/>',
    ];
@endphp

@if (isset($paths[$name]))
    @if ($lucide)
        <i data-lucide="{{ $lucide }}" width="{{ $size }}" height="{{ $size }}"
           {{ $attributes->merge(['class' => 'aftable-icon']) }}>
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true" focusable="false"
                 style="vertical-align:-.125em;display:inline-block">{!! $paths[$name] !!}</svg>
        </i>
    @else
        <svg {{ $attributes->merge(['class' => 'aftable-icon']) }}
             width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" focusable="false"
             style="vertical-align:-.125em;display:inline-block">{!! $paths[$name] !!}</svg>
    @endif
@endif
