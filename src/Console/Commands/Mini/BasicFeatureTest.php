<?php

namespace ArtflowStudio\Table\Console\Commands\Mini;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class BasicFeatureTest
{
    protected $command;
    
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Test basic DatatableTrait functionality
     */
    public function run(): array
    {
        $this->command->info("🔧 Testing Basic Features...");
        
        $results = [
            'passed' => 0,
            'total' => 0,
            'details' => []
        ];

        // Test 1: Trait instantiation
        $results['total']++;
        try {
            $testComponent = new class extends DatatableTrait {
                public function render() { return 'test'; }
                protected function getModel() { return null; }
                protected function getColumns() { return []; }
            };
            
            $results['passed']++;
            $results['details'][] = "✅ DatatableTrait instantiation successful";
            $this->command->info("  ✅ DatatableTrait instantiation: PASS");
        } catch (\Exception $e) {
            $results['details'][] = "❌ DatatableTrait instantiation failed: " . $e->getMessage();
            $this->command->error("  ❌ DatatableTrait instantiation: FAIL - " . $e->getMessage());
        }

        // Test 2: Basic properties exist
        $results['total']++;
        try {
            $properties = ['search', 'sortColumn', 'sortDirection', 'selectedRows'];
            $allExist = true;
            $missing = [];
            
            foreach ($properties as $property) {
                if (!property_exists($testComponent, $property)) {
                    $allExist = false;
                    $missing[] = $property;
                }
            }
            
            if ($allExist) {
                $results['passed']++;
                $results['details'][] = "✅ All basic properties exist";
                $this->command->info("  ✅ Basic properties: PASS");
            } else {
                $results['details'][] = "❌ Missing properties: " . implode(', ', $missing);
                $this->command->error("  ❌ Basic properties: FAIL - Missing: " . implode(', ', $missing));
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Property check failed: " . $e->getMessage();
            $this->command->error("  ❌ Basic properties: ERROR - " . $e->getMessage());
        }

        // Test 3: Search functionality
        $results['total']++;
        try {
            $testComponent->search = 'test search';
            if ($testComponent->search === 'test search') {
                $results['passed']++;
                $results['details'][] = "✅ Search property assignment works";
                $this->command->info("  ✅ Search assignment: PASS");
            } else {
                $results['details'][] = "❌ Search property assignment failed";
                $this->command->error("  ❌ Search assignment: FAIL");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Search test failed: " . $e->getMessage();
            $this->command->error("  ❌ Search assignment: ERROR - " . $e->getMessage());
        }

        // Test 4: Sorting functionality
        $results['total']++;
        try {
            $testComponent->sortColumn = 'name';
            $testComponent->sortDirection = 'desc';
            
            if ($testComponent->sortColumn === 'name' && $testComponent->sortDirection === 'desc') {
                $results['passed']++;
                $results['details'][] = "✅ Sorting properties work";
                $this->command->info("  ✅ Sorting properties: PASS");
            } else {
                $results['details'][] = "❌ Sorting properties failed";
                $this->command->error("  ❌ Sorting properties: FAIL");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Sorting test failed: " . $e->getMessage();
            $this->command->error("  ❌ Sorting properties: ERROR - " . $e->getMessage());
        }

        // Test 5: Method existence check
        $results['total']++;
        try {
            $methods = ['sanitizeSearch', 'getRowPropertyValue', 'getNestedPropertyValue'];
            $allExist = true;
            $missing = [];
            
            foreach ($methods as $method) {
                if (!method_exists($testComponent, $method)) {
                    $allExist = false;
                    $missing[] = $method;
                }
            }
            
            if ($allExist) {
                $results['passed']++;
                $results['details'][] = "✅ All critical methods exist";
                $this->command->info("  ✅ Critical methods: PASS");
            } else {
                $results['details'][] = "❌ Missing methods: " . implode(', ', $missing);
                $this->command->error("  ❌ Critical methods: FAIL - Missing: " . implode(', ', $missing));
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Method check failed: " . $e->getMessage();
            $this->command->error("  ❌ Critical methods: ERROR - " . $e->getMessage());
        }

        $this->command->info("📊 Basic Features: {$results['passed']}/{$results['total']} tests passed");
        
        return $results;
    }
}
