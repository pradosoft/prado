<?php

/**
 * TMysqlMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Data\IDataConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;
use Prado\Test\Unit\Harness\Util\Mutex\TTestLockConnection;
use Prado\Util\Mutex\TMysqlMutex;

/**
 * A TMysqlMutex over an injected connection.
 */
class TMysqlMutexTestMutex extends TMysqlMutex
{
	public ?IDataConnection $testConnection = null;

	public function getDbConnection(): IDataConnection
	{
		return $this->testConnection ?? parent::getDbConnection();
	}
}

/**
 * Unit tests for {@see TMysqlMutex}: the `GET_LOCK()` queries and name limit over the
 * {@see TTestLockConnection} harness, and exclusion between two sessions of a live
 * MySQL server (skipped without one).
 */
class TMysqlMutexTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function setUp(): void
	{
	}

	protected function getTestTables(): array
	{
		return [];
	}

	private function newMutex(?IDataConnection $connection = null): TMysqlMutexTestMutex
	{
		$mutex = new TMysqlMutexTestMutex();
		$mutex->testConnection = $connection ?? new TTestLockConnection('mysql');
		$mutex->setRetryDelay(1);
		return $mutex;
	}

	public function testSupportedDrivers(): void
	{
		$this->assertSame(['mysql'], (new TMysqlMutex())->getSupportedDrivers());
	}

	public function testQueriesGetLockAndReleaseLock(): void
	{
		$mutex = $this->newMutex();
		$connection = $mutex->testConnection;
		$connection->results = [['1'], [1]];
		$this->assertTrue($mutex->acquire('job'));
		$this->assertTrue($mutex->release('job'));
		$this->assertSame('SELECT GET_LOCK(:name, 0)', $connection->commands[0]->sql);
		$this->assertSame('SELECT RELEASE_LOCK(:name)', $connection->commands[1]->sql);
		$this->assertSame('job', $connection->commands[0]->bound[':name']);
	}

	public function testBusyOrErrorIsNotAcquired(): void
	{
		$mutex = $this->newMutex();
		$mutex->testConnection->results = [['0'], [null]];
		$this->assertFalse($mutex->acquire('job'));
		$this->assertFalse($mutex->acquire('job'));
	}

	public function testLongNameBecomesHash(): void
	{
		$mutex = $this->newMutex();
		$this->assertSame(str_repeat('n', 64), $mutex->getServerLockName(str_repeat('n', 64)));
		$this->assertSame(sha1(str_repeat('n', 65)), $mutex->getServerLockName(str_repeat('n', 65)));
		$mutex->setKeyPrefix('app:');
		$this->assertSame(sha1('app:' . str_repeat('n', 61)), $mutex->getServerLockName(str_repeat('n', 61)));
	}

	public function testOtherDriverThrows(): void
	{
		$mutex = $this->newMutex(new TTestLockConnection('pgsql'));
		try {
			$mutex->acquire('job');
			$this->fail('TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertStringContainsString("'pgsql'", $e->getMessage());
		}
	}

	public function testSessionsExcludeEachOther(): void
	{
		$first = $this->setUpConnection();
		$second = $this->setUpConnection();
		if ($first === null || $second === null) {
			$this->markTestSkipped('No MySQL connection.');
		}
		$prefix = 'prado-unit-' . bin2hex(random_bytes(4)) . ':';
		$a = $this->newMutex($first)->setKeyPrefix($prefix);
		$b = $this->newMutex($second)->setKeyPrefix($prefix);
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.05));
		$this->assertTrue($b->acquire(str_repeat('long', 30)));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$b->releaseAll();
	}
}
