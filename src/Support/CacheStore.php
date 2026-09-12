<?php

namespace ArtflowStudio\Table\Support;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Predis\Client;

/**
 * Resolves which cache store the package should use, preferring Redis when the
 * application actually has a working one.
 *
 * Why this exists
 * ---------------
 * The package caches distinct filter values, column metadata and schema
 * lookups. On Redis those can be *tagged*, which means invalidation is precise
 * — `flush(['model:App\Models\Order'])` instead of clearing broadly. On file or
 * database stores tags do not exist, so the package has to be conservative.
 *
 * Rather than force Redis as a dependency, or assume it is absent, this class
 * probes once and remembers the answer:
 *
 *   1. An explicit `aftable.cache.store` always wins — no probing.
 *   2. Otherwise, if `aftable.cache.redis.autodetect` is on and the app defines
 *      a `redis` cache store whose connection answers, use it.
 *   3. Otherwise fall back to the application's default store.
 *
 * The probe result is memoised for the process AND written to the fallback
 * store for `probe_ttl` seconds, so an unreachable Redis costs one connection
 * attempt per minute rather than one per request.
 */
final class CacheStore
{
    /** Per-process memo of the resolved store name (false = not yet resolved). */
    private static string|false $resolved = false;

    /** Per-process memo of the Redis probe, so one request never probes twice. */
    private static ?bool $redisAvailable = null;

    private const PROBE_KEY = 'aftable:redis-probe';

    /**
     * The cache repository the package should use.
     */
    public static function repository(): Repository
    {
        $store = self::storeName();

        return $store === null ? Cache::store() : Cache::store($store);
    }

    /**
     * Name of the resolved store, or null for "the application default".
     */
    public static function storeName(): ?string
    {
        if (self::$resolved !== false) {
            return self::$resolved ?: null;
        }

        $configured = config('aftable.cache.store');

        if (is_string($configured) && $configured !== '') {
            return self::$resolved = $configured;
        }

        if (self::redisIsUsable()) {
            return self::$resolved = 'redis';
        }

        self::$resolved = '';

        return null;
    }

    /** True when the resolved store is Redis. */
    public static function usingRedis(): bool
    {
        $name = self::storeName() ?? config('cache.default');

        return $name === 'redis';
    }

    /**
     * Can the package tag its cache entries on the resolved store?
     *
     * Tag support is what makes precise invalidation possible; callers should
     * fall back to prefix-scoped keys when this is false.
     */
    public static function supportsTags(): bool
    {
        try {
            $store = self::repository()->getStore();
        } catch (\Throwable) {
            return false;
        }

        // ArrayStore also implements tags, which keeps tests representative.
        return $store instanceof TaggableStore
            || method_exists($store, 'tags');
    }

    /**
     * A tagged repository when the store supports tags, otherwise the plain one.
     *
     * @param  list<string>  $tags
     */
    public static function tagged(array $tags): Repository
    {
        $repository = self::repository();

        if ($tags === [] || ! self::supportsTags()) {
            return $repository;
        }

        try {
            return $repository->tags($tags);
        } catch (\Throwable) {
            return $repository;
        }
    }

    /**
     * Is Redis configured, installed AND answering?
     *
     * All three matter. `config('cache.stores.redis')` exists in a stock
     * Laravel app whether or not a Redis server is running, so a config check
     * alone would send the package to a store that throws on every call.
     */
    public static function redisIsUsable(): bool
    {
        if (self::$redisAvailable !== null) {
            return self::$redisAvailable;
        }

        if (! config('aftable.cache.redis.autodetect', true)) {
            return self::$redisAvailable = false;
        }

        // Not configured as a cache store → nothing to detect.
        if (! is_array(config('cache.stores.redis'))) {
            return self::$redisAvailable = false;
        }

        // No client library → phpredis extension or predis package.
        if (! extension_loaded('redis') && ! class_exists(Client::class)) {
            return self::$redisAvailable = false;
        }

        // Remember a recent probe so a dead server is not dialled every request.
        $cached = self::rememberedProbe();

        if ($cached !== null) {
            return self::$redisAvailable = $cached;
        }

        $available = self::pingRedis();

        self::rememberProbe($available);

        return self::$redisAvailable = $available;
    }

    /**
     * Reset the memoised state. Used by tests and after a config change.
     */
    public static function forget(): void
    {
        self::$resolved = false;
        self::$redisAvailable = null;
    }

    private static function pingRedis(): bool
    {
        try {
            $connection = config('aftable.cache.redis.connection', 'cache');

            // A trivial round-trip: cheaper and more honest than PING because it
            // exercises the same code path the cache will actually use.
            Cache::store('redis')->put(self::PROBE_KEY.':ping', 1, 5);

            return Cache::store('redis')->get(self::PROBE_KEY.':ping') == 1
                || app('redis')->connection($connection)->ping() !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function rememberedProbe(): ?bool
    {
        try {
            $value = Cache::get(self::PROBE_KEY);
        } catch (\Throwable) {
            return null;
        }

        return is_bool($value) ? $value : null;
    }

    private static function rememberProbe(bool $available): void
    {
        try {
            Cache::put(
                self::PROBE_KEY,
                $available,
                (int) config('aftable.cache.redis.probe_ttl', 60)
            );
        } catch (\Throwable) {
            // Caching the probe is an optimisation, never a requirement.
        }
    }
}
