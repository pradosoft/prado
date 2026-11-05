<?php

namespace Prado\Test\Unit\Caching;

use Prado\Caching\ICache;
use Prado\Caching\ICacheDependency;
use Prado\Caching\TCache;
use Prado\Caching\TPsrCache;
use Prado\Caching\TPsrCacheItemPool;
use Prado\Caching\TPsrSimpleCache;
use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\Prado;
use Prado\TApplication;
use Prado\TModule;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Util\Log\TLogger;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Prado\TApplicationMode;

/**
 * A PSR-16 cache module recording the TTL of each write.
 */
class TPsrCacheTestSimpleCache extends TModule implements CacheInterface
{
	public array $values = [];
	public array $ttls = [];
	public int $clears = 0;

	public function get(string $key, mixed $default = null): mixed
	{
		return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
	}

	public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
	{
		$this->values[$key] = $value;
		$this->ttls[$key] = $ttl;
		return true;
	}

	public function delete(string $key): bool
	{
		unset($this->values[$key], $this->ttls[$key]);
		return true;
	}

	public function clear(): bool
	{
		$this->clears++;
		$this->values = [];
		$this->ttls = [];
		return true;
	}

	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$values = [];
		foreach ($keys as $key) {
			$values[$key] = $this->get($key, $default);
		}
		return $values;
	}

	public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
	{
		foreach ($values as $key => $value) {
			$this->set($key, $value, $ttl);
		}
		return true;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		foreach ($keys as $key) {
			$this->delete($key);
		}
		return true;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->values);
	}
}

/**
 * A PSR-6 item recording expiresAfter().
 */
class TPsrCacheTestPoolItem implements CacheItemInterface
{
	public int|\DateInterval|null $expiresAfter = null;

	public function __construct(private string $key, private mixed $value, private bool $isHit)
	{
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function get(): mixed
	{
		return $this->value;
	}

	public function isHit(): bool
	{
		return $this->isHit;
	}

	public function set(mixed $value): static
	{
		$this->value = $value;
		return $this;
	}

	public function expiresAt(?\DateTimeInterface $expiration): static
	{
		return $this;
	}

	public function expiresAfter(int|\DateInterval|null $time): static
	{
		$this->expiresAfter = $time;
		return $this;
	}
}

/**
 * A PSR-6 pool module recording the TTL of each save.
 */
class TPsrCacheTestItemPool extends TModule implements CacheItemPoolInterface
{
	public array $values = [];
	public array $ttls = [];
	public int $clears = 0;

	public function getItem(string $key): CacheItemInterface
	{
		$hit = array_key_exists($key, $this->values);
		return new TPsrCacheTestPoolItem($key, $hit ? $this->values[$key] : null, $hit);
	}

	public function getItems(array $keys = []): iterable
	{
		$items = [];
		foreach ($keys as $key) {
			$items[$key] = $this->getItem($key);
		}
		return $items;
	}

	public function hasItem(string $key): bool
	{
		return array_key_exists($key, $this->values);
	}

	public function clear(): bool
	{
		$this->clears++;
		$this->values = [];
		$this->ttls = [];
		return true;
	}

	public function deleteItem(string $key): bool
	{
		unset($this->values[$key], $this->ttls[$key]);
		return true;
	}

	public function deleteItems(array $keys): bool
	{
		foreach ($keys as $key) {
			$this->deleteItem($key);
		}
		return true;
	}

	public function save(CacheItemInterface $item): bool
	{
		$this->values[$item->getKey()] = $item->get();
		$this->ttls[$item->getKey()] = $item instanceof TPsrCacheTestPoolItem ? $item->expiresAfter : null;
		return true;
	}

	public function saveDeferred(CacheItemInterface $item): bool
	{
		return $this->save($item);
	}

	public function commit(): bool
	{
		return true;
	}
}

/**
 * A module implementing neither PSR cache interface.
 */
class TPsrCacheTestOtherModule extends TModule
{
}

/**
 * A dependency with a settable changed state.
 */
class TPsrCacheTestDependency implements ICacheDependency
{
	public bool $changed = false;

	public function getHasChanged(): bool
	{
		return $this->changed;
	}
}

/**
 * Exposes protected seams.
 */
class TPsrCacheTestAccessor extends TPsrCache
{
	public function pubGetZappableSleepProps(): array
	{
		$exprops = [];
		$this->_getZappableSleepProps($exprops);
		return $exprops;
	}

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}
}

/**
 * Tests TPsrCache: configuration, backing resolution, the recursion guard, storage
 * through PSR-16 and PSR-6, and serialization.
 */
class TPsrCacheTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;
	private TPsrCacheTestSimpleCache $simple;
	private TPsrCacheTestItemPool $pool;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/mockapp');
		$this->app->setMode(TApplicationMode::Debug);
		$this->simple = new TPsrCacheTestSimpleCache();
		$this->pool = new TPsrCacheTestItemPool();
	}

	protected function tearDown(): void
	{
		$this->app->unlisten();
		$this->app = null;
	}

	private function newCache(CacheInterface|CacheItemPoolInterface|null $backing): TPsrCacheTestAccessor
	{
		$cache = new TPsrCacheTestAccessor();
		$cache->setPrimaryCache(false);
		if ($backing !== null) {
			$cache->setBackingCache($backing);
			$cache->init(null);
		}
		return $cache;
	}

	private function countLogs(): int
	{
		return count(Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.caching'));
	}

	// ── contract and configuration ────────────────────────────────────────────────

	public function testClassContract(): void
	{
		$cache = $this->newCache($this->simple);
		$this->assertInstanceOf(TCache::class, $cache);
		$this->assertInstanceOf(ICache::class, $cache);
		$this->assertInstanceOf(IModuleDependency::class, $cache);
		$this->assertTrue(TPsrCache::getIsAvailable());
		$fresh = new TPsrCache();
		$this->assertSame('', $fresh->getBackingCacheId());
		$this->assertNull($fresh->getModuleDependencies());
	}

	public function testInitRequiresBacking(): void
	{
		$cache = new TPsrCache();
		$cache->setPrimaryCache(false);
		try {
			$cache->init(null);
			$this->fail('A cache without a backing was initialized.');
		} catch (TConfigurationException $e) {
			$this->assertSame('psrcache_backing_cache_required', $e->getErrorCode());
		}
		$cache->setBackingCacheId('psr');
		$cache->init(null);
		$this->assertSame('psr', $cache->getBackingCacheId());
	}

	public function testGetBackingCacheWithoutConfiguration(): void
	{
		try {
			(new TPsrCache())->getBackingCache();
			$this->fail('A cache without a backing resolved one.');
		} catch (TConfigurationException $e) {
			$this->assertSame('psrcache_backing_cache_required', $e->getErrorCode());
		}
	}

	public function testBackingCacheIdResolvesModule(): void
	{
		$this->app->setModule('psr', $this->simple);
		$cache = new TPsrCache();
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('psr');
		$cache->init(null);
		$this->assertSame('psr', $cache->getModuleDependencies());
		$this->assertSame('psr', $cache->getModuleDependencies(true));
		$this->assertSame($this->simple, $cache->getBackingCache());
		$cache->set('k', 'v');
		$this->assertSame('v', $cache->get('k'));
	}

	public function testBackingCacheIdNotFound(): void
	{
		$cache = $this->newCache(null);
		$cache->setBackingCacheId('nope');
		try {
			$cache->get('k');
			$this->fail('A missing module was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('psrcache_backing_cache_not_found', $e->getErrorCode());
			$this->assertStringContainsString("'nope'", $e->getMessage());
		}
	}

	public function testBackingCacheIdInvalidType(): void
	{
		$this->app->setModule('other', new TPsrCacheTestOtherModule());
		$cache = $this->newCache(null);
		$cache->setBackingCacheId('other');
		try {
			$cache->getBackingCache();
			$this->fail('A module without a PSR cache interface was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('psrcache_invalid_backing_cache_type', $e->getErrorCode());
		}
	}

	public function testSetBackingCacheIdDropsBackingAndLogs(): void
	{
		$this->app->setModule('first', $this->simple);
		$this->app->setModule('second', $this->pool);
		$cache = $this->newCache(null);
		$before = $this->countLogs();

		$cache->setBackingCacheId('first');
		$this->assertSame($before, $this->countLogs());
		$this->assertSame($this->simple, $cache->getBackingCache());
		$cache->setBackingCacheId('first');
		$this->assertSame($before, $this->countLogs());

		$cache->setBackingCacheId('second');
		$this->assertSame($before + 1, $this->countLogs());
		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.caching');
		$message = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString("TPsrCache.BackingCacheId changed from 'first' to 'second'", $message);
		$this->assertSame($this->pool, $cache->getBackingCache());

		$cache->setBackingCache($this->simple);
		$this->assertSame($this->simple, $cache->getBackingCache());
		$cache->setBackingCache(null);
		$this->assertSame($this->pool, $cache->getBackingCache());
	}

	public function testRecursiveBackingIsRejected(): void
	{
		$cache = $this->newCache(null);
		$adapter = new TPsrSimpleCache();
		$adapter->setBackingCache($cache);
		$cache->setBackingCache($adapter);
		try {
			$cache->get('k');
			$this->fail('An adapter of the cache itself was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('psrcache_backing_cache_recursive', $e->getErrorCode());
			$this->assertStringContainsString(TPsrSimpleCache::class, $e->getMessage());
		}

		$pool = new TPsrCacheItemPool();
		$primary = new TPsrCache();
		$primary->setBackingCache($pool);
		$primary->init(null);
		$this->assertSame($primary, $this->app->getCache());
		try {
			$primary->set('k', 'v');
			$this->fail('A pool adapting the primary cache was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('psrcache_backing_cache_recursive', $e->getErrorCode());
			$this->assertStringContainsString(TPsrCacheItemPool::class, $e->getMessage());
		}
		$primary->unlisten();
	}

	// ── storage ───────────────────────────────────────────────────────────────────

	public function testPsr16Storage(): void
	{
		$cache = $this->newCache($this->simple);
		$this->assertFalse($cache->get('k'));
		$this->assertTrue($cache->set('k', 'v'));
		$this->assertSame('v', $cache->get('k'));
		$this->assertCount(1, $this->simple->values);
		$key = $cache->pubGenerateUniqueKey('k');
		$this->assertSame(['v', null], $this->simple->values[$key]);
		$this->assertNull($this->simple->ttls[$key]);
		$this->assertTrue($cache->set('k', 'v2', 30));
		$this->assertSame(30, $this->simple->ttls[$key]);
		$this->assertFalse($cache->add('k', 'v3'));
		$this->assertSame('v2', $cache->get('k'));
		$this->assertTrue($cache->add('n', 'new', 5));
		$this->assertSame('new', $cache->get('n'));
		$this->assertSame(5, $this->simple->ttls[$cache->pubGenerateUniqueKey('n')]);
		$this->assertTrue($cache->delete('k'));
		$this->assertFalse($cache->get('k'));
		$this->assertTrue($cache->flush());
		$this->assertSame(1, $this->simple->clears);
		$this->assertFalse($cache->get('n'));
	}

	public function testPsr6Storage(): void
	{
		$cache = $this->newCache($this->pool);
		$this->assertFalse($cache->get('k'));
		$this->assertTrue($cache->set('k', 'v'));
		$this->assertSame('v', $cache->get('k'));
		$this->assertCount(1, $this->pool->values);
		$key = $cache->pubGenerateUniqueKey('k');
		$this->assertSame(['v', null], $this->pool->values[$key]);
		$this->assertNull($this->pool->ttls[$key]);
		$this->assertTrue($cache->set('k', 'v2', 30));
		$this->assertSame(30, $this->pool->ttls[$key]);
		$this->assertFalse($cache->add('k', 'v3'));
		$this->assertSame('v2', $cache->get('k'));
		$this->assertTrue($cache->add('n', 'new', 5));
		$this->assertSame('new', $cache->get('n'));
		$this->assertSame(5, $this->pool->ttls[$cache->pubGenerateUniqueKey('n')]);
		$this->assertTrue($cache->delete('k'));
		$this->assertFalse($cache->get('k'));
		$this->assertTrue($cache->flush());
		$this->assertSame(1, $this->pool->clears);
		$this->assertFalse($cache->get('n'));
	}

	public function testArrayAccess(): void
	{
		$cache = $this->newCache($this->simple);
		$cache['k'] = 'v';
		$this->assertTrue(isset($cache['k']));
		$this->assertSame('v', $cache['k']);
		unset($cache['k']);
		$this->assertFalse(isset($cache['k']));
	}

	public function testDependency(): void
	{
		$cache = $this->newCache($this->pool);
		$dependency = new TPsrCacheTestDependency();
		$this->assertTrue($cache->set('k', 'v', 0, $dependency));
		$this->assertSame('v', $cache->get('k'));
		$dependency->changed = true;
		$this->assertFalse($cache->get('k'));
	}

	public function testEmptyValueDeletes(): void
	{
		$cache = $this->newCache($this->simple);
		$cache->set('k', 'v');
		$this->assertTrue($cache->set('k', ''));
		$this->assertSame([], $this->simple->values);
	}

	public function testKeyPrefixIsolation(): void
	{
		$a = $this->newCache($this->simple);
		$a->setKeyPrefix('a');
		$b = $this->newCache($this->simple);
		$b->setKeyPrefix('b');
		$a->set('k', 'from a');
		$this->assertSame('from a', $a->get('k'));
		$this->assertFalse($b->get('k'));
		$this->assertCount(1, $this->simple->values);
		$this->assertSame(40, strlen(array_key_first($this->simple->values)));
	}

	public function testRoundTripThroughAdapters(): void
	{
		$store = new TTestArrayCache();
		$adapter = new TPsrSimpleCache();
		$adapter->setBackingCache($store);
		$adapter->setKeyPrefix('x.');
		$cache = $this->newCache($adapter);
		$this->assertTrue($cache->set('k', ['v'], 10));
		$this->assertSame(['v'], $cache->get('k'));
		$this->assertCount(1, $store->values);
		$this->assertStringStartsWith('x.', array_key_first($store->values));
		$this->assertSame(10, reset($store->expires));
		$this->assertTrue($cache->flush());
		$this->assertSame([], $store->values);

		$pool = new TPsrCacheItemPool();
		$pool->setBackingCache($store);
		$cache = $this->newCache($pool);
		$this->assertTrue($cache->set('k', 'v'));
		$this->assertSame('v', $cache->get('k'));
		$this->assertFalse($cache->add('k', 'other'));
		$this->assertTrue($cache->delete('k'));
		$this->assertFalse($cache->get('k'));
	}

	// ── serialization ─────────────────────────────────────────────────────────────

	public function testSleepProps(): void
	{
		$cache = new TPsrCacheTestAccessor();
		$class = TPsrCache::class;
		$props = $cache->pubGetZappableSleepProps();
		$this->assertContains("\0{$class}\0_backingCacheId", $props);
		$this->assertNotContains("\0{$class}\0_backingCache", $props);
		$cache->setBackingCacheId('psr');
		$props = $cache->pubGetZappableSleepProps();
		$this->assertNotContains("\0{$class}\0_backingCacheId", $props);
		$this->assertContains("\0{$class}\0_backingCache", $props);
	}

	public function testSerializationRoundTrip(): void
	{
		$this->app->setModule('psr', $this->simple);
		$cache = new TPsrCache();
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('psr');
		$cache->init(null);
		$cache->getBackingCache();
		$copy = unserialize(serialize($cache));
		$this->assertSame('psr', $copy->getBackingCacheId());
		$this->assertSame($this->simple, $copy->getBackingCache());
	}
}
