<?php

namespace ArtflowStudio\Table\Support;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use Livewire\ComponentHook;

/**
 * Restricts which component methods a browser may invoke.
 *
 * Livewire treats **every public method** on a component as an action any
 * visitor can call with arbitrary arguments. `AftableComponent` composes 27
 * traits and exposes 400+ public methods, the overwhelming majority of which
 * are internal helpers that are public only so a sibling trait can reach them.
 * That is 400 entry points to reason about, and it is how `renderRawHtml()` —
 * which compiles and evaluates its first argument as Blade — became remotely
 * callable (AUDIT.md SEC-01).
 *
 * Demoting them one at a time is not safe while the traits are still entangled:
 * PHP will not tell you that a trait 400 lines away called the method you just
 * made protected. So the surface is closed here instead, at the one place every
 * client-initiated call passes through.
 *
 * The list below is the *deliberate* UI surface — everything the shipped
 * template binds to, plus the documented public API. `ActionGuardTest` parses
 * the Blade template and asserts every `wire:click` / `wire:model` target is
 * present, so the guard cannot silently break the table.
 *
 * Escape hatches, in order of preference:
 *   - `aftable.security.extra_actions` — add your own method names
 *   - `aftable.security.guard_actions = false` — disable entirely
 */
class GuardsActions extends ComponentHook
{
    /**
     * Methods a browser is allowed to invoke.
     *
     * @var list<string>
     */
    public const ALLOWED = [
        // sorting
        'sortBy', 'toggleSort', 'setSort', 'clearSort', 'toggleSortDirection', 'applySorts',

        // column visibility
        'toggleColumn', 'toggleColumnVisibility', 'updateColumnVisibility',
        'showAllColumns', 'hideAllColumns', 'resetColumnVisibility',
        'clearColumnVisibilitySession', 'setColumnVisibility',

        // filtering
        'addFilterInstance', 'removeFilterInstance', 'updateFilterInstance',
        'clearAllFilterInstances', 'clearAllFilters', 'clearFilters',
        'applyDateRange', 'clearQuickFilters',

        // search
        'clearSearch', 'clearSearchInput', 'setSearch',

        // selection + bulk actions
        'selectAllVisible', 'selectAllOnPage', 'clearAllSelections', 'clearSelection',
        'clearBulkSelection', 'clearActionSelection',

        // export
        'handleExport',

        // refresh
        'refresh', 'refreshTable', 'refreshComponent',

        // pagination (Livewire\WithPagination)
        'gotoPage', 'nextPage', 'previousPage', 'setPage', 'resetPage',
        'previousPageWithNumber', 'nextPageWithNumber', 'gotoPageWithNumber',
    ];

    /**
     * Livewire's own magic actions, which never reach a component method.
     *
     * @var list<string>
     */
    private const MAGIC = ['$refresh', '$set', '$toggle', '$sync', '$commit'];

    /**
     * Livewire's own internal actions.
     *
     * These are dispatched by the framework's JavaScript, not by anything in our
     * template, so they are easy to forget — and blocking one silently breaks a
     * whole feature. `__lazyLoadIsland` is how a lazy island asks for its
     * content; without it `lazy => true` renders a skeleton that never resolves.
     *
     * @var list<string>
     */
    private const LIVEWIRE_INTERNAL = [
        '__dispatch',
        '__lazyLoad',
        '__lazyLoadIsland',
        '__runtimeWith',
    ];

    /**
     * Not a Livewire action at all — a JavaScript interop artifact. The
     * `$wire` object Livewire's JS hands out is a Proxy whose `get` trap
     * returns a bound server-call function for *any* property name it does
     * not itself define. `JSON.stringify()` always probes an object for a
     * `toJSON` method before serialising it — Alpine's reactivity,
     * `$persist`, devtools, and any app/tooling code that logs a `$wire`
     * reference (e.g. Laravel Boost's browser-log console interceptor) all
     * do this without intending to invoke anything. The Proxy answers "yes,
     * I have one" and calls it, turning an incidental stringify into a real
     * server request for a method literally named `toJSON`, which no
     * component defines.
     *
     * Unlike LIVEWIRE_INTERNAL, letting this one reach Livewire's real
     * dispatcher is wrong even when permitted: no component defines
     * `toJSON`, so it falls through to `MethodNotFoundException` (a 500)
     * instead of the guard's 403. It is intercepted and no-opped here,
     * before dispatch, so it is invisible either way.
     *
     * @var list<string>
     */
    private const NOOP = ['toJSON'];

    /**
     * Livewire's per-call hook. Implement call(), not callCall(): the latter is
     * the base class dispatcher and returning from it breaks the finish chain.
     */
    public function call($method, $params, $returnEarly, $metadata, $componentContext)
    {
        if (! $this->component instanceof AftableComponent) {
            return null;
        }

        $method = (string) $method;

        if (in_array($method, self::NOOP, true)) {
            $returnEarly(null);

            return null;
        }

        if (! (bool) config('aftable.security.guard_actions', true)) {
            return null;
        }

        if (self::permits($method)) {
            return null;
        }

        throw new ActionNotAllowedException($method);
    }

    /** Is this method name callable from the browser? */
    public static function permits(string $method): bool
    {
        if (in_array($method, self::MAGIC, true)
            || in_array($method, self::LIVEWIRE_INTERNAL, true)) {
            return true;
        }

        // Lifecycle hooks Livewire drives itself.
        if (str_starts_with($method, 'updating') || str_starts_with($method, 'updated')) {
            return true;
        }

        return in_array($method, self::allowed(), true);
    }

    /** @return list<string> */
    public static function allowed(): array
    {
        $extra = (array) config('aftable.security.extra_actions', []);

        return array_values(array_unique(array_merge(
            self::ALLOWED,
            array_filter($extra, 'is_string')
        )));
    }
}
