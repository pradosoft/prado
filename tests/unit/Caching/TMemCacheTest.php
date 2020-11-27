<?php

use Prado\Caching\TMemCache;
use Prado\TApplication;

class TMemCacheTest extends PHPUnit\Framework\TestCase
{
	protected $app;
	protected static $cache = null;

	protected function setUp(): void
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

	protected function tearDown(): void
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

	public function testSetOptions()
	{
		self::$cache->setOptions([
			Memcached::OPT_HASH => Memcached::HASH_MURMUR,
			Memcached::OPT_PREFIX_KEY => "widgets"
		]);
		$this->testSetAndGet();
	}

	public function testGetPersistentID()
	{
		$cache = new TMemCache();
		$cache->setPersistentID('test');
		self::assertEquals('test', $cache->getPersistentID());
	}

	public function testSetPersistentID()
	{
		$cache = new TMemCache();
		$cache->setPersistentID('test');
		$cache->setPrimaryCache(false);
		$cache->init(null);
		$cache->set('persistentkey', 'persistentvalue');
		unset($cache);

		$cache2 = new TMemCache();
		$cache2->setPersistentID('test');
		$cache2->setPrimaryCache(false);
		$cache2->init(null);
		self::assertEquals('persistentvalue', $cache2->get('persistentkey'));
	}
}
