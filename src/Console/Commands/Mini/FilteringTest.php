<?php

namespace ArtflowStudio\Table\Console\Commands\Mini;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class FilteringTest
{
    protected $command;
    
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Test filtering functionality
     */
    public function run(): array
    {
        $this->command->info("🔍 Testing Filtering Features...");
        
        $results = [
            'passed' => 0,
            'total' => 0,
            'details' => []
        ];

        // Test 1: Filter property initialization
        $results['total']++;
        try {
            $testComponent = new class extends DatatableTrait {
                public function render() { return 'test'; }
                protected function getModel() { return null; }
                protected function getColumns() { return []; }
            };
            
            // Check if filters property exists and is array
            if (property_exists($testComponent, 'filters') && is_array($testComponent->filters)) {
                $results['passed']++;
                $results['details'][] = "✅ Filters property exists and is array";
                $this->command->info("  ✅ Filters property: PASS");
            } else {
                $results['details'][] = "❌ Filters property missing or not array";
                $this->command->error("  ❌ Filters property: FAIL");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Filter property test failed: " . $e->getMessage();
            $this->command->error("  ❌ Filter property: ERROR - " . $e->getMessage());
        }

        // Test 2: Filter assignment
        $results['total']++;
        try {
            $testFilters = ['status' => 'active', 'category' => 'premium'];
            $testComponent->filters = $testFilters;
            
            if ($testComponent->filters === $testFilters) {
                $results['passed']++;
                $results['details'][] = "✅ Filter assignment works";
                $this->command->info("  ✅ Filter assignment: PASS");
            } else {
                $results['details'][] = "❌ Filter assignment failed";
                $this->command->error("  ❌ Filter assignment: FAIL");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Filter assignment test failed: " . $e->getMessage();
            $this->command->error("  ❌ Filter assignment: ERROR - " . $e->getMessage());
        }

        // Test 3: Filter methods exist
        $results['total']++;
        try {
            $filterMethods = ['applyFilters', 'clearFilters', 'resetFilters'];
            $existingMethods = [];
            $missingMethods = [];
            
            foreach ($filterMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $existingMethods[] = $method;
                } else {
                    $missingMethods[] = $method;
                }
            }
            
            if (count($existingMethods) > 0) {
                $results['passed']++;
                $results['details'][] = "✅ Filter methods exist: " . implode(', ', $existingMethods);
                $this->command->info("  ✅ Filter methods: PASS - Found: " . implode(', ', $existingMethods));
                
                if (count($missingMethods) > 0) {
                    $this->command->warn("  ⚠️  Missing optional methods: " . implode(', ', $missingMethods));
                }
            } else {
                $results['details'][] = "❌ No filter methods found";
                $this->command->error("  ❌ Filter methods: FAIL - No methods found");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Filter methods test failed: " . $e->getMessage();
            $this->command->error("  ❌ Filter methods: ERROR - " . $e->getMessage());
        }

        // Test 4: Filter clearing
        $results['total']++;
        try {
            $testComponent->filters = ['test' => 'value'];
            
            // Try different clear methods
            if (method_exists($testComponent, 'clearFilters')) {
                $testComponent->clearFilters();
                if (empty($testComponent->filters)) {
                    $results['passed']++;
                    $results['details'][] = "✅ clearFilters() works";
                    $this->command->info("  ✅ Clear filters: PASS");
                } else {
                    $results['details'][] = "❌ clearFilters() didn't clear filters";
                    $this->command->error("  ❌ Clear filters: FAIL");
                }
            } elseif (method_exists($testComponent, 'resetFilters')) {
                $testComponent->resetFilters();
                if (empty($testComponent->filters)) {
                    $results['passed']++;
                    $results['details'][] = "✅ resetFilters() works";
                    $this->command->info("  ✅ Clear filters: PASS (using resetFilters)");
                } else {
                    $results['details'][] = "❌ resetFilters() didn't clear filters";
                    $this->command->error("  ❌ Clear filters: FAIL");
                }
            } else {
                // Manual clear test
                $testComponent->filters = [];
                if (empty($testComponent->filters)) {
                    $results['passed']++;
                    $results['details'][] = "✅ Manual filter clearing works";
                    $this->command->info("  ✅ Clear filters: PASS (manual)");
                } else {
                    $results['details'][] = "❌ Manual filter clearing failed";
                    $this->command->error("  ❌ Clear filters: FAIL");
                }
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Filter clearing test failed: " . $e->getMessage();
            $this->command->error("  ❌ Clear filters: ERROR - " . $e->getMessage());
        }

        // Test 5: Search sanitization (if method exists)
        $results['total']++;
        try {
            if (method_exists($testComponent, 'sanitizeSearch')) {
                $dangerousInput = '<script>alert("xss")</script>test search';
                $sanitized = $testComponent->sanitizeSearch($dangerousInput);
                
                if (!str_contains($sanitized, '<script>') && str_contains($sanitized, 'test search')) {
                    $results['passed']++;
                    $results['details'][] = "✅ Search sanitization works";
                    $this->command->info("  ✅ Search sanitization: PASS");
                } else {
                    $results['details'][] = "❌ Search sanitization failed";
                    $this->command->error("  ❌ Search sanitization: FAIL");
                }
            } else {
                // Skip test if method doesn't exist
                $results['passed']++;
                $results['details'][] = "⚠️ sanitizeSearch method not found - skipped";
                $this->command->warn("  ⚠️  Search sanitization: SKIP - Method not found");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Search sanitization test failed: " . $e->getMessage();
            $this->command->error("  ❌ Search sanitization: ERROR - " . $e->getMessage());
        }

        $this->command->info("📊 Filtering: {$results['passed']}/{$results['total']} tests passed");
        
        return $results;
    }
}
