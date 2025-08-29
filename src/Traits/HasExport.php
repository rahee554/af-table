<?php

namespace ArtflowStudio\Table\Traits;

trait HasExport
{
    /**
     * Export data to CSV
     */
    public function exportToCsv($filename = null)
    {
        $filename = $filename ?? 'datatable_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Get all records for export
        $query = $this->getExportQuery();
        $records = $query->get();
        
        // Prepare headers
        $headers = [];
        $exportableColumns = $this->getExportableColumns();
        
        foreach ($exportableColumns as $columnKey) {
            $headers[] = $this->getColumnLabel($columnKey);
        }
        
        // Create CSV content
        $csvContent = $this->generateCsvContent($records, $exportableColumns, $headers);
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Export data to Excel
     */
    public function exportToExcel($filename = null)
    {
        $filename = $filename ?? 'datatable_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // For Excel export, you would typically use Laravel Excel package
        // This is a placeholder implementation
        return $this->exportToCsv($filename);
    }

    /**
     * Export data to JSON
     */
    public function exportToJson($filename = null)
    {
        $filename = $filename ?? 'datatable_export_' . date('Y-m-d_H-i-s') . '.json';
        
        $query = $this->getExportQuery();
        $records = $query->get();
        
        $exportData = [];
        $exportableColumns = $this->getExportableColumns();
        
        foreach ($records as $record) {
            $row = [];
            foreach ($exportableColumns as $columnKey) {
                $row[$this->getColumnLabel($columnKey)] = $this->getColumnValueForExport($record, $columnKey);
            }
            $exportData[] = $row;
        }
        
        $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);
        
        return response($jsonContent)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get query for export (applies current filters and search)
     */
    protected function getExportQuery()
    {
        $query = $this->getQuery();
        
        // Remove pagination for export
        $query->limit(null)->offset(null);
        
        return $query;
    }

    /**
     * Get exportable columns
     */
    protected function getExportableColumns(): array
    {
        $exportableColumns = [];
        
        foreach ($this->columns as $columnKey => $column) {
            // Check if column is exportable
            if ($this->isColumnExportable($column)) {
                $exportableColumns[] = $columnKey;
            }
        }
        
        return $exportableColumns;
    }

    /**
     * Check if column is exportable
     */
    protected function isColumnExportable($column): bool
    {
        // Default to true unless explicitly set to false
        return !isset($column['exportable']) || $column['exportable'] !== false;
    }

    /**
     * Get column label for export
     */
    protected function getColumnLabel($columnKey): string
    {
        if (isset($this->columns[$columnKey]['label'])) {
            return $this->columns[$columnKey]['label'];
        }
        
        // Convert column key to readable label
        return ucwords(str_replace(['_', '-'], ' ', $columnKey));
    }

    /**
     * Get column value for export
     */
    protected function getColumnValueForExport($record, $columnKey)
    {
        $column = $this->columns[$columnKey];
        
        // Handle function columns
        if (isset($column['function'])) {
            return $this->callColumnFunction($record, $column['function']);
        }
        
        // Handle relation columns
        if (isset($column['relation'])) {
            return $this->getRelationValue($record, $column['relation']);
        }
        
        // Handle JSON columns
        if (isset($column['json'])) {
            return $this->extractJsonValue($record, $columnKey, $column['json']);
        }
        
        // Handle regular columns
        if (isset($column['key'])) {
            $value = $record->{$column['key']};
            
            // Format value for export
            return $this->formatValueForExport($value, $column);
        }
        
        return '';
    }

    /**
     * Format value for export
     */
    protected function formatValueForExport($value, $column)
    {
        // Handle null values
        if (is_null($value)) {
            return '';
        }
        
        // Handle dates
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d H:i:s');
        }
        
        // Handle arrays/objects
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        // Handle booleans
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        
        // Return as string
        return (string) $value;
    }

    /**
     * Generate CSV content
     */
    protected function generateCsvContent($records, $exportableColumns, $headers): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add headers
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($records as $record) {
            $row = [];
            foreach ($exportableColumns as $columnKey) {
                $row[] = $this->getColumnValueForExport($record, $columnKey);
            }
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }

    /**
     * Export with chunking for large datasets
     */
    public function exportWithChunking($format = 'csv', $filename = null, $chunkSize = 1000)
    {
        $filename = $filename ?? 'datatable_export_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        $exportableColumns = $this->getExportableColumns();
        $headers = [];
        
        foreach ($exportableColumns as $columnKey) {
            $headers[] = $this->getColumnLabel($columnKey);
        }
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'datatable_export_');
        $output = fopen($tempFile, 'w');
        
        if ($format === 'csv') {
            // Add BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
        }
        
        // Process data in chunks
        $query = $this->getExportQuery();
        
        $query->chunk($chunkSize, function ($records) use ($output, $exportableColumns, $format) {
            foreach ($records as $record) {
                $row = [];
                foreach ($exportableColumns as $columnKey) {
                    $row[] = $this->getColumnValueForExport($record, $columnKey);
                }
                
                if ($format === 'csv') {
                    fputcsv($output, $row);
                }
            }
        });
        
        fclose($output);
        
        // Return file download
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get export statistics
     */
    public function getExportStats(): array
    {
        $query = $this->getExportQuery();
        $totalRecords = $query->count();
        $exportableColumns = $this->getExportableColumns();
        
        // Estimate file sizes
        $estimatedCsvSize = $totalRecords * count($exportableColumns) * 20; // Rough estimate
        
        return [
            'total_records' => $totalRecords,
            'exportable_columns' => count($exportableColumns),
            'estimated_csv_size_bytes' => $estimatedCsvSize,
            'estimated_csv_size_readable' => $this->formatBytes($estimatedCsvSize),
            'columns' => $exportableColumns,
            'recommended_chunk_size' => $totalRecords > 10000 ? 1000 : 0
        ];
    }

    /**
     * Format bytes to readable format
     */
    protected function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Set column exportable status
     */
    public function setColumnExportable($columnKey, $exportable = true)
    {
        if (isset($this->columns[$columnKey])) {
            $this->columns[$columnKey]['exportable'] = $exportable;
        }
    }

    /**
     * Get export formats available
     */
    public function getAvailableExportFormats(): array
    {
        return [
            'csv' => [
                'name' => 'CSV',
                'extension' => 'csv',
                'mime_type' => 'text/csv',
                'description' => 'Comma Separated Values'
            ],
            'json' => [
                'name' => 'JSON',
                'extension' => 'json',
                'mime_type' => 'application/json',
                'description' => 'JavaScript Object Notation'
            ],
            'excel' => [
                'name' => 'Excel',
                'extension' => 'xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'description' => 'Microsoft Excel (requires Laravel Excel package)'
            ]
        ];
    }
}
