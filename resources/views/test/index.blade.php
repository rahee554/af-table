@extends('artflow-table::test.layout')

@section('title', 'AFTable test bench')

{{--
    AFTable test bench.

    One page, one scroll, every feature the package advertises rendered against
    real data — so a regression is visible rather than theoretical. Each section
    lists the specific behaviours it exercises, and the strip at the top reports
    what the request actually cost.

    Every table below is mounted through the PUBLIC api (@livewire / @AFtable /
    @aftable), never through an internal helper, so this page breaks in exactly
    the way a host application would.
--}}

@php
    use ArtflowStudio\Table\Support\TableCache;
    use ArtflowStudio\Table\Tests\Models\TestCompany;
    use ArtflowStudio\Table\Tests\Models\TestDepartment;
    use ArtflowStudio\Table\Tests\Models\TestEmployee;
    use ArtflowStudio\Table\Tests\Models\TestProject;
    use ArtflowStudio\Table\Tests\Models\TestTask;
    use ArtflowStudio\Table\Tests\Models\TestTimesheet;

    $benchStart = microtime(true);

    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $schemaReady = \Illuminate\Support\Facades\Schema::hasTable('test_employees');

    $counts = $schemaReady ? [
        'employees'  => TestEmployee::count(),
        'projects'   => TestProject::count(),
        'tasks'      => TestTask::count(),
        'timesheets' => TestTimesheet::count(),
    ] : ['employees' => 0, 'projects' => 0, 'tasks' => 0, 'timesheets' => 0];

    $cache = TableCache::describe();
    $guardOn = (bool) config('aftable.security.guard_actions', true);
    $allowedActions = count(\ArtflowStudio\Table\Support\GuardsActions::allowed());
    $publicMethodCount = count((new ReflectionClass(
        \ArtflowStudio\Table\Http\Livewire\AftableComponent::class
    ))->getMethods(ReflectionMethod::IS_PUBLIC));

    // In-memory rows for the static / foreach mode section.
    $staticRows = collect([
        ['id' => 1, 'sku' => 'WID-001', 'name' => 'Widget',      'price' => 10.00, 'qty' => 3,   'category' => 'Tools'],
        ['id' => 2, 'sku' => 'GAD-002', 'name' => 'Gadget',      'price' => 25.50, 'qty' => 1,   'category' => 'Tools'],
        ['id' => 3, 'sku' => 'DOO-003', 'name' => 'Doohickey',   'price' =>  5.25, 'qty' => 10,  'category' => 'Parts'],
        ['id' => 4, 'sku' => 'THI-004', 'name' => 'Thingamajig', 'price' => 99.99, 'qty' => 2,   'category' => 'Parts'],
        ['id' => 5, 'sku' => 'WHA-005', 'name' => 'Whatsit',     'price' =>  1.00, 'qty' => 100, 'category' => 'Misc'],
        ['id' => 6, 'sku' => 'GIZ-006', 'name' => 'Gizmo',       'price' => 42.00, 'qty' => 7,   'category' => 'Misc'],
    ]);
@endphp

@section('content')

@unless ($schemaReady)
    <div class="alert alert-warning">
        <strong>Demo tables are missing.</strong>
        Run <code>php artisan af-table:migrate --seed --scale=0.05</code> to create and populate them.
        Full scale (<code>--scale=1</code>) inserts ~118,600 rows; 0.05 gives a few thousand, which is
        plenty for everything on this page except the large-dataset section.
    </div>
@endunless

{{-- ── diagnostics ──────────────────────────────────────────────────── --}}
<dl class="tb-diag">
    <div>
        <dt>Rows available</dt>
        <dd>{{ number_format(array_sum($counts)) }}</dd>
    </div>
    <div>
        <dt>Cache store</dt>
        <dd class="{{ $cache['redis'] ? 'is-ok' : '' }}">
            {{ $cache['store'] }}
            <small>{{ $cache['redis'] ? 'redis, auto-detected' : 'redis not available' }}</small>
        </dd>
    </div>
    <div>
        <dt>Cache tags</dt>
        <dd class="{{ $cache['tags'] ? 'is-ok' : 'is-warn' }}">
            {{ $cache['tags'] ? 'supported' : 'unsupported' }}
            <small>{{ $cache['tags'] ? 'precise invalidation' : 'broad invalidation only' }}</small>
        </dd>
    </div>
    <div>
        <dt>Queries</dt>
        <dd id="diag-queries">&hellip;</dd>
    </div>
    <div>
        <dt>Render</dt>
        <dd id="diag-time">&hellip;</dd>
    </div>
    <div>
        <dt>Peak memory</dt>
        <dd id="diag-memory">&hellip;</dd>
    </div>
</dl>

{{-- ── 1. core ──────────────────────────────────────────────────────── --}}
<section class="tb-section" id="core">
    <header>
        <h2>1 &middot; Core <span class="tb-count">{{ number_format($counts['employees']) }} employees</span></h2>
        <p class="tb-lede">
            The everyday path: plain columns, global search, per-column sorting, pagination,
            column visibility and CSV export. Everything here should feel instant.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">search (min 3 chars)</li>
        <li class="tb-check">sort asc/desc</li>
        <li class="tb-check">paginate</li>
        <li class="tb-check">page-size</li>
        <li class="tb-check">column visibility</li>
        <li class="tb-check">row index</li>
        <li class="tb-check">checkbox select</li>
        <li class="tb-check">csv export</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'      => TestEmployee::class,
            'tableId'    => 'bench-core',
            'index'      => true,
            'checkbox'   => true,
            'exportable' => true,
            'refreshBtn' => true,
            'columns' => [
                ['key' => 'employee_code', 'label' => 'Code'],
                ['key' => 'first_name',    'label' => 'First name'],
                ['key' => 'last_name',     'label' => 'Last name'],
                ['key' => 'email',         'label' => 'Email'],
                ['key' => 'position',      'label' => 'Position'],
                ['key' => 'salary',        'label' => 'Salary'],
                ['key' => 'hire_date',     'label' => 'Hired'],
            ],
            'filters' => [
                'position'        => ['type' => 'distinct'],
                'employment_type' => ['type' => 'distinct'],
                'salary'          => ['type' => 'number'],
                'last_name'       => ['type' => 'text'],
            ],
            'sortBy'        => 'employee_code',
            'sortDirection' => 'asc',
        ], key('bench-core'))
    </div>
</section>

{{-- ── 2. relations ─────────────────────────────────────────────────── --}}
<section class="tb-section" id="relations">
    <header>
        <h2>2 &middot; Relations &amp; aggregates</h2>
        <p class="tb-lede">
            Single-level and nested relation columns, plus <code>countAggregations</code>. Watch the
            query count in the strip above: relations are eager-loaded, so adding rows here must not
            add queries. The count column previously always rendered blank —
            <code>withCount()</code> was being wiped by a later <code>select()</code>.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">relation:column</li>
        <li class="tb-check">a.b:column (nested)</li>
        <li class="tb-check">relation sorting (join)</li>
        <li class="tb-check">withCount</li>
        <li class="tb-check">no N+1</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'   => TestEmployee::class,
            'tableId' => 'bench-relations',
            'columns' => [
                ['key' => 'first_name',      'label' => 'Employee'],
                ['key' => 'department_name', 'label' => 'Department', 'relation' => 'department:name'],
                ['key' => 'department_code', 'label' => 'Dept code',  'relation' => 'department:code'],
                ['key' => 'company_name',    'label' => 'Company',    'relation' => 'department.company:name'],
                ['key' => 'company_country', 'label' => 'Country',    'relation' => 'department.company:country'],
            ],
            'filters' => [
                'department_name' => ['type' => 'text'],
            ],
        ], key('bench-relations'))
    </div>

    <div class="tb-panel">
        <p class="tb-lede mb-2"><code>countAggregations</code> — employees per department, one query.</p>
        @livewire('aftable', [
            'model'   => TestDepartment::class,
            'tableId' => 'bench-counts',
            'columns' => [
                ['key' => 'code',            'label' => 'Code'],
                ['key' => 'name',            'label' => 'Department'],
                ['key' => 'company_name',    'label' => 'Company', 'relation' => 'company:name'],
                ['key' => 'employees_count', 'label' => 'Employees', 'sortable' => false],
                ['key' => 'budget',          'label' => 'Budget'],
            ],
            'countAggregations' => ['employees' => null],
        ], key('bench-counts'))
    </div>
</section>

{{-- ── 3. computed columns ──────────────────────────────────────────── --}}
<section class="tb-section" id="computed">
    <header>
        <h2>3 &middot; JSON, raw &amp; function columns</h2>
        <p class="tb-lede">
            JSON-path extraction, raw Blade with live HTML and conditional logic, and a column
            resolved by calling a method on the model. JSON columns used to fatal the entire table.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">json path</li>
        <li class="tb-check">nested json path</li>
        <li class="tb-check">raw html</li>
        <li class="tb-check">raw conditional</li>
        <li class="tb-check">raw arithmetic</li>
        <li class="tb-check">function column</li>
        <li class="tb-check">extraVars</li>
        <li class="tb-check">xss-escaped</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'   => TestEmployee::class,
            'tableId' => 'bench-computed',
            'vars'    => ['currency' => 'PKR', 'taxRate' => 0.15],
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],

                ['key' => 'full_name', 'function' => 'getFullNameAttribute', 'label' => 'Full name'],

                ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
                ['key' => 'preferences', 'json' => 'language', 'label' => 'Locale'],

                ['key' => 'status', 'label' => 'Status', 'raw' =>
                    '<span class="badge bg-@{{ $row->status === "active" ? "success" : ($row->status === "on_leave" ? "warning text-dark" : "secondary") }}">'
                    .'@{{ ucfirst(str_replace("_", " ", $row->status)) }}</span>'],

                ['key' => 'salary', 'label' => 'Salary', 'raw' =>
                    '<span class="font-monospace">@{{ $currency }} @{{ number_format($row->salary, 0) }}</span>'],

                ['key' => 'salary_net', 'label' => 'Net (after tax)', 'sortable' => false, 'raw' =>
                    '<span class="font-monospace text-muted">@{{ number_format($row->salary * (1 - $taxRate), 0) }}</span>'],
            ],
        ], key('bench-computed'))
    </div>
</section>

{{-- ── 4. filters ───────────────────────────────────────────────────── --}}
<section class="tb-section" id="filters">
    <header>
        <h2>4 &middot; Filtering</h2>
        <p class="tb-lede">
            Every filter type at once, plus stacked filter instances (the <strong>+</strong> button).
            Filter columns are validated against this list — a hand-edited query string cannot make
            the table filter on a column that was never declared.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">distinct</li>
        <li class="tb-check">text (3-char floor)</li>
        <li class="tb-check">number + operators</li>
        <li class="tb-check">date</li>
        <li class="tb-check">stacked instances</li>
        <li class="tb-check">column allow-list</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'   => TestTask::class,
            'tableId' => 'bench-filters',
            'columns' => [
                ['key' => 'title',       'label' => 'Task'],
                ['key' => 'status',      'label' => 'Status'],
                ['key' => 'priority',    'label' => 'Priority'],
                ['key' => 'due_date',    'label' => 'Due'],
                ['key' => 'project_name', 'label' => 'Project', 'relation' => 'project:name'],
            ],
            'filters' => [
                'status'   => ['type' => 'distinct'],
                'priority' => ['type' => 'distinct'],
                'title'    => ['type' => 'text'],
                'due_date' => ['type' => 'date'],
            ],
        ], key('bench-filters'))
    </div>
</section>

{{-- ── 5. static mode ───────────────────────────────────────────────── --}}
<section class="tb-section" id="static">
    <header>
        <h2>5 &middot; Static / foreach mode <span class="tb-count">no database</span></h2>
        <p class="tb-lede">
            Fed a plain Collection instead of a model. Search, sort, filter and pagination all run
            in memory — this section must add <strong>zero</strong> queries.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">array/collection source</li>
        <li class="tb-check">in-memory search</li>
        <li class="tb-check">in-memory sort</li>
        <li class="tb-check">in-memory filter</li>
        <li class="tb-check">pagination</li>
        <li class="tb-check">computed raw</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'data'    => $staticRows,
            'tableId' => 'bench-static',
            'records' => 3,
            'columns' => [
                ['key' => 'sku',      'label' => 'SKU'],
                ['key' => 'name',     'label' => 'Product'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'price',    'label' => 'Unit price'],
                ['key' => 'qty',      'label' => 'Qty'],
                ['key' => 'total', 'label' => 'Line total', 'sortable' => false, 'raw' =>
                    '<strong class="font-monospace">@{{ number_format($row->price * $row->qty, 2) }}</strong>'],
            ],
            'filters' => [
                'category' => ['type' => 'distinct'],
            ],
        ], key('bench-static'))
    </div>
</section>

{{-- ── 6. directives ────────────────────────────────────────────────── --}}
<section class="tb-section" id="directives">
    <header>
        <h2>6 &middot; Blade directives</h2>
        <p class="tb-lede">
            All four entry points, rendered for real. Until recently every one of these except
            <code>&#64;livewire</code> fataled — <code>Livewire::mount()</code> returns a string in
            Livewire 4 and the directives were still calling <code>-&gt;html()</code> on it. If this
            section renders, the directives work.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">&#64;AFtable</li>
        <li class="tb-check">&#64;AFtableTrait</li>
        <li class="tb-check">&#64;aftable &hellip; &#64;endaftable</li>
        <li class="tb-check">custom table shell</li>
        <li class="tb-check">raw html through directives</li>
    </ul>

    <div class="tb-panel">
        <p class="tb-lede mb-2"><code>&#64;AFtable([...])</code> — shorthand, default markup.</p>

        @AFtable([
            'model'   => \ArtflowStudio\Table\Tests\Models\TestEmployee::class,
            'tableId' => 'bench-directive-short',
            'records' => 5,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'position',   'label' => 'Position'],
                ['key' => 'employment_type', 'label' => 'Type', 'raw' =>
                    '<span class="badge bg-primary text-uppercase">@{{ $row->employment_type }}</span>'],
            ],
        ])
    </div>

    <div class="tb-panel">
        <p class="tb-lede mb-2">
            <code>&#64;aftable([...]) &hellip; &#64;endaftable</code> — the body becomes the outer
            <code>&lt;table&gt;</code>, so the host app controls the classes.
        </p>

        @aftable([
            'model'   => \ArtflowStudio\Table\Tests\Models\TestDepartment::class,
            'tableId' => 'bench-directive-paired',
            'records' => 5,
            'columns' => [
                ['key' => 'code',   'label' => 'Code'],
                ['key' => 'name',   'label' => 'Department'],
                ['key' => 'budget', 'label' => 'Budget', 'raw' =>
                    '<span class="font-monospace">@{{ number_format($row->budget ?? 0) }}</span>'],
            ],
        ])
            <table class="table table-sm table-striped align-middle bench-custom-shell"></table>
        @endaftable
    </div>
</section>

{{-- ── 7. actions & print ───────────────────────────────────────────── --}}
<section class="tb-section" id="actions">
    <header>
        <h2>7 &middot; Row actions, bulk select &amp; print</h2>
        <p class="tb-lede">
            Link actions, raw-HTML actions, checkbox selection, and the isolated print window
            (landscape A4 with a custom header). Print opens a pop-up containing only the table.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">GET link action</li>
        <li class="tb-check">raw html action</li>
        <li class="tb-check">bulk checkbox</li>
        <li class="tb-check">printConfig</li>
        <li class="tb-check">export menu (gated by installed packages)</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'      => TestCompany::class,
            'tableId'    => 'bench-actions',
            'checkbox'   => true,
            'printable'  => true,
            'exportable' => true,
            'printConfig' => [
                'title'       => 'Company register',
                'header'      => 'AFTable test bench &mdash; isolated print output',
                'footer'      => 'Generated from the demo dataset',
                'orientation' => 'landscape',
                'paperSize'   => 'A4',
                'fontSize'    => 10,
                'showDate'    => true,
                'showPageNum' => true,
            ],
            'columns' => [
                ['key' => 'name',     'label' => 'Company'],
                ['key' => 'country',  'label' => 'Country'],
                ['key' => 'city',     'label' => 'City'],
                ['key' => 'metadata', 'json' => 'tier', 'label' => 'Tier'],
                ['key' => 'revenue',  'label' => 'Revenue'],
            ],
            'filters' => [
                'country' => ['type' => 'distinct'],
                'status'  => ['type' => 'distinct'],
            ],
            'actions' => [
                'view' => [
                    'label'  => 'Open',
                    'route'  => 'aftable.test',
                    'params' => [],
                    'class'  => 'btn btn-sm btn-outline-secondary',
                ],
                '<span class="badge bg-light text-dark border">raw action</span>',
            ],
        ], key('bench-actions'))
    </div>
</section>

{{-- ── 8. scale ─────────────────────────────────────────────────────── --}}
<section class="tb-section" id="scale">
    <header>
        <h2>8 &middot; Scale <span class="tb-count">{{ number_format($counts['timesheets']) }} timesheets</span></h2>
        <p class="tb-lede">
            The largest table in the demo schema, with a relation column and a distinct filter.
            Paging, sorting and searching here should stay flat — the query is always
            <code>LIMIT</code>ed and distinct-filter values are cached and capped.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">large offset paging</li>
        <li class="tb-check">sort on indexed column</li>
        <li class="tb-check">cached distinct values</li>
        <li class="tb-check">capped dropdown</li>
        <li class="tb-check">deferred rows</li>
    </ul>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'   => TestTimesheet::class,
            'tableId' => 'bench-scale',
            'records' => 25,
            'columns' => [
                ['key' => 'work_date',     'label' => 'Date'],
                ['key' => 'employee_name', 'label' => 'Employee', 'relation' => 'employee:first_name'],
                ['key' => 'hours',         'label' => 'Hours'],
                ['key' => 'status',        'label' => 'Status'],
                ['key' => 'description',   'label' => 'Description'],
            ],
            'filters' => [
                'status' => ['type' => 'distinct'],
                'hours'  => ['type' => 'number'],
            ],
            'sortBy'        => 'work_date',
            'sortDirection' => 'desc',
        ], key('bench-scale'))
    </div>

    <h3 class="tb-sub">Deferred rows &mdash; <code>deferData =&gt; true</code></h3>
    <p class="tb-lede">
        The same table, split across two round trips. The toolbar and shell arrive with the page;
        the rows follow from a lazy island, with the skeleton component standing in.
        Reload and watch the striped placeholder appear first &mdash; on the first request the
        query does not run at all.
    </p>

    <div class="tb-panel">
        @livewire('aftable', [
            'model'     => TestTimesheet::class,
            'tableId'   => 'bench-defer',
            'records'   => 10,
            'deferData' => true,
            'columns'   => [
                ['key' => 'work_date',     'label' => 'Date'],
                ['key' => 'employee_name', 'label' => 'Employee', 'relation' => 'employee:first_name'],
                ['key' => 'hours',         'label' => 'Hours'],
                ['key' => 'status',        'label' => 'Status'],
            ],
            'filters' => [
                'status' => ['type' => 'distinct'],
            ],
            'sortBy'        => 'work_date',
            'sortDirection' => 'desc',
        ], key('bench-defer'))
    </div>
</section>

{{-- ── 9. edge cases ────────────────────────────────────────────────── --}}
<section class="tb-section" id="edges">
    <header>
        <h2>9 &middot; Edge cases</h2>
        <p class="tb-lede">
            The states that usually go untested: an empty result set, a table with every optional
            control switched off, and a query-constrained table whose base filter must survive
            search, sort and export.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">empty state</li>
        <li class="tb-check">no toolbar</li>
        <li class="tb-check">base query constraint</li>
        <li class="tb-check">hidden column</li>
        <li class="tb-check">assets=false</li>
    </ul>

    <div class="tb-panel">
        <p class="tb-lede mb-2">Empty result set — should render the empty-state row, not a blank table.</p>
        @livewire('aftable', [
            'model'   => TestEmployee::class,
            'tableId' => 'bench-empty',
            'query'   => [['first_name', 'no-such-employee-exists']],
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'email',      'label' => 'Email'],
            ],
        ], key('bench-empty'))
    </div>

    <div class="tb-panel">
        <p class="tb-lede mb-2">
            Chrome off (<code>searchable</code>, <code>colvisBtn</code> false), a hidden column,
            and a base <code>query</code> constraint pinned to full-time staff.
        </p>
        @livewire('aftable', [
            'model'      => TestEmployee::class,
            'tableId'    => 'bench-bare',
            'searchable' => false,
            'colvisBtn'  => false,
            'records'    => 5,
            'query'      => ['employment_type' => 'full-time'],
            'columns' => [
                ['key' => 'first_name',      'label' => 'Name'],
                ['key' => 'employment_type', 'label' => 'Type'],
                ['key' => 'email',           'label' => 'Email', 'hide' => true],
            ],
        ], key('bench-bare'))
    </div>
</section>

{{-- ── 10. theming ──────────────────────────────────────────────────── --}}
<section class="tb-section" id="theming">
    <header>
        <h2>10 &middot; Theming <span class="tb-count">{{ implode(' / ', \ArtflowStudio\Table\Support\Theme::available()) }}</span></h2>
        <p class="tb-lede">
            The package used to hard-code Bootstrap classes into its markup, so a Tailwind app — or
            any app with its own design system — had no way out short of republishing the view.
            Every part of the table is now a named <em>slot</em>, and slots resolve through a theme.
            See <code>docs/THEMING.md</code>.
        </p>
    </header>

    <ul class="tb-checks">
        <li class="tb-check">preset: bootstrap</li>
        <li class="tb-check">preset: none</li>
        <li class="tb-check">per-slot override</li>
        <li class="tb-check">app-wide default</li>
        <li class="tb-check">custom preset</li>
    </ul>

    <div class="tb-panel">
        <p class="tb-lede mb-2">
            <code>'classes' =&gt; [...]</code> — override only the slots you care about. Here the
            table, header cells and rows are restyled; everything else keeps the preset.
        </p>

        @livewire('aftable', [
            'model'   => TestEmployee::class,
            'tableId' => 'bench-theme-custom',
            'records' => 5,
            'classes' => [
                'table' => 'table table-sm align-middle bench-striped',
                'th'    => 'bench-th',
                'tr'    => 'bench-tr',
                'responsive' => 'bench-scroll',
            ],
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'position',   'label' => 'Position'],
                ['key' => 'salary',     'label' => 'Salary'],
            ],
        ], key('bench-theme-custom'))
    </div>

    <div class="tb-panel">
        <p class="tb-lede mb-2">
            <code>'theme' =&gt; 'none'</code> — the package emits <strong>no</strong> opinionated
            classes at all, only structural <code>aft-*</code> hooks for your own stylesheet.
            The CSS below this table is the entire styling.
        </p>

        @livewire('aftable', [
            'model'   => TestEmployee::class,
            'tableId' => 'bench-theme-none',
            'theme'   => 'none',
            'records' => 5,
            'searchable' => false,
            'colvisBtn'  => false,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'position',   'label' => 'Position'],
                ['key' => 'salary',     'label' => 'Salary'],
            ],
        ], key('bench-theme-none'))

        <pre class="mt-3"><code>{{ '.aft-table { width:100%; border-collapse:collapse }
.aft-th    { text-align:left; font-weight:600; padding:.5rem }
.aft-td    { padding:.5rem; border-top:1px solid var(--line) }' }}</code></pre>
    </div>
</section>

{{-- ── 11. security ─────────────────────────────────────────────────── --}}
<section class="tb-section" id="security">
    <header>
        <h2>11 &middot; Security posture</h2>
        <p class="tb-lede">
            Livewire exposes every public method as an action a visitor can invoke with arbitrary
            arguments — this component has {{ $publicMethodCount }} of them. The action guard closes
            that to a declared list at the one point every client call passes through.
        </p>
    </header>

    <div class="tb-panel">
        <table class="table table-sm">
            <thead>
                <tr><th>Control</th><th>State</th><th>Effect</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Action guard</td>
                    <td>{!! $guardOn
                        ? '<span class="is-ok">on</span>'
                        : '<span class="is-bad">off</span>' !!}</td>
                    <td>{{ $allowedActions }} of {{ $publicMethodCount }} public methods are client-callable</td>
                </tr>
                <tr>
                    <td>Raw template execution</td>
                    <td><span class="is-ok">protected</span></td>
                    <td><code>renderRawHtml()</code> cannot be invoked from the browser</td>
                </tr>
                <tr>
                    <td>Compiled template cache</td>
                    <td><span class="is-ok">storage/</span></td>
                    <td>written atomically inside the app, not the shared OS temp dir</td>
                </tr>
                <tr>
                    <td>Filter column allow-list</td>
                    <td><span class="is-ok">enforced</span></td>
                    <td>a crafted query string cannot filter on an undeclared column</td>
                </tr>
                <tr>
                    <td>Column qualification</td>
                    <td><span class="is-ok">enforced</span></td>
                    <td>joins cannot produce ambiguous-column errors or leak across tables</td>
                </tr>
                <tr>
                    <td>Demo routes</td>
                    <td><span class="is-ok">non-production only</span></td>
                    <td>this page never registers when <code>APP_ENV=production</code></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

@php
    $benchQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
    $benchMs = round((microtime(true) - $benchStart) * 1000);
    $benchMem = round(memory_get_peak_usage(true) / 1048576);
@endphp

@push('scripts')
<script>
    // Fill the diagnostics strip after render, so the numbers describe the whole
    // page rather than the point at which the strip happened to be emitted.
    (() => {
        const set = (id, value, cls) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = value;
            if (cls) el.classList.add(cls);
        };

        const queries = {{ $benchQueries }};
        const ms = {{ $benchMs }};

        set('diag-queries', queries, queries > 120 ? 'is-warn' : 'is-ok');
        set('diag-time', ms + ' ms', ms > 3000 ? 'is-warn' : 'is-ok');
        set('diag-memory', '{{ $benchMem }} MB');
    })();
</script>
@endpush

@endsection
