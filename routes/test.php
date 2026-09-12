<?php

use ArtflowStudio\Table\Support\SchemaIntrospector;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Demo, docs and generator routes
|--------------------------------------------------------------------------
|
| TableServiceProvider only loads this file when `aftable.routes.enabled` is
| true AND the application is not in production. The prefix and middleware
| come from config so a host application can move or protect these pages —
| both keys existed before 1.6.0 but were ignored here.
|
*/

Route::middleware((array) config('aftable.routes.middleware', ['web']))
    ->prefix((string) config('aftable.routes.prefix', 'aftable'))
    ->group(function () {
        Route::view('/test', 'artflow-table::test.index')->name('aftable.test');
        Route::view('/docs', 'artflow-table::docs')->name('aftable.docs');
        Route::view('/generator', 'artflow-table::generator')->name('aftable.generator');

        // Schema introspection for the generator. Gated a second time, on its
        // own flag: the demo pages read the package's own test_* tables, but
        // this one reads YOUR schema and hands back table, column and relation
        // names. Off unless `aftable.generator.enabled` is explicitly true.
        Route::get('/generator/schema', function () {
            abort_unless(SchemaIntrospector::enabled(), 404);

            return response()->json(['models' => array_values(SchemaIntrospector::models())]);
        })->name('aftable.generator.schema');

        Route::get('/generator/schema/{model}', function (string $model) {
            abort_unless(SchemaIntrospector::enabled(), 404);

            try {
                return response()->json(SchemaIntrospector::describe(str_replace('/', '\\', $model)));
            } catch (InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 404);
            }
        })->where('model', '.*')->name('aftable.generator.describe');
    });
