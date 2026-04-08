@extends('artflow-table::test.layout')

@section('withoutLivewireAssets', '1')

@push('styles')
<style>
    /* -- Layout -------------------------------------------- */
    .docs-sidebar-shell {
        height: 100%;
    }
    .docs-main-col {
        min-width: 0;
    }
    .docs-sidebar {
        height: 100%;
        overflow-y: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 16px rgba(0,0,0,.07);
        padding: 1.5rem 1.25rem;
    }
    .docs-sidebar::-webkit-scrollbar { width: 4px; }
    .docs-sidebar::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 4px; }
    .docs-sidebar .toc-group-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #adb5bd;
        padding: .75rem 0 .25rem;
    }
    .docs-sidebar ul { list-style: none; padding: 0; margin: 0; }
    .docs-sidebar li a {
        display: block;
        padding: .3rem .5rem;
        border-radius: 6px;
        font-size: .875rem;
        color: #495057;
        text-decoration: none;
        transition: background .15s, color .15s;
    }
    .docs-sidebar li a:hover,
    .docs-sidebar li a.active { background: #f0f3ff; color: #667eea; }

    /* -- Cards --------------------------------------------- */
    .doc-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 16px rgba(0,0,0,.07);
        padding: 2rem 2.25rem;
        margin-bottom: 2rem;
        scroll-margin-top: 80px;
    }
    .doc-card h2 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #343a40;
        border-bottom: 2px solid #f0f3ff;
        padding-bottom: .6rem;
        margin-bottom: 1rem;
    }
    .doc-card h2 .emoji { margin-right: .4rem; }
    .doc-card h5 { font-weight: 600; color: #495057; margin-top: 1.5rem; margin-bottom: .5rem; }
    .doc-card p { color: #6c757d; }

    /* -- Code blocks --------------------------------------- */
    pre.code-block {
        background: #1e1e2e;
        color: #cdd6f4;
        border-radius: 10px;
        padding: 1.25rem 1.5rem;
        font-size: .84rem;
        overflow-x: auto;
        white-space: pre;
        margin: .75rem 0;
        position: relative;
    }
    pre.code-block .copy-btn {
        position: absolute;
        top: .6rem;
        right: .75rem;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.2);
        color: #cdd6f4;
        padding: .2rem .55rem;
        border-radius: 6px;
        font-size: .75rem;
        cursor: pointer;
        transition: background .15s;
    }
    pre.code-block .copy-btn:hover { background: rgba(255,255,255,.22); }

    /* -- Tables -------------------------------------------- */
    .doc-table { font-size: .875rem; }
    .doc-table thead th { background: #f8f9fa; font-weight: 600; }
    .doc-table td code, .doc-table th code {
        background: #f0f3ff;
        color: #667eea;
        padding: .1em .4em;
        border-radius: 4px;
        font-size: .82rem;
    }

    /* -- Badges ------------------------------------------- */
    .badge-prop { background: #e7f3ff; color: #0066cc; font-size: .75rem; padding: .25em .6em; border-radius: 20px; font-weight: 600; }
    .badge-type { background: #f0fff4; color: #198754; font-size: .75rem; padding: .25em .6em; border-radius: 20px; font-weight: 600; }
    .badge-default { background: #fff8e1; color: #856404; font-size: .75rem; padding: .25em .6em; border-radius: 20px; font-weight: 600; }

    /* -- Callouts -----------------------------------------*/
    .callout {
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        font-size: .9rem;
    }
    .callout-info  { background: #e7f3ff; border-left: 4px solid #0d6efd; color: #084298; }
    .callout-tip   { background: #f0fff4; border-left: 4px solid #198754; color: #0a3622; }
    .callout-warn  { background: #fff8e1; border-left: 4px solid #ffc107; color: #664d03; }

    /* -- Page header --------------------------------------- */
    .docs-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border-radius: 14px;
        padding: 2.5rem 2.5rem 2rem;
        margin-bottom: 2rem;
    }
    .docs-hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: .5rem; }
    .docs-hero p  { opacity: .9; margin-bottom: 1rem; font-size: 1.05rem; }
    .docs-hero .badge-version {
        background: rgba(255,255,255,.2);
        border: 1px solid rgba(255,255,255,.35);
        color: #fff;
        font-size: .8rem;
        padding: .3em .75em;
        border-radius: 20px;
        font-weight: 600;
        margin-right: .4rem;
    }
    @media (min-width: 992px) {
        .docs-layout {
            --docs-sidebar-width: clamp(220px, 21vw, 260px);
            display: grid;
            grid-template-columns: var(--docs-sidebar-width) minmax(0, 1fr);
            gap: 1.5rem;
            align-items: start;
        }
        .docs-sidebar-col,
        .docs-main-col {
            width: auto;
            max-width: none;
            padding: 0;
            margin: 0;
        }
        .docs-sidebar-col {
            position: relative;
            min-height: calc(100vh - 104px);
        }
        .docs-sidebar-shell {
            height: calc(100vh - 104px);
        }
        .docs-sidebar-shell.is-pinned {
            position: fixed;
            top: 88px;
        }
    }
    @media (max-width: 991.98px) {
        .docs-sidebar-shell {
            position: static;
            top: auto;
            height: auto;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">

    {{-- Hero ---------------------------------------------------------- --}}
    <div class="docs-hero">
        <h1>&#128214; AFTable Documentation</h1>
        <p>A production-ready Laravel Livewire 4 datatable component with automatic sorting, searching, filtering, exports, bulk actions, and more.</p>
        <span class="badge-version">v1.5.6</span>
        <span class="badge-version">PHP 8.2+</span>
        <span class="badge-version">Laravel 12+</span>
        <span class="badge-version">Livewire 4</span>
        <div class="mt-3">
            <a href="{{ route('aftable.generator') }}" class="btn btn-light btn-sm fw-semibold me-2">&#9889; Open Generator</a>
            <a href="{{ route('aftable.test') }}" class="btn btn-outline-light btn-sm">&#129514; Test Environment</a>
        </div>
    </div>

    <div class="row g-4 docs-layout">

        {{-- Sidebar ---------------------------------------------------- --}}
        <div class="col-xl-2 col-lg-3 d-none d-lg-block docs-sidebar-col">
            <div class="docs-sidebar-shell">
                <nav class="docs-sidebar" id="docs-toc">
                    <div class="toc-group-label">Getting Started</div>
                    <ul>
                        <li><a href="#installation">Installation</a></li>
                        <li><a href="#quick-start">Quick Start</a></li>
                    </ul>
                    <div class="toc-group-label">Features</div>
                    <ul>
                        <li><a href="#columns">Column Types</a></li>
                        <li><a href="#raw">Raw Templates</a></li>
                        <li><a href="#relations">Relationships</a></li>
                        <li><a href="#filters">Filters</a></li>
                        <li><a href="#sorting">Sorting</a></li>
                        <li><a href="#actions">Row Actions</a></li>
                        <li><a href="#bulk-actions">Bulk Actions</a></li>
                        <li><a href="#static-mode">Static / Array Mode</a></li>
                        <li><a href="#exports">Exports</a></li>
                        <li><a href="#events">Events</a></li>
                    </ul>
                    <div class="toc-group-label">Reference</div>
                    <ul>
                        <li><a href="#livewire4">Livewire 4 Notes</a></li>
                        <li><a href="#api">Full API Reference</a></li>
                    </ul>
                </nav>
            </div>
        </div>

        {{-- Main -------------------------------------------------------- --}}
        <div class="col-xl-10 col-lg-9 docs-main-col">

            {{-- INSTALLATION ------------------------------------------ --}}
            <div id="installation" class="doc-card">
                <h2><span class="emoji">&#128230;</span>Installation</h2>
                <p>Install the package via Composer. It auto-registers with Laravel's package discovery — no manual provider or alias needed.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatimcomposer require artflow-studio/table@endverbatim</pre>

                <div class="callout callout-tip">
                    <strong>Zero config.</strong> The <code>@@AFtable</code> Blade directive and the <code>aftable</code> Livewire component are registered automatically.
                </div>

                <h5>Optional: Disable Blaze compilation</h5>
                <p>The package bundles <code>livewire/blaze</code> for optimized Blade component rendering. To disable it:</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatimBLAZE_ENABLED=false   # in your .env@endverbatim</pre>
            </div>

            {{-- QUICK START ------------------------------------------- --}}
            <div id="quick-start" class="doc-card">
                <h2><span class="emoji">&#128640;</span>Quick Start</h2>
                <p>Drop the <code>@@livewire</code> directive into any Blade view. The table handles fetching, sorting, searching, and pagination automatically.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'   => App\Models\User::class,
    'columns' => [
        ['key' => 'id',         'label' => 'ID'],
        ['key' => 'name',       'label' => 'Name'],
        ['key' => 'email',      'label' => 'Email'],
        ['key' => 'created_at', 'label' => 'Created'],
    ],
])@endverbatim</pre>

                <p>Or use the shorter Blade directive alias:</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@AFtable([
    'model'   => App\Models\Post::class,
    'columns' => [['key' => 'title', 'label' => 'Title']],
])@endverbatim</pre>

                <div class="callout callout-info">
                    <strong>What you get for free:</strong> Sorting on every column · Global search · Pagination · Column visibility toggle · Responsive layout
                </div>
            </div>

            {{-- COLUMN TYPES ------------------------------------------ --}}
            <div id="columns" class="doc-card">
                <h2><span class="emoji">&#128203;</span>Column Types</h2>
                <p>Each column is an associative array. The only required key is <code>key</code>.</p>

                <h5>Minimal column</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'name', 'label' => 'Full Name']@endverbatim</pre>

                <h5>All column options</h5>
                <div class="table-responsive">
                    <table class="table table-bordered doc-table">
                        <thead><tr><th>Option</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td><code>key</code></td><td>string</td><td>—</td><td>Database column / accessor name</td></tr>
                            <tr><td><code>label</code></td><td>string</td><td>auto-title</td><td>Column header text</td></tr>
                            <tr><td><code>sortable</code></td><td>bool</td><td>true</td><td>Whether column header is clickable for sorting</td></tr>
                            <tr><td><code>searchable</code></td><td>bool</td><td>true</td><td>Include this column in the global search query</td></tr>
                            <tr><td><code>hide</code></td><td>bool</td><td>false</td><td>Hidden by default; user can reveal via Column Visibility</td></tr>
                            <tr><td><code>class</code></td><td>string</td><td>—</td><td>CSS class applied to both <code>&lt;th&gt;</code> and <code>&lt;td&gt;</code></td></tr>
                            <tr><td><code>th_class</code></td><td>string</td><td>—</td><td>CSS class on <code>&lt;th&gt;</code> only</td></tr>
                            <tr><td><code>td_class</code></td><td>string</td><td>—</td><td>CSS class on <code>&lt;td&gt;</code> only</td></tr>
                            <tr><td><code>raw</code></td><td>string / Closure</td><td>—</td><td>Custom Blade/HTML template per row (see Raw Templates)</td></tr>
                            <tr><td><code>relation</code></td><td>string</td><td>—</td><td>Relationship traversal — <code>user:name</code> or <code>user.role:name</code></td></tr>
                            <tr><td><code>function</code></td><td>string</td><td>—</td><td>Model method name to call for this cell's value</td></tr>
                            <tr><td><code>json</code></td><td>string</td><td>—</td><td>Dot-notation path inside a JSON column — e.g. <code>meta.city</code></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- RAW TEMPLATES ----------------------------------------- --}}
            <div id="raw" class="doc-card">
                <h2><span class="emoji">&#127912;</span>Raw / Template Columns</h2>
                <p>Use <code>raw</code> to render any Blade expression or HTML per row. The current row model is available as <code>$row</code>. The <a href="{{ route('aftable.generator') }}">Generator</a> can build all of the patterns below for you automatically.</p>

                <div class="callout callout-tip">
                    <strong>Two syntaxes available:</strong>
                    <code>'raw' => 'Blade string with &#123;&#123; $row->field &#125;&#125;'</code> (compiled by Blade) or
                    <code>'raw' => fn($row) =&gt; 'PHP string'</code> (faster PHP closure — skips Blade compilation entirely).
                </div>

                {{-- AUTO E() ------------------------------------------- --}}
                <h5>&#9889; Auto-escaped Output (Auto e() type)</h5>
                <p>The safest one-liner for plain text. Wrap any expression with <code>e()</code> to XSS-escape it automatically. The generator's <strong>Auto e()</strong> button produces this pattern.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim// Auto — just escape and display the value
['key' => 'status',   'label' => 'Status',   'raw' => fn($row) => e($row->status)]
['key' => 'username', 'label' => 'Username', 'raw' => fn($row) => e($row->user->name ?? '—')]

// With simple formatting (still safe)
['key' => 'price', 'label' => 'Price',
    'raw' => fn($row) => 'PKR ' . number_format($row->price, 2)]@endverbatim</pre>

                {{-- STATIC BADGE -------------------------------------- --}}
                <h5>&#127381; Static Badge (single color)</h5>
                <p>Display a value inside a Bootstrap badge with a fixed background colour. Generated by the <strong>Badge</strong> button in the generator (Static mode).</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'role', 'label' => 'Role',
    'raw' => fn($row) => '&lt;span class="badge bg-primary"&gt;'.e($row->role).'&lt;/span&gt;']

['key' => 'plan', 'label' => 'Plan',
    'raw' => fn($row) => '&lt;span class="badge bg-info text-dark"&gt;'.e($row->plan).'&lt;/span&gt;']@endverbatim</pre>

                {{-- CONDITIONAL BADGE --------------------------------- --}}
                <h5>&#127381; Conditional Badge (value → colour map)</h5>
                <p>Map each possible value to a different Bootstrap colour. Generated by the <strong>Badge</strong> button (Map mode) — enter comma-separated <code>value:color</code> pairs in the generator.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim// Define the map, then look up the colour at render time
['key' => 'status', 'label' => 'Status',
    'raw' => fn($row) => '&lt;span class="badge bg-'.(['active'=>'success','inactive'=>'secondary','banned'=>'danger'][$row->status] ?? 'secondary').'"&gt;'.e($row->status).'&lt;/span&gt;']

// More colours
['key' => 'priority', 'label' => 'Priority',
    'raw' => fn($row) => '&lt;span class="badge bg-'.([
        'high'   => 'danger',
        'medium' => 'warning text-dark',
        'low'    => 'secondary',
    ][$row->priority] ?? 'secondary').'"&gt;'.e($row->priority).'&lt;/span&gt;']@endverbatim</pre>

                {{-- BADGE + ICON -------------------------------------- --}}
                <h5>&#128313; Badge + Icon (active / inactive)</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'active', 'label' => 'Status',
    'raw' => fn($row) => $row->active
            ? '&lt;span class="badge bg-success"&gt;&lt;i class="fas fa-check me-1"&gt;&lt;/i&gt;Active&lt;/span&gt;'
            : '&lt;span class="badge bg-secondary"&gt;&lt;i class="fas fa-times me-1"&gt;&lt;/i&gt;Inactive&lt;/span&gt;']@endverbatim</pre>

                {{-- IF / ELSE ----------------------------------------- --}}
                <h5>&#8635; If / Else Ternary Column</h5>
                <p>Render completely different HTML for each branch. Generated by the <strong>If/Else</strong> button in the generator.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim// Simple text branches
['key' => 'verified', 'label' => 'Verified',
    'raw' => fn($row) => $row->verified ? '&#9989; Yes' : '&#10060; No']

// HTML branches
['key' => 'active', 'label' => 'Active',
    'raw' => fn($row) => $row->active
        ? '&lt;span class="text-success fw-bold"&gt;&#9679; Online&lt;/span&gt;'
        : '&lt;span class="text-muted"&gt;&#9675; Offline&lt;/span&gt;']

// Null-safe
['key' => 'deleted_at', 'label' => 'Archived',
    'raw' => fn($row) => $row->deleted_at
        ? '&lt;span class="badge bg-warning text-dark"&gt;Archived&lt;/span&gt;'
        : '&lt;span class="text-muted"&gt;—&lt;/span&gt;']@endverbatim</pre>

                {{-- LINKS & CLICKABLE CELLS --------------------------- --}}
                <h5>&#128279; Clickable Name / Link Column</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'name', 'label' => 'Name',
    'raw' => fn($row) => '&lt;a href="/users/'.e($row->id).'/edit" class="text-decoration-none fw-semibold"&gt;'.e($row->name).'&lt;/a&gt;']

// Open in new tab
['key' => 'website', 'label' => 'Website',
    'raw' => fn($row) => $row->website
        ? '&lt;a href="'.e($row->website).'" target="_blank" rel="noopener"&gt;'.e($row->website).'&lt;/a&gt;'
        : '&lt;span class="text-muted"&gt;—&lt;/span&gt;']@endverbatim</pre>

                {{-- AVATAR -------------------------------------------- --}}
                <h5>&#128100; Avatar + Name Cell</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'name', 'label' => 'User',
    'raw' => fn($row) =>
                '&lt;div class="d-flex align-items-center gap-2"&gt;'
            . '&lt;img src="'.e($row->avatar_url ?? '/img/default-avatar.png').'" class="rounded-circle" width="32" height="32" style="object-fit:cover"&gt;'
            . '&lt;span class="fw-semibold"&gt;'.e($row->name).'&lt;/span&gt;'
            . '&lt;/div&gt;']@endverbatim</pre>

                {{-- PROGRESS BAR -------------------------------------- --}}
                <h5>&#128202; Progress Bar</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'progress', 'label' => 'Progress',
    'raw' => fn($row) =>
                '&lt;div class="progress" style="height:8px;min-width:80px"&gt;'
            . '&lt;div class="progress-bar bg-primary" role="progressbar" style="width:'.min(100,(int)$row->progress).'%"&gt;&lt;/div&gt;'
            . '&lt;/div&gt;'
            . '&lt;span class="small text-muted ms-1"&gt;'.min(100,(int)$row->progress).'%&lt;/span&gt;']@endverbatim</pre>

                {{-- TRUNCATED TEXT ------------------------------------ --}}
                <h5>&#128196; Truncated Text with Tooltip</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'notes', 'label' => 'Notes',
    'raw' => fn($row) => $row->notes
            ? '&lt;span title="'.e($row->notes).'" style="cursor:help;max-width:180px;display:inline-block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom"&gt;'.e($row->notes).'&lt;/span&gt;'
            : '&lt;span class="text-muted"&gt;—&lt;/span&gt;']@endverbatim</pre>

                {{-- DATE ---------------------------------------------- --}}
                <h5>&#128197; Date Formatting</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim// Blade string template
['key' => 'created_at', 'label' => 'Date',
    'raw' => '{{ \Carbon\Carbon::parse($row->created_at)->format("d M Y") }}']

// PHP closure — faster
['key' => 'created_at', 'label' => 'Created',
    'raw' => fn($row) => $row->created_at
        ? e($row->created_at->format('d M Y, g:i a'))
        : '—']

// Relative (human diff)
['key' => 'updated_at', 'label' => 'Last Updated',
    'raw' => fn($row) => '&lt;span title="'.e($row->updated_at).'"&gt;'.e($row->updated_at->diffForHumans()).'&lt;/span&gt;']@endverbatim</pre>

                {{-- CURRENCY ------------------------------------------ --}}
                <h5>&#128176; Currency</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim['key' => 'amount', 'label' => 'Amount',
    'raw' => fn($row) => '&lt;span class="fw-bold text-success"&gt;PKR '.number_format($row->amount, 2).'&lt;/span&gt;']

// Multi-currency with extraVars
['key' => 'total', 'label' => 'Total',
    'raw' => '{{ $currency }} {{ number_format($row->total, 2) }}']@endverbatim</pre>

                {{-- RAW BLADE STRING ----------------------------------- --}}
                <h5>&#127800; Blade String Template</h5>
                <p>Use <code>&#123;&#123; &#125;&#125;</code> for escaped output and <code>&#123;!! !!&#125;</code> for raw HTML inside a Blade string. Remember to escape user data with <code>e()</code> or <code>&#123;&#123; &#125;&#125;</code>.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim// Escaped Blade output
['key' => 'email', 'label' => 'Email',
    'raw' => '&lt;a href="mailto:{{ $row->email }}"&gt;{{ $row->email }}&lt;/a&gt;']

// Conditional Blade (unescaped — only safe for trusted/internal data)
['key' => 'active', 'label' => 'Status', 'raw' =>
    '{!! $row->active
        ? "&lt;span class=\"badge bg-success\"&gt;Active&lt;/span&gt;"
        : "&lt;span class=\"badge bg-secondary\"&gt;Inactive&lt;/span&gt;" !!}']@endverbatim</pre>

                {{-- EXTRA VARS ---------------------------------------- --}}
                <h5>&#128297; Extra Variables (<code>extraVars</code>)</h5>
                <p>Pass <code>extraVars</code> to inject additional data into every Blade <code>raw</code> template on the same table. Not needed for PHP closure columns — closures can capture scope directly.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'     => App\Models\Order::class,
    'extraVars' => ['currency' => 'USD', 'taxRate' => 0.15],
    'columns'   => [
        ['key' => 'subtotal', 'label' => 'Total inc. Tax',
         'raw' => '{{ $currency }} {{ number_format($row->subtotal * (1 + $taxRate), 2) }}'],
    ],
])@endverbatim</pre>

                <div class="callout callout-warn">
                    <strong>Security reminder:</strong> Always escape user-supplied values with <code>e()</code> in closures or <code>&#123;&#123; &#125;&#125;</code> in Blade strings. Never use <code>&#123;!! !!&#125;</code> with data that originates from user input.
                </div>
            </div>

            {{-- RELATIONSHIPS ----------------------------------------- --}}
            <div id="relations" class="doc-card">
                <h2><span class="emoji">&#128279;</span>Relationship Columns</h2>
                <p>Use <code>relation</code> with the format <code>relationMethod:attribute</code>. Relationships are automatically eager-loaded, preventing N+1 queries.</p>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim// BelongsTo — show user name on each row
['key' => 'user_id', 'label' => 'User', 'relation' => 'user:name']

// Nested — user ? role ? name
['key' => 'user_id', 'label' => 'Role', 'relation' => 'user.role:name']

// HasOne — profile avatar
['key' => 'id', 'label' => 'Avatar', 'relation' => 'profile:avatar_url']

// HasMany count (use withCount instead for performance)
['key' => 'id', 'label' => 'Orders', 'relation' => 'orders:count']@endverbatim</pre>

                <div class="callout callout-tip">
                    <strong>Auto eager-loading:</strong> Any <code>relation</code> column is added to an <code>->with()</code> call automatically — no manual <code>load()</code> needed.
                </div>
            </div>

            {{-- FILTERS ---------------------------------------------- --}}
            <div id="filters" class="doc-card">
                <h2><span class="emoji">&#128269;</span>Filters</h2>
                <p>Add a <code>filters</code> array to enable per-column filtering. Filters appear as additional row controls above the table.</p>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'   => App\Models\Order::class,
    'columns' => [...],
    'filters' => [
        'status'      => ['type' => 'select', 'values' => ['pending', 'paid', 'cancelled']],
        'total'       => ['type' => 'number'],
        'created_at'  => ['type' => 'date'],
        'notes'       => ['type' => 'text'],
        // Relation filter — dropdown populated from distinct DB values
        'category_id' => ['type' => 'distinct', 'relation' => 'category:name'],
    ],
])@endverbatim</pre>

                <h5>Filter Types</h5>
                <div class="table-responsive">
                    <table class="table table-bordered doc-table">
                        <thead><tr><th>Type</th><th>Operators</th><th>Input rendered</th></tr></thead>
                        <tbody>
                            <tr><td><code>text</code></td><td>LIKE</td><td>Text input (min 3 chars)</td></tr>
                            <tr><td><code>select</code></td><td>=</td><td>Dropdown of provided values</td></tr>
                            <tr><td><code>distinct</code></td><td>=</td><td>Dropdown populated from distinct DB values of the column</td></tr>
                            <tr><td><code>number</code> / <code>integer</code></td><td>=  !=  &lt;  &gt;  =  =</td><td>Number input with operator selector</td></tr>
                            <tr><td><code>date</code></td><td>=  !=  &lt;  &gt;  =  =</td><td>Date picker with operator selector</td></tr>
                        </tbody>
                    </table>
                </div>

                <h5>Date Range Filter</h5>
                <p>For quick date-range filtering using a date-range picker, pass <code>dateColumn</code> and dispatch the <code>dateRangeSelected</code> event.</p>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'      => App\Models\Invoice::class,
    'dateColumn' => 'created_at',
    'columns'    => [...],
])@endverbatim</pre>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>// Dispatch from a JS date-range picker callback:
Livewire.dispatch('dateRangeSelected', { startDate: '2024-01-01', endDate: '2024-12-31' });</pre>
            </div>

            {{-- SORTING ----------------------------------------------- --}}
            <div id="sorting" class="doc-card">
                <h2><span class="emoji">&#8597;</span>Sorting</h2>
                <p>Every column is sortable by default. Click a column header to toggle between ascending and descending. Set initial sort state with <code>sortBy</code> and <code>sortDirection</code>.</p>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'         => App\Models\Invoice::class,
    'columns'       => [...],
    'sortBy'        => 'created_at',   // column to sort on initial load
    'sortDirection' => 'desc',          // 'asc' or 'desc'
])@endverbatim</pre>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="callout callout-info">
                            <strong>Disable sorting on a column:</strong><br>
                            <code>['key' => 'notes', 'sortable' => false]</code>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="callout callout-warn">
                            <strong>Relation columns</strong> are automatically marked non-sortable (SQL JOIN not generated) unless you sort by an eagerly-loaded accessor.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ROW ACTIONS ------------------------------------------- --}}
            <div id="actions" class="doc-card">
                <h2><span class="emoji">&#9889;</span>Row Actions</h2>
                <p>Pass an <code>actions</code> key to append a dedicated "Actions" column to every row. The <code>raw</code> template has access to <code>$row</code>.</p>

                <h5>String template</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'   => App\Models\User::class,
    'columns' => [...],
    'actions' => [
        'label' => 'Actions',   // optional — defaults to "Actions"
          'raw'   => "&lt;a href='{{ route(\"users.edit\", \$row->id) }}'
                              class='btn btn-sm btn-warning me-1'&gt;Edit&lt;/a&gt;
                          &lt;a href='{{ route(\"users.show\", \$row->id) }}'
                              class='btn btn-sm btn-info'&gt;View&lt;/a&gt;",
    ],
])@endverbatim</pre>

                <h5>PHP Closure (recommended for dynamic routing)</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim'actions' => [
    'raw' => fn($row) =>
          '&lt;a href="'.route('users.edit', $row->id).'" class="btn btn-sm btn-primary me-1"&gt;Edit&lt;/a&gt;'
          .'&lt;button wire:click="deleteRow('.$row->id.')" class="btn btn-sm btn-danger"&gt;Delete&lt;/button&gt;',
],@endverbatim</pre>
            </div>

            {{-- BULK ACTIONS ------------------------------------------ --}}
            <div id="bulk-actions" class="doc-card">
                <h2><span class="emoji">&#9989;</span>Bulk Actions (Checkboxes)</h2>
                <p>Enable row checkboxes with <code>'checkbox' => true</code>. Selected row IDs are tracked in the <code>$selectedRows</code> Livewire property. Use <code>bulkActions</code> to define what happens when the user applies an action to the selection.</p>

                <h5>Enable checkboxes</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'    => App\Models\User::class,
    'checkbox' => true,
    'columns'  => [...],
    'bulkActions' => [
        'delete'    => 'Delete Selected',
        'activate'  => 'Mark Active',
        'deactivate'=> 'Mark Inactive',
        'export'    => 'Export Selected',
    ],
])@endverbatim</pre>

                <div class="callout callout-info">
                    The <strong>Select All</strong> checkbox in the header selects all rows on the current page. The selected IDs are stored in <code>$selectedRows</code> as an array of strings.
                </div>

                <h5>Handling bulk actions in your Livewire parent</h5>
                <p>Listen for the <code>bulkActionTriggered</code> event dispatched by the table, or wire the action buttons directly into the parent component.</p>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>// Option A — listen for the event dispatched by the table
#[On('bulkActionTriggered')]
public function onBulkAction(string $action, array $ids): void
{
    match ($action) {
        'delete'     => User::whereIn('id', $ids)->delete(),
        'activate'   => User::whereIn('id', $ids)->update(['active' => true]),
        'deactivate' => User::whereIn('id', $ids)->update(['active' => false]),
        default      => null,
    };
    $this->dispatch('refreshTable');
}</pre>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>// Option B — call handleBulkAction directly (if embedding in same component)
public function handleBulkAction(string $actionKey): void
{
    // $this->selectedRows is already populated by the table island
    $ids = $this->selectedRows;
    // ... process $ids ...
    $this->clearAllSelections();
}</pre>

                <h5>Bulk action toolbar</h5>
                <p>When at least one row is checked, a floating toolbar appears above the table showing the count and dropdown of defined actions. This is rendered automatically — no extra markup needed.</p>

                <div class="callout callout-tip">
                    Call <code>wire:click="clearAllSelections"</code> (or dispatch <code>refreshTable</code>) after processing to reset the selection state.
                </div>
            </div>

            {{-- STATIC / ARRAY MODE ----------------------------------- --}}
            <div id="static-mode" class="doc-card">
                <h2><span class="emoji">&#128452;</span>Static / Array Mode</h2>
                <p>Pass any array or Collection as <code>data</code> instead of a <code>model</code>. Search, sort, and filter all work in-memory — no database needed.</p>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'data'    => $myCollection,          // array, Collection, or Model[]
    'columns' => [
        ['key' => 'name',  'label' => 'Name'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'total', 'label' => 'Total',
         'raw' => 'PKR {{ number_format($row->price * $row->qty, 2) }}'],
    ],
])@endverbatim</pre>

                <div class="callout callout-warn">
                    Recommended for = 500 rows. For larger datasets use the <code>model</code> approach with database-level filtering.
                </div>
            </div>

            {{-- EXPORTS ---------------------------------------------- --}}
            <div id="exports" class="doc-card">
                <h2><span class="emoji">&#128228;</span>Exports</h2>
                <p>Enable the export button group by setting <code>'exportable' => true</code>. Supports CSV (built-in), Excel (requires <code>maatwebsite/excel</code>), and PDF (requires <code>barryvdh/laravel-snappy</code>).</p>

                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>@verbatim@livewire('aftable', [
    'model'      => App\Models\Invoice::class,
    'exportable' => true,   // shows CSV / Excel / PDF buttons
    'printable'  => true,   // shows a Print button (opens isolated print window)
    'columns'    => [...],
])@endverbatim</pre>

                <div class="callout callout-info">
                    Exports respect all active filters and sorting — only the currently visible result set is exported, not the full table.
                </div>
            </div>

            {{-- EVENTS ----------------------------------------------- --}}
            <div id="events" class="doc-card">
                <h2><span class="emoji">&#128225;</span>Events</h2>

                <h5>Events the table listens to</h5>
                <div class="table-responsive">
                    <table class="table table-bordered doc-table">
                        <thead><tr><th>Event</th><th>Payload</th><th>Effect</th></tr></thead>
                        <tbody>
                            <tr><td><code>refreshTable</code></td><td>—</td><td>Clears computed cache and re-renders with fresh data</td></tr>
                            <tr><td><code>dateRangeSelected</code></td><td><code>startDate, endDate</code></td><td>Applies date range constraint to the <code>dateColumn</code></td></tr>
                        </tbody>
                    </table>
                </div>

                <h5>Events the table dispatches</h5>
                <div class="table-responsive">
                    <table class="table table-bordered doc-table">
                        <thead><tr><th>Event</th><th>Payload</th><th>When</th></tr></thead>
                        <tbody>
                            <tr><td><code>bulkActionTriggered</code></td><td><code>action (string), ids (array)</code></td><td>User clicks a bulk action button</td></tr>
                        </tbody>
                    </table>
                </div>

                <h5>Dispatch from Alpine / JS</h5>
                <pre class="code-block"><button class="copy-btn" onclick="copyCode(this)">Copy</button>// Refresh the table
Livewire.dispatch('refreshTable');

// Apply a date range to the dateColumn
Livewire.dispatch('dateRangeSelected', {
    startDate: '2024-01-01',
    endDate:   '2024-12-31'
});</pre>
            </div>

            {{-- LIVEWIRE 4 NOTES ------------------------------------- --}}
            <div id="livewire4" class="doc-card">
                <h2><span class="emoji">&#127381;</span>Livewire 4 Notes</h2>
                <ul class="mb-0" style="color:#6c757d; line-height:1.9;">
                    <li><code>raw</code> templates use <code>compileString + eval</code> internally — fully compatible with Livewire 4's <code>ExtendedCompilerEngine</code>.</li>
                    <li>Event listeners use <code>#[On]</code> attributes instead of the deprecated <code>$listeners</code> array.</li>
                    <li>Table data is a <code>#[Computed]</code> property — cached per request, automatically invalidated on state change.</li>
                    <li><code>wire:model.live</code> is used for real-time updates (search, filters, records per page).</li>
                    <li>Alpine.js is bundled with Livewire 4 — no separate import needed.</li>
                    <li>The table + pagination are wrapped in a single <code>@@island</code> with <code>always: true</code> so any outer trigger (column visibility, refresh) always re-renders the data.</li>
                </ul>
            </div>

            {{-- FULL API REFERENCE ------------------------------------ --}}
            <div id="api" class="doc-card">
                <h2><span class="emoji">&#9881;</span>Full API Reference</h2>
                <div class="table-responsive">
                    <table class="table table-bordered doc-table">
                        <thead><tr><th>Prop</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td><code>model</code></td><td>string</td><td>—</td><td>Eloquent model FQCN. Use instead of <code>data</code>.</td></tr>
                            <tr><td><code>data</code></td><td>array / Collection</td><td>—</td><td>Static in-memory data. Use instead of <code>model</code>.</td></tr>
                            <tr><td><code>query</code></td><td>Builder</td><td>null</td><td>Pre-constrained query builder. Overrides <code>model::query()</code>.</td></tr>
                            <tr><td><code>columns</code></td><td>array</td><td>[]</td><td>Column definitions (see Column Types).</td></tr>
                            <tr><td><code>filters</code></td><td>array</td><td>[]</td><td>Per-column filter configuration (see Filters).</td></tr>
                            <tr><td><code>actions</code></td><td>array</td><td>[]</td><td>Row actions column config — <code>['raw' => '...']</code>.</td></tr>
                            <tr><td><code>bulkActions</code></td><td>array</td><td>[]</td><td>Bulk action map — <code>['key' => 'Label']</code>. Requires <code>checkbox: true</code>.</td></tr>
                            <tr><td><code>sortBy</code></td><td>string</td><td>null</td><td>Initial sort column key.</td></tr>
                            <tr><td><code>sortDirection</code></td><td>string</td><td>'desc'</td><td>Initial sort direction — <code>'asc'</code> or <code>'desc'</code>.</td></tr>
                            <tr><td><code>perPage</code></td><td>int</td><td>10</td><td>Rows per page on initial load.</td></tr>
                            <tr><td><code>searchable</code></td><td>bool</td><td>true</td><td>Show / hide the global search input.</td></tr>
                            <tr><td><code>checkbox</code></td><td>bool</td><td>false</td><td>Show row checkboxes for bulk selection.</td></tr>
                            <tr><td><code>index</code></td><td>bool</td><td>false</td><td>Prepend a row-number column.</td></tr>
                            <tr><td><code>colvisBtn</code></td><td>bool</td><td>true</td><td>Show the Column Visibility dropdown.</td></tr>
                            <tr><td><code>refreshBtn</code></td><td>bool</td><td>false</td><td>Show a manual refresh button.</td></tr>
                            <tr><td><code>exportable</code></td><td>bool</td><td>false</td><td>Show Export buttons (CSV / Excel / PDF).</td></tr>
                            <tr><td><code>printable</code></td><td>bool</td><td>false</td><td>Show a Print button (separate window).</td></tr>
                            <tr><td><code>extraVars</code></td><td>array</td><td>[]</td><td>Extra variables available in all <code>raw</code> templates.</td></tr>
                            <tr><td><code>dateColumn</code></td><td>string</td><td>null</td><td>Column used for the date-range filter event.</td></tr>
                            <tr><td><code>tableId</code></td><td>string</td><td>auto</td><td>HTML <code>id</code> attribute of the rendered <code>&lt;table&gt;</code>.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- /col --}}
    </div>{{-- /row --}}
</div>
@endsection

@push('scripts')
<script>
// Highlight active TOC link on scroll
(function () {
    const sections = document.querySelectorAll('.doc-card[id]');
    const links    = document.querySelectorAll('#docs-toc a');

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                links.forEach(l => l.classList.remove('active'));
                const active = document.querySelector('#docs-toc a[href="#' + entry.target.id + '"]');
                if (active) active.classList.add('active');
            }
        });
    }, { rootMargin: '-60px 0px -70% 0px' });

    sections.forEach(s => observer.observe(s));
})();

(function () {
    const mediaQuery = window.matchMedia('(min-width: 992px)');
    const sidebarCol = document.querySelector('.docs-sidebar-col');
    const sidebarShell = document.querySelector('.docs-sidebar-shell');
    const topOffset = 88;

    if (!sidebarCol || !sidebarShell) {
        return;
    }

    function resetPinnedState() {
        sidebarShell.classList.remove('is-pinned');
        sidebarShell.style.left = '';
        sidebarShell.style.width = '';
    }

    function syncSidebarPin() {
        if (!mediaQuery.matches) {
            resetPinnedState();
            return;
        }

        const sidebarTop = sidebarCol.getBoundingClientRect().top + window.scrollY;

        if (window.scrollY + topOffset >= sidebarTop) {
            const rect = sidebarCol.getBoundingClientRect();
            sidebarShell.classList.add('is-pinned');
            sidebarShell.style.left = rect.left + 'px';
            sidebarShell.style.width = rect.width + 'px';
        } else {
            resetPinnedState();
        }
    }

    window.addEventListener('scroll', syncSidebarPin, { passive: true });
    window.addEventListener('resize', syncSidebarPin);
    mediaQuery.addEventListener('change', syncSidebarPin);
    syncSidebarPin();
})();

// Copy-to-clipboard for code blocks
function copyCode(btn) {
    const code = btn.closest('pre').textContent.replace(btn.textContent, '').trim();
    navigator.clipboard.writeText(code).then(() => {
        const orig = btn.textContent;
        btn.textContent = '? Copied';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}
</script>
@endpush
