<?php

namespace Prado\Test\Unit\Web;

use Prado\Caching\TMemoryCache;
use Prado\Exceptions\TConfigurationException;
use Prado\TModule;
use Prado\Web\TCacheHttpSession;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Traits\PradoUnitModuleDependencyTrait;

class TCacheHttpSessionTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;

	protected ?TTestApplication $app = null;
	protected ?TMemoryCache $cache = null;
	protected ?TCacheHttpSession $session = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/app');
		$this->cache = new TMemoryCache();
		$this->cache->init(null);
		$this->app->setModule('MyCache', $this->cache);
		$this->session = new TCacheHttpSession();
		$this->session->setCacheModuleID('MyCache');
		$this->session->init(null);
	}

	protected function tearDown(): void
	{
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
		$this->cache = null;
		$this->session = null;
	}

	private function assertInitThrows(TCacheHttpSession $session, string $errorCode): void
	{
		try {
			$session->init(null);
			self::fail('TConfigurationException was not thrown');
		} catch (TConfigurationException $e) {
			self::assertSame($errorCode, $e->getErrorCode());
		}
		self::assertFalse($session->getUseCustomStorage());
	}

	public function testInitWithoutCacheModuleIDThrows()
	{
		$session = new TCacheHttpSession();
		$this->assertInitThrows($session, 'cachesession_cachemoduleid_required');
	}

	public function testInitWithMissingCacheModuleThrows()
	{
		$session = new TCacheHttpSession();
		$session->setCacheModuleID('MaiCache');
		$this->assertInitThrows($session, 'cachesession_cachemodule_inexistent');
	}

	public function testInitWithModuleThatIsNotACacheThrows()
	{
		$this->app->setModule('NotCache', new class() extends TModule {
		});
		$session = new TCacheHttpSession();
		$session->setCacheModuleID('NotCache');
		$this->assertInitThrows($session, 'cachesession_cachemodule_invalid');
	}

	public function testInitUsesCustomStorage()
	{
		self::assertTrue($this->session->getUseCustomStorage());
		self::assertSame($this->cache, $this->session->getCache());
		self::assertFalse($this->session->getIsStarted());
	}

	public function testCacheModuleID()
	{
		self::assertSame('MyCache', $this->session->getCacheModuleID());
		$this->session->setCacheModuleID('value');
		self::assertSame('value', $this->session->getCacheModuleID());
	}

	public function testKeyPrefix()
	{
		self::assertSame('session', $this->session->getKeyPrefix());
		$this->session->setKeyPrefix('value');
		self::assertSame('value', $this->session->getKeyPrefix());
	}

	public function testStorageReadsWritesAndDestroysThroughTheCache()
	{
		$this->session->setKeyPrefix('sess');
		self::assertSame('', $this->session->_read('abc'));
		self::assertTrue($this->session->_write('abc', 'data'));
		self::assertSame('data', $this->cache->get('sess:abc'));
		self::assertSame('data', $this->session->_read('abc'));
		self::assertTrue($this->session->_destroy('abc'));
		self::assertFalse($this->cache->get('sess:abc'));
		self::assertSame('', $this->session->_read('abc'));
	}

	public function testCronTaskInfoIsNull()
	{
		$this->session->setID('session');
		self::assertNull($this->session->fxGetCronTaskInfos(null, null));
	}

	public function testImplementsIModuleDependency()
	{
		$this->assertInstanceOf(\Prado\IModuleDependency::class, new TCacheHttpSession());
	}

	public function testGetModuleDependencies()
	{
		$session = new TCacheHttpSession();
		$this->assertModuleDependency(null, $session->getModuleDependencies(false));
		$session->setCacheModuleID('my_cache_id');
		$this->assertModuleDependency('my_cache_id', $session->getModuleDependencies(false));
		$this->assertModuleDependency('my_cache_id', $session->getModuleDependencies(true));
	}
}
