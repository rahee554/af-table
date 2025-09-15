<?php

namespace ArtflowStudio\Table\Traits;

trait HasTargetedCaching
{
    /**
     * Cache configuration for the datatable
     */
    protected array $cacheConfig = [
        'default_ttl' => 300, // 5 minutes
        'tags_enabled' => null, // Will be auto-detected
        'prefix' => 'datatable',
    ];

    /**
     * Get the cache tags for this datatable instance
     */
    protected function getCacheTags(): array
    {
        $modelName = str_replace('\\', '_', $this->model);
        return [
            "datatable:{$this->tableId}",
            "model:{$modelName}",
            "table_cache"
        ];
    }

    /**
     * Get cache key with proper prefixing
     */
    protected function getCacheKey(string $suffix): string
    {
        return "{$this->cacheConfig['prefix']}:{$this->tableId}:{$suffix}";
    }

    /**
     * Store data in cache with tags
     */
    protected function cacheRemember(string $key, $ttl, callable $callback)
    {
        $fullKey = $this->getCacheKey($key);
        
        if ($this->cacheSupportsTagging()) {
            return \Illuminate\Support\Facades\Cache::tags($this->getCacheTags())->remember($fullKey, $ttl, $callback);
        }
        
        return \Illuminate\Support\Facades\Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Get data from cache with tags
     */
    protected function cacheGet(string $key, $default = null)
    {
        $fullKey = $this->getCacheKey($key);
        
        if ($this->cacheSupportsTagging()) {
            return \Illuminate\Support\Facades\Cache::tags($this->getCacheTags())->get($fullKey, $default);
        }
        
        return \Illuminate\Support\Facades\Cache::get($fullKey, $default);
    }

    /**
     * Store data in cache with tags
     */
    protected function cachePut(string $key, $value, $ttl = null)
    {
        $fullKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->cacheConfig['default_ttl'];
        
        if ($this->cacheSupportsTagging()) {
            return \Illuminate\Support\Facades\Cache::tags($this->getCacheTags())->put($fullKey, $value, $ttl);
        }
        
        return \Illuminate\Support\Facades\Cache::put($fullKey, $value, $ttl);
    }

    /**
     * Remove specific cache key
     */
    protected function cacheForget(string $key): bool
    {
        $fullKey = $this->getCacheKey($key);
        
        if ($this->cacheSupportsTagging()) {
            return \Illuminate\Support\Facades\Cache::tags($this->getCacheTags())->forget($fullKey);
        }
        
        return \Illuminate\Support\Facades\Cache::forget($fullKey);
    }

    /**
     * Clear all cache for this datatable instance
     */
    protected function clearDatatableCache(): bool
    {
        try {
            if ($this->cacheSupportsTagging()) {
                // Use cache tags to clear only this datatable's cache
                \Illuminate\Support\Facades\Cache::tags($this->getCacheTags())->flush();
                return true;
            }
            
            // Fallback: Clear cache by pattern for drivers that don't support tagging
            return $this->clearCacheByPattern($this->getCacheKey('*'));
            
        } catch (\Exception $e) {
            // Log silently to avoid dependency issues
            if (function_exists('logger')) {
                logger('Datatable cache clearing failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Clear cache by pattern (fallback for non-tagging drivers)
     */
    protected function clearCacheByPattern(string $pattern): bool
    {
        try {
            $driver = config('cache.default');

            switch ($driver) {
                case 'redis':
                    return $this->clearRedisPatternCache($pattern);
                
                case 'file':
                    return $this->clearFilePatternCache($pattern);
                
                case 'database':
                    return $this->clearDatabasePatternCache($pattern);
                
                case 'array':
                case 'null':
                    // For testing environments, clear all is acceptable
                    \Illuminate\Support\Facades\Cache::flush();
                    return true;
                
                default:
                    // For other drivers, skip pattern clearing
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear Redis cache by pattern
     */
    protected function clearRedisPatternCache(string $pattern): bool
    {
        try {
            $cache = \Illuminate\Support\Facades\Cache::getStore();
            $redis = $cache->getRedis();
            $prefix = $cache->getPrefix();
            $fullPattern = $prefix . str_replace('*', '*', $pattern);
            
            $keys = $redis->keys($fullPattern);
            
            if (!empty($keys)) {
                // Remove prefix from keys for deletion
                $keysToDelete = array_map(function($key) use ($prefix) {
                    return str_replace($prefix, '', $key);
                }, $keys);
                
                \Illuminate\Support\Facades\Cache::deleteMultiple($keysToDelete);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear file cache by pattern
     */
    protected function clearFilePatternCache(string $pattern): bool
    {
        try {
            $cacheDirectory = storage_path('framework/cache/data');
            
            if (!is_dir($cacheDirectory)) {
                return true; // No cache directory means nothing to clear
            }
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDirectory)
            );
            
            $pattern = str_replace(['*', ':'], ['.*', '\:'], $pattern);
            $deleted = 0;
            
            foreach ($iterator as $file) {
                if ($file->isFile() && preg_match("/{$pattern}/", $file->getFilename())) {
                    unlink($file->getPathname());
                    $deleted++;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear database cache by pattern
     */
    protected function clearDatabasePatternCache(string $pattern): bool
    {
        try {
            $table = config('cache.stores.database.table', 'cache');
            $pattern = str_replace('*', '%', $pattern);
            
            \Illuminate\Support\Facades\DB::table($table)
                ->where('key', 'LIKE', $pattern)
                ->delete();
                
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the current cache driver supports tagging
     */
    protected function cacheSupportsTagging(): bool
    {
        if ($this->cacheConfig['tags_enabled'] !== null) {
            return $this->cacheConfig['tags_enabled'];
        }

        $driver = config('cache.default');
        $store = \Illuminate\Support\Facades\Cache::getStore();
        
        // Cache the result for this request
        $this->cacheConfig['tags_enabled'] = in_array($driver, ['redis', 'memcached']) && 
                                           method_exists($store, 'tags');
        
        return $this->cacheConfig['tags_enabled'];
    }

    /**
     * Clear specific cache patterns for this datatable
     */
    protected function clearSpecificCache(array $patterns): bool
    {
        $cleared = true;
        
        foreach ($patterns as $pattern) {
            if ($this->cacheSupportsTagging()) {
                // When using tags, we can be more selective
                $this->cacheForget($pattern);
            } else {
                // Use pattern clearing
                $cleared = $this->clearCacheByPattern($this->getCacheKey($pattern)) && $cleared;
            }
        }
        
        return $cleared;
    }

    /**
     * Get cache statistics for this datatable
     */
    public function getCacheStatistics(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'supports_tagging' => $this->cacheSupportsTagging(),
            'table_id' => $this->tableId,
            'cache_prefix' => $this->cacheConfig['prefix'],
            'tags' => $this->getCacheTags(),
        ];

        if ($this->cacheSupportsTagging()) {
            $stats['tagged_cache'] = true;
        } else {
            $stats['pattern_based'] = true;
        }

        return $stats;
    }

    /**
     * Configure caching behavior
     */
    public function configureCaching(array $config): void
    {
        $this->cacheConfig = array_merge($this->cacheConfig, $config);
    }

    /**
     * Force cache refresh for specific key
     */
    protected function refreshCache(string $key, callable $callback, $ttl = null): mixed
    {
        $this->cacheForget($key);
        return $this->cacheRemember($key, $ttl ?? $this->cacheConfig['default_ttl'], $callback);
    }
}
