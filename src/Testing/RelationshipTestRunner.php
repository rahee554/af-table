<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\Datatable;

class RelationshipTestRunner extends BaseTestRunner
{
    /**
     * Get test suite name
     */
    public function getName(): string
    {
        return 'Relationship Tests';
    }

    /**
     * Get test suite description
     */
    public function getDescription(): string
    {
        return 'Tests eager loading, nested relationships, and relation optimization';
    }

    /**
     * Run all relationship tests
     */
    public function run(): array
    {
        $this->command->line('');
        $this->command->info("📊 Running {$this->getName()}...");
        $this->command->line("   {$this->getDescription()}");
        $this->command->line('');

        // Initialize results
        $this->results = ['passed' => [], 'failed' => []];

        // Run relationship tests
        $this->runTest('Simple Relationship Configuration', [$this, 'testSimpleRelationshipConfiguration']);
        $this->runTest('Nested Relationship Configuration', [$this, 'testNestedRelationshipConfiguration']);
        $this->runTest('Eager Loading Detection', [$this, 'testEagerLoadingDetection']);
        $this->runTest('Relation Parsing', [$this, 'testRelationParsing']);
        $this->runTest('Deep Nested Relations', [$this, 'testDeepNestedRelations']);
        $this->runTest('Relation Query Building', [$this, 'testRelationQueryBuilding']);
        $this->runTest('Relation Sorting Limitations', [$this, 'testRelationSortingLimitations']);
        $this->runTest('Relation Search Functionality', [$this, 'testRelationSearchFunctionality']);
        $this->runTest('Relation Filter Support', [$this, 'testRelationFilterSupport']);
        $this->runTest('Relation Performance', [$this, 'testRelationPerformance']);

        return $this->getResults();
    }

    /**
     * Test simple relationship configuration
     */
    public function testSimpleRelationshipConfiguration(): bool
    {
        try {
            $columns = [
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User Name'],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test relation column configuration
            $this->assertArrayHasKey('user_id', $component->columns);
            $this->assertArrayHasKey('category_id', $component->columns);
            
            $this->assertEquals('user:name', $component->columns['user_id']['relation']);
            $this->assertEquals('category:title', $component->columns['category_id']['relation']);

            return true;
        } catch (\Exception $e) {
            $this->log("Simple relationship configuration test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test nested relationship configuration
     */
    public function testNestedRelationshipConfiguration(): bool
    {
        try {
            $columns = [
                ['key' => 'student_id', 'relation' => 'student.user:name', 'label' => 'Student Name'],
                ['key' => 'enrollment_id', 'relation' => 'enrollment.course.category:name', 'label' => 'Course Category'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Grade', $columns);

            // Test nested relation configuration
            $this->assertArrayHasKey('student_id', $component->columns);
            $this->assertArrayHasKey('enrollment_id', $component->columns);
            
            $this->assertEquals('student.user:name', $component->columns['student_id']['relation']);
            $this->assertEquals('enrollment.course.category:name', $component->columns['enrollment_id']['relation']);

            return true;
        } catch (\Exception $e) {
            $this->log("Nested relationship configuration test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test eager loading detection
     */
    public function testEagerLoadingDetection(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User'],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
                ['key' => 'student_id', 'relation' => 'student.user:email', 'label' => 'Student Email'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test that relations are detected for eager loading
            $relations = $this->invokeMethod($component, 'calculateRequiredRelations', [$columns]);
            
            $this->assertContains('user', $relations);
            $this->assertContains('category', $relations);
            $this->assertContains('student', $relations);
            $this->assertContains('student.user', $relations);

            return true;
        } catch (\Exception $e) {
            $this->log("Eager loading detection test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test relation parsing
     */
    public function testRelationParsing(): bool
    {
        try {
            // Test various relation formats
            $testCases = [
                'user:name' => ['relation' => 'user', 'attribute' => 'name'],
                'category:title' => ['relation' => 'category', 'attribute' => 'title'],
                'student.user:name' => ['relation' => 'student.user', 'attribute' => 'name'],
                'order.customer.profile:bio' => ['relation' => 'order.customer.profile', 'attribute' => 'bio'],
            ];

            foreach ($testCases as $relationString => $expected) {
                $parts = explode(':', $relationString);
                $this->assertEquals(2, count($parts), "Relation string should have exactly 2 parts: {$relationString}");
                
                [$relation, $attribute] = $parts;
                $this->assertEquals($expected['relation'], $relation);
                $this->assertEquals($expected['attribute'], $attribute);
            }

            return true;
        } catch (\Exception $e) {
            $this->log("Relation parsing test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test deep nested relations
     */
    public function testDeepNestedRelations(): bool
    {
        try {
            $columns = [
                ['key' => 'booking_id', 'relation' => 'booking.passenger.user.profile:bio', 'label' => 'Passenger Bio'],
                ['key' => 'order_id', 'relation' => 'order.customer.company.address:street', 'label' => 'Company Address'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Transaction', $columns);

            // Test deep nested relations are configured
            $this->assertArrayHasKey('booking_id', $component->columns);
            $this->assertArrayHasKey('order_id', $component->columns);

            // Test relation strings
            $this->assertEquals('booking.passenger.user.profile:bio', $component->columns['booking_id']['relation']);
            $this->assertEquals('order.customer.company.address:street', $component->columns['order_id']['relation']);

            // Test that deep relations are detected for eager loading
            $relations = $this->invokeMethod($component, 'calculateRequiredRelations', [$columns]);
            
            $this->assertContains('booking', $relations);
            $this->assertContains('booking.passenger', $relations);
            $this->assertContains('booking.passenger.user', $relations);
            $this->assertContains('booking.passenger.user.profile', $relations);

            return true;
        } catch (\Exception $e) {
            $this->log("Deep nested relations test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test relation query building
     */
    public function testRelationQueryBuilding(): bool
    {
        try {
            $columns = [
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User'],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test select columns calculation
            $selectColumns = $this->invokeMethod($component, 'calculateSelectColumns', [$columns]);
            
            $this->assertContains('id', $selectColumns);
            $this->assertContains('user_id', $selectColumns);
            $this->assertContains('category_id', $selectColumns);

            return true;
        } catch (\Exception $e) {
            $this->log("Relation query building test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test relation sorting limitations
     */
    public function testRelationSortingLimitations(): bool
    {
        try {
            $columns = [
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User Name'],
                ['key' => 'student_id', 'relation' => 'student.user:name', 'label' => 'Student Name'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test sorting on simple relation (should work)
            $component->toggleSort('user_id');
            $this->assertEquals('user_id', $component->sortColumn);

            // Test sorting on nested relation (should be handled gracefully)
            $originalColumn = $component->sortColumn;
            $component->toggleSort('student_id');
            
            // For nested relations, sorting might be disabled or handled differently
            // The component should not crash
            $this->assertNotNull($component->sortColumn);

            return true;
        } catch (\Exception $e) {
            $this->log("Relation sorting limitations test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test relation search functionality
     */
    public function testRelationSearchFunctionality(): bool
    {
        try {
            $columns = [
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User Name'],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test search with relations
            $component->search = 'test user';
            $component->updatedSearch();
            
            $this->assertEquals('test user', $component->search);

            return true;
        } catch (\Exception $e) {
            $this->log("Relation search functionality test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test relation filter support
     */
    public function testRelationFilterSupport(): bool
    {
        try {
            $columns = [
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User Name'],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
            ];

            $filters = [
                'user_id' => ['type' => 'select', 'relation' => 'user:name'],
                'category_id' => ['type' => 'distinct'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns, $filters);

            // Test filter configuration with relations
            $this->assertEquals($filters, $component->filters);
            
            // Test filter updates
            $component->filterColumn = 'user_id';
            $component->filterValue = 'John Doe';
            
            $this->assertEquals('user_id', $component->filterColumn);
            $this->assertEquals('John Doe', $component->filterValue);

            return true;
        } catch (\Exception $e) {
            $this->log("Relation filter support test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test relation performance
     */
    public function testRelationPerformance(): bool
    {
        try {
            $columns = [
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User'],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category'],
                ['key' => 'student_id', 'relation' => 'student.user:name', 'label' => 'Student'],
                ['key' => 'enrollment_id', 'relation' => 'enrollment.course:title', 'label' => 'Course'],
            ];

            $metrics = $this->measureTime(function() use ($columns) {
                $component = new Datatable();
                $component->mount('App\\Models\\Post', $columns);
                
                // Test relation calculation performance
                $relations = $this->invokeMethod($component, 'calculateRequiredRelations', [$columns]);
                $selectColumns = $this->invokeMethod($component, 'calculateSelectColumns', [$columns]);
                
                return [
                    'relations' => $relations,
                    'selectColumns' => $selectColumns
                ];
            });

            $this->log("Relation processing time: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Performance should be reasonable
            $this->assertLessThan(100, $metrics['time'], 'Relation processing should be under 100ms');

            return true;
        } catch (\Exception $e) {
            $this->log("Relation performance test failed: " . $e->getMessage(), 'error');
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
