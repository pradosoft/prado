<?php

/**
 * TMemoryCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICacheSize;
use Prado\Caching\TCache;
use Prado\Caching\TMemoryCache;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;
use Prado\TApplicationMode;

// ── Helpers ────────────────────────────────────────────────────────────────────

/**
 * Exposes protected internals and overrides now() for clock-controlled TTL tests.
 */
class TMemoryCacheTestAccessor extends TMemoryCache
{
	/** @var int|null when set, now() returns this value instead of time() */
	public ?int $fakeNow = null;

	protected function now(): int
	{
		return $this->fakeNow ?? parent::now();
	}

	public function pubNow(): int
	{
		return $this->now();
	}

	public function pubGetValue(string $key): mixed
	{
		return $this->getValue($key);
	}

	public function pubSetValue(string $key, mixed $value, int $expire): bool
	{
		return $this->setValue($key, $value, $expire);
	}

	public function pubAddValue(string $key, mixed $value, int $expire): bool
	{
		return $this->addValue($key, $value, $expire);
	}

	public function pubDeleteValue(string $key): bool
	{
		return $this->deleteValue($key);
	}

	public function pubLoad(): bool
	{
		return $this->load();
	}

	public function pubSave(): bool
	{
		return $this->save();
	}

	public function pubLoadFromBacking(): ?array
	{
		return $this->loadFromBacking();
	}

	public function pubSaveToBacking(array $store): bool
	{
		return $this->saveToBacking($store);
	}

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}

	public function pubGetStore(): array
	{
		return $this->getStoreDirect();
	}

	public function &pubGetStoreRef(): array
	{
		return $this->getStoreDirect();
	}

	public function pubComputeSizeFingerprint(): string
	{
		return $this->computeSizeFingerprint();
	}

	public function pubComputeCurrentSize(): int
	{
		return $this->computeCurrentSize();
	}

	public function pubEvictToFitMaximumSize(): void
	{
		$this->evictToFitMaximumSize();
	}

	public function pubValidateSizeCache(): void
	{
		$this->validateSizeCache();
	}

	public function pubGetMaximumSizeDirect(): int
	{
		return $this->getMaximumSizeDirect();
	}

	public function pubGetCurrentSizeDirect(): int
	{
		return $this->getCurrentSizeDirect();
	}

	public function pubSetCurrentSizeDirect(int $value): void
	{
		$this->setCurrentSizeDirect($value);
	}

	public function pubGetSizeFingerprintDirect(): string
	{
		return $this->getSizeFingerprintDirect();
	}

	public function pubSetSizeFingerprintDirect(string $value): void
	{
		$this->setSizeFingerprintDirect($value);
	}

	public function pubSetStore(array $value): void
	{
		$this->setStoreDirect($value);
	}

	public function pubGetStoreEntry(string $key): ?array
	{
		return $this->getStoreEntry($key);
	}

	public function pubHasStoreEntry(string $key): bool
	{
		return $this->hasStoreEntry($key);
	}

	public function pubSetStoreEntry(string $key, array $entry): void
	{
		$this->setStoreEntry($key, $entry);
	}

	public function pubClearStoreEntry(string $key): void
	{
		$this->clearStoreEntry($key);
	}

	public function pubGetChanged(): bool
	{
		return $this->getChanged();
	}

	public function pubGetHashKeys(): ?bool
	{
		return $this->getHashKeys();
	}

	public function pubSerialize(mixed $value): string
	{
		return $this->serialize($value);
	}

	public function pubUnserialize(string $data): mixed
	{
		return $this->unserialize($data);
	}

	public function pubGetContents(string $filePath): string|false
	{
		return $this->getContents($filePath);
	}

	public function pubPutContents(string $filePath, string $data): int|false
	{
		return $this->putContents($filePath, $data);
	}
}

/**
 * A TMemoryCache subclass that overrides DEFAULT_BACKING_CACHE_KEY to verify
 * that the constructor seeds _backingCacheKey via late static binding.
 */
class TMemoryCacheCustomKeyAccessor extends TMemoryCacheTestAccessor
{
	public const DEFAULT_BACKING_CACHE_KEY = 'custom.key';
}

/**
 * A TMemoryCache subclass that overrides DEFAULT_MERGE_POLICY to verify
 * that the constructor seeds _mergePolicy via late static binding.
 */
class TMemoryCacheCustomMergePolicyAccessor extends TMemoryCacheTestAccessor
{
	public const DEFAULT_MERGE_POLICY = TMemoryCache::REPLACE;
}

/**
 * A minimal in-memory TCache stub used as a backing cache module in tests.
 * Stores data in a plain PHP array so tests do not depend on TFileCache or
 * any external service.
 */
class StubBackingCache extends TCache
{
	private array $_data = [];

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function init($config): void
	{
		parent::init($config);
	}

	protected function getValue($key)
	{
		return $this->_data[$key] ?? false;
	}

	protected function setValue($key, $value, $expire)
	{
		$this->_data[$key] = $value;
		return true;
	}

	protected function addValue($key, $value, $expire)
	{
		if (array_key_exists($key, $this->_data)) {
			return false;
		}
		$this->_data[$key] = $value;
		return true;
	}

	protected function deleteValue($key)
	{
		unset($this->_data[$key]);
		return true;
	}

	public function flush()
	{
		$this->_data = [];
		return true;
	}

	/** Direct access for test assertions. */
	public function getRawData(): array
	{
		return $this->_data;
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TMemoryCacheTest class.
 *
 * Comprehensive unit tests for TMemoryCache: in-memory store, TTL expiry
 * (clock-controlled, no sleep()), backing cache module, backing file,
 * MergePolicy, BackingCacheKey, auto-save via OnSaveState, IModuleDependency,
 * DEFAULT_BACKING_CACHE_KEY and DEFAULT_MERGE_POLICY constants and late-static-binding constructor seeding,
 * TCacheSizeTrait integration (MaximumSize, getCurrentSize, isOverCapacity, LRU
 * eviction), serialization exclusions via _getZappableSleepProps, and edge cases.
 *
 * @package Prado\Tests\Unit\Caching
 */
class TMemoryCacheTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;
	private static string $tempDir;

	private ?TApplication $app = null;

	/** @var TMemoryCacheTestAccessor */
	private TMemoryCacheTestAccessor $cache;

	public static function setUpBeforeClass(): void
	{
		self::$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_memorycache_test_' . getmypid();
		if (!is_dir(self::$tempDir)) {
			mkdir(self::$tempDir, 0o755, true);
		}
	}

	public static function tearDownAfterClass(): void
	{
		if (is_dir(self::$tempDir)) {
			foreach (glob(self::$tempDir . '/*') ?: [] as $f) {
				@unlink($f);
			}
			@rmdir(self::$tempDir);
		}
	}

	protected function setUp(): void
	{
		$basePath = __DIR__ . '/mockapp';
		$this->app = new TApplication($basePath);

		$this->cache = new TMemoryCacheTestAccessor();
		$this->cache->setPrimaryCache(false);
		$this->cache->init(null);
	}

	protected function tearDown(): void
	{
		$this->cache->flush();
		$this->app = null;
	}

	// ── helpers ─────────────────────────────────────────────────────────────────

	/**
	 * Creates and initializes a StubBackingCache, registers it with the
	 * application as a non-primary module, and returns it.
	 */
	private function makeBackingCache(string $moduleId = 'backingCache'): StubBackingCache
	{
		$backing = new StubBackingCache();
		$backing->setID($moduleId);
		$backing->setPrimaryCache(false);
		$backing->init(null);
		$this->app->setModule($moduleId, $backing);
		return $backing;
	}

	/**
	 * Creates a TMemoryCacheTestAccessor wired to the given backing cache module.
	 */
	private function makeCacheWithBacking(string $moduleId = 'backingCache'): TMemoryCacheTestAccessor
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setID('memcache');
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId($moduleId);
		$cache->init(null);
		return $cache;
	}

	// ── construction ──────────────────────────────────────────────────────────

	public function testIsInstanceOfTMemoryCache(): void
	{
		$this->assertInstanceOf(TMemoryCache::class, $this->cache);
	}

	public function testIsInstanceOfTCache(): void
	{
		$this->assertInstanceOf(TCache::class, $this->cache);
	}

	public function testImplementsIModuleDependency(): void
	{
		$this->assertInstanceOf(\Prado\IModuleDependency::class, $this->cache);
	}

	public function testImplementsICacheSize(): void
	{
		$this->assertInstanceOf(ICacheSize::class, $this->cache);
	}

	// ── ICacheSize ────────────────────────────────────────────────────────────

	public function testSizeNotComputedConstantIsNegativeOne(): void
	{
		$this->assertSame(-1, ICacheSize::SIZE_NOT_COMPUTED,
			'ICacheSize::SIZE_NOT_COMPUTED must equal -1.');
	}

	public function testSizeNotComputedConstantAccessibleViaClass(): void
	{
		$this->assertSame(ICacheSize::SIZE_NOT_COMPUTED, TMemoryCache::SIZE_NOT_COMPUTED,
			'TMemoryCache::SIZE_NOT_COMPUTED must equal ICacheSize::SIZE_NOT_COMPUTED.');
	}

	// ── IModuleDependency ─────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenNoBackingCacheId(): void
	{
		self::assertModuleDependency(null, $this->cache->getModuleDependencies());
	}

	public function testGetModuleDependenciesReturnsIdWhenBackingCacheIdSet(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setBackingCacheId('fileCache');

		self::assertModuleDependency('fileCache', $cache->getModuleDependencies());
	}

	public function testGetModuleDependenciesSameForBothPhases(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setBackingCacheId('someModule');

		self::assertModuleDependency(
			$cache->getModuleDependencies(true),
			$cache->getModuleDependencies(false)
		);
	}

	// ── init() ────────────────────────────────────────────────────────────────

	public function testInitSetsDefaultBackingCacheKeyFromModuleId(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setID('myCache');
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$this->assertSame('prado.memory-cache.myCache', $cache->getBackingCacheKey());
	}

	public function testInitSetsDefaultBackingCacheKeyWhenNoId(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$this->assertSame('prado.memory-cache', $cache->getBackingCacheKey());
	}

	public function testExplicitBackingCacheKeyIsPreservedByInit(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheKey('my-custom-key');
		$cache->init(null);

		$this->assertSame('my-custom-key', $cache->getBackingCacheKey());
	}

	// ── set() / get() ─────────────────────────────────────────────────────────

	public function testSetAndGetString(): void
	{
		$this->cache->set('key1', 'hello');
		$this->assertSame('hello', $this->cache->get('key1'));
	}

	public function testSetAndGetArray(): void
	{
		$data = ['a' => 1, 'nested' => [2, 3]];
		$this->cache->set('arr_key', $data);
		$this->assertSame($data, $this->cache->get('arr_key'));
	}

	public function testSetAndGetObject(): void
	{
		$obj = new stdClass();
		$obj->x = 99;
		$this->cache->set('obj_key', $obj);
		$retrieved = $this->cache->get('obj_key');
		$this->assertInstanceOf(stdClass::class, $retrieved);
		$this->assertSame(99, $retrieved->x);
	}

	public function testGetReturnsFalseForMissingKey(): void
	{
		$this->assertFalse($this->cache->get('never_set_' . uniqid()));
	}

	public function testSetOverwritesExistingEntry(): void
	{
		$this->cache->set('overwrite', 'first');
		$this->cache->set('overwrite', 'second');
		$this->assertSame('second', $this->cache->get('overwrite'));
	}

	public function testSetWithEmptyValueAndZeroExpireDeletesEntry(): void
	{
		$this->cache->set('empty_key', 'original');
		$this->cache->set('empty_key', '');
		$this->assertFalse($this->cache->get('empty_key'));
	}

	public function testSetWithEmptyValueAndNonZeroExpireStoresIt(): void
	{
		$result = $this->cache->set('empty_exp_key', '', 3600);
		$this->assertTrue($result);
		$this->assertSame('', $this->cache->get('empty_exp_key'));
	}

	// ── TTL / expiry (clock-controlled — no sleep()) ──────────────────────────

	public function testGetReturnsFalseForExpiredEntry(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('exp_key', 'value', 10); // expires at 1_000_010

		$this->assertSame('value', $this->cache->get('exp_key'));

		$this->cache->fakeNow = 1_000_011;
		$this->assertFalse($this->cache->get('exp_key'));
	}

	public function testGetWithZeroExpireNeverExpires(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('persist_key', 'persist', 0);

		$this->cache->fakeNow = 2_000_000;
		$this->assertSame('persist', $this->cache->get('persist_key'));
	}

	public function testExpiredEntryIsRemovedFromStoreOnGet(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$key = $this->cache->pubGenerateUniqueKey('expire_remove');
		$this->cache->pubSetValue($key, ['val', null], 5); // expires at 1_000_005

		$this->cache->fakeNow = 1_000_006;
		// getValue triggers removal.
		$this->assertFalse($this->cache->pubGetValue($key));
		// The entry must have been pruned.
		$this->assertFalse($this->cache->pubGetValue($key));
	}

	// ── add() ─────────────────────────────────────────────────────────────────

	public function testAddStoresValueWhenKeyAbsent(): void
	{
		$result = $this->cache->add('add_key', 'added');
		$this->assertTrue($result);
		$this->assertSame('added', $this->cache->get('add_key'));
	}

	public function testAddReturnsFalseWhenKeyAlreadyExists(): void
	{
		$this->cache->set('dup_key', 'original');
		$result = $this->cache->add('dup_key', 'second');
		$this->assertFalse($result);
		$this->assertSame('original', $this->cache->get('dup_key'));
	}

	public function testAddSucceedsAfterExpiry(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('add_exp_key', 'first', 10);

		$this->cache->fakeNow = 1_000_011;
		$result = $this->cache->add('add_exp_key', 'second');
		$this->assertTrue($result);
		$this->assertSame('second', $this->cache->get('add_exp_key'));
	}

	// ── delete() ──────────────────────────────────────────────────────────────

	public function testDeleteRemovesExistingEntry(): void
	{
		$this->cache->set('del_key', 'value');
		$this->cache->delete('del_key');
		$this->assertFalse($this->cache->get('del_key'));
	}

	public function testDeleteReturnsTrueForAbsentKey(): void
	{
		$this->assertTrue($this->cache->delete('never_set_' . uniqid()));
	}

	// ── flush() ───────────────────────────────────────────────────────────────

	public function testFlushRemovesAllEntries(): void
	{
		$this->cache->set('f1', 'v1');
		$this->cache->set('f2', 'v2');
		$this->cache->flush();

		$this->assertFalse($this->cache->get('f1'));
		$this->assertFalse($this->cache->get('f2'));
	}

	public function testFlushReturnsTrueAlways(): void
	{
		$this->assertTrue($this->cache->flush());
	}

	// ── ArrayAccess ───────────────────────────────────────────────────────────

	public function testArrayAccessSetAndGet(): void
	{
		$this->cache['arr_key'] = 'arr_value';
		$this->assertSame('arr_value', $this->cache['arr_key']);
	}

	public function testArrayAccessIsset(): void
	{
		$this->cache['exists_key'] = 'val';
		$this->assertTrue(isset($this->cache['exists_key']));
		$this->assertFalse(isset($this->cache['missing_' . uniqid()]));
	}

	public function testArrayAccessUnset(): void
	{
		$this->cache['unset_key'] = 'val';
		unset($this->cache['unset_key']);
		$this->assertFalse(isset($this->cache['unset_key']));
	}

	// ── Various value types ───────────────────────────────────────────────────

	public function testStoresIntegerValue(): void
	{
		$this->cache->set('int_key', 12345);
		$this->assertSame(12345, $this->cache->get('int_key'));
	}

	public function testStoresFloatValue(): void
	{
		$this->cache->set('float_key', 3.14);
		$this->assertSame(3.14, $this->cache->get('float_key'));
	}

	public function testStoresBooleanTrue(): void
	{
		// Note: boolean false cannot be cached — TCache::get() uses false as the
		// "key not found" sentinel, so a stored false is indistinguishable from a miss.
		$this->cache->set('bool_true', true);
		$this->assertTrue($this->cache->get('bool_true'));
	}

	public function testStoresNullValue(): void
	{
		// null with expire=0 triggers delete path in TCache::set(); use expire>0.
		$this->cache->set('null_key', null, 3600);
		$this->assertNull($this->cache->get('null_key'));
	}

	// ── Multiple independent keys ─────────────────────────────────────────────

	public function testIndependentKeysDoNotInterfere(): void
	{
		$this->cache->set('k1', 'v1');
		$this->cache->set('k2', 'v2');
		$this->cache->set('k3', 'v3');

		$this->assertSame('v1', $this->cache->get('k1'));
		$this->assertSame('v2', $this->cache->get('k2'));
		$this->assertSame('v3', $this->cache->get('k3'));
	}

	// ── KeyPrefix ─────────────────────────────────────────────────────────────

	public function testKeyPrefixIsolatesNamespaces(): void
	{
		$this->cache->setKeyPrefix('ns1_');
		$this->cache->set('shared_key', 'ns1_value');

		$this->cache->setKeyPrefix('ns2_');
		$this->assertFalse($this->cache->get('shared_key'));

		$this->cache->setKeyPrefix('ns1_');
		$this->assertSame('ns1_value', $this->cache->get('shared_key'));
	}

	// ── now() ────────────────────────────────────────────────────────────────

	public function testNowReturnsIntegerCloseToCurrentTime(): void
	{
		$before = time();
		$result = $this->cache->pubNow();
		$after = time();

		$this->assertGreaterThanOrEqual($before, $result);
		$this->assertLessThanOrEqual($after, $result);
	}

	public function testFakeNowOverridesNow(): void
	{
		$this->cache->fakeNow = 42;
		$this->assertSame(42, $this->cache->pubNow());
	}

	// ── BackingCacheId property ───────────────────────────────────────────────

	public function testGetSetBackingCacheId(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setBackingCacheId('someModule');
		$this->assertSame('someModule', $cache->getBackingCacheId());
	}

	public function testBackingCacheIdDefaultsToEmpty(): void
	{
		$this->assertSame('', $this->cache->getBackingCacheId());
	}

	// ── BackingFile property ──────────────────────────────────────────────────

	public function testGetSetBackingFile(): void
	{
		$file = self::$tempDir . DIRECTORY_SEPARATOR . 'test.cache';
		$this->cache->setBackingFile($file);
		$this->assertSame($file, $this->cache->getBackingFile());
	}

	public function testBackingFileDefaultsToEmpty(): void
	{
		$this->assertSame('', $this->cache->getBackingFile());
	}

	public function testSetBackingFileToEmptyStringClearsIt(): void
	{
		$file = self::$tempDir . DIRECTORY_SEPARATOR . 'test.cache';
		$this->cache->setBackingFile($file);
		$this->cache->setBackingFile('');
		$this->assertSame('', $this->cache->getBackingFile());
	}

	public function testSetBackingFileThrowsWhenDirectoryDoesNotExist(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->cache->setBackingFile('/nonexistent/dir/cache.dat');
	}

	// ── BackingCacheKey property ──────────────────────────────────────────────

	public function testGetSetBackingCacheKey(): void
	{
		$this->cache->setBackingCacheKey('my-store-key');
		$this->assertSame('my-store-key', $this->cache->getBackingCacheKey());
	}

	// ── MergePolicy property ──────────────────────────────────────────────────

	public function testMergePolicyDefaultsToMerge(): void
	{
		$this->assertSame(TMemoryCache::MERGE, $this->cache->getMergePolicy());
	}

	public function testSetMergePolicyAcceptsReplace(): void
	{
		$this->cache->setMergePolicy(TMemoryCache::REPLACE);
		$this->assertSame(TMemoryCache::REPLACE, $this->cache->getMergePolicy());
	}

	public function testSetMergePolicyAcceptsMerge(): void
	{
		$this->cache->setMergePolicy(TMemoryCache::MERGE);
		$this->assertSame(TMemoryCache::MERGE, $this->cache->getMergePolicy());
	}

	public function testSetMergePolicyThrowsOnInvalidValue(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->cache->setMergePolicy('invalid-policy');
	}

	// ── Changed (dirty) flag ─────────────────────────────────────────────────

	public function testChangedFalseAfterInit(): void
	{
		// init() calls load(); no backing is configured → load() returns false
		// and does not reset the flag. The flag starts false and stays false.
		$this->assertFalse($this->cache->pubGetChanged());
	}

	public function testChangedTrueAfterSet(): void
	{
		$this->cache->set('k', 'v');
		$this->assertTrue($this->cache->pubGetChanged());
	}

	public function testChangedTrueAfterDelete(): void
	{
		$this->cache->delete('any_key');
		$this->assertTrue($this->cache->pubGetChanged());
	}

	public function testChangedTrueAfterFlush(): void
	{
		$this->cache->flush();
		$this->assertTrue($this->cache->pubGetChanged());
	}

	public function testChangedFalseAfterSuccessfulSave(): void
	{
		$this->makeBackingCache();
		$cache = $this->makeCacheWithBacking();

		$cache->set('k', 'v');
		$this->assertTrue($cache->pubGetChanged(), 'Flag must be true before save().');
		$cache->save();
		$this->assertFalse($cache->pubGetChanged(), 'Flag must be false after a successful save().');
	}

	public function testChangedFalseAfterSuccessfulLoad(): void
	{
		$this->makeBackingCache();

		$seed = $this->makeCacheWithBacking();
		$seed->set('k', 'v');
		$seed->save();

		$cache = $this->makeCacheWithBacking();
		// init() called load() which succeeded — flag must be false.
		$this->assertFalse($cache->pubGetChanged());
	}

	public function testChangedRemainsWhenSaveFails(): void
	{
		// Back cache is not registered → saveToBacking returns false.
		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('nonexistent');
		$cache->init(null);
		$cache->set('k', 'v');
		$cache->save(); // fails
		$this->assertTrue($cache->pubGetChanged(),
			'Flag must stay true when save() failed so the next attempt will retry.');
	}

	public function testSaveReturnsTrueWithoutWritingWhenUnchanged(): void
	{
		// No backing configured → saveToBacking would return false.
		// But _changed is false, so save() short-circuits and returns true.
		$this->assertFalse($this->cache->pubGetChanged());
		$this->assertTrue($this->cache->save(),
			'save() must return true without writing when the store is unchanged.');
	}

	public function testSaveSkipsWriteWhenUnchanged(): void
	{
		$backing = $this->makeBackingCache();
		$cache = $this->makeCacheWithBacking();

		// Store is clean after init() load (nothing in backing, no data written).
		$this->assertFalse($cache->pubGetChanged());
		$cache->save(); // must be a no-op
		$this->assertEmpty($backing->getRawData(),
			'Backing cache must remain empty when save() is called with no changes.');
	}

	// ── save() / load() — no backing (no-op) ─────────────────────────────────

	public function testSaveReturnsFalseWhenNoBackingConfigured(): void
	{
		// set() marks the store changed; saveToBacking returns false (no backing).
		$this->cache->set('key', 'value');
		$this->assertFalse($this->cache->save());
	}

	public function testLoadReturnsFalseWhenNoBackingConfigured(): void
	{
		$this->assertFalse($this->cache->load());
	}

	// ── save() / load() — backing cache module ────────────────────────────────

	public function testSaveAndLoadRoundtripViaBakingCacheModule(): void
	{
		$this->makeBackingCache();
		$cache = $this->makeCacheWithBacking();

		$cache->set('hello', 'world');
		$this->assertTrue($cache->save());

		// New instance, same backing — load must restore.
		$cache2 = $this->makeCacheWithBacking();
		$cache2->flush(); // clear any load() data from init
		$cache2->pubLoad();
		$this->assertSame('world', $cache2->get('hello'));
	}

	public function testLoadFromBackingCacheModulePopulatesStore(): void
	{
		$backing = $this->makeBackingCache();

		// Pre-seed the backing cache with a store snapshot.
		$seed = new TMemoryCacheTestAccessor();
		$seed->setID('memcache');
		$seed->setPrimaryCache(false);
		$seed->setBackingCacheId('backingCache');
		$seed->init(null);
		$seed->set('seeded', 'value');
		$seed->save();

		// Fresh instance must pick up the seeded data.
		$fresh = new TMemoryCacheTestAccessor();
		$fresh->setID('memcache');
		$fresh->setPrimaryCache(false);
		$fresh->setBackingCacheId('backingCache');
		$fresh->init(null);

		$this->assertSame('value', $fresh->get('seeded'));
	}

	public function testLoadReturnsFalseWhenBackingCacheModuleHasNoData(): void
	{
		$this->makeBackingCache();
		$cache = new TMemoryCacheTestAccessor();
		$cache->setID('memcache');
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('backingCache');
		$cache->init(null);

		// Nothing was saved beforehand; backing cache has no entry.
		$this->assertNull($cache->pubLoadFromBacking());
	}

	public function testSaveReturnsFalseWhenBackingModuleNotFound(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('nonexistent');
		$cache->init(null);
		$cache->set('k', 'v');
		$this->assertFalse($cache->save());
	}

	// ── save() / load() — backing file ───────────────────────────────────────

	public function testSaveAndLoadRoundtripViaBackingFile(): void
	{
		$file = self::$tempDir . DIRECTORY_SEPARATOR . 'roundtrip_' . uniqid() . '.dat';

		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->setBackingFile($file);
		$cache->init(null);
		$cache->set('file_key', 'file_value');
		$this->assertTrue($cache->save());

		// Second instance reads from file.
		$cache2 = new TMemoryCacheTestAccessor();
		$cache2->setPrimaryCache(false);
		$cache2->setBackingFile($file);
		$cache2->init(null);
		$this->assertSame('file_value', $cache2->get('file_key'));

		@unlink($file);
	}

	public function testLoadReturnsFalseWhenBackingFileIsMissing(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->setBackingFile(self::$tempDir . DIRECTORY_SEPARATOR . 'no_such_file_' . uniqid() . '.dat');
		$cache->init(null);

		$this->assertNull($cache->pubLoadFromBacking());
	}

	public function testLoadReturnsFalseWhenBackingFileContainsGarbage(): void
	{
		$file = self::$tempDir . DIRECTORY_SEPARATOR . 'garbage_' . uniqid() . '.dat';
		file_put_contents($file, 'THIS IS NOT VALID SERIALIZED DATA');

		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->setBackingFile($file);
		$cache->init(null);

		$this->assertNull($cache->pubLoadFromBacking());

		@unlink($file);
	}

	// ── Module preferred over file ────────────────────────────────────────────

	public function testBackingCacheModulePreferredOverBackingFile(): void
	{
		$backing = $this->makeBackingCache();

		$file = self::$tempDir . DIRECTORY_SEPARATOR . 'not_used_' . uniqid() . '.dat';

		$cache = new TMemoryCacheTestAccessor();
		$cache->setID('memcache');
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('backingCache');
		$cache->setBackingFile($file);
		$cache->init(null);
		$cache->set('source', 'from-module');
		$cache->save();

		// The backing file is not written because the module takes priority.
		$this->assertFileDoesNotExist($file);

		// Reload — data comes from module, not file.
		$cache2 = new TMemoryCacheTestAccessor();
		$cache2->setID('memcache');
		$cache2->setPrimaryCache(false);
		$cache2->setBackingCacheId('backingCache');
		$cache2->setBackingFile($file);
		$cache2->init(null);
		$this->assertSame('from-module', $cache2->get('source'));
	}

	// ── MergePolicy — Merge ───────────────────────────────────────────────────

	public function testMergePolicyMergePreservesExistingKeysOnLoad(): void
	{
		$backing = $this->makeBackingCache();

		// Pre-seed backing with 'shared' and 'from_backing'.
		$seed = $this->makeCacheWithBacking();
		$seed->set('shared', 'backing-value');
		$seed->set('from_backing', 'b');
		$seed->save();

		// Fresh instance already has 'shared' in memory from init() load.
		// Simulate a manual second load after writing 'shared' locally.
		$cache = new TMemoryCacheTestAccessor();
		$cache->setID('memcache');
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('backingCache');
		$cache->setMergePolicy(TMemoryCache::MERGE);
		$cache->init(null);

		// Override 'shared' in memory after loading.
		$cache->set('shared', 'memory-value');
		// Load again — Merge policy must preserve the in-memory 'shared'.
		$cache->pubLoad();

		$this->assertSame('memory-value', $cache->get('shared'),
			'Merge policy must preserve the in-memory value for existing keys.');
		$this->assertSame('b', $cache->get('from_backing'),
			'Merge policy must import keys absent from memory.');
	}

	// ── MergePolicy — Replace ─────────────────────────────────────────────────

	public function testMergePolicyReplaceOverwritesMemoryWithBackingData(): void
	{
		$backing = $this->makeBackingCache();

		// Pre-seed backing.
		$seed = $this->makeCacheWithBacking();
		$seed->set('key', 'backing-value');
		$seed->save();

		$cache = new TMemoryCacheTestAccessor();
		$cache->setID('memcache');
		$cache->setPrimaryCache(false);
		$cache->setBackingCacheId('backingCache');
		$cache->setMergePolicy(TMemoryCache::REPLACE);
		$cache->init(null);

		// Override in memory.
		$cache->set('key', 'memory-value');
		// Replace — load must overwrite.
		$cache->pubLoad();

		$this->assertSame('backing-value', $cache->get('key'),
			'Replace policy must overwrite in-memory value with backing data.');
	}

	// ── OnSaveState auto-save ─────────────────────────────────────────────────

	public function testHandleSaveStateCallsSave(): void
	{
		$backing = $this->makeBackingCache();
		$cache = $this->makeCacheWithBacking();

		$cache->set('auto', 'saved');

		// Simulate the application raising OnSaveState.
		$cache->handleSaveState($this->app, null);

		// Verify the backing cache now contains the store.
		$raw = $backing->getRawData();
		$this->assertNotEmpty($raw, 'Backing cache must contain data after handleSaveState().');

		// Reload into a fresh instance to confirm round-trip.
		$cache2 = $this->makeCacheWithBacking();
		$this->assertSame('saved', $cache2->get('auto'));
	}

	// ── _getZappableSleepProps ────────────────────────────────────────────────

	public function testSerializationExcludesInMemoryStore(): void
	{
		$this->cache->set('transient', 'data');
		$serialized = serialize($this->cache);
		$restored = unserialize($serialized);

		// The store is excluded from serialization; it must be empty on restoration.
		$this->assertFalse($restored->get('transient'),
			'The in-memory store must not survive serialization.');
	}

	public function testSerializationAlwaysExcludesCurrentSize(): void
	{
		// _currentSize is transient — always zapped regardless of its value.
		$this->cache->pubSetCurrentSizeDirect(512);
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_currentSize', $serialized,
			'_currentSize must always be excluded from the serialized payload.');
		$restored = unserialize($serialized);
		$this->assertSame(TMemoryCache::SIZE_NOT_COMPUTED, $restored->pubGetCurrentSizeDirect(),
			'_currentSize must reset to SIZE_NOT_COMPUTED after deserialization.');
	}

	public function testSerializationAlwaysExcludesSizeFingerprint(): void
	{
		// _sizeFingerprint is transient — always zapped.
		$this->cache->pubSetSizeFingerprintDirect('abc123');
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_sizeFingerprint', $serialized,
			'_sizeFingerprint must always be excluded from the serialized payload.');
		$restored = unserialize($serialized);
		$this->assertSame('', $restored->pubGetSizeFingerprintDirect(),
			'_sizeFingerprint must reset to empty string after deserialization.');
	}

	public function testSerializationExcludesMaximumSizeWhenDefault(): void
	{
		// MaximumSize == 0 is the default; it must be zapped to keep the payload lean.
		$this->assertSame(0, $this->cache->pubGetMaximumSizeDirect());
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_maximumSize', $serialized,
			'_maximumSize == 0 must be excluded from the serialized payload.');
	}

	public function testSerializationIncludesMaximumSizeWhenNonDefault(): void
	{
		// A non-zero MaximumSize is user-configured and must survive round-trip.
		$this->cache->setMaximumSize(65536);
		$serialized = serialize($this->cache);
		$this->assertStringContainsString('_maximumSize', $serialized,
			'A non-zero _maximumSize must be retained in the serialized payload.');
		$restored = unserialize($serialized);
		$this->assertSame(65536, $restored->pubGetMaximumSizeDirect(),
			'_maximumSize must survive serialization round-trip when non-zero.');
	}

	// ── PrimaryCache ─────────────────────────────────────────────────────────

	public function testGetSetPrimaryCache(): void
	{
		$this->cache->setPrimaryCache(true);
		$this->assertTrue($this->cache->getPrimaryCache());
		$this->cache->setPrimaryCache(false);
		$this->assertFalse($this->cache->getPrimaryCache());
	}

	// ── Store direct accessors ───────────────────────────────────────────────

	public function testGetStoreDirectReturnsEmptyArrayInitially(): void
	{
		$this->assertSame([], $this->cache->pubGetStore());
	}

	public function testSetStoreDirectReplacesEntireStore(): void
	{
		$snapshot = [
			'k1' => ['data' => 'v1', 'expire' => 0],
			'k2' => ['data' => 'v2', 'expire' => 0],
		];
		$this->cache->pubSetStore($snapshot);
		$this->assertSame($snapshot, $this->cache->pubGetStore());
	}

	public function testGetStoreDirectReflectsSetOperation(): void
	{
		// Use the public set() so the key is hashed via generateUniqueKey() before
		// being written into the store. pubSetValue() bypasses the hash step.
		$this->cache->set('raw', 'payload');
		$internalKey = $this->cache->pubGenerateUniqueKey('raw');
		$this->assertArrayHasKey($internalKey, $this->cache->pubGetStore());
	}

	public function testSetStoreDirectEmptyArrayClearsStore(): void
	{
		$this->cache->set('k', 'v');
		$this->cache->pubSetStore([]);
		$this->assertSame([], $this->cache->pubGetStore());
	}

	// ── Store entry accessors ────────────────────────────────────────────────

	public function testHasStoreEntryReturnsFalseWhenAbsent(): void
	{
		$this->assertFalse($this->cache->pubHasStoreEntry('nonexistent'));
	}

	public function testHasStoreEntryReturnsTrueAfterSetStoreEntry(): void
	{
		$this->cache->pubSetStoreEntry('k', ['data' => 'v', 'expire' => 0]);
		$this->assertTrue($this->cache->pubHasStoreEntry('k'));
	}

	public function testGetStoreEntryReturnsNullWhenAbsent(): void
	{
		$this->assertNull($this->cache->pubGetStoreEntry('nonexistent'));
	}

	public function testGetStoreEntryReturnsEntryAfterSetStoreEntry(): void
	{
		$entry = ['data' => 'hello', 'expire' => 42];
		$this->cache->pubSetStoreEntry('k', $entry);
		$this->assertSame($entry, $this->cache->pubGetStoreEntry('k'));
	}

	public function testGetStoreEntryWithNullDataIsDistinctFromAbsent(): void
	{
		// An entry whose STORE_DATA is null must be retrievable and must not
		// be confused with a missing key — this is the reason clearStoreEntry()
		// exists as a separate method from setStoreEntry().
		$this->cache->pubSetStoreEntry('null_key', ['data' => null, 'expire' => 0]);

		$this->assertTrue($this->cache->pubHasStoreEntry('null_key'),
			'A key with null data must still be present in the store.');
		$entry = $this->cache->pubGetStoreEntry('null_key');
		$this->assertIsArray($entry);
		$this->assertNull($entry['data']);
	}

	public function testClearStoreEntryRemovesExistingEntry(): void
	{
		$this->cache->pubSetStoreEntry('k', ['data' => 'v', 'expire' => 0]);
		$this->cache->pubClearStoreEntry('k');
		$this->assertFalse($this->cache->pubHasStoreEntry('k'));
		$this->assertNull($this->cache->pubGetStoreEntry('k'));
	}

	public function testClearStoreEntryOnAbsentKeyIsNoop(): void
	{
		// Must not throw — removing a nonexistent key is silently ignored.
		$this->cache->pubClearStoreEntry('nonexistent');
		$this->assertFalse($this->cache->pubHasStoreEntry('nonexistent'));
	}

	public function testSetStoreEntryOverwritesExisting(): void
	{
		$this->cache->pubSetStoreEntry('k', ['data' => 'first', 'expire' => 0]);
		$this->cache->pubSetStoreEntry('k', ['data' => 'second', 'expire' => 99]);
		$entry = $this->cache->pubGetStoreEntry('k');
		$this->assertSame('second', $entry['data']);
		$this->assertSame(99, $entry['expire']);
	}

	public function testClearStoreEntryDoesNotAffectOtherKeys(): void
	{
		$this->cache->pubSetStoreEntry('keep', ['data' => 'v', 'expire' => 0]);
		$this->cache->pubSetStoreEntry('remove', ['data' => 'x', 'expire' => 0]);
		$this->cache->pubClearStoreEntry('remove');

		$this->assertTrue($this->cache->pubHasStoreEntry('keep'));
		$this->assertFalse($this->cache->pubHasStoreEntry('remove'));
	}

	// ── Low-level getValue / setValue / addValue / deleteValue ───────────────

	public function testGetValueReturnsFalseForMissingKey(): void
	{
		$this->assertFalse($this->cache->pubGetValue('nonexistent'));
	}

	public function testSetValueAndGetValueRoundtrip(): void
	{
		$payload = ['val', null];
		$this->cache->pubSetValue('rawkey', $payload, 0);
		$this->assertSame($payload, $this->cache->pubGetValue('rawkey'));
	}

	public function testSetValueWithExpireComputesAbsoluteTimestamp(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->pubSetValue('exp_key', 'payload', 100);

		// One second before expiry — must still be present.
		$this->cache->fakeNow = 1_000_099;
		$this->assertSame('payload', $this->cache->pubGetValue('exp_key'));

		// One second past expiry — must return false.
		$this->cache->fakeNow = 1_000_101;
		$this->assertFalse($this->cache->pubGetValue('exp_key'));
	}

	public function testAddValueReturnsFalseWhenKeyExists(): void
	{
		$this->cache->pubSetValue('existing', 'data', 0);
		$this->assertFalse($this->cache->pubAddValue('existing', 'new', 0));
	}

	public function testAddValueSucceedsWhenKeyAbsent(): void
	{
		$this->assertTrue($this->cache->pubAddValue('fresh', 'data', 0));
		$this->assertSame('data', $this->cache->pubGetValue('fresh'));
	}

	public function testDeleteValueRemovesEntry(): void
	{
		$this->cache->pubSetValue('del', 'x', 0);
		$this->cache->pubDeleteValue('del');
		$this->assertFalse($this->cache->pubGetValue('del'));
	}

	public function testDeleteValueOnAbsentKeyReturnsTrue(): void
	{
		$this->assertTrue($this->cache->pubDeleteValue('nonexistent'));
	}

	// ── Protected I/O helpers ─────────────────────────────────────────────────

	public function testSerializeAndUnserializeRoundtrip(): void
	{
		$values = [
			'string' => 'hello world',
			'integer' => 42,
			'float' => 3.14,
			'bool' => true,
			'null' => null,
			'array' => [1, 'two', [3]],
			'object' => (static function () {
				$o = new stdClass();
				$o->x = 99;
				return $o;
			})(),
		];
		foreach ($values as $label => $value) {
			$serialized = $this->cache->pubSerialize($value);
			$this->assertIsString($serialized,
				"serialize() must return a string for: $label");
			$roundtripped = $this->cache->pubUnserialize($serialized);
			$this->assertEquals($value, $roundtripped,
				"unserialize(serialize(value)) must equal the original for: $label");
		}
	}

	public function testUnserializeReturnsFalseOnInvalidData(): void
	{
		// PHP's unserialize() returns false and emits a notice on corrupt input;
		// the @ suppressor in unserialize() must silence the notice.
		$result = $this->cache->pubUnserialize('THIS IS NOT VALID SERIALIZED DATA!!!');
		$this->assertFalse($result);
	}

	public function testPutContentsAndGetContentsRoundtrip(): void
	{
		$file = self::$tempDir . DIRECTORY_SEPARATOR . 'io_helper_' . uniqid() . '.dat';
		$payload = "binary\x00data\nwith newlines";

		$written = $this->cache->pubPutContents($file, $payload);
		$this->assertIsInt($written, 'putContents() must return the byte count on success.');
		$this->assertGreaterThan(0, $written);

		$read = $this->cache->pubGetContents($file);
		$this->assertSame($payload, $read, 'getContents() must return exactly what putContents() wrote.');

		@unlink($file);
	}

	public function testGetContentsReturnsFalseForMissingFile(): void
	{
		$missing = self::$tempDir . DIRECTORY_SEPARATOR . 'no_such_file_' . uniqid() . '.dat';
		$this->assertFalse($this->cache->pubGetContents($missing));
	}

	public function testPutContentsReturnsFalseForUnwritablePath(): void
	{
		// /nonexistent/path/file.dat — the directory does not exist so
		// file_put_contents will fail and return false.
		$result = $this->cache->pubPutContents('/nonexistent/path/file_' . uniqid() . '.dat', 'data');
		$this->assertFalse($result);
	}

	// ── HashKeys property ─────────────────────────────────────────────────────

	public function testHashKeysDefaultsToNull(): void
	{
		$this->assertNull($this->cache->getHashKeys());
	}

	public function testSetHashKeysToTrue(): void
	{
		$this->cache->setHashKeys(true);
		$this->assertTrue($this->cache->getHashKeys());
	}

	public function testSetHashKeysToFalse(): void
	{
		$this->cache->setHashKeys(false);
		$this->assertFalse($this->cache->getHashKeys());
	}

	public function testSetHashKeysToNullResetsToAuto(): void
	{
		$this->cache->setHashKeys(true);
		$this->cache->setHashKeys(null);
		$this->assertNull($this->cache->getHashKeys());
	}

	public function testSetHashKeysFromStringTrue(): void
	{
		$this->cache->setHashKeys('true');
		$this->assertTrue($this->cache->getHashKeys());
	}

	public function testSetHashKeysFromStringFalse(): void
	{
		$this->cache->setHashKeys('false');
		$this->assertFalse($this->cache->getHashKeys());
	}

	public function testSetHashKeysFromStringNull(): void
	{
		$this->cache->setHashKeys(true);
		$this->cache->setHashKeys('null');
		$this->assertNull($this->cache->getHashKeys());
	}

	public function testSetHashKeysFromEmptyString(): void
	{
		$this->cache->setHashKeys(true);
		$this->cache->setHashKeys('');
		$this->assertNull($this->cache->getHashKeys());
	}

	// ── generateUniqueKey (hash on/off/auto) ──────────────────────────────────

	public function testGenerateUniqueKeyWithHashEnabled(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setKeyPrefix('pfx_');
		$cache->setPrimaryCache(false);
		$cache->setHashKeys(true);
		$cache->init(null);

		$key = 'my_key';
		$this->assertSame(sha1('pfx_' . $key), $cache->pubGenerateUniqueKey($key));
	}

	public function testGenerateUniqueKeyWithHashDisabled(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setKeyPrefix('pfx_');
		$cache->setPrimaryCache(false);
		$cache->setHashKeys(false);
		$cache->init(null);

		$key = 'my_key';
		$this->assertSame('pfx_' . $key, $cache->pubGenerateUniqueKey($key));
	}

	public function testGenerateUniqueKeyAutoDebugModeDoesNotHash(): void
	{
		// TApplication defaults to Debug mode — hash must be off.
		$this->assertSame(TApplicationMode::Debug, $this->app->getMode());

		$cache = new TMemoryCacheTestAccessor();
		$cache->setKeyPrefix('pfx_');
		$cache->setPrimaryCache(false);
		// HashKeys is null (auto).
		$cache->init(null);

		$key = 'my_key';
		$this->assertSame('pfx_' . $key, $cache->pubGenerateUniqueKey($key),
			'In Debug mode with HashKeys=null, keys must NOT be hashed.');
	}

	public function testGenerateUniqueKeyAutoNormalModeHashes(): void
	{
		$this->app->setMode(TApplicationMode::Normal);

		$cache = new TMemoryCacheTestAccessor();
		$cache->setKeyPrefix('pfx_');
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$key = 'my_key';
		$this->assertSame(sha1('pfx_' . $key), $cache->pubGenerateUniqueKey($key),
			'In Normal mode with HashKeys=null, keys must be hashed.');
	}

	public function testGenerateUniqueKeyAutoPerformanceModeHashes(): void
	{
		$this->app->setMode(TApplicationMode::Performance);

		$cache = new TMemoryCacheTestAccessor();
		$cache->setKeyPrefix('pfx_');
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$key = 'my_key';
		$this->assertSame(sha1('pfx_' . $key), $cache->pubGenerateUniqueKey($key),
			'In Performance mode with HashKeys=null, keys must be hashed.');
	}

	public function testHashKeysSerializationPreservesTrueAndFalse(): void
	{
		// true and false are non-default — they must survive serialization.
		foreach ([true, false] as $setting) {
			$cache = new TMemoryCacheTestAccessor();
			$cache->setPrimaryCache(false);
			$cache->setHashKeys($setting);
			$cache->init(null);

			$restored = unserialize(serialize($cache));
			$this->assertSame($setting, $restored->getHashKeys(),
				"HashKeys=$setting must survive serialization.");
		}
	}

	public function testHashKeysSerializationExcludesNullDefault(): void
	{
		// null is the default — it must be excluded from the serialized form.
		$this->assertNull($this->cache->getHashKeys());
		$serialized = serialize($this->cache);
		// The field name must not appear in the payload.
		$this->assertStringNotContainsString('_hashKeys', $serialized,
			'HashKeys=null must be zapped from the serialized payload.');
	}

	// ── DEFAULT_BACKING_CACHE_KEY constant ───────────────────────────────────────

	public function testDefaultBackingCacheKeyConstantValue(): void
	{
		$this->assertSame('prado.memory-cache', TMemoryCache::DEFAULT_BACKING_CACHE_KEY);
	}

	public function testConstructSetsBackingCacheKeyToDefaultConstant(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$this->assertSame(
			TMemoryCache::DEFAULT_BACKING_CACHE_KEY,
			$cache->getBackingCacheKey(),
			'Constructor must seed _backingCacheKey from DEFAULT_BACKING_CACHE_KEY.'
		);
	}

	public function testSubclassCanOverrideDefaultBackingCacheKey(): void
	{
		$cache = new TMemoryCacheCustomKeyAccessor();
		$this->assertSame(
			'custom.key',
			$cache->getBackingCacheKey(),
			'Subclass overriding DEFAULT_BACKING_CACHE_KEY must have its value used via late static binding.'
		);
	}

	// ── DEFAULT_MERGE_POLICY constant ────────────────────────────────────────────

	public function testDefaultMergePolicyConstantValue(): void
	{
		$this->assertSame(TMemoryCache::MERGE, TMemoryCache::DEFAULT_MERGE_POLICY);
	}

	public function testConstructSetsMergePolicyToDefaultConstant(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$this->assertSame(
			TMemoryCache::DEFAULT_MERGE_POLICY,
			$cache->getMergePolicy(),
			'Constructor must seed _mergePolicy from DEFAULT_MERGE_POLICY.'
		);
	}

	public function testSubclassCanOverrideDefaultMergePolicy(): void
	{
		$cache = new TMemoryCacheCustomMergePolicyAccessor();
		$this->assertSame(
			TMemoryCache::REPLACE,
			$cache->getMergePolicy(),
			'Subclass overriding DEFAULT_MERGE_POLICY must have its value used via late static binding.'
		);
	}

	// ── getModuleDependencies() ───────────────────────────────────────────────────

	public function testGetModuleDependenciesUsesIsPreInitParameter(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setBackingCacheId('someId');

		// TMemoryCache does not differentiate by phase — both calls must return
		// equivalent dependency declarations.
		self::assertModuleDependency(
			$cache->getModuleDependencies(true),
			$cache->getModuleDependencies(false),
			'getModuleDependencies() must return equivalent values for both $isPreInit values.'
		);
		self::assertModuleDependency('someId', $cache->getModuleDependencies(false));
	}

	// ── getStoreDirect() returns by reference ─────────────────────────────────────

	public function testGetStoreDirectReturnsReference(): void
	{
		// Obtain a true reference to the backing store array via pubGetStoreRef().
		// Mutate it directly and confirm the change is visible in a subsequent
		// pubGetStore() call, which re-enters the same getStoreDirect() path.
		$ref = &$this->cache->pubGetStoreRef();
		$ref['injected'] = ['data' => 'x', 'expire' => 0];
		$this->assertArrayHasKey('injected', $this->cache->pubGetStore(),
			'Mutating the reference returned by getStoreDirect() must be visible in subsequent reads.');
	}

	// ── TCacheSizeTrait — MaximumSize property ────────────────────────────────────

	public function testMaximumSizeDefaultsToZero(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$this->assertSame(0, $cache->getMaximumSize(),
			'MaximumSize must default to 0 (unlimited).');
	}

	public function testSetMaximumSizeAndGet(): void
	{
		$this->cache->setMaximumSize(1024);
		$this->assertSame(1024, $this->cache->getMaximumSize());
	}

	public function testSetMaximumSizeNegativeClampedToZero(): void
	{
		$this->cache->setMaximumSize(-100);
		$this->assertSame(0, $this->cache->getMaximumSize(),
			'Negative MaximumSize must be clamped to 0.');
	}

	public function testSetMaximumSizeZeroIsAllowed(): void
	{
		$this->cache->setMaximumSize(1024);
		$this->cache->setMaximumSize(0);
		$this->assertSame(0, $this->cache->getMaximumSize());
	}

	public function testSetMaximumSizeFromString(): void
	{
		$this->cache->setMaximumSize('2048');
		$this->assertSame(2048, $this->cache->getMaximumSize(),
			'setMaximumSize() must accept a plain numeric string.');
	}

	// ── TCacheSizeTrait — MaximumSize size string parsing ─────────────────────────

	/**
	 * @return array<string, array{0: int|string, 1: int}>
	 */
	public static function provideSizeStrings(): array
	{
		return [
			// plain integers and numeric strings
			'int zero'            => [0,          0],
			'int bytes'           => [4194304,    4194304],
			'string plain bytes'  => ['4194304',  4194304],
			// K / KB
			'K lowercase'         => ['256k',     256 * 1_024],
			'K uppercase'         => ['256K',     256 * 1_024],
			'KB uppercase'        => ['256KB',    256 * 1_024],
			'kb lowercase'        => ['256kb',    256 * 1_024],
			// M / MB
			'M uppercase'         => ['512M',     512 * 1_048_576],
			'MB uppercase'        => ['512MB',    512 * 1_048_576],
			'mb lowercase'        => ['512mb',    512 * 1_048_576],
			// G / GB
			'G uppercase'         => ['1G',       1 * 1_073_741_824],
			'GB uppercase'        => ['2GB',      2 * 1_073_741_824],
			// T / TB
			'T uppercase'         => ['1T',       1 * 1_099_511_627_776],
			'TB uppercase'        => ['1TB',      1 * 1_099_511_627_776],
			// P / PB
			'P uppercase'         => ['1P',       1 * 1_125_899_906_842_624],
			'PB uppercase'        => ['1PB',      1 * 1_125_899_906_842_624],
			// whitespace tolerance
			'leading/trailing ws' => ['  512M  ', 512 * 1_048_576],
			// zero with suffix
			'zero with M suffix'  => ['0M',       0],
			// unrecognized → clamped to 0 (unlimited)
			'garbage string'      => ['bad!',     0],
			'negative string'     => ['-1',       0],
		];
	}

	/**
	 * @dataProvider provideSizeStrings
	 */
	public function testSetMaximumSizeAcceptsSizeStrings(int|string $input, int $expected): void
	{
		$this->cache->setMaximumSize($input);
		$this->assertSame($expected, $this->cache->getMaximumSize(),
			"setMaximumSize($input) must resolve to $expected bytes.");
	}

	// ── TCacheSizeTrait — getCurrentSize() ────────────────────────────────────────

	public function testGetCurrentSizeReturnsSizeNotComputedWhenInactiveAndNoMaximumSize(): void
	{
		$cache = new TMemoryCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->init(null);
		// MaximumSize is 0 by default; size tracking is inactive, sentinel stays SIZE_NOT_COMPUTED.
		$this->assertSame(TMemoryCache::SIZE_NOT_COMPUTED, $cache->pubGetCurrentSizeDirect(),
			'Initial _currentSize must be SIZE_NOT_COMPUTED when MaximumSize is 0.');
	}

	public function testGetCurrentSizeComputesOnDemandWhenMaximumSizeActive(): void
	{
		$this->cache->setMaximumSize(99999);
		$this->cache->set('k', 'hello');
		$size = $this->cache->getCurrentSize();
		$this->assertGreaterThan(0, $size,
			'getCurrentSize() must return a positive byte count after a write with MaximumSize active.');
	}

	public function testGetCurrentSizeZeroAfterFlushWithMaximumSize(): void
	{
		$this->cache->setMaximumSize(99999);
		$this->cache->set('k', 'hello');
		$this->cache->flush();
		$this->assertSame(0, $this->cache->getCurrentSize(),
			'getCurrentSize() must return 0 after flush() with MaximumSize active.');
	}

	// ── TCacheSizeTrait — isOverCapacity() ────────────────────────────────────────

	public function testIsOverCapacityFalseWhenMaximumSizeZero(): void
	{
		// MaximumSize=0 means unlimited; isOverCapacity() must always return false.
		$this->cache->set('k', str_repeat('x', 500));
		$this->assertFalse($this->cache->isOverCapacity(),
			'isOverCapacity() must return false when MaximumSize is 0.');
	}

	public function testIsOverCapacityFalseWhenUnderLimit(): void
	{
		$this->cache->setMaximumSize(1_000_000);
		$this->cache->set('k', 'small value');
		$this->assertFalse($this->cache->isOverCapacity(),
			'isOverCapacity() must return false when the store is well under the limit.');
	}

	public function testIsOverCapacityTrueWhenOverLimit(): void
	{
		// Set a very large value with no size limit first, then adjust the internal
		// counters to simulate being over capacity without triggering eviction.
		$this->cache->setMaximumSize(999999);
		$this->cache->set('big', str_repeat('z', 200));
		// Force the size tracking to report a large current size and a tiny maximum.
		$this->cache->pubSetCurrentSizeDirect(999999 + 1);
		$this->cache->pubSetSizeFingerprintDirect($this->cache->pubComputeSizeFingerprint());
		// setMaximumSizeDirect bypasses enforceMaximumSize, so we can set a tiny limit
		// without triggering immediate eviction.
		// Call isOverCapacity() which reads the direct fields we just set.
		// We achieve this by checking the condition manually through the fingerprint path:
		// validateSizeCache() won't recompute because the fingerprint is fresh.
		$this->assertTrue($this->cache->isOverCapacity(),
			'isOverCapacity() must return true when _currentSize exceeds MaximumSize.');
	}

	// ── TCacheSizeTrait — LRU eviction ────────────────────────────────────────────

	public function testEvictionRemovesLeastRecentlyAccessedEntry(): void
	{
		// Use a payload large enough that only one entry fits within 150 bytes.
		$payload = str_repeat('x', 100);
		$this->cache->setMaximumSize(150);
		$this->cache->set('a', $payload); // A is written first (older access time)
		// A brief sleep is unacceptable; instead write B immediately after A.
		// microtime(true) resolution is sufficient for the two calls to differ.
		$this->cache->set('b', $payload); // B is newer, triggers eviction of A
		// After eviction A must be gone and B must survive.
		$this->assertFalse($this->cache->get('a'),
			'LRU eviction must remove the oldest entry (a) when the cache is over capacity.');
		$this->assertSame($payload, $this->cache->get('b'),
			'LRU eviction must preserve the most recently written entry (b).');
	}

	public function testComputeSizeFingerprintChangesWhenKeyAdded(): void
	{
		$fp1 = $this->cache->pubComputeSizeFingerprint();
		$this->cache->set('k', 'v');
		$fp2 = $this->cache->pubComputeSizeFingerprint();
		$this->assertNotSame($fp1, $fp2,
			'computeSizeFingerprint() must change when a key is added.');
	}

	public function testComputeSizeFingerprintStableWhenKeysUnchanged(): void
	{
		$this->cache->set('k', 'v');
		$fp1 = $this->cache->pubComputeSizeFingerprint();
		$fp2 = $this->cache->pubComputeSizeFingerprint();
		$this->assertSame($fp1, $fp2,
			'computeSizeFingerprint() must return the same value when the key set has not changed.');
	}

	public function testComputeCurrentSizeReflectsTotalBytes(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('k', 'hello');
		$size = $this->cache->pubComputeCurrentSize();
		$this->assertGreaterThan(0, $size,
			'computeCurrentSize() must return a positive byte count after a write.');
	}

	public function testValidateSizeCacheTriggersRecomputeOnFingerprintMismatch(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('k', 'v');
		// Corrupt the fingerprint so validateSizeCache() is forced to recompute.
		$this->cache->pubSetSizeFingerprintDirect('stale-fingerprint');
		$this->cache->pubSetCurrentSizeDirect(TMemoryCache::SIZE_NOT_COMPUTED); // reset to sentinel
		$this->cache->pubValidateSizeCache();
		$this->assertGreaterThanOrEqual(0, $this->cache->pubGetCurrentSizeDirect(),
			'validateSizeCache() must recompute _currentSize when the fingerprint is stale.');
		$this->assertNotSame('stale-fingerprint', $this->cache->pubGetSizeFingerprintDirect(),
			'validateSizeCache() must update the fingerprint after recomputing.');
	}

	public function testSetMaximumSizeImmediatelyEvictsWhenBelowCurrentSize(): void
	{
		// Write entries with no size limit.
		for ($i = 0; $i < 5; $i++) {
			$this->cache->set('entry' . $i, str_repeat('y', 200));
		}
		// Now enforce a tiny limit — setMaximumSize() calls enforceMaximumSize() immediately.
		$this->cache->setMaximumSize(50);
		// After enforcement the current size must be within the limit.
		$currentSize = $this->cache->getCurrentSize();
		$this->assertLessThanOrEqual(50, $currentSize,
			'setMaximumSize() must immediately enforce the new limit by evicting LRU entries.');
	}

	public function testFlushResetsCurrentSizeToZero(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('k1', 'value1');
		$this->cache->set('k2', 'value2');
		$this->cache->flush();
		$this->assertSame(0, $this->cache->getCurrentSize(),
			'flush() must reset getCurrentSize() to 0 when MaximumSize is active.');
	}

	public function testDeleteDecrementsCurrentSize(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('keep', str_repeat('a', 100));
		$this->cache->set('remove', str_repeat('b', 100));
		$sizeWithBoth = $this->cache->getCurrentSize();
		$this->cache->delete('remove');
		$sizeAfterDelete = $this->cache->getCurrentSize();
		$this->assertLessThan($sizeWithBoth, $sizeAfterDelete,
			'delete() must decrement getCurrentSize() when MaximumSize is active.');
	}

	public function testGetExpiredEntryDecrementsCurrentSizeWhenMaximumSizeActive(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('exp', str_repeat('x', 100), 10); // expires at 1_000_010
		$this->cache->set('keep', str_repeat('y', 100), 0); // never expires

		$sizeWithBoth = $this->cache->getCurrentSize();
		$this->assertGreaterThan(0, $sizeWithBoth);

		// Advance past expiry — getValue() removes the expired entry and must decrement size.
		$this->cache->fakeNow = 1_000_011;
		$this->assertFalse($this->cache->get('exp'));

		$sizeAfterExpiry = $this->cache->getCurrentSize();
		$this->assertLessThan($sizeWithBoth, $sizeAfterExpiry,
			'Accessing an expired entry with MaximumSize active must decrement getCurrentSize().');
		$this->assertGreaterThan(0, $sizeAfterExpiry,
			'The surviving entry must still contribute to getCurrentSize().');
	}

	public function testGetExpiredEntryKeepsFingerprintConsistentWithStore(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('exp', 'payload', 5); // expires at 1_000_005

		// Advance past expiry and trigger the expiry removal.
		$this->cache->fakeNow = 1_000_006;
		$this->assertFalse($this->cache->get('exp'));

		// The fingerprint must now match the (empty) store so that the next
		// validateSizeCache() does NOT trigger a redundant full recompute.
		$expectedFp = $this->cache->pubComputeSizeFingerprint();
		$this->assertSame($expectedFp, $this->cache->pubGetSizeFingerprintDirect(),
			'After expiry removal, _sizeFingerprint must match the current store state.');
	}

	// ── TCacheSizeTrait — oversized item rejection ────────────────────────────────

	public function testSetValueThrowsWhenItemExceedsMaximumSize(): void
	{
		$this->cache->setMaximumSize(10);
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->cache->set('big', str_repeat('x', 100));
	}

	public function testSetValueDoesNotWriteEntryWhenItemExceedsMaximumSize(): void
	{
		// TCache::set() passes [$value, $dependency] as a raw PHP array to setValue().
		// TMemoryCache measures strlen(serialize([$value, null])).
		// serialize(['ok', null]) ≈ 25 bytes; serialize([str_repeat('x',100), null]) ≈ 125 bytes.
		// MaximumSize=50 fits 'ok' but rejects the 100-char string.
		$this->cache->setMaximumSize(50);
		$this->cache->set('small', 'ok');
		try {
			$this->cache->set('big', str_repeat('x', 100));
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
			// expected — verify the oversized entry was never written
		}
		$this->assertFalse($this->cache->get('big'),
			'An oversized entry must not appear in the cache after rejection.');
		$this->assertSame('ok', $this->cache->get('small'),
			'Existing entries must survive an oversized-item rejection.');
	}

	public function testSetValueAllowsItemExactlyAtMaximumSize(): void
	{
		// assertItemFitsMaximumSize uses strict >, so an item whose measured size
		// equals MaximumSize exactly must succeed (not throw).
		// TCache::set() passes [$value, $dependency] as a raw PHP array to setValue().
		// TMemoryCache::setValue() measures strlen(serialize([$value, null])) for non-string $value,
		// but since setValue() receives the array directly, the measurement is always
		// strlen(serialize([$value, null])).
		$value = 'x';
		$exactSize = strlen(serialize([$value, null])); // e.g. 24 bytes
		$this->cache->setMaximumSize($exactSize);
		$this->assertTrue($this->cache->set('exact', $value),
			'set() must succeed when the item\'s serialized size equals MaximumSize exactly (strict > check).');
		$this->assertSame($value, $this->cache->get('exact'),
			'An item exactly at MaximumSize must survive and be retrievable after the write.');
	}

	public function testSetValueWithNoMaximumSizeNeverThrows(): void
	{
		// MaximumSize = 0 means unlimited; assertItemFitsMaximumSize must be a no-op.
		$this->assertSame(0, $this->cache->getMaximumSize());
		$this->assertTrue($this->cache->set('large', str_repeat('x', 1_000_000)),
			'set() must succeed for any payload when MaximumSize is 0 (unlimited).');
	}
}
