<?php

use Prado\Caching\TMemCache;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;
use Prado\Web\TCacheHttpSession;

class TCacheHttpSessionTest extends PHPUnit\Framework\TestCase
{
	protected $app;
	protected static $cache = null;
	protected static $session = null;

	protected function setUp(): void
	{
		if (!extension_loaded('memcached')) {
			self::markTestSkipped('The memcached extension is not available');
		} else {
			$basePath = __DIR__ . '/app';
			$runtimePath = $basePath . '/runtime';
			if (!is_writable($runtimePath)) {
				self::markTestSkipped("'$runtimePath' is not writable");
			}
			$this->app = new TApplication($basePath);
			self::$cache = new TMemCache();
			self::$cache->setKeyPrefix('MyCache');
			self::$cache->init(null);
			$this->app->setModule('MyCache', self::$cache);
			self::$session = new TCacheHttpSession();
			self::$session->setCacheModuleID('MyCache');
			self::$session->init(null);
		}
	}

	protected function tearDown(): void
	{
		$this->app = null;
		self::$cache = null;
		self::$session = null;
	}

	public function testInitOne()
	{
		$session = new TCacheHttpSession();
		self::expectException('Prado\\Exceptions\\TConfigurationException');
		$session->init(null);
	}

	public function testInitTwo()
	{
		$session = new TCacheHttpSession();
		self::expectException('Prado\\Exceptions\\TConfigurationException');
		$session->setCacheModuleID('MaiCache');
		$session->init(null);
	}

	public function testInitThree()
	{
		$session = new TCacheHttpSession();
		$session->setCacheModuleID('MyCache');
		$session->init(null);
		$this->assertInstanceOf(TCacheHttpSession::class, $session);
	}

	public function testGetCache()
	{
		$cache = self::$session->getCache();
		$this->assertEquals(true, $cache instanceof TMemCache);
	}

	public function testCacheModuleID()
	{
		$id = 'value';
		self::$session->setCacheModuleID($id);
		self::assertEquals($id, self::$session->getCacheModuleID());
	}

	public function testKeyPrefix()
	{
		$id = 'value';
		self::$session->setKeyPrefix($id);
		self::assertEquals($id, self::$session->getKeyPrefix());
	}

	public function testSetAndGet()
	{
		self::$session['key'] = 'value';
		self::assertEquals('value', self::$session['key']);
	}

	public function testAdd()
	{
		self::$session->add('anotherkey', 'value');
		self::assertEquals('value', self::$session['anotherkey']);
	}

	public function testRemove()
	{
		self::$session->remove('key');
		self::assertEquals(false, self::$session['key']);
	}

	public function testDestroyAndIsStarted()
	{
		$this->testSetAndGet();
		self::$session->destroy();
		self::assertEquals(false, self::$session->getIsStarted());
	}
}
