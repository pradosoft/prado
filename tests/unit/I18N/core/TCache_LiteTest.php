<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\TCache_Lite;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A cache whose read-back never matches the data written, so the write control fails.
 */
class TCache_LiteFailingReadFixture extends TCache_Lite
{
	protected function _read()
	{
		return 'not the data written';
	}
}

class TCache_LiteTest extends TestCase
{
	use TTempDirectoryTrait;

	/** @var string[] paths whose permissions a test changed. */
	private array $_chmodded = [];

	protected function tearDown(): void
	{
		foreach ($this->_chmodded as $path) {
			@chmod($path, is_dir($path) ? 0o755 : 0o644);
		}
		$this->_chmodded = [];
		$this->removeTempDirectories();
	}

	/**
	 * @param array $options the TCache_Lite options, cacheDir excluded.
	 * @return TCache_Lite a cache in a new temp directory.
	 */
	private function createCache(array $options = [], string $class = TCache_Lite::class): TCache_Lite
	{
		return new $class(['cacheDir' => $this->makeTempDirectory('prado_cachelite_') . '/'] + $options);
	}

	private function chmodPath(string $path, int $mode): void
	{
		$this->_chmodded[] = $path;
		chmod($path, $mode);
	}

	// ------- construction -------

	public function testConstructorDefaultsIgnoreTheNullOption()
	{
		$cache = new TCache_Lite();

		$this->assertSame('/tmp/', PradoUnit::getProp($cache, '_cacheDir'));
		$this->assertSame(3600, PradoUnit::getProp($cache, '_lifeTime'));
		$this->assertTrue(PradoUnit::getProp($cache, '_readControl'));
		$this->assertSame('crc32', PradoUnit::getProp($cache, '_readControlType'));
	}

	public function testConstructorWithNoOptions()
	{
		$cache = new TCache_Lite([]);

		$this->assertTrue(PradoUnit::getProp($cache, '_caching'));
		$this->assertFalse(PradoUnit::getProp($cache, '_memoryCaching'));
	}

	public function testConstructorSetsKnownOptionsAndIgnoresUnknownOptions()
	{
		$cache = new TCache_Lite(['lifeTime' => 60]);
		$this->assertSame(60, PradoUnit::getProp($cache, '_lifeTime'));

		$cache = new TCache_Lite(['memoryCachingLimit' => 5, 'unknownOption' => 'x', 'readControlType' => 'md5']);
		$this->assertSame(5, PradoUnit::getProp($cache, '_memoryCachingLimit'));
		$this->assertSame('md5', PradoUnit::getProp($cache, '_readControlType'));
		$this->assertFalse(property_exists($cache, '_unknownOption'));
	}

	// ------- file names -------

	public function testSetFileNameProtectsTheNameWithMd5()
	{
		$cache = new TCache_Lite(['cacheDir' => '/dir/']);
		$cache->_setFileName('id', 'group');

		$this->assertSame('/dir/cache_' . md5('group') . '_' . md5('id'), $cache->getCacheFile());
	}

	public function testSetFileNameWithoutProtectionUsesTheNames()
	{
		$cache = new TCache_Lite(['cacheDir' => '/dir/', 'fileNameProtection' => false]);
		$cache->_setFileName('id', 'group');

		$this->assertSame('/dir/cache_group_id', $cache->getCacheFile());
	}

	// ------- save and get through the file -------

	public function testSaveAndGetWithEachReadControlType()
	{
		foreach (['md5', 'crc32', 'strlen'] as $type) {
			$cache = $this->createCache(['readControlType' => $type]);

			$this->assertTrue($cache->save('data ' . $type, 'id', 'group'), $type);
			$this->assertSame('data ' . $type, $cache->get('id', 'group'), $type);
		}
	}

	public function testReadControlStoresTheHashBeforeTheData()
	{
		$expected = [
			'md5' => md5('data'),
			'crc32' => sprintf('% 32d', crc32('data')),
			'strlen' => sprintf('% 32d', 4),
		];
		foreach ($expected as $type => $hash) {
			$cache = $this->createCache(['readControlType' => $type]);
			$cache->save('data', 'id');

			$this->assertSame($hash . 'data', file_get_contents($cache->getCacheFile()), $type);
		}
	}

	public function testUnknownReadControlTypeThrows()
	{
		$cache = $this->createCache(['readControlType' => 'sha1', 'fileLocking' => false]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Unknown controlType');
		$cache->save('data', 'id');
	}

	public function testSaveWithoutReadControlStoresOnlyTheData()
	{
		$cache = $this->createCache(['readControl' => false]);

		$this->assertTrue($cache->save('data', 'id'));
		$this->assertSame('data', file_get_contents($cache->getCacheFile()));
		$this->assertSame('data', $cache->get('id'));
	}

	public function testSaveWithoutWriteControlWritesOnce()
	{
		$cache = $this->createCache(['writeControl' => false]);

		$this->assertTrue($cache->save('data', 'id'));
		$this->assertSame('data', $cache->get('id'));
	}

	public function testSaveAndGetWithoutFileLocking()
	{
		$cache = $this->createCache(['fileLocking' => false]);

		$this->assertTrue($cache->save('data', 'id'));
		$this->assertSame('data', $cache->get('id'));

		$cache = $this->createCache(['fileLocking' => false, 'writeControl' => false, 'readControl' => false]);
		$this->assertTrue($cache->save('plain', 'id'));
		$this->assertSame('plain', $cache->get('id'));
	}

	public function testSaveWithoutAnIdUsesTheFileOfTheLastGet()
	{
		$cache = $this->createCache();

		$this->assertFalse($cache->get('id', 'group'));
		$this->assertTrue($cache->save('data'));
		$this->assertSame('data', $cache->get('id', 'group'));
	}

	public function testAutomaticSerializationRoundTripsAnArray()
	{
		$cache = $this->createCache(['automaticSerialization' => true]);

		$this->assertTrue($cache->save(['a' => 1, 'b' => [2]], 'id'));
		$this->assertSame(['a' => 1, 'b' => [2]], $cache->get('id'));
		$this->assertFalse($cache->get('missing'));
	}

	public function testFailedWriteControlReturnsFalse()
	{
		$cache = $this->createCache([], TCache_LiteFailingReadFixture::class);

		$this->assertFalse($cache->save('data', 'id'));
		$this->assertFileExists($cache->getCacheFile());
	}

	public function testDisabledCachingNeitherSavesNorGets()
	{
		$cache = $this->createCache(['caching' => false]);

		$this->assertFalse($cache->save('data', 'id'));
		$this->assertFalse($cache->get('id'));
	}

	public function testGetOfAMissingIdIsFalse()
	{
		$cache = $this->createCache();

		$this->assertFalse($cache->get('missing'));
		$this->assertFalse($cache->get('missing', 'default', true));
	}

	public function testGetIgnoresAnExpiredFileUnlessValidityIsNotTested()
	{
		$cache = $this->createCache();
		$cache->save('data', 'id');
		touch($cache->getCacheFile(), time() - 7200);
		clearstatcache();

		$this->assertFalse($cache->get('id'));
		$this->assertSame('data', $cache->get('id', 'default', true));
	}

	public function testSetLifeTimeMovesTheRefreshTime()
	{
		$cache = $this->createCache();
		$cache->save('data', 'id');
		touch($cache->getCacheFile(), time() - 600);
		clearstatcache();
		$this->assertSame('data', $cache->get('id'));

		$cache->setLifeTime(60);

		$this->assertSame(60, PradoUnit::getProp($cache, '_lifeTime'));
		$this->assertFalse($cache->get('id'));
	}

	public function testGetOfACorruptedFileIsFalse()
	{
		$cache = $this->createCache();
		$cache->save('data', 'id');
		$file = $cache->getCacheFile();
		file_put_contents($file, str_repeat('0', 32) . 'data');

		$this->assertFalse($cache->get('id'));
	}

	public function testReadOfAnUnreadableFileThrowsWithoutFileLocking()
	{
		$cache = $this->createCache(['fileLocking' => false]);
		$cache->save('data', 'id');
		$this->chmodPath($cache->getCacheFile(), 0o000);
		if (is_readable($cache->getCacheFile())) {
			$this->markTestSkipped('The process reads a file without read permission.');
		}

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Cache_Lite: Unable to read cache');
		$cache->get('id', 'default', true);
	}

	public function testWriteIntoAMissingDirectoryThrows()
	{
		$cache = new TCache_Lite(['cacheDir' => $this->makeTempDirectory('prado_cachelite_') . '/missing/']);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Cache_Lite: Unable to write cache');
		$cache->save('data', 'id');
	}

	// ------- memory caching -------

	public function testMemoryCachingServesTheSavedData()
	{
		$cache = $this->createCache(['memoryCaching' => true]);
		$cache->save('data', 'id');
		unlink($cache->getCacheFile());

		$this->assertSame('data', $cache->get('id'));
	}

	public function testMemoryCachingWithAutomaticSerialization()
	{
		$cache = $this->createCache(['memoryCaching' => true, 'onlyMemoryCaching' => true, 'automaticSerialization' => true]);

		$this->assertTrue($cache->save(['a' => 1], 'id'));
		$this->assertSame(['a' => 1], $cache->get('id'));
	}

	public function testMemoryCachingKeepsTheDataReadFromAFile()
	{
		$dir = $this->makeTempDirectory('prado_cachelite_') . '/';
		$writer = new TCache_Lite(['cacheDir' => $dir]);
		$writer->save('data', 'id');

		$cache = new TCache_Lite(['cacheDir' => $dir, 'memoryCaching' => true]);
		$this->assertSame('data', $cache->get('id'));
		unlink($cache->getCacheFile());

		$this->assertSame('data', $cache->get('id'));
		$this->assertFalse($cache->get('missing'));
	}

	public function testOnlyMemoryCachingWritesNoFile()
	{
		$cache = $this->createCache(['memoryCaching' => true, 'onlyMemoryCaching' => true]);

		$this->assertTrue($cache->save('data', 'id'));
		$this->assertFileDoesNotExist($cache->getCacheFile());
		$this->assertSame('data', $cache->get('id'));
		$this->assertFalse($cache->get('missing'));
	}

	public function testMemoryCachingLimitEvictsTheOldestEntry()
	{
		$cache = $this->createCache(['memoryCaching' => true, 'onlyMemoryCaching' => true, 'memoryCachingLimit' => 2]);
		$cache->save('a', 'a');
		$cache->save('b', 'b');
		$cache->save('c', 'c');

		$this->assertFalse($cache->get('a'));
		$this->assertSame('b', $cache->get('b'));
		$this->assertSame('c', $cache->get('c'));
		$this->assertSame(2, PradoUnit::getProp($cache, '_memoryCachingCounter'));
	}

	// ------- remove -------

	public function testRemoveDeletesTheFile()
	{
		$cache = $this->createCache();
		$cache->save('data', 'id', 'group');

		$this->assertTrue($cache->remove('id', 'group'));
		$this->assertFileDoesNotExist($cache->getCacheFile());
	}

	public function testRemoveOfAMissingFileThrows()
	{
		$cache = $this->createCache();

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('TCache_Lite: Unable to remove cache');
		$cache->remove('missing');
	}

	// ------- clean -------

	public function testCleanAGroupRemovesOnlyThatGroup()
	{
		$cache = $this->createCache();
		$cache->save('a', 'a', 'one');
		$cache->save('b', 'b', 'two');
		$dir = PradoUnit::getProp($cache, '_cacheDir');
		mkdir($dir . 'subdir');
		file_put_contents($dir . 'other.txt', 'x');

		$this->assertTrue($cache->clean('one'));

		$this->assertFalse($cache->get('a', 'one'));
		$this->assertSame('b', $cache->get('b', 'two'));
		$this->assertFileExists($dir . 'other.txt');
		$this->assertDirectoryExists($dir . 'subdir');
	}

	public function testCleanAllRemovesEveryCacheFile()
	{
		$cache = $this->createCache();
		$cache->save('a', 'a', 'one');
		$cache->save('b', 'b', 'two');

		$this->assertTrue($cache->clean());

		$this->assertFalse($cache->get('a', 'one'));
		$this->assertFalse($cache->get('b', 'two'));
	}

	public function testCleanWithoutFileNameProtection()
	{
		$cache = $this->createCache(['fileNameProtection' => false]);
		$cache->save('a', 'a', 'one');
		$cache->save('b', 'b', 'two');

		$this->assertTrue($cache->clean('one'));
		$this->assertFalse($cache->get('a', 'one'));
		$this->assertSame('b', $cache->get('b', 'two'));

		$this->assertTrue($cache->clean());
		$this->assertFalse($cache->get('b', 'two'));
	}

	public function testCleanOfOnlyMemoryCachingRemovesTheGroupEntries()
	{
		$cache = $this->createCache(['memoryCaching' => true, 'onlyMemoryCaching' => true]);
		$cache->save('a', 'a', 'one');
		$cache->save('b', 'b', 'two');

		$this->assertTrue($cache->clean('one'));

		$this->assertFalse($cache->get('a', 'one'));
		$this->assertSame('b', $cache->get('b', 'two'));
		$this->assertSame(1, PradoUnit::getProp($cache, '_memoryCachingCounter'));
	}

	public function testCleanOfMemoryCachingRemovesTheMemoryAndTheFile()
	{
		$cache = $this->createCache(['memoryCaching' => true]);
		$cache->save('a', 'a', 'one');

		$this->assertTrue($cache->clean('one'));

		$this->assertFileDoesNotExist($cache->getCacheFile());
		$this->assertFalse($cache->get('a', 'one'));
	}

	public function testCleanOfAnUnremovableFileThrows()
	{
		if (PHP_OS_FAMILY === 'Windows') {
			$this->markTestSkipped('Directory permissions do not stop writes on Windows.');
		}
		$cache = $this->createCache();
		$cache->save('a', 'a');
		$dir = PradoUnit::getProp($cache, '_cacheDir');
		$this->chmodPath($dir, 0o555);
		if (is_writable($dir)) {
			$this->markTestSkipped('The process writes into a directory without write permission.');
		}

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Cache_Lite: Unable to remove cache');
		$cache->clean();
	}

	public function testSaveAndGetAnEmptyString()
	{
		foreach ([true, false] as $readControl) {
			$cache = $this->createCache(['readControl' => $readControl]);
			$this->assertTrue($cache->save('', 'id'));
			$this->assertSame('', $cache->get('id', 'default', true));
		}
	}

	public function testGetOfAFileShorterThanTheHashIsFalse()
	{
		$cache = $this->createCache();
		$cache->save('data', 'id');
		file_put_contents($cache->getCacheFile(), 'short');
		$this->assertFalse($cache->get('id', 'default', true));
		$this->assertTrue($cache->save('again', 'id'));
		$this->assertSame('again', $cache->get('id'));
	}

	public function testReadOfAnUnreadableFileThrowsWithFileLocking()
	{
		$cache = $this->createCache(['fileLocking' => true]);
		$cache->save('data', 'id');
		$this->chmodPath($cache->getCacheFile(), 0o000);
		if (is_readable($cache->getCacheFile())) {
			$this->markTestSkipped('The process reads a file without read permission.');
		}

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Cache_Lite: Unable to read cache');
		$cache->get('id', 'default', true);
	}

	public function testCleanOfAMissingDirectoryThrows()
	{
		$cache = new TCache_Lite(['cacheDir' => $this->makeTempDirectory('prado_cachelite_') . '/missing/']);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('TCache_Lite: Unable to open cache directory');
		$cache->clean();
	}

	public function testCleanRemovesAFileNamedLikeZero()
	{
		$dir = $this->makeTempDirectory('prado_cachelite_') . '/';
		$cache = new TCache_Lite(['cacheDir' => $dir, 'fileNameProtection' => false]);
		touch($dir . '0');
		$cache->save('data', 'id', 'g');
		$this->assertTrue($cache->clean('g'));
		$this->assertFileDoesNotExist($dir . 'cache_g_id');
		$this->assertFileExists($dir . '0');
	}

	public function testCleanRemovesAMemoryKeyAtTheStart()
	{
		$cache = new TCache_Lite(['cacheDir' => '', 'fileNameProtection' => false, 'memoryCaching' => true, 'onlyMemoryCaching' => true]);
		$cache->save('data', 'id', 'g');
		$this->assertSame(['cache_g_id' => 'data'], PradoUnit::getProp($cache, '_memoryCachingArray'));
		$this->assertTrue($cache->clean('g'));
		$this->assertSame([], PradoUnit::getProp($cache, '_memoryCachingArray'));
	}

	public function testMemoryCachingOverwriteKeepsTheCount()
	{
		$cache = $this->createCache(['memoryCaching' => true, 'onlyMemoryCaching' => true, 'memoryCachingLimit' => 2]);
		$cache->save('a1', 'a');
		$cache->save('a2', 'a');
		$cache->save('b', 'b');
		$this->assertSame('a2', $cache->get('a'));
		$this->assertSame('b', $cache->get('b'));
		$this->assertSame(2, PradoUnit::getProp($cache, '_memoryCachingCounter'));
	}
}
