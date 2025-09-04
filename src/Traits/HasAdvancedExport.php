<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

trait HasAdvancedExport
{
    /**
     * Export configuration
     */
    protected $exportConfig = [
        'chunk_size' => 1000,
        'memory_limit' => '512M',
        'max_execution_time' => 300,
        'formats' => ['csv', 'xlsx', 'json', 'pdf'],
        'compression' => true,
        'include_headers' => true,
        'date_format' => 'Y-m-d H:i:s',
        'number_format' => '#,##0.00',
        'encoding' => 'UTF-8',
    ];

    /**
     * Export statistics
     */
    protected $exportStats = [
        'records_processed' => 0,
        'memory_used' => 0,
        'execution_time' => 0,
        'file_size' => 0,
    ];

    /**
     * Export data in specified format with optimizations
     */
    public function export(string $format = 'csv', array $options = [])
    {
        // Validate format
        if (!in_array($format, $this->exportConfig['formats'])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        // Set up export environment
        $this->setupExportEnvironment();
        $startTime = microtime(true);

        try {
            // Merge options with defaults
            $options = array_merge($this->exportConfig, $options);

            // Build optimized query for export
            $query = $this->buildOptimizedExportQuery();
            
            // Get total record count
            $totalRecords = $query->count();
            
            if ($totalRecords === 0) {
                throw new \Exception('No data to export');
            }

            // Choose export method based on data size
            if ($totalRecords > $options['chunk_size']) {
                $result = $this->exportLargeDataset($query, $format, $options);
            } else {
                $result = $this->exportSmallDataset($query, $format, $options);
            }

            // Track statistics
            $this->exportStats['execution_time'] = microtime(true) - $startTime;
            $this->exportStats['records_processed'] = $totalRecords;
            $this->exportStats['memory_used'] = memory_get_peak_usage(true);

            return $result;

        } catch (\Exception $e) {
            $this->restoreEnvironment();
            throw $e;
        }
    }

    /**
     * Setup optimized export environment
     */
    protected function setupExportEnvironment(): void
    {
        // Increase memory limit
        ini_set('memory_limit', $this->exportConfig['memory_limit']);
        
        // Increase execution time
        set_time_limit($this->exportConfig['max_execution_time']);
        
        // Disable query log to save memory
        DB::disableQueryLog();
    }

    /**
     * Restore environment after export
     */
    protected function restoreEnvironment(): void
    {
        // Re-enable query log
        DB::enableQueryLog();
        
        // Garbage collection
        gc_collect_cycles();
    }

    /**
     * Build optimized query for export
     */
    protected function buildOptimizedExportQuery(): Builder
    {
        $query = $this->buildQuery();
        
        // Remove pagination for export
        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;
        
        // Remove unnecessary eager loading for export
        $query->setEagerLoads([]);
        
        // Select only required columns for export
        $exportColumns = $this->getExportColumns();
        if (!empty($exportColumns)) {
            $query->select($exportColumns);
        }
        
        return $query;
    }

    /**
     * Get columns to include in export
     */
    protected function getExportColumns(): array
    {
        $columns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns unless explicitly included
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }
            
            // Skip columns marked as non-exportable
            if (isset($column['exportable']) && !$column['exportable']) {
                continue;
            }
            
            // Skip function-based columns for performance
            if (isset($column['function'])) {
                continue;
            }
            
            // Include column key
            if (isset($column['key'])) {
                $columns[] = $column['key'];
            }
        }
        
        return $columns;
    }

    /**
     * Export small datasets (under chunk size)
     */
    protected function exportSmallDataset(Builder $query, string $format, array $options)
    {
        $data = $query->get();
        $formattedData = $this->formatDataForExport($data);
        
        return $this->generateExportFile($formattedData, $format, $options);
    }

    /**
     * Export large datasets using chunking
     */
    protected function exportLargeDataset(Builder $query, string $format, array $options)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'datatable_export_');
        $handle = fopen($tempFile, 'w');
        
        if (!$handle) {
            throw new \Exception('Unable to create temporary export file');
        }

        $isFirstChunk = true;
        $headers = [];

        try {
            $query->chunk($options['chunk_size'], function ($chunk) use ($handle, $format, $options, &$isFirstChunk, &$headers) {
                $formattedChunk = $this->formatDataForExport($chunk);
                
                if ($isFirstChunk) {
                    $headers = $this->getExportHeaders();
                    
                    if ($format === 'csv') {
                        fputcsv($handle, $headers);
                    }
                    
                    $isFirstChunk = false;
                }
                
                foreach ($formattedChunk as $row) {
                    if ($format === 'csv') {
                        fputcsv($handle, array_values($row));
                    } else {
                        fwrite($handle, json_encode($row) . "\n");
                    }
                }
                
                // Free memory
                unset($formattedChunk, $chunk);
                gc_collect_cycles();
            });

            fclose($handle);
            
            return $this->processExportFile($tempFile, $format, $options);

        } catch (\Exception $e) {
            fclose($handle);
            unlink($tempFile);
            throw $e;
        }
    }

    /**
     * Format data for export
     */
    protected function formatDataForExport($data): array
    {
        $formatted = [];
        
        foreach ($data as $record) {
            $row = [];
            
            foreach ($this->columns as $columnKey => $column) {
                // Skip non-visible columns
                if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                    continue;
                }
                
                // Skip non-exportable columns
                if (isset($column['exportable']) && !$column['exportable']) {
                    continue;
                }
                
                $value = $this->getExportValue($record, $column, $columnKey);
                $row[$column['label'] ?? $columnKey] = $value;
            }
            
            $formatted[] = $row;
        }
        
        return $formatted;
    }

    /**
     * Get export value for a column
     */
    protected function getExportValue($record, array $column, string $columnKey)
    {
        if (isset($column['relation'])) {
            return $this->getRelationExportValue($record, $column);
        }
        
        if (isset($column['function'])) {
            return $this->getFunctionExportValue($record, $column);
        }
        
        $key = $column['key'] ?? $columnKey;
        $value = data_get($record, $key);
        
        // Format value based on type
        return $this->formatExportValue($value, $column);
    }

    /**
     * Get relation value for export
     */
    protected function getRelationExportValue($record, array $column)
    {
        $relation = $column['relation'];
        $relationColumn = $column['relation_column'] ?? 'name';
        
        $relationRecord = $record->{$relation} ?? null;
        
        if (!$relationRecord) {
            return '';
        }
        
        return data_get($relationRecord, $relationColumn, '');
    }

    /**
     * Get function value for export
     */
    protected function getFunctionExportValue($record, array $column)
    {
        $function = $column['function'];
        
        if (is_callable($function)) {
            return $function($record);
        }
        
        return '';
    }

    /**
     * Format value for export based on column type
     */
    protected function formatExportValue($value, array $column)
    {
        if ($value === null) {
            return '';
        }
        
        $type = $column['type'] ?? 'string';
        
        switch ($type) {
            case 'date':
            case 'datetime':
                if ($value instanceof \Carbon\Carbon) {
                    return $value->format($this->exportConfig['date_format']);
                }
                if (is_string($value)) {
                    try {
                        return \Carbon\Carbon::parse($value)->format($this->exportConfig['date_format']);
                    } catch (\Exception $e) {
                        return $value;
                    }
                }
                return $value;
                
            case 'number':
            case 'currency':
                if (is_numeric($value)) {
                    return number_format((float)$value, 2, '.', '');
                }
                return $value;
                
            case 'boolean':
                return $value ? 'Yes' : 'No';
                
            case 'array':
            case 'json':
                if (is_array($value) || is_object($value)) {
                    return json_encode($value);
                }
                return $value;
                
            default:
                return (string)$value;
        }
    }

    /**
     * Get export headers
     */
    protected function getExportHeaders(): array
    {
        $headers = [];
        
        foreach ($this->columns as $columnKey => $column) {
            // Skip non-visible columns
            if (isset($this->visibleColumns) && !($this->visibleColumns[$columnKey] ?? true)) {
                continue;
            }
            
            // Skip non-exportable columns
            if (isset($column['exportable']) && !$column['exportable']) {
                continue;
            }
            
            $headers[] = $column['label'] ?? $columnKey;
        }
        
        return $headers;
    }

    /**
     * Generate export file
     */
    protected function generateExportFile(array $data, string $format, array $options)
    {
        switch ($format) {
            case 'csv':
                return $this->generateCsvFile($data, $options);
                
            case 'xlsx':
                return $this->generateXlsxFile($data, $options);
                
            case 'json':
                return $this->generateJsonFile($data, $options);
                
            case 'pdf':
                return $this->generatePdfFile($data, $options);
                
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Generate CSV file
     */
    protected function generateCsvFile(array $data, array $options)
    {
        $filename = $this->generateExportFilename('csv');
        $headers = $this->getExportHeaders();
        
        $callback = function() use ($data, $headers, $options) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            if ($options['encoding'] === 'UTF-8') {
                fwrite($file, "\xEF\xBB\xBF");
            }
            
            // Write headers
            if ($options['include_headers']) {
                fputcsv($file, $headers);
            }
            
            // Write data
            foreach ($data as $row) {
                fputcsv($file, array_values($row));
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate XLSX file
     */
    protected function generateXlsxFile(array $data, array $options)
    {
        // XLSX export requires PhpSpreadsheet library
        // Install with: composer require phpoffice/phpspreadsheet
        throw new \Exception('XLSX export requires PhpSpreadsheet library. Install with: composer require phpoffice/phpspreadsheet');
    }

    /**
     * Generate JSON file
     */
    protected function generateJsonFile(array $data, array $options)
    {
        $filename = $this->generateExportFilename('json');
        
        $jsonData = [
            'meta' => [
                'exported_at' => now()->toISOString(),
                'total_records' => count($data),
                'table_id' => $this->tableId ?? 'unknown',
            ],
            'data' => $data,
        ];
        
        $callback = function() use ($jsonData) {
            echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        };
        
        return Response::stream($callback, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate PDF file (basic implementation)
     */
    protected function generatePdfFile(array $data, array $options)
    {
        // This would require a PDF library like TCPDF or DOMPDF
        throw new \Exception('PDF export not implemented. Please install a PDF library.');
    }

    /**
     * Style Excel headers (requires PhpSpreadsheet)
     */
    protected function styleExcelHeaders($sheet, int $columnCount): void
    {
        // This method requires PhpSpreadsheet library
        throw new \Exception('Excel styling requires PhpSpreadsheet library');
    }

    /**
     * Generate export filename
     */
    protected function generateExportFilename(string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $tableName = $this->tableId ?? 'datatable';
        
        return "{$tableName}_export_{$timestamp}.{$extension}";
    }

    /**
     * Process export file (for large datasets)
     */
    protected function processExportFile(string $tempFile, string $format, array $options)
    {
        $filename = $this->generateExportFilename($format);
        
        // Compress if enabled and file is large
        if ($options['compression'] && filesize($tempFile) > 1024 * 1024) { // > 1MB
            $compressedFile = $this->compressFile($tempFile, $format);
            unlink($tempFile);
            $tempFile = $compressedFile;
            $filename = str_replace(".{$format}", ".{$format}.gz", $filename);
        }
        
        $callback = function() use ($tempFile) {
            readfile($tempFile);
            unlink($tempFile);
        };
        
        $mimeType = $this->getMimeType($format, $options['compression']);
        
        return Response::stream($callback, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Compress file
     */
    protected function compressFile(string $file, string $format): string
    {
        $compressedFile = $file . '.gz';
        
        $input = fopen($file, 'rb');
        $output = gzopen($compressedFile, 'wb9');
        
        while (!feof($input)) {
            gzwrite($output, fread($input, 8192));
        }
        
        fclose($input);
        gzclose($output);
        
        return $compressedFile;
    }

    /**
     * Get MIME type for format
     */
    protected function getMimeType(string $format, bool $compressed = false): string
    {
        $mimeTypes = [
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'json' => 'application/json',
            'pdf' => 'application/pdf',
        ];
        
        $mimeType = $mimeTypes[$format] ?? 'application/octet-stream';
        
        if ($compressed) {
            $mimeType = 'application/gzip';
        }
        
        return $mimeType;
    }

    /**
     * Get export statistics
     */
    public function getExportStats(): array
    {
        return $this->exportStats;
    }

    /**
     * Set export configuration
     */
    public function setExportConfig(array $config): void
    {
        $this->exportConfig = array_merge($this->exportConfig, $config);
    }

    /**
     * Get available export formats
     */
    public function getAvailableExportFormats(): array
    {
        return $this->exportConfig['formats'];
    }
}
