<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Console\Commands\Mini\BasicFeatureTest;
use ArtflowStudio\Table\Console\Commands\Mini\FilteringTest;
use ArtflowStudio\Table\Console\Commands\Mini\SessionManagementTest;
use ArtflowStudio\Table\Console\Commands\Mini\TraitCollisionTest;

class MiniTestRunner extends Command
{
    protected $signature = 'table:test-mini {--test=* : Specific tests to run} {--verbose : Show detailed output}';
    protected $description = 'Run modular mini tests for the table package';

    protected $availableTests = [
        'basic' => BasicFeatureTest::class,
        'filtering' => FilteringTest::class,
        'session' => SessionManagementTest::class,
        'collision' => TraitCollisionTest::class,
    ];

    public function handle()
    {
        $this->info("🧪 AF Table Package - Mini Test Suite");
        $this->info("=====================================");

        $specificTests = $this->option('test');
        $verbose = $this->option('verbose');
        
        $testsToRun = empty($specificTests) ? array_keys($this->availableTests) : $specificTests;
        
        $allResults = [];
        $overallPassed = 0;
        $overallTotal = 0;
        
        foreach ($testsToRun as $testName) {
            if (!isset($this->availableTests[$testName])) {
                $this->error("❌ Unknown test: {$testName}");
                $this->info("Available tests: " . implode(', ', array_keys($this->availableTests)));
                continue;
            }
            
            $this->newLine();
            $this->info("🏃‍♂️ Running: {$testName}");
            $this->info(str_repeat("-", 40));
            
            try {
                $testClass = $this->availableTests[$testName];
                $testInstance = new $testClass($this);
                $results = $testInstance->run();
                
                $allResults[$testName] = $results;
                $overallPassed += $results['passed'];
                $overallTotal += $results['total'];
                
                if ($verbose) {
                    $this->newLine();
                    $this->info("📋 Detailed Results for {$testName}:");
                    foreach ($results['details'] as $detail) {
                        $this->line("  " . $detail);
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("💥 Test {$testName} crashed: " . $e->getMessage());
                $this->error("Stack trace: " . $e->getTraceAsString());
                $allResults[$testName] = [
                    'passed' => 0,
                    'total' => 1,
                    'details' => ["❌ Test crashed: " . $e->getMessage()]
                ];
                $overallTotal += 1;
            }
        }
        
        // Summary
        $this->newLine(2);
        $this->info("📊 OVERALL TEST SUMMARY");
        $this->info("======================");
        
        foreach ($allResults as $testName => $results) {
            $status = $results['passed'] === $results['total'] ? '✅' : '❌';
            $this->info("  {$status} {$testName}: {$results['passed']}/{$results['total']} passed");
        }
        
        $this->newLine();
        $overallPercentage = $overallTotal > 0 ? round(($overallPassed / $overallTotal) * 100, 1) : 0;
        
        if ($overallPassed === $overallTotal) {
            $this->info("🎉 ALL TESTS PASSED! ({$overallPassed}/{$overallTotal}) - 100%");
        } elseif ($overallPercentage >= 80) {
            $this->info("✅ MOSTLY PASSING ({$overallPassed}/{$overallTotal}) - {$overallPercentage}%");
        } elseif ($overallPercentage >= 60) {
            $this->warn("⚠️  SOME ISSUES ({$overallPassed}/{$overallTotal}) - {$overallPercentage}%");
        } else {
            $this->error("❌ MAJOR ISSUES ({$overallPassed}/{$overallTotal}) - {$overallPercentage}%");
        }
        
        // Recommendations
        if ($overallPassed < $overallTotal) {
            $this->newLine();
            $this->info("🔧 RECOMMENDATIONS:");
            
            foreach ($allResults as $testName => $results) {
                if ($results['passed'] < $results['total']) {
                    $this->warn("  • Check {$testName} test failures above for specific issues");
                }
            }
            
            $this->info("  • Run with --verbose for detailed error information");
            $this->info("  • Run specific tests with --test=testname");
        }
        
        // Available commands info
        $this->newLine();
        $this->info("📖 USAGE EXAMPLES:");
        $this->info("  php artisan table:test-mini                    # Run all tests");
        $this->info("  php artisan table:test-mini --verbose          # Detailed output");
        $this->info("  php artisan table:test-mini --test=basic       # Run specific test");
        $this->info("  php artisan table:test-mini --test=basic,filtering # Multiple tests");
        
        return $overallPassed === $overallTotal ? 0 : 1;
    }
    
    /**
     * Get list of available tests
     */
    public function getAvailableTests(): array
    {
        return $this->availableTests;
    }
    
    /**
     * Add a new test to the suite
     */
    public function addTest(string $name, string $className): void
    {
        $this->availableTests[$name] = $className;
    }
}
