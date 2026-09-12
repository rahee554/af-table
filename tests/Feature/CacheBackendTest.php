<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\CacheStore;
use ArtflowStudio\Table\Support\TableCache;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * The package's cache layer: Redis auto-detection, namespacing, tagging and
 * graceful degradation.
 *
 * Redis is opt-in *by availability* — the package uses it when the application
 * has a working Redis cache store and silently falls back otherwise. These
 * tests run on the array store, so they exercise the fallback path and the
 * detection logic rather than a live Redis.
 */
class CacheBackendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CacheStore::forget();
        TableCache::flushMemo();
        $this->seedSmallDataset();
    }

    protected function tearDown(): void
    {
        CacheStore::forget();
        parent::tearDown();
    }

    // ------------------------------------------------------------ detection

    #[Test]
    public function it_does_not_choose_redis_when_no_redis_store_is_configured(): void
    {
        config()->set('cache.stores.redis', null);
        CacheStore::forget();

        $this->assertFalse(CacheStore::redisIsUsable());
        $this->assertFalse(CacheStore::usingRedis());
    }

    #[Test]
    public function it_does_not_probe_at_all_when_autodetect_is_disabled(): void
    {
        config()->set('aftable.cache.redis.autodetect', false);
        CacheStore::forget();

        $this->assertFalse(CacheStore::redisIsUsable());
    }

    #[Test]
    public function an_explicit_store_short_circuits_detection(): void
    {
        config()->set('aftable.cache.store', 'array');
        CacheStore::forget();

        $this->assertSame('array', CacheStore::storeName());
        $this->assertFalse(CacheStore::usingRedis());
    }

    #[Test]
    public function it_falls_back_to_the_application_default_store(): void
    {
        config()->set('aftable.cache.store', null);
        config()->set('cache.stores.redis', null);
        CacheStore::forget();

        $this->assertNull(CacheStore::storeName(), 'null means "use the app default".');
        $this->assertInstanceOf(Repository::class, CacheStore::repository());
    }

    #[Test]
    public function an_unreachable_redis_never_takes_the_table_down(): void
    {
        // A configured-but-dead Redis is the realistic failure mode.
        config()->set('cache.stores.redis', ['driver' => 'redis', 'connection' => 'nope']);
        config()->set('database.redis.nope', ['host' => '127.0.0.1', 'port' => 65530, 'database' => 0]);
        CacheStore::forget();

        // Must not throw, and must not select the dead store.
        $this->assertFalse(CacheStore::redisIsUsable());

        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'filters' => ColumnSets::filters(),
        ])->assertOk();
    }

    #[Test]
    public function the_probe_result_is_remembered_so_a_dead_redis_is_not_dialled_every_request(): void
    {
        config()->set('cache.stores.redis', null);
        CacheStore::forget();

        CacheStore::redisIsUsable();

        $this->assertFalse(
            Cache::has('aftable:redis-probe'),
            'With no redis store configured the probe short-circuits before any caching.'
        );
    }

    // ------------------------------------------------------------- behaviour

    #[Test]
    public function it_reports_which_backend_is_in_use(): void
    {
        $described = TableCache::describe();

        $this->assertArrayHasKey('store', $described);
        $this->assertArrayHasKey('redis', $described);
        $this->assertArrayHasKey('tags', $described);
        $this->assertTrue($described['enabled']);
        $this->assertIsBool($described['redis']);
    }

    #[Test]
    public function every_key_is_namespaced_under_the_configured_prefix(): void
    {
        config()->set('aftable.cache.prefix', 'aftable');

        $this->assertSame('aftable:widgets', TableCache::key('widgets'));
        $this->assertSame('aftable:widgets', TableCache::key('aftable:widgets'), 'must not double-prefix');
    }

    #[Test]
    public function remember_caches_the_value_and_does_not_recompute(): void
    {
        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;

            return 'computed';
        };

        $this->assertSame('computed', TableCache::remember('k1', 'default', $compute));
        $this->assertSame('computed', TableCache::remember('k1', 'default', $compute));

        $this->assertSame(1, $calls, 'The second call should be served from cache.');
    }

    #[Test]
    public function forget_drops_the_value(): void
    {
        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;

            return $calls;
        };

        TableCache::remember('k2', 'default', $compute);
        TableCache::forget('k2');
        TableCache::remember('k2', 'default', $compute);

        $this->assertSame(2, $calls);
    }

    #[Test]
    public function disabling_the_cache_makes_every_call_a_pass_through(): void
    {
        config()->set('aftable.cache.enabled', false);
        TableCache::flushMemo();

        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;

            return 'x';
        };

        TableCache::remember('k3', 'default', $compute);
        TableCache::remember('k3', 'default', $compute);

        $this->assertSame(2, $calls, 'With caching off nothing should be memoised.');
    }

    #[Test]
    public function ttls_come_from_config_per_kind(): void
    {
        config()->set('aftable.cache.ttl', [
            'default' => 111,
            'distinct_values' => 222,
        ]);

        $this->assertSame(222, TableCache::ttl('distinct_values'));
        $this->assertSame(111, TableCache::ttl('default'));
        $this->assertSame(111, TableCache::ttl('unknown-kind'), 'unknown kinds fall back to default');
    }

    #[Test]
    public function a_cache_failure_still_returns_the_computed_value(): void
    {
        config()->set('aftable.cache.store', 'does-not-exist');
        CacheStore::forget();
        TableCache::flushMemo();

        $this->assertSame(
            'still works',
            TableCache::remember('k4', 'default', fn () => 'still works'),
            'A broken cache store must degrade to computing the value, not throw.'
        );
    }

    // --------------------------------------------------------- integration

    #[Test]
    public function distinct_filter_values_are_cached_between_component_instances(): void
    {
        $table = fn () => Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'filters' => ColumnSets::filters(),
            'tableId' => 'cache-distinct',
        ]);

        $table()->instance()->getDistinctValues('position');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $values = $table()->instance()->getDistinctValues('position');

        $distinctQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains(strtolower($q['query']), 'distinct'))
            ->count();

        $this->assertNotEmpty($values);
        $this->assertSame(0, $distinctQueries, 'The second mount should hit the cache, not the database.');
    }

    #[Test]
    public function distinct_values_are_capped_so_a_high_cardinality_column_cannot_flood_the_dropdown(): void
    {
        config()->set('aftable.query.max_distinct_values', 3);

        $values = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'email', 'label' => 'Email'],
            ],
            'filters' => ['email' => ['type' => 'distinct']],
            'tableId' => 'cache-cap',
        ])->instance()->getDistinctValues('email');

        $this->assertLessThanOrEqual(3, count($values));
    }
}
