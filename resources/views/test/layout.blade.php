{{--
    AFTable test-bench layout.

    Deliberately quiet: system fonts, one neutral palette, no gradients, no
    icon-font CDN. The point of this page is to make the TABLE legible, so the
    chrome around it stays out of the way. Bootstrap is still loaded because the
    package's own markup is Bootstrap-classed — but the visual language here is
    overridden to something plainer, and it follows the OS light/dark setting.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AFTable test bench')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ── tokens ─────────────────────────────────────────────── */
        :root {
            --bg:        #ffffff;
            --bg-soft:   #f6f7f9;
            --bg-sunken: #eceef1;
            --line:      #e3e6ea;
            --line-soft: #eef0f3;
            --ink:       #16181d;
            --ink-soft:  #5b6270;
            --ink-faint: #8b929e;
            --accent:    #2f6feb;
            --ok:        #1a7f45;
            --warn:      #9a6700;
            --bad:       #b3261e;
            --radius:    8px;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg:        #14161a;
                --bg-soft:   #1a1d22;
                --bg-sunken: #22262c;
                --line:      #2b3037;
                --line-soft: #23272d;
                --ink:       #e6e8ec;
                --ink-soft:  #a3aab6;
                --ink-faint: #767d89;
                --accent:    #6ea1ff;
                --ok:        #4ec27e;
                --warn:      #e0b341;
                --bad:       #f28b82;
            }
        }

        /* ── base ───────────────────────────────────────────────── */
        html, body { overflow-x: hidden; }

        body {
            background: var(--bg);
            color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        code, kbd, pre { font-family: var(--mono); font-size: .85em; }

        code {
            background: var(--bg-sunken);
            color: var(--ink);
            padding: .1em .35em;
            border-radius: 4px;
        }

        pre {
            background: var(--bg-sunken);
            color: var(--ink);
            padding: .75rem .9rem;
            border-radius: var(--radius);
            overflow-x: auto;
            margin: 0;
        }

        /* ── shell ──────────────────────────────────────────────── */
        .tb-bar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: var(--bg);
            border-bottom: 1px solid var(--line);
            padding: .6rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        .tb-brand {
            font-weight: 650;
            letter-spacing: -.01em;
            color: var(--ink);
            display: flex;
            align-items: baseline;
            gap: .45rem;
        }
        .tb-brand:hover { text-decoration: none; color: var(--ink); }
        .tb-brand span { font-weight: 400; font-size: .78rem; color: var(--ink-faint); }

        .tb-nav { display: flex; gap: .25rem; margin-left: auto; flex-wrap: wrap; }
        .tb-nav a {
            color: var(--ink-soft);
            padding: .3rem .65rem;
            border-radius: 6px;
            font-size: .85rem;
        }
        .tb-nav a:hover { background: var(--bg-soft); color: var(--ink); text-decoration: none; }
        .tb-nav a.is-active { background: var(--bg-sunken); color: var(--ink); font-weight: 550; }

        .tb-main { max-width: 1600px; margin: 0 auto; padding: 1.5rem 1.25rem 4rem; }

        /* ── diagnostics strip ──────────────────────────────────── */
        .tb-diag {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1px;
            background: var(--line);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 1.75rem;
        }
        .tb-diag > div { background: var(--bg); padding: .7rem .9rem; }
        .tb-diag dt {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--ink-faint);
            font-weight: 600;
            margin: 0 0 .15rem;
        }
        .tb-diag dd {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            letter-spacing: -.01em;
        }
        .tb-diag dd small { font-weight: 400; font-size: .72rem; color: var(--ink-faint); }
        .is-ok   { color: var(--ok); }
        .is-warn { color: var(--warn); }
        .is-bad  { color: var(--bad); }

        /* ── sections ───────────────────────────────────────────── */
        .tb-section { margin-bottom: 2.25rem; scroll-margin-top: 4.5rem; }
        .tb-section > header { margin-bottom: .85rem; }
        .tb-section h2 {
            font-size: 1rem;
            font-weight: 650;
            letter-spacing: -.01em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .tb-section h2 .tb-count {
            font-weight: 400;
            font-size: .75rem;
            color: var(--ink-faint);
            font-variant-numeric: tabular-nums;
        }
        .tb-section p.tb-lede {
            margin: .3rem 0 0;
            color: var(--ink-soft);
            font-size: .86rem;
            max-width: 78ch;
        }
        .tb-sub {
            margin: 1.6rem 0 0;
            font-size: .92rem;
            font-weight: 600;
            letter-spacing: -.01em;
        }
        .tb-sub + p.tb-lede { margin-bottom: .85rem; }

        .tb-panel {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--bg);
            padding: 1rem;
        }
        .tb-panel + .tb-panel { margin-top: .85rem; }

        .tb-checks { display: flex; flex-wrap: wrap; gap: .35rem; margin: 0 0 .85rem; padding: 0; list-style: none; }
        .tb-check {
            font-size: .74rem;
            padding: .16rem .5rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            color: var(--ink-soft);
            background: var(--bg-soft);
            font-family: var(--mono);
        }

        /* ── table skin ─────────────────────────────────────────── */
        .tb-main .table { --bs-table-bg: transparent; color: var(--ink); margin-bottom: 0; font-size: .86rem; }
        .tb-main .table > :not(caption) > * > * { border-color: var(--line-soft); padding: .5rem .65rem; }
        .tb-main .table thead th,
        .tb-main .table th.bg-light {
            background: var(--bg-soft) !important;
            color: var(--ink-soft) !important;
            font-weight: 600;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }
        .tb-main .table tbody tr:hover > * { background: var(--bg-soft); }
        .tb-main .table-responsive { border: 1px solid var(--line); border-radius: var(--radius); }
        .tb-main .table-responsive.h-500px { height: auto !important; min-height: 0 !important; }

        .tb-main .form-control,
        .tb-main .form-select {
            background: var(--bg);
            border-color: var(--line);
            color: var(--ink);
            font-size: .84rem;
        }
        .tb-main .form-control:focus,
        .tb-main .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
        }
        .tb-main .form-control::placeholder { color: var(--ink-faint); }
        .tb-main .form-label { color: var(--ink-soft); font-size: .74rem; font-weight: 500; }

        .tb-main .btn { font-size: .82rem; border-radius: 6px; }
        .tb-main .btn-outline,
        .tb-main .btn-outline-secondary {
            border-color: var(--line);
            color: var(--ink-soft);
            background: var(--bg);
        }
        .tb-main .btn-outline:hover,
        .tb-main .btn-outline-secondary:hover { background: var(--bg-soft); color: var(--ink); }
        .tb-main .dropdown-menu {
            background: var(--bg);
            border-color: var(--line);
            font-size: .85rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.10);
        }
        .tb-main .dropdown-item { color: var(--ink); }
        .tb-main .dropdown-item:hover { background: var(--bg-soft); color: var(--ink); }
        .tb-main .pagination { --bs-pagination-font-size: .82rem; margin-bottom: 0; }
        .tb-main .alert { border-radius: var(--radius); font-size: .85rem; border-color: var(--line); }

        footer.tb-foot {
            border-top: 1px solid var(--line);
            padding: 1.25rem;
            text-align: center;
            color: var(--ink-faint);
            font-size: .8rem;
        }

        /* ── section 10 demo skins ──────────────────────────────── */
        /* Proves a host application can restyle the table with nothing but CSS. */
        .bench-scroll { overflow-x: auto; border: 1px dashed var(--accent); border-radius: var(--radius); }
        .bench-th { background: var(--accent) !important; color: #fff !important; font-size: .7rem; }
        .bench-tr:nth-child(odd) > * { background: var(--bg-soft); }
        .bench-striped tbody tr:hover > * { background: color-mix(in srgb, var(--accent) 8%, transparent); }

        /* theme => 'none' emits only these structural hooks. */
        .aft-table { width: 100%; border-collapse: collapse; }
        .aft-th    { text-align: left; font-weight: 600; padding: .5rem; color: var(--ink-soft);
                     border-bottom: 2px solid var(--accent); font-size: .75rem; text-transform: uppercase; }
        .aft-td    { padding: .5rem; border-top: 1px solid var(--line-soft); }
        .aft-scroll{ overflow-x: auto; }

        @media (max-width: 720px) {
            .tb-main { padding: 1rem .75rem 3rem; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <header class="tb-bar">
        <a class="tb-brand" href="{{ route('aftable.test') }}">
            AFTable <span>test bench</span>
        </a>

        <nav class="tb-nav">
            <a href="{{ route('aftable.test') }}" class="{{ request()->routeIs('aftable.test') ? 'is-active' : '' }}">Bench</a>
            <a href="{{ route('aftable.generator') }}" class="{{ request()->routeIs('aftable.generator') ? 'is-active' : '' }}">Generator</a>
            <a href="{{ route('aftable.docs') }}" class="{{ request()->routeIs('aftable.docs') ? 'is-active' : '' }}">Docs</a>
        </nav>
    </header>

    <main class="tb-main">
        @yield('content')
    </main>

    <footer class="tb-foot">
        AFTable &middot; Laravel {{ app()->version() }} &middot; Livewire 4 &middot; PHP {{ PHP_VERSION }}
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    @unless(View::hasSection('withoutLivewireAssets'))
        @livewireScripts
    @endunless
    @stack('scripts')
</body>
</html>
