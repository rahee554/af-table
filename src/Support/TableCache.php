<?php

namespace ArtflowStudio\Table\Support;

/**
 * The package's cache front door.
 *
 * Every cached value the package writes goes through here, which buys three
 * things the old ad-hoc `Cache::remember()` calls did not have:
 *
 *  - **One key namespace.** Everything is prefixed with `aftable.cache.prefix`,
 *    so clearing is scoped and can never touch host-application entries.
 *  - **Tags when the store supports them.** On Redis (auto-detected, see
 *    {@see CacheStore}) entries are tagged by model and table, so invalidating
 *    one model's distinct values does not disturb another's.
 *  - **A single kill switch.** `aftable.cache.enabled=false` makes every call
 *    a straight pass-through, which is what you want while debugging a
 *    stale-data report.
 */
final class TableCache
{
    /** @var array<string, mixed> Per-request memo, in front of the cache store. */
    private static array $memo = [];

    /**
     * Remember a value.
     *
     * @param  string  $kind  A TTL bucket from `aftable.cache.ttl` (e.g. 'distinct_values').
     * @param  list<string>  $tags  Extra tags for precise invalidation, used only on tagging stores.
     */
    public static function remember(string $key, string $kind, callable $callback, array $tags = []): mixed
    {
        if (! self::enabled()) {
            return $callback();
        }

        $key = self::key($key);

        // Within a single request the same distinct-value list is often asked
        // for several times (once per filter dropdown render). Serve those from
        // memory rather than paying a cache round-trip each time.
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        $ttl = self::ttl($kind);

        try {
            $value = CacheStore::tagged(self::tags($tags))->remember($key, $ttl, $callback);
        } catch (\Throwable) {
            // A cache outage must never take the table down.
            $value = $callback();
        }

        return self::$memo[$key] = $value;
    }

    /** Forget one key. */
    public static function forget(string $key, array $tags = []): void
    {
        $key = self::key($key);
        unset(self::$memo[$key]);

        try {
            CacheStore::tagged(self::tags($tags))->forget($key);
        } catch (\Throwable) {
            // nothing to do
        }
    }

    /**
     * Drop everything the package cached.
     *
     * On a tagging store this is exact. Elsewhere there is no portable way to
     * enumerate keys by prefix, so the caller is told nothing could be done
     * rather than being silently lied to — see ClearPhantomColumnsCommand.
     */
    public static function flush(array $tags = []): bool
    {
        self::$memo = [];

        if (! CacheStore::supportsTags()) {
            return false;
        }

        try {
            CacheStore::tagged(self::tags($tags))->flush();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Clear only the per-request memo (used between Livewire updates). */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    /** Build a namespaced key. */
    public static function key(string $key): string
    {
        $prefix = (string) config('aftable.cache.prefix', 'aftable');

        return str_starts_with($key, $prefix.':') ? $key : $prefix.':'.$key;
    }

    public static function enabled(): bool
    {
        return (bool) config('aftable.cache.enabled', true);
    }

    public static function ttl(string $kind): int
    {
        $ttls = (array) config('aftable.cache.ttl', []);

        return (int) ($ttls[$kind] ?? $ttls['default'] ?? 300);
    }

    /** Always include the package tag so a global flush is possible. */
    private static function tags(array $tags): array
    {
        return array_values(array_unique(array_merge(
            [(string) config('aftable.cache.prefix', 'aftable')],
            array_filter($tags)
        )));
    }

    /**
     * A short, stable description of the active cache backend — surfaced in the
     * debug panel and the test UI so "is Redis actually on?" is answerable.
     *
     * @return array{store: string, redis: bool, tags: bool, enabled: bool}
     */
    public static function describe(): array
    {
        return [
            'store' => CacheStore::storeName() ?? (string) config('cache.default'),
            'redis' => CacheStore::usingRedis(),
            'tags' => CacheStore::supportsTags(),
            'enabled' => self::enabled(),
        ];
    }
}
