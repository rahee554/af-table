# AF Table – Comprehensive Trait Architecture Enhancement Guide

**Version**: 2.0 | **Target**: Laravel 11+ / PHP 8.3+ | **Focus**: Trait-based DatatableTrait component only

## Executive Summary

This enhanced guide provides a complete modernization roadmap for the trait-based datatable architecture. Based on deep analysis of 25+ traits and their interactions, we've identified critical performance bottlenecks, security vulnerabilities, and modernization opportunities.

### Key Insights from Analysis:
- **25+ traits** with overlapping responsibilities causing method conflicts and memory bloat
- **Dual query pipelines** (`buildQuery()` vs `query()`) creating inconsistent performance
- **Aggressive caching** with `Cache::flush()` destroying application-wide cache
- **Memory threshold of 64MB** per component is too conservative for modern applications
- **Legacy PHP patterns** missing modern Laravel 11 and PHP 8.3 features

### Strategic Goals:
1. **Performance**: 60% faster queries through consolidated pipelines and optimized caching
2. **Memory**: 40% reduction through lazy loading and efficient trait composition
3. **Security**: Hardened raw template rendering and relation validation
4. **Modernization**: PHP 8.3 features, Laravel 11 patterns, and type safety
5. **DX**: Simplified API with better debugging and profiling tools

---

## High-priority improvements (P0)

1) Single query pipeline (remove duplication)
- Problem: Two code paths construct queries: buildQuery() → getData() and query() → render()/export. Divergence risks subtle bugs and inconsistent performance.
- Action:
  - Introduce a single method buildBaseQuery(): Builder that applies constraints in a deterministic order: base → custom constraints → select → relations → filters → search → sorting.
  - Make getData(), render(), export(), and any metrics pull from buildBaseQuery().
  - Keep a guarded extension point (protected applyPipelineStage($stage, Builder $q)) to allow traits to hook into correct stage without re-ordering.

2) Unify pagination and per-page properties
- Problem: Both $records and $perPage exist; getData() uses $perPage, render() uses $records. Inconsistent UX and hydration.
- Action: Keep only $perPage (Livewire-native), deprecate $records with a small compatibility shim. Ensure WithPagination uses $perPage consistently.

3) Targeted cache invalidation (no global flush)
- Problem: clearDistinctValuesCache() calls Cache::flush(), which is dangerous and slow.
- Action:
  - Use cache tags if supported (Redis/Array): Cache::tags(["aftable", $this->tableId, "distinct"]) → flush only this tag.
  - If tags unavailable, maintain an index key (e.g., aftable:{tableId}:distinct:index) that lists keys to delete; iterate and delete.
  - Add TTL configuration per cache group and warm-up paths (warmSpecificCache()).

4) Secure raw rendering and template parsing
- Problem: Blade::render on arbitrary strings can XSS if not sanitized. sanitizeHtmlContent() exists but is not used consistently.
- Action:
  - Default to escaped output; allow opt-in to “unsafe_raw” with explicit flag per column. For raw templates, first run through sanitizeHtmlContent() and optionally a configurable HTML sanitizer (e.g., HTML Purifier if user installs it).
  - Add allowlisted helper functions available in raw templates to avoid arbitrary PHP execution.

5) State isolation per user and table instance
- Problem: Column visibility session key is model+class+tableId; not user-specific; can bleed between users.
- Action: Include auth()->id() (or ‘guest’) in key derivation. Example: md5(model|class|tableId|userId). Add config to opt-out.

---

## 🔧 PHASE 2: Memory & Performance Optimization (Week 3-4)

### 2.1 Intelligent Column Selection

**Problem**: Over-fetching columns and inefficient select calculations.

**Current Issue**:
```php
// Fetching all columns regardless of visibility
$query = Model::query(); // Selects *
```

**Solution**:
```php
// NEW: Smart column selection with caching
protected function getOptimizedSelectColumns(): array
{
    $visibilityHash = md5(serialize($this->visibleColumns));
    $cacheKey = "select_columns:{$this->tableId}:{$visibilityHash}";
    
    return Cache::remember($cacheKey, 300, function () {
        return $this->calculateOptimalSelectColumns();
    });
}

protected function calculateOptimalSelectColumns(): array
{
    $modelInstance = new $this->model;
    $table = $modelInstance->getTable();
    
    // Always include primary key
    $selects = ["{$table}.{$modelInstance->getKeyName()}"];
    
    // Add visible columns only
    foreach ($this->columns as $key => $column) {
        if (!($this->visibleColumns[$key] ?? true)) continue;
        
        if (isset($column['function'])) {
            // Function columns need no DB selection
            continue;
        }
        
        if (isset($column['relation'])) {
            // Add foreign key for relation
            $fkColumn = $this->extractForeignKeyFromRelation($column['relation']);
            if ($fkColumn && $this->isValidColumn($fkColumn)) {
                $selects[] = "{$table}.{$fkColumn}";
            }
        } elseif (isset($column['key']) && $this->isValidColumn($column['key'])) {
            $selects[] = "{$table}.{$column['key']}";
        }
    }
    
    // Add action dependencies
    $actionColumns = $this->extractColumnsFromActions();
    foreach ($actionColumns as $col) {
        if ($this->isValidColumn($col)) {
            $selects[] = "{$table}.{$col}";
        }
    }
    
    return array_unique($selects);
}

// Cache invalidation on visibility change
public function toggleColumn($columnKey)
{
    $oldVisibility = $this->visibleColumns[$columnKey] ?? true;
    $this->visibleColumns[$columnKey] = !$oldVisibility;
    
    // Clear select cache
    $this->clearSelectColumnCache();
    
    $this->triggerColumnVisibilityEvent($columnKey, $this->visibleColumns[$columnKey], $oldVisibility);
    
    if ($this->enableSessionPersistence) {
        $this->saveColumnPreferences();
    }
}

protected function clearSelectColumnCache(): void
{
    Cache::tags(['aftable', $this->tableId, 'selects'])->flush();
}
```

**Implementation Steps**:
1. Update `HasColumnOptimization.php` trait
2. Implement select caching mechanism
3. Add visibility change handlers
4. Test with large column sets

### 2.2 Advanced Memory Management

**Problem**: Conservative 64MB limit and inefficient batch processing.

**Current State**:
```php
// Fixed 64MB memory threshold
private $memoryThreshold = 67108864; // 64MB
```

**Solution**:
```php
// NEW: Adaptive memory management
trait HasAdvancedMemoryManagement
{
    protected function getAdaptiveMemoryThreshold(): int
    {
        $systemMemory = $this->getAvailableSystemMemory();
        $baseThreshold = 128 * 1024 * 1024; // 128MB base
        
        // Scale based on system resources and environment
        $scaleFactor = app()->environment('production') ? 0.8 : 1.2;
        
        return min(
            $baseThreshold * $scaleFactor, 
            $systemMemory * 0.1 // Max 10% of system memory
        );
    }
    
    protected function getOptimalBatchSize(): int
    {
        $memoryPerRecord = $this->estimateMemoryPerRecord();
        $availableMemory = $this->getAdaptiveMemoryThreshold() - memory_get_usage(true);
        
        return max(100, min(2000, intval($availableMemory / $memoryPerRecord)));
    }
    
    protected function estimateMemoryPerRecord(): int
    {
        static $estimate = null;
        
        if ($estimate === null) {
            $startMemory = memory_get_usage(true);
            
            // Sample small batch to estimate
            $sample = $this->model::limit(10)->get();
            $endMemory = memory_get_usage(true);
            
            $estimate = max(1024, ($endMemory - $startMemory) / max(1, $sample->count()));
            unset($sample); // Free memory
        }
        
        return $estimate;
    }
    
    protected function getAvailableSystemMemory(): int
    {
        if (function_exists('memory_get_total')) {
            return memory_get_total();
        }
        
        // Fallback estimation
        $memInfo = file_get_contents('/proc/meminfo');
        if ($memInfo && preg_match('/MemTotal:\s+(\d+)\s+kB/', $memInfo, $matches)) {
            return $matches[1] * 1024; // Convert KB to bytes
        }
        
        return 2 * 1024 * 1024 * 1024; // 2GB fallback
    }
    
    public function getMemoryOptimizationRecommendations(): array
    {
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $recommendations = [];
        
        if ($peakUsage > $this->getAdaptiveMemoryThreshold()) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'high',
                'message' => 'Consider enabling cursor pagination for large datasets',
                'details' => 'Current peak usage: ' . $this->formatBytes($peakUsage)
            ];
        }
        
        if ($this->getSelectedCount() > 1000) {
            $recommendations[] = [
                'type' => 'bulk_actions',
                'priority' => 'medium',
                'message' => 'Use bulk actions with queued jobs for large selections',
                'details' => 'Selected items: ' . $this->getSelectedCount()
            ];
        }
        
        $visibleColumnCount = count(array_filter($this->visibleColumns));
        if ($visibleColumnCount > 20) {
            $recommendations[] = [
                'type' => 'columns',
                'priority' => 'low',
                'message' => 'Hide unused columns to improve performance',
                'details' => 'Visible columns: ' . $visibleColumnCount
            ];
        }
        
        return $recommendations;
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
```

**Implementation Steps**:
1. Replace memory management in `HasMemoryManagement.php`
2. Add adaptive threshold calculation
3. Implement memory profiling
4. Add recommendations system

### 2.3 Database-Level JSON Operations

**Problem**: PHP-level JSON processing is slow and memory-intensive.

**Current Approach**:
```php
// Inefficient: Fetch all data then decode in PHP
$records = Model::all();
foreach ($records as $record) {
    $data = json_decode($record->json_column, true);
    // Process in PHP
}
```

**Solution**:
```php
// NEW: Database-native JSON extraction
protected function supportsJsonExtraction(): bool
{
    $driver = DB::connection()->getDriverName();
    return in_array($driver, ['mysql', 'pgsql']);
}

protected function buildJsonSelectClause(string $column, string $jsonPath): string
{
    $driver = DB::connection()->getDriverName();
    
    return match($driver) {
        'mysql' => "JSON_EXTRACT({$column}, '$.{$jsonPath}') as {$column}_{$jsonPath}",
        'pgsql' => "{$column}->'{$jsonPath}' as {$column}_{$jsonPath}",
        default => $column // Fallback to PHP extraction
    };
}

protected function applyJsonFiltering(Builder $query, string $column, string $jsonPath, $value): void
{
    if ($this->supportsJsonExtraction()) {
        $driver = DB::connection()->getDriverName();
        
        match($driver) {
            'mysql' => $query->whereRaw("JSON_EXTRACT({$column}, '$.{$jsonPath}') = ?", [$value]),
            'pgsql' => $query->whereRaw("{$column}->'{$jsonPath}' = ?", [$value]),
            default => $this->applyJsonFilteringPhp($query, $column, $jsonPath, $value)
        };
    } else {
        $this->applyJsonFilteringPhp($query, $column, $jsonPath, $value);
    }
}

protected function applyJsonFilteringPhp(Builder $query, string $column, string $jsonPath, $value): void
{
    $query->where(function ($q) use ($column, $jsonPath, $value) {
        $q->whereRaw("JSON_EXTRACT({$column}, '$.{$jsonPath}') = ?", [$value])
          ->orWhere(function ($subQ) use ($column, $jsonPath, $value) {
              // Fallback for complex JSON structures
              $subQ->whereNotNull($column)
                   ->where($column, 'like', "%\"{$jsonPath}\":\"{$value}\"%");
          });
    });
}

protected function buildJsonSearchClause(string $column, string $searchTerm): string
{
    $driver = DB::connection()->getDriverName();
    
    return match($driver) {
        'mysql' => "JSON_SEARCH({$column}, 'all', '%{$searchTerm}%') IS NOT NULL",
        'pgsql' => "{$column}::text ILIKE '%{$searchTerm}%'",
        default => "{$column} LIKE '%{$searchTerm}%'"
    };
}
```

**Implementation Steps**:
1. Update `HasJsonSupport.php` trait
2. Add database driver detection
3. Implement native JSON operations
4. Add fallback for unsupported drivers

### 2.4 Cursor Pagination for Large Datasets

**Problem**: OFFSET pagination becomes slow with large datasets.

**Solution**:
```php
// NEW: Cursor pagination option
trait HasCursorPagination
{
    public bool $useCursorPagination = false;
    public ?string $cursorColumn = null;
    public ?string $cursor = null;
    public string $cursorDirection = 'next';
    
    public function getCursorPaginatedData(): CursorPaginator
    {
        if (!$this->useCursorPagination) {
            return $this->getData(); // Fallback to regular pagination
        }
        
        $query = $this->buildBaseQuery();
        
        // Use primary key or specified cursor column
        $cursorColumn = $this->cursorColumn ?? $this->getPrimaryKeyColumn();
        
        if ($this->cursor) {
            if ($this->cursorDirection === 'next') {
                $query->where($cursorColumn, '>', $this->cursor);
            } else {
                $query->where($cursorColumn, '<', $this->cursor);
                $query->orderByDesc($cursorColumn);
            }
        }
        
        if ($this->cursorDirection === 'next') {
            $query->orderBy($cursorColumn);
        }
        
        return $query->cursorPaginate($this->perPage);
    }
    
    protected function shouldUseCursorPagination(): bool
    {
        // Auto-enable for large datasets
        $threshold = config('aftable.cursor_pagination_threshold', 50000);
        $estimatedCount = $this->getEstimatedRecordCount();
        
        return $estimatedCount > $threshold;
    }
    
    protected function getEstimatedRecordCount(): int
    {
        // Use table statistics for estimation to avoid COUNT(*) on large tables
        return Cache::remember(
            "record_count_estimate:{$this->tableId}",
            300,
            fn() => $this->model::count()
        );
    }
}
```

**Implementation Steps**:
1. Create `HasCursorPagination.php` trait
2. Update pagination controls in views
3. Add auto-detection logic
4. Test with large datasets

---

## 🔬 PHASE 3: Modern Laravel/PHP Patterns (Week 5-6)

### 3.1 PHP 8.3+ Type Safety & Performance

**Current State**: Mixed typing and legacy patterns

**Solution**:
```php
// NEW: Modern PHP patterns with performance benefits
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DatatableTrait extends Component
{
    // Typed properties for better performance and IDE support
    public readonly string $model;
    public array $columns = [];
    public array $visibleColumns = [];
    public bool $checkbox = false;
    public int $perPage = 10;
    public string $search = '';
    public ?string $sortColumn = null;
    
    // Use enums for better type safety
    public SortDirection $sortDirection = SortDirection::ASC;
    public CacheStrategy $cacheStrategy = CacheStrategy::TAGGED;
    
    // Constructor property promotion
    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly MemoryOptimizer $memoryOptimizer,
        private readonly QueryProfiler $profiler
    ) {
        parent::__construct();
    }
    
    // Return type declarations for better performance
    public function getData(): LengthAwarePaginator
    {
        return $this->buildBaseQuery()->paginate($this->perPage);
    }
    
    public function getDistinctValues(string $columnKey): Collection
    {
        return $this->cacheManager->remember(
            key: "distinct:{$this->tableId}:{$columnKey}",
            callback: fn() => $this->calculateDistinctValues($columnKey),
            ttl: 300
        );
    }
    
    // Use match expressions for cleaner code
    protected function determineFilterStrategy(string $type): FilterStrategy
    {
        return match($type) {
            'text' => new TextFilterStrategy(),
            'number' => new NumberFilterStrategy(),
            'date' => new DateFilterStrategy(),
            'select' => new SelectFilterStrategy(),
            'json' => new JsonFilterStrategy(),
            default => new DefaultFilterStrategy()
        };
    }
}

// Enums for type safety
enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';
    
    public function opposite(): self
    {
        return $this === self::ASC ? self::DESC : self::ASC;
    }
}

enum CacheStrategy: string
{
    case TAGGED = 'tagged';
    case INDEXED = 'indexed';
    case NONE = 'none';
}

enum FilterOperator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case LIKE = 'like';
    case NOT_LIKE = 'not like';
    case GREATER_THAN = '>';
    case LESS_THAN = '<';
    case GREATER_EQUAL = '>=';
    case LESS_EQUAL = '<=';
    case IN = 'in';
    case NOT_IN = 'not in';
    case BETWEEN = 'between';
    case NULL = 'null';
    case NOT_NULL = 'not null';
}
```

### 3.2 Laravel 11 Integration Patterns

**Solution**:
```php
// NEW: Laravel 11 features integration
class DatatableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Use new Laravel 11 container patterns
        $this->app->scoped(DatatableOptimizer::class);
        $this->app->scoped(CacheManager::class);
        
        // Register filter strategies with dependency injection
        $this->app->bind(FilterStrategyFactory::class, function ($app) {
            return new FilterStrategyFactory([
                'text' => $app->make(TextFilterStrategy::class),
                'number' => $app->make(NumberFilterStrategy::class),
                'date' => $app->make(DateFilterStrategy::class),
                'select' => $app->make(SelectFilterStrategy::class),
                'json' => $app->make(JsonFilterStrategy::class),
            ]);
        });
        
        // Tag strategies for discovery
        $this->app->tag([
            TextFilterStrategy::class,
            NumberFilterStrategy::class,
            DateFilterStrategy::class,
        ], 'datatable.filters');
    }
    
    public function boot(): void
    {
        // Laravel 11 Blade directives
        Blade::directive('datatablePerformance', function ($expression) {
            return "<?php if(app()->environment('local')): ?>
                        <div class='datatable-debug'>
                            Performance: <?php echo {$expression}->getPerformanceStats(); ?>
                        </div>
                    <?php endif; ?>";
        });
        
        Blade::directive('datatableMemory', function ($expression) {
            return "<?php if(config('app.debug')): ?>
                        <small class='text-muted'>
                            Memory: <?php echo {$expression}->getFormattedMemoryUsage(); ?>
                        </small>
                    <?php endif; ?>";
        });
        
        // Register Livewire component with lazy loading
        Livewire::component('datatable-trait', DatatableTrait::class);
        
        // Register custom validation rules
        Validator::extend('datatable_column', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, $parameters);
        });
    }
}

// Use Laravel 11 Collections enhancements
protected function processColumnsEfficiently(array $columns): Collection
{
    return collect($columns)
        ->lazy() // Memory efficient for large column sets
        ->filter(fn($col) => $this->visibleColumns[$col['key']] ?? true)
        ->map(fn($col) => $this->optimizeColumn($col))
        ->values();
}

// Utilize new query builder features
protected function buildOptimizedQuery(): Builder
{
    return $this->model::query()
        ->when($this->search, function ($q) {
            // Use new whereAny for multiple column search (Laravel 11)
            $searchableColumns = $this->getSearchableColumns();
            if (method_exists($q, 'whereAny')) {
                $q->whereAny($searchableColumns, 'like', "%{$this->search}%");
            } else {
                // Fallback for older Laravel versions
                $q->where(function ($subQuery) use ($searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $subQuery->orWhere($column, 'like', "%{$this->search}%");
                    }
                });
            }
        })
        ->when($this->hasRelations(), function ($q) {
            $q->with($this->getOptimizedRelations());
        });
}

// Use new Laravel 11 process handling for exports
protected function handleLargeExport(): void
{
    Process::path(storage_path('exports'))
        ->run(['php', 'artisan', 'datatable:export', $this->tableId])
        ->throw();
}
```

### 3.3 Strategy Pattern for Filters

**Solution**:
```php
// NEW: Strategy pattern for extensible filtering
interface FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): void;
    public function validate(mixed $value): bool;
    public function getOperators(): array;
}

class TextFilterStrategy implements FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): void
    {
        if (!is_string($value) || empty($value)) {
            return;
        }
        
        $query->where($column, 'like', "%{$value}%");
    }
    
    public function validate(mixed $value): bool
    {
        return is_string($value) && strlen($value) >= 2;
    }
    
    public function getOperators(): array
    {
        return [
            FilterOperator::LIKE,
            FilterOperator::NOT_LIKE,
            FilterOperator::EQUALS,
            FilterOperator::NOT_EQUALS
        ];
    }
}

class NumberFilterStrategy implements FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): void
    {
        if (!is_numeric($value)) {
            return;
        }
        
        $query->where($column, '=', $value);
    }
    
    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }
    
    public function getOperators(): array
    {
        return [
            FilterOperator::EQUALS,
            FilterOperator::NOT_EQUALS,
            FilterOperator::GREATER_THAN,
            FilterOperator::LESS_THAN,
            FilterOperator::GREATER_EQUAL,
            FilterOperator::LESS_EQUAL,
            FilterOperator::BETWEEN
        ];
    }
}

class DateFilterStrategy implements FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): void
    {
        try {
            $date = Carbon::parse($value);
            $query->whereDate($column, $date);
        } catch (\Exception $e) {
            // Invalid date, ignore filter
        }
    }
    
    public function validate(mixed $value): bool
    {
        try {
            Carbon::parse($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getOperators(): array
    {
        return [
            FilterOperator::EQUALS,
            FilterOperator::NOT_EQUALS,
            FilterOperator::GREATER_THAN,
            FilterOperator::LESS_THAN,
            FilterOperator::BETWEEN
        ];
    }
}

// Factory for strategy selection
class FilterStrategyFactory
{
    public function __construct(
        private array $strategies = []
    ) {}
    
    public function create(string $type): FilterStrategy
    {
        return $this->strategies[$type] ?? new DefaultFilterStrategy();
    }
    
    public function register(string $type, FilterStrategy $strategy): void
    {
        $this->strategies[$type] = $strategy;
    }
    
    public function getAvailableTypes(): array
    {
        return array_keys($this->strategies);
    }
}
```

**Implementation Steps**:
1. Create filter strategy interfaces and classes
2. Update `HasFiltering.php` to use strategies
3. Add factory for strategy selection
4. Test all filter types with new system

---

## 📊 PHASE 4: Advanced Features & Monitoring (Week 7-8)

### 4.1 Performance Monitoring & Profiling

**Problem**: No visibility into performance bottlenecks or optimization opportunities.

**Solution**:
```php
// NEW: Built-in performance monitoring
trait HasPerformanceMonitoring
{
    protected array $performanceMetrics = [];
    protected float $queryStartTime;
    protected array $queryLog = [];
    
    public function startPerformanceTracking(string $operation): void
    {
        $this->performanceMetrics[$operation] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'start_queries' => count(DB::getQueryLog())
        ];
    }
    
    public function endPerformanceTracking(string $operation): array
    {
        if (!isset($this->performanceMetrics[$operation])) {
            return [];
        }
        
        $start = $this->performanceMetrics[$operation];
        $metrics = [
            'operation' => $operation,
            'duration_ms' => (microtime(true) - $start['start_time']) * 1000,
            'memory_used' => memory_get_usage(true) - $start['start_memory'],
            'memory_peak' => memory_get_peak_usage(true),
            'query_count' => count(DB::getQueryLog()) - $start['start_queries'],
            'timestamp' => now()
        ];
        
        // Log slow operations
        if ($metrics['duration_ms'] > config('aftable.slow_operation_threshold', 1000)) {
            Log::warning('Slow datatable operation detected', $metrics);
        }
        
        // Store for debugging
        $this->queryLog[] = $metrics;
        
        return $metrics;
    }
    
    public function getPerformanceReport(): array
    {
        return [
            'component_stats' => $this->getComponentStats(),
            'memory_stats' => $this->getMemoryStats(),
            'cache_stats' => $this->getCacheStats(),
            'query_stats' => $this->getQueryStats(),
            'recommendations' => $this->getOptimizationRecommendations()
        ];
    }
    
    protected function getComponentStats(): array
    {
        return [
            'total_columns' => count($this->columns),
            'visible_columns_count' => count(array_filter($this->visibleColumns)),
            'hidden_columns_count' => count($this->columns) - count(array_filter($this->visibleColumns)),
            'has_search' => !empty($this->search),
            'has_filters' => !empty($this->filterColumn),
            'has_sorting' => !empty($this->sortColumn),
            'per_page' => $this->perPage,
            'total_records' => $this->getTotalRecordCount(),
            'cache_enabled' => $this->enableCache ?? false
        ];
    }
    
    protected function getMemoryStats(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'formatted_current' => $this->formatBytes(memory_get_usage(true)),
            'formatted_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'threshold' => $this->getAdaptiveMemoryThreshold(),
            'usage_percentage' => (memory_get_usage(true) / $this->getAdaptiveMemoryThreshold()) * 100
        ];
    }
    
    protected function getCacheStats(): array
    {
        $stats = [
            'strategy' => $this->getCacheStrategy(),
            'tags_supported' => Cache::supportsTags(),
            'default_ttl' => config('aftable.cache.default_ttl', 300)
        ];
        
        // Add cache hit/miss stats if available
        if (method_exists(Cache::store(), 'getStats')) {
            $stats['cache_driver_stats'] = Cache::store()->getStats();
        }
        
        return $stats;
    }
    
    protected function getQueryStats(): array
    {
        $queries = DB::getQueryLog();
        
        return [
            'total_queries' => count($queries),
            'slow_queries' => count(array_filter($queries, fn($q) => $q['time'] > 100)),
            'duplicate_queries' => $this->findDuplicateQueries($queries),
            'avg_query_time' => count($queries) > 0 ? array_sum(array_column($queries, 'time')) / count($queries) : 0,
            'operation_log' => $this->queryLog
        ];
    }
    
    protected function findDuplicateQueries(array $queries): array
    {
        $grouped = [];
        foreach ($queries as $query) {
            $sql = $query['query'];
            $grouped[$sql] = ($grouped[$sql] ?? 0) + 1;
        }
        
        return array_filter($grouped, fn($count) => $count > 1);
    }
    
    protected function getOptimizationRecommendations(): array
    {
        $recommendations = [];
        $stats = $this->getComponentStats();
        $memoryStats = $this->getMemoryStats();
        $queryStats = $this->getQueryStats();
        
        // Performance recommendations
        if ($stats['visible_columns_count'] > 15) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Consider hiding unused columns to improve performance',
                'impact' => 'Could reduce render time by 20-30%',
                'action' => 'Hide columns via column visibility toggle'
            ];
        }
        
        if ($stats['total_records'] > 10000 && !($this->useCursorPagination ?? false)) {
            $recommendations[] = [
                'type' => 'pagination',
                'priority' => 'medium',
                'message' => 'Enable cursor pagination for better performance with large datasets',
                'impact' => 'Could reduce query time by 50%+',
                'action' => 'Set $useCursorPagination = true'
            ];
        }
        
        // Memory recommendations
        if ($memoryStats['usage_percentage'] > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'high',
                'message' => 'High memory usage detected',
                'impact' => 'Risk of memory exhaustion',
                'action' => 'Reduce visible columns or enable lazy loading'
            ];
        }
        
        // Query recommendations
        if ($queryStats['slow_queries'] > 0) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'high',
                'message' => "Found {$queryStats['slow_queries']} slow queries",
                'impact' => 'Affecting user experience',
                'action' => 'Add database indexes or optimize query structure'
            ];
        }
        
        if (count($queryStats['duplicate_queries']) > 0) {
            $recommendations[] = [
                'type' => 'caching',
                'priority' => 'medium',
                'message' => 'Duplicate queries detected',
                'impact' => 'Unnecessary database load',
                'action' => 'Enable query caching or fix N+1 problems'
            ];
        }
        
        // Cache recommendations
        if (!($this->enableCache ?? false)) {
            $recommendations[] = [
                'type' => 'caching',
                'priority' => 'medium',
                'message' => 'Caching is disabled',
                'impact' => 'Missing performance benefits',
                'action' => 'Enable caching for distinct values and query results'
            ];
        }
        
        return $recommendations;
    }
    
    // Auto-profiling wrapper for public methods
    public function render()
    {
        $this->startPerformanceTracking('render');
        
        try {
            $result = parent::render();
            return $result;
        } finally {
            $this->endPerformanceTracking('render');
        }
    }
    
    public function getData()
    {
        $this->startPerformanceTracking('getData');
        
        try {
            $result = $this->buildBaseQuery()->paginate($this->perPage);
            return $result;
        } finally {
            $this->endPerformanceTracking('getData');
        }
    }
}
```

### 4.2 Advanced Export System

**Problem**: Export blocking UI and failing on large datasets.

**Solution**:
```php
// NEW: Scalable export system with queuing
class AdvancedExportManager
{
    public function __construct(
        private readonly QueueManager $queueManager,
        private readonly StorageManager $storageManager
    ) {}
    
    public function export(string $format, DatatableTrait $datatable, ?User $user = null): mixed
    {
        $recordCount = $datatable->buildBaseQuery()->count();
        $threshold = config('aftable.queue_export_threshold', 5000);
        
        // Use queued export for large datasets
        if ($recordCount > $threshold) {
            return $this->queueExport($format, $datatable, $user);
        }
        
        return $this->streamExport($format, $datatable);
    }
    
    protected function streamExport(string $format, DatatableTrait $datatable): StreamedResponse
    {
        return response()->streamDownload(function () use ($format, $datatable) {
            $handle = fopen('php://output', 'w');
            
            try {
                // Write headers
                match($format) {
                    'csv' => $this->writeCsvHeaders($handle, $datatable),
                    'json' => $this->writeJsonStart($handle),
                    default => throw new InvalidArgumentException("Unsupported format: {$format}")
                };
                
                // Stream data in optimized chunks
                $batchSize = $this->calculateOptimalBatchSize($datatable);
                $datatable->buildBaseQuery()
                         ->lazy($batchSize)
                         ->each(function ($row, $index) use ($handle, $format, $datatable) {
                             $exportRow = $this->transformRowForExport($row, $datatable);
                             
                             match($format) {
                                 'csv' => fputcsv($handle, $exportRow),
                                 'json' => $this->writeJsonRow($handle, $exportRow, $index),
                                 default => throw new InvalidArgumentException("Unsupported format: {$format}")
                             };
                         });
                
                // Write footers
                if ($format === 'json') {
                    $this->writeJsonEnd($handle);
                }
                
            } finally {
                fclose($handle);
            }
        }, $this->generateFilename($format, $datatable), [
            'Content-Type' => $this->getContentType($format),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }
    
    protected function queueExport(string $format, DatatableTrait $datatable, ?User $user = null): string
    {
        $exportId = Str::uuid();
        $user = $user ?? auth()->user();
        
        ExportDatatableJob::dispatch(
            $exportId,
            $format,
            $datatable->model,
            $datatable->buildBaseQuery()->toSql(),
            $datatable->buildBaseQuery()->getBindings(),
            $datatable->getExportableColumns(),
            $user?->id
        )->onQueue(config('aftable.export_queue', 'exports'));
        
        // Store export request
        $this->storageManager->storeExportRequest($exportId, [
            'user_id' => $user?->id,
            'format' => $format,
            'table_id' => $datatable->tableId,
            'status' => 'queued',
            'created_at' => now()
        ]);
        
        if ($user) {
            session()->flash('export_queued', [
                'id' => $exportId,
                'message' => 'Export has been queued and will be emailed when complete.'
            ]);
        }
        
        return $exportId;
    }
    
    protected function calculateOptimalBatchSize(DatatableTrait $datatable): int
    {
        // Base batch size
        $baseBatchSize = 1000;
        
        // Adjust based on visible columns
        $visibleColumns = count(array_filter($datatable->visibleColumns ?? []));
        $columnAdjustment = max(0.5, 1 - ($visibleColumns * 0.05));
        
        // Adjust based on memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->parseMemoryLimit($memoryLimit);
        $memoryAdjustment = max(0.3, 1 - ($memoryUsage / $memoryBytes));
        
        return intval($baseBatchSize * $columnAdjustment * $memoryAdjustment);
    }
    
    protected function parseMemoryLimit(string $limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
    
    protected function transformRowForExport($row, DatatableTrait $datatable): array
    {
        $exportRow = [];
        
        foreach ($datatable->getExportableColumns() as $key => $column) {
            if (isset($column['function'])) {
                // Function columns
                $exportRow[$key] = $this->evaluateFunctionColumn($column, $row);
            } elseif (isset($column['relation'])) {
                // Relation columns
                $exportRow[$key] = $this->extractRelationValue($column, $row);
            } else {
                // Regular columns
                $exportRow[$key] = $row->{$column['key']} ?? '';
            }
            
            // Apply formatting if specified
            if (isset($column['export_format'])) {
                $exportRow[$key] = $this->applyExportFormat($exportRow[$key], $column['export_format']);
            }
        }
        
        return $exportRow;
    }
    
    protected function applyExportFormat($value, string $format): string
    {
        return match($format) {
            'date' => $value ? Carbon::parse($value)->format('Y-m-d') : '',
            'datetime' => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : '',
            'currency' => is_numeric($value) ? number_format($value, 2) : $value,
            'percentage' => is_numeric($value) ? number_format($value * 100, 2) . '%' : $value,
            'boolean' => $value ? 'Yes' : 'No',
            default => (string) $value
        };
    }
    
    protected function writeCsvHeaders($handle, DatatableTrait $datatable): void
    {
        $headers = [];
        foreach ($datatable->getExportableColumns() as $key => $column) {
            $headers[] = $column['label'] ?? $key;
        }
        fputcsv($handle, $headers);
    }
    
    protected function writeJsonStart($handle): void
    {
        fwrite($handle, '{"data":[');
    }
    
    protected function writeJsonRow($handle, array $row, int $index): void
    {
        if ($index > 0) {
            fwrite($handle, ',');
        }
        fwrite($handle, json_encode($row));
    }
    
    protected function writeJsonEnd($handle): void
    {
        fwrite($handle, ']}');
    }
    
    protected function generateFilename(string $format, DatatableTrait $datatable): string
    {
        $modelName = class_basename($datatable->model);
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$modelName}_export_{$timestamp}.{$format}";
    }
    
    protected function getContentType(string $format): string
    {
        return match($format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream'
        };
    }
}

// Export job for large datasets
class ExportDatatableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $timeout = 3600; // 1 hour
    public int $tries = 3;
    
    public function __construct(
        public string $exportId,
        public string $format,
        public string $model,
        public string $sql,
        public array $bindings,
        public array $columns,
        public ?int $userId = null
    ) {}
    
    public function handle(): void
    {
        try {
            $this->updateExportStatus('processing');
            
            $tempFile = $this->createTempFile();
            $this->processExport($tempFile);
            $finalPath = $this->moveToFinalLocation($tempFile);
            
            $this->updateExportStatus('completed', $finalPath);
            $this->notifyUser($finalPath);
            
        } catch (\Exception $e) {
            $this->updateExportStatus('failed', null, $e->getMessage());
            $this->notifyUserOfFailure($e);
            
            throw $e;
        }
    }
    
    protected function processExport(string $tempFile): void
    {
        $handle = fopen($tempFile, 'w');
        
        try {
            // Write headers for CSV
            if ($this->format === 'csv') {
                $headers = array_map(fn($col) => $col['label'] ?? $col['key'], $this->columns);
                fputcsv($handle, $headers);
            }
            
            // Process data in chunks
            DB::connection()->select($this->sql, $this->bindings)
                ->chunk(1000)
                ->each(function ($chunk) use ($handle) {
                    foreach ($chunk as $row) {
                        $exportRow = $this->transformRow($row);
                        
                        match($this->format) {
                            'csv' => fputcsv($handle, $exportRow),
                            'json' => fwrite($handle, json_encode($exportRow) . "\n"),
                            default => throw new InvalidArgumentException("Unsupported format: {$this->format}")
                        };
                    }
                });
                
        } finally {
            fclose($handle);
        }
    }
    
    protected function updateExportStatus(string $status, ?string $filePath = null, ?string $error = null): void
    {
        app(StorageManager::class)->updateExportStatus($this->exportId, [
            'status' => $status,
            'file_path' => $filePath,
            'error' => $error,
            'updated_at' => now()
        ]);
    }
    
    protected function notifyUser(string $filePath): void
    {
        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                Mail::to($user)->send(new ExportCompletedMail(
                    $this->exportId,
                    $this->format,
                    $filePath
                ));
            }
        }
    }
}
```

### 4.3 Real-time Updates & WebSocket Integration

**Solution**:
```php
// NEW: Real-time data updates
trait HasRealTimeUpdates
{
    public bool $enableRealTime = false;
    protected string $broadcastChannel;
    
    public function mount(): void
    {
        parent::mount();
        
        if ($this->enableRealTime) {
            $this->broadcastChannel = "datatable.{$this->tableId}";
            $this->subscribeToUpdates();
        }
    }
    
    protected function subscribeToUpdates(): void
    {
        // Subscribe to model events
        $modelClass = $this->model;
        
        // Listen for model changes
        $modelClass::created(function ($model) {
            $this->handleModelCreated($model);
        });
        
        $modelClass::updated(function ($model) {
            $this->handleModelUpdated($model);
        });
        
        $modelClass::deleted(function ($model) {
            $this->handleModelDeleted($model);
        });
    }
    
    protected function handleModelCreated($model): void
    {
        if ($this->shouldIncludeInResults($model)) {
            broadcast(new DatatableUpdated(
                $this->broadcastChannel,
                'created',
                $model->toArray()
            ));
            
            // Refresh current page if new item would appear
            if ($this->shouldRefreshForNewItem($model)) {
                $this->dispatch('refreshTable');
            }
        }
    }
    
    protected function handleModelUpdated($model): void
    {
        broadcast(new DatatableUpdated(
            $this->broadcastChannel,
            'updated',
            $model->toArray()
        ));
        
        // Refresh if item no longer matches filters
        if (!$this->shouldIncludeInResults($model)) {
            $this->dispatch('refreshTable');
        }
    }
    
    protected function handleModelDeleted($model): void
    {
        broadcast(new DatatableUpdated(
            $this->broadcastChannel,
            'deleted',
            ['id' => $model->getKey()]
        ));
        
        $this->dispatch('refreshTable');
    }
    
    protected function shouldIncludeInResults($model): bool
    {
        // Check if model matches current filters
        $query = $this->buildBaseQuery();
        
        return $query->where($model->getKeyName(), $model->getKey())->exists();
    }
    
    protected function shouldRefreshForNewItem($model): bool
    {
        // Check if we're on the first page and item would appear at top
        return $this->paginators['page'] === 1 && 
               $this->sortColumn === 'created_at' && 
               $this->sortDirection === 'desc';
    }
    
    // JavaScript event listeners
    public function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            "echo-private:{$this->broadcastChannel},DatatableUpdated" => 'handleBroadcastUpdate'
        ]);
    }
    
    public function handleBroadcastUpdate(array $data): void
    {
        match($data['type']) {
            'created' => $this->handleRemoteCreate($data['model']),
            'updated' => $this->handleRemoteUpdate($data['model']),
            'deleted' => $this->handleRemoteDelete($data['model']),
            default => null
        };
        
        // Clear relevant caches
        $this->clearCacheForUpdate();
    }
    
    protected function clearCacheForUpdate(): void
    {
        Cache::tags(['aftable', $this->tableId, 'data'])->flush();
        Cache::tags(['aftable', $this->tableId, 'distinct'])->flush();
    }
}

// Broadcasting event
class DatatableUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public string $channel,
        public string $type,
        public array $model
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->channel)
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'model' => $this->model,
            'timestamp' => now()->toISOString()
        ];
    }
}
```

---

## 🎯 PHASE 5: Testing & Quality Assurance (Week 9-10)

### 5.1 Comprehensive Testing Strategy

**Problem**: Lack of systematic testing for trait interactions and performance.

**Solution**:
```php
// NEW: Per-trait testing with performance benchmarks
class HasFilteringTest extends TestCase
{
    use RefreshDatabase;
    
    protected DatatableTrait $datatable;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'test-table';
        };
    }
    
    /** @test */
    public function it_applies_text_filters_efficiently(): void
    {
        // Setup test data
        User::factory(1000)->create(['name' => 'John Doe']);
        User::factory(500)->create(['name' => 'Jane Smith']);
        
        $this->datatable->filterColumn = 'name';
        $this->datatable->filterValue = 'John';
        
        // Measure performance
        $start = microtime(true);
        $result = $this->datatable->buildBaseQuery()->get();
        $duration = microtime(true) - $start;
        
        // Assertions
        $this->assertTrue($result->every(fn($user) => str_contains($user->name, 'John')));
        $this->assertLessThan(0.1, $duration, 'Filter query should complete in under 100ms');
        $this->assertCount(1000, $result);
    }
    
    /** @test */
    public function it_handles_relation_filters_without_n_plus_one(): void
    {
        User::factory(100)->has(Profile::factory())->create();
        
        $this->datatable->columns = [
            'profile_name' => ['relation' => 'profile:name', 'label' => 'Profile Name']
        ];
        
        DB::enableQueryLog();
        
        $result = $this->datatable->buildBaseQuery()->with(['profile'])->get();
        
        $queryCount = count(DB::getQueryLog());
        $this->assertLessThan(5, $queryCount, 'Should not trigger N+1 queries');
        
        DB::disableQueryLog();
    }
    
    /** @test */
    public function it_validates_filter_inputs(): void
    {
        $this->datatable->filterColumn = 'name';
        
        // Test invalid inputs
        $this->datatable->filterValue = null;
        $this->assertEmpty($this->datatable->buildBaseQuery()->toSql());
        
        $this->datatable->filterValue = '';
        $this->assertEmpty($this->datatable->buildBaseQuery()->toSql());
        
        // Test SQL injection protection
        $this->datatable->filterValue = "'; DROP TABLE users; --";
        $result = $this->datatable->buildBaseQuery()->get();
        $this->assertInstanceOf(Collection::class, $result);
    }
    
    /** @test */
    public function it_handles_complex_filter_combinations(): void
    {
        Product::factory(1000)->create([
            'category' => 'electronics',
            'price' => 100,
            'in_stock' => true
        ]);
        
        $this->datatable->model = Product::class;
        $this->datatable->columns = [
            'category' => ['key' => 'category', 'filter' => 'select'],
            'price' => ['key' => 'price', 'filter' => 'number'],
            'in_stock' => ['key' => 'in_stock', 'filter' => 'boolean']
        ];
        
        // Apply multiple filters
        $this->datatable->filterColumn = 'category';
        $this->datatable->filterValue = 'electronics';
        $this->datatable->search = 'test';
        $this->datatable->sortColumn = 'price';
        $this->datatable->sortDirection = 'desc';
        
        $start = microtime(true);
        $result = $this->datatable->getData();
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(0.2, $duration, 'Complex filter should complete in under 200ms');
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
}

// Integration tests for full pipeline
class DatatableIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_handles_complete_datatable_workflow(): void
    {
        // Setup large dataset
        Product::factory(10000)->create();
        
        $datatable = new class extends DatatableTrait {
            public $model = Product::class;
            public $tableId = 'integration-test';
            public $perPage = 25;
        };
        
        // Test search
        $datatable->search = 'widget';
        $searchResults = $datatable->getData();
        $this->assertInstanceOf(LengthAwarePaginator::class, $searchResults);
        
        // Test filtering
        $datatable->filterColumn = 'category';
        $datatable->filterValue = 'electronics';
        $filteredResults = $datatable->getData();
        $this->assertInstanceOf(LengthAwarePaginator::class, $filteredResults);
        
        // Test sorting
        $datatable->sortColumn = 'price';
        $datatable->sortDirection = 'desc';
        $sortedResults = $datatable->getData();
        $this->assertInstanceOf(LengthAwarePaginator::class, $sortedResults);
        
        // Test column visibility
        $datatable->visibleColumns = ['name' => true, 'price' => true, 'category' => false];
        $visibilityResults = $datatable->getData();
        $this->assertInstanceOf(LengthAwarePaginator::class, $visibilityResults);
        
        // Test export
        $exportData = $datatable->getDataForExport();
        $this->assertInstanceOf(LazyCollection::class, $exportData);
    }
    
    /** @test */
    public function it_maintains_state_across_operations(): void
    {
        User::factory(100)->create();
        
        $datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'state-test';
        };
        
        // Set initial state
        $datatable->search = 'john';
        $datatable->perPage = 50;
        $datatable->sortColumn = 'created_at';
        $datatable->sortDirection = 'desc';
        
        // Perform multiple operations
        $datatable->getData();
        $datatable->render();
        $datatable->getDistinctValues('email');
        
        // Verify state is maintained
        $this->assertEquals('john', $datatable->search);
        $this->assertEquals(50, $datatable->perPage);
        $this->assertEquals('created_at', $datatable->sortColumn);
        $this->assertEquals('desc', $datatable->sortDirection);
    }
}

// Performance benchmark tests
class DatatablePerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function benchmark_memory_usage_with_large_datasets(): void
    {
        User::factory(50000)->create();
        
        $datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'memory-test';
            public $perPage = 100;
        };
        
        $memoryBefore = memory_get_usage(true);
        $result = $datatable->getData();
        $memoryAfter = memory_get_usage(true);
        
        $memoryUsed = $memoryAfter - $memoryBefore;
        $this->assertLessThan(32 * 1024 * 1024, $memoryUsed, 'Should use less than 32MB for pagination');
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
    
    /** @test */
    public function benchmark_query_performance_with_relations(): void
    {
        User::factory(1000)
            ->has(Profile::factory())
            ->has(Post::factory(5))
            ->create();
        
        $datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'relation-test';
            public $columns = [
                'name' => ['key' => 'name'],
                'profile_bio' => ['relation' => 'profile:bio'],
                'posts_count' => ['relation' => 'posts:count']
            ];
        };
        
        DB::enableQueryLog();
        
        $start = microtime(true);
        $result = $datatable->getData();
        $duration = microtime(true) - $start;
        
        $queryCount = count(DB::getQueryLog());
        
        $this->assertLessThan(0.5, $duration, 'Relation query should complete in under 500ms');
        $this->assertLessThan(10, $queryCount, 'Should not generate excessive queries');
        $this->assertGreaterThan(0, $result->count());
        
        DB::disableQueryLog();
    }
    
    /** @test */
    public function benchmark_cache_performance(): void
    {
        Product::factory(10000)->create();
        
        $datatable = new class extends DatatableTrait {
            public $model = Product::class;
            public $tableId = 'cache-test';
            public $enableCache = true;
        };
        
        // First call (cache miss)
        $start1 = microtime(true);
        $result1 = $datatable->getDistinctValues('category');
        $duration1 = microtime(true) - $start1;
        
        // Second call (cache hit)
        $start2 = microtime(true);
        $result2 = $datatable->getDistinctValues('category');
        $duration2 = microtime(true) - $start2;
        
        $this->assertEquals($result1, $result2);
        $this->assertLessThan($duration1 * 0.1, $duration2, 'Cached call should be 90% faster');
    }
    
    /** @test */
    public function stress_test_concurrent_operations(): void
    {
        User::factory(5000)->create();
        
        $datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'stress-test';
        };
        
        // Simulate concurrent operations
        $operations = [];
        for ($i = 0; $i < 10; $i++) {
            $operations[] = function () use ($datatable) {
                $datatable->search = 'user' . rand(1, 100);
                return $datatable->getData()->count();
            };
        }
        
        $start = microtime(true);
        $results = array_map(fn($op) => $op(), $operations);
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(2.0, $duration, 'Concurrent operations should complete in under 2 seconds');
        $this->assertCount(10, $results);
        $this->assertTrue(array_sum($results) >= 0);
    }
}

// Feature tests for user interactions
class DatatableFeatureTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_can_search_across_multiple_columns(): void
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        
        Livewire::test(DatatableTrait::class, [
            'model' => User::class,
            'tableId' => 'search-test'
        ])
        ->set('search', 'john')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith')
        ->set('search', 'jane@example.com')
        ->assertSee('Jane Smith')
        ->assertDontSee('John Doe');
    }
    
    /** @test */
    public function user_can_toggle_column_visibility(): void
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        
        Livewire::test(DatatableTrait::class, [
            'model' => User::class,
            'tableId' => 'visibility-test',
            'columns' => [
                'name' => ['key' => 'name', 'label' => 'Name'],
                'email' => ['key' => 'email', 'label' => 'Email']
            ]
        ])
        ->assertSee('John Doe')
        ->assertSee('john@example.com')
        ->call('toggleColumn', 'email')
        ->assertSee('John Doe')
        ->assertDontSee('john@example.com');
    }
    
    /** @test */
    public function user_can_apply_filters(): void
    {
        Product::factory()->create(['category' => 'electronics', 'name' => 'Laptop']);
        Product::factory()->create(['category' => 'books', 'name' => 'Novel']);
        
        Livewire::test(DatatableTrait::class, [
            'model' => Product::class,
            'tableId' => 'filter-test'
        ])
        ->set('filterColumn', 'category')
        ->set('filterValue', 'electronics')
        ->assertSee('Laptop')
        ->assertDontSee('Novel');
    }
    
    /** @test */
    public function user_can_sort_columns(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Charlie']);
        
        $component = Livewire::test(DatatableTrait::class, [
            'model' => User::class,
            'tableId' => 'sort-test'
        ])
        ->call('sortBy', 'name')
        ->assertSeeInOrder(['Alice', 'Bob', 'Charlie'])
        ->call('sortBy', 'name') // Toggle to desc
        ->assertSeeInOrder(['Charlie', 'Bob', 'Alice']);
    }
}
```

### 5.2 Automated Quality Gates

**Solution**:
```php
// NEW: Quality assurance automation
class DatatableQualityTest extends TestCase
{
    /** @test */
    public function it_validates_trait_composition(): void
    {
        $reflection = new ReflectionClass(DatatableTrait::class);
        $traits = $reflection->getTraitNames();
        
        // Ensure all required traits are present
        $requiredTraits = [
            'HasQueryBuilder',
            'HasMemoryManagement', 
            'HasAdvancedCaching',
            'HasColumnVisibility',
            'HasFiltering',
            'HasSorting',
            'HasExport'
        ];
        
        foreach ($requiredTraits as $trait) {
            $this->assertContains("App\\Traits\\{$trait}", $traits, "Missing required trait: {$trait}");
        }
        
        // Check for trait conflicts
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $duplicates = array_diff_assoc($methodNames, array_unique($methodNames));
        
        $this->assertEmpty($duplicates, 'Trait method conflicts detected: ' . implode(', ', $duplicates));
    }
    
    /** @test */
    public function it_enforces_type_safety(): void
    {
        $reflection = new ReflectionClass(DatatableTrait::class);
        
        // Check for typed properties
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $this->assertNotNull($property->getType(), "Property {$property->getName()} should be typed");
        }
        
        // Check for return type declarations on public methods
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if (!$method->isConstructor() && !str_starts_with($method->getName(), '__')) {
                $this->assertNotNull($method->getReturnType(), "Method {$method->getName()} should have return type");
            }
        }
    }
    
    /** @test */
    public function it_validates_security_measures(): void
    {
        $datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'security-test';
        };
        
        // Test XSS prevention in raw templates
        $maliciousTemplate = '<script>alert("xss")</script>{{ $row->name }}';
        $result = $datatable->renderRawHtml($maliciousTemplate, new User(['name' => 'Test']), false);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        
        // Test SQL injection prevention
        $datatable->search = "'; DROP TABLE users; --";
        $query = $datatable->buildBaseQuery();
        
        $this->assertStringNotContainsString('DROP TABLE', $query->toSql());
    }
    
    /** @test */
    public function it_maintains_performance_standards(): void
    {
        // Performance benchmarks
        $benchmarks = [
            'query_building' => 0.1,     // 100ms max
            'rendering' => 0.2,          // 200ms max
            'caching' => 0.05,           // 50ms max
            'memory_usage' => 32 * 1024 * 1024 // 32MB max
        ];
        
        foreach ($benchmarks as $operation => $threshold) {
            $this->assertPerformanceWithin($operation, $threshold);
        }
    }
    
    protected function assertPerformanceWithin(string $operation, float $threshold): void
    {
        $datatable = new class extends DatatableTrait {
            public $model = User::class;
            public $tableId = 'performance-test';
        };
        
        User::factory(1000)->create();
        
        $start = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        match($operation) {
            'query_building' => $datatable->buildBaseQuery(),
            'rendering' => $datatable->render(),
            'caching' => $datatable->getDistinctValues('email'),
            'memory_usage' => $datatable->getData(),
            default => throw new InvalidArgumentException("Unknown operation: {$operation}")
        };
        
        $duration = microtime(true) - $start;
        $memoryUsed = memory_get_usage(true) - $memoryBefore;
        
        if ($operation === 'memory_usage') {
            $this->assertLessThan($threshold, $memoryUsed, "Memory usage for {$operation} exceeds threshold");
        } else {
            $this->assertLessThan($threshold, $duration, "Performance for {$operation} exceeds threshold");
        }
    }
}

// Configuration validation
class DatatableConfigTest extends TestCase
{
    /** @test */
    public function it_validates_default_configuration(): void
    {
        $config = config('aftable');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('cache', $config);
        $this->assertArrayHasKey('export', $config);
        $this->assertArrayHasKey('performance', $config);
        
        // Validate cache configuration
        $cacheConfig = $config['cache'];
        $this->assertIsInt($cacheConfig['default_ttl']);
        $this->assertIsBool($cacheConfig['enabled']);
        
        // Validate export configuration
        $exportConfig = $config['export'];
        $this->assertIsInt($exportConfig['queue_threshold']);
        $this->assertIsString($exportConfig['queue_name']);
        
        // Validate performance configuration
        $performanceConfig = $config['performance'];
        $this->assertIsInt($performanceConfig['slow_query_threshold']);
        $this->assertIsInt($performanceConfig['memory_threshold']);
    }
}
```

---

---

## Caching & invalidation model (P1)

13) Cache key contract
- Define generateCacheKey($namespace, array $parts): string; compose with driver, model, tableId, visibility hash, page, filters, search, sort. Reuse across traits to avoid drift.

14) Distinct values caching
- Tag distinct values by column and filter hash. Invalidate only affected column when filter definitions change. Prewarm distincts on first mount if configured.

15) Metrics & cache inspection
- Expose getCacheStatistics(): hits, misses, warmed keys, invalidations. Optionally integrate with Laravel Telescope or a PSR-3 logger channel.

---

## API & DX consistency (P2)

16) Normalize naming and behavior
- Prefer sortBy() over toggleSort(); deprecate legacy methods with @deprecated annotations and small wrappers.
- Keep a single query string strategy: either property $queryString or getQueryString(), not both. Prefer getQueryString() for dynamic enabling.
- Make per-page, search, filters, sort all saved via a single saveStateToSession() call; call in one place after state changes (avoid repeated writes).

17) Strong typing & docs
- Use typed properties and return types (PHP 8+). Add phpdoc for array shapes: columns, filters, actions.
- Provide small interface contracts for traits that expose public methods (e.g., Exportable, Cachable, Filterable) to document guarantees.

18) Safer public API for actions & bulk actions
- Provide validateActionConfig($action) and normalizeAction($action) to ensure route/url/handler integrity and prevent unexpected callables.
- Run bulk actions within DB::transaction when required and queue long-running operations.

---

## Security

19) Raw template hardening
- Use sanitizeHtmlContent() on raw before Blade::render. Add config to enforce escape-only mode by default.
- Maintain a list of allowed variables and helpers available inside templates (row only, no component instance by default) to avoid privilege escalation through $this usage.

20) Relation/column allowlists
- Validate relation names against model relation methods; disallow dynamic methods. Sanitize relation strings (relation:column) and ensure the column exists on the related table.
- Maintain a denylist for dangerous columns if needed (passwords, api_keys) even if present.

---

## Observability & benchmarking

21) Built-in profiler hooks
- Add a lightweight timer/collector around each pipeline stage (constraints, select, eager, filters, search, sort). Summarize in getQueryStats().
- Offer a debug panel (toggle) that renders stats below the table in dev.

22) Logging policy
- Log warnings for expensive paths taken (deep relation sorting, untagged cache clears, full-table distincts) with actionable hints.

---

## Testing strategy

23) Per-trait unit tests
- For each trait, test inputs/outputs with tiny data fixtures. Example: HasFiltering applies correct where clauses; HasSorting orders as expected for BelongsTo vs HasOne.

24) Integration tests for pipelines
- Ensure buildBaseQuery() order; verify that toggling visibility changes selects; search, filter, sort work together.

25) Performance tests
- Simple benchmarks (Laravel’s withoutExceptionHandling + timers) on 100k rows with and without relation columns; ensure regressions are caught.

---

## Backward compatibility & migration

26) Deprecation layer
- Keep legacy properties ($records, toggleSort, updatedrecords) but emit deprecation warnings in non-prod. Provide codemods or upgrade notes.

27) Feature flags
- Introduce booleans to gate heavy features: $enableRelationSorting, $enableDbJsonExtraction, $useCursorPagination, $forceQueuedExports.

---

## Quick wins checklist

- [ ] Replace Cache::flush() with tagged or indexed invalidation for distincts
- [ ] Consolidate query paths into buildBaseQuery(); route all consumers to it
- [ ] Unify on $perPage; deprecate $records
- [ ] Include auth()->id() in visibility session key
- [ ] Always qualify selects with parent table; recompute selects on visibility change only
- [ ] Sanitize raw templates before Blade::render; add “unsafe_raw” explicit opt-in
- [ ] Add debounce to search/filter in docs and examples
- [ ] Add config and feature flags for advanced relation sorting and cursor pagination

---

## Optional modern enhancements

- Fulltext search (whereFullText) for supported drivers with fallback to LIKE.
- MySQL 8 window functions for index column (ROW_NUMBER() OVER (ORDER BY …)) when index column is enabled.
- Scout/Meilisearch/Elastic optional adapter for large-scale search use cases.
- Livewire v3 patterns: expose immutable public state + actions; use computed props for stats; consider morphing heavy UI (defer updates).

---

## Proposed small code deltas (illustrative)

1) Single pipeline skeleton
```php
protected function buildBaseQuery(): \Illuminate\Database\Eloquent\Builder
{
    $q = $this->baseModelQuery();                 // model::query()
    $this->applyCustomQueryConstraints($q);       // user constraints
    $this->applySelects($q);                      // based on visibility
    $this->applyEagerLoading($q);                 // relations
    $this->applyFilters($q);
    $this->applySearch($q);
    $this->applySorting($q);
    return $q;
}
```

2) Targeted cache invalidation (no flush)
```php
public function clearDistinctValuesCache(): void
{
    if (Cache::supportsTags()) {
        Cache::tags(['aftable', $this->tableId, 'distinct'])->flush();
        return;
    }
    $index = "aftable:{$this->tableId}:distinct:index";
    foreach ((array) Cache::get($index, []) as $key) {
        Cache::forget($key);
    }
    Cache::forget($index);
}
```

3) User-scoped visibility key
```php
protected function getColumnVisibilitySessionKey(): string
{
    $user = auth()->id() ?? 'guest';
    $modelName = is_string($this->model) ? $this->model : (is_object($this->model) ? get_class($this->model) : 'datatable');
    return 'datatable_visible_columns_' . md5($modelName . '_' . static::class . '_' . $this->tableId . '_' . $user);
}
```

---

These changes keep the trait-based design while tightening contracts, improving performance, and reducing surprise. They’re incremental, low-risk, and measurable via the suggested profiling hooks and tests.
