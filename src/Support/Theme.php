<?php

namespace ArtflowStudio\Table\Support;

/**
 * Resolves the CSS classes the table renders with.
 *
 * The component's markup used to hard-code Bootstrap 5 classes
 * (`table table-bordered`, `btn btn-sm btn-outline-secondary`,
 * `form-select form-select-sm`, …). That is fine if the host application runs
 * Bootstrap and wants the package's opinion — and wrong in every other case.
 * The only escape hatches were `assets => false` (which removes the package's
 * CSS but not its classes) and the `@aftable` directive's custom `<table>` tag
 * (which restyles exactly one element).
 *
 * A theme is a flat map of *slot* => *class string*. Slots are stable names for
 * the parts of the table — `table`, `thead`, `button`, `input`, … — so a host
 * application can restyle any of them without republishing the view.
 *
 * Resolution order, most specific first:
 *
 *   1. `classes` passed to the component      — per-table, per-slot override
 *   2. the named preset from `theme`          — per-table preset
 *   3. `aftable.theme.overrides` in config    — app-wide, per-slot override
 *   4. the app-wide preset (`aftable.theme.preset`)
 *   5. the built-in `bootstrap` preset        — unchanged default
 *
 * Every level merges over the one below it, so overriding one slot never means
 * redeclaring the rest.
 */
final class Theme
{
    /**
     * Bootstrap 5. The historical, and still default, look.
     *
     * @var array<string, string>
     */
    public const BOOTSTRAP = [
        'wrapper' => 'aftable-wrapper',
        'toolbar' => 'row mb-2',
        'toolbar.start' => 'col',
        'toolbar.end' => 'col d-flex justify-content-end',
        'filters' => 'mb-3',
        'filters.row' => 'row g-2 mb-2 align-items-end',
        'filters.instance' => 'row g-2 mb-2 align-items-end border-start border-3 border-primary ps-3',
        'filters.col' => 'col-12 col-sm-6 col-md-3',
        'filters.col.sm' => 'col-12 col-sm-6 col-md-2',
        'filters.col.value' => 'col-12 col-sm-4 col-md-3 col-lg-2',
        'filters.col.auto' => 'col-auto',

        'label' => 'form-label small text-muted',
        'input' => 'form-control form-control-sm',
        'select' => 'form-select form-select-sm',
        'checkbox' => 'form-check-input',
        'search' => 'form-control form-control-sm border-0 p-2 pe-4',

        'button' => 'btn btn-sm btn-outline-secondary',
        'button.primary' => 'btn btn-sm btn-outline-primary',
        'button.danger' => 'btn btn-sm btn-outline-danger',
        'button.warning' => 'btn btn-sm btn-outline-warning',
        'button.success' => 'btn btn-sm btn-outline-success',
        'button.action' => 'btn btn-sm btn-secondary',

        'dropdown' => 'dropdown me-2',
        // Was 'btn-outline' with no colour suffix — not a real Bootstrap
        // class (unlike its 'button' sibling above, which correctly uses
        // btn-outline-secondary). Bootstrap's base .btn has a transparent
        // border/background until a variant class sets one, so this toggle
        // rendered with no visible border or background at all: just text
        // and a caret, indistinguishable from plain content until clicked.
        'dropdown.toggle' => 'btn btn-outline-secondary btn-sm dropdown-toggle',
        'dropdown.menu' => 'dropdown-menu',
        'dropdown.item' => 'dropdown-item',

        // border-dark-subtle rather than plain `border`: the latter resolves
        // to the theme's default card-border token, which is already so
        // faint against a white card that the table looked borderless.
        // border-dark-subtle is one step darker (ui-flow's --uf-border-strong)
        // and is the outline that's actually visible.
        'responsive' => 'table-responsive rounded border border-dark-subtle aftable-table-wrap',
        'table' => 'table table-bordered p-2',
        'thead' => '',
        'th' => 'fw-bold bg-light position-relative',
        'tbody' => '',
        'tr' => '',
        'td' => '',
        'empty' => 'text-center align-middle py-5',

        'pagination' => 'artflow-table::livewire.pagination',
        'pagination.footer' => 'pt-3 mt-1',
        'badge.muted' => 'text-muted ms-1',
    ];

    /**
     * Tailwind CSS 3/4. Utility classes only — no @apply, no build step.
     *
     * @var array<string, string>
     */
    public const TAILWIND = [
        'wrapper' => 'aftable-wrapper space-y-3',
        'toolbar' => 'flex flex-wrap items-center gap-3',
        'toolbar.start' => 'flex-1 min-w-0',
        'toolbar.end' => 'flex items-center gap-2 ml-auto',
        'filters' => 'space-y-2',
        'filters.row' => 'flex flex-wrap items-end gap-2',
        'filters.instance' => 'flex flex-wrap items-end gap-2 border-l-2 border-blue-500 pl-3',
        'filters.col' => 'min-w-[12rem]',
        'filters.col.sm' => 'min-w-[9rem]',
        'filters.col.value' => 'min-w-[11rem]',
        'filters.col.auto' => 'shrink-0',

        'label' => 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1',
        'input' => 'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500',
        'select' => 'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500',
        'checkbox' => 'rounded border-gray-300 text-blue-600 focus:ring-blue-500',
        'search' => 'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm pr-8',

        'button' => 'inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700',
        'button.primary' => 'inline-flex items-center gap-1.5 rounded-md border border-blue-500 px-2.5 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50',
        'button.danger' => 'inline-flex items-center gap-1.5 rounded-md border border-red-400 px-2.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50',
        'button.warning' => 'inline-flex items-center gap-1.5 rounded-md border border-amber-400 px-2.5 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50',
        'button.success' => 'inline-flex items-center gap-1.5 rounded-md border border-emerald-500 px-2.5 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50',
        'button.action' => 'inline-flex items-center gap-1.5 rounded-md bg-gray-800 px-2.5 py-1 text-xs font-medium text-white hover:bg-gray-700',

        'dropdown' => 'relative inline-block',
        'dropdown.toggle' => 'inline-flex items-center gap-1.5 rounded-md border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 text-sm',
        'dropdown.menu' => 'absolute right-0 z-20 mt-1 max-h-96 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-1 shadow-lg',
        'dropdown.item' => 'block w-full px-3 py-1.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700',

        'responsive' => 'overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700',
        'table' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm',
        'thead' => 'bg-gray-50 dark:bg-gray-800',
        'th' => 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 whitespace-nowrap',
        'tbody' => 'divide-y divide-gray-100 dark:divide-gray-800',
        'tr' => 'hover:bg-gray-50 dark:hover:bg-gray-800/50',
        'td' => 'px-3 py-2 align-middle',
        'empty' => 'px-3 py-10 text-center text-gray-500',

        // Laravel ships a Tailwind paginator; use it rather than our
        // Bootstrap-classed one. Any view name works here.
        'pagination' => 'pagination::tailwind',
        'pagination.footer' => 'pt-3 mt-1',
        'badge.muted' => 'text-xs text-gray-400 ml-1',
    ];

    /**
     * No opinion at all. Every slot empty, plus a stable `aft-*` hook on the
     * structural elements so a stylesheet can target them.
     *
     * Use this when the host application owns the entire look.
     *
     * @var array<string, string>
     */
    public const NONE = [
        'wrapper' => 'aftable-wrapper',
        'toolbar' => 'aft-toolbar',
        'toolbar.start' => 'aft-toolbar-start',
        'toolbar.end' => 'aft-toolbar-end',
        'filters' => 'aft-filters',
        'filters.row' => 'aft-filters-row',
        'filters.instance' => 'aft-filters-instance',
        'filters.col' => 'aft-filters-col',
        'filters.col.sm' => 'aft-filters-col-sm',
        'filters.col.value' => 'aft-filters-col-value',
        'filters.col.auto' => 'aft-filters-col-auto',
        'label' => 'aft-label',
        'input' => 'aft-input',
        'select' => 'aft-select',
        'checkbox' => 'aft-checkbox',
        'search' => 'aft-search',
        'button' => 'aft-btn',
        'button.primary' => 'aft-btn aft-btn-primary',
        'button.danger' => 'aft-btn aft-btn-danger',
        'button.warning' => 'aft-btn aft-btn-warning',
        'button.success' => 'aft-btn aft-btn-success',
        'button.action' => 'aft-btn aft-btn-action',
        'dropdown' => 'aft-dropdown',
        'dropdown.toggle' => 'aft-dropdown-toggle',
        'dropdown.menu' => 'aft-dropdown-menu',
        'dropdown.item' => 'aft-dropdown-item',
        'responsive' => 'aft-scroll',
        'table' => 'aft-table',
        'thead' => 'aft-thead',
        'th' => 'aft-th',
        'tbody' => 'aft-tbody',
        'tr' => 'aft-tr',
        'td' => 'aft-td',
        'empty' => 'aft-empty',
        // Laravel's unstyled paginator - simple markup, no framework classes.
        'pagination' => 'pagination::simple-default',
        'pagination.footer' => 'aft-pagination-footer',
        'badge.muted' => 'aft-muted',
    ];

    /**
     * Artflow Studio's ui-flow admin theme. Same Bootstrap 5 foundation as
     * {@see self::BOOTSTRAP} — ui-flow reskins Bootstrap's own selectors via
     * CSS variables rather than inventing new class names — so only the
     * slots ui-flow actually styles differently (soft buttons instead of
     * outline, a borderless hover table, a `table-light` head) are declared
     * here; the rest is a straight copy of the Bootstrap preset.
     *
     * @var array<string, string>
     */
    public const UI_FLOW = [
        'wrapper' => 'aftable-wrapper',
        'toolbar' => 'row mb-2',
        'toolbar.start' => 'col',
        'toolbar.end' => 'col d-flex justify-content-end',
        'filters' => 'mb-3',
        'filters.row' => 'row g-2 mb-2 align-items-end',
        'filters.instance' => 'row g-2 mb-2 align-items-end border-start border-3 border-primary ps-3',
        'filters.col' => 'col-12 col-sm-6 col-md-3',
        'filters.col.sm' => 'col-12 col-sm-6 col-md-2',
        'filters.col.value' => 'col-12 col-sm-4 col-md-3 col-lg-2',
        'filters.col.auto' => 'col-auto',

        'label' => 'form-label small text-muted',
        'input' => 'form-control form-control-sm',
        'select' => 'form-select form-select-sm',
        'checkbox' => 'form-check-input',
        'search' => 'form-control form-control-sm border-0 p-2 pe-4',

        'button' => 'btn btn-sm btn-soft-secondary',
        'button.primary' => 'btn btn-sm btn-soft-primary',
        'button.danger' => 'btn btn-sm btn-soft-danger',
        'button.warning' => 'btn btn-sm btn-soft-warning',
        'button.success' => 'btn btn-sm btn-soft-success',
        'button.action' => 'btn btn-sm btn-soft-primary',

        'dropdown' => 'dropdown me-2',
        'dropdown.toggle' => 'btn btn-soft-secondary btn-sm dropdown-toggle',
        'dropdown.menu' => 'dropdown-menu',
        'dropdown.item' => 'dropdown-item',

        'responsive' => 'table-responsive aftable-table-wrap',
        'table' => 'table table-hover table-borderless align-middle mb-0',
        'thead' => 'table-light',
        'th' => 'fw-bold position-relative',
        'tbody' => '',
        'tr' => '',
        'td' => '',
        'empty' => 'text-center align-middle py-5',

        'pagination' => 'artflow-table::livewire.pagination',
        'pagination.footer' => 'pt-3 mt-1',
        'badge.muted' => 'text-muted ms-1',
    ];

    /** @var array<string, array<string, string>> Presets registered at runtime. */
    private static array $custom = [];

    /**
     * Register a preset from a service provider:
     *
     *     Theme::register('acme', [...]);            // full map
     *     Theme::register('acme', [...], 'tailwind') // merge over tailwind
     */
    public static function register(string $name, array $classes, ?string $extends = null): void
    {
        self::$custom[$name] = $extends === null
            ? $classes
            : array_merge(self::preset($extends), $classes);
    }

    /** @return array<string, string> */
    public static function preset(string $name): array
    {
        return match ($name) {
            'bootstrap', 'bootstrap5' => self::BOOTSTRAP,
            'tailwind' => self::TAILWIND,
            'none', 'unstyled' => self::NONE,
            'ui-flow' => self::UI_FLOW,
            default => self::$custom[$name] ?? self::BOOTSTRAP,
        };
    }

    /** @return list<string> */
    public static function available(): array
    {
        return array_values(array_unique(array_merge(
            ['bootstrap', 'tailwind', 'none', 'ui-flow'],
            array_keys(self::$custom)
        )));
    }

    /**
     * Build the effective class map for one table.
     *
     * @param  string|null  $theme    preset name passed to the component
     * @param  array<string, string>  $overrides  per-table slot overrides
     * @return array<string, string>
     */
    public static function resolve(?string $theme = null, array $overrides = []): array
    {
        $preset = self::preset($theme ?? (string) config('aftable.theme.preset', 'bootstrap'));
        $appOverrides = (array) config('aftable.theme.overrides', []);

        return array_merge($preset, $appOverrides, array_filter(
            $overrides,
            fn ($value) => is_string($value)
        ));
    }
}
