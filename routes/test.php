<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Apply web middleware for session/CSRF support
Route::middleware(['web'])->prefix('aftable')->group(function () {
    // Test environment home
    Route::get('/test', function () {
        return view('artflow-table::test.index');
    })->name('aftable.test');

    // Run performance tests endpoint
    Route::post('/test/run-performance', function () {
        try {
            // Run the performance test suite
            Artisan::call('af-table:test-trait', ['--suite' => 'performance']);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'output' => 'Error running tests. Please check the logs.',
            ], 500);
        }
    })->name('aftable.test.run-performance');
});
