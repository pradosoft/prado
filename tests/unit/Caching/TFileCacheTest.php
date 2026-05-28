<?php

/**
 * TFileCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICacheSize;
use Prado\Caching\TCache;
use Prado\Caching\TFileCache;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;

// ── Helper class ───────────────────────────────────────────────────────────────

/**
 * Exposes protected internals and overrides now() for clock-controlled TTL tests.
 * Using this subclass eliminates all sleep() calls from the test suite, making
 * expiry tests instant and deterministic.
 */
class TFileCacheTestAccessor extends TFileCache
{
	/** @var int|null when set, now() returns this value instead of time() */
	public ?int $fakeNow = null;

	/**
	 * @var callable|null when set, hashToken() delegates to this callable instead
	 *   of the default sha1 implementation. Allows tests to inject a bad hashToken
	 *   after init() has already succeeded with the normal implementation.
	 */
	public $hashTokenCallback = null;

	protected function now(): int
	{
		return $this->fakeNow ?? parent::now();
	}

	protected function hashToken(string $token): string
	{
		return $this->hashTokenCallback !== null
			? ($this->hashTokenCallback)($token)
			: parent::hashToken($token);
	}

	public function pubNow(): int
	{
		return $this->now();
	}

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}

	public function pubHashToken(string $token): string
	{
		return $this->hashToken($token);
	}

	public function pubPathFor(string $key): string
	{
		return $this->pathFor($key);
	}

	public function pubSerialize(mixed $value): string
	{
		return $this->serialize($value);
	}

	public function pubUnserialize(string $value): mixed
	{
		return $this->unserialize($value);
	}

	public function pubGetContents(string $filePath): string|false
	{
		return $this->getContents($filePath);
	}

	public function pubPutContents(string $filePath, string $data): int|false
	{
		return $this->putContents($filePath, $data);
	}

	public function pubUnlink(string $filePath): bool
	{
		return $this->unlink($filePath);
	}

	public function pubRename(string $srcFilePath, string $destFilePath): bool
	{
		return $this->rename($srcFilePath, $destFilePath);
	}

	public function pubChmod(string $filePath, int $mode): bool
	{
		return $this->chmod($filePath, $mode);
	}

	public function pubTempnam(string $dir, string $prefix): string|false
	{
		return $this->tempnam($dir, $prefix);
	}

	public function pubGetTempFilePrefixDirect(): string
	{
		return $this->getTempFilePrefixDirect();
	}

	public function pubIsFile(string $path): bool
	{
		return $this->isFile($path);
	}

	public function pubTouch(string $filePath): bool
	{
		return $this->touch($filePath);
	}

	public function pubComputeSizeFingerprint(): string
	{
		return $this->computeSizeFingerprint();
	}

	public function pubComputeCurrentSize(): int
	{
		return $this->computeCurrentSize();
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
}

// ── Fixture: identity hashToken subclass ───────────────────────────────────────

/**
 * A TFileCache subclass whose hashToken() returns its input unchanged.
 * Used to verify that init() rejects an identity hashToken() implementation.
 */
class TFileCacheIdentityHash extends TFileCache
{
	protected function hashToken(string $token): string
	{
		return $token;
	}
}

/**
 * A TFileCache subclass whose hashToken() returns a value containing a
 * forward-slash path separator.
 * Used to verify that init() rejects unsafe hashToken() implementations.
 */
class TFileCacheSlashHash extends TFileCache
{
	protected function hashToken(string $token): string
	{
		return 'sub/dir/' . sha1($token);
	}
}

/**
 * A TFileCache subclass whose hashToken() returns a value containing a
 * backslash path separator.
 * Used to verify that init() rejects unsafe hashToken() implementations.
 */
class TFileCacheBackslashHash extends TFileCache
{
	protected function hashToken(string $token): string
	{
		return 'sub\\dir\\' . sha1($token);
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TFileCacheTest class.
 *
 * Comprehensive unit tests for TFileCache: directory configuration, init(),
 * set/get/add/delete/flush, TTL semantics (clock-controlled, no sleep()),
 * atomic writes, corrupt-file handling, ArrayAccess, DefaultTtl fallback,
 * protected helper methods (touch, pathFor, hashToken, now), serialization
 * exclusions via _getZappableSleepProps, TCacheSizeTrait integration
 * (MaximumSize, getCurrentSize, LRU mtime-based eviction), and edge cases.
 *
 * @package Prado\Tests\Unit\Caching
 */
class TFileCacheTest extends PHPUnit\Framework\TestCase
{
	private static string $cacheDir;

	/** @var TFileCacheTestAccessor */
	private TFileCacheTestAccessor $cache;

	private ?TApplication $app = null;

	public static function setUpBeforeClass(): void
	{
		self::$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_filecache_test_' . getmypid();
		if (!is_dir(self::$cacheDir)) {
			mkdir(self::$cacheDir, 0o755, true);
		}
	}

	public static function tearDownAfterClass(): void
	{
		// Remove all test cache files.
		if (is_dir(self::$cacheDir)) {
			foreach (glob(self::$cacheDir . '/*') ?: [] as $f) {
				@unlink($f);
			}
			@rmdir(self::$cacheDir);
		}
	}

	protected function setUp(): void
	{
		$basePath = __DIR__ . '/mockapp';
		$this->app = new TApplication($basePath);

		$this->cache = new TFileCacheTestAccessor(self::$cacheDir);
		$this->cache->setPrimaryCache(false);
		$this->cache->init(null);
	}

	protected function tearDown(): void
	{
		// Flush between tests to prevent cross-test interference.
		$this->cache->flush();
		$this->app = null;
	}

	// ── Construction ─────────────────────────────────────────────────────────

	public function testIsInstanceOfTFileCache(): void
	{
		$this->assertInstanceOf(TFileCache::class, $this->cache);
	}

	public function testIsInstanceOfTCache(): void
	{
		$this->assertInstanceOf(TCache::class, $this->cache);
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
		$this->assertSame(ICacheSize::SIZE_NOT_COMPUTED, TFileCache::SIZE_NOT_COMPUTED,
			'TFileCache::SIZE_NOT_COMPUTED must equal ICacheSize::SIZE_NOT_COMPUTED.');
	}

	public function testConstructWithNoArgsCreatesInstance(): void
	{
		$cache = new TFileCache();
		$this->assertInstanceOf(TFileCache::class, $cache);
		$this->assertSame('', $cache->getDirectory());
		$this->assertSame(0, $cache->getDefaultTtl());
	}

	public function testConstructWithDirectorySetsDirectory(): void
	{
		$this->assertSame(realpath(self::$cacheDir) ?: self::$cacheDir, $this->cache->getDirectory());
	}

	public function testConstructWithDefaultTtlSetsDefaultTtl(): void
	{
		$cache = new TFileCacheTestAccessor(self::$cacheDir, 300);
		$this->assertSame(300, $cache->getDefaultTtl());
	}

	// ── Directory property ────────────────────────────────────────────────────

	public function testGetSetDirectory(): void
	{
		$newDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'subdir';
		$this->cache->setDirectory($newDir);
		$this->assertSame(realpath($newDir) ?: $newDir, $this->cache->getDirectory());
		// Directory should have been created.
		$this->assertDirectoryExists($newDir);
		@rmdir($newDir);
	}

	public function testSetDirectoryCreatesDirectoryIfNotExists(): void
	{
		$newDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'autocreated_' . uniqid();
		$this->assertDirectoryDoesNotExist($newDir);

		$this->cache->setDirectory($newDir);

		$this->assertDirectoryExists($newDir);
		@rmdir($newDir);
	}

	public function testSetDirectoryCreatesNestedDirectoriesRecursively(): void
	{
		$newDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'level2';
		$this->assertDirectoryDoesNotExist($newDir);

		$this->cache->setDirectory($newDir);

		$this->assertDirectoryExists($newDir);
		@rmdir($newDir);
		@rmdir(self::$cacheDir . DIRECTORY_SEPARATOR . 'level1');
	}

	public function testSetDirectoryThrowsOnEmptyString(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->cache->setDirectory('');
	}

	// ── DefaultTtl property ───────────────────────────────────────────────────

	public function testGetSetDefaultTtl(): void
	{
		$this->cache->setDefaultTtl(600);
		$this->assertSame(600, $this->cache->getDefaultTtl());
	}

	public function testSetDefaultTtlClampsNegativeToZero(): void
	{
		$this->cache->setDefaultTtl(-100);
		$this->assertSame(0, $this->cache->getDefaultTtl());
	}

	public function testSetDefaultTtlZeroAllowed(): void
	{
		$this->cache->setDefaultTtl(0);
		$this->assertSame(0, $this->cache->getDefaultTtl());
	}

	// ── TempFilePrefix property ───────────────────────────────────────────────

	public function testDefaultTempFilePrefixMatchesCacheFilePrefixConstant(): void
	{
		$cache = new TFileCacheTestAccessor();
		// The constructor seeds TempFilePrefix from static::CACHE_FILE_PREFIX.
		$this->assertSame(TFileCache::CACHE_FILE_PREFIX, $cache->getTempFilePrefix());
	}

	public function testDefaultTempFilePrefixIsSetViaDirectAccessor(): void
	{
		$cache = new TFileCacheTestAccessor();
		$this->assertSame(TFileCache::CACHE_FILE_PREFIX, $cache->pubGetTempFilePrefixDirect());
	}

	public function testGetSetTempFilePrefix(): void
	{
		$this->cache->setTempFilePrefix('.my-cache-');
		$this->assertSame('.my-cache-', $this->cache->getTempFilePrefix());
	}

	public function testTempFilePrefixUsedForTempFilesOnWrite(): void
	{
		$this->cache->setTempFilePrefix('.custom-prefix-');
		$this->cache->set('prefix_key', 'val');

		// No leftover temp files with the custom prefix.
		$leftover = glob(self::$cacheDir . DIRECTORY_SEPARATOR . '.custom-prefix-*') ?: [];
		$this->assertCount(0, $leftover, 'No temporary files should remain after a successful write.');
	}

	// ── init() ────────────────────────────────────────────────────────────────

	public function testInitUsesDefaultDirFromRuntimePathWhenNoneSet(): void
	{
		$basePath = __DIR__ . '/mockapp';
		$app = new TApplication($basePath);

		$cache = new TFileCacheTestAccessor();
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$expected = $app->getRuntimePath() . DIRECTORY_SEPARATOR . 'filecache';
		$this->assertSame($expected, $cache->getDirectory());
		$this->assertDirectoryExists($expected);

		// Cleanup.
		$cache->flush();
		@rmdir($expected);
	}

	public function testInitThrowsWhenDirectoryIsNotWritable(): void
	{
		if (!function_exists('posix_getuid') || posix_getuid() === 0) {
			$this->markTestSkipped('Test requires a non-root POSIX environment.');
		}

		$unwritable = self::$cacheDir . DIRECTORY_SEPARATOR . 'unwritable_' . uniqid();
		mkdir($unwritable, 0o000);

		try {
			// setDirectory succeeds because the directory already exists.
			// init() must throw because the directory is not writable.
			$cache = new TFileCacheTestAccessor($unwritable);
			$cache->setPrimaryCache(false);
			$this->expectException(TConfigurationException::class);
			$cache->init(null);
		} finally {
			chmod($unwritable, 0o755);
			@rmdir($unwritable);
		}
	}

	public function testInitThrowsWhenHashTokenReturnsInputUnchanged(): void
	{
		$cache = new TFileCacheIdentityHash(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testInitThrowsWhenHashTokenContainsForwardSlash(): void
	{
		$cache = new TFileCacheSlashHash(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testInitThrowsWhenHashTokenContainsBackslash(): void
	{
		$cache = new TFileCacheBackslashHash(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	// ── set() / get() ─────────────────────────────────────────────────────────

	public function testSetAndGetString(): void
	{
		$this->cache->set('key1', 'hello');
		$this->assertSame('hello', $this->cache->get('key1'));
	}

	public function testSetAndGetArray(): void
	{
		$data = ['a' => 1, 'b' => [2, 3]];
		$this->cache->set('key_array', $data);
		$this->assertSame($data, $this->cache->get('key_array'));
	}

	public function testSetAndGetObject(): void
	{
		$obj = new stdClass();
		$obj->value = 42;
		$this->cache->set('key_obj', $obj);
		$retrieved = $this->cache->get('key_obj');
		$this->assertInstanceOf(stdClass::class, $retrieved);
		$this->assertSame(42, $retrieved->value);
	}

	public function testGetReturnsFalseForMissingKey(): void
	{
		$this->assertFalse($this->cache->get('nonexistent_' . uniqid()));
	}

	public function testSetOverwritesExistingEntry(): void
	{
		$this->cache->set('overwrite_key', 'first');
		$this->cache->set('overwrite_key', 'second');
		$this->assertSame('second', $this->cache->get('overwrite_key'));
	}

	public function testSetWithEmptyValueAndZeroExpireDeletesEntry(): void
	{
		$this->cache->set('empty_key', 'original');
		// set() with empty value + expire=0 triggers delete path in TCache.
		$this->cache->set('empty_key', '');
		$this->assertFalse($this->cache->get('empty_key'));
	}

	public function testSetWithEmptyValueAndNonZeroExpireStoresIt(): void
	{
		// When expire > 0 and value is empty, set() still stores (the empty-value
		// delete shortcut in TCache only fires when expire == 0).
		$result = $this->cache->set('empty_expire_key', '', 3600);
		$this->assertTrue($result);
		// Verify the value is actually retrievable, not silently dropped.
		$this->assertSame('', $this->cache->get('empty_expire_key'));
	}

	// ── TTL / expiry (clock-controlled — no sleep()) ──────────────────────────

	public function testGetReturnsFalseForExpiredEntry(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('exp_key', 'value', 10); // expires at 1_000_010

		$this->assertSame('value', $this->cache->get('exp_key'));

		$this->cache->fakeNow = 1_000_011; // one second past expiry
		$this->assertFalse($this->cache->get('exp_key'));
	}

	public function testGetWithZeroExpireNeverExpires(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('persist_key', 'persist', 0);

		$this->cache->fakeNow = 2_000_000; // far in the future
		$this->assertSame('persist', $this->cache->get('persist_key'));
	}

	public function testDefaultTtlAppliedWhenExpireIsZero(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->setDefaultTtl(3600);
		$this->cache->set('default_ttl_key', 'val', 0); // expires at 1_003_600

		$this->cache->fakeNow = 1_003_599; // one second before expiry
		$this->assertSame('val', $this->cache->get('default_ttl_key'));

		$this->cache->fakeNow = 1_003_601; // one second past expiry
		$this->assertFalse($this->cache->get('default_ttl_key'));
	}

	public function testExplicitExpireOverridesDefaultTtl(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->setDefaultTtl(3600);
		$this->cache->set('explicit_exp', 'val', 10); // expires at 1_000_010, not 1_003_600

		$this->cache->fakeNow = 1_000_009;
		$this->assertSame('val', $this->cache->get('explicit_exp'));

		$this->cache->fakeNow = 1_000_011;
		$this->assertFalse($this->cache->get('explicit_exp'));
	}

	// ── add() ────────────────────────────────────────────────────────────────

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

		$this->cache->fakeNow = 1_000_011; // past expiry
		$result = $this->cache->add('add_exp_key', 'second');
		$this->assertTrue($result);
		$this->assertSame('second', $this->cache->get('add_exp_key'));
	}

	public function testAddReturnsFalseWhenValueIsEmptyAndExpireZero(): void
	{
		// TCache::add() returns false without delegating when value is empty
		// and expire == 0.
		$result = $this->cache->add('empty_add_key', '');
		$this->assertFalse($result);
	}

	// ── delete() ─────────────────────────────────────────────────────────────

	public function testDeleteRemovesExistingEntry(): void
	{
		$this->cache->set('del_key', 'value');
		$this->cache->delete('del_key');
		$this->assertFalse($this->cache->get('del_key'));
	}

	public function testDeleteReturnsTrueWhenEntryAbsent(): void
	{
		$result = $this->cache->delete('never_set_' . uniqid());
		$this->assertTrue($result);
	}

	// ── flush() ───────────────────────────────────────────────────────────────

	public function testFlushRemovesAllEntries(): void
	{
		$this->cache->set('f1', 'v1');
		$this->cache->set('f2', 'v2');
		$this->cache->set('f3', 'v3');

		$this->cache->flush();

		$this->assertFalse($this->cache->get('f1'));
		$this->assertFalse($this->cache->get('f2'));
		$this->assertFalse($this->cache->get('f3'));
	}

	public function testFlushReturnsTrueOnSuccess(): void
	{
		$this->cache->set('flush_key', 'val');
		$result = $this->cache->flush();
		$this->assertTrue($result);
	}

	public function testFlushOnEmptyDirReturnsTrue(): void
	{
		$emptyDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'empty_' . uniqid();
		mkdir($emptyDir, 0o755, true);

		$cache = new TFileCacheTestAccessor($emptyDir);
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$result = $cache->flush();

		$this->assertTrue($result);
		@rmdir($emptyDir);
	}

	public function testFlushReturnsTrueWhenDirectoryDoesNotExist(): void
	{
		$ghostDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'ghost_' . uniqid();
		mkdir($ghostDir, 0o755, true);

		$cache = new TFileCacheTestAccessor($ghostDir);
		$cache->setPrimaryCache(false);
		$cache->init(null);

		@rmdir($ghostDir); // Remove after init so flush sees a missing dir.

		$result = $cache->flush();
		$this->assertTrue($result);
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

	// ── Corrupt / malformed cache files ───────────────────────────────────────

	public function testGetReturnsFalseOnEmptyFile(): void
	{
		$key = 'corrupt_empty';
		$this->cache->set($key, 'val');

		// Target the specific cache file for this key rather than globbing the
		// entire directory, which would corrupt unrelated test entries.
		$filePath = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey($key));
		file_put_contents($filePath, '');

		$this->assertFalse($this->cache->get($key));
	}

	public function testGetReturnsFalseOnGarbageFile(): void
	{
		$key = 'corrupt_garbage';
		$this->cache->set($key, 'val');

		$filePath = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey($key));
		file_put_contents($filePath, 'THIS IS NOT VALID SERIALIZED DATA !!!');

		$this->assertFalse($this->cache->get($key));
	}

	public function testGetReturnsFalseWhenSerializedArrayMissingKeys(): void
	{
		$key = 'corrupt_keys';
		$this->cache->set($key, 'val');

		// Write a valid serialized array but without the required CACHE_VALUE
		// and CACHE_EXPIRED keys.
		$filePath = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey($key));
		file_put_contents($filePath, serialize(['x' => 1, 'y' => 2]));

		$this->assertFalse($this->cache->get($key));
	}

	// ── ArrayAccess interface ─────────────────────────────────────────────────

	public function testOffsetSetAndOffsetGet(): void
	{
		$this->cache['arr_key'] = 'arr_value';
		$this->assertSame('arr_value', $this->cache['arr_key']);
	}

	public function testOffsetExistsTrueWhenPresent(): void
	{
		$this->cache['exists_key'] = 'exists_value';
		$this->assertTrue(isset($this->cache['exists_key']));
	}

	public function testOffsetExistsFalseWhenAbsent(): void
	{
		$this->assertFalse(isset($this->cache['missing_' . uniqid()]));
	}

	public function testOffsetUnsetRemovesEntry(): void
	{
		$this->cache['unset_key'] = 'unset_val';
		unset($this->cache['unset_key']);
		$this->assertFalse(isset($this->cache['unset_key']));
	}

	// ── KeyPrefix ────────────────────────────────────────────────────────────

	public function testKeyPrefixIsolatesNamespaces(): void
	{
		$this->cache->setKeyPrefix('ns1_');
		$this->cache->set('shared_key', 'ns1_value');

		$this->cache->setKeyPrefix('ns2_');
		$this->assertFalse($this->cache->get('shared_key'));

		$this->cache->setKeyPrefix('ns1_');
		$this->assertSame('ns1_value', $this->cache->get('shared_key'));
	}

	// ── PrimaryCache ─────────────────────────────────────────────────────────

	public function testGetSetPrimaryCache(): void
	{
		$this->cache->setPrimaryCache(true);
		$this->assertTrue($this->cache->getPrimaryCache());
		$this->cache->setPrimaryCache(false);
		$this->assertFalse($this->cache->getPrimaryCache());
	}

	// ── Cache file is atomic (temp + rename) ──────────────────────────────────

	public function testCacheFilesUseHashedNames(): void
	{
		// After set, there should be exactly one .cache file.
		$this->cache->flush();
		$this->cache->set('atomic_key', 'atomic_value');

		$files = glob(self::$cacheDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
		$this->assertCount(1, $files);

		// File name should be sha1 of the unique key, NOT the raw key.
		$basename = basename($files[0], '.cache');
		$this->assertSame(40, strlen($basename), 'Cache file name should be a 40-char SHA-1 hex.');
	}

	public function testNoTempFilesLeftAfterWrite(): void
	{
		$this->cache->set('temp_key', 'temp_value');

		$tempFiles = glob(self::$cacheDir . DIRECTORY_SEPARATOR . $this->cache->getTempFilePrefix() . '*') ?: [];
		$this->assertCount(0, $tempFiles, 'No temporary files should remain after a successful write.');
	}

	// ── Expired file is cleaned up on getValue ────────────────────────────────

	public function testExpiredFileIsDeletedOnGet(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('delete_on_expire', 'v', 10);

		$files = glob(self::$cacheDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
		$this->assertCount(1, $files);

		$this->cache->fakeNow = 1_000_011; // past expiry
		$this->assertFalse($this->cache->get('delete_on_expire'));

		// The expired file should have been removed.
		$filesAfter = glob(self::$cacheDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
		$this->assertCount(0, $filesAfter);
	}

	// ── Cache with various value types ────────────────────────────────────────

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
		// null with expire=0 triggers delete path in TCache::set(); use expire>0
		$this->cache->set('null_key', null, 3600);
		$this->assertNull($this->cache->get('null_key'));
	}

	public function testStoresDeepNestedArray(): void
	{
		$deep = ['a' => ['b' => ['c' => ['d' => 'deep_value']]]];
		$this->cache->set('deep_key', $deep);
		$this->assertSame($deep, $this->cache->get('deep_key'));
	}

	// ── Protected helpers: now() ──────────────────────────────────────────────

	public function testNowReturnsAnIntegerCloseToCurrentTime(): void
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

	// ── Protected helpers: isFile() ───────────────────────────────────────────

	public function testIsFileTrueForExistingFile(): void
	{
		$path = self::$cacheDir . DIRECTORY_SEPARATOR . 'testfile_' . uniqid();
		file_put_contents($path, 'x');

		$this->assertTrue($this->cache->pubIsFile($path));

		@unlink($path);
	}

	public function testIsFileFalseForMissingPath(): void
	{
		$this->assertFalse($this->cache->pubIsFile(self::$cacheDir . DIRECTORY_SEPARATOR . 'no_such_file'));
	}

	public function testIsFileFalseForDirectory(): void
	{
		$this->assertFalse($this->cache->pubIsFile(self::$cacheDir));
	}

	// ── Protected helpers: tempnam() ─────────────────────────────────────────

	public function testTempnamCreatesFileInDirectory(): void
	{
		$path = $this->cache->pubTempnam(self::$cacheDir, '.prado-test-');

		$this->assertNotFalse($path);
		$this->assertFileExists($path);
		// Use the cache's realpath-resolved directory to handle platform symlinks
		// (e.g. macOS resolves /var → /private/var).
		$this->assertStringStartsWith($this->cache->getDirectory(), $path);

		@unlink($path);
	}

	// ── Protected helpers: serialize() / unserialize() ───────────────────────

	public function testSerializeProducesStringUnserializableBackToOriginal(): void
	{
		$original = ['key' => 'value', 'nested' => [1, 2, 3]];
		$serialized = $this->cache->pubSerialize($original);

		$this->assertIsString($serialized);
		$this->assertSame($original, $this->cache->pubUnserialize($serialized));
	}

	public function testUnserializeReturnsFalseOnInvalidInput(): void
	{
		$result = $this->cache->pubUnserialize('this is not serialized data');
		$this->assertFalse($result);
	}

	// ── Protected helpers: getContents() / putContents() ─────────────────────

	public function testPutContentsAndGetContentsRoundtrip(): void
	{
		$path = self::$cacheDir . DIRECTORY_SEPARATOR . 'rw_test_' . uniqid();
		$data = 'hello world';

		$written = $this->cache->pubPutContents($path, $data);
		$this->assertIsInt($written);
		$this->assertSame(strlen($data), $written);

		$read = $this->cache->pubGetContents($path);
		$this->assertSame($data, $read);

		@unlink($path);
	}

	public function testGetContentsReturnsFalseForMissingFile(): void
	{
		$result = $this->cache->pubGetContents(self::$cacheDir . DIRECTORY_SEPARATOR . 'no_such_file_' . uniqid());
		$this->assertFalse($result);
	}

	// ── Protected helpers: unlink() ──────────────────────────────────────────

	public function testUnlinkDeletesExistingFile(): void
	{
		$path = self::$cacheDir . DIRECTORY_SEPARATOR . 'unlink_test_' . uniqid();
		file_put_contents($path, 'x');

		$result = $this->cache->pubUnlink($path);

		$this->assertTrue($result);
		$this->assertFileDoesNotExist($path);
	}

	public function testUnlinkReturnsFalseForMissingFile(): void
	{
		$result = $this->cache->pubUnlink(self::$cacheDir . DIRECTORY_SEPARATOR . 'no_such_file_' . uniqid());
		$this->assertFalse($result);
	}

	// ── Protected helpers: rename() ───────────────────────────────────────────

	public function testRenameMovesFile(): void
	{
		$src = self::$cacheDir . DIRECTORY_SEPARATOR . 'rename_src_' . uniqid();
		$dst = self::$cacheDir . DIRECTORY_SEPARATOR . 'rename_dst_' . uniqid();
		file_put_contents($src, 'rename_content');

		$result = $this->cache->pubRename($src, $dst);

		$this->assertTrue($result);
		$this->assertFileDoesNotExist($src);
		$this->assertFileExists($dst);
		$this->assertSame('rename_content', file_get_contents($dst));

		@unlink($dst);
	}

	// ── Protected helpers: chmod() ────────────────────────────────────────────

	public function testChmodSetsFilePermissions(): void
	{
		if (!function_exists('posix_getuid')) {
			$this->markTestSkipped('Test requires a POSIX environment.');
		}

		$path = self::$cacheDir . DIRECTORY_SEPARATOR . 'chmod_test_' . uniqid();
		file_put_contents($path, 'x');

		$result = $this->cache->pubChmod($path, 0o600);

		$this->assertTrue($result);
		$perms = fileperms($path) & 0o777;
		$this->assertSame(0o600, $perms);

		@unlink($path);
	}

	// ── Protected helpers: generateUniqueKey() / hashToken() / pathFor() ───────

	public function testHashTokenReturnsSha1(): void
	{
		$token = 'some_cache_key';
		$hash = $this->cache->pubHashToken($token);

		$this->assertSame(40, strlen($hash));
		$this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $hash);
		$this->assertSame(sha1($token), $hash);
	}

	public function testGenerateUniqueKeyUsesSha1AndKeyPrefix(): void
	{
		$prefix = $this->cache->getKeyPrefix();
		$rawKey = 'my_key';
		$unique = $this->cache->pubGenerateUniqueKey($rawKey);

		$this->assertSame(sha1($prefix . $rawKey), $unique,
			'generateUniqueKey must produce sha1(prefix . key).');
	}

	public function testGenerateUniqueKeyThrowsWhenHashTokenReturnsIdentity(): void
	{
		// init() runs with the default sha1 hashToken and succeeds.
		// The callback is then swapped in so that generateUniqueKey() receives an
		// identity function, exercising the per-call guard independently of init().
		$cache = new TFileCacheTestAccessor(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$cache->hashTokenCallback = fn(string $token) => $token;

		$this->expectException(TConfigurationException::class);
		$cache->pubGenerateUniqueKey('any-cache-key');
	}

	public function testGenerateUniqueKeyThrowsWhenHashTokenContainsPathSeparator(): void
	{
		// init() runs with the default sha1 hashToken and succeeds.
		// The callback is then swapped in to inject a "/" separator, exercising
		// the per-call path-traversal guard independently of init().
		$cache = new TFileCacheTestAccessor(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$cache->init(null);

		$cache->hashTokenCallback = fn(string $token) => 'bad/hash/' . sha1($token);

		$this->expectException(TConfigurationException::class);
		$cache->pubGenerateUniqueKey('any-cache-key');
	}

	public function testPathForReturnsCorrectPath(): void
	{
		// pathFor receives an already-hashed key and appends ".cache" to it.
		$key = sha1('some_unique_key');
		$path = $this->cache->pubPathFor($key);
		$dir = $this->cache->getDirectory();
		$expected = $dir . DIRECTORY_SEPARATOR . $key . '.cache';

		$this->assertSame($expected, $path);
	}

	// ── Protected helper: touch() ─────────────────────────────────────────────────

	public function testTouchUpdatesFileMtime(): void
	{
		$path = self::$cacheDir . DIRECTORY_SEPARATOR . 'touch_test_' . uniqid();
		file_put_contents($path, 'content');
		// Backdate the file's mtime by 10 seconds so the before/after comparison is reliable.
		$oldMtime = time() - 10;
		touch($path, $oldMtime);
		clearstatcache(true, $path);

		$result = $this->cache->pubTouch($path);

		$this->assertTrue($result, 'touch() must return true on success.');
		clearstatcache(true, $path);
		$newMtime = filemtime($path);
		$this->assertGreaterThanOrEqual($oldMtime, $newMtime,
			'touch() must update mtime to the current time, which must be >= the backdated mtime.');

		@unlink($path);
	}

	// ── _getZappableSleepProps — serialization ────────────────────────────────────

	public function testSerializationPreservesDirectory(): void
	{
		$serialized = serialize($this->cache);
		$restored = unserialize($serialized);
		$this->assertSame($this->cache->getDirectory(), $restored->getDirectory(),
			'The Directory path must survive serialization.');
	}

	public function testSerializationPreservesDefaultTtlWhenNonZero(): void
	{
		$this->cache->setDefaultTtl(3600);
		$serialized = serialize($this->cache);
		$restored = unserialize($serialized);
		$this->assertSame(3600, $restored->getDefaultTtl(),
			'A non-zero DefaultTtl must survive serialization.');
	}

	public function testSerializationExcludesDefaultTtlWhenZero(): void
	{
		// DefaultTtl=0 is the default and must be zapped from the payload.
		$this->cache->setDefaultTtl(0);
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_defaultTtl', $serialized,
			'DefaultTtl=0 must be excluded from the serialized payload.');
	}

	public function testSerializationExcludesTempFilePrefixWhenDefault(): void
	{
		// The default TempFilePrefix equals CACHE_FILE_PREFIX and must be zapped.
		$this->assertSame(TFileCache::CACHE_FILE_PREFIX, $this->cache->getTempFilePrefix());
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_tempFilePrefix', $serialized,
			'The default TempFilePrefix must be excluded from the serialized payload.');
	}

	public function testSerializationPreservesTempFilePrefixWhenCustom(): void
	{
		$this->cache->setTempFilePrefix('.custom-prefix-');
		$serialized = serialize($this->cache);
		$restored = unserialize($serialized);
		$this->assertSame('.custom-prefix-', $restored->getTempFilePrefix(),
			'A non-default TempFilePrefix must survive serialization.');
	}

	public function testSerializationExcludesCurrentSizeAlways(): void
	{
		$this->cache->setMaximumSize(99999);
		$this->cache->set('k', 'v');
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_currentSize', $serialized,
			'_currentSize must always be excluded from the serialized payload.');
	}

	public function testSerializationExcludesSizeFingerprintAlways(): void
	{
		$this->cache->setMaximumSize(99999);
		$this->cache->set('k', 'v');
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_sizeFingerprint', $serialized,
			'_sizeFingerprint must always be excluded from the serialized payload.');
	}

	public function testSerializationExcludesMaximumSizeWhenZero(): void
	{
		// MaximumSize=0 is the default and must be zapped.
		$this->assertSame(0, $this->cache->getMaximumSize());
		$serialized = serialize($this->cache);
		$this->assertStringNotContainsString('_maximumSize', $serialized,
			'MaximumSize=0 must be excluded from the serialized payload.');
	}

	public function testSerializationPreservesMaximumSizeWhenNonZero(): void
	{
		$this->cache->setMaximumSize(1024);
		$serialized = serialize($this->cache);
		$restored = unserialize($serialized);
		$this->assertSame(1024, $restored->getMaximumSize(),
			'A non-zero MaximumSize must survive serialization.');
	}

	// ── TCacheSizeTrait — MaximumSize property ────────────────────────────────────

	public function testFileCacheMaximumSizeDefaultsToZero(): void
	{
		$cache = new TFileCacheTestAccessor();
		$this->assertSame(0, $cache->getMaximumSize(),
			'MaximumSize must default to 0 (unlimited).');
	}

	public function testFileCacheSetMaximumSizeAndGet(): void
	{
		$this->cache->setMaximumSize(1024);
		$this->assertSame(1024, $this->cache->getMaximumSize());
	}

	public function testFileCacheSetMaximumSizeNegativeClampedToZero(): void
	{
		$this->cache->setMaximumSize(-100);
		$this->assertSame(0, $this->cache->getMaximumSize(),
			'Negative MaximumSize must be clamped to 0.');
	}

	public function testFileCacheSetMaximumSizeZeroIsAllowed(): void
	{
		$this->cache->setMaximumSize(1024);
		$this->cache->setMaximumSize(0);
		$this->assertSame(0, $this->cache->getMaximumSize());
	}

	public function testFileCacheSetMaximumSizeFromString(): void
	{
		$this->cache->setMaximumSize('2048');
		$this->assertSame(2048, $this->cache->getMaximumSize(),
			'setMaximumSize() must accept a string integer.');
	}

	// ── TCacheSizeTrait — getCurrentSize() ────────────────────────────────────────

	public function testGetCurrentSizeReturnsSizeNotComputedWhenNoMaximumSize(): void
	{
		// MaximumSize is 0 by default; size tracking is inactive, sentinel stays SIZE_NOT_COMPUTED.
		// Access the raw field directly to confirm it has never been set.
		$cache = new TFileCacheTestAccessor();
		$this->assertSame(TFileCache::SIZE_NOT_COMPUTED, $cache->pubGetCurrentSizeDirect(),
			'Initial _currentSize must be SIZE_NOT_COMPUTED when MaximumSize is 0.');
	}

	public function testGetCurrentSizeAfterWriteReflectsDiskSize(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('k', str_repeat('x', 100));
		$size = $this->cache->getCurrentSize();
		$this->assertGreaterThan(0, $size,
			'getCurrentSize() must return a positive byte count after a write with MaximumSize active.');
	}

	public function testGetCurrentSizeZeroAfterFlush(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('k', str_repeat('x', 100));
		$this->cache->flush();
		$this->assertSame(0, $this->cache->getCurrentSize(),
			'getCurrentSize() must return 0 after flush() with MaximumSize active.');
	}

	// ── TCacheSizeTrait — LRU eviction ────────────────────────────────────────────

	public function testFileCacheEvictionRemovesOldestFileByMtime(): void
	{
		// Write entry A, then backdate its mtime to make it appear older than B.
		$payload = str_repeat('f', 200);
		$this->cache->set('a', $payload);
		$keyA = $this->cache->pubGenerateUniqueKey('a');
		$fileA = $this->cache->pubPathFor($keyA);
		// Backdate A so it sorts as LRU before B.
		touch($fileA, time() - 10);
		clearstatcache(true, $fileA);

		$this->cache->set('b', $payload);

		// setMaximumSize() enforces the limit immediately.
		// Choose a limit smaller than the combined on-disk size of both entries
		// but larger than one entry alone so only A is evicted.
		$oneEntrySize = (int) @filesize($fileA);
		if ($oneEntrySize <= 0) {
			$this->markTestSkipped('Could not determine cache file size for eviction test.');
		}
		$this->cache->setMaximumSize($oneEntrySize + 50);

		$this->assertFalse($this->cache->get('a'),
			'LRU eviction must remove the oldest file (a) when the cache is over capacity.');
		$this->assertSame($payload, $this->cache->get('b'),
			'LRU eviction must preserve the most recently written file (b).');
	}

	public function testFileCacheComputeSizeFingerprintChangesWhenFileAdded(): void
	{
		$fp1 = $this->cache->pubComputeSizeFingerprint();
		$this->cache->set('k', 'v');
		$fp2 = $this->cache->pubComputeSizeFingerprint();
		$this->assertNotSame($fp1, $fp2,
			'computeSizeFingerprint() must change when a cache file is added.');
	}

	public function testFileCacheComputeCurrentSizeReturnsPositiveAfterWrite(): void
	{
		$this->cache->set('k', str_repeat('x', 100));
		$size = $this->cache->pubComputeCurrentSize();
		$this->assertGreaterThan(0, $size,
			'computeCurrentSize() must return a positive byte count after a write.');
	}

	public function testFileCacheSetMaximumSizeImmediatelyEvictsWhenBelowCurrentSize(): void
	{
		// Write several entries with no size limit to accumulate on-disk data.
		for ($i = 0; $i < 4; $i++) {
			$this->cache->set('entry' . $i, str_repeat('z', 200));
		}
		// Determine the current on-disk size so we can set a limit below it.
		$currentSize = $this->cache->pubComputeCurrentSize();
		if ($currentSize <= 0) {
			$this->markTestSkipped('Could not determine current on-disk size for eviction test.');
		}
		// Enforce a limit that is much smaller than the accumulated data.
		$this->cache->setMaximumSize((int) ($currentSize / 4));
		// The enforcement must have reduced the total below the new limit.
		$sizeAfter = $this->cache->getCurrentSize();
		$this->assertLessThanOrEqual((int) ($currentSize / 4), $sizeAfter,
			'setMaximumSize() must immediately enforce the new limit by evicting LRU files.');
	}

	public function testFileCacheFlushResetsCurrentSizeToZeroWithMaximumSize(): void
	{
		$this->cache->setMaximumSize(999999);
		$this->cache->set('k1', 'value1');
		$this->cache->set('k2', 'value2');
		$this->cache->flush();
		$this->assertSame(0, $this->cache->getCurrentSize(),
			'flush() must reset getCurrentSize() to 0 when MaximumSize is active.');
	}

	public function testFileCacheIsOverCapacityFalseWhenMaximumSizeZero(): void
	{
		// MaximumSize=0 means unlimited; isOverCapacity() must always return false.
		$this->cache->set('k', str_repeat('x', 500));
		$this->assertFalse($this->cache->isOverCapacity(),
			'isOverCapacity() must return false when MaximumSize is 0.');
	}

	public function testFileCacheIsOverCapacityFalseWhenUnderLimit(): void
	{
		$this->cache->setMaximumSize(1_000_000);
		$this->cache->set('k', 'small value');
		$this->assertFalse($this->cache->isOverCapacity(),
			'isOverCapacity() must return false when the cache is well under the limit.');
	}

	public function testGetValueWithMaximumSizeActivePromotesEntryInLruOrder(): void
	{
		// Write entry A, then B.  Both are the same size so the LRU tiebreak is mtime.
		$payload = str_repeat('g', 200);
		$this->cache->set('a', $payload);

		// Backdate A's cache file so it appears older than B before any reads.
		$keyA = $this->cache->pubGenerateUniqueKey('a');
		$fileA = $this->cache->pubPathFor($keyA);
		touch($fileA, time() - 20);
		clearstatcache(true, $fileA);

		$this->cache->set('b', $payload);

		// Backdate B too, so it also appears old.
		$keyB = $this->cache->pubGenerateUniqueKey('b');
		$fileB = $this->cache->pubPathFor($keyB);
		touch($fileB, time() - 10);
		clearstatcache(true, $fileB);

		// READ entry A with MaximumSize active — getValue() must call touch() to
		// refresh A's mtime, promoting it above B in the LRU order.
		$this->cache->setMaximumSize(99999);
		$this->assertSame($payload, $this->cache->get('a'),
			'Entry A must still be present after enabling MaximumSize.');

		// Now set a limit small enough to force one eviction.
		$oneEntrySize = (int) @filesize($fileA);
		if ($oneEntrySize <= 0) {
			$this->markTestSkipped('Could not determine cache file size for LRU promotion test.');
		}
		$this->cache->setMaximumSize($oneEntrySize + 50);

		// B was not read after being backdated, so its mtime is older than A's (which
		// was refreshed by the touch() in getValue()). B must be the eviction victim.
		$this->assertFalse($this->cache->get('b'),
			'Entry B must have been evicted: it has the oldest mtime after A was read.');
		$this->assertSame($payload, $this->cache->get('a'),
			'Entry A must survive: getValue() promoted it via touch().');
	}

	// ── TCacheSizeTrait — oversized item rejection ────────────────────────────────

	public function testSetValueThrowsWhenItemExceedsMaximumSize(): void
	{
		$this->cache->setMaximumSize(10);
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->cache->set('big', str_repeat('x', 100));
	}

	public function testSetValueDoesNotWriteFileWhenItemExceedsMaximumSize(): void
	{
		// TCache::set() passes [$value, $dependency] as a raw PHP array to writeEntry().
		// writeEntry() wraps it in ['value' => $tcacheArray, 'expired' => 0] and serializes.
		// serialize(['value' => ['ok', null], 'expired' => 0]) ≈ 61 bytes.
		// serialize(['value' => [str_repeat('x',100), null], 'expired' => 0]) ≈ 161 bytes.
		// MaximumSize=100 fits 'ok' but rejects the 100-char string.
		$this->cache->setMaximumSize(100);
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
		// assertItemFitsMaximumSize uses strict >, so an item whose serialized size
		// equals MaximumSize exactly must succeed (not throw).
		// TCache::set() passes [$value, $dependency] as a raw PHP array to setValue(),
		// which in turn passes it to writeEntry(). writeEntry() wraps that array in
		// ['value' => $tcacheArray, 'expired' => $expiry] and serializes the whole
		// structure. Reproduce that exact layout to compute the true on-disk size.
		$value = 'x';
		$tcacheArray = [$value, null]; // the [$value, $dependency] pair TCache builds
		$entry = ['value' => $tcacheArray, 'expired' => 0]; // DefaultTtl=0 → expiry=0
		$exactSize = strlen(serialize($entry));
		$this->cache->setMaximumSize($exactSize);
		$this->assertTrue($this->cache->set('exact', $value),
			'set() must succeed when the serialized size equals MaximumSize exactly (strict > check).');
		$this->assertSame($value, $this->cache->get('exact'),
			'An item exactly at MaximumSize must survive and be retrievable after the write.');
	}

	public function testSetValueWithNoMaximumSizeNeverThrows(): void
	{
		// MaximumSize = 0 means unlimited; assertItemFitsMaximumSize must be a no-op.
		$this->assertSame(0, $this->cache->getMaximumSize());
		$this->assertTrue($this->cache->set('large', str_repeat('x', 10_000)),
			'set() must succeed for any payload when MaximumSize is 0 (unlimited).');
	}
}
