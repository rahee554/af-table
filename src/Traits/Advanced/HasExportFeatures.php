<?php

namespace ArtflowStudio\Table\Traits\Advanced;

/**
 * Trait HasExportFeatures
 * 
 * Handles all export-related functionality including format-specific exports,
 * chunking for large datasets, and export method delegation
 * Consolidates export logic moved from main DatatableTrait
 */
trait HasExportFeatures
{
    /**
     * Export data in specified format
     * Moved from DatatableTrait.php line 1324
     */
    public function export($format, $filename = null)
    {
        // Use the chunked export method that is implemented in the DatatableTrait
        return $this->exportWithChunks($format, 1000);
    }

    /**
     * Export to CSV format
     * Moved from DatatableTrait.php line 1334
     */
    public function exportToCsv($filename = null)
    {
        return $this->export('csv', $filename);
    }

    /**
     * Export to JSON format
     * Moved from DatatableTrait.php line 1342
     */
    public function exportToJson($filename = null)
    {
        return $this->export('json', $filename);
    }

    /**
     * Export to Excel format
     * Moved from DatatableTrait.php line 1350
     */
    public function exportToExcel($filename = null)
    {
        return $this->export('xlsx', $filename);
    }

    /**
     * Export PDF with chunking for large datasets
     * Moved from DatatableTrait.php line 1358
     */
    public function exportPdfChunked()
    {
        // Use the new consolidated export functionality
        return $this->export('pdf');
    }

    /**
     * Get data for export
     * Moved from DatatableTrait.php line 1367
     */
    public function getDataForExport()
    {
        // Use chunked processing to avoid memory issues
        return $this->buildUnifiedQuery()->lazy(1000); // Use lazy collection for memory efficiency
    }

    /**
     * Export with chunking
     * Moved from DatatableTrait.php line 2425
     */
    public function exportWithChunking(string $format = 'csv', int $chunkSize = 1000): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exportInChunks($format, $chunkSize);
    }

    /**
     * Abstract methods that must be implemented in the main class or other traits
     */
    abstract protected function buildUnifiedQuery(): \Illuminate\Database\Eloquent\Builder;
    abstract protected function exportInChunks(string $format, int $chunkSize): \Symfony\Component\HttpFoundation\StreamedResponse;
}
