<?php

use Illuminate\Support\Facades\Route;
use ArtflowStudio\Table\Http\Controllers\TestController;

// Test routes for AF Table package
Route::prefix('af-table')->name('af-table.')->group(function () {
    
    // Test pages for datatable components
    Route::get('/test', [TestController::class, 'testPage'])->name('test');
    Route::get('/test-trait', [TestController::class, 'testTraitPage'])->name('test-trait');
    
    // Individual test model pages
    Route::get('/test-departments', [TestController::class, 'testDepartments'])->name('test-departments');
    Route::get('/test-users', [TestController::class, 'testUsers'])->name('test-users');
    Route::get('/test-projects', [TestController::class, 'testProjects'])->name('test-projects');
    Route::get('/test-tasks', [TestController::class, 'testTasks'])->name('test-tasks');
    
    // Comprehensive test suite
    Route::get('/test-comprehensive', [TestController::class, 'testComprehensive'])->name('test-comprehensive');
    
    // Performance testing pages
    Route::get('/test-performance', [TestController::class, 'testPerformance'])->name('test-performance');
    Route::get('/test-large-dataset', [TestController::class, 'testLargeDataset'])->name('test-large-dataset');
    
    // Feature-specific tests
    Route::get('/test-json', [TestController::class, 'testJsonColumns'])->name('test-json');
    Route::get('/test-relations', [TestController::class, 'testRelations'])->name('test-relations');
    Route::get('/test-export', [TestController::class, 'testExport'])->name('test-export');
    Route::get('/test-filtering', [TestController::class, 'testFiltering'])->name('test-filtering');
    Route::get('/test-search', [TestController::class, 'testSearch'])->name('test-search');
    Route::get('/test-sorting', [TestController::class, 'testSorting'])->name('test-sorting');
    
    // Security and validation tests
    Route::get('/test-security', [TestController::class, 'testSecurity'])->name('test-security');
    Route::get('/test-validation', [TestController::class, 'testValidation'])->name('test-validation');
    
    // API endpoints for testing
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/test-data/{model}', [TestController::class, 'getTestData'])->name('test-data');
        Route::post('/test-export/{model}', [TestController::class, 'testExportData'])->name('test-export');
        Route::get('/health-check', [TestController::class, 'healthCheck'])->name('health-check');
    });
    
    // Documentation and examples
    Route::get('/docs', [TestController::class, 'documentation'])->name('docs');
    Route::get('/examples', [TestController::class, 'examples'])->name('examples');
    Route::get('/changelog', [TestController::class, 'changelog'])->name('changelog');
});

// Legacy routes for backward compatibility
Route::get('/test-datatable-trait', [TestController::class, 'testTraitPage'])->name('test-datatable-trait');
Route::get('/af-table-test', [TestController::class, 'testPage'])->name('af-table-test');