<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The package caches things that are expensive and change rarely: the
    | distinct values behind select/distinct filters, per-model column
    | metadata, and schema lookups.
    |
    | `store` = null means AUTO-DETECT: if the application has a working Redis
    | cache store the package uses it (Redis gives tag support, so the package
    | can invalidate precisely instead of clearing broadly); otherwise it falls
    | back to the application's default store and degrades gracefully.
    |
    | Set an explicit store name to opt out of detection entirely.
    |
    */

    'cache' => [
        // null = auto-detect Redis, else the app default. Or name a store: 'redis', 'file', 'array'…
        'store' => env('AFTABLE_CACHE_STORE'),

        // Master switch. false disables every package cache read/write.
        'enabled' => env('AFTABLE_CACHE_ENABLED', true),

        // Prefix for every key this package writes, so clearing is scoped.
        'prefix' => env('AFTABLE_CACHE_PREFIX', 'aftable'),

        // Per-kind TTLs in seconds.
        'ttl' => [
            'default' => 300,
            'distinct_values' => 600,
            'schema' => 3600,
        ],

        // Redis auto-detection. The probe result is remembered for
        // `probe_ttl` seconds so a dead Redis does not cost a connection
        // attempt on every request.
        'redis' => [
            'autodetect' => env('AFTABLE_CACHE_AUTODETECT_REDIS', true),
            'probe_ttl' => 60,
            'connection' => env('AFTABLE_REDIS_CONNECTION', 'cache'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    */

    'query' => [
        // Hard ceiling on rows returned for a distinct-value filter dropdown.
        'max_distinct_values' => 1000,

        // Selectable page sizes.
        'per_page_options' => [10, 25, 50, 100, 500],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Livewire treats every public method on a component as an action any
    | visitor can invoke with arbitrary arguments. This component composes 27
    | traits and exposes 400+ public methods, almost all of them internal
    | helpers. `guard_actions` closes that surface to the deliberate UI actions
    | listed in Support/GuardsActions::ALLOWED.
    |
    | If your application legitimately calls another method from the browser,
    | name it in `extra_actions` rather than turning the guard off.
    |
    */

    'security' => [
        'guard_actions' => env('AFTABLE_GUARD_ACTIONS', true),
        'extra_actions' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | `preset` is the app-wide default: 'bootstrap' (the historical look),
    | 'tailwind', 'ui-flow' (Artflow Studio's admin theme), 'none' (structural
    | aft-* hooks only), or one registered with Theme::register(). `overrides` re-skins individual slots app-wide.
    |
    | Per table:  @livewire('aftable', ['theme' => 'tailwind'])
    |             @livewire('aftable', ['classes' => ['table' => 'table my-look']])
    |
    | See docs/THEMING.md.
    |
    */

    'theme' => [
        'preset' => env('AFTABLE_THEME', 'bootstrap'),
        'overrides' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo routes
    |--------------------------------------------------------------------------
    |
    | /aftable/test, /aftable/docs and /aftable/generator. These query the
    | package's `test_*` demo tables and expose a code generator, so they are
    | OFF unless explicitly enabled, and never registered in production.
    |
    */

    'routes' => [
        'enabled' => env('AFTABLE_ROUTES_ENABLED', true),
        'prefix' => 'aftable',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Code generator
    |--------------------------------------------------------------------------
    |
    | /aftable/generator can read your application's own schema so it offers
    | real models and real columns instead of asking you to type them.
    |
    | That means it will happily list your table names, your column names and
    | your relation graph to whoever loads the page, so it is OFF by default
    | and — like the demo routes — never registered in production. Turn it on
    | locally, generate your Blade, turn it off again.
    |
    */

    'generator' => [
        'enabled' => env('AFTABLE_GENERATOR_ENABLED', false),

        // Where to look for Eloquent models. Either a plain list of paths
        // (which use `model_namespace` below), or namespace => path pairs:
        //
        //     'model_paths' => [
        //         'App\Models'   => app_path('Models'),
        //         'Modules\Blog' => base_path('modules/Blog/src'),
        //     ],
        'model_paths' => [
            app_path('Models'),
        ],

        'model_namespace' => 'App\Models',
    ],

];
