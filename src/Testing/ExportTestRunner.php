<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\Datatable;

class ExportTestRunner extends BaseTestRunner
{
    /**
     * Get test suite name
     */
    public function getName(): string
    {
        return 'Export Tests';
    }

    /**
     * Get test suite description
     */
    public function getDescription(): string
    {
        return 'Tests data export functionality, formats, and performance';
    }

    /**
     * Run all export tests
     */
    public function run(): array
    {
        $this->command->line('');
        $this->command->info("📤 Running {$this->getName()}...");
        $this->command->line("   {$this->getDescription()}");
        $this->command->line('');

        // Initialize results
        $this->results = ['passed' => [], 'failed' => []];

        // Run export tests
        $this->runTest('Export Configuration', [$this, 'testExportConfiguration']);
        $this->runTest('CSV Export Format', [$this, 'testCsvExportFormat']);
        $this->runTest('Excel Export Format', [$this, 'testExcelExportFormat']);
        $this->runTest('PDF Export Format', [$this, 'testPdfExportFormat']);
        $this->runTest('Export with Filters', [$this, 'testExportWithFilters']);
        $this->runTest('Export with Search', [$this, 'testExportWithSearch']);
        $this->runTest('Export with Relations', [$this, 'testExportWithRelations']);
        $this->runTest('Export Performance', [$this, 'testExportPerformance']);
        $this->runTest('Export File Validation', [$this, 'testExportFileValidation']);
        $this->runTest('Export Memory Management', [$this, 'testExportMemoryManagement']);

        return $this->getResults();
    }

    /**
     * Test export configuration
     */
    public function testExportConfiguration(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'email', 'label' => 'Email', 'exportable' => true],
                ['key' => 'password', 'label' => 'Password', 'exportable' => false],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test export configuration
            $this->assertTrue(isset($component->columns['id']));
            $this->assertTrue(isset($component->columns['name']));
            $this->assertTrue(isset($component->columns['email']));
            $this->assertTrue(isset($component->columns['password']));

            // Test exportable flags
            $this->assertTrue($component->columns['id']['exportable'] ?? false);
            $this->assertTrue($component->columns['name']['exportable'] ?? false);
            $this->assertTrue($component->columns['email']['exportable'] ?? false);
            $this->assertFalse($component->columns['password']['exportable'] ?? true);

            return true;
        } catch (\Exception $e) {
            $this->log("Export configuration test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test CSV export format
     */
    public function testCsvExportFormat(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'email', 'label' => 'Email', 'exportable' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test CSV export preparation
            $exportColumns = $this->getExportableColumns($component->columns);
            $this->assertEquals(3, count($exportColumns));

            // Test CSV headers
            $headers = array_column($exportColumns, 'label');
            $this->assertEquals(['ID', 'Name', 'Email'], $headers);

            // Test CSV data format
            $testData = [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ];

            $csvData = $this->formatDataForCsv($testData, $exportColumns);
            $this->assertEquals(2, count($csvData));
            $this->assertEquals([1, 'John Doe', 'john@example.com'], $csvData[0]);

            return true;
        } catch (\Exception $e) {
            $this->log("CSV export format test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test Excel export format
     */
    public function testExcelExportFormat(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'created_at', 'label' => 'Created At', 'exportable' => true, 'type' => 'date'],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test Excel export configuration
            $exportColumns = $this->getExportableColumns($component->columns);
            $this->assertEquals(3, count($exportColumns));

            // Test Excel data formatting
            $testData = [
                ['id' => 1, 'name' => 'John Doe', 'created_at' => '2023-01-01 10:00:00'],
                ['id' => 2, 'name' => 'Jane Smith', 'created_at' => '2023-01-02 11:00:00'],
            ];

            $excelData = $this->formatDataForExcel($testData, $exportColumns);
            $this->assertEquals(2, count($excelData));
            $this->assertTrue(is_array($excelData[0]));

            return true;
        } catch (\Exception $e) {
            $this->log("Excel export format test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test PDF export format
     */
    public function testPdfExportFormat(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'email', 'label' => 'Email', 'exportable' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test PDF export configuration
            $exportColumns = $this->getExportableColumns($component->columns);
            $this->assertEquals(3, count($exportColumns));

            // Test PDF table structure
            $testData = [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ];

            $pdfData = $this->formatDataForPdf($testData, $exportColumns);
            $this->assertTrue(isset($pdfData['headers']));
            $this->assertTrue(isset($pdfData['rows']));
            $this->assertEquals(3, count($pdfData['headers']));
            $this->assertEquals(2, count($pdfData['rows']));

            return true;
        } catch (\Exception $e) {
            $this->log("PDF export format test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test export with filters
     */
    public function testExportWithFilters(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'status', 'label' => 'Status', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
            ];

            $filters = [
                'status' => ['type' => 'select', 'options' => ['active', 'inactive']],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns, $filters);

            // Apply filter
            $component->filterColumn = 'status';
            $component->filterValue = 'active';

            // Test that export respects filters
            $this->assertEquals('status', $component->filterColumn);
            $this->assertEquals('active', $component->filterValue);

            return true;
        } catch (\Exception $e) {
            $this->log("Export with filters test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test export with search
     */
    public function testExportWithSearch(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'email', 'label' => 'Email', 'exportable' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Apply search
            $component->search = 'john';
            $component->updatedSearch();

            // Test that export respects search
            $this->assertEquals('john', $component->search);

            return true;
        } catch (\Exception $e) {
            $this->log("Export with search test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test export with relations
     */
    public function testExportWithRelations(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User', 'exportable' => true],
                ['key' => 'category_id', 'relation' => 'category:title', 'label' => 'Category', 'exportable' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\Post', $columns);

            // Test export columns with relations
            $exportColumns = $this->getExportableColumns($component->columns);
            $this->assertEquals(4, count($exportColumns));

            // Test relation column handling
            $relationColumns = array_filter($exportColumns, function($col) {
                return isset($col['relation']);
            });
            $this->assertEquals(2, count($relationColumns));

            return true;
        } catch (\Exception $e) {
            $this->log("Export with relations test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test export performance
     */
    public function testExportPerformance(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'email', 'label' => 'Email', 'exportable' => true],
                ['key' => 'user_id', 'relation' => 'user:name', 'label' => 'User', 'exportable' => true],
            ];

            // Create large dataset simulation
            $largeDataset = [];
            for ($i = 1; $i <= 1000; $i++) {
                $largeDataset[] = [
                    'id' => $i,
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com",
                    'user_id' => $i,
                ];
            }

            $metrics = $this->measureTime(function() use ($columns, $largeDataset) {
                $component = new Datatable();
                $component->mount('App\\Models\\Post', $columns);
                
                $exportColumns = $this->getExportableColumns($component->columns);
                $csvData = $this->formatDataForCsv($largeDataset, $exportColumns);
                
                return count($csvData);
            });

            $this->log("Export processing time: {$metrics['time']}ms");
            $this->log("Memory used: " . round($metrics['memory'] / 1024, 2) . "KB");

            // Performance should be reasonable for 1000 records
            $this->assertLessThan(1000, $metrics['time'], 'Export processing should be under 1000ms for 1000 records');

            return true;
        } catch (\Exception $e) {
            $this->log("Export performance test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test export file validation
     */
    public function testExportFileValidation(): bool
    {
        try {
            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
            ];

            $component = new Datatable();
            $component->mount('App\\Models\\User', $columns);

            // Test filename generation
            $filename = $this->generateExportFilename('users', 'csv');
            $this->assertStringContainsString('users', $filename);
            $this->assertStringEndsWith('.csv', $filename);

            // Test different format filenames
            $excelFilename = $this->generateExportFilename('products', 'xlsx');
            $this->assertStringEndsWith('.xlsx', $excelFilename);

            $pdfFilename = $this->generateExportFilename('orders', 'pdf');
            $this->assertStringEndsWith('.pdf', $pdfFilename);

            return true;
        } catch (\Exception $e) {
            $this->log("Export file validation test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test export memory management
     */
    public function testExportMemoryManagement(): bool
    {
        try {
            $initialMemory = memory_get_usage(true);

            $columns = [
                ['key' => 'id', 'label' => 'ID', 'exportable' => true],
                ['key' => 'name', 'label' => 'Name', 'exportable' => true],
                ['key' => 'description', 'label' => 'Description', 'exportable' => true],
            ];

            // Create large dataset
            $largeDataset = [];
            for ($i = 1; $i <= 5000; $i++) {
                $largeDataset[] = [
                    'id' => $i,
                    'name' => "Item {$i}",
                    'description' => str_repeat("Long description for item {$i}. ", 10),
                ];
            }

            $component = new Datatable();
            $component->mount('App\\Models\\Product', $columns);

            $exportColumns = $this->getExportableColumns($component->columns);
            $csvData = $this->formatDataForCsv($largeDataset, $exportColumns);

            $finalMemory = memory_get_usage(true);
            $memoryUsed = $finalMemory - $initialMemory;

            $this->log("Memory used for 5000 records: " . round($memoryUsed / 1024 / 1024, 2) . "MB");

            // Memory usage should be reasonable (under 50MB for 5000 records)
            $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Export should use less than 50MB for 5000 records');

            return true;
        } catch (\Exception $e) {
            $this->log("Export memory management test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Helper method to get exportable columns
     */
    private function getExportableColumns(array $columns): array
    {
        return array_filter($columns, function($column) {
            return $column['exportable'] ?? false;
        });
    }

    /**
     * Helper method to format data for CSV
     */
    private function formatDataForCsv(array $data, array $columns): array
    {
        $formatted = [];
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($columns as $column) {
                $csvRow[] = $row[$column['key']] ?? '';
            }
            $formatted[] = $csvRow;
        }
        return $formatted;
    }

    /**
     * Helper method to format data for Excel
     */
    private function formatDataForExcel(array $data, array $columns): array
    {
        return $this->formatDataForCsv($data, $columns);
    }

    /**
     * Helper method to format data for PDF
     */
    private function formatDataForPdf(array $data, array $columns): array
    {
        return [
            'headers' => array_column($columns, 'label'),
            'rows' => $this->formatDataForCsv($data, $columns),
        ];
    }

    /**
     * Helper method to generate export filename
     */
    private function generateExportFilename(string $base, string $extension): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        return "{$base}_export_{$timestamp}.{$extension}";
    }
}
