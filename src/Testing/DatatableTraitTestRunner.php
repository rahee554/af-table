<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use ArtflowStudio\Table\Testing\Models\TestUser;

class DatatableTraitTestRunner extends BaseTestRunner
{
    public function getName(): string
    {
        return 'DatatableTrait Tests';
    }

    public function getDescription(): string
    {
        return 'Tests DatatableTrait functionality and trait-based architecture';
    }

    public function run(): array
    {
        echo "\n🧪 Testing DatatableTrait Functionality...\n";
        echo "   Verifying trait-based architecture works correctly\n\n";

        $this->testTraitMethods();
        $this->testTraitInstantiation();
        $this->testTraitMethodCollisions();
        $this->testTraitFunctionality();

        return $this->getTestStats();
    }

    protected function testTraitMethods()
    {
        $this->runTest('Trait Method Availability', function() {
            // Create a test class that uses DatatableTrait
            $testClass = new class extends DatatableTrait {
                public function __construct() {
                    // Minimal setup
                    $this->model = TestUser::class;
                    $this->columns = [
                        ['key' => 'name', 'label' => 'Name'],
                        ['key' => 'email', 'label' => 'Email']
                    ];
                }
            };

            // Test that trait methods are available
            $this->assertTrue(method_exists($testClass, 'sanitizeSearch'), 'sanitizeSearch method exists');
            $this->assertTrue(method_exists($testClass, 'validateRelationString'), 'validateRelationString method exists');
            $this->assertTrue(method_exists($testClass, 'validateJsonPath'), 'validateJsonPath method exists');
            $this->assertTrue(method_exists($testClass, 'sanitizeFilterValue'), 'sanitizeFilterValue method exists');
        });
    }

    protected function testTraitInstantiation()
    {
        $this->runTest('Trait Instantiation', function() {
            $testClass = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = TestUser::class;
                    $this->columns = [
                        ['key' => 'id', 'label' => 'ID'],
                        ['key' => 'name', 'label' => 'Name'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'status', 'label' => 'Status']
                    ];
                }
            };

            $this->assertNotNull($testClass, 'Test class instantiated');
            $this->assertEquals(TestUser::class, $testClass->model, 'Model set correctly');
            $this->assertIsArray($testClass->columns, 'Columns is array');
            $this->assertCount(4, $testClass->columns, 'Correct number of columns');
        });
    }

    protected function testTraitMethodCollisions()
    {
        $this->runTest('Trait Method Collision Resolution', function() {
            $testClass = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = TestUser::class;
                    $this->columns = [];
                }
            };

            // Test that sanitizeSearch method works (should come from HasSearch trait)
            $result = $testClass->sanitizeSearch('  test search  ');
            $this->assertEquals('test search', $result, 'sanitizeSearch works correctly');

            // Test a longer string
            $longString = str_repeat('a', 150);
            $result = $testClass->sanitizeSearch($longString);
            $this->assertEquals(100, strlen($result), 'sanitizeSearch limits length to 100');

            // Test null handling
            $result = $testClass->sanitizeSearch(null);
            $this->assertEquals('', $result, 'sanitizeSearch handles null correctly');
        });
    }

    protected function testTraitFunctionality()
    {
        $this->runTest('Trait Functionality', function() {
            $testClass = new class extends DatatableTrait {
                public function __construct() {
                    $this->model = TestUser::class;
                    $this->columns = [
                        ['key' => 'name', 'label' => 'Name'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'profile', 'label' => 'Profile', 'json' => 'first_name']
                    ];
                }
            };

            // Test JSON path validation
            $this->assertTrue($testClass->validateJsonPath('first_name'), 'Valid JSON path');
            $this->assertTrue($testClass->validateJsonPath('address.street'), 'Valid nested JSON path');
            $this->assertFalse($testClass->validateJsonPath(''), 'Empty JSON path invalid');
            $this->assertFalse($testClass->validateJsonPath(null), 'Null JSON path invalid');

            // Test relation string validation
            $this->assertTrue($testClass->validateRelationString('user:name'), 'Valid relation string');
            $this->assertTrue($testClass->validateRelationString('category:title'), 'Valid relation string');
            $this->assertFalse($testClass->validateRelationString('invalidrelation'), 'Invalid relation string');
            $this->assertFalse($testClass->validateRelationString(''), 'Empty relation string invalid');

            // Test filter value sanitization
            $result = $testClass->sanitizeFilterValue('  test value  ');
            $this->assertEquals('test value', $result, 'Filter value sanitized');

            $result = $testClass->sanitizeFilterValue(123);
            $this->assertEquals(123, $result, 'Non-string filter value unchanged');
        });
    }

    public function getTestStats(): array
    {
        $passed = count(array_filter($this->results, function($result) {
            return $result['status'] === 'passed';
        }));
        $failed = count($this->results) - $passed;

        return [
            'total_tests' => count($this->results),
            'passed_tests' => $passed,
            'failed_tests' => $failed,
            'test_duration' => 0,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }
}
