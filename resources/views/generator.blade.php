@extends('artflow-table::test.layout')

@php
    $schemaEnabled = \ArtflowStudio\Table\Support\SchemaIntrospector::enabled();
@endphp

@section('withoutLivewireAssets', '1')

@push('styles')
<style>
    .gen-hero{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px;padding:1.1rem 1.5rem;margin-bottom:1rem;}
    .gen-hero h1{font-size:1.25rem;font-weight:800;margin-bottom:.15rem;}
    .gen-hero p{opacity:.88;margin:0;font-size:.82rem;}

    .gen-card{background:#fff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.07);padding:.9rem 1.1rem;margin-bottom:.9rem;}
    .gen-card h2{font-size:.82rem;font-weight:700;color:#343a40;border-bottom:2px solid #f0f3ff;padding-bottom:.3rem;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.04em;}

    .builder-row{display:flex;gap:.3rem;align-items:center;flex-wrap:wrap;margin-bottom:.35rem;padding:.35rem .5rem;background:#f8f9fa;border-radius:6px;border:1px solid #e9ecef;}
    .builder-row .form-control,.builder-row .form-select{flex:1;min-width:70px;}
    .builder-row .w-kl{max-width:100px;}
    .builder-row .w-lb{max-width:120px;}
    .builder-row .w-md{max-width:145px;}
    .builder-row .wide{flex:2;min-width:155px;}
    .builder-row .full-width{flex:100 1 100%;}

    .type-badge{font-size:.62rem;font-weight:700;padding:.18em .45em;border-radius:20px;white-space:nowrap;flex-shrink:0;line-height:1.4;}
    .badge-db      {background:#e7f3ff;color:#0066cc;}
    .badge-auto    {background:#f0fff4;color:#059669;}
    .badge-badge   {background:#fdf4ff;color:#9333ea;}
    .badge-ifelse  {background:#fff7ed;color:#c2410c;}
    .badge-raw     {background:#fff8e1;color:#856404;}
    .badge-closure {background:#f3e8ff;color:#7c3aed;}
    .badge-relation{background:#ecfdf5;color:#198754;}
    .badge-json    {background:#e8fdf5;color:#0891b2;}
    .badge-filter  {background:#fff0f0;color:#dc3545;}
    .badge-action  {background:#eef2ff;color:#4338ca;}

    pre.gen-output{background:#1e1e2e;color:#cdd6f4;border-radius:10px;padding:.9rem 1.1rem;font-size:.78rem;overflow-x:auto;white-space:pre;margin:0;max-height:520px;overflow-y:auto;position:relative;}
    .copy-float{position:absolute;top:.45rem;right:.55rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);color:#cdd6f4;padding:.12rem .45rem;border-radius:5px;font-size:.68rem;cursor:pointer;transition:background .15s;}
    .copy-float:hover{background:rgba(255,255,255,.25);}

    .option-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.5rem;}
    .gen-toggle-group{display:flex;flex-wrap:wrap;gap:.4rem;}
    .gen-toggle-item{display:flex;align-items:center;gap:.3rem;font-size:.76rem;color:#495057;}

    .col-btn-row{display:flex;gap:.3rem;flex-wrap:wrap;}
    .col-btn-row .btn{font-size:.7rem;padding:.22rem .5rem;line-height:1.3;}
    .rm-btn{font-size:.65rem !important;padding:.12rem .35rem !important;line-height:1.3 !important;}
    .snip-btn{font-size:.7rem;padding:.2rem .5rem;text-align:left;}
</style>
@endpush

@section('content')
<div class="container-fluid py-2">

    {{-- Hero --}}
    <div class="gen-hero">
        <h1>&#9889; AFTable Code Generator</h1>
        <p>Build your <code class="opacity-75">@@livewire('aftable', [...])</code> &mdash; columns, badges, if/else, filters, actions &mdash; then click <strong>Generate</strong>.</p>
    </div>

    <div class="row g-3">

        {{-- LEFT: Builder --}}
        <div class="col-xl-8">

            {{-- 1. Data Source --}}
            <div class="gen-card">
                <h2>1. Data Source</h2>
                <div class="row g-2">
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold mb-1">Data Mode</label>
                        <select id="gen-mode" class="form-select form-select-sm" onchange="toggleDataMode()">
                            <option value="eloquent">Eloquent Model</option>
                            <option value="static">Static / Array</option>
                        </select>
                    </div>
                    <div class="col-sm-8" id="source-eloquent">
                        <label class="form-label small fw-semibold mb-1">Model Class <span class="text-danger">*</span></label>
@if ($schemaEnabled)
                        <div class="input-group input-group-sm">
                            <select id="gen-model-picker" class="form-select form-select-sm" onchange="pickModel()">
                                <option value="">&mdash; loading your models &mdash;</option>
                            </select>
                            <button class="btn btn-outline-primary" type="button" id="gen-schema-btn" onclick="loadSchema()" disabled>Load columns</button>
                        </div>
                        <input id="gen-model" type="text" class="form-control form-control-sm mt-1" placeholder="App\Models\User" value="App\Models\User">
                        <div class="form-text" style="font-size:.68rem;" id="gen-schema-status">Reading your schema&hellip;</div>
@else
                        <input id="gen-model" type="text" class="form-control form-control-sm" placeholder="App\Models\User" value="App\Models\User">
                        <div class="form-text" style="font-size:.68rem;">
                            Set <code>AFTABLE_GENERATOR_ENABLED=true</code> to pick from your own models and fill the
                            column builder from the real schema.
                        </div>
@endif
                    </div>
                    <div class="col-sm-8" id="source-static" style="display:none">
                        <label class="form-label small fw-semibold mb-1">PHP Variable <span class="text-danger">*</span></label>
                        <input id="gen-static-var" type="text" class="form-control form-control-sm" placeholder="$employees">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold mb-1">Extra Variables <span class="text-muted fw-normal">(optional)</span></label>
                        <input id="gen-vars" type="text" class="form-control form-control-sm" placeholder="currency=USD, tax=0.15">
                        <div class="form-text" style="font-size:.68rem;">Comma-separated <code>key=value</code></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold mb-1">Date Column <span class="text-muted fw-normal">(optional)</span></label>
                        <input id="gen-date-col" type="text" class="form-control form-control-sm" placeholder="created_at">
                        <div class="form-text" style="font-size:.68rem;">Enables date-range filter event</div>
                    </div>
                </div>
            </div>

            {{-- 2. Columns --}}
            <div class="gen-card">
                <h2>2. Columns</h2>
                <div id="gen-relations" class="mb-2" style="display:none">
                    <div class="form-label small fw-semibold mb-1">Relations on this model</div>
                    <div id="gen-relations-list" class="d-flex flex-wrap gap-1 mb-2"></div>
                </div>
                <div id="columns-list" class="mb-2"></div>
                <div class="col-btn-row">
                    <button class="btn btn-outline-primary"   onclick="addColumn('db')">+ DB Col</button>
                    <button class="btn btn-outline-success"   onclick="addColumn('auto')">+ Auto e()</button>
                    <button class="btn" style="border-color:#9333ea;color:#9333ea;" onclick="addColumn('badge')">+ Badge</button>
                    <button class="btn" style="border-color:#c2410c;color:#c2410c;" onclick="addColumn('ifelse')">+ If/Else</button>
                    <button class="btn btn-outline-warning"   onclick="addColumn('raw')">+ Template</button>
                    <button class="btn" style="border-color:#7c3aed;color:#7c3aed;" onclick="addColumn('closure')">+ Raw PHP/HTML</button>
                    <button class="btn btn-outline-success"   onclick="addColumn('relation')">+ Relation</button>
                    <button class="btn" style="border-color:#0891b2;color:#0891b2;" onclick="addColumn('json')">+ JSON Path</button>
                </div>
            </div>

            {{-- 3. Filters --}}
            <div class="gen-card">
                <h2>3. Filters <span class="text-muted fw-normal" style="text-transform:none;">optional</span></h2>
                <div id="filters-list" class="mb-2"></div>
                <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem;" onclick="addFilter()">+ Add Filter</button>
            </div>

            {{-- 4. Row Actions --}}
            <div class="gen-card">
                <h2>4. Row Actions <span class="text-muted fw-normal" style="text-transform:none;">optional</span></h2>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="small fw-semibold flex-shrink-0">Col label:</span>
                    <input id="gen-actions-label" type="text" class="form-control form-control-sm" value="Actions" style="max-width:140px">
                </div>
                <div id="actions-list" class="mb-2"></div>
                <div class="col-btn-row">
                    <button class="btn btn-outline-primary"   onclick="addAction('button')">+ Link Button</button>
                    <button class="btn btn-outline-secondary" onclick="addAction('raw')">+ Raw HTML</button>
                </div>
            </div>

            {{-- 5. Table Options --}}
            <div class="gen-card">
                <h2>5. Table Options</h2>
                <div class="option-grid mb-2">
                    <div>
                        <label class="form-label small fw-semibold mb-1">Per Page</label>
                        <input id="gen-perpage" type="number" class="form-control form-control-sm" value="10" min="1" max="500">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1">Sort Column</label>
                        <input id="gen-sort" type="text" class="form-control form-control-sm" placeholder="created_at">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold mb-1">Sort Dir</label>
                        <select id="gen-direction" class="form-select form-select-sm">
                            <option value="desc" selected>Desc</option>
                            <option value="asc">Asc</option>
                        </select>
                    </div>
                </div>
                <label class="form-label small fw-semibold mb-1">Flags</label>
                <div class="gen-toggle-group">
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-searchable" class="form-check-input" checked> Search</label>
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-colvis"     class="form-check-input" checked> Col Vis</label>
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-checkbox"   class="form-check-input"> Checkboxes</label>
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-index"      class="form-check-input"> Row Index</label>
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-exportable" class="form-check-input"> Export</label>
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-printable"  class="form-check-input"> Print</label>
                    <label class="gen-toggle-item"><input type="checkbox" id="gen-refreshbtn" class="form-check-input"> Refresh</label>
                </div>
            </div>

            {{-- 6. Bulk Actions --}}
            <div class="gen-card" id="bulk-actions-card" style="display:none">
                <h2>6. Bulk Actions <span class="text-muted fw-normal" style="text-transform:none;">requires checkboxes</span></h2>
                <div id="bulk-actions-list" class="mb-2"></div>
                <button class="btn btn-sm btn-outline-secondary" style="font-size:.7rem;" onclick="addBulkAction()">+ Add Bulk Action</button>
            </div>

        </div>{{-- /LEFT col-xl-8 --}}

        {{-- RIGHT: Sticky output + reference --}}
        <div class="col-xl-4">
            <div style="position:sticky;top:60px;display:flex;flex-direction:column;gap:.9rem;">

                {{-- Output card --}}
                <div class="gen-card mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="mb-0">Generated Code</h2>
                        <button class="btn btn-primary btn-sm" onclick="generateCode()" style="font-size:.76rem;">&#9889; Generate</button>
                    </div>
                    <div id="gen-placeholder" class="text-center py-4 text-muted">
                        <div style="font-size:2.25rem;">&#128203;</div>
                        <p class="mt-1 mb-0 small">Configure then click <strong>Generate</strong>.</p>
                    </div>
                    <div id="gen-output-wrap" style="display:none">
                        <div class="position-relative">
                            <pre class="gen-output" id="gen-output"></pre>
                            <button class="copy-float" onclick="copyGenCode()">&#128203; Copy</button>
                        </div>
                        <div id="gen-hint" class="mt-2"></div>
                    </div>
                </div>

                {{-- Column type reference --}}
                <div class="gen-card mb-0">
                    <h2>Column Types</h2>
                    <table class="table table-sm mb-0" style="font-size:.72rem;">
                        <tbody>
                            <tr><td><span class="type-badge badge-db">DB</span></td>     <td>DB field + format/sort/hide</td></tr>
                            <tr><td><span class="type-badge badge-auto">Auto</span></td>  <td><code>fn($row) =&gt; e($row-&gt;x)</code> &mdash; auto-escaped</td></tr>
                            <tr><td><span class="type-badge badge-badge">Badge</span></td><td>Static color or value&rarr;color map</td></tr>
                            <tr><td><span class="type-badge badge-ifelse">If/Else</span></td><td>Ternary &mdash; text or HTML per branch</td></tr>
                            <tr><td><span class="type-badge badge-raw">Template</span></td><td>Blade <code>&#123;&#123; &#125;&#125;</code> / <code>&#123;!! !!&#125;</code> string</td></tr>
                            <tr><td><span class="type-badge badge-closure">PHP/HTML</span></td><td>Full PHP expression, raw HTML</td></tr>
                            <tr><td><span class="type-badge badge-relation">Rel</span></td><td><code>user:name</code> relation</td></tr>
                            <tr><td><span class="type-badge badge-json">JSON</span></td>  <td>JSON path e.g. <code>meta.city</code></td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Power snippets --}}
                <div class="gen-card mb-0">
                    <h2>Ready-Made Snippets</h2>
                    <p class="text-muted mb-2" style="font-size:.69rem;">Click to copy a complete column entry.</p>
                    <div class="d-grid gap-1">
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('badge-cond')">&#128203; Conditional badge (value&rarr;color)</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('badge-icon')">&#128203; Badge + icon (active/inactive)</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('link-col')">&#128203; Clickable name link</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('avatar')">&#128203; Avatar + name cell</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('progress')">&#128203; Progress bar</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('currency')">&#128203; Currency (PKR formatted)</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('date-fmt')">&#128203; Date (Carbon formatted)</button>
                        <button class="btn btn-outline-secondary snip-btn" onclick="copySnippet('truncate')">&#128203; Truncated text + tooltip</button>
                    </div>
                </div>

                <a href="{{ route('aftable.docs') }}" class="btn btn-outline-primary btn-sm w-100">
                    &#128218; Full Documentation &rarr;
                </a>

            </div>
        </div>{{-- /RIGHT col-xl-4 --}}

    </div><!-- /row g-3 -->
</div>
@endsection

@push('scripts')
<script>
@verbatim
let colCount  = 0;
let filtCount = 0;
let actCount  = 0;
let bulkCount = 0;

/* ── helpers ─────────────────────────────────────────────────────── */
function toggleDataMode() {
    const mode = document.getElementById('gen-mode').value;
    document.getElementById('source-eloquent').style.display = mode === 'eloquent' ? '' : 'none';
    document.getElementById('source-static').style.display   = mode === 'static'   ? '' : 'none';
}

/**
 * Escape for PHP single-quoted string literal.
 * Backslashes → \\  |  Single quotes → \'
 */
function phpEsc(str) {
    if (!str) { return ''; }
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function rmBtn(id) {
    return `<button class="btn btn-sm btn-outline-danger ms-auto flex-shrink-0 rm-btn" onclick="removeEl('${id}')">&#10005;</button>`;
}

/* ── Column builder ─────────────────────────────────────────────── */
function addColumn(type) {
    colCount++;
    const id  = 'col_' + colCount;
    const div = document.createElement('div');
    div.className = 'builder-row';
    div.id = id;

    const badgeMap = {
        db:'badge-db', auto:'badge-auto', badge:'badge-badge',
        ifelse:'badge-ifelse', raw:'badge-raw', closure:'badge-closure',
        relation:'badge-relation', json:'badge-json'
    };
    const labelMap = {
        db:'DB', auto:'Auto&nbsp;e()', badge:'Badge', ifelse:'If/Else',
        raw:'Template', closure:'PHP/HTML', relation:'Rel', json:'JSON'
    };

    const bdg  = `<span class="type-badge ${badgeMap[type]}">${labelMap[type]}</span>`;
    const keyI = `<input class="form-control form-control-sm w-kl" placeholder="key" data-role="key">`;
    const lblI = `<input class="form-control form-control-sm w-lb" placeholder="label" data-role="label">`;
    const rm   = rmBtn(id);

    if (type === 'db') {
        div.innerHTML = bdg + keyI + lblI
            + `<select class="form-select form-select-sm" data-role="format" style="max-width:105px">`
            +   `<option value="">No Fmt</option><option value="currency">Currency</option>`
            +   `<option value="date">Date</option><option value="boolean">Boolean</option>`
            +   `<option value="number">Number</option>`
            + `</select>`
            + `<select class="form-select form-select-sm" data-role="sortable" style="max-width:90px">`
            +   `<option value="">Sortable</option><option value="false">No Sort</option>`
            + `</select>`
            + `<select class="form-select form-select-sm" data-role="hide" style="max-width:80px">`
            +   `<option value="">Visible</option><option value="true">Hidden</option>`
            + `</select>`
            + rm;

    } else if (type === 'auto') {
        /* Auto-escaped: type $row->status, outputs fn($row) => e($row->status) */
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm wide" placeholder="PHP expr: $row->status" data-role="auto-expr" style="font-family:monospace;font-size:.76rem;">`
            + `<small class="text-success flex-shrink-0" title="e() is added automatically" style="font-size:.63rem;white-space:nowrap;">&#9679;&nbsp;e()&nbsp;auto</small>`
            + rm;

    } else if (type === 'badge') {
        /* Static color OR value→color map badge */
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm w-md" placeholder="$row->status" data-role="badge-field" style="font-family:monospace;font-size:.76rem;">`
            + `<select class="form-select form-select-sm" data-role="badge-mode" onchange="updateBadgeUI('${id}')" style="max-width:110px">`
            +   `<option value="static">Static color</option><option value="map">Value→Color</option>`
            + `</select>`
            + `<select class="form-select form-select-sm" data-role="badge-color" style="max-width:88px">`
            +   `<option value="primary">blue</option><option value="success">green</option>`
            +   `<option value="danger">red</option><option value="warning">yellow</option>`
            +   `<option value="info">cyan</option><option value="secondary">grey</option>`
            +   `<option value="dark">dark</option>`
            + `</select>`
            + `<input class="form-control form-control-sm wide" placeholder="active=success,pending=warning,inactive=danger" data-role="badge-map" style="display:none;font-family:monospace;font-size:.72rem;">`
            + rm;

    } else if (type === 'ifelse') {
        /* Ternary; true/false values are PHP single-quoted string literals */
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm w-md" placeholder="condition: $row->active" data-role="cond" style="font-family:monospace;font-size:.76rem;">`
            + `<input class="form-control form-control-sm" placeholder="&#10003; Yes  or  &lt;b&gt;Active&lt;/b&gt;" data-role="true-val" style="font-size:.76rem;">`
            + `<input class="form-control form-control-sm" placeholder="&#10007; No  or  Inactive" data-role="false-val" style="font-size:.76rem;">`
            + rm;

    } else if (type === 'raw') {
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm wide" placeholder="Blade: {{ $row->status }}  or  {!! $html !!}" data-role="raw" style="font-family:monospace;font-size:.76rem;">`
            + rm;

    } else if (type === 'closure') {
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm wide" placeholder="PHP: $row->a ? '<b>'.e($row->b).'</b>' : 'No'" data-role="closure" style="font-family:monospace;font-size:.76rem;">`
            + rm;

    } else if (type === 'relation') {
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm w-md" placeholder="user:name" data-role="relation">`
            + rm;

    } else if (type === 'json') {
        div.innerHTML = bdg + keyI + lblI
            + `<input class="form-control form-control-sm w-md" placeholder="meta.city" data-role="json">`
            + rm;
    }

    document.getElementById('columns-list').appendChild(div);

    return div;
}

function updateBadgeUI(rowId) {
    const row  = document.getElementById(rowId);
    const mode = row.querySelector('[data-role="badge-mode"]').value;
    row.querySelector('[data-role="badge-color"]').style.display = mode === 'static' ? '' : 'none';
    row.querySelector('[data-role="badge-map"]').style.display   = mode === 'map'    ? '' : 'none';
}

/* ── Filter builder ─────────────────────────────────────────────── */
function addFilter() {
    filtCount++;
    const id  = 'filt_' + filtCount;
    const div = document.createElement('div');
    div.className = 'builder-row';
    div.id = id;
    div.innerHTML = `<span class="type-badge badge-filter">Filter</span>`
        + `<input class="form-control form-control-sm w-md" placeholder="column key" data-role="filt-key">`
        + `<select class="form-select form-select-sm" data-role="filt-type" onchange="updateFilterUI('${id}')" style="max-width:125px">`
        +   `<option value="text">Text</option><option value="select">Select</option>`
        +   `<option value="distinct">Distinct</option><option value="date">Date Range</option>`
        +   `<option value="number">Number Range</option>`
        + `</select>`
        + `<input class="form-control form-control-sm wide" placeholder="values: active,inactive" data-role="filt-values" style="display:none">`
        + `<input class="form-control form-control-sm wide" placeholder="relation: category:name" data-role="filt-relation" style="display:none">`
        + rmBtn(id);
    document.getElementById('filters-list').appendChild(div);
}

function updateFilterUI(rowId) {
    const row  = document.getElementById(rowId);
    const type = row.querySelector('[data-role="filt-type"]').value;
    row.querySelector('[data-role="filt-values"]').style.display   = type === 'select'   ? '' : 'none';
    row.querySelector('[data-role="filt-relation"]').style.display = type === 'distinct' ? '' : 'none';
}

/* ── Action builder ─────────────────────────────────────────────── */
function addAction(type) {
    actCount++;
    const id  = 'act_' + actCount;
    const div = document.createElement('div');
    div.className = 'builder-row';
    div.id = id;
    const abg = `<span class="type-badge badge-action">${type === 'button' ? 'Link&nbsp;Btn' : 'Raw&nbsp;HTML'}</span>`;
    const rm  = rmBtn(id);

    if (type === 'button') {
        div.innerHTML = abg
            + `<input class="form-control form-control-sm w-lb" placeholder="label: Edit" data-role="act-label">`
            + `<input class="form-control form-control-sm wide" placeholder="/users/{{ $row->id }}/edit" data-role="act-url" style="font-family:monospace;font-size:.74rem;">`
            + `<input class="form-control form-control-sm" placeholder="btn btn-sm btn-primary" data-role="act-class">`
            + `<input class="form-control form-control-sm w-lb" placeholder="icon class (fa)" data-role="act-icon">`
            + rm;
    } else {
        div.innerHTML = abg
            + `<input class="form-control form-control-sm full-width" placeholder="Raw HTML: <a href='/users/{{ $row->id }}'>Edit</a>" data-role="act-raw" style="font-family:monospace;font-size:.74rem;">`
            + rm;
    }
    document.getElementById('actions-list').appendChild(div);
}

/* ── Bulk action builder ─────────────────────────────────────────── */
function addBulkAction() {
    bulkCount++;
    const id  = 'ba_' + bulkCount;
    const div = document.createElement('div');
    div.className = 'builder-row';
    div.id = id;
    div.innerHTML = `<input class="form-control form-control-sm w-md" placeholder="key (delete)" data-role="ba-key">`
        + `<input class="form-control form-control-sm" placeholder="label (Delete Selected)" data-role="ba-label">`
        + rmBtn(id);
    document.getElementById('bulk-actions-list').appendChild(div);
}

/* ── Shared remove ──────────────────────────────────────────────── */
function removeEl(id) { document.getElementById(id)?.remove(); }

/* ── Checkbox → bulk card ───────────────────────────────────────── */
document.getElementById('gen-checkbox').addEventListener('change', function () {
    document.getElementById('bulk-actions-card').style.display = this.checked ? '' : 'none';
});

/* ── Ready-made snippets ─────────────────────────────────────────── */
const SNIPPETS = {
    'badge-cond': `['key' => 'status', 'label' => 'Status', 'raw' => fn($row) => '<span class="badge bg-'.(['active'=>'success','pending'=>'warning','inactive'=>'danger'][$row->status] ?? 'secondary').'">'.e($row->status).'</span>']`,

    'badge-icon': `['key' => 'active', 'label' => 'Active', 'raw' => fn($row) => $row->active
        ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Yes</span>'
        : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>No</span>']`,

    'link-col': `['key' => 'name', 'label' => 'Name', 'raw' => fn($row) => '<a href="/users/'.$row->id.'" class="fw-semibold text-decoration-none">'.e($row->name).'</a>']`,

    'avatar': `['key' => 'user', 'label' => 'User', 'raw' => fn($row) => '<div class="d-flex align-items-center gap-2"><img src="'.e($row->avatar_url).'" class="rounded-circle" width="28" height="28"><span>'.e($row->name).'</span></div>']`,

    'progress': `['key' => 'progress', 'label' => 'Progress', 'raw' => fn($row) => '<div class="progress" style="height:6px;"><div class="progress-bar bg-primary" style="width:'.min((int)$row->progress,100).'%"></div></div><small class="text-muted">'.((int)$row->progress).'%</small>']`,

    'currency': `['key' => 'amount', 'label' => 'Amount', 'raw' => fn($row) => '<span class="fw-bold text-success">PKR '.number_format($row->amount, 2).'</span>']`,

    'date-fmt': `['key' => 'created_at', 'label' => 'Created', 'raw' => fn($row) => '<span class="text-muted">'.\\Carbon\\Carbon::parse($row->created_at)->format('d M Y').'</span>']`,

    'truncate': `['key' => 'notes', 'label' => 'Notes', 'raw' => fn($row) => '<span title="'.e($row->notes).'">'.e(\\Str::limit($row->notes, 40)).'</span>']`,
};

function copySnippet(key) {
    const txt = SNIPPETS[key];
    if (!txt) { return; }
    if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(() => { alert('Snippet copied!'); });
    } else {
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.cssText = 'position:fixed;opacity:0;';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        alert('Snippet copied!');
    }
}

/* ── Code generator ─────────────────────────────────────────────── */
function generateCode() {
    const mode      = document.getElementById('gen-mode').value;
    const model     = document.getElementById('gen-model').value.trim() || 'App\\Models\\YourModel';
    const staticVar = document.getElementById('gen-static-var').value.trim();
    const extraVars = document.getElementById('gen-vars').value.trim();
    const dateCol   = document.getElementById('gen-date-col').value.trim();
    const perPage   = parseInt(document.getElementById('gen-perpage').value) || 10;
    const sortBy    = document.getElementById('gen-sort').value.trim();
    const direction = document.getElementById('gen-direction').value;

    const flags = {
        searchable: document.getElementById('gen-searchable').checked,
        colvisBtn:  document.getElementById('gen-colvis').checked,
        checkbox:   document.getElementById('gen-checkbox').checked,
        index:      document.getElementById('gen-index').checked,
        exportable: document.getElementById('gen-exportable').checked,
        printable:  document.getElementById('gen-printable').checked,
        refreshBtn: document.getElementById('gen-refreshbtn').checked,
    };

    const lines = [];

    /* — data source — */
    if (mode === 'eloquent') {
        lines.push("    'model'   => " + model + "::class");
    } else {
        const v = staticVar.startsWith('$') ? staticVar : ('$' + (staticVar || 'data'));
        lines.push("    'data'    => " + v);
    }

    /* — extra vars — */
    if (extraVars) {
        const pairs = extraVars.split(',').map(p => {
            const [k, ...rest] = p.trim().split('=');
            return "'" + phpEsc(k.trim()) + "' => '" + phpEsc(rest.join('=').trim()) + "'";
        }).filter(Boolean);
        if (pairs.length) { lines.push("    'vars'    => [" + pairs.join(', ') + "]"); }
    }

    /* — date column — */
    if (dateCol) { lines.push("    'dateColumn' => '" + phpEsc(dateCol) + "'"); }

    /* — sort / perPage — */
    if (sortBy)               { lines.push("    'sortBy'        => '" + phpEsc(sortBy) + "'"); }
    if (direction !== 'desc') { lines.push("    'sortDirection' => 'asc'"); }
    if (perPage !== 10)       { lines.push("    'perPage'       => " + perPage); }

    /* — feature flags (only non-defaults) — */
    if (!flags.searchable) { lines.push("    'searchable' => false"); }
    if (!flags.colvisBtn)  { lines.push("    'colvisBtn'  => false"); }
    if (flags.checkbox)    { lines.push("    'checkbox'   => true"); }
    if (flags.index)       { lines.push("    'index'      => true"); }
    if (flags.exportable)  { lines.push("    'exportable' => true"); }
    if (flags.printable)   { lines.push("    'printable'  => true"); }
    if (flags.refreshBtn)  { lines.push("    'refreshBtn' => true"); }

    /* — bulk actions — */
    const baItems = [];
    document.querySelectorAll('#bulk-actions-list .builder-row').forEach(row => {
        const k = row.querySelector('[data-role="ba-key"]')?.value?.trim();
        const l = row.querySelector('[data-role="ba-label"]')?.value?.trim();
        if (k && l) { baItems.push("        '" + phpEsc(k) + "' => '" + phpEsc(l) + "'"); }
    });
    if (baItems.length) {
        lines.push("    'bulkActions' => [\n" + baItems.join(",\n") + "\n    ]");
    }

    /* — columns — */
    const colLines = [];
    document.querySelectorAll('#columns-list .builder-row').forEach(row => {
        const typeBadge = row.querySelector('.type-badge');
        if (!typeBadge) { return; }

        let type = 'db';
        const cls = typeBadge.className;
        if      (cls.includes('badge-auto'))     { type = 'auto';     }
        else if (cls.includes('badge-badge'))    { type = 'badge';    }
        else if (cls.includes('badge-ifelse'))   { type = 'ifelse';   }
        else if (cls.includes('badge-raw'))      { type = 'raw';      }
        else if (cls.includes('badge-closure'))  { type = 'closure';  }
        else if (cls.includes('badge-relation')) { type = 'relation'; }
        else if (cls.includes('badge-json'))     { type = 'json';     }

        const key   = row.querySelector('[data-role="key"]')?.value?.trim();
        const label = row.querySelector('[data-role="label"]')?.value?.trim();
        if (!key) { return; }

        let col = "        ['key' => '" + phpEsc(key) + "', 'label' => '" + phpEsc(label || key) + "'";

        if (type === 'db') {
            const format   = row.querySelector('[data-role="format"]')?.value;
            const sortable = row.querySelector('[data-role="sortable"]')?.value;
            const hide     = row.querySelector('[data-role="hide"]')?.value;
            if (format)               { col += ", 'format'   => '" + format + "'"; }
            if (sortable === 'false') { col += ", 'sortable' => false"; }
            if (hide === 'true')      { col += ", 'hide'     => true"; }

        } else if (type === 'auto') {
            /* Auto-escaped: wraps expression in e() automatically */
            const expr = row.querySelector('[data-role="auto-expr"]')?.value?.trim();
            if (expr) { col += ", 'raw' => fn($row) => e(" + expr + ")"; }

        } else if (type === 'badge') {
            const field      = row.querySelector('[data-role="badge-field"]')?.value?.trim() || '$row->status';
            const badgeMode  = row.querySelector('[data-role="badge-mode"]')?.value;
            if (badgeMode === 'map') {
                const mapStr = row.querySelector('[data-role="badge-map"]')?.value?.trim();
                if (mapStr) {
                    const pairs = mapStr.split(',').map(p => {
                        const parts = p.trim().split('=');
                        const k = parts[0]?.trim() || '';
                        const v = parts[1]?.trim() || 'secondary';
                        return "'" + phpEsc(k) + "'=>'" + phpEsc(v) + "'";
                    }).filter(Boolean);
                    col += ", 'raw' => fn($row) => '<span class=\"badge bg-'.([" + pairs.join(',') + "][" + field + "] ?? 'secondary').'\">'.e(" + field + ").'</span>'";
                }
            } else {
                const color = row.querySelector('[data-role="badge-color"]')?.value || 'primary';
                col += ", 'raw' => fn($row) => '<span class=\"badge bg-" + color + "\">'.e(" + field + ").'</span>'";
            }

        } else if (type === 'ifelse') {
            const cond     = row.querySelector('[data-role="cond"]')?.value?.trim()      || '$row->active';
            const trueVal  = row.querySelector('[data-role="true-val"]')?.value?.trim()  || 'Yes';
            const falseVal = row.querySelector('[data-role="false-val"]')?.value?.trim() || 'No';
            col += ", 'raw' => fn($row) => " + cond
                + " ? '" + phpEsc(trueVal)  + "'"
                + " : '" + phpEsc(falseVal) + "'";

        } else if (type === 'raw') {
            const rawVal = row.querySelector('[data-role="raw"]')?.value?.trim();
            if (rawVal) { col += ", 'raw' => '" + phpEsc(rawVal) + "'"; }

        } else if (type === 'closure') {
            const expr = row.querySelector('[data-role="closure"]')?.value?.trim();
            if (expr) { col += ", 'raw' => fn($row) => " + expr; }

        } else if (type === 'relation') {
            const rel = row.querySelector('[data-role="relation"]')?.value?.trim();
            if (rel) { col += ", 'relation' => '" + phpEsc(rel) + "'"; }

        } else if (type === 'json') {
            const jp = row.querySelector('[data-role="json"]')?.value?.trim();
            if (jp) { col += ", 'json' => '" + phpEsc(jp) + "'"; }
        }

        col += "]";
        colLines.push(col);
    });

    lines.push(
        colLines.length
            ? "    'columns' => [\n" + colLines.join(",\n") + ",\n    ]"
            : "    'columns' => [\n        // Add columns using the builder on the left\n    ]"
    );

    /* — filters — */
    const filtLines = [];
    document.querySelectorAll('#filters-list .builder-row').forEach(row => {
        const key  = row.querySelector('[data-role="filt-key"]')?.value?.trim();
        const type = row.querySelector('[data-role="filt-type"]')?.value;
        const vals = row.querySelector('[data-role="filt-values"]')?.value?.trim();
        const rel  = row.querySelector('[data-role="filt-relation"]')?.value?.trim();
        if (!key) { return; }
        let f = "        '" + phpEsc(key) + "' => ['type' => '" + type + "'";
        if (type === 'select' && vals) {
            const arr = vals.split(',').map(v => "'" + phpEsc(v.trim()) + "'").join(', ');
            f += ", 'values' => [" + arr + "]";
        }
        if (type === 'distinct' && rel) { f += ", 'relation' => '" + phpEsc(rel) + "'"; }
        f += "]";
        filtLines.push(f);
    });
    if (filtLines.length) {
        lines.push("    'filters' => [\n" + filtLines.join(",\n") + ",\n    ]");
    }

    /* — row actions — */
    const actParts = [];
    document.querySelectorAll('#actions-list .builder-row').forEach(row => {
        const actRaw = row.querySelector('[data-role="act-raw"]')?.value?.trim();
        const lbl    = row.querySelector('[data-role="act-label"]')?.value?.trim();
        const url    = row.querySelector('[data-role="act-url"]')?.value?.trim();
        const cls    = row.querySelector('[data-role="act-class"]')?.value?.trim() || 'btn btn-sm btn-primary';
        const ico    = row.querySelector('[data-role="act-icon"]')?.value?.trim();
        if (actRaw)      { actParts.push(actRaw); return; }
        if (lbl || url) {
            let html = '<a href=\\"' + (url || '#') + '\\" class=\\"' + cls + '\\">';
            if (ico) { html += '<i class=\\"' + ico + ' me-1\\"></i>'; }
            html += (lbl || 'Action') + '</a>';
            actParts.push(html);
        }
    });
    if (actParts.length) {
        const actLabel = phpEsc(document.getElementById('gen-actions-label').value.trim() || 'Actions');
        lines.push("    'actions' => [\n        'label' => '" + actLabel + "',\n        'raw'   => '" + phpEsc(actParts.join(' ')) + "'\n    ]");
    }

    /* — output — */
    const code = "@livewire('aftable', [\n" + lines.join(",\n") + ",\n])";
    document.getElementById('gen-placeholder').style.display = 'none';
    document.getElementById('gen-output-wrap').style.display = '';
    document.getElementById('gen-output').textContent = code;

    /* — hints — */
    const hints = [];
    if (flags.checkbox && baItems.length === 0) {
        hints.push('&#9888; Checkboxes enabled but no bulk actions defined.');
    }
    if (flags.exportable) {
        hints.push('&#128230; Export requires <code>maatwebsite/excel</code> (CSV/XLS) or <code>barryvdh/laravel-dompdf</code> (PDF).');
    }
    if (colLines.length === 0) {
        hints.push('&#128161; No columns added — the table will have no data columns.');
    }
    document.getElementById('gen-hint').innerHTML = hints.map(h =>
        `<div style="font-size:.78rem;background:#fff8e1;border:1px solid #ffc107;border-radius:7px;color:#664d03;padding:.45rem .85rem;margin-top:.4rem;">${h}</div>`
    ).join('');
}

/* ── Copy output ─────────────────────────────────────────────────── */
function copyGenCode() {
    const code = document.getElementById('gen-output').textContent;
    const btn  = document.querySelector('.copy-float');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            btn.innerHTML = '\u2705 Copied!';
            setTimeout(() => { btn.innerHTML = '&#128203; Copy'; }, 2000);
        });
    } else {
        const ta = document.createElement('textarea');
        ta.value = code;
        ta.style.cssText = 'position:fixed;opacity:0;';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }
}

/* seed on load */
addColumn('db');
addColumn('db');
@endverbatim

/* ── Schema introspection ────────────────────────────────────────────
   Only wired up when aftable.generator.enabled is true; the endpoints
   404 otherwise, so there is nothing to call. ─────────────────────── */
@if ($schemaEnabled)
@verbatim
const GEN_SCHEMA = {
    list: null, describe: null, current: null,
};
@endverbatim
GEN_SCHEMA.list     = @json(route('aftable.generator.schema'));
GEN_SCHEMA.describe = @json(route('aftable.generator.describe', ['x']));
@verbatim

/* route() gives us .../schema/x — swap the placeholder for the real class. */
function describeUrl(model) {
    return GEN_SCHEMA.describe.replace(/\/x$/, '/' + model.split('\').join('/'));
}

function schemaStatus(text, tone) {
    const el = document.getElementById('gen-schema-status');
    if (!el) return;
    el.innerHTML = text;
    el.style.color = tone === 'bad' ? '#b91c1c' : (tone === 'good' ? '#15803d' : '');
}

async function initSchema() {
    const picker = document.getElementById('gen-model-picker');
    if (!picker) return;

    try {
        const res = await fetch(GEN_SCHEMA.list, {headers: {'Accept': 'application/json'}});
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const {models} = await res.json();

        picker.innerHTML = '<option value="">&mdash; pick a model &mdash;</option>'
            + models.map(m => `<option value="${m}">${m.split('\').pop()} &mdash; ${m}</option>`).join('');

        schemaStatus(models.length
            ? `${models.length} model${models.length === 1 ? '' : 's'} found. Pick one, then <strong>Load columns</strong>.`
            : 'No models found &mdash; check <code>aftable.generator.model_paths</code>.');
    } catch (e) {
        picker.innerHTML = '<option value="">&mdash; unavailable &mdash;</option>';
        schemaStatus('Could not read the schema: ' + e.message, 'bad');
    }
}

function pickModel() {
    const picker = document.getElementById('gen-model-picker');
    const chosen = picker.value;

    if (chosen) document.getElementById('gen-model').value = chosen;

    document.getElementById('gen-schema-btn').disabled = !chosen;
}

async function loadSchema() {
    const model = document.getElementById('gen-model-picker').value;
    if (!model) return;

    const btn = document.getElementById('gen-schema-btn');
    btn.disabled = true;
    schemaStatus('Reading <code>' + model + '</code>&hellip;');

    try {
        const res = await fetch(describeUrl(model), {headers: {'Accept': 'application/json'}});
        if (!res.ok) throw new Error((await res.json()).message || ('HTTP ' + res.status));

        applySchema(await res.json());
    } catch (e) {
        schemaStatus('Could not read that model: ' + e.message, 'bad');
    } finally {
        btn.disabled = false;
    }
}

function applySchema(schema) {
    GEN_SCHEMA.current = schema;

    document.getElementById('columns-list').innerHTML = '';
    document.getElementById('gen-model').value = schema.model;

    let added = 0, needPath = 0, skipped = 0;

    schema.columns.forEach(col => {
        const s = col.suggested;

        /* suggested === null means "sensitive or useless in a table". */
        if (!s) { skipped++; return; }

        if (s.kind === 'json') {
            const row = addColumn('json');
            setField(row, 'key', col.name);
            setField(row, 'label', s.label);
            needPath++;
        } else {
            const row = addColumn('db');
            setField(row, 'key', col.name);
            setField(row, 'label', s.label);
            setField(row, 'format', {date: 'date', boolean: 'boolean', number: 'number'}[s.kind] || '');
            setField(row, 'sortable', s.sortable === false ? 'false' : '');
        }

        added++;
    });

    /* First date-ish column becomes the date-range filter target. */
    const firstDate = schema.columns.find(c => c.suggested && c.suggested.kind === 'date');
    if (firstDate) document.getElementById('gen-date-col').value = firstDate.name;

    renderRelations(schema.relations || []);

    const notes = [`${added} column${added === 1 ? '' : 's'} from <code>${schema.table}</code>`];
    if (needPath) notes.push(`${needPath} JSON column${needPath === 1 ? ' needs a' : 's need'} path`);
    if (skipped)  notes.push(`${skipped} hidden/sensitive skipped`);

    schemaStatus(notes.join(' &middot; '), 'good');
}

function setField(row, role, value) {
    const el = row.querySelector(`[data-role="${role}"]`);
    if (el) el.value = value;
}

/* Relations are offered, not added: a table with every relation eager-loaded
   is exactly the N+1-adjacent thing this package exists to avoid. */
function renderRelations(relations) {
    const wrap = document.getElementById('gen-relations');
    const list = document.getElementById('gen-relations-list');

    if (!relations.length) { wrap.style.display = 'none'; return; }

    const preferred = ['name', 'title', 'label', 'first_name', 'email', 'slug'];

    list.innerHTML = relations.map((rel, i) => {
        const target = preferred.find(p => rel.columns.includes(p)) || rel.columns[0] || 'id';
        const verb   = rel.countable ? 'count' : target;

        return `<button type="button" class="btn btn-sm btn-outline-success"
                        style="font-size:.7rem;padding:.15rem .5rem;"
                        onclick="addRelationColumn(${i})"
                        title="${rel.type} &rarr; ${rel.related}">
                    + ${rel.name}:${verb}
                </button>`;
    }).join('');

    wrap.style.display = '';
}

function addRelationColumn(index) {
    const rel = (GEN_SCHEMA.current?.relations || [])[index];
    if (!rel) return;

    const preferred = ['name', 'title', 'label', 'first_name', 'email', 'slug'];
    const target = preferred.find(p => rel.columns.includes(p)) || rel.columns[0] || 'id';

    const row = addColumn('relation');
    setField(row, 'key', rel.name + '_' + target);
    setField(row, 'label', rel.name.replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
    setField(row, 'relation', rel.name + ':' + target);
}

initSchema();
@endverbatim
@endif

</script>
@endpush
