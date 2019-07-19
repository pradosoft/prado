<?php

use Prado\Caching\TMemCache;
use Prado\TApplication;

/**
 * @package System.Caching
 */
class TMemCacheTest extends PHPUnit\Framework\TestCase
{
	protected $app;
	protected static $cache = null;

	protected function setUp()
	{
		if (!extension_loaded('memcached')) {
			self::markTestSkipped('The memcached extension is not available');
		} else {
			$basePath = __DIR__ . '/mockapp';
			$runtimePath = $basePath . '/runtime';
			if (!is_writable($runtimePath)) {
				self::markTestSkipped("'$runtimePath' is not writable");
			}
			$this->app = new TApplication($basePath);
			self::$cache = new TMemCache();
			self::$cache->init(null);
		}
	}

	protected function tearDown()
	{
		$this->app = null;
		self::$cache = null;
	}

	public function testInit()
	{
		$this->assertInstanceOf(TMemCache::class, self::$cache);
	}

	public function testPrimaryCache()
	{
		self::$cache->PrimaryCache = true;
		self::assertEquals(true, self::$cache->PrimaryCache);
		self::$cache->PrimaryCache = false;
		self::assertEquals(false, self::$cache->PrimaryCache);
	}

	public function testKeyPrefix()
	{
		self::$cache->KeyPrefix = 'prefix';
		self::assertEquals('prefix', self::$cache->KeyPrefix);
	}

	public function testSetAndGet()
	{
		self::$cache->set('key', 'value');
		self::assertEquals('value', self::$cache->get('key'));
	}

	public function testAdd()
	{
		self::$cache->add('anotherkey', 'value');
		self::assertEquals('value', self::$cache->get('anotherkey'));
	}

	public function testDelete()
	{
		self::$cache->delete('key');
		self::assertEquals(false, self::$cache->get('key'));
	}

	public function testFlush()
	{
		$this->testSetAndGet();
		self::assertEquals(true, self::$cache->flush());
	}
}
