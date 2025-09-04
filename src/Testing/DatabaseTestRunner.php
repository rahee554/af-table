<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\Datatable;

class DatabaseTestRunner extends BaseTestRunner
{
    /**
     * Get test suite name
     */
    public function getName(): string
    {
        return 'Database Tests';
    }

    /**
     * Get test suite description
     */
    public function getDescription(): string
    {
        return 'Tests database queries, performance, and optimization features';
    }

    /**
     * Run all database tests
     */
    public function run(): array
    {
        $this->command->line('');
        $this->command->info("🗄️ Running {$this->getName()}...");
        $this->command->line("   {$this->getDescription()}");
        $this->command->line('');

        // Initialize results
        $this->results = ['passed' => [], 'failed' => []];

        // Run database tests
        $this->runTest('Query Builder Configuration', [$this, 'testQueryBuilderConfiguration']);
        $this->runTest('Basic Pagination', [$this, 'testBasicPagination']);
        $this->runTest('Search Query Building', [$this, 'testSearchQueryBuilding']);
        $this->runTest('Filter Query Building', [$this, 'testFilterQueryBuilding']);
        $this->runTest('Sorting Query Building', [$this, 'testSortingQueryBuilding']);
        $this->runTest('Query Performance', [$this, 'testQueryPerformance']);
        $this->runTest('Memory Usage', [$this, 'testMemoryUsage']);
        $this->runTest('Column Selection Optimization', [$this, 'testColumnSelectionOptimization']);
        $this->runTest('Large Dataset Handling', [$this, 'testLargeDatasetHandling']);
        $this->runTest('Database Connection Health', [$this, 'testDatabaseConnectionHealth']);

        return $this->getResults();
    }

    /**
     * Test query builder configuration
     */
    public function testQueryBuilderConfiguration(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test that the component initializes with correct model
            $this->assertEquals('App\\Models\\User', $component->model);
            $this->assertIsArray($component->columns);
            $this->assertCount(3, $component->columns);

            return true;
        } catch (\Exception $e) {
            $this->log("Query builder configuration test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test basic pagination
     */
    public function testBasicPagination(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test pagination configuration
            $this->assertEquals(10, $component->perPage);
            $this->assertEquals(1, $component->page);

            // Test per page updates
            $component->updatedPerPage(25);
            $this->assertEquals(25, $component->perPage);
            $this->assertEquals(1, $component->page); // Should reset to page 1

            // Test page navigation
            $component->setPage(3, 'page');
            $this->assertEquals(3, $component->page);

            return true;
        } catch (\Exception $e) {
            $this->log("Basic pagination test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test search query building
     */
    public function testSearchQueryBuilding(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test search functionality
            $component->search = 'john';
            $component->updatedSearch();
            
            $this->assertEquals('john', $component->search);
            $this->assertEquals(1, $component->page); // Should reset to page 1

            // Test search reset
            $component->search = '';
            $component->updatedSearch();
            
            $this->assertEquals('', $component->search);

            return true;
        } catch (\Exception $e) {
            $this->log("Search query building test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test filter query building
     */
    public function testFilterQueryBuilding(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ];

            $filters = [
                'status' => ['type' => 'select', 'options' => ['active', 'inactive']],
                'created_at' => ['type' => 'date'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns, $filters);

            // Test filter configuration
            $this->assertEquals($filters, $component->filters);

            // Test filter application
            $component->filterColumn = 'status';
            $component->filterValue = 'active';
            $component->applyFilter();

            $this->assertEquals('status', $component->filterColumn);
            $this->assertEquals('active', $component->filterValue);

            // Test filter clearing
            $component->clearFilter();
            $this->assertEquals('', $component->filterColumn);
            $this->assertEquals('', $component->filterValue);

            return true;
        } catch (\Exception $e) {
            $this->log("Filter query building test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test sorting query building
     */
    public function testSortingQueryBuilding(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test initial sort state
            $this->assertEquals('id', $component->sortColumn);
            $this->assertEquals('desc', $component->sortDirection);

            // Test sort toggling
            $component->toggleSort('name');
            $this->assertEquals('name', $component->sortColumn);
            $this->assertEquals('asc', $component->sortDirection);

            // Test sort direction toggling
            $component->toggleSort('name');
            $this->assertEquals('name', $component->sortColumn);
            $this->assertEquals('desc', $component->sortDirection);

            return true;
        } catch (\Exception $e) {
            $this->log("Sorting query building test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test query performance
     */
    public function testQueryPerformance(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User'],
            ];

            $metrics = $this->measureTime(function() use ($columns) {
                $component = new Datatable();
                $component->mount('App\\Models\\Post', $columns);
                
                // Simulate query building
                $component->search = 'test';
                $component->filterColumn = 'status';
                $component->filterValue = 'active';
                $component->sortColumn = 'created_at';
                $component->sortDirection = 'desc';
                
                return $component;
            });

            $this->log("Query building time: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Performance should be reasonable
            $this->assertLessThan(50, $metrics['time'], 'Query building should be under 50ms');

            return true;
        } catch (\Exception $e) {
            $this->log("Query performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test memory usage
     */
    public function testMemoryUsage(): bool
    {
        try {
            $initialMemory = memory_get_usage(true);

            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            $finalMemory = memory_get_usage(true);
            $memoryUsed = $finalMemory - $initialMemory;

            $this->log("Memory used by component: " . round($memoryUsed / 1024, 2) . "KB");

            // Memory usage should be reasonable (under 1MB)
            $this->assertLessThan(1024 * 1024, $memoryUsed, 'Component should use less than 1MB of memory');

            return true;
        } catch (\Exception $e) {
            $this->log("Memory usage test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test column selection optimization
     */
    public function testColumnSelectionOptimization(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test that only necessary columns are selected
            $selectColumns = $this->invokeMethod($component, 'calculateSelectColumns', [$columns]);
            
            $this->assertContains('id', $selectColumns);
            $this->assertContains('name', $selectColumns);
            $this->assertContains('user_id', $selectColumns);
            
            // Should not contain unnecessary columns
            $this->assertFalse(in_array('*', $selectColumns), "Select columns should not contain '*'");

            return true;
        } catch (\Exception $e) {
            $this->log("Column selection optimization test failed: " . $e->getMessage(), 'error');
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
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test with large per page values
            $component->updatedPerPage(1000);
            $this->assertEquals(1000, $component->perPage);

            // Test with very large page numbers
            $component->setPage(100, 'page');
            $this->assertEquals(100, $component->page);

            return true;
        } catch (\Exception $e) {
            $this->log("Large dataset handling test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test database connection health
     */
    public function testDatabaseConnectionHealth(): bool
    {
        try {
            // Test database connection
            $connection = \DB::connection();
            $this->assertNotNull($connection);

            // Test basic query
            $result = \DB::select('SELECT 1 as test');
            $this->assertIsArray($result);
            $this->assertCount(1, $result);
            $this->assertEquals(1, $result[0]->test);

            $this->log("Database connection: OK");
            $this->log("Database driver: " . config('database.default'));

            return true;
        } catch (\Exception $e) {
            $this->log("Database connection health test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Helper method to invoke protected methods for testing
     */
    private function invokeMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
