<?php

require_once(dirname(__FILE__).'/../common.php');
require_once(dirname(__FILE__).'/CacheTestCase.php');
Prado::using('System.Data.TSqliteCache');

class utSqliteCache extends CacheTestCase
{
	private $dbFile;

	public function __construct()
	{
		parent::__construct();
		if(Prado::getPathOfAlias('utSqliteCache')===null)
			Prado::setPathOfAlias('utSqliteCache',dirname(__FILE__));
		$this->dbFile='utSqliteCache.test';
	}

	public function tearDown()
	{
		$file=Prado::getPathOfNamespace('utSqliteCache.test',TSqliteCache::DB_FILE_EXT);
		if(is_file($file))
			unlink($file);
		else
			$this->fail("Unable to clean up db file: '".$file."'.");
	}

	public function testInit()
	{
		$cache=new TSqliteCache;

		$this->assertTrue($cache->getDbFile()===null);
		$cache->setDbFile($this->dbFile);
		$this->assertTrue($cache->getDbFile()===$this->dbFile);

		$cache->init(null,null);
		try
		{
			$cache->setDbFile('newfile.db');
			$this->fail('exception not raised when setting DbFile after init');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testBasicOperations()
	{
		$cache=new TSqliteCache;
		$cache->setDbFile($this->dbFile);
		$cache->init(null,null);
		$this->setCache($cache);
		$this->basicOperations();
		$this->setCache(null);
	}
}

?>