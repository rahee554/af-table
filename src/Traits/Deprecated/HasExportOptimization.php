<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HasExportOptimization
{
    /**
     * Export configuration
     */
    protected $exportConfig = [
        'chunk_size' => 1000,
        'max_memory_mb' => 128,
        'timeout_seconds' => 300,
        'formats' => ['csv', 'xlsx', 'pdf'],
        'pdf_orientation' => 'landscape',
        'pdf_page_size' => 'A4',
        'include_headers' => true,
        'date_format' => 'Y-m-d H:i:s',
    ];

    /**
     * Export data in optimized chunks for large datasets
     */
    public function exportPdfChunked(array $columns = [], array $options = []): Response
    {
        $options = array_merge($this->exportConfig, $options);
        $columns = $columns ?: $this->getExportColumns();

        // Prepare the data in chunks
        $query = $this->getOptimizedExportQuery($columns);
        $totalRecords = $query->count();

        if ($totalRecords === 0) {
            return $this->createEmptyExportResponse('pdf');
        }

        // For small datasets, export directly
        if ($totalRecords <= $options['chunk_size']) {
            return $this->exportPdfDirect($query->get(), $columns, $options);
        }

        // For large datasets, use chunked processing
        return $this->exportPdfChunkedLarge($query, $columns, $options, $totalRecords);
    }

    /**
     * Export CSV in optimized chunks
     */
    public function exportCsvChunked(array $columns = [], array $options = []): StreamedResponse
    {
        $options = array_merge($this->exportConfig, $options);
        $columns = $columns ?: $this->getExportColumns();

        $query = $this->getOptimizedExportQuery($columns);

        $filename = $this->generateExportFilename('csv');

        return response()->streamDownload(function () use ($query, $columns, $options) {
            $output = fopen('php://output', 'w');

            // Write headers
            if ($options['include_headers']) {
                fputcsv($output, $this->getExportHeaders($columns));
            }

            // Stream data in chunks
            $query->chunk($options['chunk_size'], function ($records) use ($output, $columns, $options) {
                foreach ($records as $record) {
                    $row = $this->formatExportRow($record, $columns, $options);
                    fputcsv($output, $row);
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ]);
    }

    /**
     * Export Excel with memory optimization (requires Excel package)
     */
    public function exportExcelOptimized(array $columns = [], string $format = 'xlsx', array $options = []): StreamedResponse
    {
        $options = array_merge($this->exportConfig, $options);
        $columns = $columns ?: $this->getExportColumns();

        // Check if Excel package is available
        if (! class_exists('\Maatwebsite\Excel\Facades\Excel')) {
            throw new \Exception('Excel package not installed. Use CSV export instead.');
        }

        $filename = $this->generateExportFilename($format);
        $query = $this->getOptimizedExportQuery($columns);
        $data = $query->get();

        // For now, return a simple Excel-like response
        // In real implementation, you would use the Excel package
        return response()->streamDownload(function () use ($data, $columns, $options) {
            $output = fopen('php://output', 'w');

            // Write headers
            if ($options['include_headers']) {
                fputcsv($output, $this->getExportHeaders($columns));
            }

            // Write data
            foreach ($data as $record) {
                $row = $this->formatExportRow($record, $columns, $options);
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Get optimized query for export
     */
    protected function getOptimizedExportQuery(array $columns): Builder
    {
        $query = $this->getQuery();

        // Apply filters but remove pagination
        $query = $this->applyFiltersToQuery($query);
        $query = $this->applySearchToQuery($query);

        // Optimize select columns for export
        $selectColumns = $this->getOptimizedExportSelectColumns($columns);

        if (! empty($selectColumns)) {
            $query->select($selectColumns);
        }

        // Add necessary eager loading for relations
        $relations = $this->getExportRelations($columns);
        if (! empty($relations)) {
            $query->with($relations);
        }

        // Order by primary key for consistent chunking
        $query->orderBy($this->getModel()->getKeyName());

        return $query;
    }

    /**
     * Get optimized select columns for export
     */
    protected function getOptimizedExportSelectColumns(array $columns): array
    {
        $selectColumns = [];
        $tableName = $this->getModel()->getTable();

        foreach ($columns as $column) {
            if (! str_contains($column, '.')) {
                $selectColumns[] = "{$tableName}.{$column}";
            }
        }

        // Always include primary key for chunking
        $primaryKey = $this->getModel()->getKeyName();
        if (! in_array("{$tableName}.{$primaryKey}", $selectColumns)) {
            $selectColumns[] = "{$tableName}.{$primaryKey}";
        }

        return $selectColumns;
    }

    /**
     * Get relations needed for export
     */
    protected function getExportRelations(array $columns): array
    {
        $relations = [];

        foreach ($columns as $column) {
            if (str_contains($column, '.')) {
                $parts = explode('.', $column);
                $relation = $parts[0];

                if (! in_array($relation, $relations)) {
                    $relations[] = $relation;
                }
            }
        }

        return $relations;
    }

    /**
     * Export PDF for large datasets using chunked processing
     */
    protected function exportPdfChunkedLarge(Builder $query, array $columns, array $options, int $totalRecords): Response
    {
        $tempFiles = [];
        $chunkCount = 0;
        $maxRecordsPerFile = $options['chunk_size'] * 10; // 10 chunks per temp file

        // Process data in chunks and create temporary files
        $query->chunk($options['chunk_size'], function ($records) use (&$tempFiles, &$chunkCount, $columns, $options) {
            if ($chunkCount % 10 === 0) {
                // Start a new temp file
                $tempFile = tempnam(sys_get_temp_dir(), 'datatable_export_');
                $tempFiles[] = $tempFile;
                $tempHandle = fopen($tempFile, 'w');

                // Write headers for first chunk
                if (count($tempFiles) === 1 && $options['include_headers']) {
                    fputcsv($tempHandle, $this->getExportHeaders($columns));
                }
            } else {
                $tempHandle = fopen(end($tempFiles), 'a');
            }

            foreach ($records as $record) {
                $row = $this->formatExportRow($record, $columns, $options);
                fputcsv($tempHandle, $row);
            }

            fclose($tempHandle);
            $chunkCount++;
        });

        // Merge temp files and create PDF
        return $this->createPdfFromTempFiles($tempFiles, $columns, $options);
    }

    /**
     * Export PDF directly for small datasets
     */
    protected function exportPdfDirect(Collection $data, array $columns, array $options): Response
    {
        $filename = $this->generateExportFilename('pdf');

        // Check if PDF package is available
        if (! class_exists('\PDF')) {
            // Fallback to CSV if PDF not available
            return response()->streamDownload(function () use ($data, $columns, $options) {
                $output = fopen('php://output', 'w');

                if ($options['include_headers']) {
                    fputcsv($output, $this->getExportHeaders($columns));
                }

                foreach ($data as $record) {
                    $row = $this->formatExportRow($record, $columns, $options);
                    fputcsv($output, $row);
                }

                fclose($output);
            }, str_replace('.pdf', '.csv', $filename), ['Content-Type' => 'text/csv']);
        }

        $pdf = \PDF::loadView('datatable.export.pdf', [
            'data' => $data,
            'columns' => $columns,
            'headers' => $this->getExportHeaders($columns),
            'title' => $this->getExportTitle(),
            'options' => $options,
        ]);

        $pdf->setPaper($options['pdf_page_size'], $options['pdf_orientation']);

        return $pdf->download($filename);
    }

    /**
     * Create PDF from temporary files
     */
    protected function createPdfFromTempFiles(array $tempFiles, array $columns, array $options): Response
    {
        $filename = $this->generateExportFilename('pdf');

        // For very large datasets, create a simplified PDF or redirect to CSV
        if (count($tempFiles) > 5) {
            return $this->createLargeDatasetFallback($tempFiles, $columns, $options);
        }

        // Merge temp files into a single collection
        $allData = collect();

        foreach ($tempFiles as $tempFile) {
            $handle = fopen($tempFile, 'r');

            while (($row = fgetcsv($handle)) !== false) {
                $allData->push($row);
            }

            fclose($handle);
            unlink($tempFile); // Clean up temp file
        }

        // Create PDF from merged data
        $pdf = \PDF::loadView('datatable.export.pdf-large', [
            'data' => $allData->skip(1), // Skip header row
            'headers' => $this->getExportHeaders($columns),
            'title' => $this->getExportTitle(),
            'options' => $options,
        ]);

        $pdf->setPaper($options['pdf_page_size'], $options['pdf_orientation']);

        return $pdf->download($filename);
    }

    /**
     * Create fallback for very large datasets
     */
    protected function createLargeDatasetFallback(array $tempFiles, array $columns, array $options): StreamedResponse
    {
        // Clean up temp files
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        // Redirect to CSV export for very large datasets
        return $this->exportCsvChunked($columns, $options);
    }

    /**
     * Get export headers for columns
     */
    protected function getExportHeaders(array $columns): array
    {
        $headers = [];

        foreach ($columns as $column) {
            $headers[] = $this->getExportColumnLabel($column);
        }

        return $headers;
    }

    /**
     * Get human-readable label for export column
     */
    protected function getExportColumnLabel(string $column): string
    {
        // Check if custom labels are defined
        if (isset($this->columnLabels[$column])) {
            return $this->columnLabels[$column];
        }

        // Generate label from column name
        if (str_contains($column, '.')) {
            $parts = explode('.', $column);
            $relation = ucfirst($parts[0]);
            $field = ucwords(str_replace('_', ' ', $parts[1]));

            return "{$relation} {$field}";
        }

        return ucwords(str_replace('_', ' ', $column));
    }

    /**
     * Format export row data
     */
    protected function formatExportRow($record, array $columns, array $options): array
    {
        $row = [];

        foreach ($columns as $column) {
            $value = $this->getExportValue($record, $column, $options);
            $row[] = $this->formatExportValue($value, $column, $options);
        }

        return $row;
    }

    /**
     * Get value for export from record
     */
    protected function getExportValue($record, string $column, array $options)
    {
        if (str_contains($column, '.')) {
            return $this->getRelationValue($record, $column);
        }

        return $record->{$column} ?? '';
    }

    /**
     * Get value from relation
     */
    protected function getRelationValue($record, string $column)
    {
        $parts = explode('.', $column);
        $value = $record;

        foreach ($parts as $part) {
            if (is_null($value)) {
                return '';
            }

            if (is_object($value) && isset($value->{$part})) {
                $value = $value->{$part};
            } else {
                return '';
            }
        }

        return $value ?? '';
    }

    /**
     * Format value for export
     */
    protected function formatExportValue($value, string $column, array $options): string
    {
        if (is_null($value)) {
            return '';
        }

        // Format dates
        if ($this->isDateColumn($column) && $this->isValidDate($value)) {
            return \Carbon\Carbon::parse($value)->format($options['date_format']);
        }

        // Format booleans
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        // Format numbers
        if (is_numeric($value) && $this->isMoneyColumn($column)) {
            return number_format($value, 2);
        }

        return (string) $value;
    }

    /**
     * Check if column contains monetary values
     */
    protected function isMoneyColumn(string $column): bool
    {
        $moneyPatterns = ['price', 'cost', 'amount', 'fee', 'total', 'subtotal'];

        foreach ($moneyPatterns as $pattern) {
            if (str_contains(strtolower($column), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate export filename
     */
    protected function generateExportFilename(string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $tableName = $this->getTableName() ?: 'datatable';

        return "{$tableName}_export_{$timestamp}.{$format}";
    }

    /**
     * Get table name for export
     */
    protected function getTableName(): string
    {
        if (isset($this->exportTitle)) {
            return $this->exportTitle;
        }

        if (isset($this->tableId)) {
            return $this->tableId;
        }

        return class_basename($this->model);
    }

    /**
     * Get export title
     */
    protected function getExportTitle(): string
    {
        return $this->getTableName().' Export';
    }

    /**
     * Create empty export response
     */
    protected function createEmptyExportResponse(string $format): Response
    {
        $filename = $this->generateExportFilename($format);

        if ($format === 'csv') {
            return response()->streamDownload(function () {
                echo "No data available for export\n";
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return response('No data available for export', 404);
    }

    /**
     * Get memory usage in MB
     */
    protected function getMemoryUsageMB(): float
    {
        return memory_get_usage(true) / 1024 / 1024;
    }

    /**
     * Check if memory limit is approaching
     */
    protected function isMemoryLimitApproaching(): bool
    {
        return $this->getMemoryUsageMB() > $this->exportConfig['max_memory_mb'] * 0.8;
    }

    /**
     * Configure export settings
     */
    public function configureExport(array $config): void
    {
        $this->exportConfig = array_merge($this->exportConfig, $config);
    }

    /**
     * Get export configuration
     */
    public function getExportConfig(): array
    {
        return $this->exportConfig;
    }

    /**
     * Estimate export size
     */
    public function estimateExportSize(array $columns = []): array
    {
        $columns = $columns ?: $this->getExportColumns();
        $query = $this->getOptimizedExportQuery($columns);
        $recordCount = $query->count();

        // Estimate based on averages
        $avgRowSize = count($columns) * 20; // Average 20 characters per column
        $estimatedBytes = $recordCount * $avgRowSize;

        return [
            'record_count' => $recordCount,
            'estimated_size_mb' => round($estimatedBytes / 1024 / 1024, 2),
            'recommended_format' => $estimatedBytes > 10 * 1024 * 1024 ? 'csv' : 'xlsx', // > 10MB recommend CSV
            'chunking_required' => $recordCount > $this->exportConfig['chunk_size'],
        ];
    }

    /**
     * Get available export formats
     */
    public function getAvailableExportFormats(): array
    {
        return $this->exportConfig['formats'];
    }
}
