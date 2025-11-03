<?php

namespace ArtflowStudio\Table\Console\Commands\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceQueryCachingTest
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
        $this->output->info('🚀 Testing Query Result Caching...');
        
        try {
            // Test 1: Initial render should execute query
            $this->output->info('  📊 Test 1: Initial render executes query...');
            DB::connection()->enableQueryLog();
            
            $hash1 = $this->component->generateQueryHash();
            $this->component->render();
            
            $queries1 = DB::getQueryLog();
            $queryCount1 = count($queries1);
            
            if ($queryCount1 > 0) {
                $this->output->info("    ✓ Initial render: {$queryCount1} queries executed");
            } else {
                $this->output->error('    ✗ No queries executed on initial render');
                return false;
            }
            
            DB::connection()->disableQueryLog();
            
            // Test 2: Second render with same parameters should use cache
            $this->output->info('  📊 Test 2: Second render uses cache...');
            DB::connection()->enableQueryLog();
            
            $hash2 = $this->component->generateQueryHash();
            $this->component->render();
            
            $queries2 = DB::getQueryLog();
            $queryCount2 = count($queries2);
            
            if ($hash1 === $hash2) {
                $this->output->info("    ✓ Query hashes match: Cache should be used");
            }
            
            if ($queryCount2 === 0) {
                $this->output->info("    ✓ Second render: 0 queries (cached)");
            } else {
                $this->output->warning("    ⚠ Second render: {$queryCount2} queries (should be 0)");
            }
            
            DB::connection()->disableQueryLog();
            
            // Test 3: Changing search should invalidate cache
            $this->output->info('  📊 Test 3: Changing search invalidates cache...');
            DB::connection()->enableQueryLog();
            
            $this->component->updatedSearch('test');
            $hash3 = $this->component->generateQueryHash();
            $this->component->render();
            
            $queries3 = DB::getQueryLog();
            $queryCount3 = count($queries3);
            
            if ($hash3 !== $hash1) {
                $this->output->info("    ✓ Query hash changed after search update");
            }
            
            if ($queryCount3 > 0) {
                $this->output->info("    ✓ After search: {$queryCount3} queries executed");
            }
            
            DB::connection()->disableQueryLog();
            
            // Test 4: Cache invalidation method
            $this->output->info('  📊 Test 4: Manual cache invalidation...');
            $this->component->invalidateQueryCache();
            
            DB::connection()->enableQueryLog();
            $this->component->render();
            
            $queries4 = DB::getQueryLog();
            $queryCount4 = count($queries4);
            
            if ($queryCount4 > 0) {
                $this->output->info("    ✓ After invalidation: {$queryCount4} queries executed");
            }
            
            DB::connection()->disableQueryLog();
            
            // Calculate performance improvement
            $improvement = $queryCount2 === 0 ? 100 : ((1 - ($queryCount2 / $queryCount1)) * 100);
            
            $this->output->newLine();
            $this->output->info("  📈 Performance Improvement:");
            $this->output->info("     Initial queries: {$queryCount1}");
            $this->output->info("     Cached queries: {$queryCount2}");
            $this->output->info("     Improvement: " . round($improvement, 2) . "%");
            
            return $queryCount2 === 0;
            
        } catch (\Exception $e) {
            $this->output->error('  ✗ Test failed: ' . $e->getMessage());
            Log::error('Query caching test error: ' . $e->getMessage());
            return false;
        }
    }
}
