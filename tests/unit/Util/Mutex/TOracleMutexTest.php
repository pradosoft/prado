<?php

/**
 * TOracleMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Data\IDataConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Util\Mutex\TTestLockConnection;
use Prado\Util\Mutex\TOracleMutex;

/**
 * A TOracleMutex over a {@see TTestLockConnection}.
 */
class TOracleMutexTestMutex extends TOracleMutex
{
	public ?IDataConnection $testConnection = null;

	public function getDbConnection(): IDataConnection
	{
		return $this->testConnection ?? parent::getDbConnection();
	}
}

/**
 * Unit tests for {@see TOracleMutex}: the `DBMS_LOCK` blocks, statuses, lock mode,
 * and name limit, over the {@see TTestLockConnection} harness.
 */
class TOracleMutexTest extends \PHPUnit\Framework\TestCase
{
	private function newMutex(string $driver = 'oci'): TOracleMutexTestMutex
	{
		$mutex = new TOracleMutexTestMutex();
		$mutex->testConnection = new TTestLockConnection($driver);
		$mutex->setRetryDelay(1);
		return $mutex;
	}

	public function testSupportedDrivers(): void
	{
		$this->assertSame(['oci'], (new TOracleMutex())->getSupportedDrivers());
	}

	public function testAcquireRequestsExclusiveLockWithoutWaiting(): void
	{
		$mutex = $this->newMutex();
		$connection = $mutex->testConnection;
		$connection->results = [[null, 0]];
		$this->assertTrue($mutex->acquire('job'));
		$command = $connection->commands[0];
		$this->assertStringContainsString('DBMS_LOCK.ALLOCATE_UNIQUE(:name, handle)', $command->sql);
		$this->assertStringContainsString('DBMS_LOCK.REQUEST(handle, DBMS_LOCK.X_MODE, 0, FALSE)', $command->sql);
		$this->assertSame('job', $command->bound[':name']);
		$this->assertSame(\PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT, $command->types[':status']);
	}

	public function testBusyStatusIsNotAcquired(): void
	{
		$mutex = $this->newMutex();
		$mutex->testConnection->results = [[null, 1]];
		$this->assertFalse($mutex->acquire('job'));
		$this->assertFalse($mutex->isAcquired('job'));
	}

	public function testReleaseRunsDbmsLockRelease(): void
	{
		$mutex = $this->newMutex();
		$connection = $mutex->testConnection;
		$connection->results = [[null, 0], [null, 0]];
		$mutex->acquire('job');
		$this->assertTrue($mutex->release('job'));
		$this->assertStringContainsString(':status := DBMS_LOCK.RELEASE(handle)', $connection->commands[1]->sql);
		$this->assertSame('job', $connection->commands[1]->bound[':name']);
	}

	public function testReleaseFailureStatus(): void
	{
		$mutex = $this->newMutex();
		$mutex->testConnection->results = [[null, 0], [null, 4]];
		$mutex->acquire('job');
		$this->assertFalse($mutex->release('job'));
	}

	public function testLockModeAndReleaseOnCommit(): void
	{
		$mutex = $this->newMutex();
		$this->assertSame('X_MODE', $mutex->getLockMode());
		$this->assertFalse($mutex->getReleaseOnCommit());
		$mutex->setLockMode('s_mode');
		$mutex->setReleaseOnCommit('true');
		$this->assertSame('S_MODE', $mutex->getLockMode());
		$this->assertTrue($mutex->getReleaseOnCommit());
		$mutex->testConnection->results = [[null, 0]];
		$mutex->acquire('job');
		$this->assertStringContainsString('DBMS_LOCK.S_MODE, 0, TRUE)', $mutex->testConnection->commands[0]->sql);
		$this->expectException(TInvalidDataValueException::class);
		$mutex->setLockMode('Z_MODE');
	}

	public function testLongNameBecomesHash(): void
	{
		$mutex = $this->newMutex();
		$this->assertSame('job', $mutex->getServerLockName('job'));
		$name = str_repeat('n', 129);
		$this->assertSame(sha1($name), $mutex->getServerLockName($name));
		$this->assertSame(str_repeat('n', 128), $mutex->getServerLockName(str_repeat('n', 128)));
	}

	public function testOtherDriverThrows(): void
	{
		$mutex = $this->newMutex('sqlite');
		$this->expectException(TConfigurationException::class);
		$mutex->acquire('job');
	}
}
