<?php

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TPsrCacheBackingTrait;
use Prado\Caching\TPsrCacheException;
use Prado\Caching\TPsrCacheInvalidArgumentException;
use Prado\Caching\TPsrSimpleCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IModuleDependency;
use Prado\Prado;
use Prado\TApplication;
use Prado\TModule;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\Harness\Caching\TTestCache;
use Prado\Test\Unit\Harness\Caching\TTestNoFlushCache;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Log\TLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * A module that is not an ICache, for the invalid-type guard.
 */
class TPsrSimpleCacheTestModule extends TModule
{
}

/**
 * An ICache whose writes and deletes can be made to fail.
 */
class TPsrSimpleCacheTestFailingCache extends TTestArrayCache
{
	public bool $fail = false;

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		return $this->fail ? false : parent::set($id, $value, $expire, $dependency);
	}

	public function delete($id)
	{
		return $this->fail ? false : parent::delete($id);
	}
}

/**
 * Exposes the serialization seam.
 */
class TPsrSimpleCacheTestAccessor extends TPsrSimpleCache
{
	public function pubGetZappableSleepProps(): array
	{
		$exprops = [];
		$this->_getZappableSleepProps($exprops);
		return $exprops;
	}
}

/**
 * Tests TPsrSimpleCache and, through it, TPsrCacheBackingTrait: PSR-16 operations,
 * empty values, TTLs, keys, the key prefix, backing resolution, and serialization.
 */
class TPsrSimpleCacheTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;
	private TTestCache $backing;
	private TPsrSimpleCache $psr;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/mockapp');
		$this->backing = new TTestCache();
		$this->backing->setPrimaryCache(false);
		$this->backing->init(null);
		$this->psr = new TPsrSimpleCache();
		$this->psr->setBackingCache($this->backing);
	}

	protected function tearDown(): void
	{
		$this->psr->unlisten();
		$this->backing->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	private function countLogs(): int
	{
		return count(Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.caching'));
	}

	private function newBacking(): TTestCache
	{
		$backing = new TTestCache();
		$backing->setPrimaryCache(false);
		$backing->init(null);
		return $backing;
	}

	// ── contract ──────────────────────────────────────────────────────────────────

	public function testClassContract(): void
	{
		$this->assertInstanceOf(CacheInterface::class, $this->psr);
		$this->assertInstanceOf(IModuleDependency::class, $this->psr);
		$this->assertInstanceOf(TModule::class, $this->psr);
		$this->assertContains(TPsrCacheBackingTrait::class, class_uses($this->psr));
		$this->assertSame('{}()/\@:', TPsrSimpleCache::RESERVED_KEY_CHARACTERS);
	}

	public function testDefaults(): void
	{
		$psr = new TPsrSimpleCache();
		$this->assertSame('', $psr->getBackingCacheId());
		$this->assertSame('', $psr->getKeyPrefix());
		$this->assertSame(0, $psr->getDefaultExpire());
		$this->assertNull($psr->getModuleDependencies());
		$this->assertNull($psr->getModuleDependencies(true));
	}

	// ── get / set / has / delete / clear ──────────────────────────────────────────

	public function testSetGetRoundTrip(): void
	{
		$this->assertTrue($this->psr->set('string', 'value'));
		$this->assertSame('value', $this->psr->get('string'));
		$object = new \stdClass();
		$object->a = 1;
		$this->assertTrue($this->psr->set('object', $object));
		$this->assertEquals($object, $this->psr->get('object'));
		$this->assertTrue($this->psr->set('array', ['x' => [1, 2]]));
		$this->assertSame(['x' => [1, 2]], $this->psr->get('array'));
		$this->assertTrue($this->psr->set('float', 1.5));
		$this->assertSame(1.5, $this->psr->get('float'));
	}

	public function testEmptyValuesAreCached(): void
	{
		foreach ([null, false, 0, '', [], '0', 0.0] as $i => $value) {
			$key = 'empty' . $i;
			$this->assertTrue($this->psr->set($key, $value), $key);
			$this->assertTrue($this->psr->has($key), $key);
			$this->assertSame($value, $this->psr->get($key, 'miss'), $key);
		}
	}

	public function testGetDefaultOnMiss(): void
	{
		$this->assertNull($this->psr->get('missing'));
		$this->assertSame('default', $this->psr->get('missing', 'default'));
		$this->assertFalse($this->psr->has('missing'));
	}

	public function testOverwrite(): void
	{
		$this->psr->set('k', 'one');
		$this->psr->set('k', 'two');
		$this->assertSame('two', $this->psr->get('k'));
	}

	public function testDelete(): void
	{
		$this->psr->set('k', 'v');
		$this->assertTrue($this->psr->delete('k'));
		$this->assertFalse($this->psr->has('k'));
		$this->assertTrue($this->psr->delete('k'));
	}

	public function testClear(): void
	{
		$this->psr->set('a', 1);
		$this->psr->set('b', 2);
		$this->assertTrue($this->psr->clear());
		$this->assertSame([], $this->backing->store);
		$this->assertFalse($this->psr->has('a'));
	}

	public function testClearWithoutFlushSupportReturnsFalse(): void
	{
		$backing = new TTestNoFlushCache();
		$backing->setPrimaryCache(false);
		$backing->init(null);
		$this->psr->setBackingCache($backing);
		$this->psr->set('a', 1);
		$this->assertFalse($this->psr->clear());
		$this->assertSame(1, $this->psr->get('a'));
	}

	// ── TTL ───────────────────────────────────────────────────────────────────────

	public function testIntegerTtlExpires(): void
	{
		$this->backing->setFakeNow(1000);
		$this->assertTrue($this->psr->set('k', 'v', 10));
		$this->backing->setFakeNow(1009);
		$this->assertSame('v', $this->psr->get('k'));
		$this->backing->setFakeNow(1010);
		$this->assertNull($this->psr->get('k'));
		$this->assertFalse($this->psr->has('k'));
	}

	public function testZeroOrNegativeTtlDeletes(): void
	{
		$this->psr->set('k', 'v');
		$this->assertTrue($this->psr->set('k', 'new', 0));
		$this->assertFalse($this->psr->has('k'));
		$this->psr->set('k', 'v');
		$this->assertTrue($this->psr->set('k', 'new', -5));
		$this->assertFalse($this->psr->has('k'));
	}

	public function testNullTtlUsesDefaultExpire(): void
	{
		$backing = new TTestArrayCache();
		$this->psr->setBackingCache($backing);
		$this->psr->set('k', 'v');
		$this->assertSame(0, $backing->expires['k']);
		$this->psr->setDefaultExpire(45);
		$this->psr->set('k', 'v');
		$this->assertSame(45, $backing->expires['k']);
		$this->psr->set('k', 'v', 7);
		$this->assertSame(7, $backing->expires['k']);
	}

	public function testDateIntervalTtl(): void
	{
		$backing = new TTestArrayCache();
		$this->psr->setBackingCache($backing);
		$this->psr->setClock((new TMockClock())->setNow(1700000000));
		$this->assertTrue($this->psr->set('k', 'v', new \DateInterval('PT90S')));
		$this->assertSame(90, $backing->expires['k']);
		$this->assertTrue($this->psr->set('k', 'v', new \DateInterval('P1DT1H')));
		$this->assertSame(90000, $backing->expires['k']);
		$negative = new \DateInterval('PT5S');
		$negative->invert = 1;
		$this->assertTrue($this->psr->set('k', 'v', $negative));
		$this->assertFalse($this->psr->has('k'));
	}

	public function testDefaultExpireCoercion(): void
	{
		$this->psr->setDefaultExpire('30');
		$this->assertSame(30, $this->psr->getDefaultExpire());
		$this->psr->setDefaultExpire(-5);
		$this->assertSame(0, $this->psr->getDefaultExpire());
	}

	// ── keys and envelope ─────────────────────────────────────────────────────────

	public function testKeyPrefix(): void
	{
		$backing = new TTestArrayCache();
		$this->psr->setBackingCache($backing);
		$this->psr->setKeyPrefix('lib.');
		$this->assertSame('lib.', $this->psr->getKeyPrefix());
		$this->psr->set('a', 1);
		$this->assertSame([1], $backing->values['lib.a']);
		$this->assertSame(1, $this->psr->get('a'));
		$this->assertTrue($this->psr->delete('a'));
		$this->assertArrayNotHasKey('lib.a', $backing->values);
		$this->psr->setKeyPrefix(123);
		$this->assertSame('123', $this->psr->getKeyPrefix());
	}

	public function testValuesAreStoredInAnEnvelope(): void
	{
		$backing = new TTestArrayCache();
		$this->psr->setBackingCache($backing);
		$this->psr->set('k', null);
		$this->assertSame([null], $backing->values['k']);
		$backing->values['raw'] = 'not an envelope';
		$this->assertFalse($this->psr->has('raw'));
		$this->assertSame('default', $this->psr->get('raw', 'default'));
		$backing->values['pair'] = [1, 2];
		$this->assertFalse($this->psr->has('pair'));
	}

	public function testInvalidKeys(): void
	{
		foreach (['', 'a{b', 'a}b', 'a(b', 'a)b', 'a/b', 'a\\b', 'a@b', 'a:b'] as $key) {
			try {
				$this->psr->get($key);
				$this->fail("Key '{$key}' was accepted.");
			} catch (TPsrCacheInvalidArgumentException $e) {
				$this->assertInstanceOf(\Psr\SimpleCache\InvalidArgumentException::class, $e);
				$this->assertInstanceOf(\Psr\Cache\InvalidArgumentException::class, $e);
				$this->assertInstanceOf(TInvalidDataValueException::class, $e);
				$this->assertSame('psrcachebacking_key_invalid', $e->getErrorCode());
				$this->assertStringContainsString(TPsrSimpleCache::class, $e->getMessage());
			}
		}
	}

	public function testInvalidKeyOnEveryMethod(): void
	{
		$calls = [
			'set' => ['a:b', 1],
			'delete' => ['a:b'],
			'has' => ['a:b'],
			'getMultiple' => [['ok', 'a:b']],
			'setMultiple' => [['ok' => 1, 'a:b' => 2]],
			'deleteMultiple' => [['ok', 'a:b']],
		];
		foreach ($calls as $method => $args) {
			try {
				$this->psr->$method(...$args);
				$this->fail("{$method}() accepted an illegal key.");
			} catch (TPsrCacheInvalidArgumentException $e) {
				$this->assertSame('psrcachebacking_key_invalid', $e->getErrorCode());
			}
		}
		$this->assertSame([], $this->backing->store);
	}

	public function testNonStringKeyInIterable(): void
	{
		try {
			$this->psr->getMultiple([new \stdClass()]);
			$this->fail('An object key was accepted.');
		} catch (TPsrCacheInvalidArgumentException $e) {
			$this->assertStringContainsString('stdClass', $e->getMessage());
		}
	}

	public function testValidKeys(): void
	{
		foreach (['ABCxyz019_.', str_repeat('a', 64), str_repeat('k', 200), 'ключ', 'a-b', 'a b', '123'] as $key) {
			$this->assertTrue($this->psr->set($key, $key));
			$this->assertSame($key, $this->psr->get($key));
		}
	}

	// ── multiple ──────────────────────────────────────────────────────────────────

	public function testGetMultiple(): void
	{
		$this->psr->set('a', 1);
		$this->psr->set('b', 2);
		$this->assertSame(['a' => 1, 'b' => 2, 'c' => null], $this->psr->getMultiple(['a', 'b', 'c']));
		$this->assertSame(['a' => 1, 'c' => 'd'], $this->psr->getMultiple(new \ArrayIterator(['a', 'c']), 'd'));
		$this->assertSame([], $this->psr->getMultiple([]));
	}

	public function testSetMultiple(): void
	{
		$this->assertTrue($this->psr->setMultiple(['a' => 1, 'b' => 2, '123' => 3]));
		$this->assertSame(1, $this->psr->get('a'));
		$this->assertSame(2, $this->psr->get('b'));
		$this->assertSame(3, $this->psr->get('123'));
		$generator = (function () {
			yield 'g1' => 'one';
			yield 'g2' => 'two';
		})();
		$this->assertTrue($this->psr->setMultiple($generator, 10));
		$this->assertSame(['g1' => 'one', 'g2' => 'two'], $this->psr->getMultiple(['g1', 'g2']));
	}

	public function testSetMultipleTtlDeletes(): void
	{
		$this->psr->set('a', 1);
		$this->assertTrue($this->psr->setMultiple(['a' => 9, 'b' => 9], 0));
		$this->assertFalse($this->psr->has('a'));
		$this->assertFalse($this->psr->has('b'));
	}

	public function testDeleteMultiple(): void
	{
		$this->psr->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);
		$this->assertTrue($this->psr->deleteMultiple(['a', 'c', 'missing']));
		$this->assertSame(['a' => null, 'b' => 2, 'c' => null], $this->psr->getMultiple(['a', 'b', 'c']));
		$this->assertTrue($this->psr->deleteMultiple(new \ArrayIterator(['b'])));
		$this->assertFalse($this->psr->has('b'));
	}

	public function testFailuresPropagate(): void
	{
		$backing = new TPsrSimpleCacheTestFailingCache();
		$this->psr->setBackingCache($backing);
		$backing->fail = true;
		$this->assertFalse($this->psr->set('a', 1));
		$this->assertFalse($this->psr->set('a', 1, 0));
		$this->assertFalse($this->psr->setMultiple(['a' => 1, 'b' => 2]));
		$this->assertFalse($this->psr->delete('a'));
		$this->assertFalse($this->psr->deleteMultiple(['a', 'b']));
		$backing->fail = false;
		$this->assertTrue($this->psr->setMultiple(['a' => 1, 'b' => 2]));
		$this->assertTrue($this->psr->deleteMultiple(['a', 'b']));
	}

	// ── backing resolution ────────────────────────────────────────────────────────

	public function testBackingCacheIdResolvesModule(): void
	{
		$this->app->setModule('backingCache', $this->backing);
		$psr = new TPsrSimpleCache();
		$psr->setBackingCacheId('backingCache');
		$this->assertSame('backingCache', $psr->getBackingCacheId());
		$this->assertSame('backingCache', $psr->getModuleDependencies());
		$this->assertSame('backingCache', $psr->getModuleDependencies(true));
		$this->assertSame($this->backing, $psr->getBackingCache());
		$psr->set('k', 'v');
		$this->assertSame('v', $this->psr->get('k'));
	}

	public function testBackingCacheIdNotFound(): void
	{
		$psr = new TPsrSimpleCache();
		$psr->setBackingCacheId('nope');
		try {
			$psr->get('k');
			$this->fail('A missing module was accepted.');
		} catch (TPsrCacheException $e) {
			$this->assertInstanceOf(TConfigurationException::class, $e);
			$this->assertInstanceOf(\Psr\SimpleCache\CacheException::class, $e);
			$this->assertInstanceOf(\Psr\Cache\CacheException::class, $e);
			$this->assertSame('psrcachebacking_cache_not_found', $e->getErrorCode());
			$this->assertStringContainsString("'nope'", $e->getMessage());
			$this->assertStringContainsString(TPsrSimpleCache::class, $e->getMessage());
		}
	}

	public function testBackingCacheIdInvalidType(): void
	{
		$this->app->setModule('notACache', new TPsrSimpleCacheTestModule());
		$psr = new TPsrSimpleCache();
		$psr->setBackingCacheId('notACache');
		try {
			$psr->getBackingCache();
			$this->fail('A module that is not an ICache was accepted.');
		} catch (TPsrCacheException $e) {
			$this->assertSame('psrcachebacking_invalid_cache_type', $e->getErrorCode());
		}
	}

	public function testPrimaryCacheFallback(): void
	{
		$psr = new TPsrSimpleCache();
		try {
			$psr->getBackingCache();
			$this->fail('A missing primary cache was accepted.');
		} catch (TPsrCacheException $e) {
			$this->assertSame('psrcachebacking_cache_required', $e->getErrorCode());
		}
		$this->app->setCache($this->backing);
		$this->assertSame($this->backing, $psr->getBackingCache());
		$psr->set('k', 'v');
		$this->assertSame('v', $this->psr->get('k'));
	}

	public function testSetBackingCacheReplacesResolvedBacking(): void
	{
		$this->app->setModule('backingCache', $this->backing);
		$psr = new TPsrSimpleCache();
		$psr->setBackingCacheId('backingCache');
		$this->assertSame($this->backing, $psr->getBackingCache());
		$other = new TTestArrayCache();
		$psr->setBackingCache($other);
		$this->assertSame($other, $psr->getBackingCache());
		$psr->setBackingCache(null);
		$this->assertSame($this->backing, $psr->getBackingCache());
	}

	public function testSetBackingCacheIdDropsBackingAndLogs(): void
	{
		$second = $this->newBacking();
		$this->app->setModule('first', $this->backing);
		$this->app->setModule('second', $second);
		$before = $this->countLogs();

		$this->psr->setBackingCacheId('first');
		$this->assertSame($before, $this->countLogs());
		$this->assertSame($this->backing, $this->psr->getBackingCache());
		$this->psr->setBackingCacheId('first');
		$this->assertSame($before, $this->countLogs());

		$this->psr->setBackingCacheId('second');
		$this->assertSame($before + 1, $this->countLogs());
		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.caching');
		$message = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString("'first'", $message);
		$this->assertStringContainsString("'second'", $message);
		$this->assertStringContainsString(TPsrSimpleCache::class . '.BackingCacheId', $message);
		$this->assertSame($second, $this->psr->getBackingCache());
		$second->unlisten();
	}

	// ── serialization ─────────────────────────────────────────────────────────────

	public function testSleepProps(): void
	{
		$psr = new TPsrSimpleCacheTestAccessor();
		$class = TPsrSimpleCache::class;
		$props = $psr->pubGetZappableSleepProps();
		$this->assertContains("\0{$class}\0_backingCacheId", $props);
		$this->assertContains("\0{$class}\0_keyPrefix", $props);
		$this->assertContains("\0{$class}\0_defaultExpire", $props);
		$this->assertNotContains("\0{$class}\0_backingCache", $props);

		$psr->setBackingCacheId('backingCache');
		$psr->setKeyPrefix('p.');
		$psr->setDefaultExpire(3);
		$props = $psr->pubGetZappableSleepProps();
		$this->assertNotContains("\0{$class}\0_backingCacheId", $props);
		$this->assertNotContains("\0{$class}\0_keyPrefix", $props);
		$this->assertNotContains("\0{$class}\0_defaultExpire", $props);
		$this->assertContains("\0{$class}\0_backingCache", $props);
	}

	public function testSerializationRoundTrip(): void
	{
		$this->app->setModule('backingCache', $this->backing);
		$psr = new TPsrSimpleCache();
		$psr->setBackingCacheId('backingCache');
		$psr->setKeyPrefix('p.');
		$psr->setDefaultExpire(3);
		$psr->getBackingCache();
		$copy = unserialize(serialize($psr));
		$this->assertSame('backingCache', $copy->getBackingCacheId());
		$this->assertSame('p.', $copy->getKeyPrefix());
		$this->assertSame(3, $copy->getDefaultExpire());
		$this->assertSame($this->backing, $copy->getBackingCache());
	}
}
