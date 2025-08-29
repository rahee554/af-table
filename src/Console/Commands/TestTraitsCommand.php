<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use App\Models\AfTestTable;

class TestTraitsCommand extends Command
{
    protected $signature = 'aftable:test-traits {--trait= : Specific trait to test}';
    protected $description = 'Test all datatable traits functionality';

    public function handle()
    {
        $specificTrait = $this->option('trait');

        $this->info('🧪 Testing Datatable Traits');
        $this->newLine();

        // Create a test datatable instance
        $datatable = $this->createTestDatatable();

        if ($specificTrait) {
            $this->testSpecificTrait($datatable, $specificTrait);
        } else {
            $this->runAllTests($datatable);
        }

        return 0;
    }

    protected function createTestDatatable()
    {
        $datatable = new class extends DatatableTrait {
            public function __construct() {
                $this->model = AfTestTable::class;
                $this->tableId = 'test_datatable';
                $this->columns = [
                    'id' => ['label' => 'ID', 'sortable' => true],
                    'name' => ['label' => 'Name', 'searchable' => true, 'sortable' => true],
                    'email' => ['label' => 'Email', 'searchable' => true],
                    'company' => ['label' => 'Company', 'searchable' => true],
                    'age' => ['label' => 'Age', 'sortable' => true],
                    'salary' => ['label' => 'Salary', 'sortable' => true],
                    'is_active' => ['label' => 'Active', 'sortable' => true],
                    'status' => ['label' => 'Status', 'sortable' => true],
                    'category_name' => ['label' => 'Category', 'relation' => 'category:name'],
                    'department_name' => ['label' => 'Department', 'relation' => 'department:name'],
                    'preferences_theme' => ['label' => 'Theme', 'json' => 'theme', 'key' => 'preferences'],
                    'created_at' => ['label' => 'Created', 'sortable' => true],
                ];
                
                $this->initializeColumnVisibility();
                $this->validateConfiguration();
            }
        };

        return $datatable;
    }

    protected function runAllTests($datatable)
    {
        $tests = [
            'QueryBuilder' => [$this, 'testQueryBuilder'],
            'DataValidation' => [$this, 'testDataValidation'],
            'ColumnConfiguration' => [$this, 'testColumnConfiguration'],
            'ColumnVisibility' => [$this, 'testColumnVisibility'],
            'Search' => [$this, 'testSearch'],
            'Filtering' => [$this, 'testFiltering'],
            'Sorting' => [$this, 'testSorting'],
            'Caching' => [$this, 'testCaching'],
            'EagerLoading' => [$this, 'testEagerLoading'],
            'MemoryManagement' => [$this, 'testMemoryManagement'],
            'JsonSupport' => [$this, 'testJsonSupport'],
            'Relationships' => [$this, 'testRelationships'],
            'Export' => [$this, 'testExport'],
            'RawTemplates' => [$this, 'testRawTemplates'],
            'SessionManagement' => [$this, 'testSessionManagement'],
            'QueryStringSupport' => [$this, 'testQueryStringSupport'],
            'EventListeners' => [$this, 'testEventListeners'],
            'Actions' => [$this, 'testActions'],
        ];

        $results = [];

        foreach ($tests as $traitName => $testMethod) {
            $this->info("Testing {$traitName}...");
            
            try {
                $result = call_user_func($testMethod, $datatable);
                $results[$traitName] = $result;
                
                if ($result['success']) {
                    $this->line("  ✅ {$traitName}: PASSED");
                } else {
                    $this->line("  ❌ {$traitName}: FAILED - {$result['message']}");
                }
            } catch (\Exception $e) {
                $results[$traitName] = ['success' => false, 'message' => $e->getMessage()];
                $this->line("  ❌ {$traitName}: ERROR - {$e->getMessage()}");
            }
        }

        $this->showTestSummary($results);
    }

    protected function testSpecificTrait($datatable, $traitName)
    {
        $methodName = 'test' . $traitName;
        
        if (!method_exists($this, $methodName)) {
            $this->error("Test method {$methodName} does not exist");
            return;
        }

        $this->info("Testing {$traitName} trait...");
        
        try {
            $result = $this->$methodName($datatable);
            
            if ($result['success']) {
                $this->info("✅ {$traitName}: PASSED");
                if (isset($result['details'])) {
                    $this->table(['Property', 'Value'], $result['details']);
                }
            } else {
                $this->error("❌ {$traitName}: FAILED - {$result['message']}");
            }
        } catch (\Exception $e) {
            $this->error("❌ {$traitName}: ERROR - {$e->getMessage()}");
        }
    }

    // Test implementations for each trait
    protected function testQueryBuilder($datatable)
    {
        try {
            $query = $datatable->getQuery();
            $count = $query->count();
            
            return [
                'success' => true,
                'details' => [
                    ['Property', 'Value'],
                    ['Query Class', get_class($query)],
                    ['Record Count', $count],
                    ['Model', $datatable->model],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testDataValidation($datatable)
    {
        try {
            $validation = $datatable->validateColumns();
            
            return [
                'success' => empty($validation['errors']),
                'message' => empty($validation['errors']) ? 'All validations passed' : implode(', ', $validation['errors']),
                'details' => [
                    ['Total Columns', count($datatable->columns)],
                    ['Valid Columns', count($validation['valid'])],
                    ['Invalid Columns', count($validation['errors'])],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testColumnConfiguration($datatable)
    {
        try {
            $stats = $datatable->getColumnStats();
            
            return [
                'success' => $stats['total_columns'] > 0,
                'details' => [
                    ['Total Columns', $stats['total_columns']],
                    ['Searchable Columns', $stats['searchable_columns']],
                    ['Sortable Columns', $stats['sortable_columns']],
                    ['Exportable Columns', $stats['exportable_columns']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testColumnVisibility($datatable)
    {
        try {
            $visibleCount = count($datatable->getVisibleColumns());
            $totalCount = count($datatable->columns);
            
            return [
                'success' => $visibleCount > 0,
                'details' => [
                    ['Total Columns', $totalCount],
                    ['Visible Columns', $visibleCount],
                    ['Hidden Columns', $totalCount - $visibleCount],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testSearch($datatable)
    {
        try {
            $datatable->search = 'test';
            $query = $datatable->buildQuery();
            $sql = $query->toSql();
            
            return [
                'success' => strpos($sql, 'LIKE') !== false || strpos($sql, 'where') !== false,
                'details' => [
                    ['Search Term', $datatable->search],
                    ['SQL Contains LIKE', strpos($sql, 'LIKE') !== false ? 'Yes' : 'No'],
                    ['Searchable Columns', implode(', ', $datatable->getSearchableColumns())],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testFiltering($datatable)
    {
        try {
            $filterStats = $datatable->getFilterStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Available Filters', $filterStats['total_filters']],
                    ['Active Filters', $filterStats['active_filters']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testSorting($datatable)
    {
        try {
            $datatable->sortBy = 'name';
            $datatable->sortDirection = 'asc';
            $query = $datatable->buildQuery();
            $sql = $query->toSql();
            
            return [
                'success' => strpos($sql, 'order by') !== false,
                'details' => [
                    ['Sort Column', $datatable->sortBy],
                    ['Sort Direction', $datatable->sortDirection],
                    ['SQL Contains ORDER BY', strpos($sql, 'order by') !== false ? 'Yes' : 'No'],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testCaching($datatable)
    {
        try {
            $cacheStats = $datatable->getCacheStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Cache Driver', $cacheStats['cache_driver']],
                    ['Cache TTL', $cacheStats['cache_ttl']],
                    ['Distinct Caches', $cacheStats['distinct_caches']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testEagerLoading($datatable)
    {
        try {
            $stats = $datatable->getEagerLoadingStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Total Relations', $stats['total_relations']],
                    ['Valid Relations', $stats['valid_relations']],
                    ['Loading Strategy', $stats['loading_strategy']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testMemoryManagement($datatable)
    {
        try {
            $memoryStats = $datatable->getMemoryStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Current Memory', number_format($memoryStats['usage']['current']) . ' bytes'],
                    ['Memory Threshold', number_format($memoryStats['threshold']) . ' bytes'],
                    ['Max Batch Size', $memoryStats['max_batch_size']],
                    ['Threshold Exceeded', $memoryStats['is_threshold_exceeded'] ? 'Yes' : 'No'],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testJsonSupport($datatable)
    {
        try {
            $jsonStats = $datatable->getJsonColumnStats();
            
            return [
                'success' => true,
                'details' => [
                    ['JSON Columns', $jsonStats['total_json_columns']],
                    ['Database Supports JSON', $jsonStats['database_supports_json'] ? 'Yes' : 'No'],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testRelationships($datatable)
    {
        try {
            $relationStats = $datatable->getRelationColumnStats();
            
            return [
                'success' => $relationStats['valid_relations'] >= 0,
                'details' => [
                    ['Total Relation Columns', $relationStats['total_relation_columns']],
                    ['Valid Relations', $relationStats['valid_relations']],
                    ['Invalid Relations', $relationStats['invalid_relations']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testExport($datatable)
    {
        try {
            $exportStats = $datatable->getExportStats();
            $formats = $datatable->getAvailableExportFormats();
            
            return [
                'success' => true,
                'details' => [
                    ['Total Records', $exportStats['total_records']],
                    ['Exportable Columns', $exportStats['exportable_columns']],
                    ['Available Formats', implode(', ', array_keys($formats))],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testRawTemplates($datatable)
    {
        try {
            $templateStats = $datatable->getTemplateStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Total Templates', $templateStats['total_templates']],
                    ['Valid Templates', $templateStats['valid_templates']],
                    ['Invalid Templates', $templateStats['invalid_templates']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testSessionManagement($datatable)
    {
        try {
            $sessionStats = $datatable->getSessionStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Has State', $sessionStats['has_state'] ? 'Yes' : 'No'],
                    ['Has Column Preferences', $sessionStats['has_column_preferences'] ? 'Yes' : 'No'],
                    ['Search History Count', $sessionStats['search_history_count']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testQueryStringSupport($datatable)
    {
        try {
            $qsStats = $datatable->getQueryStringStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Has Params', $qsStats['has_params'] ? 'Yes' : 'No'],
                    ['Param Count', $qsStats['param_count']],
                    ['URL Length', $qsStats['url_length']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testEventListeners($datatable)
    {
        try {
            $eventStats = $datatable->getEventListenerStats();
            $availableEvents = $datatable->getAvailableEvents();
            
            return [
                'success' => true,
                'details' => [
                    ['Total Events', $eventStats['total_events']],
                    ['Total Listeners', $eventStats['total_listeners']],
                    ['Available Events', count($availableEvents)],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testActions($datatable)
    {
        try {
            $actionStats = $datatable->getActionStats();
            
            return [
                'success' => true,
                'details' => [
                    ['Total Actions', $actionStats['total_actions']],
                    ['Total Bulk Actions', $actionStats['total_bulk_actions']],
                    ['Selected Records', $actionStats['selected_records']],
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function showTestSummary($results)
    {
        $this->newLine();
        $this->info('📊 Test Summary');
        $this->newLine();

        $total = count($results);
        $passed = count(array_filter($results, fn($r) => $r['success']));
        $failed = $total - $passed;

        $this->table(
            ['Trait', 'Status', 'Message'],
            array_map(function($trait, $result) {
                return [
                    $trait,
                    $result['success'] ? '✅ PASSED' : '❌ FAILED',
                    $result['message'] ?? ''
                ];
            }, array_keys($results), $results)
        );

        $this->newLine();
        $this->info("Results: {$passed}/{$total} tests passed");
        
        if ($failed > 0) {
            $this->warn("{$failed} tests failed");
        } else {
            $this->info("🎉 All tests passed!");
        }
    }
}
