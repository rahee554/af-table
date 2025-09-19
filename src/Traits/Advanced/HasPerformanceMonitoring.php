<?php

namespace ArtflowStudio\Table\Traits\Advanced;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasPerformanceMonitoring
{
    /**
     * Performance monitoring configuration
     */
    protected $performanceConfig = [
        'enabled' => true,
        'log_slow_queries' => true,
        'slow_query_threshold' => 1000, // milliseconds
        'memory_threshold' => 50, // MB
        'log_memory_usage' => true,
        'profile_queries' => false,
    ];

    /**
     * Performance metrics storage
     */
    protected $performanceMetrics = [
        'query_times' => [],
        'memory_usage' => [],
        'query_counts' => [],
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];

    /**
     * Track query execution time
     */
    protected function trackQueryPerformance(callable $queryCallback, string $queryType = 'general'): mixed
    {
        if (! $this->performanceConfig['enabled']) {
            return $queryCallback();
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $queryCallback();

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsed = $endMemory - $startMemory;

            $this->recordPerformanceMetric($queryType, $executionTime, $memoryUsed);

            // Log slow queries
            if ($this->performanceConfig['log_slow_queries'] && $executionTime > $this->performanceConfig['slow_query_threshold']) {
                $this->logSlowQuery($queryType, $executionTime, $memoryUsed);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logQueryError($queryType, $e);
            throw $e;
        }
    }

    /**
     * Record performance metric
     */
    protected function recordPerformanceMetric(string $queryType, float $executionTime, int $memoryUsed): void
    {
        $this->performanceMetrics['query_times'][] = [
            'type' => $queryType,
            'time' => $executionTime,
            'timestamp' => time(),
        ];

        $this->performanceMetrics['memory_usage'][] = [
            'type' => $queryType,
            'memory' => $memoryUsed,
            'timestamp' => time(),
        ];

        $this->performanceMetrics['query_counts'][$queryType] =
            ($this->performanceMetrics['query_counts'][$queryType] ?? 0) + 1;

        // Keep only last 100 metrics to prevent memory bloat
        if (count($this->performanceMetrics['query_times']) > 100) {
            $this->performanceMetrics['query_times'] = array_slice($this->performanceMetrics['query_times'], -50);
        }

        if (count($this->performanceMetrics['memory_usage']) > 100) {
            $this->performanceMetrics['memory_usage'] = array_slice($this->performanceMetrics['memory_usage'], -50);
        }
    }

    /**
     * Log slow query for analysis
     */
    protected function logSlowQuery(string $queryType, float $executionTime, int $memoryUsed): void
    {
        Log::warning('Slow datatable query detected', [
            'table_id' => $this->tableId,
            'model' => $this->model,
            'query_type' => $queryType,
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'search' => $this->search ?? '',
            'sort_column' => $this->sortColumn ?? '',
            'sort_direction' => $this->sortDirection ?? '',
            'filters' => $this->filters ?? [],
            'per_page' => $this->perPage ?? 10,
            'visible_columns' => array_keys(array_filter($this->visibleColumns ?? [])),
        ]);
    }

    /**
     * Log query error
     */
    protected function logQueryError(string $queryType, \Exception $e): void
    {
        Log::error('Datatable query error', [
            'table_id' => $this->tableId,
            'model' => $this->model,
            'query_type' => $queryType,
            'error' => $e->getMessage(),
            'search' => $this->search ?? '',
            'sort_column' => $this->sortColumn ?? '',
            'filters' => $this->filters ?? [],
        ]);
    }

    /**
     * Monitor memory usage during query execution
     */
    protected function monitorMemoryUsage(): array
    {
        $memoryUsage = [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getPhpMemoryLimit(),
        ];

        $memoryUsage['usage_percentage'] = $memoryUsage['limit'] > 0
            ? ($memoryUsage['current'] / $memoryUsage['limit']) * 100
            : 0;

        // Warn if memory usage is high
        if ($this->performanceConfig['log_memory_usage'] &&
            $memoryUsage['usage_percentage'] > 80) {
            Log::warning('High memory usage in datatable', [
                'table_id' => $this->tableId,
                'memory_usage_mb' => round($memoryUsage['current'] / 1024 / 1024, 2),
                'memory_percentage' => round($memoryUsage['usage_percentage'], 2),
            ]);
        }

        return $memoryUsage;
    }

    /**
     * Get PHP memory limit in bytes for performance monitoring
     */
    protected function getPhpMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 0; // No limit
        }

        $value = (int) $memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Profile query execution with detailed breakdown
     */
    protected function profileQuery(Builder $query, string $operation): array
    {
        if (! $this->performanceConfig['profile_queries']) {
            return [];
        }

        $profile = [
            'operation' => $operation,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'timestamps' => [],
        ];

        $profile['timestamps']['start'] = microtime(true);

        // Enable query logging
        DB::enableQueryLog();

        try {
            // Execute the query
            $result = $query->get();

            $profile['timestamps']['end'] = microtime(true);
            $profile['execution_time'] = ($profile['timestamps']['end'] - $profile['timestamps']['start']) * 1000;
            $profile['result_count'] = $result->count();
            $profile['memory_used'] = memory_get_usage(true);

            // Get query log
            $queryLog = DB::getQueryLog();
            $profile['query_log'] = end($queryLog);

            return $profile;
        } finally {
            DB::disableQueryLog();
        }
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        $stats = [
            'query_count' => array_sum($this->performanceMetrics['query_counts']),
            'total_execution_time' => array_sum(array_column($this->performanceMetrics['query_times'], 'time')),
            'average_query_time' => 0,
            'slowest_query' => null,
            'memory_peak' => memory_get_peak_usage(true),
            'memory_current' => memory_get_usage(true),
            'cache_hit_rate' => 0,
            'query_breakdown' => $this->performanceMetrics['query_counts'],
        ];

        if (! empty($this->performanceMetrics['query_times'])) {
            $stats['average_query_time'] = $stats['total_execution_time'] / count($this->performanceMetrics['query_times']);
            $stats['slowest_query'] = max($this->performanceMetrics['query_times']);
        }

        $totalCacheRequests = $this->performanceMetrics['cache_hits'] + $this->performanceMetrics['cache_misses'];
        if ($totalCacheRequests > 0) {
            $stats['cache_hit_rate'] = ($this->performanceMetrics['cache_hits'] / $totalCacheRequests) * 100;
        }

        return $stats;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(): string
    {
        $stats = $this->getPerformanceStats();

        $report = "=== Datatable Performance Report ===\n";
        $report .= "Table ID: {$this->tableId}\n";
        $report .= "Model: {$this->model}\n";
        $report .= "Query Count: {$stats['query_count']}\n";
        $report .= 'Total Execution Time: '.round($stats['total_execution_time'], 2)."ms\n";
        $report .= 'Average Query Time: '.round($stats['average_query_time'], 2)."ms\n";
        $report .= 'Memory Usage: '.round($stats['memory_current'] / 1024 / 1024, 2)."MB\n";
        $report .= 'Memory Peak: '.round($stats['memory_peak'] / 1024 / 1024, 2)."MB\n";
        $report .= 'Cache Hit Rate: '.round($stats['cache_hit_rate'], 2)."%\n";

        if ($stats['slowest_query']) {
            $report .= 'Slowest Query: '.round($stats['slowest_query']['time'], 2)."ms ({$stats['slowest_query']['type']})\n";
        }

        $report .= "\nQuery Breakdown:\n";
        foreach ($stats['query_breakdown'] as $type => $count) {
            $report .= "  {$type}: {$count}\n";
        }

        return $report;
    }

    /**
     * Track cache performance
     */
    public function trackCacheHit(): void
    {
        $this->performanceMetrics['cache_hits']++;
    }

    /**
     * Track cache miss
     */
    public function trackCacheMiss(): void
    {
        $this->performanceMetrics['cache_misses']++;
    }

    /**
     * Reset performance metrics
     */
    public function resetPerformanceMetrics(): void
    {
        $this->performanceMetrics = [
            'query_times' => [],
            'memory_usage' => [],
            'query_counts' => [],
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }

    /**
     * Configure performance monitoring
     */
    public function configurePerformanceMonitoring(array $config): void
    {
        $this->performanceConfig = array_merge($this->performanceConfig, $config);
    }

    /**
     * Check if performance monitoring is enabled
     */
    public function isPerformanceMonitoringEnabled(): bool
    {
        return $this->performanceConfig['enabled'];
    }

    /**
     * Get current performance configuration
     */
    public function getPerformanceConfig(): array
    {
        return $this->performanceConfig;
    }

    /**
     * Analyze query performance and suggest optimizations
     */
    public function analyzePerformanceBottlenecks(): array
    {
        $stats = $this->getPerformanceStats();
        $suggestions = [];

        // Check for slow queries
        if ($stats['average_query_time'] > 500) {
            $suggestions[] = 'Average query time is high. Consider adding database indexes or optimizing queries.';
        }

        // Check memory usage
        $memoryUsageMB = $stats['memory_current'] / 1024 / 1024;
        if ($memoryUsageMB > 50) {
            $suggestions[] = 'High memory usage detected. Consider reducing the number of loaded relations or using pagination.';
        }

        // Check cache hit rate
        if ($stats['cache_hit_rate'] < 70 && ($this->performanceMetrics['cache_hits'] + $this->performanceMetrics['cache_misses']) > 10) {
            $suggestions[] = 'Low cache hit rate. Consider increasing cache TTL or reviewing cache key strategy.';
        }

        // Check for excessive queries
        if ($stats['query_count'] > 10) {
            $suggestions[] = 'High number of queries executed. Consider using eager loading or caching.';
        }

        return [
            'performance_stats' => $stats,
            'suggestions' => $suggestions,
            'severity' => $this->calculatePerformanceSeverity($stats),
        ];
    }

    /**
     * Calculate performance severity level
     */
    protected function calculatePerformanceSeverity(array $stats): string
    {
        $score = 0;

        if ($stats['average_query_time'] > 1000) {
            $score += 3;
        } elseif ($stats['average_query_time'] > 500) {
            $score += 2;
        } elseif ($stats['average_query_time'] > 200) {
            $score += 1;
        }

        if ($stats['memory_current'] / 1024 / 1024 > 100) {
            $score += 3;
        } elseif ($stats['memory_current'] / 1024 / 1024 > 50) {
            $score += 2;
        } elseif ($stats['memory_current'] / 1024 / 1024 > 25) {
            $score += 1;
        }

        if ($stats['query_count'] > 20) {
            $score += 3;
        } elseif ($stats['query_count'] > 10) {
            $score += 2;
        } elseif ($stats['query_count'] > 5) {
            $score += 1;
        }

        if ($score >= 6) {
            return 'critical';
        }
        if ($score >= 4) {
            return 'high';
        }
        if ($score >= 2) {
            return 'medium';
        }

        return 'low';
    }
}
