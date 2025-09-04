<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\Datatable;

class PerformanceTestRunner extends BaseTestRunner
{
    /**
     * Get test suite name
     */
    public function getName(): string
    {
        return 'Performance Tests';
    }

    /**
     * Get test suite description
     */
    public function getDescription(): string
    {
        return 'Tests performance benchmarks, memory usage, and query optimization';
    }

    /**
     * Run all performance tests
     */
    public function run(): array
    {
        $this->command->line('');
        $this->command->info("📊 Running {$this->getName()}...");
        $this->command->line("   {$this->getDescription()}");
        $this->command->line('');

        // Initialize results
        $this->results = ['passed' => [], 'failed' => []];

        // Run performance tests
        $this->runTest('Component Instantiation Performance', [$this, 'testComponentInstantiationPerformance']);
        $this->runTest('Large Dataset Handling', [$this, 'testLargeDatasetHandling']);
        $this->runTest('Memory Usage', [$this, 'testMemoryUsage']);
        $this->runTest('Search Performance', [$this, 'testSearchPerformance']);
        $this->runTest('Sort Performance', [$this, 'testSortPerformance']);
        $this->runTest('Filter Performance', [$this, 'testFilterPerformance']);
        $this->runTest('JSON Extraction Performance', [$this, 'testJsonExtractionPerformance']);
        $this->runTest('Column Visibility Performance', [$this, 'testColumnVisibilityPerformance']);
        $this->runTest('Concurrent Operations', [$this, 'testConcurrentOperations']);
        $this->runTest('Cache Performance', [$this, 'testCachePerformance']);

        return $this->getResults();
    }

    /**
     * Test component instantiation performance
     */
    public function testComponentInstantiationPerformance(): bool
    {
        try {
            $columns = $this->createLargeColumnSet(50); // 50 columns
            
            $metrics = $this->measureTime(function() use ($columns) {
                $component = new Datatable();
                $component->mount('App\\Models\\User', $columns);
                return $component;
            });

            $this->log("Instantiation time: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Performance thresholds
            $this->assertLessThan(1000, $metrics['time'], 'Instantiation should be under 1 second');
            $this->assertLessThan(5 * 1024 * 1024, $metrics['memory'], 'Memory usage should be under 5MB');

            return true;
        } catch (\Exception $e) {
            $this->log("Component instantiation performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test large dataset handling
     */
    public function testLargeDatasetHandling(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Simulate large dataset processing
            $largeData = $this->createMockCollection(1000);
            
            $metrics = $this->measureTime(function() use ($component, $largeData) {
                // Simulate processing large dataset
                foreach ($largeData as $row) {
                    // Simulate row processing
                    $processed = [
                        'id' => $row->id,
                        'name' => $row->name,
                        'email' => $row->email,
                    ];
                }
                return true;
            });

            $this->log("Large dataset processing time: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Performance thresholds for 1000 records
            $this->assertLessThan(5000, $metrics['time'], 'Large dataset processing should be under 5 seconds');
            $this->assertLessThan(10 * 1024 * 1024, $metrics['memory'], 'Memory usage should be under 10MB');

            return true;
        } catch (\Exception $e) {
            $this->log("Large dataset handling test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test memory usage
     */
    public function testMemoryUsage(): bool
    {
        try {
            $initialMemory = memory_get_usage();
            
            // Create multiple components to test memory usage
            $components = [];
            for ($i = 0; $i < 10; $i++) {
                $component = new Datatable();
                $component->mount('App\\Models\\User', [
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'name', 'label' => 'Name'],
                ]);
                $components[] = $component;
            }

            $finalMemory = memory_get_usage();
            $memoryUsed = $finalMemory - $initialMemory;
            $peakMemory = memory_get_peak_usage();

            $this->log("Memory used for 10 components: " . round($memoryUsed / 1024, 2) . "KB");
            $this->log("Peak memory usage: " . round($peakMemory / 1024 / 1024, 2) . "MB");

            // Memory thresholds
            $this->assertLessThan(5 * 1024 * 1024, $memoryUsed, 'Memory usage for 10 components should be under 5MB');
            $this->assertLessThan(50 * 1024 * 1024, $peakMemory, 'Peak memory should be under 50MB');

            // Cleanup
            unset($components);

            return true;
        } catch (\Exception $e) {
            $this->log("Memory usage test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test search performance
     */
    public function testSearchPerformance(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ]);

            // Test search operations
            $searchTerms = ['test', 'user', 'example', 'admin', ''];
            
            foreach ($searchTerms as $term) {
                $metrics = $this->measureTime(function() use ($component, $term) {
                    $component->search = $term;
                    $component->updatedSearch();
                    return true;
                });

                $this->log("Search '{$term}' took: {$metrics['time']}ms");
                
                // Each search should be fast
                $this->assertLessThan(100, $metrics['time'], "Search for '{$term}' should be under 100ms");
            }

            return true;
        } catch (\Exception $e) {
            $this->log("Search performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test sort performance
     */
    public function testSortPerformance(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'created_at', 'label' => 'Created'],
            ]);

            $columns = ['id', 'name', 'email', 'created_at'];
            
            foreach ($columns as $column) {
                $metrics = $this->measureTime(function() use ($component, $column) {
                    $component->toggleSort($column);
                    return true;
                });

                $this->log("Sort by '{$column}' took: {$metrics['time']}ms");
                
                // Sorting should be fast
                $this->assertLessThan(50, $metrics['time'], "Sort by '{$column}' should be under 50ms");
            }

            return true;
        } catch (\Exception $e) {
            $this->log("Sort performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test filter performance
     */
    public function testFilterPerformance(): bool
    {
        try {
            $filters = [
                'status' => ['type' => 'select'],
                'created_at' => ['type' => 'date'],
                'name' => ['type' => 'text'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'created_at', 'label' => 'Created'],
            ], $filters);

            $filterTests = [
                ['column' => 'status', 'value' => 'active'],
                ['column' => 'name', 'value' => 'test'],
                ['column' => 'created_at', 'value' => '2024-01-01'],
            ];
            
            foreach ($filterTests as $test) {
                $metrics = $this->measureTime(function() use ($component, $test) {
                    $component->filterColumn = $test['column'];
                    $component->filterValue = $test['value'];
                    return true;
                });

                $this->log("Filter {$test['column']}='{$test['value']}' took: {$metrics['time']}ms");
                
                // Filtering should be fast
                $this->assertLessThan(100, $metrics['time'], "Filter operation should be under 100ms");
            }

            return true;
        } catch (\Exception $e) {
            $this->log("Filter performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON extraction performance
     */
    public function testJsonExtractionPerformance(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'data', 'json' => 'name', 'label' => 'Name'],
                ['key' => 'data', 'json' => 'contact.email', 'label' => 'Email'],
            ]);

            $mockRow = $this->createMockModel([
                'data' => json_encode([
                    'name' => 'John Doe',
                    'contact' => ['email' => 'john@example.com'],
                    'preferences' => ['theme' => 'dark'],
                    'metadata' => ['created_by' => 'system']
                ])
            ]);

            // Test multiple JSON extractions
            $paths = ['name', 'contact.email', 'preferences.theme', 'metadata.created_by'];
            
            foreach ($paths as $path) {
                $metrics = $this->measureTime(function() use ($component, $mockRow, $path) {
                    return $component->extractJsonValue($mockRow, 'data', $path);
                });

                $this->log("JSON extraction '{$path}' took: {$metrics['time']}ms");
                
                // JSON extraction should be very fast
                $this->assertLessThan(10, $metrics['time'], "JSON extraction should be under 10ms");
            }

            return true;
        } catch (\Exception $e) {
            $this->log("JSON extraction performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test column visibility performance
     */
    public function testColumnVisibilityPerformance(): bool
    {
        try {
            $columns = $this->createLargeColumnSet(20);
            
            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test toggling multiple columns
            $columnKeys = array_keys($component->columns);
            
            foreach ($columnKeys as $key) {
                $metrics = $this->measureTime(function() use ($component, $key) {
                    $component->toggleColumnVisibility($key);
                    return true;
                });

                $this->log("Toggle column '{$key}' took: {$metrics['time']}ms");
                
                // Column visibility toggle should be fast
                $this->assertLessThan(20, $metrics['time'], "Column visibility toggle should be under 20ms");
            }

            return true;
        } catch (\Exception $e) {
            $this->log("Column visibility performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test concurrent operations
     */
    public function testConcurrentOperations(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ]);

            // Simulate concurrent operations
            $metrics = $this->measureTime(function() use ($component) {
                // Multiple operations at once
                $component->search = 'test';
                $component->updatedSearch();
                $component->toggleSort('name');
                $component->filterColumn = 'email';
                $component->filterValue = 'test@example.com';
                $component->records = 25;
                $component->updatedrecords();
                
                return true;
            });

            $this->log("Concurrent operations took: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Concurrent operations should still be reasonably fast
            $this->assertLessThan(500, $metrics['time'], 'Concurrent operations should be under 500ms');

            return true;
        } catch (\Exception $e) {
            $this->log("Concurrent operations test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test cache performance
     */
    public function testCachePerformance(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'status', 'label' => 'Status'],
            ]);

            // Test cache operations (simulated)
            $metrics = $this->measureTime(function() use ($component) {
                // Simulate cache operations using reflection
                $reflection = new \ReflectionClass($component);
                $method = $reflection->getMethod('getColumnVisibilitySessionKey');
                $method->setAccessible(true);
                $sessionKey = $method->invoke($component);
                $this->assertNotNull($sessionKey);
                
                // Simulate multiple session operations
                for ($i = 0; $i < 10; $i++) {
                    $component->toggleColumnVisibility('name');
                    $component->toggleColumnVisibility('status');
                }
                
                return true;
            });

            $this->log("Cache operations took: {$metrics['time']}ms");

            // Cache operations should be very fast
            $this->assertLessThan(100, $metrics['time'], 'Cache operations should be under 100ms');

            return true;
        } catch (\Exception $e) {
            $this->log("Cache performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Create a large set of columns for testing
     */
    private function createLargeColumnSet(int $count): array
    {
        $columns = [];
        for ($i = 1; $i <= $count; $i++) {
            $columns[] = [
                'key' => "column_{$i}",
                'label' => "Column {$i}",
                'sortable' => $i % 2 === 0,
                'searchable' => $i % 3 === 0,
            ];
        }
        return $columns;
    }
}
