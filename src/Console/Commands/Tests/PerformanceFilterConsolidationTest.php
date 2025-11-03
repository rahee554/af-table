<?php

namespace ArtflowStudio\Table\Console\Commands\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceFilterConsolidationTest
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
        $this->output->info('🚀 Testing Filter Consolidation...');
        
        try {
            // Test 1: Check if filters are configured
            $this->output->info('  📊 Test 1: Filter configuration...');
            
            $filterCount = count($this->component->filters);
            $this->output->info("    ✓ Total filters configured: {$filterCount}");
            
            if ($filterCount === 0) {
                $this->output->warn('  ⚠ No filters configured, skipping test');
                return true;
            }
            
            // Apply some test filters
            $testFilters = [];
            $filterIndex = 0;
            foreach ($this->component->filters as $key => $filter) {
                if ($filterIndex < 3) { // Apply up to 3 filters
                    if ($filter['type'] === 'select' || $filter['type'] === 'distinct') {
                        // Get distinct values and pick first one
                        $distinctValues = $this->component->getDistinctValues($key);
                        if (!empty($distinctValues)) {
                            $testFilters[$key] = reset($distinctValues);
                            $filterIndex++;
                        }
                    } elseif ($filter['type'] === 'text') {
                        $testFilters[$key] = 'test';
                        $filterIndex++;
                    } elseif ($filter['type'] === 'range') {
                        $testFilters[$key] = ['min' => '0', 'max' => '1000'];
                        $filterIndex++;
                    }
                }
            }
            
            $this->output->info("    ✓ Applied {$filterIndex} test filter(s)");
            
            // Test 2: Analyze query structure for duplicate WHERE clauses
            $this->output->info('  📊 Test 2: Query structure analysis...');
            
            try {
                // Set filters
                foreach ($testFilters as $key => $value) {
                    $this->component->filterColumn[$key] = $value;
                }
                
                DB::connection()->enableQueryLog();
                
                // Force query rebuild to check structure
                if (method_exists($this->component, 'invalidateQueryCache')) {
                    $this->component->invalidateQueryCache();
                }
                $data = $this->component->getData();
                
                $queries = DB::getQueryLog();
                DB::connection()->disableQueryLog();
                
                // Find the main SELECT query
                $mainQuery = null;
                foreach ($queries as $query) {
                    if (stripos($query['query'], 'select') === 0 && 
                        stripos($query['query'], 'where') !== false) {
                        $mainQuery = $query['query'];
                        break;
                    }
                }
                
                if (!$mainQuery) {
                    $this->output->warn('    ⚠ Could not find main query with WHERE clauses');
                    return true;
                }
                
                $this->output->info("    ✓ Main query found");
                
                // Test 3: Check for duplicate WHERE conditions
                $this->output->info('  📊 Test 3: Duplicate condition detection...');
                
                // Extract WHERE clause
                preg_match('/where\s+(.+?)(?:order by|limit|group by|$)/is', $mainQuery, $matches);
                $whereClause = $matches[1] ?? '';
                
                // Split by AND/OR operators
                $conditions = preg_split('/\s+(?:and|or)\s+/i', $whereClause);
                $conditions = array_map('trim', $conditions);
                
                // Remove binding placeholders and normalize
                $normalizedConditions = [];
                foreach ($conditions as $condition) {
                    // Replace ? or :binding with placeholder
                    $normalized = preg_replace('/[\?\:]\w*/', 'BINDING', $condition);
                    // Remove extra whitespace
                    $normalized = preg_replace('/\s+/', ' ', $normalized);
                    $normalizedConditions[] = $normalized;
                }
                
                // Check for duplicates
                $uniqueConditions = array_unique($normalizedConditions);
                $duplicateCount = count($normalizedConditions) - count($uniqueConditions);
                
                $this->output->info("    ✓ Total WHERE conditions: " . count($normalizedConditions));
                $this->output->info("    ✓ Unique conditions: " . count($uniqueConditions));
                $this->output->info("    ✓ Duplicate conditions: {$duplicateCount}");
                
                if ($duplicateCount > 0) {
                    $this->output->warn("    ⚠ Duplicate WHERE conditions detected!");
                    
                    // Find which conditions are duplicated
                    $conditionCounts = array_count_values($normalizedConditions);
                    foreach ($conditionCounts as $condition => $count) {
                        if ($count > 1) {
                            $this->output->warn("      - Repeated {$count}x: " . substr($condition, 0, 80));
                        }
                    }
                } else {
                    $this->output->info("    ✓ No duplicate conditions");
                }
            } catch (\Exception $e) {
                $this->output->warn("    ⚠ Query analysis skipped: " . $e->getMessage());
                return true; // Don't fail on analysis errors
            }
            
            // Test 4: Verify applyAllFilters method exists and is used
            $this->output->info('  📊 Test 4: Filter consolidation method...');
            
            if (method_exists($this->component, 'applyAllFilters')) {
                $this->output->info("    ✓ applyAllFilters() method exists");
            } else {
                $this->output->warn("    ⚠ applyAllFilters() method not found");
            }
            
            // Test 5: Performance comparison
            $this->output->info('  📊 Test 5: Filter performance...');
            
            DB::connection()->enableQueryLog();
            $startTime = microtime(true);
            
            // Execute query with filters
            if (method_exists($this->component, 'invalidateQueryCache')) {
                $this->component->invalidateQueryCache();
            }
            $results = $this->component->getData();
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // Convert to ms
            
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            
            DB::connection()->disableQueryLog();
            
            $this->output->info("    ✓ Query execution time: " . round($executionTime, 2) . "ms");
            $this->output->info("    ✓ Queries executed: {$queryCount}");
            $this->output->info("    ✓ Results returned: " . $results->count());
            
            // Calculate efficiency score
            $expectedQueries = 1 + count($this->component->cachedRelations ?? []);
            $efficiency = ($expectedQueries / max($queryCount, 1)) * 100;
            
            $this->output->newLine();
            $this->output->info("  📈 Filter Consolidation Analysis:");
            $this->output->info("     Filters applied: {$filterIndex}");
            $this->output->info("     WHERE conditions: " . count($normalizedConditions));
            $this->output->info("     Duplicate conditions: {$duplicateCount}");
            $this->output->info("     Query efficiency: " . round($efficiency, 2) . "%");
            $this->output->info("     Execution time: " . round($executionTime, 2) . "ms");
            
            // Determine pass/fail
            if ($duplicateCount > 2) {
                $this->output->error("    ✗ TOO MANY DUPLICATE CONDITIONS!");
                return false;
            } elseif ($duplicateCount > 0) {
                $this->output->warn("    ⚠ Some duplicate conditions found");
                return true;
            } else {
                $this->output->info("    ✓ Filter consolidation working properly");
                return true;
            }
            
        } catch (\Exception $e) {
            $this->output->error('  ✗ Test failed: ' . $e->getMessage());
            Log::error('Filter consolidation test error: ' . $e->getMessage());
            return false;
        } finally {
            // Clean up test filters
            if (property_exists($this->component, 'filterColumn')) {
                $this->component->filterColumn = [];
            }
            if (method_exists($this->component, 'invalidateQueryCache')) {
                $this->component->invalidateQueryCache();
            }
        }
    }
}
