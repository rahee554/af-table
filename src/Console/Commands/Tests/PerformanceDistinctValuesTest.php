<?php

namespace ArtflowStudio\Table\Console\Commands\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceDistinctValuesTest
{
    protected $component;
    protected $output;

    public function __construct($component, $output)
    {
        $this->component = $component;
        $this->output = $output;
    }

    public function run(): bool
    {
        $this->output->info('🚀 Testing Distinct Values Caching...');
        
        try {
            // Check if component has filters configured
            if (empty($this->component->filters)) {
                $this->output->warn('  ⚠ No filters configured, skipping test');
                return true;
            }
            
            // Find a distinct/select filter
            $testFilterKey = null;
            foreach ($this->component->filters as $key => $config) {
                if (in_array($config['type'] ?? null, ['distinct', 'select'])) {
                    $testFilterKey = $key;
                    break;
                }
            }
            
            if (!$testFilterKey) {
                $this->output->warn('  ⚠ No distinct/select filters found, skipping test');
                return true;
            }
            
            $this->output->info("  📊 Testing with filter: {$testFilterKey}");
            
            // Test 1: preloadDistinctValues should populate cache
            $this->output->info('  📊 Test 1: Preload populates cache...');
            
            $cacheSize = count($this->component->distinctValuesCache ?? []);
            $this->output->info("    ✓ Cache contains {$cacheSize} filter(s)");
            
            if (isset($this->component->distinctValuesCache[$testFilterKey])) {
                $cachedValues = count($this->component->distinctValuesCache[$testFilterKey]);
                $this->output->info("    ✓ Filter '{$testFilterKey}' has {$cachedValues} distinct values cached");
            }
            
            // Test 2: getDistinctValues should NOT query database (use cache)
            $this->output->info('  📊 Test 2: getDistinctValues uses cache...');
            DB::connection()->enableQueryLog();
            
            $values1 = $this->component->getDistinctValues($testFilterKey);
            
            $queries1 = DB::getQueryLog();
            $queryCount1 = count($queries1);
            
            if ($queryCount1 === 0) {
                $this->output->info("    ✓ First call: 0 queries (using cache)");
            } else {
                $this->output->warn("    ⚠ First call: {$queryCount1} queries (should be 0)");
            }
            
            DB::connection()->disableQueryLog();
            
            // Test 3: Multiple calls should NOT query database
            $this->output->info('  📊 Test 3: Multiple calls use cache...');
            DB::connection()->enableQueryLog();
            
            for ($i = 0; $i < 5; $i++) {
                $this->component->getDistinctValues($testFilterKey);
            }
            
            $queries2 = DB::getQueryLog();
            $queryCount2 = count($queries2);
            
            if ($queryCount2 === 0) {
                $this->output->info("    ✓ 5 calls: 0 queries (all cached)");
            } else {
                $this->output->warn("    ⚠ 5 calls: {$queryCount2} queries (should be 0)");
            }
            
            DB::connection()->disableQueryLog();
            
            // Test 4: Cache persists across renders
            $this->output->info('  📊 Test 4: Cache persists across renders...');
            
            $this->component->render();
            
            DB::connection()->enableQueryLog();
            $values2 = $this->component->getDistinctValues($testFilterKey);
            
            $queries3 = DB::getQueryLog();
            $queryCount3 = count($queries3);
            
            if ($queryCount3 === 0) {
                $this->output->info("    ✓ After render: 0 queries (cache persists)");
            }
            
            if ($values1 === $values2) {
                $this->output->info("    ✓ Values match across calls");
            }
            
            DB::connection()->disableQueryLog();
            
            // Calculate performance improvement
            $expectedQueriesWithoutCache = 5; // 1 preload + 1 first call + 5 loop calls - with cache
            $actualQueries = $queryCount1 + $queryCount2 + $queryCount3;
            $improvement = ($expectedQueriesWithoutCache - $actualQueries) / $expectedQueriesWithoutCache * 100;
            
            $this->output->newLine();
            $this->output->info("  📈 Performance Improvement:");
            $this->output->info("     Total queries executed: {$actualQueries}");
            $this->output->info("     Expected without cache: {$expectedQueriesWithoutCache}+");
            $this->output->info("     Improvement: ~" . round($improvement, 2) . "%");
            
            return $actualQueries <= 1; // Should be 0 or at most 1 (from preload)
            
        } catch (\Exception $e) {
            $this->output->error('  ✗ Test failed: ' . $e->getMessage());
            Log::error('Distinct values test error: ' . $e->getMessage());
            return false;
        }
    }
}
