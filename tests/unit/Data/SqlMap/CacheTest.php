<?php

require_once(__DIR__ . '/BaseCase.php');

class CacheTest extends BaseCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::initSqlMap();

		//force autoload
		new Account;
	}

	public function resetDatabase()
	{
		$this->initScript('account-init.sql');
	}

	/**
	 * Test for JIRA 29
	 */
	public function testJIRA28()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$account = self::$sqlmap->queryForObject("GetNoAccountWithCache",-99);
				$this->assertNull($account);
		*/
	}

	/**
	 * Test Cache query
	 */
	public function testQueryWithCache()
	{
		$this->resetDatabase();

		$list1 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$list2 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 === $list2);

		$account = $list1[1];
		$account->setEmailAddress("somebody@cache.com");

		//this will cause the cache to flush
		self::$sqlmap->update("UpdateAccountViaInlineParameters", $account);

		$list3 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 !== $list3);

		$this->resetDatabase();
	}


	/**
	 * Test flush Cache
	 */
	public function testFlushDataCache()
	{
		$list1 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");
		$list2 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 === $list2);
		self::$sqlmap->flushCaches();

		$list3 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 !== $list3);
	}

	/**
	 *
	 */
	public function testFlushDataCacheOnExecute()
	{
		$list1 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$list2 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 === $list2);
		self::$sqlmap->update("UpdateAccountViaInlineParameters", $list1[0]);

		$list3 = self::$sqlmap->queryForList("GetCachedAccountsViaResultMap");

		$this->assertTrue($list1 !== $list3);
	}

	/**
	 */
	protected function getCacheModel()
	{
		$cache = new TSqlMapCacheModel();
		//	$cache->setFlushInterval(5*60);
		$cache->setImplementation('LRU');
		$cache->initialize();
		return $cache;
	}

	/**
	 * Test CacheHit
	 */
	public function testCacheHit()
	{
		$cache = $this->getCacheModel();
		$key = new TSqlMapCacheKey('testkey');
		$cache->set($key, 'a');

		$returnedObject = $cache->get($key);

		$this->assertSame('a', $returnedObject);

		$this->assertSame(1, $cache->getHitRatio());
	}



	/**
	 * Test CacheMiss
	 */
	public function testCacheMiss()
	{
		$cache = $this->getCacheModel();
		$key = new TSqlMapCacheKey('testKey');
		$value = 'testValue';
		$cache->set($key, $value);

		$wrongKey = new TSqlMapCacheKey('wrongKey');

		$returnedObject = $cache->get($wrongKey);
		$this->assertNotEquals($value, $returnedObject);
		$this->assertNull($returnedObject) ;
		$this->assertSame(0, $cache->getHitRatio());
	}

	/**
	 * Test CacheHitMiss
	 */
	public function testCacheHitMiss()
	{
		$cache = $this->getCacheModel();
		$key = new TSqlMapCacheKey('testKey');

		$value = "testValue";
		$cache->set($key, $value);

		$returnedObject = $cache->get($key);
		$this->assertSame($value, $returnedObject);

		$wrongKey = new TSqlMapCacheKey('wrongKey');

		$returnedObject = $cache->get($wrongKey);
		$this->assertNotEquals($value, $returnedObject);
		$this->assertNull($returnedObject) ;
		$this->assertSame(0.5, $cache->getHitRatio());
	}
}
