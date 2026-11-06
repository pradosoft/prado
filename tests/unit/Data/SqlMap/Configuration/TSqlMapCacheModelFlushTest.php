<?php

namespace Prado\Test\Unit\Data\SqlMap\Configuration;

use Prado\Data\SqlMap\TSqlMapManager;

class TSqlMapCacheModelFlushTest extends \PHPUnit\Framework\TestCase
{
	private TSqlMapManager $manager;

	protected function setUp(): void
	{
		$this->manager = new TSqlMapManager();
		$this->manager->configureXml(__DIR__ . '/../maps/cache-flush-config.xml');
	}

	private function executes(string $statementID, string $cacheID): bool
	{
		$cache = $this->manager->getCacheModel($cacheID);
		$cache->set('key', 'value');
		$this->manager->getMappedStatement($statementID)->onExecuteQuery('');
		return $cache->get('key') === null;
	}

	public function testWriteStatementWithCacheModelFlushesCache()
	{
		$this->assertTrue($this->executes('InsertCached', 'shared-cache'));
		$this->assertTrue($this->executes('UpdateCached', 'shared-cache'));
		$this->assertTrue($this->executes('DeleteCached', 'shared-cache'));
	}

	public function testSelectWithCacheModelUsesCache()
	{
		$cache = $this->manager->getCacheModel('shared-cache');
		$this->assertSame($cache, $this->manager->getMappedStatement('SelectCached')->getStatement()->getCache());
	}

	public function testStatementWithoutCacheModelDoesNotFlushCache()
	{
		$this->assertFalse($this->executes('UpdateUncached', 'shared-cache'));
	}

	public function testWriteStatementFlushesOnlyItsOwnCacheModel()
	{
		$this->assertFalse($this->executes('UpdateCached', 'listed-cache'));
		$this->assertFalse($this->executes('UpdateListed', 'shared-cache'));
	}

	public function testFlushOnExecuteFlushesCache()
	{
		$this->assertTrue($this->executes('UpdateListed', 'listed-cache'));
	}

	public function testStatementListedAndWithCacheModelFlushesCache()
	{
		$this->assertTrue($this->executes('UpdateListedAndCached', 'listed-cache'));
	}

	public function testCacheModelInAnotherMappingFile()
	{
		$this->assertTrue($this->executes('UpdateInOtherFile', 'shared-cache'));
		$this->assertTrue($this->executes('DeleteOtherFileCache', 'other-cache'));
	}
}
