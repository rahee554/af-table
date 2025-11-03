<?php

namespace ArtflowStudio\Table\Console\Commands\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceN1RelationTest
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
        $this->output->info('🚀 Testing N+1 Relation Detection...');
        
        try {
            // Check if component has relation columns
            $relationColumns = [];
            foreach ($this->component->columns as $key => $column) {
                if (isset($column['relation'])) {
                    $relationColumns[$key] = $column['relation'];
                }
            }
            
            if (empty($relationColumns)) {
                $this->output->warn('  ⚠ No relation columns configured, skipping test');
                return true;
            }
            
            $this->output->info('  📊 Found ' . count($relationColumns) . ' relation column(s)');
            foreach ($relationColumns as $key => $relation) {
                $this->output->info("    - {$key}: {$relation}");
            }
            
            // Test 1: Check if relations are properly detected
            $this->output->info('  📊 Test 1: Relation detection...');
            
            $cachedRelations = $this->component->cachedRelations ?? [];
            if (!empty($cachedRelations)) {
                $this->output->info("    ✓ Cached relations: " . implode(', ', $cachedRelations));
            } else {
                $this->output->warn("    ⚠ No cached relations found");
            }
            
            // Test 2: Check if eager loading is applied
            $this->output->info('  📊 Test 2: Eager loading application...');
            DB::connection()->enableQueryLog();
            
            $data = $this->component->getData();
            
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            
            $this->output->info("    ✓ Total queries executed: {$queryCount}");
            
            // Analyze queries for N+1 patterns
            $selectQueries = array_filter($queries, function($query) {
                return stripos($query['query'], 'select') === 0;
            });
            
            $selectCount = count($selectQueries);
            $this->output->info("    ✓ SELECT queries: {$selectCount}");
            
            // Check for repeated queries (N+1 indicator)
            $queryHashes = [];
            $duplicateQueries = 0;
            
            foreach ($queries as $query) {
                $hash = md5($query['query']);
                if (isset($queryHashes[$hash])) {
                    $queryHashes[$hash]++;
                    $duplicateQueries++;
                } else {
                    $queryHashes[$hash] = 1;
                }
            }
            
            if ($duplicateQueries > 0) {
                $this->output->warn("    ⚠ Duplicate queries detected: {$duplicateQueries}");
                $this->output->warn("    ⚠ Possible N+1 issue!");
            } else {
                $this->output->info("    ✓ No duplicate queries detected");
            }
            
            DB::connection()->disableQueryLog();
            
            // Test 3: Verify eager loading with data iteration
            $this->output->info('  📊 Test 3: Data iteration query count...');
            
            DB::connection()->enableQueryLog();
            
            $iterationQueries = 0;
            foreach ($data as $row) {
                // Access relation columns to trigger lazy loading if not eager loaded
                foreach ($relationColumns as $key => $relation) {
                    [$relationPath, $attribute] = explode(':', $relation);
                    
                    // Try to access the relation
                    try {
                        $parts = explode('.', $relationPath);
                        $value = $row;
                        foreach ($parts as $part) {
                            if (is_object($value) && isset($value->{$part})) {
                                $value = $value->{$part};
                            } else {
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        // Relation might not exist on some rows
                    }
                }
            }
            
            $iterationQueriesLog = DB::getQueryLog();
            $iterationQueryCount = count($iterationQueriesLog);
            
            if ($iterationQueryCount === 0) {
                $this->output->info("    ✓ Iteration queries: 0 (perfect eager loading)");
            } else {
                $this->output->warn("    ⚠ Iteration queries: {$iterationQueryCount}");
                $this->output->warn("    ⚠ Relations may not be fully eager loaded");
            }
            
            DB::connection()->disableQueryLog();
            
            // Calculate N+1 risk score
            $rowCount = $data->count();
            $expectedMaxQueries = 1 + count($cachedRelations); // 1 main + 1 per unique relation
            $n1RiskScore = ($queryCount > $expectedMaxQueries) ? 
                (($queryCount - $expectedMaxQueries) / $rowCount) * 100 : 0;
            
            $this->output->newLine();
            $this->output->info("  📈 N+1 Risk Analysis:");
            $this->output->info("     Rows fetched: {$rowCount}");
            $this->output->info("     Expected max queries: {$expectedMaxQueries}");
            $this->output->info("     Actual queries: {$queryCount}");
            $this->output->info("     Duplicate queries: {$duplicateQueries}");
            $this->output->info("     Iteration queries: {$iterationQueryCount}");
            $this->output->info("     N+1 Risk Score: " . round($n1RiskScore, 2) . "%");
            
            if ($n1RiskScore > 10) {
                $this->output->error("    ✗ HIGH N+1 RISK DETECTED!");
                return false;
            } elseif ($n1RiskScore > 0) {
                $this->output->warning("    ⚠ Moderate N+1 risk");
                return true;
            } else {
                $this->output->info("    ✓ No N+1 issues detected");
                return true;
            }
            
        } catch (\Exception $e) {
            $this->output->error('  ✗ Test failed: ' . $e->getMessage());
            Log::error('N+1 relation test error: ' . $e->getMessage());
            return false;
        }
    }
}
