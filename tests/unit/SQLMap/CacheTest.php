<?php

require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class CacheTest extends BaseTest
{
	function __construct()
	{
		parent::__construct();

		$this->initSqlMap();

		//force autoload
		new Account;
	}

	function resetDatabase()
	{
		$this->initScript('account-init.sql');
	}

	/**
	 * Test for JIRA 29
	 */
	function testJIRA28()
	{
		$account = $this->sqlmap->queryForObject("GetNoAccountWithCache",-99);
		$this->assertNull($account);
	}

	/**
	 * Test Cache query
	 */
	function testQueryWithCache() 
	{
		$this->resetDatabase();

		$list1 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$list2 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 === $list2);

		$account = $list1[1];
		$account->setEmailAddress("somebody@cache.com");
		
		//this will cause the cache to flush
		$this->sqlmap->update("UpdateAccountViaInlineParameters", $account);

		$list3 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 !== $list3);

		$this->resetDatabase();
	}


	/**
	 * Test flush Cache
	 */
	function testFlushDataCache() 
	{
		$list1 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");
		$list2 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 === $list2);
		$this->sqlmap->flushCaches();

		$list3 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 !== $list3);
	}

	/**
	 *
	 */
	function testFlushDataCacheOnExecute()
	{
		$list1 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$list2 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");
		
		$this->assertTrue($list1 === $list2);
		$this->sqlmap->update("UpdateAccountViaInlineParameters", $list1[0]);
		
		$list3 = $this->sqlmap->queryForList("GetCachedAccountsViaResultMap");
		
		$this->assertTrue($list1 !== $list3);
	}

	/**
	 */
	protected function 	getCacheModel() 
	{
		$cache = new TSqlMapCacheModel();
		$cache->setFlushInterval(5*60);
		$cache->setImplementation('LRU');
		$cache->initialize($this->sqlmap);
		return $cache;
	}

	/**
	 * Test CacheHit
	 */
	function testCacheHit()
	{
		$cache = $this->getCacheModel();
		$key = new TSqlMapCacheKey('testkey');
		$cache->set($key, 'a');

		$returnedObject = $cache->get($key);

		$this->assertEquals('a', $returnedObject);
		
		$this->assertEquals(1, $cache->getHitRatio());
	}



	/**
	 * Test CacheMiss
	 */
	function testCacheMiss() 
	{
		$cache = $this->getCacheModel();
		$key = new TSqlMapCacheKey('testKey');
		$value = 'testValue';
		$cache->set($key, $value);

		$wrongKey = new TSqlMapCacheKey('wrongKey');

		$returnedObject = $cache->get($wrongKey);
		$this->assertNotEquals($value, $returnedObject);
		$this->assertNull($returnedObject) ;
		$this->assertEquals(0, $cache->getHitRatio());
	}
	
	/**
	 * Test CacheHitMiss
	 */
	function testCacheHitMiss() 
	{
		$cache = $this->getCacheModel();
		$key = new TSqlMapCacheKey('testKey');

		$value = "testValue";
		$cache->set($key, $value);

		$returnedObject = $cache->get($key);
		$this->assertEquals($value, $returnedObject);

		$wrongKey = new TSqlMapCacheKey('wrongKey');

		$returnedObject = $cache->get($wrongKey);
		$this->assertNotEquals($value, $returnedObject);
		$this->assertNull($returnedObject) ;
		$this->assertEquals(0.5, $cache->getHitRatio());
	}
}

?>