<?php

namespace ArtflowStudio\Table\Traits;

use Illuminate\Support\Facades\Log;

trait HasMemoryManagement
{
    /**
     * Memory usage threshold in bytes (default: 64MB - optimized for consolidated traits)
     */
    protected $memoryThreshold = 67108864;

    /**
     * Maximum records to process in a single batch (optimized)
     */
    protected $maxBatchSize = 500;

    /**
     * Check current memory usage
     */
    protected function getCurrentMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getMemoryLimit(),
            'percentage' => $this->getMemoryUsagePercentage()
        ];
    }

    /**
     * Get memory limit in bytes
     */
    protected function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === -1) {
            return PHP_INT_MAX; // No limit
        }

        return $this->convertToBytes($memoryLimit);
    }

    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * Get current memory usage percentage
     */
    protected function getMemoryUsagePercentage(): float
    {
        $current = memory_get_usage(true);
        $limit = $this->getMemoryLimit();

        if ($limit === PHP_INT_MAX) {
            return 0.0; // No limit
        }

        return ($current / $limit) * 100;
    }

    /**
     * Check if memory usage is approaching limit
     */
    protected function isMemoryThresholdExceeded(): bool
    {
        return memory_get_usage(true) > $this->memoryThreshold;
    }

    /**
     * Optimize query for memory efficiency
     */
    protected function optimizeQueryForMemory($query)
    {
        $recordCount = $this->getEstimatedRecordCount();

        // For large datasets, use chunking
        if ($recordCount > $this->maxBatchSize) {
            return $this->applyChunking($query);
        }

        // Limit select columns to only what's needed
        return $this->limitSelectColumns($query);
    }

    /**
     * Apply chunking for large datasets
     */
    protected function applyChunking($query)
    {
        // Note: This returns the query, actual chunking would be handled in the calling method
        $query->limit($this->maxBatchSize);
        
        return $query;
    }

    /**
     * Limit select columns to reduce memory usage
     */
    protected function limitSelectColumns($query)
    {
        $visibleColumns = array_keys(array_filter($this->visibleColumns));
        $selectColumns = [];

        // Always include the primary key
        $primaryKey = (new ($this->model))->getKeyName();
        $selectColumns[] = $primaryKey;

        // Add visible columns
        foreach ($visibleColumns as $columnKey) {
            if (isset($this->columns[$columnKey])) {
                $column = $this->columns[$columnKey];
                
                // Skip relation and function columns for now
                if (!isset($column['relation']) && !isset($column['function'])) {
                    if (isset($column['key']) && !in_array($column['key'], $selectColumns)) {
                        $selectColumns[] = $column['key'];
                    }
                }
            }
        }

        // Add sorting columns if not already included
        if (!empty($this->sortBy)) {
            if (!in_array($this->sortBy, $selectColumns)) {
                $selectColumns[] = $this->sortBy;
            }
        }

        if (!empty($selectColumns)) {
            $query->select($selectColumns);
        }

        return $query;
    }

    /**
     * Process large datasets in chunks
     */
    protected function processInChunks($query, callable $callback, int $chunkSize = null)
    {
        $chunkSize = $chunkSize ?? $this->maxBatchSize;
        $results = [];

        try {
            $query->chunk($chunkSize, function ($chunk) use ($callback, &$results) {
                // Check memory before processing each chunk
                if ($this->isMemoryThresholdExceeded()) {
                    Log::warning('Memory threshold exceeded during chunk processing');
                    return false; // Stop chunking
                }

                $chunkResults = $callback($chunk);
                if (is_array($chunkResults)) {
                    $results = array_merge($results, $chunkResults);
                }
            });
        } catch (\Exception $e) {
            Log::error('Chunk processing failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Clear unnecessary data from memory
     */
    protected function clearMemory()
    {
        // Clear any large arrays that might be cached
        if (property_exists($this, 'cachedData')) {
            $this->cachedData = [];
        }

        if (property_exists($this, 'processedRecords')) {
            $this->processedRecords = [];
        }

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Monitor memory during export operations
     */
    protected function monitorExportMemory($totalRecords): array
    {
        $memoryStats = $this->getCurrentMemoryUsage();
        $estimatedMemoryPerRecord = $memoryStats['current'] / max($totalRecords, 1);
        $recommendedBatchSize = min(
            $this->maxBatchSize,
            floor($this->memoryThreshold / max($estimatedMemoryPerRecord, 1))
        );

        return [
            'current_memory' => $memoryStats,
            'estimated_per_record' => $estimatedMemoryPerRecord,
            'recommended_batch_size' => max(100, $recommendedBatchSize), // Minimum 100 records
            'memory_safe' => !$this->isMemoryThresholdExceeded()
        ];
    }

    /**
     * Get memory optimization recommendations
     */
    public function getMemoryOptimizationRecommendations(): array
    {
        $memoryStats = $this->getCurrentMemoryUsage();
        $recordCount = $this->getEstimatedRecordCount();
        $columnCount = count($this->columns);
        $visibleColumnCount = count(array_filter($this->visibleColumns));

        $recommendations = [];

        // Memory usage recommendations
        if ($memoryStats['percentage'] > 80) {
            $recommendations[] = 'Critical: Memory usage is above 80%';
        } elseif ($memoryStats['percentage'] > 60) {
            $recommendations[] = 'Warning: Memory usage is above 60%';
        }

        // Record count recommendations
        if ($recordCount > 10000) {
            $recommendations[] = 'Consider implementing pagination for large datasets';
            $recommendations[] = 'Use chunking for export operations';
        }

        // Column recommendations
        if ($columnCount > 20) {
            $recommendations[] = 'Consider hiding non-essential columns to reduce memory usage';
        }

        if ($visibleColumnCount > 10) {
            $recommendations[] = 'Too many visible columns may impact performance';
        }

        // Query optimization recommendations
        $eagerLoads = method_exists($this, 'getEagerLoads') ? $this->getEagerLoads() : [];
        if (count($eagerLoads) > 5) {
            $recommendations[] = 'Consider selective eager loading to reduce memory usage';
        }

        return [
            'memory_stats' => $memoryStats,
            'record_count' => $recordCount,
            'column_stats' => [
                'total' => $columnCount,
                'visible' => $visibleColumnCount,
                'eager_loads' => count($eagerLoads)
            ],
            'recommendations' => $recommendations,
            'suggested_batch_size' => $this->calculateOptimalBatchSize($memoryStats, $recordCount)
        ];
    }

    /**
     * Calculate optimal batch size based on memory
     */
    protected function calculateOptimalBatchSize(array $memoryStats, int $recordCount): int
    {
        $availableMemory = $memoryStats['limit'] - $memoryStats['current'];
        $estimatedMemoryPerRecord = $memoryStats['current'] / max($recordCount, 1);

        // Conservative calculation: use 50% of available memory
        $optimalBatchSize = floor(($availableMemory * 0.5) / max($estimatedMemoryPerRecord, 1));

        // Ensure batch size is within reasonable bounds
        return max(100, min($this->maxBatchSize, $optimalBatchSize));
    }

    /**
     * Set memory threshold
     */
    public function setMemoryThreshold(int $threshold)
    {
        $this->memoryThreshold = $threshold;
    }

    /**
     * Set maximum batch size
     */
    public function setMaxBatchSize(int $size)
    {
        $this->maxBatchSize = $size;
    }

    /**
     * Get memory statistics for debugging
     */
    public function getMemoryStats(): array
    {
        return [
            'usage' => $this->getCurrentMemoryUsage(),
            'threshold' => $this->memoryThreshold,
            'max_batch_size' => $this->maxBatchSize,
            'is_threshold_exceeded' => $this->isMemoryThresholdExceeded(),
            'php_memory_limit' => ini_get('memory_limit'),
            'recommendations' => $this->getMemoryOptimizationRecommendations()
        ];
    }
}
