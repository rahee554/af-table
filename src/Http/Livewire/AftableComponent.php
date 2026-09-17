<?php

namespace ArtflowStudio\Table\Http\Livewire;

use ArtflowStudio\Table\Concerns\BuildsQueries;
use ArtflowStudio\Table\Concerns\CachesResults;
use ArtflowStudio\Table\Concerns\DispatchesEvents;
use ArtflowStudio\Table\Concerns\ExportsData;
use ArtflowStudio\Table\Concerns\HandlesActions;
use ArtflowStudio\Table\Concerns\ManagesColumns;
use ArtflowStudio\Table\Concerns\PersistsState;
use ArtflowStudio\Table\Concerns\RendersCells;
use ArtflowStudio\Table\Concerns\SearchesRows;
use ArtflowStudio\Table\Concerns\SortsRows;
use ArtflowStudio\Table\Concerns\ValidatesInput;
use ArtflowStudio\Table\Exports\ArrayExport;
use ArtflowStudio\Table\Support\RelationPath;
use ArtflowStudio\Table\Support\SchemaCache;
use ArtflowStudio\Table\Support\TableCache;
use ArtflowStudio\Table\Support\Theme;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class AftableComponent extends Component
{
    /*
     * Composition
     * -----------
     * Eleven concerns, no `insteadof`, no aliases.
     *
     * Until 1.6.0 this was 27 traits joined by 40+ conflict-resolution clauses,
     * with 14 method names declared by more than one trait. PHP cannot warn you
     * when two such implementations disagree, and one pair genuinely did: two
     * traits declared getNestedValue() with the arguments in opposite orders,
     * the `insteadof` picked the wrong one, and every JSON column fataled.
     *
     * The rule now is simply that no two concerns may declare the same method
     * name. `CodeHealthRegressionTest` enforces it.
     */
    use BuildsQueries;
    use CachesResults;
    use DispatchesEvents;
    use ExportsData;
    use HandlesActions;
    use ManagesColumns;
    use PersistsState;
    use RendersCells;
    use SearchesRows;
    use SortsRows;
    use ValidatesInput;
    use WithPagination;

    // *----------- Properties -----------*//
    public $model;

    public $columns = [];

    public $visibleColumns = [];

    // *----------- Static / Foreach Mode -----------*//
    /** Raw rows for foreach/array data mode (max ~500 rows recommended for Livewire serialization) */
    public array $staticRows = [];

    /** Whether we are operating on static data instead of an Eloquent model */
    public bool $isStaticMode = false;

    /** Extra variables made available inside every `raw` column template via $varName or $vars['varName'] */
    public array $extraVars = [];

    /**
     * Optional custom outer table HTML passed via @aftable/@endaftable directive.
     * When set, the component renders its rows/headers INTO this template instead
     * of the default Bootstrap table markup.
     */
    public ?string $customTemplate = null;

    public $checkbox = false;

    public $search = '';

    public $sortColumn = null;

    public $sortBy = null; // Alias for sortColumn - for user convenience

    public $sortDirection = 'asc';

    public $selectedRows = [];

    public $selectAll = false;

    public $filters = [];

    public $filterColumn = null;

    public $filterOperator = '=';

    public $filterValue = null;

    public $multipleFilters = [];

    public $filterInstances = [];

    public $dateColumn = null;

    public $startDate = null;

    public $endDate = null;

    public $selectedColumn = null;

    public $numberOperator = '=';

    public $distinctValues = [];

    public $columnType = null;

    public $index = false;

    public $tableId = null;

    public $query = null;

    public $colvisBtn = true;

    public $perPage = 10;

    public $records = 10; // For template pagination dropdown wire:model

    public array $routeFallbacks = [];

    public $actions = [];

    // Performance optimization properties
    protected $cachedRelations = null;

    protected $cachedSelectColumns = null;

    protected $distinctValuesCacheTime = 300;

    /**
     * Per-instance override for the distinct-value cap. null = use
     * `aftable.query.max_distinct_values`. It used to default to 1000, which
     * meant the config value could never take effect.
     */
    protected ?int $maxDistinctValues = null;

    // Phase 1 Performance Optimizations — replaced by #[Computed] tableData()
    protected $distinctValuesCache = [];

    // *----------- Optional Configuration -----------*//
    public $searchable = true;

    public $exportable = false;

    public $printable = false;

    /** Print window configuration */
    public array $printConfig = [
        'title' => '',
        'header' => '',
        'footer' => '',
        'fontSize' => 11,
        'orientation' => 'portrait',
        'paperSize' => 'A4',
        'showDate' => true,
        'showPageNum' => true,
        'tableClass' => '',
    ];

    public $colSort = true;

    public $sort = 'desc';

    public $refreshBtn = false;

    /**
     * Named theme preset: 'bootstrap' (default), 'tailwind', 'none', or one
     * registered with Theme::register(). null uses `aftable.theme.preset`.
     */
    public ?string $theme = null;

    /**
     * Per-slot class overrides for THIS table, e.g.
     *   'classes' => ['table' => 'table table-sm my-theme', 'th' => 'sticky-top']
     * Merged over the preset, so you only name what you want to change.
     *
     * @var array<string, string>
     */
    public array $classes = [];

    /**
     * Defer the first data query until the rows scroll into view.
     *
     * The toolbar, filters and column menu render immediately; only the data
     * island waits. On a page with several heavy tables this turns one slow
     * response into a fast shell plus parallel fills.
     *
     * NOT named `lazy`: that is a RESERVED Livewire mount parameter. Livewire
     * strips `lazy` and `defer` from the params and uses them to defer the
     * WHOLE component — so a property by that name can never be set, and
     * passing it silently gets you component-level lazy loading (blank shell,
     * no toolbar) instead of this.
     *
     * Both are useful; they just do different things:
     *   ['deferData' => true]  fast shell, rows arrive on scroll   <- this
     *   ['lazy' => true]       Livewire defers the entire component
     */
    public bool $deferData = false;

    /**
     * Whether to inject the package's own CSS and JS assets.
     * Set to false when your theme already provides the needed styles and you want
     * zero extra network requests from this package.
     *
     * Usage: @livewire('aftable', ['assets' => false, ...])
     */
    public bool $assets = true;

    // *----------- Query String Parameters -----------*//
    public $queryString = [
        'records' => ['except' => 10],
        'perPage' => ['except' => 10], // Keep both for compatibility
        'search' => ['except' => ''],
        'sortColumn' => ['except' => null],
        'sortDirection' => ['except' => 'asc'],
        'filterColumn' => ['except' => null],
        'filterValue' => ['except' => null],
        'filterOperator' => ['except' => '='],
        'startDate' => ['except' => null],
        'endDate' => ['except' => null],
    ];

    // *----------- Additional Properties for Trait Functionality -----------*//
    public $enableSessionPersistence = true;

    public $enableQueryStringSupport = true;

    // *----------- INITIALIZATION AND CONFIGURATION METHODS -----------*//

    /**
     * Initialize columns from configuration
     * Normalizes column configuration to ensure consistency
     * Supports both direct arrays and named arrays
     *
     * Note: sortable/searchable are auto-enabled by HasAutoOptimization trait
     * No need to manually set them - just provide key and label
     */
    protected function initializeColumns(array $columns): array
    {
        $initialized = [];

        foreach ($columns as $key => $config) {
            // --- normalise every supported shape to [$columnKey, $config] ---
            if (is_string($config)) {
                // Shorthand: 'first_name'
                $columnKey = $config;
                $config = ['key' => $config];
            } elseif (! is_array($config)) {
                continue;
            } elseif (isset($config['key'])) {
                // Direct array: ['key' => 'id', 'label' => 'ID']
                $columnKey = $config['key'];
            } elseif (is_int($key)) {
                // Direct array WITHOUT a 'key' - e.g. ['relation' => 'department:name'].
                // Using the numeric array index here (the old behaviour) breaks
                // sorting, the visibility checkbox id and session persistence,
                // and silently renumbers when columns are reordered.
                // See AUDIT.md BUG-06.
                $columnKey = $this->deriveColumnKey($config, $key);
            } else {
                // Legacy named array: 'id' => ['label' => 'ID']
                $columnKey = $key;
            }

            // Two columns can legitimately read the SAME underlying database
            // column - two JSON paths out of one document, or a raw and a plain
            // rendering of one value. Keying purely on 'key' made the later one
            // overwrite the earlier, so the table silently lost a column.
            // See AUDIT.md BUG-34.
            $columnKey = $this->uniqueColumnKey($columnKey, $config, $initialized);

            $initialized[$columnKey] = array_merge([
                'key' => $config['key'] ?? $columnKey,
                'label' => ucfirst(str_replace('_', ' ', $columnKey)),
                // sortable/searchable auto-enabled by autoOptimizeColumns()
            ], $config);
        }

        return $initialized;
    }

    /**
     * Ensure a column key is not already taken, disambiguating by what makes
     * the column different rather than by a bare counter where possible.
     *
     * @param  array<string, mixed>  $taken
     */
    protected function uniqueColumnKey(string $columnKey, array $config, array $taken): string
    {
        if (! array_key_exists($columnKey, $taken)) {
            return $columnKey;
        }

        // `preferences` + json `theme` -> `preferences_theme`
        foreach (['json', 'function'] as $hint) {
            if (! empty($config[$hint]) && is_string($config[$hint])) {
                $suffix = preg_replace('/[^A-Za-z0-9_]+/', '_', $config[$hint]);
                $candidate = $columnKey.'_'.trim($suffix, '_');

                if (! array_key_exists($candidate, $taken)) {
                    return $candidate;
                }
            }
        }

        // `department_name` + relation `department:code` -> `department_name_code`
        if (! empty($config['relation']) && is_string($config['relation'])) {
            $parsed = RelationPath::tryParse($config['relation']);

            if ($parsed !== null) {
                $candidate = $columnKey.'_'.$parsed->attribute;

                if (! array_key_exists($candidate, $taken)) {
                    return $candidate;
                }
            }
        }

        $suffix = 2;
        while (array_key_exists($columnKey.'_'.$suffix, $taken)) {
            $suffix++;
        }

        return $columnKey.'_'.$suffix;
    }

    /**
     * Derive a stable key for a column declared without one.
     *
     * Preference order: the relation name, then the JSON path, then the
     * function name - anything but the array index, which changes whenever a
     * column is reordered.
     */
    protected function deriveColumnKey(array $config, int $index): string
    {
        if (isset($config['relation']) && is_string($config['relation'])) {
            $parsed = RelationPath::tryParse($config['relation']);

            if ($parsed !== null) {
                // department.company:name -> department_company_name
                return str_replace('.', '_', $parsed->path).'_'.$parsed->attribute;
            }
        }

        foreach (['json', 'function'] as $hint) {
            if (isset($config[$hint]) && is_string($config[$hint]) && $config[$hint] !== '') {
                return preg_replace('/[^A-Za-z0-9_]+/', '_', $config[$hint]);
            }
        }

        return 'column_'.$index;
    }

    /**
     * Initialize column configuration metadata
     * Sets up eager loading, caching, and optimization
     */
    protected function initializeColumnConfiguration(array $columns): void
    {
        // Calculate required relations for eager loading
        $this->cachedRelations = $this->calculateRequiredRelations($columns);

        // Calculate select columns for optimization
        $this->cachedSelectColumns = $this->calculateSelectColumns($columns);
    }

    /**
     * Calculate required relations for eager loading optimization
     * Extracts all relation names from column configurations
     */
    protected function calculateRequiredRelations(array $columns): array
    {
        $relations = [];

        foreach ($columns as $column) {
            if (! is_array($column) || ! isset($column['relation'])) {
                continue;
            }

            // One parser, one place that knows the 'a.b:column' format. The old
            // inline explode(':') emitted "Undefined array key 1" whenever a
            // relation string was missing its colon.
            $parsed = RelationPath::tryParse((string) $column['relation']);

            if ($parsed === null) {
                Log::warning('AftableComponent: ignoring malformed relation', [
                    'relation' => $column['relation'],
                ]);

                continue;
            }

            // Every intermediate path, so 'a.b.c' eager loads a, a.b and a.b.c.
            foreach ($parsed->eagerLoads as $path) {
                $relations[$path] = true;
            }
        }

        return array_keys($relations);
    }

    /**
     * Relations this table needs eager loaded.
     *
     * MUST be derived from $this->columns rather than read from a stored
     * property: $cachedRelations is protected, and Livewire only persists
     * PUBLIC properties. It was therefore null on every request after mount,
     * so applyOptimizedEagerLoading() added no with() and each relation column
     * lazy-loaded once per row - an N+1 that appeared only after the first
     * interaction, which is why it survived so long. See AUDIT.md BUG-38.
     *
     * @return list<string>
     */
    protected function requiredRelations(): array
    {
        if ($this->cachedRelations === null || $this->cachedRelations === []) {
            $this->cachedRelations = $this->calculateRequiredRelations($this->columns ?? []);
        }

        return $this->cachedRelations;
    }

    /**
     * Calculate select columns for query optimization
     * Returns array of columns to select from main table
     */
    protected function calculateSelectColumns(array $columns): array
    {
        $selectColumns = ['*']; // Start with all columns from main table

        // If we want to optimize further, we could specify exact columns
        // But for safety and compatibility, we use '*'

        return $selectColumns;
    }

    /**
     * Get the optimal sort column for initialization
     * Returns first sortable column or 'id'
     */
    protected function getOptimalSortColumn(): ?string
    {
        // Try to find first sortable column
        foreach ($this->columns as $columnKey => $column) {
            if (! is_array($column)) {
                continue;
            }

            $sortable = $column['sortable'] ?? true;
            if ($sortable && ! isset($column['function']) && ! isset($column['raw'])) {
                return $columnKey;
            }
        }

        // Default to id if it exists
        if (isset($this->columns['id'])) {
            return 'id';
        }

        return null;
    }

    // *----------- CORE LIVEWIRE METHODS -----------*//

    /**
     * Component Initialization - Core Livewire method
     * PERFORMANCE FIX: Pre-load distinct values on mount
     */
    public function mount($model = null, $columns = [], $filters = [], $actions = [], $index = false, $tableId = null, $query = null, $countAggregations = [], $sortBy = null, $sortDirection = null, $sort = null, $data = null, $vars = [], $customTemplate = null, $theme = null, $classes = [], $deferData = false)
    {
        // ---- Presentation ----
        $this->theme = is_string($theme) && $theme !== '' ? $theme : null;
        $this->classes = is_array($classes) ? array_filter($classes, 'is_string') : [];
        $this->deferData = (bool) $deferData;

        // ---- Static / foreach mode detection ----
        $this->extraVars = is_array($vars) ? $vars : [];
        $this->customTemplate = is_string($customTemplate) && $customTemplate !== '' ? $customTemplate : null;

        if ($data !== null) {
            $this->isStaticMode = true;
            $this->staticRows = $this->normalizeStaticData($data);
        }

        $this->model = $model ?? ($this->isStaticMode ? '__static__' : null);
        $this->tableId = $tableId ?? (is_string($model) ? $model : (is_object($model) ? get_class($model) : uniqid('datatable_')));
        $this->query = $query;

        // Delegate column initialization to HasColumnManagement trait
        $this->columns = $this->initializeColumns($columns);
        $this->initializeColumnConfiguration($this->columns);

        // AUTO-OPTIMIZATION
        if (! $this->isStaticMode) {
            // Initialize count aggregations FIRST before anything else
            if (! empty($countAggregations)) {
                $this->setCountAggregations($countAggregations);
            }
            $this->autoDetectCountAggregations();  // Detect _count columns
            $this->autoApplyEagerLoading($this->model::query());  // Prepare eager loading
        }

        $this->autoOptimizeColumns();   // Column config analysis — safe in both modes
        $this->autoDetectOptimalSort(); // Auto-detect best sort column

        // Delegate column visibility to HasColumnVisibility trait
        $this->initializeColumnVisibility();

        $this->filters = $filters;
        $this->actions = $actions;
        $this->index = $index;

        // Handle sorting parameters: sortBy takes precedence over auto-detection
        if (! empty($sortBy)) {
            $this->sortColumn = $sortBy;
            $this->sortBy = $sortBy;
        }

        // Handle sort direction: sortDirection takes precedence, fallback to sort for backward compatibility
        if (! empty($sortDirection)) {
            $this->sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc';
        } elseif (! empty($sort)) {
            // Backward compatibility: 'sort' parameter can specify direction
            $this->sortDirection = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
        }

        // Delegate sort optimization to HasSorting trait - only if not already set
        if (empty($this->sortColumn)) {
            $this->sortColumn = $this->getOptimalSortColumn();
        }

        // Keep sortBy in sync with sortColumn for Livewire reactivity
        if (empty($this->sortBy) && ! empty($this->sortColumn)) {
            $this->sortBy = $this->sortColumn;
        }

        // PERFORMANCE FIX: Pre-load all distinct values on mount
        $this->preloadDistinctValues();
    }

    // *----------- STATIC / FOREACH MODE METHODS -----------*//

    /**
     * Normalize incoming data to a plain-array format that Livewire can safely serialize.
     * Accepts: array of arrays, array of objects, Eloquent Collection, plain Collection.
     */
    protected function normalizeStaticData(mixed $data): array
    {
        $collection = $data instanceof Collection ? $data : collect(is_array($data) ? $data : []);

        return $collection->map(function ($item) {
            if ($item instanceof Model) {
                return $item->toArray();
            }

            if (is_object($item)) {
                return (array) $item;
            }

            return is_array($item) ? $item : [];
        })->values()->all();
    }

    /**
     * In-memory filter, sort and paginate the static rows.
     * Rows are stored as plain arrays; they are cast to stdClass for the view
     * so that `$row->property` works exactly like Eloquent rows.
     *
     * Usage example in a Blade view:
     *
     *   @livewire('aftable', [
     *       'data'    => $myCollection,
     *       'columns' => [
     *           ['key' => 'name',   'label' => 'Name'],
     *           ['key' => 'price',  'label' => 'Price'],
     *           // PHP calculation in raw — works in static AND Eloquent mode:
     *           ['key' => 'total', 'label' => 'Total', 'sortable' => false,
     *            'raw' => '{{ number_format($row->price * $row->qty, 2) }}'],
     *       ],
     *   ])
     */
    protected function getStaticPaginatedData(): LengthAwarePaginator
    {
        $collection = $this->getStaticFilteredRows();

        $perPage = $this->getPerPageValue();
        $page = $this->getPage();
        $total = $collection->count();

        // Cast each plain array back to stdClass so `$row->property` works in views and raw templates
        $items = $collection->forPage($page, $perPage)
            ->map(fn (array $r) => (object) $r)
            ->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * The static rows with the active search, filter and sort applied, but no
     * pagination.
     *
     * Split out of getStaticPaginatedData() so export can reuse it. Export
     * previously called buildUnifiedQuery() unconditionally, which does
     * `$this->model::query()` — and in static mode `$this->model` is the
     * '__static__' sentinel, so exporting a foreach-mode table was a fatal
     * `Class "__static__" not found`. See AUDIT.md BUG-47.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function getStaticFilteredRows(): Collection
    {
        $collection = collect($this->staticRows);

        // --- Search (in-memory) ---
        if ($this->searchable && $this->search && strlen(trim($this->search)) >= 3) {
            $term = strtolower(trim($this->search));

            $collection = $collection->filter(function (array $row) use ($term) {
                foreach ($this->columns as $col) {
                    if (! is_array($col)) {
                        continue;
                    }

                    $key = $col['key'] ?? null;
                    $searchable = $col['searchable'] ?? true;

                    if (! $key || ! $searchable || isset($col['function'])) {
                        continue;
                    }

                    if (str_contains(strtolower((string) ($row[$key] ?? '')), $term)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        // --- Column filter (in-memory) ---
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== '') {
            $filterCol = $this->filterColumn;
            $filterVal = $this->filterValue;
            $filterOp = $this->filterOperator ?? '=';

            $collection = $collection->filter(function (array $row) use ($filterCol, $filterVal, $filterOp) {
                $val = (string) ($row[$filterCol] ?? '');

                return $this->evaluateCondition($val, $filterOp === '=' ? '==' : $filterOp, $filterVal);
            })->values();
        }

        // --- Sorting (in-memory) ---
        if ($this->sortColumn) {
            $sortKey = $this->sortColumn;
            $direction = $this->sortDirection ?? 'asc';

            $collection = ($direction === 'desc')
                ? $collection->sortByDesc(fn (array $r) => $r[$sortKey] ?? null)->values()
                : $collection->sortBy(fn (array $r) => $r[$sortKey] ?? null)->values();
        }

        return $collection;
    }

    /**
     * In static mode, derive distinct filter values from in-memory rows (no DB query).
     */
    protected function getStaticDistinctValues(string $columnKey): array
    {
        $key = $this->columns[$columnKey]['key'] ?? $columnKey;

        return collect($this->staticRows)
            ->pluck($key)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Pre-load distinct values for all select/distinct filters
     * PERFORMANCE FIX: Prevents repeated database queries on each render
     */
    protected function preloadDistinctValues(): void
    {
        foreach ($this->filters as $filterKey => $filterConfig) {
            $filterType = $filterConfig['type'] ?? null;
            if (in_array($filterType, ['select', 'distinct'])) {
                try {
                    $this->distinctValuesCache[$filterKey] = $this->loadDistinctValuesForFilter($filterKey);
                } catch (\Exception $e) {
                    // Silently fail for individual filters to not break the component
                    Log::warning("Failed to preload distinct values for filter: {$filterKey}", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->distinctValuesCache[$filterKey] = [];
                }
            }
        }
    }

    /**
     * Load distinct values for a specific filter
     * Internal method used by preloadDistinctValues()
     */
    protected function loadDistinctValuesForFilter(string $filterKey): array
    {
        if (! isset($this->columns[$filterKey]) || ! isset($this->filters[$filterKey])) {
            return [];
        }

        try {
            // In static mode, derive distinct values from in-memory rows — no DB queries
            if ($this->isStaticMode) {
                return $this->getStaticDistinctValues($filterKey);
            }

            // getCachedDistinctValues() is the method that actually caches.
            // The `getCachedColumnDistinctValues` alias points at the RAW query
            // implementation, so using it here re-queried the database on every
            // single mount. See AUDIT.md BUG-33.
            return $this->getCachedDistinctValues($filterKey);
        } catch (\Exception $e) {
            Log::warning("Error loading distinct values for {$filterKey}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Core query building - Unique to main class
     * PERFORMANCE FIX: Consolidated filter logic to prevent duplicates
     */
    protected function buildUnifiedQuery(): Builder
    {
        // Start with base model query
        $query = $this->model::query();

        // Apply custom query constraints with validation
        if ($this->query) {
            $this->applyCustomQueryConstraints($query);
        }

        // Apply count aggregations FIRST to prevent N+1 queries
        $query = $this->applyCountAggregations($query);

        // Delegate optimization to traits
        $query = $this->applyColumnOptimization($query);
        $query = $this->applyOptimizedEagerLoading($query);

        // Delegate search to HasUnifiedSearch trait
        if ($this->searchable && $this->search && strlen(trim($this->search)) >= 3) {
            $this->applyOptimizedSearch($query);
        }

        // PERFORMANCE FIX: Apply all filters through consolidated method
        $this->applyAllFilters($query);

        // Delegate sorting to HasSorting trait
        if ($this->sortColumn) {
            $this->applyOptimizedSorting($query);
        }

        return $query;
    }

    /**
     * Apply all filters in a consolidated way to prevent duplicates
     * PERFORMANCE FIX: Consolidates filter logic from multiple sources
     */
    protected function applyAllFilters(Builder $query): void
    {
        // Primary filter
        if ($this->filterColumn && $this->filterValue !== null && $this->filterValue !== ''
            && $this->isAllowedFilterColumn($this->filterColumn)) {
            if (isset($this->filters[$this->filterColumn]['relation'])) {
                $this->applyRelationFilter($query, $this->filterColumn, $this->filterOperator, $this->filterValue);
            } else {
                $this->applyColumnFilter($query, $this->filterColumn, $this->filterOperator, $this->filterValue);
            }
        }

        // Additional filters from filterInstances
        if (! empty($this->filterInstances)) {
            $this->applyMultipleFilters($query);
        }

        // Date range filter
        if ($this->dateColumn && $this->startDate && $this->endDate) {
            $this->applyDateRangeFilter($query, $this->dateColumn, $this->startDate, $this->endDate);
        }

        // Note: applyAdvancedFilters is NOT called here as it would duplicate filters
        // The filters are already applied through filterColumn and filterInstances
    }

    /**
     * Get the per-page value for pagination
     */
    public function getPerPageValue(): int
    {
        // Use records if set (from template), otherwise use perPage
        $requested = (int) ($this->records ?? $this->perPage ?? 10);

        // `records` and `perPage` are both query-string properties, so this is
        // user input: `?records=999999` used to make the server select, hydrate
        // and serialise the entire table into the Livewire snapshot on every
        // request. Clamp to the largest configured page size. A host app that
        // genuinely wants bigger pages raises `per_page_options`.
        // See AUDIT.md BUG-49.
        $options = array_filter(array_map('intval', (array) config(
            'aftable.query.per_page_options', [10, 25, 50, 100, 500]
        )));

        $ceiling = $options === [] ? 500 : max($options);

        return max(1, min($requested, $ceiling));
    }

    /**
     * Get data — branches between static (foreach) mode and Eloquent mode.
     */
    public function getData()
    {
        if ($this->isStaticMode) {
            return $this->getStaticPaginatedData();
        }

        $this->triggerBeforeQuery(null);

        try {
            $query = $this->buildUnifiedQuery();

            return $query->paginate($this->getPerPageValue());
        } catch (\Exception $e) {
            Log::error('DatatableTrait getData error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Livewire 4 computed property — replaces manual hash-based caching.
     *
     * Livewire caches this result for the entire request lifecycle. When any
     * component state changes (search, sort, filter, page…) the next request
     * clears the cache and re-computes automatically. Call `unset($this->tableData)`
     * inside an action to force an early invalidation within the same request.
     */
    #[Computed]
    public function tableData(): mixed
    {
        return $this->getData();
    }

    /**
     * Render the component — Livewire 4 lifecycle method.
     *
     * Data is now sourced through the `#[Computed]` tableData() property so
     * Livewire handles caching and invalidation automatically.
     */
    public function render()
    {
        $data = [
            'index' => $this->index,
            'customTemplate' => $this->customTemplate,
            'ui' => $this->themeClasses(),
        ];

        // Touching $this->tableData here would run the query even when the data
        // island is only going to render its placeholder, which defeats the
        // whole point of deferData. The island re-resolves it from the component
        // when it actually renders, so leaving it out is enough.
        if (! $this->deferData) {
            $data['data'] = $this->tableData;
        }

        return view('artflow-table::livewire.aftable-component', $data);
    }

    /**
     * The resolved class map for this table.
     *
     * Cached per request: the template asks for a slot on every row, and
     * re-merging three arrays each time is pure waste.
     *
     * @return array<string, string>
     */
    public function themeClasses(): array
    {
        return $this->resolvedTheme ??= Theme::resolve($this->theme, $this->classes);
    }

    /** @var array<string, string>|null */
    protected ?array $resolvedTheme = null;

    /**
     * Invalidate the computed tableData cache.
     *
     * Livewire 4: `unset($this->tableData)` clears the #[Computed] cache so the
     * next access within the same request re-runs getData().  This is called
     * after actions that mutate query-affecting state (search, filter, sort).
     */
    protected function invalidateQueryCache(): void
    {
        unset($this->tableData);
    }

    // *----------- LIVEWIRE LIFECYCLE METHODS -----------*//

    /** Previous search term, captured before Livewire overwrites $search. */
    protected ?string $previousSearch = null;

    /**
     * Runs BEFORE $search is overwritten, so it is the only place the old value
     * is still available.
     */
    public function updatingSearch($value): void
    {
        $this->previousSearch = (string) $this->search;
    }

    /**
     * Handle search updates - Core Livewire lifecycle
     */
    public function updatedSearch($value)
    {
        // NOTE: Livewire assigns the property BEFORE calling updated*(), so
        // $this->search already equals $value here. The previous
        // `if ($value === $this->search) return;` guard was therefore always
        // true and this whole method was dead code. See AUDIT.md BUG-04.
        // updatingSearch() captures the previous value for us.
        $oldValue = $this->previousSearch ?? '';
        $this->search = $value;

        // Only reset page and invalidate cache if search is meaningful
        if (strlen(trim((string) $value)) >= 3 || strlen(trim((string) $oldValue)) >= 3) {
            $this->resetPage();
            $this->invalidateQueryCache();
        }

        $this->triggerSearchEvent($value, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->saveSearchToSession($value);
        }
    }

    /**
     * Handle filter updates - Core Livewire lifecycle
     */
    public function updatedFilters($value, $key)
    {
        $columnKey = str_replace('filters.', '', $key);
        $oldValue = $this->filters[$columnKey] ?? null;

        $this->resetPage();
        $this->triggerFilterEvent($columnKey, $value, $oldValue);

        if ($this->enableSessionPersistence) {
            $this->saveFiltersToSession();
        }
    }

    /**
     * Handle filter column changes - Reset filter value when column changes
     * This prevents the value from previous column persisting
     * PERFORMANCE FIX: Invalidate cache when filter changes
     */
    public function updatedFilterColumn($value)
    {
        // Reset filter value and operator when column changes
        $this->filterValue = null;
        $this->filterOperator = '=';
        $this->resetPage();
        $this->invalidateQueryCache();
    }

    /**
     * Handle sorting - Core Livewire lifecycle
     * PERFORMANCE FIX: Invalidate cache when sort changes
     * Now syncs both sortColumn and sortBy for consistency
     */
    public function sortBy($column)
    {
        $oldColumn = $this->sortColumn;
        $oldDirection = $this->sortDirection;

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        // Keep sortBy in sync with sortColumn
        $this->sortBy = $this->sortColumn;

        $this->resetPage();
        $this->invalidateQueryCache();
        $this->triggerSortEvent($this->sortColumn, $this->sortDirection, $oldColumn, $oldDirection);

        if ($this->enableSessionPersistence) {
            $this->saveSortingToSession();
        }
    }

    /** Previous page size, captured before Livewire overwrites $perPage. */
    protected ?int $previousPerPage = null;

    public function updatingPerPage($value): void
    {
        $this->previousPerPage = (int) $this->perPage;
    }

    /**
     * Handle per page changes - Core Livewire lifecycle
     */
    public function updatedPerPage($value)
    {
        $oldValue = $this->previousPerPage ?? $value;
        $this->perPage = $value;
        $this->resetPage();

        $this->triggerPaginationEvent(1, $value, $this->getPage(), $oldValue);

        if ($this->enableSessionPersistence) {
            $this->savePerPageToSession($value);
        }
    }

    /**
     * Handle records per page update (legacy method for template compatibility)
     */
    public function updatedrecords($value)
    {
        $this->perPage = $value;
        $this->records = $value; // Keep both in sync
        $this->resetPage();
    }

    /**
     * Toggle column visibility - Delegates to HasColumnVisibility
     */
    public function toggleColumn($columnKey)
    {
        $this->toggleColumnVisibility($columnKey);
    }

    // *----------- USER ACTION DELEGATES -----------*//

    /**
     * Handle the 'dateRangeSelected' Livewire event.
     * Livewire 4: uses #[On] attribute instead of $listeners array.
     */
    #[On('dateRangeSelected')]
    public function applyDateRange($startDate, $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->resetPage();
        $this->invalidateQueryCache();
    }

    /**
     * Handle the 'refreshTable' Livewire event.
     * Livewire 4: uses #[On] attribute instead of $listeners array.
     */
    #[On('refreshTable')]
    public function refreshTableHandler(): void
    {
        $this->invalidateQueryCache();
    }

    /**
     * Refresh the table data - button action.
     * Invalidates the computed cache so the next render fetches fresh data.
     */
    public function refreshTable(): void
    {
        $this->invalidateQueryCache();
    }

    /**
     * Add a new filter instance
     */
    public function addFilterInstance()
    {
        $this->filterInstances[] = [
            'id' => uniqid(),
            'column' => null,
            'operator' => '=',
            'value' => null,
        ];
    }

    /**
     * Remove a filter instance by ID
     */
    public function removeFilterInstance($instanceId)
    {
        // array_values(): array_filter() preserves keys, which turns the
        // property into a JSON object on the wire and makes the next
        // $filterInstances[] append at the wrong index. See AUDIT.md BUG-23.
        $this->filterInstances = array_values(array_filter(
            $this->filterInstances,
            fn ($instance) => $instance['id'] !== $instanceId
        ));
        $this->resetPage();
    }

    /**
     * Update a filter instance
     * PERFORMANCE FIX: Invalidate cache when filter changes
     */
    public function updateFilterInstance($instanceId, $field, $value)
    {
        foreach ($this->filterInstances as &$instance) {
            if ($instance['id'] === $instanceId) {
                $instance[$field] = $value;
                break;
            }
        }
        $this->resetPage();
        $this->invalidateQueryCache();
    }

    /**
     * Apply multiple filter instances to query
     */
    protected function applyMultipleFilters(Builder $query): void
    {
        foreach ($this->filterInstances as $filterInstance) {
            if (! $filterInstance['column'] || $filterInstance['value'] === null || $filterInstance['value'] === '') {
                continue;
            }

            // Same client-controlled-column problem as the primary filter.
            if (! $this->isAllowedFilterColumn($filterInstance['column'])) {
                continue;
            }

            $column = $filterInstance['column'];
            $operator = $filterInstance['operator'] ?? '=';
            $value = $filterInstance['value'];

            // Check if this is a relation filter
            if (isset($this->filters[$column]['relation'])) {
                $this->applyRelationFilter($query, $column, $operator, $value);
            } else {
                // Apply regular column filter
                $this->applyColumnFilter($query, $column, $operator, $value);
            }
        }
    }

    /**
     * Apply relation filter for multiple filters
     * FIX: Handle distinct filters differently from search/manual filters
     * Distinct filters use IDs, so we filter by foreign key directly
     * Other filters search through the relation
     */
    protected function applyRelationFilter(Builder $query, string $column, string $operator, $value): void
    {
        $relationConfig = $this->filters[$column]['relation'] ?? null;

        if (! $relationConfig) {
            // Fallback to direct column filtering if no relation config
            $this->applyOperatorCondition($query, $column, $operator, $value);

            return;
        }

        // Check if this is a distinct filter
        $filterType = $this->filters[$column]['type'] ?? null;

        if ($filterType === 'distinct') {
            // DISTINCT FILTERS: Use foreign key directly
            // The distinct dropdown sends the ID (e.g., module_id = 1)
            // So we filter by the foreign key column directly
            $this->applyOperatorCondition($query, $column, $operator, $value);

            return;
        }

        // NON-DISTINCT FILTERS: Filter through the relation
        // Parse the relation string
        $parsed = $this->parseRelationString($relationConfig);

        if (empty($parsed)) {
            return;
        }

        $relationPath = $parsed['relationPath'];
        $attribute = $parsed['attribute'];

        // Filter through the relation for search/manual filters
        if (strpos($relationPath, '.') !== false) {
            // Nested relation - use nested whereHas
            $this->applyNestedRelationFilter($query, $relationPath, $attribute, $operator, $value);
        } else {
            // Simple relation - use standard whereHas
            $query->whereHas($relationPath, function ($q) use ($attribute, $operator, $value) {
                $this->applyOperatorCondition($q, $attribute, $operator, $value);
            });
        }
    }

    /**
     * Apply nested relation filter for multi-level relations
     * e.g., 'student.user:name' => whereHas('student', function() { whereHas('user', ...) })
     */
    protected function applyNestedRelationFilter(Builder $query, string $relationPath, string $attribute, string $operator, $value): void
    {
        $parts = explode('.', $relationPath);

        // Build nested whereHas calls from outside in
        $this->buildNestedWhereHas($query, $parts, $attribute, $operator, $value);
    }

    /**
     * Recursively build nested whereHas clauses
     */
    protected function buildNestedWhereHas(Builder $query, array $relationParts, string $attribute, string $operator, $value, int $depth = 0): void
    {
        if ($depth >= count($relationParts)) {
            return;
        }

        $currentRelation = $relationParts[$depth];

        if ($depth === count($relationParts) - 1) {
            // Last relation - apply the filter
            $query->whereHas($currentRelation, function ($q) use ($attribute, $operator, $value) {
                $this->applyOperatorCondition($q, $attribute, $operator, $value);
            });
        } else {
            // Intermediate relation - nest another whereHas
            $query->whereHas($currentRelation, function ($q) use ($relationParts, $attribute, $operator, $value, $depth) {
                $this->buildNestedWhereHas($q, $relationParts, $attribute, $operator, $value, $depth + 1);
            });
        }
    }

    /**
     * Apply column filter for multiple filters
     */
    protected function applyColumnFilter(Builder $query, string $column, string $operator, $value): void
    {
        $this->applyOperatorCondition($query, $column, $operator, $value);
    }

    /**
     * Apply operator condition to query
     */
    protected function applyOperatorCondition(Builder $query, string $column, string $operator, $value): void
    {
        // Qualify so a relation-sort join cannot make this ambiguous.
        $column = $this->qualifyColumn($column);

        switch ($operator) {
            case '=':
                $query->where($column, $value);
                break;
            case '!=':
                $query->where($column, '!=', $value);
                break;
            case '>':
                $query->where($column, '>', $value);
                break;
            case '>=':
                $query->where($column, '>=', $value);
                break;
            case '<':
                $query->where($column, '<', $value);
                break;
            case '<=':
                $query->where($column, '<=', $value);
                break;
            case 'like':
                $this->whereContains($query, $column, (string) $value);
                break;
            default:
                $query->where($column, $value);
                break;
        }
    }

    /**
     * Clear all filter instances
     */
    public function clearAllFilterInstances()
    {
        $this->filterInstances = [];
        $this->resetPage();
    }

    /**
     * Clear all filters - Delegates to HasUserActions
     */
    public function clearAllFilters()
    {
        $this->clearFilters();
    }

    /**
     * Clear search - Delegates to HasUserActions
     */
    public function clearSearch()
    {
        $this->clearSearchInput();
    }

    /**
     * Handle export - Delegates to HasExportFeatures.
     */
    public function handleExport($format = 'csv', $filename = null)
    {
        try {
            // Static mode has no query to build; the rows are already here.
            $data = $this->isStaticMode
                ? $this->getStaticFilteredRows()->map(fn (array $r) => (object) $r)->values()
                : $this->buildUnifiedQuery()->get();

            if ($data->isEmpty()) {
                $this->dispatch('showAlert', [
                    'type' => 'warning',
                    'message' => 'No data to export',
                ]);

                return;
            }

            // Prepare filename
            if (! $filename) {
                // class_basename('__static__') is '__static__', which is not a
                // filename anyone wants to receive.
                $modelName = $this->isStaticMode ? 'table' : class_basename($this->model);
                $filename = strtolower($modelName).'_export_'.date('Y-m-d_H-i-s');
            }

            // Export based on format. Every branch here must resolve to a
            // method on THIS class - the HasExportFeatures trait declares
            // one-parameter versions of the same names. See AUDIT.md BUG-08.
            return match (strtolower($format)) {
                'excel', 'xlsx' => $this->exportRowsToExcel($data, $filename),
                'pdf' => $this->exportRowsToPdf($data, $filename),
                default => $this->exportToCsv($data, $filename),
            };

        } catch (\Throwable $e) {
            // Throwable, not Exception: a bad model or column produces an
            // \Error, which the old catch let through as a 500.
            Log::error('Export error: '.$e->getMessage());
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Export failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Which export formats can this installation actually produce?
     *
     * CSV is always available. Excel needs maatwebsite/excel and PDF needs one
     * of the usual PDF packages; both are `suggest`, not `require`. The toolbar
     * asks this so it can stop advertising a format that would fail - the old
     * dropdown always offered all three. See AUDIT.md BUG-10.
     *
     * @return array<string, bool>
     */
    public function availableExportFormats(): array
    {
        return [
            'csv' => true,
            'xlsx' => class_exists(Excel::class),
            'pdf' => class_exists(SnappyPdf::class)
                || class_exists(Pdf::class),
        ];
    }

    /**
     * Excel export. Falls back to CSV-with-a-warning rather than pretending.
     */
    protected function exportRowsToExcel($data, string $filename)
    {
        if (! $this->availableExportFormats()['xlsx']) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'Excel export needs maatwebsite/excel. Exporting CSV instead.',
            ]);

            return $this->exportToCsv($data, $filename);
        }

        $rows = $this->exportRows($data);

        return Excel::download(
            new ArrayExport($rows['headings'], $rows['rows']),
            $filename.'.xlsx'
        );
    }

    /**
     * PDF export. Falls back to CSV-with-a-warning rather than pretending.
     */
    protected function exportRowsToPdf($data, string $filename)
    {
        $formats = $this->availableExportFormats();

        if (! $formats['pdf']) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'PDF export needs barryvdh/laravel-snappy or barryvdh/laravel-dompdf. Exporting CSV instead.',
            ]);

            return $this->exportToCsv($data, $filename);
        }

        $rows = $this->exportRows($data);

        $html = view('artflow-table::exports.pdf', [
            'title' => $this->printConfig['title'] ?: class_basename($this->model),
            'headings' => $rows['headings'],
            'rows' => $rows['rows'],
        ])->render();

        if (class_exists(Pdf::class)) {
            return Pdf::loadHTML($html)->download($filename.'.pdf');
        }

        return SnappyPdf::loadHTML($html)->download($filename.'.pdf');
    }

    /**
     * Flatten the result set into headings + scalar rows, honouring column
     * visibility and rendering relation / JSON / raw columns properly.
     *
     * The CSV writer used to emit an empty cell for every relation, JSON and
     * raw column, and exported hidden columns too.
     *
     * @return array{headings: list<string>, rows: list<list<string>>}
     */
    protected function exportRows($data): array
    {
        $columns = [];

        foreach ($this->columns as $columnKey => $config) {
            if (! is_array($config)) {
                $config = ['key' => $columnKey];
            }

            // Respect what the user can actually see.
            $visible = $this->visibleColumns[$columnKey] ?? ! ($config['hide'] ?? false);

            if (! $visible) {
                continue;
            }

            $columns[$columnKey] = $config;
        }

        $headings = [];
        foreach ($columns as $columnKey => $config) {
            $headings[] = $config['label'] ?? ucfirst(str_replace('_', ' ', $columnKey));
        }

        $rows = [];
        foreach ($data as $row) {
            $line = [];

            foreach ($columns as $columnKey => $config) {
                $line[] = $this->exportCellValue($row, $columnKey, $config);
            }

            $rows[] = $line;
        }

        return ['headings' => $headings, 'rows' => $rows];
    }

    /**
     * Resolve one cell to a plain scalar suitable for a spreadsheet.
     */
    protected function exportCellValue($row, string $columnKey, array $config): string
    {
        try {
            if (isset($config['json'], $config['key'])) {
                $value = $this->extractJsonValue($row, $columnKey, $config['json']);

                return $this->stringifyForExport($value);
            }

            if (isset($config['relation'])) {
                $parsed = RelationPath::tryParse($config['relation']);

                if ($parsed !== null) {
                    return $this->stringifyForExport(
                        data_get($row, $parsed->path.'.'.$parsed->attribute)
                    );
                }
            }

            if (isset($config['function']) && is_object($row) && method_exists($row, $config['function'])) {
                return $this->stringifyForExport($row->{$config['function']}());
            }

            if (isset($config['raw'])) {
                // Render, then strip markup - a spreadsheet cell wants the text.
                $rendered = $this->renderRawHtml($config['raw'], $row);

                return trim(html_entity_decode(strip_tags($rendered), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            if (isset($config['key'])) {
                return $this->stringifyForExport(data_get($row, $config['key']));
            }
        } catch (\Throwable $e) {
            Log::warning('Export cell failed', ['column' => $columnKey, 'error' => $e->getMessage()]);
        }

        return '';
    }

    protected function stringifyForExport($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value) || $value instanceof \JsonSerializable) {
            return (string) json_encode($value);
        }

        if (is_object($value) && ! method_exists($value, '__toString')) {
            return (string) json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Export data to CSV
     */
    protected function exportToCsv($data, $filename)
    {
        $prepared = $this->exportRows($data);

        return response()->streamDownload(function () use ($prepared) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens accented and non-Latin text correctly
            // instead of mojibake.
            fwrite($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, $prepared['headings']);

            foreach ($prepared['rows'] as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ]);
    }

    /**
     * Select all visible records - Delegates to HasBulkActions
     */
    public function selectAllVisible()
    {
        $this->selectAllOnPage();
    }

    /**
     * Clear all selections - Delegates to HasBulkActions
     */
    public function clearAllSelections()
    {
        $this->clearSelection();
    }

    /**
     * Refresh component - Delegates to HasUtilities
     */
    public function refresh()
    {
        $this->refreshComponent();
    }

    // *----------- CONFIGURATION AND STATS -----------*//

    /**
     * Get component statistics - Delegates to HasPerformanceMonitoring
     */
    public function getComponentStats(): array
    {
        // HasPerformanceMonitoring carried 423 lines to produce this, of which
        // exactly one method was ever reached. Build the useful part directly.
        return [
            'table_id' => $this->tableId,
            'model' => is_object($this->model) ? get_class($this->model) : $this->model,
            'mode' => $this->isStaticMode ? 'static' : 'eloquent',
            'columns' => count($this->columns ?? []),
            'visible_columns' => count(array_filter($this->visibleColumns ?? [])),
            'eager_loads' => $this->requiredRelations(),
            'per_page' => $this->getPerPageValue(),
            'memory_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            'cache' => TableCache::describe(),
        ];
    }

    /**
     * Get query string properties - Delegates to HasQueryStringSupport
     */
    public function getQueryString()
    {
        if (! $this->enableQueryStringSupport) {
            return [];
        }

        return $this->getQueryStringConfiguration();
    }

    /**
     * Get debug information - Delegates to HasUtilities
     */
    public function getDebugInfo(): array
    {
        return $this->getComponentDebugInfo();
    }

    /**
     * Get distinct values for a column (required by template for filter dropdowns)
     * PERFORMANCE FIX: Return from component-lifetime cache
     */
    public function getDistinctValues($columnKey)
    {
        // Return from cache if available
        if (isset($this->distinctValuesCache[$columnKey])) {
            return $this->distinctValuesCache[$columnKey];
        }

        // Check if column exists and has filter configuration
        if (! isset($this->columns[$columnKey]) || ! isset($this->filters[$columnKey])) {
            return [];
        }

        try {
            // Static mode — derive from in-memory rows, no DB query
            if ($this->isStaticMode) {
                $values = $this->getStaticDistinctValues($columnKey);
                $this->distinctValuesCache[$columnKey] = $values;

                return $values;
            }

            // Eloquent mode. getCachedDistinctValues() dispatches to the
            // relation or plain-column implementation itself, and caches the
            // result — see AUDIT.md BUG-33.
            $values = $this->getCachedDistinctValues($columnKey);

            $this->distinctValuesCache[$columnKey] = $values;

            return $values;
        } catch (\Exception $e) {
            Log::warning('DatatableTrait getDistinctValues error: '.$e->getMessage(), [
                'column' => $columnKey,
            ]);
            $this->distinctValuesCache[$columnKey] = [];

            return [];
        }
    }

    /**
     * Prefix a bare column name with the model's table.
     *
     * Relation sorting adds a leftJoin, and from that point on any unqualified
     * column in a WHERE or ORDER BY is ambiguous whenever both tables share the
     * name - `id`, `name`, `status` and `created_at` being the obvious ones.
     * Sorting by a relation and then searching produced:
     *
     *     SQLSTATE[HY000]: General error: 1 ambiguous column name: id
     *
     * Qualifying unconditionally is correct with or without a join, so there is
     * no need to detect one. See AUDIT.md BUG-39.
     */
    protected function qualifyColumn(string $column): string
    {
        // Already qualified, a JSON path, or an expression - leave it alone.
        if ($column === '' || str_contains($column, '.') || str_contains($column, '->')
            || str_contains($column, '(')) {
            return $column;
        }

        $table = SchemaCache::tableFor($this->model);

        return $table === null ? $column : $table.'.'.$column;
    }

    /**
     * Is this column one the host application actually declared as filterable?
     *
     * `filterColumn`, `filterInstances[*]['column']` and `dateColumn` are public
     * Livewire properties bound with wire:model and exposed in the query string,
     * so their values are attacker-controlled. Without this check a crafted
     * request can filter on ANY column of the underlying table — an oracle over
     * columns the table never displays. See AUDIT.md SEC-04.
     */
    protected function isAllowedFilterColumn($column): bool
    {
        if (! is_string($column) || $column === '') {
            return false;
        }

        // Declared as a filter, or at least declared as a column of this table.
        return array_key_exists($column, $this->filters ?? [])
            || array_key_exists($column, $this->columns ?? []);
    }

    // *----------- HELPERS THE DELEGATING METHODS ABOVE DEPEND ON -----------*//
    //
    // Each of these was called but never defined, so the corresponding public
    // action threw BadMethodCallException the moment a user triggered it.
    // See AUDIT.md BUG-09.

    /**
     * Constrain the query to a date range on a validated date column.
     *
     * Called from applyAllFilters() when dateColumn + startDate + endDate are set.
     */
    protected function applyDateRangeFilter(Builder $query, string $column, $startDate, $endDate): void
    {
        if (! $this->isAllowedFilterColumn($column) && ! $this->isValidColumn($column)) {
            Log::warning('AftableComponent: refusing date range on unknown column', ['column' => $column]);

            return;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } catch (\Throwable $e) {
            Log::warning('AftableComponent: unparseable date range', [
                'start' => $startDate, 'end' => $endDate, 'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $query->whereBetween($column, [$start, $end]);
    }

    /**
     * Reset every filter: the primary one, the extra instances and the date range.
     */
    public function clearFilters(): void
    {
        $this->filterColumn = null;
        $this->filterOperator = '=';
        $this->filterValue = null;
        $this->filterInstances = [];
        $this->dateColumn = null;
        $this->startDate = null;
        $this->endDate = null;

        $this->resetPage();
        $this->invalidateQueryCache();

        if ($this->enableSessionPersistence) {
            $this->saveFiltersToSession();
        }
    }

    /**
     * Reset the search box.
     */
    public function clearSearchInput(): void
    {
        $this->search = '';

        $this->resetPage();
        $this->invalidateQueryCache();

        if ($this->enableSessionPersistence) {
            session()->forget($this->getSessionKey('current_search'));
        }
    }

    /**
     * Snapshot of the component's state, for debugging and bug reports.
     */
    protected function getComponentDebugInfo(): array
    {
        return [
            'model' => is_object($this->model) ? get_class($this->model) : $this->model,
            'tableId' => $this->tableId,
            'mode' => $this->isStaticMode ? 'static' : 'eloquent',
            'columns' => array_keys($this->columns),
            'visibleColumns' => array_keys(array_filter($this->visibleColumns)),
            'eagerLoads' => $this->cachedRelations ?? [],
            'search' => $this->search,
            'sort' => ['column' => $this->sortColumn, 'direction' => $this->sortDirection],
            'filters' => [
                'column' => $this->filterColumn,
                'operator' => $this->filterOperator,
                'value' => $this->filterValue,
                'instances' => count($this->filterInstances ?? []),
                'dateRange' => array_filter([
                    'column' => $this->dateColumn,
                    'start' => $this->startDate,
                    'end' => $this->endDate,
                ]),
            ],
            'pagination' => ['perPage' => $this->getPerPageValue(), 'page' => $this->getPage()],
            'selection' => count($this->selectedRows ?? []),
            'flags' => [
                'searchable' => $this->searchable,
                'exportable' => $this->exportable,
                'printable' => $this->printable,
                'sessionPersistence' => $this->enableSessionPersistence,
                'queryStringSupport' => $this->enableQueryStringSupport,
            ],
        ];
    }

    /**
     * The query-string map Livewire should bind, honouring $enableQueryStringSupport.
     */
    protected function getQueryStringConfiguration(): array
    {
        return $this->queryString;
    }

    // *----------- SEARCH SUPPORT METHODS -----------*//

    /**
     * Get searchable columns from current column configuration
     * Required by search traits but not defined anywhere
     */
    protected function getSearchableColumns(): array
    {
        $searchableColumns = [];

        foreach ($this->columns as $columnKey => $column) {
            if (! $this->isColumnSearchable($columnKey)) {
                continue;
            }

            // JSON columns are searched through getSearchableJsonColumns(),
            // which builds the driver's JSON extraction expression. Including
            // them here too would search the same document twice.
            if (is_array($column) && ! empty($column['json'])) {
                continue;
            }

            // The array key is a UI identifier, NOT necessarily a database
            // column. Two columns reading `preferences` become `preferences`
            // and `preferences_language`, and the second one is not a column
            // that exists — searching it produced
            // `Unknown column 'test_employees.preferences_language'`, a 500 on
            // the most common interaction there is. Search the column the
            // config names, not the identifier the table renders under.
            // See AUDIT.md BUG-52.
            $searchableColumns[] = is_array($column) && ! empty($column['key'])
                ? $column['key']
                : $columnKey;
        }

        return array_values(array_unique($searchableColumns));
    }

    /**
     * Get searchable relations from column configuration
     * Extracts relation columns for relation-based searching
     * Required by HasUnifiedSearch trait
     */
    protected function getSearchableRelations(): array
    {
        $relationColumns = [];

        foreach ($this->columns as $columnKey => $column) {
            if (! is_array($column)) {
                continue;
            }

            if (! isset($column['relation'])) {
                continue;
            }

            // Check if this column is searchable
            if (isset($column['searchable']) && $column['searchable'] === false) {
                continue;
            }

            $parsed = RelationPath::tryParse((string) $column['relation']);

            if ($parsed === null) {
                continue;
            }

            $relationPath = $parsed->path;
            $attribute = $parsed->attribute;
            $relationParts = $parsed->segments;
            $firstRelation = $parsed->firstSegment();

            if (! isset($relationColumns[$firstRelation])) {
                $relationColumns[$firstRelation] = [];
            }

            // For simple relations, add the attribute
            if (count($relationParts) === 1) {
                $relationColumns[$firstRelation][] = $attribute;
            } else {
                // For nested relations like 'student.user:name'
                // We need to use nested where has
                // Store the full nested path
                if (! isset($relationColumns[$relationPath])) {
                    $relationColumns[$relationPath] = [];
                }
                $relationColumns[$relationPath][] = $attribute;
            }
        }

        return $relationColumns;
    }

    /**
     * Get searchable JSON columns from configuration
     * Extracts JSON columns for JSON-based searching
     * Required by HasUnifiedSearch trait
     */
    protected function getSearchableJsonColumns(): array
    {
        $jsonColumns = [];

        foreach ($this->columns as $columnKey => $column) {
            if (! is_array($column)) {
                continue;
            }

            if (! isset($column['json'])) {
                continue;
            }

            // Check if this column is searchable
            if (isset($column['searchable']) && $column['searchable'] === false) {
                continue;
            }

            $jsonPath = $column['json'];
            $actualColumn = $column['key'] ?? $columnKey;

            if (! isset($jsonColumns[$actualColumn])) {
                $jsonColumns[$actualColumn] = [];
            }

            $jsonColumns[$actualColumn][] = $jsonPath;
        }

        return $jsonColumns;
    }

    /**
     * Parse a relation string into components
     * Handles nested relations like 'student.user:name' => ['student', 'student.user'], 'name'
     * Required for proper relation handling in sorting, filtering, and searching
     */
    protected function parseRelationString(string $relationString): array
    {
        if (strpos($relationString, ':') === false) {
            // Invalid format, return empty
            return [];
        }

        [$relationPath, $attribute] = explode(':', $relationString, 2);

        // Build progressive paths for nested relations
        $relations = [];
        $parts = explode('.', $relationPath);
        $path = '';

        foreach ($parts as $part) {
            $path = $path ? "{$path}.{$part}" : $part;
            $relations[] = $path;
        }

        return [
            'relations' => $relations,
            'relationPath' => $relationPath,
            'attribute' => $attribute,
        ];
    }

    // *----------- TRAIT CONFLICT RESOLUTION -----------*//

    /**
     * Livewire lifecycle hook — fires when the selectAll header checkbox is toggled.
     * Selects or deselects all rows currently visible on the page.
     */
    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectAllOnPage();
        } else {
            $this->selectedRows = [];
        }
    }

    /**
     * Select all rows on the current page by collecting their IDs.
     */
    public function selectAllOnPage(): void
    {
        $data = $this->tableData ?? collect();
        $this->selectedRows = $data->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->selectAll = true;
    }

    /**
     * Clear bulk selection state.
     */
    public function clearBulkSelection(): void
    {
        $this->selectedRows = [];
        $this->selectAll = false;
    }

    /**
     * Clear action-level selection state (alias kept for backward compatibility).
     */
    public function clearActionSelection(): void
    {
        // Was the clearActionSelectedRows alias before the 1.6.0 consolidation.
        $this->clearSelectedRows();
    }

    /**
     * Return the number of bulk-selected rows.
     */
    public function getBulkSelectedCount(): int
    {
        return count($this->selectedRows ?? []);
    }

    /**
     * Return the number of action-selected rows (same set as bulk for this component).
     */
    public function getActionSelectedCount(): int
    {
        return count($this->selectedRows ?? []);
    }

    /**
     * Clear all selections.
     */
    public function clearSelection(): void
    {
        $this->clearBulkSelection();
        $this->clearActionSelection();
        $this->dispatch('selectionCleared');
    }

    /**
     * Return the total number of selected rows.
     */
    public function getSelectedCount(): int
    {
        return $this->getBulkSelectedCount();
    }

    /**
     * Check if a column is searchable (required by search traits).
     * In static mode we skip the DB schema check and rely solely on column config.
     */
    protected function isColumnSearchable($column): bool
    {
        if (! isset($this->columns[$column])) {
            return false;
        }

        $columnConfig = $this->columns[$column];

        if (is_string($columnConfig)) {
            return true;
        }

        if (! is_array($columnConfig)) {
            return false;
        }

        // Explicit opt-out
        if (isset($columnConfig['searchable']) && $columnConfig['searchable'] === false) {
            return false;
        }

        $hasKey = isset($columnConfig['key']) && $columnConfig['key'] !== '';
        $hasFunction = isset($columnConfig['function']) && $columnConfig['function'] !== '';
        $hasRaw = isset($columnConfig['raw']) && $columnConfig['raw'] !== '';

        // Computed-only columns (no DB key) are never searchable
        if (! $hasKey && ($hasFunction || $hasRaw)) {
            return false;
        }

        if ($hasRaw && ! $hasKey && ! $hasFunction) {
            return false;
        }

        // Static mode: trust the column config directly, no DB round-trip needed
        if ($this->isStaticMode) {
            return $columnConfig['searchable'] ?? ($hasKey && ! $hasFunction);
        }

        // Eloquent mode: verify the column actually exists in the table.
        //
        // This used to call Schema::getColumnListing() directly - once per
        // column, from several call sites, on EVERY request. A five-column
        // table issued 20 schema queries per search/sort/page. SchemaCache
        // memoises per process and caches across requests. See AUDIT.md BUG-37.
        if ($hasKey) {
            return SchemaCache::hasColumn($this->model, $columnConfig['key']);
        }

        return $columnConfig['searchable'] ?? true;
    }

    /**
     * Apply column optimization to query - Missing method implementation
     */
    protected function applyColumnOptimization(Builder $query): Builder
    {
        // Deliberately a no-op for the default ['*'] case.
        //
        // This used to run $query->select($this->cachedSelectColumns) where
        // cachedSelectColumns is hard-coded to ['*']. select() REPLACES the
        // column list, so it silently discarded the withCount() subselects
        // applyCountAggregations() had added one line earlier - which is why
        // every {relation}_count came back null. See AUDIT.md BUG-03.
        //
        // Narrowing the select list is still worth doing one day, but it must
        // use addSelect() and must not clobber aggregates.
        $columns = $this->cachedSelectColumns ?? [];

        if ($columns !== [] && $columns !== ['*']) {
            $query->addSelect($columns);
        }

        return $query;
    }

    /**
     * Apply optimized eager loading to query - Missing method implementation
     * PERFORMANCE FIX: Enhanced logging and verification
     */
    protected function applyOptimizedEagerLoading(Builder $query): Builder
    {
        $relations = $this->requiredRelations();

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query;
    }

    // *----------- ABSTRACT METHOD IMPLEMENTATIONS -----------*//

    /**
     * Implementation of abstract method from HasBladeRendering
     */
    protected function evaluateCondition($actualValue, string $operator, $checkValue): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actualValue == $checkValue;
            case '!=':
            case '<>':
                return $actualValue != $checkValue;
            case '>':
                return $actualValue > $checkValue;
            case '>=':
                return $actualValue >= $checkValue;
            case '<':
                return $actualValue < $checkValue;
            case '<=':
                return $actualValue <= $checkValue;
            case 'contains':
            case 'like':
                return str_contains(strtolower($actualValue), strtolower($checkValue));
            case 'starts_with':
                return str_starts_with(strtolower($actualValue), strtolower($checkValue));
            case 'ends_with':
                return str_ends_with(strtolower($actualValue), strtolower($checkValue));
            case 'in':
                return in_array($actualValue, is_array($checkValue) ? $checkValue : [$checkValue]);
            case 'not_in':
                return ! in_array($actualValue, is_array($checkValue) ? $checkValue : [$checkValue]);
            case 'empty':
                return empty($actualValue);
            case 'not_empty':
                return ! empty($actualValue);
            default:
                return false;
        }
    }

    /**
     * Implementation of abstract method from HasUserActions
     */
    protected function emit(string $event, ...$params): void
    {
        $this->dispatch($event, ...$params);
    }

    /**
     * Implementation of abstract method from HasValidation
     */
    protected function isValidFilterValue($value, string $type): bool
    {
        switch ($type) {
            case 'string':
            case 'text':
                return is_string($value) || is_numeric($value);
            case 'number':
            case 'integer':
                return is_numeric($value);
            case 'float':
            case 'decimal':
                return is_numeric($value);
            case 'boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);
            case 'date':
                return strtotime($value) !== false;
            case 'array':
                return is_array($value);
            case 'json':
                return is_string($value) && json_decode($value) !== null;
            default:
                return true;
        }
    }

    /**
     * Normalise a search term.
     *
     * Deliberately does NOT strip characters. Laravel binds LIKE values as query
     * parameters, so `where($col, 'LIKE', "%{$term}%")` is already injection-safe;
     * the old `preg_replace('/[^\w\s\-_@.]/i', ...)` bought nothing and broke
     * real searches — apostrophes ("O'Brien"), percent signs, "C++", "#tag" and,
     * worst of all, every non-Latin script: without the /u modifier `\w` is
     * ASCII-only, so an Arabic or Urdu term was stripped to the empty string and
     * the table silently returned every row. See AUDIT.md BUG-18.
     */
    protected function sanitizeSearch($search): string
    {
        if (! is_string($search)) {
            return '';
        }

        // strip_tags() is kept: a search term is never markup, and dropping tags
        // avoids storing something odd in the session/search history.
        $sanitized = trim(strip_tags($search));

        // Cap the length so a huge term cannot be used to hammer LIKE scans.
        return mb_substr($sanitized, 0, 255);
    }

    /**
     * Implementation of abstract method from HasBladeRendering
     */
    protected function getRowPropertyValue($row, string $property)
    {
        if (is_array($row)) {
            return $row[$property] ?? null;
        }

        if (is_object($row)) {
            // Handle Eloquent models
            if (method_exists($row, 'getAttribute')) {
                return $row->getAttribute($property);
            }

            // Handle stdClass or other objects
            return $row->{$property} ?? null;
        }

        return null;
    }

    /**
     * Implementation of abstract method from HasBladeRendering
     */
    protected function getNestedPropertyValue($row, string $path)
    {
        $keys = explode('.', $path);
        $value = $row;

        foreach ($keys as $key) {
            if (is_array($value)) {
                $value = $value[$key] ?? null;
            } elseif (is_object($value)) {
                if (method_exists($value, 'getAttribute')) {
                    $value = $value->getAttribute($key);
                } else {
                    $value = $value->{$key} ?? null;
                }
            } else {
                return null;
            }

            if ($value === null) {
                break;
            }
        }

        return $value;
    }

    /**
     * Render a raw HTML / template string (or callable) safely.
     *
     * Supported raw column values:
     *
     * 1. Blade string (Blade syntax — recommended):
     *      'raw' => '<span class="badge">{{ $row->status }}</span>'
     *      'raw' => '<span class="badge">{!! $row->status !!}</span>'
     *      'raw' => '{{ \Carbon\Carbon::parse($row->date)->format("d M Y") }}'
     *      'raw' => '{{ number_format($row->price * $row->qty, 2) }}'
     *
     * NOT supported: a Closure. $columns is a public Livewire property, so its
     * contents must survive JSON serialisation into the component snapshot, and
     * a Closure cannot — Livewire rejects it with "Property type not supported".
     * The callable branch below is reachable only from internal callers that
     * never round-trip through a snapshot. See AUDIT.md BUG-07.
     *
     * Available Blade variables:
     *  - $row       — current row (stdClass in static mode, Eloquent model in DB mode)
     *  - $vars      — the component-level extraVars array
     *  - any key from $this->extraVars spread as a top-level variable
     *      e.g. pass ['currency' => 'USD'] → use {{ $currency }} in raw
     *
     * PHP calculation example:
     *      'raw' => '{{ number_format($row->price * $row->quantity, 2) }}'
     *
     * SECURITY: this method compiles and evaluates its first argument as a Blade
     * template. It MUST stay protected — Livewire exposes every public method as
     * an action a visitor can invoke with arbitrary arguments, which would make
     * this a remote code execution primitive. See AUDIT.md SEC-01.
     *
     * NOTE: Compiles via compileString() then evaluates via a temp file include.
     * PHP 8 does not support <?php re-entry tags inside eval() strings, so we
     * write to a temp .php file (cached by template hash) and include it instead.
     * Livewire 4's ExtendedCompilerEngine binds templates to the component scope,
     * which would break output buffering for nested Blade::render() calls — this
     * approach is fully isolated from Livewire's rendering engine.
     */
    protected function renderRawHtml($rawTemplate, $row): string
    {
        // --- Callable path (internal callers only; see the note above) ---
        if (is_callable($rawTemplate)) {
            try {
                return (string) $rawTemplate($row, $this->extraVars);
            } catch (\Throwable $e) {
                Log::warning('AftableComponent renderRawHtml callable error: '.$e->getMessage(), [
                    'row_id' => is_object($row) ? ($row->id ?? 'unknown') : 'array-row',
                ]);

                return '';
            }
        }

        // --- Blade string path ---
        if (! is_string($rawTemplate) || $rawTemplate === '') {
            return '';
        }

        // NOTE: When raw templates are defined inside a @livewire() call in a Blade
        // file, Laravel's Blade compiler processes {{ }} before passing the string to
        // the component. So $rawTemplate may already contain compiled PHP code with
        // opening php tags. We must NOT strip those tags — doing so
        // produces bare "echo e(...);" code which PHP's file include treats as
        // HTML text and outputs literally.

        // Merge component-level extraVars so templates can access keys directly
        $data = array_merge(['row' => $row, 'vars' => $this->extraVars], $this->extraVars);

        try {
            // Resolve (or create) a temp PHP file containing the compiled Blade.
            // We cache by md5 hash so each unique template is only compiled and
            // written to disk once per process lifetime.
            $path = $this->resolveCompiledTemplatePath($rawTemplate);

            // Include the file in an isolated static closure so $this is never
            // bound to the Livewire component, preventing scope leakage.
            $evaluate = static function (string $__path, array $__data): string {
                extract($__data, EXTR_SKIP);
                ob_start();
                try {
                    include $__path;
                } catch (\Throwable $e) {
                    ob_end_clean();
                    throw $e;
                }

                return (string) ob_get_clean();
            };

            return $evaluate($path, $data);
        } catch (\Throwable $e) {
            Log::warning('AftableComponent renderRawHtml error: '.$e->getMessage(), [
                'template_preview' => substr($rawTemplate, 0, 120),
                'row_id' => is_object($row) ? ($row->id ?? 'unknown') : 'array-row',
            ]);

            // Fallback: show template preview with error hint as tooltip
            return '<span class="text-danger" title="'.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'">'.
                   htmlspecialchars(substr($rawTemplate, 0, 80), ENT_QUOTES, 'UTF-8').
                   '</span>';
        }
    }

    /**
     * Compile a raw Blade template string and cache it as a temp PHP file.
     * Returns the absolute path to the ready-to-include PHP file.
     * Files are re-used across rows (same template string → same file).
     */
    private function resolveCompiledTemplatePath(string $template): string
    {
        $hash = md5($template);

        // Only trust the memo while the file is still there. Clearing the cache
        // (af-table:clear-phantom-columns, a deploy wiping storage, an OS temp
        // sweep) removes these files out from under a warm process; returning a
        // stale path then makes every raw column render an include() error.
        if (isset(self::$rawTemplateCache[$hash]) && is_file(self::$rawTemplateCache[$hash])) {
            return self::$rawTemplateCache[$hash];
        }

        // If the template is already compiled PHP (Blade pre-compiled {{ }} in @livewire args),
        // use it as-is. Otherwise compile Blade syntax to PHP first.
        $compiled = str_contains($template, '<?php') || str_contains($template, '<?=')
            ? $template
            : app('blade.compiler')->compileString($template);

        $path = static::rawTemplateCachePath().DIRECTORY_SEPARATOR.'aftable_tpl_'.$hash.'.php';

        if (! is_file($path)) {
            // Write to a unique temp name in the SAME directory, then rename.
            // rename() is atomic on POSIX and on NTFS, so a concurrent request
            // can only ever include a complete file — file_put_contents(LOCK_EX)
            // does not give that guarantee to readers.
            $tmp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';

            if (file_put_contents($tmp, $compiled) === false) {
                throw new \RuntimeException("Unable to write compiled raw template to [{$tmp}].");
            }

            if (! @rename($tmp, $path)) {
                // Another request won the race and already published the file.
                @unlink($tmp);
            }
        }

        return self::$rawTemplateCache[$hash] = $path;
    }

    /**
     * Directory holding compiled `raw` column templates.
     *
     * Deliberately NOT sys_get_temp_dir(): that directory is shared and
     * world-writable on most hosts, and the filenames here are a predictable
     * md5() of the template text — so a local attacker could pre-create the
     * exact .php file this component is about to include. Keeping the cache
     * under storage/ puts it inside the application's own trust boundary,
     * alongside Blade's compiled views. See AUDIT.md SEC-02.
     */
    protected static function rawTemplateCachePath(): string
    {
        $path = function_exists('storage_path')
            ? storage_path('framework/cache/aftable')
            : sys_get_temp_dir().DIRECTORY_SEPARATOR.'aftable';

        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    /** @var array<string, string> Compiled template path cache (hash → file path). */
    private static array $rawTemplateCache = [];
}
