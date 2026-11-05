<?php

/**
 * TMssqlMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Data\IDataConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Harness\Util\Mutex\TTestLockConnection;
use Prado\Util\Mutex\TMssqlMutex;

/**
 * A TMssqlMutex over a {@see TTestLockConnection}.
 */
class TMssqlMutexTestMutex extends TMssqlMutex
{
	public ?IDataConnection $testConnection = null;

	public function getDbConnection(): IDataConnection
	{
		return $this->testConnection ?? parent::getDbConnection();
	}
}

/**
 * Unit tests for {@see TMssqlMutex}: the `sp_getapplock` batches, return codes, and
 * name limit, over the {@see TTestLockConnection} harness.
 */
class TMssqlMutexTest extends \PHPUnit\Framework\TestCase
{
	private function newMutex(string $driver = 'sqlsrv'): TMssqlMutexTestMutex
	{
		$mutex = new TMssqlMutexTestMutex();
		$mutex->testConnection = new TTestLockConnection($driver);
		$mutex->setRetryDelay(1);
		return $mutex;
	}

	public function testSupportedDrivers(): void
	{
		$this->assertSame(['sqlsrv', 'dblib', 'mssql'], (new TMssqlMutex())->getSupportedDrivers());
	}

	public function testAcquireRequestsSessionLockWithoutWaiting(): void
	{
		$mutex = $this->newMutex();
		$connection = $mutex->testConnection;
		$connection->results = [['0']];
		$this->assertTrue($mutex->acquire('job'));
		$sql = $connection->commands[0]->sql;
		$this->assertStringContainsString("sp_getapplock @Resource = :name, @LockMode = 'Exclusive', @LockOwner = 'Session', @LockTimeout = 0", $sql);
		$this->assertStringStartsWith('SET NOCOUNT ON;', $sql);
		$this->assertSame('job', $connection->commands[0]->bound[':name']);
	}

	public function testGrantedAfterWaitIsAcquired(): void
	{
		$mutex = $this->newMutex('dblib');
		$mutex->testConnection->results = [[1]];
		$this->assertTrue($mutex->acquire('job'));
	}

	public function testTimeoutCodeIsNotAcquired(): void
	{
		$mutex = $this->newMutex();
		$mutex->testConnection->results = [[-1]];
		$this->assertFalse($mutex->acquire('job'));
	}

	public function testReleaseRunsReleaseAppLock(): void
	{
		$mutex = $this->newMutex();
		$connection = $mutex->testConnection;
		$connection->results = [[0], [0]];
		$mutex->acquire('job');
		$this->assertTrue($mutex->release('job'));
		$this->assertStringContainsString("sp_releaseapplock @Resource = :name, @LockOwner = 'Session'", $connection->commands[1]->sql);
	}

	public function testReleaseFailureCode(): void
	{
		$mutex = $this->newMutex();
		$mutex->testConnection->results = [[0], [-999]];
		$mutex->acquire('job');
		$this->assertFalse($mutex->release('job'));
	}

	public function testLongNameBecomesHash(): void
	{
		$mutex = $this->newMutex();
		$mutex->setKeyPrefix('app:');
		$this->assertSame('app:job', $mutex->getServerLockName('job'));
		$name = str_repeat('n', 252);
		$this->assertSame(sha1('app:' . $name), $mutex->getServerLockName($name));
	}

	public function testOtherDriverThrows(): void
	{
		$mutex = $this->newMutex('mysql');
		$this->expectException(TConfigurationException::class);
		$mutex->acquire('job');
	}
}
