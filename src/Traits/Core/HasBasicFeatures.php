<?php

namespace ArtflowStudio\Table\Traits\Core;

trait HasBasicFeatures
{
    /**
     * Export statistics tracking
     */
    protected array $exportStats = [
        'total_exports' => 0,
        'last_export_time' => null,
        'last_export_format' => null,
        'export_errors' => 0,
    ];

    /**
     * ForEach mode properties
     */
    protected bool $foreachMode = false;
    protected array $foreachData = [];
    protected array $foreachStats = [
        'items_processed' => 0,
        'batch_size' => 100,
        'total_batches' => 0,
        'errors' => 0,
    ];

    // =================== EXPORT FEATURES ===================

    /**
     * Get export statistics
     */
    public function getExportStats(): array
    {
        return [
            'total_exports' => $this->exportStats['total_exports'],
            'last_export_time' => $this->exportStats['last_export_time'],
            'last_export_format' => $this->exportStats['last_export_format'],
            'export_errors' => $this->exportStats['export_errors'],
            'available_formats' => ['csv', 'json', 'excel'],
        ];
    }

    /**
     * Update export statistics
     */
    protected function updateExportStats(string $format, bool $success = true): void
    {
        $this->exportStats['total_exports']++;
        $this->exportStats['last_export_time'] = now()->timestamp;
        $this->exportStats['last_export_format'] = $format;
        
        if (!$success) {
            $this->exportStats['export_errors']++;
        }
    }

    // =================== FOREACH FUNCTIONALITY ===================

    /**
     * Set data for foreach processing
     */
    public function setForEachData(array $data): void
    {
        $this->foreachData = $data;
        $this->foreachStats['items_processed'] = 0;
        $this->foreachStats['total_batches'] = ceil(count($data) / $this->foreachStats['batch_size']);
    }

    /**
     * Enable foreach mode
     */
    public function enableForeachMode(): void
    {
        $this->foreachMode = true;
    }

    /**
     * Disable foreach mode
     */
    public function disableForeachMode(): void
    {
        $this->foreachMode = false;
        $this->foreachData = [];
    }

    /**
     * Get foreach data
     */
    public function getForeachData(): array
    {
        return $this->foreachData;
    }

    /**
     * Process a single foreach item
     */
    public function processForeachItem($item, int $index): array
    {
        try {
            $processed = [
                'index' => $index,
                'data' => $item,
                'processed_at' => now()->timestamp,
                'status' => 'success',
            ];
            
            $this->foreachStats['items_processed']++;
            return $processed;
            
        } catch (\Exception $e) {
            $this->foreachStats['errors']++;
            return [
                'index' => $index,
                'data' => $item,
                'error' => $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * Check if in foreach mode
     */
    public function isForeachMode(): bool
    {
        return $this->foreachMode;
    }

    /**
     * Configure foreach processing
     */
    public function configureForeEach(array $config): void
    {
        if (isset($config['batch_size'])) {
            $this->foreachStats['batch_size'] = max(1, (int) $config['batch_size']);
        }
    }

    /**
     * Get foreach statistics
     */
    public function getForeachStats(): array
    {
        return [
            'mode_enabled' => $this->foreachMode,
            'total_items' => count($this->foreachData),
            'items_processed' => $this->foreachStats['items_processed'],
            'batch_size' => $this->foreachStats['batch_size'],
            'total_batches' => $this->foreachStats['total_batches'],
            'errors' => $this->foreachStats['errors'],
            'completion_percentage' => $this->calculateCompletionPercentage(),
        ];
    }

    /**
     * Export foreach data
     */
    public function exportForeachData(string $format = 'json'): array
    {
        if (!$this->foreachMode || empty($this->foreachData)) {
            return [
                'success' => false,
                'message' => 'No foreach data available for export',
                'data' => [],
            ];
        }

        try {
            $processedData = [];
            foreach ($this->foreachData as $index => $item) {
                $processedData[] = $this->processForeachItem($item, $index);
            }

            $this->updateExportStats($format, true);

            return [
                'success' => true,
                'format' => $format,
                'data' => $processedData,
                'stats' => $this->getForeachStats(),
                'exported_at' => now()->timestamp,
            ];

        } catch (\Exception $e) {
            $this->updateExportStats($format, false);
            return [
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Batch process foreach items
     */
    public function batchProcessForeachItems(int $batchSize = null): array
    {
        $batchSize = $batchSize ?? $this->foreachStats['batch_size'];
        $results = [];
        $batches = array_chunk($this->foreachData, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            $batchResults = [];
            
            foreach ($batch as $itemIndex => $item) {
                $globalIndex = ($batchIndex * $batchSize) + $itemIndex;
                $batchResults[] = $this->processForeachItem($item, $globalIndex);
            }
            
            $results[] = [
                'batch_index' => $batchIndex,
                'batch_size' => count($batch),
                'results' => $batchResults,
            ];
        }

        return [
            'total_batches' => count($batches),
            'batches' => $results,
            'stats' => $this->getForeachStats(),
        ];
    }

    /**
     * Calculate completion percentage for foreach processing
     */
    protected function calculateCompletionPercentage(): float
    {
        $total = count($this->foreachData);
        if ($total === 0) {
            return 100.0;
        }
        
        return ($this->foreachStats['items_processed'] / $total) * 100;
    }

    // =================== COLUMN OPTIMIZATION ===================

    /**
     * Apply column optimization to query
     */
    public function applyColumnOptimization($query)
    {
        // Select only necessary columns to reduce memory usage
        $optimizedColumns = $this->getOptimizedColumns();
        
        if (!empty($optimizedColumns)) {
            $query = $query->select($optimizedColumns);
        }
        
        return $query;
    }

    /**
     * Get optimized column list
     */
    protected function getOptimizedColumns(): array
    {
        $columns = [];
        
        // Always include primary key
        if (is_string($this->model)) {
            $modelInstance = new $this->model;
            $columns[] = $modelInstance->getTable() . '.' . $modelInstance->getKeyName();
        }
        
        // Add visible columns
        foreach ($this->visibleColumns ?? [] as $columnKey => $visible) {
            if ($visible && isset($this->columns[$columnKey]['key'])) {
                $columns[] = $this->columns[$columnKey]['key'];
            }
        }
        
        return array_unique($columns);
    }
}
