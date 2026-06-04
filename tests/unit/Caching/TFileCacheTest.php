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
use Prado\Util\Cron\TCronTaskInfo;


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

	/** @var TTestFileCache */
	private TTestFileCache $cache;

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

		$this->cache = new TTestFileCache(self::$cacheDir);
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
		$cache = new TTestFileCache(self::$cacheDir, 300);
		$this->assertSame(300, $cache->getDefaultTtl());
	}

	// ── Directory property ────────────────────────────────────────────────────

	public function testGetSetDirectory(): void
	{
		$newDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'subdir';
		$cache = new TTestFileCache(); // un-initialized: Directory is still settable
		$cache->setDirectory($newDir);
		$this->assertSame(realpath($newDir) ?: $newDir, $cache->getDirectory());
		// Directory should have been created.
		$this->assertDirectoryExists($newDir);
		@rmdir($newDir);
	}

	public function testSetDirectoryCreatesDirectoryIfNotExists(): void
	{
		$newDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'autocreated_' . uniqid();
		$this->assertDirectoryDoesNotExist($newDir);

		(new TTestFileCache())->setDirectory($newDir);

		$this->assertDirectoryExists($newDir);
		@rmdir($newDir);
	}

	public function testSetDirectoryCreatesNestedDirectoriesRecursively(): void
	{
		$newDir = self::$cacheDir . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'level2';
		$this->assertDirectoryDoesNotExist($newDir);

		(new TTestFileCache())->setDirectory($newDir);

		$this->assertDirectoryExists($newDir);
		@rmdir($newDir);
		@rmdir(self::$cacheDir . DIRECTORY_SEPARATOR . 'level1');
	}

	public function testSetDirectoryThrowsOnEmptyString(): void
	{
		$this->expectException(TConfigurationException::class);
		(new TTestFileCache())->setDirectory('');
	}

	public function testDirectoryCannotChangeAfterInit(): void
	{
		// The cache from setUp() is already initialized → Directory is frozen.
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->cache->setDirectory(self::$cacheDir . DIRECTORY_SEPARATOR . 'late');
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
		$cache = new TTestFileCache();
		// The constructor seeds TempFilePrefix from static::CACHE_FILE_PREFIX.
		$this->assertSame(TFileCache::CACHE_FILE_PREFIX, $cache->getTempFilePrefix());
	}

	public function testDefaultTempFilePrefixIsSetViaDirectAccessor(): void
	{
		$cache = new TTestFileCache();
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

		$cache = new TTestFileCache();
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
			$cache = new TTestFileCache($unwritable);
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
		$cache = new TTestFileCache(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$cache->hashTokenCallback = static fn (string $token): string => $token; // identity
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testInitThrowsWhenHashTokenContainsForwardSlash(): void
	{
		$cache = new TTestFileCache(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$cache->hashTokenCallback = static fn (string $token): string => 'sub/dir/' . sha1($token);
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testInitThrowsWhenHashTokenContainsBackslash(): void
	{
		$cache = new TTestFileCache(self::$cacheDir);
		$cache->setPrimaryCache(false);
		$cache->hashTokenCallback = static fn (string $token): string => 'sub\\dir\\' . sha1($token);
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

		$cache = new TTestFileCache($emptyDir);
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

		$cache = new TTestFileCache($ghostDir);
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

	public function testGetReturnsFalseWhenFileHasNoExpiryHeader(): void
	{
		$key = 'corrupt_keys';
		$this->cache->set($key, 'val');

		// Write content with no expiry-header newline; getSerializedValue() treats
		// it as malformed and returns a miss.
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
		$result = $this->cache->pubTime();
		$after = time();

		$this->assertGreaterThanOrEqual($before, $result);
		$this->assertLessThanOrEqual($after, $result);
	}

	public function testFakeNowOverridesNow(): void
	{
		$this->cache->fakeNow = 42;
		$this->assertSame(42, $this->cache->pubTime());
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
		$cache = new TTestFileCache(self::$cacheDir);
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
		$cache = new TTestFileCache(self::$cacheDir);
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
		$cache = new TTestFileCache();
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
		$cache = new TTestFileCache();
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

	public function testEvictionRemovesSoonestToExpireFirst(): void
	{
		// mtime mirrors the absolute expiry, so eviction orders by expiry.
		$payload = str_repeat('g', 200);
		$this->cache->set('soon', $payload, 10);      // expires sooner
		$this->cache->set('later', $payload, 100_000); // expires much later

		$oneEntrySize = (int) @filesize(
			$this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('soon'))
		);
		$this->assertGreaterThan(0, $oneEntrySize);

		// A limit fitting ~one entry forces eviction of the soonest-to-expire entry.
		$this->cache->setMaximumSize($oneEntrySize + 50);

		$this->assertFalse($this->cache->get('soon'),
			'The soonest-to-expire entry must be evicted first.');
		$this->assertSame($payload, $this->cache->get('later'),
			'The later-expiring entry must survive.');
	}

	public function testNeverExpiringEntryIsEvictedLast(): void
	{
		$payload = str_repeat('g', 200);
		$this->cache->set('forever', $payload);   // DefaultTtl 0 → never expires (mtime 0)
		$this->cache->set('soon', $payload, 10);  // expires soon

		$oneEntrySize = (int) @filesize(
			$this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('soon'))
		);
		$this->cache->setMaximumSize($oneEntrySize + 50);

		$this->assertFalse($this->cache->get('soon'),
			'The expiring entry is evicted before the never-expiring one.');
		$this->assertSame($payload, $this->cache->get('forever'),
			'A never-expiring entry is evicted last.');
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
		// The on-disk entry is "<expireAt>\n<serialized [$value,$dependency]>".
		// For 'ok' that is well under 100 bytes; for a 100-char string it exceeds 100.
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
		// assertItemFitsMaximumSize uses strict >, so an item whose on-disk size
		// equals MaximumSize exactly must succeed (not throw).
		// TCache::set() passes [$value, $dependency] to TSerializingCache::setValue(),
		// which serializes it and hands the string to TFileCache::setSerializedValue().
		// The on-disk content is "<expireAt>\n<serialized payload>" (expireAt=0 when
		// DefaultTtl=0). Reproduce that exact layout to compute the true on-disk size.
		$value = 'x';
		$tcacheArray = [$value, null]; // the [$value, $dependency] pair TCache builds
		$exactSize = strlen('0' . "\n" . serialize($tcacheArray));
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

	// ── Expired-file flushing / cron task ─────────────────────────────────────

	public function testFlushIntervalDefaultsTo60(): void
	{
		$this->assertSame(60, $this->cache->getFlushInterval());
	}

	public function testSetFlushIntervalClampsNegativeToZero(): void
	{
		$this->cache->setFlushInterval(-5);
		$this->assertSame(0, $this->cache->getFlushInterval());
		$this->cache->setFlushInterval(120);
		$this->assertSame(120, $this->cache->getFlushInterval());
	}

	public function testFlushCacheExpiredForceRemovesOnlyExpiredFiles(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('live', 'v');       // DefaultTtl 0 → never expires
		$this->cache->set('exp', 'v', 10);    // expires at 1_000_010

		$liveFile = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('live'));
		$expFile  = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('exp'));
		$this->assertTrue($this->cache->pubIsFile($liveFile));
		$this->assertTrue($this->cache->pubIsFile($expFile));

		$this->cache->fakeNow = 1_000_011; // past the expiry
		$this->cache->flushCacheExpired(true);

		$this->assertFalse($this->cache->pubIsFile($expFile), 'Expired file must be swept.');
		$this->assertTrue($this->cache->pubIsFile($liveFile), 'Never-expiring file must remain.');
	}

	public function testFlushCacheExpiredWithZeroIntervalSkipsWhenNotForced(): void
	{
		$this->cache->setFlushInterval(0);
		$this->cache->fakeNow = 1_000_000;
		$this->cache->set('exp', 'v', 10);
		$expFile = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('exp'));

		$this->cache->fakeNow = 1_000_011;
		$this->cache->flushCacheExpired(false); // interval 0 + not forced → no sweep

		$this->assertTrue($this->cache->pubIsFile($expFile),
			'With FlushInterval=0 and no force, the expired file must not be swept.');
	}

	public function testFxGetCronTaskInfosReturnsFileCacheTask(): void
	{
		$info = $this->cache->fxGetCronTaskInfos(null, null);
		$this->assertInstanceOf(TCronTaskInfo::class, $info);
		$this->assertSame('filecacheflush', $info->getName());
	}

	public function testDoFlushCacheExpiredHonorsInterval(): void
	{
		// With the default 60s interval and no recorded prior flush, doFlushCacheExpired()
		// performs the sweep on first call.
		$this->cache->fakeNow = 2_000_000;
		$this->cache->set('exp', 'v', 10);
		$expFile = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('exp'));

		$this->cache->fakeNow = 2_000_100; // past expiry and past the 60s interval
		$this->cache->doFlushCacheExpired();
		$this->assertFalse($this->cache->pubIsFile($expFile));
	}

	public function testFlushCacheExpiredThrottlesWithinInterval(): void
	{
		$this->cache->setFlushInterval(60);
		// First non-forced flush records the global-state timestamp.
		$this->cache->fakeNow = 3_000_000;
		$this->cache->flushCacheExpired(false);

		// An entry expires shortly after; a second non-forced flush within the
		// interval is throttled and must not sweep it.
		$this->cache->set('exp', 'v', 10); // expires at 3_000_010
		$expFile = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('exp'));
		$this->cache->fakeNow = 3_000_030; // past expiry, < 60s since the recorded flush
		$this->cache->flushCacheExpired(false);
		$this->assertTrue($this->cache->pubIsFile($expFile),
			'A non-forced flush within FlushInterval must be throttled.');

		// Once the interval elapses, the next non-forced flush sweeps.
		$this->cache->fakeNow = 3_000_061; // > 60s since the recorded flush
		$this->cache->flushCacheExpired(false);
		$this->assertFalse($this->cache->pubIsFile($expFile),
			'After FlushInterval elapses, the expired file is swept.');
	}

	// ── Expiry mirrored into mtime ────────────────────────────────────────────

	public function testWriteMirrorsExpiryIntoMtime(): void
	{
		$this->cache->fakeNow = 1_500_000;
		$this->cache->set('exp', 'v', 30); // expires at 1_500_030
		$file = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('exp'));
		clearstatcache(true, $file);
		$this->assertSame(1_500_030, filemtime($file), 'mtime mirrors the absolute expiry.');

		$this->cache->set('forever', 'v'); // DefaultTtl 0 → never expires
		$fileF = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('forever'));
		clearstatcache(true, $fileF);
		$this->assertSame(TFileCache::NEVER_EXPIRES_MTIME, filemtime($fileF),
			'A never-expiring entry stamps the sentinel mtime, not 0.');
	}

	public function testFlushCacheExpiredUsesMtimeWithoutReadingContents(): void
	{
		// Store a never-expiring entry (its header says expire=0), then mark it expired
		// via mtime only. The sweep deletes it from filesystem metadata alone.
		$this->cache->set('k', 'v');
		$file = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('k'));
		touch($file, time() - 100); // past mtime
		clearstatcache(true, $file);

		$this->cache->flushCacheExpired(true);
		$this->assertFalse($this->cache->pubIsFile($file),
			'The sweep removes a file with a past mtime regardless of its stored header.');
	}

	public function testStrayEpochMtimeFileIsSweptButRealNeverExpireSurvives(): void
	{
		// A legitimately never-expiring entry uses the sentinel mtime and survives.
		$this->cache->set('forever', 'v');
		$foreverFile = $this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('forever'));

		// A stray .cache file left at mtime 1 (e.g. a boolean-true modification time) is
		// NOT mistaken for never-expiring — the sentinel is >= 2 specifically to avoid that.
		$stray = $this->cache->getDirectory() . DIRECTORY_SEPARATOR . 'stray.cache';
		file_put_contents($stray, "0\nx");
		touch($stray, 1);
		clearstatcache(true, $stray);

		$this->cache->flushCacheExpired(true);

		$this->assertFalse($this->cache->pubIsFile($stray),
			'A stray epoch/bool-true mtime file must be swept.');
		$this->assertTrue($this->cache->pubIsFile($foreverFile),
			'A real never-expiring entry (sentinel mtime) must survive.');
	}

	// ── On-disk format ────────────────────────────────────────────────────────

	public function testOnDiskFormatIsExpiryHeaderThenSerializedPayload(): void
	{
		$this->cache->fakeNow = 1_500_000;
		$this->cache->set('fmt', 'the-value', 30); // expires at 1_500_030

		$raw = $this->cache->pubGetContents(
			$this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('fmt'))
		);
		$this->assertIsString($raw);
		$pos = strpos($raw, "\n");
		$this->assertNotFalse($pos, 'File must contain an expiry-header newline.');
		$this->assertSame('1500030', substr($raw, 0, $pos), 'First line is the absolute expiry timestamp.');
		// Remainder is the base-class serialized [value, dependency] payload.
		$this->assertSame(serialize(['the-value', null]), substr($raw, $pos + 1));
	}

	public function testOnDiskFormatNeverExpiresStoresZeroHeader(): void
	{
		$this->cache->set('forever', 'v'); // DefaultTtl 0 → never expires
		$raw = $this->cache->pubGetContents(
			$this->cache->pubPathFor($this->cache->pubGenerateUniqueKey('forever'))
		);
		$this->assertStringStartsWith("0\n", $raw, 'A never-expiring entry stores a 0 expiry header.');
	}
}
