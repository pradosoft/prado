<?php

use Prado\Caching\TAPCCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNotSupportedException;
use Prado\TApplication;

class TAPCCacheTest extends PHPUnit\Framework\TestCase
{
	protected $app;
	protected static $cache = null;

	protected function setUp(): void
	{
		if (!extension_loaded('apcu')) {
			self::markTestSkipped('The APCu extension is not available');
		} else {
			$basePath = __DIR__ . '/mockapp';
			$runtimePath = $basePath . '/runtime';
			if (!is_writable($runtimePath)) {
				self::markTestSkipped("'$runtimePath' is writable");
			}
			try {
				$this->app = new TApplication($basePath);
				self::$cache = new TAPCCache();
				self::$cache->init(null);
			} catch (TConfigurationException $e) {
				self::markTestSkipped($e->getMessage());
			}
		}
	}

	protected function tearDown(): void
	{
		$this->app = null;
		self::$cache = null;
	}

	public function testInit()
	{
		$this->assertInstanceOf(TAPCCache::class, self::$cache);
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
		try {
			self::$cache->add('anotherkey', 'value');
		} catch (TNotSupportedException $e) {
			self::markTestSkipped('apc_add is not supported');
			return;
		}
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
