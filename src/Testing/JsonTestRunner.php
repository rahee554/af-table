<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\DatatableJson;

class JsonTestRunner extends BaseTestRunner
{
    /**
     * Get test suite name
     */
    public function getName(): string
    {
        return 'JSON Tests';
    }

    /**
     * Get test suite description
     */
    public function getDescription(): string
    {
        return 'Tests JSON column support, nested data handling, and performance';
    }

    /**
     * Run all JSON tests
     */
    public function run(): array
    {
        $this->command->line('');
        $this->command->info("📋 Running {$this->getName()}...");
        $this->command->line("   {$this->getDescription()}");
        $this->command->line('');

        // Initialize results
        $this->results = ['passed' => [], 'failed' => []];

        // Run JSON tests
        $this->runTest('JSON Component Initialization', [$this, 'testJsonComponentInitialization']);
        $this->runTest('JSON Column Configuration', [$this, 'testJsonColumnConfiguration']);
        $this->runTest('JSON Data Rendering', [$this, 'testJsonDataRendering']);
        $this->runTest('JSON Search Functionality', [$this, 'testJsonSearchFunctionality']);
        $this->runTest('JSON Filter Support', [$this, 'testJsonFilterSupport']);
        $this->runTest('JSON Sorting Behavior', [$this, 'testJsonSortingBehavior']);
        $this->runTest('Nested JSON Data Access', [$this, 'testNestedJsonDataAccess']);
        $this->runTest('JSON Array Handling', [$this, 'testJsonArrayHandling']);
        $this->runTest('JSON Performance', [$this, 'testJsonPerformance']);
        $this->runTest('JSON Validation', [$this, 'testJsonValidation']);

        return $this->getResults();
    }

    /**
     * Test JSON component initialization
     */
    public function testJsonComponentInitialization(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'meta', 'label' => 'Meta Data', 'type' => 'json'],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'json'],
            ];

            $component = new DatatableJson();
            $component->mount('App\\Models\\Product', $columns);

            // Test that the component initializes correctly
            $this->assertEquals('App\\Models\\Product', $component->model);
            $this->assertTrue(is_array($component->columns));
            $this->assertEquals(3, count($component->columns));

            // Test JSON column type detection
            $this->assertEquals('json', $component->columns['meta']['type'] ?? '');
            $this->assertEquals('json', $component->columns['settings']['type'] ?? '');

            return true;
        } catch (\Exception $e) {
            $this->log("JSON component initialization test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON column configuration
     */
    public function testJsonColumnConfiguration(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'meta', 'label' => 'Meta', 'type' => 'json', 'path' => 'name'],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'json', 'path' => 'theme.color'],
                ['key' => 'tags', 'label' => 'Tags', 'type' => 'json', 'is_array' => true],
            ];

            $component = new DatatableJson();
            $component->mount('App\\Models\\Product', $columns);

            // Test JSON column configuration
            $this->assertTrue(isset($component->columns['meta']));
            $this->assertTrue(isset($component->columns['settings']));
            $this->assertTrue(isset($component->columns['tags']));

            // Test JSON path configuration
            $this->assertEquals('name', $component->columns['meta']['path'] ?? '');
            $this->assertEquals('theme.color', $component->columns['settings']['path'] ?? '');
            $this->assertTrue($component->columns['tags']['is_array'] ?? false);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON column configuration test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON data rendering
     */
    public function testJsonDataRendering(): bool
    {
        try {
            $testData = [
                'meta' => '{"name": "Product A", "category": "electronics"}',
                'settings' => '{"theme": {"color": "blue", "size": "large"}}',
                'tags' => '["new", "featured", "sale"]'
            ];

            $columns = [
                ['key' => 'meta', 'label' => 'Meta', 'type' => 'json', 'path' => 'name'],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'json', 'path' => 'theme.color'],
                ['key' => 'tags', 'label' => 'Tags', 'type' => 'json', 'is_array' => true],
            ];

            $component = new DatatableJson();
            $component->mount('App\\Models\\Product', $columns);

            // Test JSON data extraction
            $metaValue = $this->invokeMethod($component, 'extractJsonValue', [$testData['meta'], 'name']);
            $this->assertEquals('Product A', $metaValue);

            $settingsValue = $this->invokeMethod($component, 'extractJsonValue', [$testData['settings'], 'theme.color']);
            $this->assertEquals('blue', $settingsValue);

            $tagsValue = $this->invokeMethod($component, 'extractJsonValue', [$testData['tags'], null, true]);
            $this->assertEquals(['new', 'featured', 'sale'], $tagsValue);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON data rendering test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON search functionality
     */
    public function testJsonSearchFunctionality(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'meta', 'label' => 'Meta', 'type' => 'json', 'searchable' => true],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'json'],
            ];

            $component = new DatatableJson();
            $component->mount('App\\Models\\Product', $columns);

            // Test search on JSON columns
            $component->search = 'electronics';
            $component->updatedSearch();
            
            $this->assertEquals('electronics', $component->search);
            $this->assertEquals(1, $component->page ?? 1);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON search functionality test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON filter support
     */
    public function testJsonFilterSupport(): bool
    {
        try {
            $columns = [
                ['key' => 'meta', 'label' => 'Meta', 'type' => 'json', 'path' => 'category'],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'json', 'path' => 'theme.color'],
            ];

            $filters = [
                'meta' => ['type' => 'json', 'path' => 'category'],
                'settings' => ['type' => 'json', 'path' => 'theme.color'],
            ];

            $component = new DatatableJson();
            $component->mount('App\\Models\\Product', $columns, $filters);

            // Test JSON filter configuration
            $this->assertEquals($filters, $component->filters);

            // Test JSON filter application
            $component->filterColumn = 'meta';
            $component->filterValue = 'electronics';
            
            $this->assertEquals('meta', $component->filterColumn);
            $this->assertEquals('electronics', $component->filterValue);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON filter support test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON sorting behavior
     */
    public function testJsonSortingBehavior(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'meta', 'label' => 'Meta', 'type' => 'json', 'path' => 'name'],
                ['key' => 'settings', 'label' => 'Settings', 'type' => 'json', 'path' => 'priority'],
            ];

            $component = new DatatableJson();
            $component->mount('App\\Models\\Product', $columns);

            // Test sorting on JSON columns
            $component->toggleSort('meta');
            $this->assertEquals('meta', $component->sortColumn);

            // Test sorting on nested JSON paths
            $component->toggleSort('settings');
            $this->assertEquals('settings', $component->sortColumn);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON sorting behavior test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test nested JSON data access
     */
    public function testNestedJsonDataAccess(): bool
    {
        try {
            $testJson = '{"user": {"profile": {"name": "John Doe", "age": 30}, "settings": {"theme": "dark"}}}';

            $component = new DatatableJson();
            
            // Test deep nested access
            $name = $this->invokeMethod($component, 'extractJsonValue', [$testJson, 'user.profile.name']);
            $this->assertEquals('John Doe', $name);

            $age = $this->invokeMethod($component, 'extractJsonValue', [$testJson, 'user.profile.age']);
            $this->assertEquals(30, $age);

            $theme = $this->invokeMethod($component, 'extractJsonValue', [$testJson, 'user.settings.theme']);
            $this->assertEquals('dark', $theme);

            // Test non-existent path
            $nonExistent = $this->invokeMethod($component, 'extractJsonValue', [$testJson, 'user.profile.email']);
            $this->assertNull($nonExistent);

            return true;
        } catch (\Exception $e) {
            $this->log("Nested JSON data access test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON array handling
     */
    public function testJsonArrayHandling(): bool
    {
        try {
            $arrayJson = '["apple", "banana", "cherry"]';
            $objectArrayJson = '[{"name": "Item 1", "value": 10}, {"name": "Item 2", "value": 20}]';

            $component = new DatatableJson();

            // Test simple array
            $simpleArray = $this->invokeMethod($component, 'extractJsonValue', [$arrayJson, null, true]);
            $this->assertEquals(['apple', 'banana', 'cherry'], $simpleArray);

            // Test object array
            $objectArray = $this->invokeMethod($component, 'extractJsonValue', [$objectArrayJson, null, true]);
            $this->assertTrue(is_array($objectArray));
            $this->assertEquals(2, count($objectArray));
            $this->assertEquals('Item 1', $objectArray[0]['name']);

            // Test array element access
            $firstElement = $this->invokeMethod($component, 'extractJsonValue', [$objectArrayJson, '0.name']);
            $this->assertEquals('Item 1', $firstElement);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON array handling test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON performance
     */
    public function testJsonPerformance(): bool
    {
        try {
            // Create complex JSON data
            $complexJson = json_encode([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'data' => 'deep value',
                            'array' => range(1, 100),
                            'objects' => array_map(function($i) {
                                return ['id' => $i, 'name' => "Item {$i}"];
                            }, range(1, 50))
                        ]
                    ]
                ]
            ]);

            $metrics = $this->measureTime(function() use ($complexJson) {
                $component = new DatatableJson();
                
                // Test multiple JSON extractions
                $values = [];
                for ($i = 0; $i < 100; $i++) {
                    $values[] = $this->invokeMethod($component, 'extractJsonValue', [$complexJson, 'level1.level2.level3.data']);
                    $values[] = $this->invokeMethod($component, 'extractJsonValue', [$complexJson, 'level1.level2.level3.array', true]);
                }
                
                return $values;
            });

            $this->log("JSON processing time: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Performance should be reasonable
            $this->assertLessThan(500, $metrics['time'], 'JSON processing should be under 500ms');

            return true;
        } catch (\Exception $e) {
            $this->log("JSON performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON validation
     */
    public function testJsonValidation(): bool
    {
        try {
            $component = new DatatableJson();

            // Test valid JSON
            $validJson = '{"name": "John", "age": 30}';
            $result = $this->invokeMethod($component, 'extractJsonValue', [$validJson, 'name']);
            $this->assertEquals('John', $result);

            // Test invalid JSON
            $invalidJson = '{"name": "John", "age":}';
            $result = $this->invokeMethod($component, 'extractJsonValue', [$invalidJson, 'name']);
            $this->assertNull($result);

            // Test empty JSON
            $emptyJson = '';
            $result = $this->invokeMethod($component, 'extractJsonValue', [$emptyJson, 'name']);
            $this->assertNull($result);

            // Test null JSON
            $nullJson = null;
            $result = $this->invokeMethod($component, 'extractJsonValue', [$nullJson, 'name']);
            $this->assertNull($result);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON validation test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Helper method to invoke protected methods for testing
     */
    private function invokeMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
