<?php

/**
 * TPgsqlMutexTest class file.
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
use Prado\Util\Mutex\TPgsqlMutex;

/**
 * A TPgsqlMutex over an injected connection.
 */
class TPgsqlMutexTestMutex extends TPgsqlMutex
{
	public ?IDataConnection $testConnection = null;

	public function getDbConnection(): IDataConnection
	{
		return $this->testConnection ?? parent::getDbConnection();
	}
}

/**
 * Unit tests for {@see TPgsqlMutex}: the advisory keys and queries over the
 * {@see TTestLockConnection} harness, and exclusion between two sessions of a live
 * PostgreSQL server (skipped without one).
 */
class TPgsqlMutexTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function setUp(): void
	{
	}

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupPgsqlConnection';
	}

	protected function getTestTables(): array
	{
		return [];
	}

	private function newMutex(?IDataConnection $connection = null): TPgsqlMutexTestMutex
	{
		$mutex = new TPgsqlMutexTestMutex();
		$mutex->testConnection = $connection ?? new TTestLockConnection('pgsql');
		$mutex->setRetryDelay(1);
		return $mutex;
	}

	public function testSupportedDrivers(): void
	{
		$this->assertSame(['pgsql'], (new TPgsqlMutex())->getSupportedDrivers());
	}

	public function testAdvisoryKeysAreSigned32BitHashWords(): void
	{
		$mutex = $this->newMutex();
		$words = unpack('N2', sha1('job', true));
		$expected = array_map(fn ($w) => $w > 0x7FFFFFFF ? $w - 0x100000000 : $w, array_values($words));
		$this->assertSame($expected, $mutex->getAdvisoryKeys('job'));
		foreach (['a', 'b', 'job', 'another name', str_repeat('z', 1000)] as $name) {
			foreach ($mutex->getAdvisoryKeys($name) as $key) {
				$this->assertGreaterThanOrEqual(-0x80000000, $key);
				$this->assertLessThanOrEqual(0x7FFFFFFF, $key);
			}
		}
		$keys = $mutex->getAdvisoryKeys('job');
		$mutex->setKeyPrefix('app:');
		$this->assertNotSame($keys, $mutex->getAdvisoryKeys('job'));
	}

	public function testQueriesTryLockAndUnlock(): void
	{
		$mutex = $this->newMutex();
		$connection = $mutex->testConnection;
		$connection->results = [[true], ['t']];
		$this->assertTrue($mutex->acquire('job'));
		$this->assertTrue($mutex->release('job'));
		[$key1, $key2] = $mutex->getAdvisoryKeys('job');
		$this->assertSame('SELECT pg_try_advisory_lock(:key1, :key2)', $connection->commands[0]->sql);
		$this->assertSame('SELECT pg_advisory_unlock(:key1, :key2)', $connection->commands[1]->sql);
		$this->assertSame([':key1' => $key1, ':key2' => $key2], $connection->commands[0]->bound);
		$this->assertSame(\PDO::PARAM_INT, $connection->commands[0]->types[':key1']);
	}

	public function testFalseResultIsNotAcquired(): void
	{
		$mutex = $this->newMutex();
		$mutex->testConnection->results = [[false], ['f']];
		$this->assertFalse($mutex->acquire('job'));
		$this->assertFalse($mutex->acquire('job'));
	}

	public function testOtherDriverThrows(): void
	{
		$mutex = $this->newMutex(new TTestLockConnection('mysql'));
		$this->expectException(TConfigurationException::class);
		$mutex->acquire('job');
	}

	public function testSessionsExcludeEachOther(): void
	{
		$first = $this->setUpConnection();
		$second = $this->setUpConnection();
		if ($first === null || $second === null) {
			$this->markTestSkipped('No PostgreSQL connection.');
		}
		$prefix = 'prado-unit-' . bin2hex(random_bytes(4)) . ':';
		$a = $this->newMutex($first)->setKeyPrefix($prefix);
		$b = $this->newMutex($second)->setKeyPrefix($prefix);
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.05));
		$this->assertTrue($b->acquire('other'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$b->releaseAll();
	}

	public function testClosedSessionReleasesLock(): void
	{
		$first = $this->setUpConnection();
		$second = $this->setUpConnection();
		if ($first === null || $second === null) {
			$this->markTestSkipped('No PostgreSQL connection.');
		}
		$prefix = 'prado-unit-' . bin2hex(random_bytes(4)) . ':';
		$a = $this->newMutex($first)->setKeyPrefix($prefix)->setAutoRelease(false);
		$b = $this->newMutex($second)->setKeyPrefix($prefix);
		$this->assertTrue($a->acquire('job'));
		$first->setActive(false);
		$this->assertTrue($b->acquire('job', 1));
		$b->releaseAll();
	}
}
