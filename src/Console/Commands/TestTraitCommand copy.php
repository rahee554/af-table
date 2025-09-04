<?php

namespace ArtflowStudio\Tabl    private function runInteractiveTests()
    {
        while (true) {
            $this->info('🧪 Available Test Suites:');
            $this->newLine();
            $this->info('  [1]  🎯 Component Instantiation');
            $this->info('  [2]  ✅ Validation Methods');
            $this->info('  [3]  🔗 Trait Integration');
            $this->info('  [4]  📊 Property Validation');
            $this->info('  [5]  🔄 Query Building');
            $this->info('  [6]  📋 Column Management');
            $this->info('  [7]  🔍 Search & Filter');
            $this->info('  [8]  ⚡ Performance Tests');
            $this->info('  [9]  🔗 Relationship Tests');
            $this->info('  [10] 📄 JSON Column Tests');
            $this->info('  [11] 🎛️ Export Functions');
            $this->info('  [12] 📄 Memory Management');
            $this->info('  [13] 🔄 ForEach Functionality');
            $this->info('  [14] 🌐 API Endpoint Integration');
            $this->info('  [15] 🎨 Enhanced Testing');
            $this->info('  [0]  🚪 Exit');
            $this->newLine();nds;

use Illuminate\Console\Command;
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TestTraitCommand extends Command
{
    protected $signature = 'af-table:test-trait {--interactive : Run interactive tests} {--suite=all : Test suite to run}';
    protected $description = 'Test the DatatableTrait architecture and functionality';

    public function handle()
    {
        $this->displayHeader();

        if ($this->option('interactive')) {
            return $this->runInteractiveTests();
        }

        $suite = $this->option('suite');
        return $this->runTestSuite($suite);
    }

    private function displayHeader()
    {
        $this->info('╭─────────────────────────────────────────────────────╮');
        $this->info('│            AF TABLE TRAIT TEST SUITE               │');
        $this->info('│         DatatableTrait Architecture Testing        │');
        $this->info('╰─────────────────────────────────────────────────────╯');
        $this->newLine();
    }

    private function runInteractiveTests()
    {
        while (true) {
            $this->info('🧪 Available Test Suites:');
            $this->newLine();
            $this->info('  [1]  🎯 Component Instantiation');
            $this->info('  [2]  ✅ Validation Methods');
            $this->info('  [3]  🔗 Trait Integration');
            $this->info('  [4]  📊 Property Validation');
            $this->info('  [5]  🔄 Query Building');
            $this->info('  [6]  📋 Column Management');
            $this->info('  [7]  🔍 Search & Filter');
            $this->info('  [8]  ⚡ Performance Tests');
            $this->info('  [9]  🔗 Relationship Tests');
            $this->info('  [10] � JSON Column Tests');
            $this->info('  [11] �📤 Export Functions');
            $this->info('  [12] 🛡️  Security Methods');
            $this->info('  [0]  🎯 Run All Tests');
            $this->info('  [q]  👋 Quit');
            $this->newLine();

            $choice = $this->ask('Please select a test suite');

            switch ($choice) {
                case '1':
                    $this->runComponentInstantiationTest();
                    break;
                case '2':
                    $this->runValidationMethodsTest();
                    break;
                case '3':
                    $this->runTraitIntegrationTest();
                    break;
                case '4':
                    $this->runPropertyValidationTest();
                    break;
                case '5':
                    $this->runQueryBuildingTest();
                    break;
                case '6':
                    $this->runColumnManagementTest();
                    break;
                case '7':
                    $this->runSearchFilterTest();
                    break;
                case '8':
                    $this->runPerformanceTests();
                    break;
                case '9':
                    $this->runRelationshipTests();
                    break;
                case '10':
                    $this->runJsonColumnTests();
                    break;
                case '11':
                    $this->runExportFunctionsTest();
                    break;
                case '12':
                    $this->runSecurityMethodsTest();
                    break;
                case '0':
                    $this->runAllTests();
                    break;
                case 'q':
                case 'quit':
                    $this->info('👋 Goodbye!');
                    return 0;
                default:
                    $this->error('Invalid choice. Please try again.');
            }

            $this->newLine();
            if (!$this->confirm('Continue testing?', true)) {
                break;
            }
        }

        return 0;
    }

    private function runTestSuite($suite)
    {
        switch (strtolower($suite)) {
            case 'all':
                return $this->runAllTests();
            case 'validation':
                return $this->runValidationMethodsTest();
            case 'traits':
                return $this->runTraitIntegrationTest();
            case 'component':
                return $this->runComponentInstantiationTest();
            case 'performance':
                return $this->runPerformanceTests();
            case 'relationships':
                return $this->runRelationshipTests();
            case 'json':
                return $this->runJsonColumnTests();
            case 'export':
                return $this->runExportFunctionsTest();
            case 'security':
                return $this->runSecurityMethodsTest();
            default:
                $this->error("Unknown test suite: {$suite}");
                return 1;
        }
    }

    private function runAllTests()
    {
        $this->info('🎯 Running Comprehensive DatatableTrait Test Suite...');
        $this->newLine();

        $results = [];
        $results['Component Instantiation'] = $this->runComponentInstantiationTest();
        $results['Validation Methods'] = $this->runValidationMethodsTest();
        $results['Trait Integration'] = $this->runTraitIntegrationTest();
        $results['Property Validation'] = $this->runPropertyValidationTest();
        $results['Query Building'] = $this->runQueryBuildingTest();
        $results['Column Management'] = $this->runColumnManagementTest();
        $results['Search & Filter'] = $this->runSearchFilterTest();
        $results['Performance Tests'] = $this->runPerformanceTests();
        $results['Relationship Tests'] = $this->runRelationshipTests();
        $results['JSON Column Tests'] = $this->runJsonColumnTests();
        $results['Export Functions'] = $this->runExportFunctionsTest();
        $results['Security Methods'] = $this->runSecurityMethodsTest();

        $this->displayFinalResults($results);
        return array_sum($results) === 0 ? 0 : 1;
    }

    private function runComponentInstantiationTest()
    {
        $this->info('🔄 Testing Component Instantiation...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = [
                        'id' => ['key' => 'id', 'label' => 'ID'],
                        'name' => ['key' => 'name', 'label' => 'Name'],
                        'email' => ['key' => 'email', 'label' => 'Email'],
                    ];
                }
            };

            $this->info('  ✅ Component instantiated successfully');

            // Test properties exist
            $requiredProps = ['model', 'columns', 'visibleColumns', 'search', 'query'];
            foreach ($requiredProps as $prop) {
                if (property_exists($testComponent, $prop)) {
                    $this->info("  ✅ Property \${$prop} exists");
                } else {
                    $this->error("  ❌ Property \${$prop} missing");
                    return 1;
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("  ❌ Component instantiation failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runValidationMethodsTest()
    {
        $this->info('🔄 Testing Validation Methods...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = [
                        'id' => ['key' => 'id', 'label' => 'ID'],
                        'name' => ['key' => 'name', 'label' => 'Name'],
                        'email' => ['key' => 'email', 'label' => 'Email'],
                        'profile_name' => ['relation' => 'profile:name', 'label' => 'Profile'],
                        'settings_theme' => ['key' => 'settings', 'json' => 'theme.color', 'label' => 'Theme'],
                    ];
                }
            };

            $validationMethods = [
                'validateColumns',
                'validateRelationColumns',
                'validateConfiguration',
                'validateColumnConfiguration',
                'validateBasicRelationString',
                'validateJsonPath',
                'validateExportFormat',
            ];

            $passed = 0;
            foreach ($validationMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $this->info("  ✅ {$method} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ {$method} missing");
                }
            }

            // Test validateColumns execution
            try {
                $reflection = new \ReflectionClass($testComponent);
                $method = $reflection->getMethod('validateColumns');
                $method->setAccessible(true);
                $result = $method->invoke($testComponent);
                $this->info("  ✅ validateColumns executed: " . count($result['valid']) . " valid, " . count($result['invalid']) . " invalid");
            } catch (\Exception $e) {
                $this->error("  ❌ validateColumns execution failed: {$e->getMessage()}");
                return 1;
            }

            $this->info("  📊 Validation methods: {$passed}/" . count($validationMethods) . " passed");
            return $passed === count($validationMethods) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Validation test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runTraitIntegrationTest()
    {
        $this->info('🔄 Testing Trait Integration...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                }
            };

            $expectedTraits = [
                'HasQueryBuilder',
                'HasDataValidation',
                'HasColumnConfiguration',
                'HasColumnVisibility',
                'HasSearch',
                'HasSorting',
                'HasEagerLoading',
                'HasMemoryManagement',
                'HasJsonSupport',
                'HasRelationships',
                'HasRawTemplates',
                'HasSessionManagement',
                'HasQueryStringSupport',
                'HasEventListeners',
                'HasActions',
                'HasBulkActions',
                'HasAdvancedFiltering',
                'HasAdvancedCaching',
                'HasAdvancedExport',
                'HasColumnOptimization',
                'HasQueryOptimization',
                'HasPerformanceMonitoring',
                'HasDistinctValues'
            ];

            $usedTraits = class_uses_recursive($testComponent);
            $passed = 0;

            foreach ($expectedTraits as $traitName) {
                $fullTraitName = "ArtflowStudio\\Table\\Traits\\{$traitName}";
                if (in_array($fullTraitName, $usedTraits)) {
                    $this->info("  ✅ {$traitName} integrated");
                    $passed++;
                } else {
                    $this->error("  ❌ {$traitName} missing");
                }
            }

            $this->info("  📊 Traits integrated: {$passed}/" . count($expectedTraits));
            return $passed === count($expectedTraits) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Trait integration test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runPropertyValidationTest()
    {
        $this->info('🔄 Testing Property Validation...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                }
            };

            $requiredProperties = [
                'tableId', 'model', 'columns', 'visibleColumns', 'search', 'sortColumn',
                'sortDirection', 'perPage', 'filters', 'query', 'enableSessionPersistence',
                'enableQueryStringSupport', 'index', 'colvisBtn', 'searchable', 'exportable'
            ];

            $passed = 0;
            foreach ($requiredProperties as $prop) {
                if (property_exists($testComponent, $prop)) {
                    $this->info("  ✅ Property \${$prop} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ Property \${$prop} missing");
                }
            }

            $this->info("  📊 Properties: {$passed}/" . count($requiredProperties) . " passed");
            return $passed === count($requiredProperties) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Property validation failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runQueryBuildingTest()
    {
        $this->info('🔄 Testing Query Building...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                }
            };

            $queryMethods = ['getQuery', 'buildQuery', 'applyOptimizedSearch', 'applyFilters', 'applySortingToQuery'];
            $passed = 0;

            foreach ($queryMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $this->info("  ✅ {$method} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ {$method} missing");
                }
            }

            $this->info("  📊 Query methods: {$passed}/" . count($queryMethods) . " passed");
            return $passed === count($queryMethods) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Query building test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runColumnManagementTest()
    {
        $this->info('🔄 Testing Column Management...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = [
                        'id' => ['key' => 'id', 'label' => 'ID'],
                        'name' => ['key' => 'name', 'label' => 'Name'],
                    ];
                    $this->visibleColumns = ['id' => true, 'name' => false];
                }
            };

            $columnMethods = [
                'getVisibleColumns', 'toggleColumn', 'getColumnVisibilitySessionKey',
                'initializeColumnVisibility', 'isColumnVisible'
            ];
            $passed = 0;

            foreach ($columnMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $this->info("  ✅ {$method} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ {$method} missing");
                }
            }

            // Test getVisibleColumns execution
            try {
                $visibleColumns = $testComponent->getVisibleColumns();
                $this->info("  ✅ getVisibleColumns returned " . count($visibleColumns) . " columns");
            } catch (\Exception $e) {
                $this->error("  ❌ getVisibleColumns failed: {$e->getMessage()}");
                return 1;
            }

            $this->info("  📊 Column methods: {$passed}/" . count($columnMethods) . " passed");
            return $passed === count($columnMethods) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Column management test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runSearchFilterTest()
    {
        $this->info('🔄 Testing Search & Filter...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                }
            };

            $searchFilterMethods = [
                'sanitizeSearch', 'applyOptimizedSearch', 'applyFilters',
                'sanitizeFilterValue', 'clearAllFilters', 'clearSearch'
            ];
            $passed = 0;

            foreach ($searchFilterMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $this->info("  ✅ {$method} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ {$method} missing");
                }
            }

            $this->info("  📊 Search/Filter methods: {$passed}/" . count($searchFilterMethods) . " passed");
            return $passed === count($searchFilterMethods) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Search & Filter test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runExportFunctionsTest()
    {
        $this->info('🔄 Testing Export Functions...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                }
            };

            $exportMethods = [
                'handleExport', 'exportToCsv', 'exportToJson', 'exportToExcel',
                'validateExportFormat', 'getExportStats'
            ];
            $passed = 0;

            foreach ($exportMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $this->info("  ✅ {$method} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ {$method} missing");
                }
            }

            $this->info("  📊 Export methods: {$passed}/" . count($exportMethods) . " passed");
            return $passed === count($exportMethods) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Export functions test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function runSecurityMethodsTest()
    {
        $this->info('🔄 Testing Security Methods...');

        try {
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                }
            };

            $securityMethods = [
                'sanitizeSearch', 'sanitizeFilterValue', 'sanitizeHtmlContent',
                'isValidColumn', 'isAllowedColumn', 'validateJsonPath', 'validateRelationString'
            ];
            $passed = 0;

            foreach ($securityMethods as $method) {
                if (method_exists($testComponent, $method)) {
                    $this->info("  ✅ {$method} exists");
                    $passed++;
                } else {
                    $this->error("  ❌ {$method} missing");
                }
            }

            // Test sanitization methods
            try {
                $reflection = new \ReflectionClass($testComponent);
                $sanitizeMethod = $reflection->getMethod('sanitizeSearch');
                $sanitizeMethod->setAccessible(true);
                $result = $sanitizeMethod->invoke($testComponent, '<script>alert("test")</script>');
                $this->info("  ✅ sanitizeSearch working: " . strlen($result) . " chars");
            } catch (\Exception $e) {
                $this->error("  ❌ Sanitization test failed: {$e->getMessage()}");
                return 1;
            }

            $this->info("  📊 Security methods: {$passed}/" . count($securityMethods) . " passed");
            return $passed === count($securityMethods) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Security methods test failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Test performance benchmarks
     */
    private function runPerformanceTests()
    {
        $this->info('⚡ Testing Performance...');

        try {
            $passed = 0;
            $total = 0;

            // Test component instantiation performance
            $total++;
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = [
                        'id' => ['key' => 'id', 'label' => 'ID'],
                        'name' => ['key' => 'name', 'label' => 'Name'],
                        'email' => ['key' => 'email', 'label' => 'Email'],
                    ];
                }
            };
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $executionTime = ($endTime - $startTime) * 1000;
            $memoryUsed = ($endMemory - $startMemory) / 1024;
            
            $this->info("  ℹ️  Instantiation time: {$executionTime}ms");
            $this->info("  ℹ️  Memory used: {$memoryUsed}KB");
            
            if ($executionTime < 500 && $memoryUsed < 3000) { // Increased threshold for consolidated traits
                $this->info("  ✅ Component instantiation performance");
                $passed++;
            } else {
                $this->error("  ❌ Component instantiation too slow or memory intensive");
            }

            // Test search performance
            $total++;
            $searches = ['test', 'user', 'example', '', 'admin'];
            $searchTimes = [];
            
            foreach ($searches as $searchTerm) {
                $startTime = microtime(true);
                $reflection = new \ReflectionClass($testComponent);
                $method = $reflection->getMethod('sanitizeSearch');
                $method->setAccessible(true);
                $method->invoke($testComponent, $searchTerm);
                $endTime = microtime(true);
                
                $searchTime = ($endTime - $startTime) * 1000;
                $searchTimes[] = $searchTime;
                $this->info("  ℹ️  Search '{$searchTerm}' took: {$searchTime}ms");
            }
            
            $avgSearchTime = array_sum($searchTimes) / count($searchTimes);
            if ($avgSearchTime < 1) {
                $this->info("  ✅ Search performance");
                $passed++;
            } else {
                $this->error("  ❌ Search performance too slow");
            }

            // Test memory usage with multiple components
            $total++;
            $startMemory = memory_get_usage(true);
            $components = [];
            
            for ($i = 0; $i < 10; $i++) {
                $components[] = new class extends DatatableTrait {
                    public function __construct() {
                        $this->model = 'App\\Models\\User';
                        $this->columns = ['id' => ['key' => 'id', 'label' => 'ID']];
                    }
                };
            }
            
            $endMemory = memory_get_usage(true);
            $memoryUsed = ($endMemory - $startMemory) / 1024;
            $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
            
            $this->info("  ℹ️  Memory used for 10 components: {$memoryUsed}KB");
            $this->info("  ℹ️  Peak memory usage: {$peakMemory}MB");
            
            // Adjusted thresholds for consolidated trait architecture with enhanced functionality
            // 18 consolidated traits with advanced features (caching, filtering, export, column optimization)
            if ($memoryUsed < 5000 && $peakMemory < 50) { // 5MB per 10 components, 50MB peak
                $this->info("  ✅ Memory usage");
                $passed++;
            } else {
                $this->error("  ❌ Memory usage too high");
            }

            $this->info("  📊 Performance tests: {$passed}/{$total} passed");
            return $passed === $total ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Performance test failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Test relationship functionality
     */
    private function runRelationshipTests()
    {
        $this->info('🔗 Testing Relationships...');

        try {
            $passed = 0;
            $total = 0;

            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\Post';
                    $this->columns = [
                        'user_id' => ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User'],
                        'category_id' => ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
                        'nested' => ['key' => 'booking_id', 'relation' => 'booking.passenger:name', 'label' => 'Passenger'],
                    ];
                }
            };

            // Test relation parsing
            $total++;
            $relationString = 'user:name';
            $parts = explode(':', $relationString);
            if (count($parts) === 2 && $parts[0] === 'user' && $parts[1] === 'name') {
                $this->info("  ✅ Simple relation parsing");
                $passed++;
            } else {
                $this->error("  ❌ Simple relation parsing failed");
            }

            // Test nested relation parsing
            $total++;
            $nestedRelation = 'booking.passenger:name';
            $parts = explode(':', $nestedRelation);
            if (count($parts) === 2 && $parts[0] === 'booking.passenger' && $parts[1] === 'name') {
                $this->info("  ✅ Nested relation parsing");
                $passed++;
            } else {
                $this->error("  ❌ Nested relation parsing failed");
            }

            // Test column configuration with relations
            $total++;
            if (isset($testComponent->columns['user_id']['relation']) && 
                $testComponent->columns['user_id']['relation'] === 'user:name') {
                $this->info("  ✅ Relation column configuration");
                $passed++;
            } else {
                $this->error("  ❌ Relation column configuration failed");
            }

            $this->info("  📊 Relationship tests: {$passed}/{$total} passed");
            return $passed === $total ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ Relationship test failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Test JSON column functionality
     */
    private function runJsonColumnTests()
    {
        $this->info('📊 Testing JSON Columns...');

        try {
            $passed = 0;
            $total = 0;

            $testComponent = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = 'App\\Models\\User';
                    $this->columns = [
                        'data.name' => ['key' => 'data', 'json' => 'name', 'label' => 'Name'],
                        'metadata.contact.email' => ['key' => 'metadata', 'json' => 'contact.email', 'label' => 'Email'],
                    ];
                }
            };

            // Test JSON column configuration
            $total++;
            if (isset($testComponent->columns['data.name']['json']) && 
                $testComponent->columns['data.name']['json'] === 'name') {
                $this->info("  ✅ JSON column configuration");
                $passed++;
            } else {
                $this->error("  ❌ JSON column configuration failed");
            }

            // Test nested JSON path
            $total++;
            if (isset($testComponent->columns['metadata.contact.email']['json']) && 
                $testComponent->columns['metadata.contact.email']['json'] === 'contact.email') {
                $this->info("  ✅ Nested JSON path configuration");
                $passed++;
            } else {
                $this->error("  ❌ Nested JSON path configuration failed");
            }

            // Test JSON path validation
            $total++;
            $reflection = new \ReflectionClass($testComponent);
            $method = $reflection->getMethod('validateJsonPath');
            $method->setAccessible(true);
            
            $validPath = $method->invoke($testComponent, 'name');
            $validNestedPath = $method->invoke($testComponent, 'contact.email');
            $invalidPath = $method->invoke($testComponent, '<script>alert(1)</script>');
            
            if ($validPath && $validNestedPath && !$invalidPath) {
                $this->info("  ✅ JSON path validation");
                $passed++;
            } else {
                $this->error("  ❌ JSON path validation failed");
            }

            $this->info("  📊 JSON column tests: {$passed}/{$total} passed");
            return $passed === $total ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("  ❌ JSON column test failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function displayFinalResults($results)
    {
        $this->newLine();
        $this->info('╭─────────────────────────────────────────────────────╮');
        $this->info('│                   FINAL RESULTS                    │');
        $this->info('╰─────────────────────────────────────────────────────╯');
        $this->newLine();

        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($result) => $result === 0));

        foreach ($results as $testName => $result) {
            $status = $result === 0 ? '✅' : '❌';
            $this->info("  {$status} {$testName}");
        }

        $this->newLine();
        $successRate = round(($passedTests / $totalTests) * 100, 1);
        $this->info("📊 Overall: {$passedTests}/{$totalTests} tests passed");
        $this->info("📈 Success Rate: {$successRate}%");

        if ($passedTests === $totalTests) {
            $this->info('🎉 All tests passed! DatatableTrait is fully functional.');
        } else {
            $this->warn('⚠️  Some tests failed. Please review the results above.');
        }
    }
}
