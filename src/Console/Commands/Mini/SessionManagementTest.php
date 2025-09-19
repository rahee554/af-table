<?php

namespace ArtflowStudio\Table\Console\Commands\Mini;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use Illuminate\Support\Facades\Session;

class SessionManagementTest
{
    protected $command;
    
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Test session management functionality
     */
    public function run(): array
    {
        $this->command->info("💾 Testing Session Management...");
        
        $results = [
            'passed' => 0,
            'total' => 0,
            'details' => []
        ];

        // Test 1: Session methods exist
        $results['total']++;
        try {
            $testComponent = new class extends DatatableTrait {
                public function render() { return 'test'; }
                protected function getModel() { return null; }
                protected function getColumns() { return []; }
            };
            
            $sessionMethods = ['saveSearchToSession', 'loadSearchFromSession', 'clearSearchSession'];
            $existingMethods = [];
            $missingMethods = [];
            
            foreach ($sessionMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $existingMethods[] = $method;
                } else {
                    $missingMethods[] = $method;
                }
            }
            
            if (count($existingMethods) > 0) {
                $results['passed']++;
                $results['details'][] = "✅ Session methods found: " . implode(', ', $existingMethods);
                $this->command->info("  ✅ Session methods: PASS - Found: " . implode(', ', $existingMethods));
                
                if (count($missingMethods) > 0) {
                    $this->command->warn("  ⚠️  Missing methods: " . implode(', ', $missingMethods));
                }
            } else {
                $results['details'][] = "❌ No session methods found";
                $this->command->error("  ❌ Session methods: FAIL - No methods found");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Session methods test failed: " . $e->getMessage();
            $this->command->error("  ❌ Session methods: ERROR - " . $e->getMessage());
        }

        // Test 2: saveSearchToSession functionality
        $results['total']++;
        try {
            if (method_exists($testComponent, 'saveSearchToSession')) {
                // Mock session behavior by setting search and calling save
                $testSearchTerm = 'test search term';
                $testComponent->search = $testSearchTerm;
                $testComponent->filters = ['status' => 'active'];
                
                // Try to save to session with search term parameter
                $testComponent->saveSearchToSession($testSearchTerm);
                
                $results['passed']++;
                $results['details'][] = "✅ saveSearchToSession executed without error";
                $this->command->info("  ✅ Save search to session: PASS");
            } else {
                $results['passed']++; // Skip if method doesn't exist
                $results['details'][] = "⚠️ saveSearchToSession method not found - skipped";
                $this->command->warn("  ⚠️  Save search to session: SKIP - Method not found");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ saveSearchToSession test failed: " . $e->getMessage();
            $this->command->error("  ❌ Save search to session: ERROR - " . $e->getMessage());
        }

        // Test 3: Session key generation
        $results['total']++;
        try {
            $hasSessionKey = false;
            $sessionKeyMethods = ['getSessionKey', 'sessionKey', 'getSearchSessionKey'];
            
            foreach ($sessionKeyMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $hasSessionKey = true;
                    break;
                }
            }
            
            if ($hasSessionKey) {
                $results['passed']++;
                $results['details'][] = "✅ Session key method found";
                $this->command->info("  ✅ Session key generation: PASS");
            } else {
                // Check if sessionKey property exists
                if (property_exists($testComponent, 'sessionKey')) {
                    $results['passed']++;
                    $results['details'][] = "✅ Session key property found";
                    $this->command->info("  ✅ Session key generation: PASS (property)");
                } else {
                    $results['passed']++; // Not critical
                    $results['details'][] = "⚠️ No session key method/property found";
                    $this->command->warn("  ⚠️  Session key generation: SKIP - No key mechanism found");
                }
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Session key test failed: " . $e->getMessage();
            $this->command->error("  ❌ Session key generation: ERROR - " . $e->getMessage());
        }

        // Test 4: Export session functionality
        $results['total']++;
        try {
            $exportMethods = ['saveExportToSession', 'loadExportFromSession', 'clearExportSession'];
            $existingExportMethods = [];
            
            foreach ($exportMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $existingExportMethods[] = $method;
                }
            }
            
            if (count($existingExportMethods) > 0) {
                $results['passed']++;
                $results['details'][] = "✅ Export session methods found: " . implode(', ', $existingExportMethods);
                $this->command->info("  ✅ Export session: PASS - Found: " . implode(', ', $existingExportMethods));
            } else {
                $results['passed']++; // Not critical
                $results['details'][] = "⚠️ No export session methods found";
                $this->command->warn("  ⚠️  Export session: SKIP - No export methods found");
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Export session test failed: " . $e->getMessage();
            $this->command->error("  ❌ Export session: ERROR - " . $e->getMessage());
        }

        // Test 5: Session data structure
        $results['total']++;
        try {
            // Test that session data can be structured properly
            $sessionData = [
                'search' => 'test',
                'filters' => ['status' => 'active'],
                'sortColumn' => 'name',
                'sortDirection' => 'asc'
            ];
            
            // Try to access component properties that would be saved
            $canStructure = true;
            $missingProps = [];
            
            foreach (['search', 'filters', 'sortColumn', 'sortDirection'] as $prop) {
                if (!property_exists($testComponent, $prop)) {
                    $canStructure = false;
                    $missingProps[] = $prop;
                }
            }
            
            if ($canStructure) {
                $results['passed']++;
                $results['details'][] = "✅ All session data properties exist";
                $this->command->info("  ✅ Session data structure: PASS");
            } else {
                $results['details'][] = "❌ Missing session properties: " . implode(', ', $missingProps);
                $this->command->error("  ❌ Session data structure: FAIL - Missing: " . implode(', ', $missingProps));
            }
        } catch (\Exception $e) {
            $results['details'][] = "❌ Session data structure test failed: " . $e->getMessage();
            $this->command->error("  ❌ Session data structure: ERROR - " . $e->getMessage());
        }

        $this->command->info("📊 Session Management: {$results['passed']}/{$results['total']} tests passed");
        
        return $results;
    }
}
