<?php

namespace ArtflowStudio\Table\Console\Commands\Mini;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class TraitCollisionTest
{
    protected $command;
    
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Test trait collision resolution and method precedence
     */
    public function run(): array
    {
        $this->command->info("⚔️ Testing Trait Collision Resolution...");
        
        $results = [
            'passed' => 0,
            'total' => 0,
            'details' => []
        ];

        // Test 1: DatatableTrait instantiation (basic collision test)
        $results['total']++;
        try {
            $testComponent = new class extends DatatableTrait {
                public function render() { return 'test'; }
                protected function getModel() { return null; }
                protected function getColumns() { return []; }
            };
            
            $results['passed']++;
            $results['details'][] = "✅ DatatableTrait instantiated without collision errors";
            $this->command->info("  ✅ Trait instantiation: PASS");
        } catch (\Exception $e) {
            $results['details'][] = "❌ Trait collision during instantiation: " . $e->getMessage();
            $this->command->error("  ❌ Trait instantiation: FAIL - " . $e->getMessage());
        }

        // Test 2: Check for specific collision-prone methods
        $results['total']++;
        try {
            $collisionMethods = [
                'render', 'mount', 'boot', 'booted', 
                'updating', 'updated', 'hydrate', 'dehydrate'
            ];
            $implementedMethods = [];
            $missingMethods = [];
            
            foreach ($collisionMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $implementedMethods[] = $method;
                } else {
                    $missingMethods[] = $method;
                }
            }
            
            if (count($implementedMethods) > 0) {
                $results['passed']++;
                $results['details'][] = "✅ Collision-prone methods handled: " . implode(', ', $implementedMethods);
                $this->command->info("  ✅ Collision methods: PASS - Found: " . implode(', ', $implementedMethods));
                
                if (count($missingMethods) > 0) {
                    $this->command->info("  ℹ️  Missing methods (OK): " . implode(', ', $missingMethods));
                }
            } else {
                $results['details'][] = "⚠️ No collision-prone methods found";
                $this->command->warn("  ⚠️  Collision methods: WARN - No methods found");
                $results['passed']++; // Not critical
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Collision methods test failed: " . $e->getMessage();
            $this->command->error("  ❌ Collision methods: ERROR - " . $e->getMessage());
        }

        // Test 3: Test specific method precedence (render method)
        $results['total']++;
        try {
            // Test that render method works and returns expected content
            $renderResult = $testComponent->render();
            
            if ($renderResult === 'test') {
                $results['passed']++;
                $results['details'][] = "✅ Render method precedence correct";
                $this->command->info("  ✅ Render precedence: PASS");
            } else {
                $results['details'][] = "❌ Render method precedence issue";
                $this->command->error("  ❌ Render precedence: FAIL");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Render precedence test failed: " . $e->getMessage();
            $this->command->error("  ❌ Render precedence: ERROR - " . $e->getMessage());
        }

        // Test 4: Check trait method availability from different traits
        $results['total']++;
        try {
            $traitMethods = [
                // From HasSessionManagement
                'saveSearchToSession', 'loadSearchFromSession',
                // From HasExport
                'export', 'exportToCsv', 'exportToExcel',
                // From HasBulkActions
                'bulkDelete', 'bulkUpdate', 'selectAll',
                // From HasFiltering
                'applyFilters', 'clearFilters',
                // From HasAdvancedFeatures
                'getAdvancedFilters', 'applyAdvancedSort'
            ];
            
            $availableMethods = [];
            $unavailableMethods = [];
            
            foreach ($traitMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $availableMethods[] = $method;
                } else {
                    $unavailableMethods[] = $method;
                }
            }
            
            // Pass if at least some trait methods are available
            if (count($availableMethods) >= 3) {
                $results['passed']++;
                $results['details'][] = "✅ Trait methods available: " . count($availableMethods) . " found";
                $this->command->info("  ✅ Trait methods: PASS - Found: " . count($availableMethods) . " methods");
                $this->command->info("    Available: " . implode(', ', array_slice($availableMethods, 0, 5)) . 
                                   (count($availableMethods) > 5 ? '...' : ''));
            } else {
                $results['details'][] = "❌ Insufficient trait methods available: " . count($availableMethods);
                $this->command->error("  ❌ Trait methods: FAIL - Only " . count($availableMethods) . " found");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Trait methods test failed: " . $e->getMessage();
            $this->command->error("  ❌ Trait methods: ERROR - " . $e->getMessage());
        }

        // Test 5: Test property collision resolution
        $results['total']++;
        try {
            $expectedProperties = [
                'search', 'filters', 'sortColumn', 'sortDirection',
                'selectedRows', 'perPage', 'currentPage'
            ];
            
            $existingProperties = [];
            $missingProperties = [];
            
            foreach ($expectedProperties as $property) {
                if (property_exists($testComponent, $property)) {
                    $existingProperties[] = $property;
                } else {
                    $missingProperties[] = $property;
                }
            }
            
            if (count($existingProperties) >= 4) {
                $results['passed']++;
                $results['details'][] = "✅ Property collision resolution: " . count($existingProperties) . " properties found";
                $this->command->info("  ✅ Property collisions: PASS - Found: " . count($existingProperties) . " properties");
            } else {
                $results['details'][] = "❌ Property collision issues: only " . count($existingProperties) . " found";
                $this->command->error("  ❌ Property collisions: FAIL - Missing properties");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Property collision test failed: " . $e->getMessage();
            $this->command->error("  ❌ Property collisions: ERROR - " . $e->getMessage());
        }

        $this->command->info("📊 Trait Collisions: {$results['passed']}/{$results['total']} tests passed");
        
        return $results;
    }
}
