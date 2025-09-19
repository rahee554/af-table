<?php

namespace ArtflowStudio\Table\Traits\Export;

use Illuminate\Http\Response;

trait HasExportOperations
{
    /**
     * Handle export
     */
    public function handleExport($format = 'csv', $filename = null)
    {
        try {
            $stats = $this->getExportStats();

            if ($stats['total_records'] > 10000) {
                return $this->exportWithChunking($format, $filename);
            } else {
                return $this->export($format);
            }
        } catch (\Exception $e) {
            $this->triggerErrorEvent($e, ['method' => 'handleExport', 'format' => $format]);
            session()->flash('error', 'Export failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Export data in specified format
     */
    public function export($format)
    {
        // Use the new consolidated export functionality from HasAdvancedExport trait
        // The parent export method already handles all formats including PDF
        return parent::export($format);
    }

    /**
     * Export to CSV format
     */
    public function exportToCsv()
    {
        return $this->export('csv');
    }

    /**
     * Export to JSON format
     */
    public function exportToJson()
    {
        return $this->export('json');
    }

    /**
     * Export to Excel format
     */
    public function exportToExcel()
    {
        return $this->export('xlsx');
    }

    /**
     * Export PDF with chunking for large datasets
     */
    public function exportPdfChunked()
    {
        // Use the new consolidated export functionality
        return $this->export('pdf');
    }

    /**
     * Get data for export
     */
    public function getDataForExport()
    {
        // Use chunked processing to avoid memory issues
        return $this->buildUnifiedQuery()->lazy(1000); // Use lazy collection for memory efficiency
    }

    /**
     * Export with chunking for large datasets
     */
    public function exportWithChunking(string $format = 'csv', int $chunkSize = 1000): Response
    {
        return $this->exportInChunks($format, $chunkSize);
    }
}
