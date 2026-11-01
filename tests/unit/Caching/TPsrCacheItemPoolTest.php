<?php

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TPsrCacheException;
use Prado\Caching\TPsrCacheInvalidArgumentException;
use Prado\Caching\TPsrCacheItem;
use Prado\Caching\TPsrCacheItemPool;
use Prado\IModuleDependency;
use Prado\TApplication;
use Prado\TModule;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\Harness\Caching\TTestCache;
use Prado\Test\Unit\Harness\Caching\TTestNoFlushCache;
use Prado\Util\Clock\TMockClock;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Prado\TApplicationMode;

/**
 * An item of another PSR-6 implementation.
 */
class TPsrCacheItemPoolTestForeignItem implements CacheItemInterface
{
	public function __construct(private string $key, private mixed $value)
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
		return false;
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
		return $this;
	}
}

/**
 * An ICache whose writes can be made to fail.
 */
class TPsrCacheItemPoolTestFailingCache extends TTestArrayCache
{
	public bool $fail = false;

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		return $this->fail ? false : parent::set($id, $value, $expire, $dependency);
	}
}

/**
 * Exposes the serialization seam.
 */
class TPsrCacheItemPoolTestAccessor extends TPsrCacheItemPool
{
	public function pubGetZappableSleepProps(): array
	{
		$exprops = [];
		$this->_getZappableSleepProps($exprops);
		return $exprops;
	}
}

/**
 * Tests TPsrCacheItemPool: items, expiration, deferred items, and backing resolution.
 */
class TPsrCacheItemPoolTest extends \PHPUnit\Framework\TestCase
{
	private const NOW = 1700000000;

	private ?TApplication $app = null;
	private TTestArrayCache $backing;
	private TPsrCacheItemPool $pool;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/mockapp');
		$this->app->setMode(TApplicationMode::Debug);
		$this->backing = new TTestArrayCache();
		$this->pool = new TPsrCacheItemPool();
		$this->pool->setBackingCache($this->backing);
		$this->pool->setClock((new TMockClock())->setNow(self::NOW));
	}

	protected function tearDown(): void
	{
		$this->pool->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── contract ──────────────────────────────────────────────────────────────────

	public function testClassContract(): void
	{
		$this->assertInstanceOf(CacheItemPoolInterface::class, $this->pool);
		$this->assertInstanceOf(IModuleDependency::class, $this->pool);
		$this->assertInstanceOf(TModule::class, $this->pool);
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertNull($this->pool->getModuleDependencies());
	}

	// ── items ─────────────────────────────────────────────────────────────────────

	public function testGetItemMiss(): void
	{
		$item = $this->pool->getItem('key');
		$this->assertInstanceOf(TPsrCacheItem::class, $item);
		$this->assertSame('key', $item->getKey());
		$this->assertFalse($item->isHit());
		$this->assertNull($item->get());
		$this->assertSame($this->pool->getClock(), $item->getClock());
		$this->assertFalse($this->pool->hasItem('key'));
	}

	public function testSaveAndGetItemHit(): void
	{
		$item = $this->pool->getItem('key')->set('value');
		$this->assertTrue($this->pool->save($item));
		$this->assertSame(['value'], $this->backing->values['key']);
		$this->assertSame(0, $this->backing->expires['key']);
		$this->assertTrue($this->pool->hasItem('key'));
		$hit = $this->pool->getItem('key');
		$this->assertTrue($hit->isHit());
		$this->assertSame('value', $hit->get());
		$this->assertNotSame($item, $hit);
	}

	public function testEmptyValuesAreHits(): void
	{
		foreach ([null, false, 0, '', []] as $i => $value) {
			$key = 'empty' . $i;
			$this->pool->save($this->pool->getItem($key)->set($value));
			$item = $this->pool->getItem($key);
			$this->assertTrue($item->isHit(), $key);
			$this->assertSame($value, $item->get(), $key);
		}
	}

	public function testGetItems(): void
	{
		$this->pool->save($this->pool->getItem('a')->set(1));
		$items = $this->pool->getItems(['a', 'b']);
		$this->assertSame(['a', 'b'], array_keys($items));
		$this->assertTrue($items['a']->isHit());
		$this->assertSame(1, $items['a']->get());
		$this->assertFalse($items['b']->isHit());
		$this->assertSame('b', $items['b']->getKey());
		$this->assertSame([], $this->pool->getItems());
	}

	public function testDeleteItem(): void
	{
		$this->pool->save($this->pool->getItem('a')->set(1));
		$this->assertTrue($this->pool->deleteItem('a'));
		$this->assertFalse($this->pool->hasItem('a'));
		$this->assertTrue($this->pool->deleteItem('a'));
	}

	public function testDeleteItems(): void
	{
		$this->pool->save($this->pool->getItem('a')->set(1));
		$this->pool->save($this->pool->getItem('b')->set(2));
		$this->assertTrue($this->pool->deleteItems(['a', 'missing']));
		$this->assertFalse($this->pool->hasItem('a'));
		$this->assertTrue($this->pool->hasItem('b'));
		try {
			$this->pool->deleteItems(['b', 'bad:key']);
			$this->fail('An illegal key was accepted.');
		} catch (TPsrCacheInvalidArgumentException $e) {
			$this->assertTrue($this->pool->hasItem('b'));
		}
	}

	public function testClear(): void
	{
		$this->pool->save($this->pool->getItem('a')->set(1));
		$this->pool->saveDeferred(new TPsrCacheItem('b', 2));
		$this->assertTrue($this->pool->clear());
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertSame([], $this->backing->values);
	}

	public function testClearWithoutFlushSupportReturnsFalse(): void
	{
		$backing = new TTestNoFlushCache();
		$backing->setPrimaryCache(false);
		$backing->init(null);
		$this->pool->setBackingCache($backing);
		$this->pool->save($this->pool->getItem('a')->set(1));
		$this->assertFalse($this->pool->clear());
		$this->assertTrue($this->pool->hasItem('a'));
		$backing->unlisten();
	}

	// ── expiration ────────────────────────────────────────────────────────────────

	public function testSaveUsesDefaultExpire(): void
	{
		$this->pool->setDefaultExpire(120);
		$this->pool->save($this->pool->getItem('key')->set('v'));
		$this->assertSame(120, $this->backing->expires['key']);
	}

	public function testSaveExpiresAfter(): void
	{
		$this->pool->save($this->pool->getItem('key')->set('v')->expiresAfter(60));
		$this->assertSame(60, $this->backing->expires['key']);
		$this->pool->save($this->pool->getItem('key')->set('v')->expiresAfter(new \DateInterval('PT2M')));
		$this->assertSame(120, $this->backing->expires['key']);
	}

	public function testSaveExpiresAtRoundsUp(): void
	{
		$item = $this->pool->getItem('key')->set('v')->expiresAt(new \DateTimeImmutable('@' . (self::NOW + 90) . '.5'));
		$this->assertTrue($this->pool->save($item));
		$this->assertSame(91, $this->backing->expires['key']);
	}

	public function testSaveExpiredItemDeletes(): void
	{
		$this->pool->save($this->pool->getItem('key')->set('old'));
		$this->assertTrue($this->pool->save($this->pool->getItem('key')->set('new')->expiresAfter(0)));
		$this->assertFalse($this->pool->hasItem('key'));
		$this->pool->save($this->pool->getItem('key')->set('old'));
		$this->assertTrue($this->pool->save($this->pool->getItem('key')->expiresAt(new \DateTimeImmutable('@' . (self::NOW - 1)))));
		$this->assertFalse($this->pool->hasItem('key'));
	}

	public function testExpiryAgainstBackingClock(): void
	{
		$backing = new TTestCache();
		$backing->setPrimaryCache(false);
		$backing->init(null);
		$backing->setFakeNow(self::NOW);
		$this->pool->setBackingCache($backing);
		$this->pool->save($this->pool->getItem('key')->set('v')->expiresAfter(10));
		$this->assertTrue($this->pool->getItem('key')->isHit());
		$backing->setFakeNow(self::NOW + 10);
		$this->assertFalse($this->pool->getItem('key')->isHit());
		$this->assertFalse($this->pool->hasItem('key'));
		$backing->unlisten();
	}

	public function testSaveForeignItem(): void
	{
		$this->pool->setDefaultExpire(30);
		$this->assertTrue($this->pool->save(new TPsrCacheItemPoolTestForeignItem('foreign', 'v')));
		$this->assertSame(30, $this->backing->expires['foreign']);
		$this->assertSame('v', $this->pool->getItem('foreign')->get());
	}

	// ── deferred ──────────────────────────────────────────────────────────────────

	public function testSaveDeferredAndCommit(): void
	{
		$item = $this->pool->getItem('a')->set(1);
		$this->assertTrue($this->pool->saveDeferred($item));
		$this->assertSame(['a' => $item], $this->pool->getDeferredItems());
		$this->assertArrayNotHasKey('a', $this->backing->values);
		$replacement = new TPsrCacheItem('a', 2);
		$this->assertTrue($this->pool->saveDeferred($replacement));
		$this->assertSame(['a' => $replacement], $this->pool->getDeferredItems());
		$this->assertTrue($this->pool->commit());
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertSame([2], $this->backing->values['a']);
		$this->assertTrue($this->pool->commit());
	}

	public function testReadsCommitDeferred(): void
	{
		$this->pool->saveDeferred(new TPsrCacheItem('a', 1));
		$this->assertTrue($this->pool->hasItem('a'));
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->pool->saveDeferred(new TPsrCacheItem('b', 2));
		$this->assertSame(2, $this->pool->getItem('b')->get());
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->pool->saveDeferred(new TPsrCacheItem('c', 3));
		$this->assertSame(3, $this->pool->getItems(['c'])['c']->get());
		$this->assertSame([], $this->pool->getDeferredItems());
	}

	public function testDeleteDropsDeferred(): void
	{
		$this->pool->saveDeferred(new TPsrCacheItem('a', 1));
		$this->pool->saveDeferred(new TPsrCacheItem('b', 2));
		$this->assertTrue($this->pool->deleteItem('a'));
		$this->assertSame(['b'], array_keys($this->pool->getDeferredItems()));
		$this->assertTrue($this->pool->deleteItems(['b']));
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertSame([], $this->backing->values);
	}

	public function testCommitReportsFailure(): void
	{
		$backing = new TPsrCacheItemPoolTestFailingCache();
		$this->pool->setBackingCache($backing);
		$this->pool->saveDeferred(new TPsrCacheItem('a', 1));
		$this->pool->saveDeferred(new TPsrCacheItem('b', 2));
		$backing->fail = true;
		$this->assertFalse($this->pool->commit());
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertFalse($this->pool->save(new TPsrCacheItem('c', 3)));
		$backing->fail = false;
		$this->assertTrue($this->pool->save(new TPsrCacheItem('c', 3)));
	}

	public function testEndRequestCommits(): void
	{
		$this->pool->init(null);
		$this->pool->saveDeferred(new TPsrCacheItem('a', 1));
		$this->app->raiseEvent('OnEndRequest', $this->app, null);
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertSame([1], $this->backing->values['a']);
	}

	public function testDestructCommits(): void
	{
		$pool = new TPsrCacheItemPool();
		$pool->setBackingCache($this->backing);
		$pool->saveDeferred(new TPsrCacheItem('a', 1));
		$pool = null;
		$this->assertSame([1], $this->backing->values['a']);
	}

	public function testDestructDiscardsFailure(): void
	{
		$this->expectNotToPerformAssertions();
		$pool = new TPsrCacheItemPool();
		$pool->setBackingCacheId('missing');
		$pool->saveDeferred(new TPsrCacheItem('a', 1));
		$pool = null;
	}

	// ── keys ──────────────────────────────────────────────────────────────────────

	public function testInvalidKeys(): void
	{
		foreach (['', 'a{b', 'a:b'] as $key) {
			foreach (['getItem', 'hasItem', 'deleteItem'] as $method) {
				try {
					$this->pool->$method($key);
					$this->fail("{$method}() accepted the key '{$key}'.");
				} catch (TPsrCacheInvalidArgumentException $e) {
					$this->assertInstanceOf(\Psr\Cache\InvalidArgumentException::class, $e);
					$this->assertSame('psrcachebacking_key_invalid', $e->getErrorCode());
				}
			}
		}
		foreach (['save', 'saveDeferred'] as $method) {
			try {
				$this->pool->$method(new TPsrCacheItem('a:b', 1));
				$this->fail("{$method}() accepted an illegal item key.");
			} catch (TPsrCacheInvalidArgumentException $e) {
				$this->assertSame('psrcachebacking_key_invalid', $e->getErrorCode());
			}
		}
		try {
			$this->pool->getItems(['ok', 'a:b']);
			$this->fail('getItems() accepted an illegal key.');
		} catch (TPsrCacheInvalidArgumentException $e) {
			$this->assertSame('psrcachebacking_key_invalid', $e->getErrorCode());
		}
		$this->assertSame([], $this->pool->getDeferredItems());
		$this->assertSame([], $this->backing->values);
	}

	// ── backing resolution ────────────────────────────────────────────────────────

	public function testBackingResolution(): void
	{
		$backing = new TTestCache();
		$backing->setPrimaryCache(false);
		$backing->init(null);
		$this->app->setModule('backingCache', $backing);
		$pool = new TPsrCacheItemPool();
		$pool->setBackingCacheId('backingCache');
		$this->assertSame('backingCache', $pool->getModuleDependencies());
		$this->assertSame($backing, $pool->getBackingCache());
		$pool->save($pool->getItem('k')->set('v'));
		$this->assertSame('v', $pool->getItem('k')->get());

		$pool->setBackingCacheId('missing');
		try {
			$pool->getItem('k');
			$this->fail('A missing module was accepted.');
		} catch (TPsrCacheException $e) {
			$this->assertInstanceOf(\Psr\Cache\CacheException::class, $e);
			$this->assertSame('psrcachebacking_cache_not_found', $e->getErrorCode());
		}
		$backing->unlisten();
	}

	// ── serialization ─────────────────────────────────────────────────────────────

	public function testSleepProps(): void
	{
		$pool = new TPsrCacheItemPoolTestAccessor();
		$pool->setBackingCache(new TTestArrayCache());
		$class = TPsrCacheItemPool::class;
		$props = $pool->pubGetZappableSleepProps();
		$this->assertContains("\0{$class}\0_deferred", $props);
		$this->assertContains("\0{$class}\0_backingCacheId", $props);
		$this->assertNotContains("\0{$class}\0_backingCache", $props);
		$pool->saveDeferred(new TPsrCacheItem('a', 1));
		$this->assertNotContains("\0{$class}\0_deferred", $pool->pubGetZappableSleepProps());
		$pool->commit();
		$this->assertContains("\0{$class}\0_deferred", $pool->pubGetZappableSleepProps());
	}
}
