<?php

require_once(dirname(__FILE__).'/../common.php');
require_once(dirname(__FILE__).'/CacheTestCase.php');
Prado::using('System.Caching.TMemCache');

class utMemCache extends UnitTestCase
{
	private $_prefix='';
	private $_server='localhost';
	private $_port=11211;

	public function testInit()
	{
		if(!extension_loaded('memcache'))
		{
			$this->fail('TMemCache is not tested. PHP extension "memcache" is required by TMemCache.');
			return;
		}
		$cache=new TMemCache;

		$this->assertTrue($cache->getHost()==='localhost');
		$cache->setHost('localhost2');
		$this->assertTrue($cache->getHost()==='localhost2');

		$this->assertTrue($cache->getPort()===11211);
		$cache->setPort(1000);
		$this->assertTrue($cache->getPort()===1000);

		$cache->init(null,null);
		try
		{
			$cache->setHost('newhost');
			$this->fail('exception not raised when setting Server after init');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
		try
		{
			$cache->setPort(10000);
			$this->fail('exception not raised when setting Port after init');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testBasicOperations()
	{
		if(!extension_loaded('memcache'))
		{
			$this->fail('TMemCache is not tested. PHP extension "memcache" is required by TMemCache.');
			return;
		}
		$cache=new TMemCache;
		$cache->init(null,null);
		$this->setCache($cache);
		$this->basicOperations();
		$this->setCache(null);
	}
}

?>