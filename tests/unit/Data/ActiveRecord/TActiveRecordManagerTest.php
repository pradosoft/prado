<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Caching\TCache;
use Prado\Data\ActiveRecord\TActiveRecordGateway;
use Prado\Data\ActiveRecord\TActiveRecordInvalidFinderResult;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TInvalidDataValueException;

class ManagerTestGateway extends TActiveRecordGateway
{
}

class TActiveRecordManagerTest extends \PHPUnit\Framework\TestCase
{
	private $_previous;

	protected function setUp(): void
	{
		$this->_previous = TActiveRecordManager::getInstance();
	}

	protected function tearDown(): void
	{
		TActiveRecordManager::getInstance($this->_previous);
	}

	public function testGetInstance_replacesAndKeepsSingleton()
	{
		$manager = new TActiveRecordManager();
		$this->assertSame($manager, TActiveRecordManager::getInstance($manager));
		$this->assertSame($manager, TActiveRecordManager::getInstance());
	}

	public function testDbConnection()
	{
		$manager = new TActiveRecordManager();
		$this->assertNull($manager->getDbConnection());
		$connection = new TDbConnection('sqlite::memory:');
		$manager->setDbConnection($connection);
		$this->assertSame($connection, $manager->getDbConnection());
	}

	public function testCache()
	{
		$manager = new TActiveRecordManager();
		$this->assertNull($manager->getCache());
		$cache = $this->createMock(TCache::class);
		$manager->setCache($cache);
		$this->assertSame($cache, $manager->getCache());
	}

	public function testRecordGateway_createdOnceFromGatewayClass()
	{
		$manager = new TActiveRecordManager();
		$this->assertSame(TActiveRecordGateway::class, $manager->getGatewayClass());
		$gateway = $manager->getRecordGateway();
		$this->assertInstanceOf(TActiveRecordGateway::class, $gateway);
		$this->assertSame($gateway, $manager->getRecordGateway());
	}

	public function testSetGatewayClass_discardsTheCurrentGateway()
	{
		$manager = new TActiveRecordManager();
		$gateway = $manager->getRecordGateway();
		$manager->setGatewayClass(ManagerTestGateway::class);
		$this->assertSame(ManagerTestGateway::class, $manager->getGatewayClass());
		$this->assertInstanceOf(ManagerTestGateway::class, $manager->getRecordGateway());
		$this->assertNotSame($gateway, $manager->getRecordGateway());
	}

	public function testInvalidFinderResult()
	{
		$manager = new TActiveRecordManager();
		$this->assertSame(TActiveRecordInvalidFinderResult::Null, $manager->getInvalidFinderResult());
		$manager->setInvalidFinderResult('Exception');
		$this->assertSame(TActiveRecordInvalidFinderResult::Exception, $manager->getInvalidFinderResult());
		$this->expectException(TInvalidDataValueException::class);
		$manager->setInvalidFinderResult('Quiet');
	}
}
