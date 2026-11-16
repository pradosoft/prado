<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\TCache_Lite;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

class MessageCacheTest extends TestCase
{
	use TTempDirectoryTrait;

	/** @var string[] directories whose permissions a test changed. */
	private array $_chmodded = [];

	protected function tearDown(): void
	{
		foreach ($this->_chmodded as $path) {
			@chmod($path, 0o755);
		}
		$this->_chmodded = [];
		$this->removeTempDirectories();
	}

	/**
	 * @return string the cache file of a catalogue and culture.
	 */
	private function cacheFile(MessageCache $cache, string $catalogue, string $culture): string
	{
		$lite = PradoUnit::getProp($cache, 'cache');
		$lite->_setFileName($catalogue . ':' . $culture, $catalogue . ':' . MessageCache::class);
		return $lite->getCacheFile();
	}

	public function testConstructorCreatesASerializingCacheInTheDirectory()
	{
		$dir = $this->makeTempDirectory('prado_msgcache_');
		$cache = new MessageCache($dir);

		$lite = PradoUnit::getProp($cache, 'cache');
		$this->assertInstanceOf(TCache_Lite::class, $lite);
		$this->assertSame($dir . '/', PradoUnit::getProp($lite, '_cacheDir'));
		$this->assertSame(3153600, PradoUnit::getProp($lite, '_lifeTime'));
		$this->assertTrue(PradoUnit::getProp($lite, '_automaticSerialization'));
	}

	public function testConstructorRejectsAMissingDirectory()
	{
		$dir = $this->makeTempDirectory('prado_msgcache_') . '/missing';

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->expectExceptionMessage("The message cache directory '$dir/' does not exist.");
		new MessageCache($dir);
	}

	public function testConstructorRejectsAReadOnlyDirectory()
	{
		$dir = $this->makeTempDirectory('prado_msgcache_');
		$this->_chmodded[] = $dir;
		chmod($dir, 0o555);
		if (is_writable($dir)) {
			$this->markTestSkipped('The process writes into a directory without write permission.');
		}

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$this->expectExceptionMessage("The message cache directory '$dir/' must be writable");
		new MessageCache($dir);
	}

	public function testLifeTime()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$this->assertSame(3153600, $cache->getLifeTime());

		$cache->setLifeTime('60');

		$this->assertSame(60, $cache->getLifeTime());
	}

	public function testSaveAndGet()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$data = ['Hello' => ['Bonjour', '1', '']];

		$this->assertTrue($cache->save($data, 'messages', 'fr'));
		$file = $this->cacheFile($cache, 'messages', 'fr');
		$this->assertFileExists($file);

		$this->assertSame($data, $cache->get('messages', 'fr', filemtime($file)));
		$this->assertSame($data, $cache->get('messages', 'fr', (string) filemtime($file)));
	}

	public function testGetOfAMissingCatalogueIsFalse()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));

		$this->assertFalse($cache->get('messages', 'fr', time()));
	}

	public function testGetWithoutALastModifiedTimeIsFalse()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$cache->save(['a' => ['b', '1', '']], 'messages', 'fr');

		$this->assertFalse($cache->get('messages', 'fr'));
		$this->assertFalse($cache->get('messages', 'fr', -1));
	}

	public function testGetOfASourceNewerThanTheCacheIsFalse()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$cache->save(['a' => ['b', '1', '']], 'messages', 'fr');
		$file = $this->cacheFile($cache, 'messages', 'fr');

		$this->assertFalse($cache->get('messages', 'fr', filemtime($file) + 1));
	}

	public function testCleanRemovesOnlyTheCatalogue()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$cache->save(['a' => ['A', '1', '']], 'messages', 'fr');
		$cache->save(['b' => ['B', '1', '']], 'other', 'fr');
		$messages = $this->cacheFile($cache, 'messages', 'fr');
		$other = $this->cacheFile($cache, 'other', 'fr');

		$cache->clean('messages', 'fr');

		$this->assertFileDoesNotExist($messages);
		$this->assertFileExists($other);
	}

	public function testClearRemovesEveryCatalogue()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$cache->save(['a' => ['A', '1', '']], 'messages', 'fr');
		$cache->save(['b' => ['B', '1', '']], 'other', 'de');
		$messages = $this->cacheFile($cache, 'messages', 'fr');
		$other = $this->cacheFile($cache, 'other', 'de');

		$cache->clear();

		$this->assertFileDoesNotExist($messages);
		$this->assertFileDoesNotExist($other);
	}

	public function testSetLifeTimeAppliesToTheCache()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgcache_'));
		$cache->save(['a' => ['A', '1', '']], 'messages', 'fr');
		$file = $this->cacheFile($cache, 'messages', 'fr');
		touch($file, time() - 100);
		clearstatcache();
		$this->assertSame(['a' => ['A', '1', '']], $cache->get('messages', 'fr', filemtime($file)));
		$cache->setLifeTime(10);
		$this->assertFalse($cache->get('messages', 'fr', filemtime($file)));
	}
}
