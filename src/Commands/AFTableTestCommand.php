<?php

namespace ArtflowStudio\Table\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ArtflowStudio\Table\Testing\ComponentTestRunner;
use ArtflowStudio\Table\Testing\PerformanceTestRunner;
use ArtflowStudio\Table\Testing\RelationshipTestRunner;
use ArtflowStudio\Table\Testing\DatabaseTestRunner;
use ArtflowStudio\Table\Testing\JsonTestRunner;
use ArtflowStudio\Table\Testing\ExportTestRunner;
use ArtflowStudio\Table\Testing\SecurityTestRunner;

class AFTableTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'af-table:test 
                           {--suite= : Run specific test suite}
                           {--detailed : Show detailed output}
                           {--setup : Setup test environment}
                           {--cleanup : Cleanup test data}';

    /**
     * The console command description.
     */
    protected $description = 'Interactive testing suite for AF Table package';

    /**
     * Test runners
     */
    protected array $testRunners = [];

    /**
     * Initialize command
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->testRunners = [
            'component' => ComponentTestRunner::class,
            'performance' => PerformanceTestRunner::class,
            'relationships' => RelationshipTestRunner::class,
            'database' => DatabaseTestRunner::class,
            'json' => JsonTestRunner::class,
            'export' => ExportTestRunner::class,
            'security' => SecurityTestRunner::class,
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->showBanner();

        // Handle specific options
        if ($this->option('setup')) {
            return $this->setupTestEnvironment();
        }

        if ($this->option('cleanup')) {
            return $this->cleanupTestData();
        }

        if ($this->option('suite')) {
            return $this->runSpecificSuite($this->option('suite'));
        }

        // Interactive mode
        return $this->runInteractiveMode();
    }

    /**
     * Show application banner
     */
    protected function showBanner(): void
    {
        $this->line('');
        $this->line('╭─────────────────────────────────────────────────────╮');
        $this->line('│                 AF TABLE TEST SUITE                 │');
        $this->line('│            Comprehensive Testing Framework          │');
        $this->line('╰─────────────────────────────────────────────────────╯');
        $this->line('');
        $this->info('🚀 Welcome to AF Table Interactive Testing Suite');
        $this->line('   Version: 2.8.0 | Laravel Livewire Datatable Package');
        $this->line('');
    }

    /**
     * Run interactive mode
     */
    protected function runInteractiveMode(): int
    {
        while (true) {
            $this->showMainMenu();
            $choice = $this->ask('Please select an option');

            switch ($choice) {
                case '0':
                    return $this->runAllTests();

                case '1':
                    return $this->runComponentTests();

                case '2':
                    return $this->runPerformanceTests();

                case '3':
                    return $this->runRelationshipTests();

                case '4':
                    return $this->runDatabaseTests();

                case '5':
                    return $this->runJsonColumnTests();

                case '6':
                    return $this->runExportTests();

                case '7':
                    return $this->runSecurityTests();

                case '8':
                    return $this->setupTestEnvironment();

                case '9':
                    return $this->cleanupTestData();

                case '10':
                    return $this->showTestReport();

                case 'q':
                case 'quit':
                case 'exit':
                    $this->info('👋 Goodbye! Thanks for testing AF Table.');
                    return 0;

                default:
                    $this->error('❌ Invalid option. Please try again.');
                    continue 2;
            }
        }
    }

    /**
     * Show main menu
     */
    protected function showMainMenu(): void
    {
        $this->line('');
        $this->line('📋 Available Test Suites:');
        $this->line('');
        $this->line('  [0]  🎯 Run All Tests (Comprehensive)');
        $this->line('  [1]  🧩 Component Tests (Livewire, UI, Events)');
        $this->line('  [2]  ⚡ Performance Tests (Speed, Memory, Queries)');
        $this->line('  [3]  🔗 Relationship Tests (Eager Loading, Nested Relations)');
        $this->line('  [4]  🗄️  Database Tests (Queries, Validation, Integrity)');
        $this->line('  [5]  📊 JSON Column Tests (Extraction, Validation)');
        $this->line('  [6]  📤 Export Tests (CSV, Excel, PDF)');
        $this->line('  [7]  🔒 Security Tests (SQL Injection, XSS, Validation)');
        $this->line('');
        $this->line('📋 Utility Options:');
        $this->line('');
        $this->line('  [8]  🛠️  Setup Test Environment');
        $this->line('  [9]  🧹 Cleanup Test Data');
        $this->line('  [10] 📊 View Test Report');
        $this->line('  [q]  👋 Quit');
        $this->line('');
    }

    /**
     * Run all tests
     */
    protected function runAllTests(): int
    {
        $this->info('🎯 Running Comprehensive Test Suite...');
        $this->line('');

        $results = [];
        $totalTests = 0;
        $passedTests = 0;

        foreach ($this->testRunners as $name => $class) {
            $this->info("🔄 Running {$name} tests...");
            
            $runner = new $class($this);
            $result = $runner->run();
            
            $results[$name] = $result;
            $totalTests += $result['total'];
            $passedTests += $result['passed'];

            if ($result['passed'] === $result['total']) {
                $this->info("✅ {$name} tests: {$result['passed']}/{$result['total']} passed");
            } else {
                $this->error("❌ {$name} tests: {$result['passed']}/{$result['total']} passed");
            }
            
            $this->line('');
        }

        return $this->showFinalResults($results, $totalTests, $passedTests);
    }

    /**
     * Run component tests
     */
    protected function runComponentTests(): int
    {
        $this->info('🧩 Running Component Tests...');
        $runner = new ComponentTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Run performance tests
     */
    protected function runPerformanceTests(): int
    {
        $this->info('⚡ Running Performance Tests...');
        $runner = new PerformanceTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Run relationship tests
     */
    protected function runRelationshipTests(): int
    {
        $this->info('🔗 Running Relationship Tests...');
        $runner = new RelationshipTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Run database tests
     */
    protected function runDatabaseTests(): int
    {
        $this->info('🗄️ Running Database Tests...');
        $runner = new DatabaseTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Run JSON column tests
     */
    protected function runJsonColumnTests(): int
    {
        $this->info('📊 Running JSON Column Tests...');
        $runner = new JsonTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Run export tests
     */
    protected function runExportTests(): int
    {
        $this->info('📤 Running Export Tests...');
        $runner = new ExportTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Run security tests
     */
    protected function runSecurityTests(): int
    {
        $this->info('🔒 Running Security Tests...');
        $runner = new SecurityTestRunner($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Setup test environment
     */
    protected function setupTestEnvironment(): int
    {
        $this->info('🛠️ Setting up test environment...');
        
        try {
            // Step 1: Check Laravel installation
            $this->line('  • Checking Laravel installation...');
            if (!app()->version()) {
                $this->error('❌ Laravel installation not found!');
                return 1;
            }
            $this->line('    ✓ Laravel ' . app()->version());

            // Step 2: Verify database connection
            $this->line('  • Testing database connection...');
            try {
                DB::connection()->getPDO();
                $this->line('    ✓ Database connection successful');
            } catch (\Exception $e) {
                $this->error('❌ Database connection failed: ' . $e->getMessage());
                return 1;
            }

            // Step 3: Run migrations with seed
            $this->line('  • Running AF Table migrations...');
            try {
                $exitCode = Artisan::call('af-table:migrate', [
                    '--seed' => true,
                    '--force' => true,
                ]);
                
                if ($exitCode !== 0) {
                    $this->error('❌ Migration failed!');
                    return 1;
                }
                $this->line('    ✓ Migrations and seeding completed');
            } catch (\Exception $e) {
                $this->error('❌ Migration error: ' . $e->getMessage());
                return 1;
            }

            // Step 4: Verify test tables exist
            $this->line('  • Verifying test tables...');
            $tablesToCheck = [
                'test_companies',
                'test_departments',
                'test_employees',
                'test_projects',
                'test_tasks',
                'test_employee_project',
                'test_clients',
                'test_invoices',
                'test_timesheets',
                'test_documents',
            ];

            $tablesFound = 0;
            foreach ($tablesToCheck as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    $this->line("    ✓ {$table} ({$count} records)");
                    $tablesFound++;
                } else {
                    $this->warn("    ⚠ {$table} not found");
                }
            }

            if ($tablesFound === count($tablesToCheck)) {
                $this->info('✅ Test environment setup completed successfully!');
                $this->line('');
                $this->info('🎉 Ready to run tests! Use: php artisan af-table:test');
                return 0;
            } else {
                $this->error("⚠️  Only {$tablesFound}/" . count($tablesToCheck) . " tables were created");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Cleanup test data
     */
    protected function cleanupTestData(): int
    {
        $this->info('🧹 Cleaning up test data...');
        
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $tables = [
                'test_documents',
                'test_timesheets',
                'test_invoices',
                'test_clients',
                'test_employee_project',
                'test_tasks',
                'test_projects',
                'test_employees',
                'test_departments',
                'test_companies',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->line('  • Truncating ' . $table . '...');
                    DB::table($table)->truncate();
                    $this->line('    ✓ Cleared');
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('✅ Test data cleanup completed successfully!');
            return 0;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show test report
     */
    protected function showTestReport(): int
    {
        $this->info('📊 Generating Test Report...');
        
        // Implementation for test reporting
        $this->line('');
        $this->line('📋 Test Report Summary:');
        $this->line('');
        $this->line('• Last Run: ' . now()->format('Y-m-d H:i:s'));
        $this->line('• Environment: ' . app()->environment());
        $this->line('• Laravel Version: ' . app()->version());
        $this->line('• PHP Version: ' . PHP_VERSION);
        $this->line('');
        
        $this->info('📊 Report generated successfully!');
        return 0;
    }

    /**
     * Run specific test suite
     */
    protected function runSpecificSuite(string $suite): int
    {
        if (!isset($this->testRunners[$suite])) {
            $this->error("❌ Unknown test suite: {$suite}");
            $this->info("Available suites: " . implode(', ', array_keys($this->testRunners)));
            return 1;
        }

        $this->info("🔄 Running {$suite} test suite...");
        $runner = new $this->testRunners[$suite]($this);
        return $this->handleTestResult($runner->run());
    }

    /**
     * Handle test result
     */
    protected function handleTestResult(array $result): int
    {
        $this->line('');
        
        if ($result['passed'] === $result['total']) {
            $this->info("✅ All tests passed! ({$result['passed']}/{$result['total']})");
            return 0;
        } else {
            $this->error("❌ Some tests failed. ({$result['passed']}/{$result['total']} passed)");
            
            if (!empty($result['failures'])) {
                $this->line('');
                $this->error('Failed tests:');
                foreach ($result['failures'] as $failure) {
                    $this->line("  • {$failure}");
                }
            }
            
            return 1;
        }
    }

    /**
     * Show final results
     */
    protected function showFinalResults(array $results, int $totalTests, int $passedTests): int
    {
        $this->line('');
        $this->line('╭─────────────────────────────────────────────────────╮');
        $this->line('│                   FINAL RESULTS                    │');
        $this->line('╰─────────────────────────────────────────────────────╯');
        $this->line('');
        
        foreach ($results as $suite => $result) {
            $status = $result['passed'] === $result['total'] ? '✅' : '❌';
            $this->line("  {$status} {$suite}: {$result['passed']}/{$result['total']} tests passed");
        }
        
        $this->line('');
        $this->line("📊 Overall: {$passedTests}/{$totalTests} tests passed");
        
        $percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        $this->line("📈 Success Rate: {$percentage}%");
        
        $this->line('');
        
        if ($passedTests === $totalTests) {
            $this->info('🎉 All tests passed! Your AF Table package is working perfectly.');
            return 0;
        } else {
            $this->error('⚠️  Some tests failed. Please review the results above.');
            return 1;
        }
    }
}
