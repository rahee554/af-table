<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\Datatable;
use Livewire\Livewire;

class ComponentTestRunner extends BaseTestRunner
{
    /**
     * Get test suite name
     */
    public function getName(): string
    {
        return 'Component Tests';
    }

    /**
     * Get test suite description
     */
    public function getDescription(): string
    {
        return 'Tests Livewire component functionality, UI rendering, and event handling';
    }

    /**
     * Run all component tests
     */
    public function run(): array
    {
        $this->command->line('');
        $this->command->info("📊 Running {$this->getName()}...");
        $this->command->line("   {$this->getDescription()}");
        $this->command->line('');

        // Initialize results
        $this->results = ['passed' => [], 'failed' => []];

        // Run individual tests
        $this->runTest('Component Instantiation', [$this, 'testComponentInstantiation']);
        $this->runTest('Column Configuration', [$this, 'testColumnConfiguration']);
        $this->runTest('Search Functionality', [$this, 'testSearchFunctionality']);
        $this->runTest('Sorting Functionality', [$this, 'testSortingFunctionality']);
        $this->runTest('Filter Functionality', [$this, 'testFilterFunctionality']);
        $this->runTest('Pagination', [$this, 'testPagination']);
        $this->runTest('Column Visibility', [$this, 'testColumnVisibility']);
        $this->runTest('Event Handling', [$this, 'testEventHandling']);
        $this->runTest('JSON Column Support', [$this, 'testJsonColumnSupport']);
        $this->runTest('Raw Templates', [$this, 'testRawTemplates']);
        $this->runTest('Function Columns', [$this, 'testFunctionColumns']);
        $this->runTest('Action Buttons', [$this, 'testActionButtons']);

        return $this->getResults();
    }

    /**
     * Test component instantiation
     */
    public function testComponentInstantiation(): bool
    {
        try {
            // Test basic component creation
            $component = new Datatable();
            $this->assertInstanceOf(Datatable::class, $component);

            // Test with parameters
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);
            
            $this->assertEquals('App\\Models\\User', $component->model);
            $this->assertNotNull($component->columns);
            
            return true;
        } catch (\Exception $e) {
            $this->log("Component instantiation failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test column configuration
     */
    public function testColumnConfiguration(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'email', 'label' => 'Email', 'searchable' => true],
                ['key' => 'status', 'label' => 'Status', 'hide' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test column mapping
            $this->assertArrayHasKey('id', $component->columns);
            $this->assertArrayHasKey('name', $component->columns);
            $this->assertArrayHasKey('email', $component->columns);
            
            // Test column properties
            $this->assertEquals('ID', $component->columns['id']['label']);
            $this->assertTrue($component->columns['name']['sortable'] ?? false);
            $this->assertTrue($component->columns['email']['searchable'] ?? false);
            $this->assertTrue($component->columns['status']['hide'] ?? false);

            return true;
        } catch (\Exception $e) {
            $this->log("Column configuration test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test search functionality
     */
    public function testSearchFunctionality(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ]);

            // Test search property
            $this->assertEquals('', $component->search);
            
            // Test search update
            $component->search = 'test';
            $component->updatedSearch();
            $this->assertEquals('test', $component->search);

            // Test search sanitization
            $component->search = '<script>alert("xss")</script>';
            $sanitized = $component->sanitizeSearch($component->search);
            $this->assertFalse(strpos($sanitized, '<script>') !== false);

            return true;
        } catch (\Exception $e) {
            $this->log("Search functionality test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test sorting functionality
     */
    public function testSortingFunctionality(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
            ]);

            // Test initial sort
            $this->assertNotNull($component->sortColumn);
            $this->assertContains($component->sortDirection, ['asc', 'desc']);

            // Test sort toggle
            $originalDirection = $component->sortDirection;
            $component->toggleSort('name');
            
            if ($component->sortColumn === 'name') {
                $this->assertNotEquals($originalDirection, $component->sortDirection);
            } else {
                $this->assertEquals('name', $component->sortColumn);
                $this->assertEquals('asc', $component->sortDirection);
            }

            return true;
        } catch (\Exception $e) {
            $this->log("Sorting functionality test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test filter functionality
     */
    public function testFilterFunctionality(): bool
    {
        try {
            $filters = [
                'status' => ['type' => 'select'],
                'created_at' => ['type' => 'date'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'status', 'label' => 'Status'],
            ], $filters);

            $this->assertEquals($filters, $component->filters);

            // Test filter update
            $component->filterColumn = 'status';
            $component->filterValue = 'active';
            $component->updatedFilterValue();

            $this->assertEquals('status', $component->filterColumn);
            $this->assertEquals('active', $component->filterValue);

            return true;
        } catch (\Exception $e) {
            $this->log("Filter functionality test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test pagination
     */
    public function testPagination(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
            ]);

            // Test default records per page
            $this->assertEquals(10, $component->records);

            // Test records update
            $component->records = 25;
            $component->updatedrecords();
            $this->assertEquals(25, $component->records);

            return true;
        } catch (\Exception $e) {
            $this->log("Pagination test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test column visibility
     */
    public function testColumnVisibility(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email', 'hide' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test initial visibility
            $this->assertNotNull($component->visibleColumns);
            
            // Test visibility toggle
            $component->toggleColumnVisibility('email');
            
            // Verify the toggle worked (should now be visible since it was hidden)
            $this->assertTrue($component->visibleColumns['email']);

            return true;
        } catch (\Exception $e) {
            $this->log("Column visibility test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test event handling
     */
    public function testEventHandling(): bool
    {
        try {
            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
            ]);

            // Test refresh event
            $component->refreshTable();
            $this->assertEquals('', $component->search);

            // Test date range event
            $component->applyDateRange('2024-01-01', '2024-12-31');
            $this->assertEquals('2024-01-01', $component->startDate);
            $this->assertEquals('2024-12-31', $component->endDate);

            return true;
        } catch (\Exception $e) {
            $this->log("Event handling test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test JSON column support
     */
    public function testJsonColumnSupport(): bool
    {
        try {
            $columns = [
                ['key' => 'data', 'json' => 'name', 'label' => 'Name from JSON'],
                ['key' => 'data', 'json' => 'contact.email', 'label' => 'Email from JSON'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test JSON column mapping
            $this->assertArrayHasKey('data.name', $component->columns);
            $this->assertArrayHasKey('data.contact.email', $component->columns);

            // Test JSON value extraction
            $mockRow = $this->createMockModel([
                'data' => json_encode([
                    'name' => 'John Doe',
                    'contact' => ['email' => 'john@example.com']
                ])
            ]);

            $extractedName = $component->extractJsonValue($mockRow, 'data', 'name');
            $this->assertEquals('John Doe', $extractedName);

            $extractedEmail = $component->extractJsonValue($mockRow, 'data', 'contact.email');
            $this->assertEquals('john@example.com', $extractedEmail);

            return true;
        } catch (\Exception $e) {
            $this->log("JSON column support test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test raw templates
     */
    public function testRawTemplates(): bool
    {
        try {
            $columns = [
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'raw' => '<strong>{{ $row->name }}</strong>'
                ],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            $mockRow = $this->createMockModel(['name' => 'Test User']);
            $rendered = $component->renderRawHtml('<strong>{{ $row->name }}</strong>', $mockRow);

            $this->assertStringContains('Test User', $rendered);
            $this->assertStringContains('<strong>', $rendered);

            return true;
        } catch (\Exception $e) {
            $this->log("Raw templates test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test function columns
     */
    public function testFunctionColumns(): bool
    {
        try {
            $columns = [
                ['function' => 'isActive', 'label' => 'Status'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test function column mapping
            $this->assertArrayHasKey('isActive', $component->columns);
            $this->assertEquals('isActive', $component->columns['isActive']['function']);

            return true;
        } catch (\Exception $e) {
            $this->log("Function columns test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test action buttons
     */
    public function testActionButtons(): bool
    {
        try {
            $actions = [
                '<button wire:click="edit({{ $row->id }})">Edit</button>',
                '<button wire:click="delete({{ $row->id }})">Delete</button>',
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', [
                ['key' => 'name', 'label' => 'Name'],
            ], [], $actions);

            $this->assertEquals($actions, $component->actions);
            $this->assertEquals(2, count($component->actions));

            return true;
        } catch (\Exception $e) {
            $this->log("Action buttons test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
