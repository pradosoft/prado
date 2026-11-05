<?php

/**
 * TDbTableMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Data\IDataConnection;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Mutex\TDbTableMutex;

/**
 * A TDbTableMutex over an injected connection.
 */
class TDbTableMutexTestMutex extends TDbTableMutex
{
	public ?IDataConnection $testConnection = null;

	public function getDbConnection(): IDataConnection
	{
		return $this->testConnection ?? parent::getDbConnection();
	}
}

/**
 * Unit tests for {@see TDbTableMutex} over SQLite: the lock table, exclusion
 * between two connections, leases, and token-checked release.
 */
class TDbTableMutexTest extends \PHPUnit\Framework\TestCase
{
	private string $file;

	private TMockClock $clock;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$this->file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-dbtablemutex-' . bin2hex(random_bytes(6)) . '.db';
		$this->clock = (new TMockClock())->setTime(1_000_000);
	}

	protected function tearDown(): void
	{
		if (is_file($this->file)) {
			@unlink($this->file);
		}
	}

	private function newConnection(): TDbConnection
	{
		$connection = new TDbConnection('sqlite:' . $this->file);
		$connection->setActive(true);
		return $connection;
	}

	private function newMutex(?TDbConnection $connection = null): TDbTableMutexTestMutex
	{
		$mutex = new TDbTableMutexTestMutex();
		$mutex->testConnection = $connection ?? $this->newConnection();
		$mutex->setClock($this->clock);
		$mutex->setRetryDelay(1);
		return $mutex;
	}

	private function rows(TDbConnection $connection, string $table = 'pradomutex'): array
	{
		return $connection->createCommand("SELECT lockname, locktoken, expire FROM {$table} ORDER BY lockname")->queryAll();
	}

	public function testSupportsEveryDriver(): void
	{
		$this->assertSame([], (new TDbTableMutex())->getSupportedDrivers());
	}

	public function testCreatesTableAndRow(): void
	{
		$mutex = $this->newMutex();
		$mutex->setExpire(45);
		$this->assertTrue($mutex->acquire('job'));
		$rows = $this->rows($mutex->testConnection);
		$this->assertCount(1, $rows);
		$this->assertSame('job', $rows[0]['lockname']);
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $rows[0]['locktoken']);
		$this->assertEquals(1_000_045, $rows[0]['expire']);
		$this->assertTrue($mutex->release('job'));
		$this->assertSame([], $this->rows($mutex->testConnection));
	}

	public function testConnectionsExcludeEachOther(): void
	{
		$a = $this->newMutex();
		$b = $this->newMutex();
		$this->assertTrue($a->acquire('job'));
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.02));
		$this->assertTrue($b->acquire('other'));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$b->releaseAll();
	}

	public function testExpiredLeaseIsTaken(): void
	{
		$a = $this->newMutex()->setExpire(10);
		$b = $this->newMutex()->setExpire(10);
		$this->assertTrue($a->acquire('job'));
		$this->clock->sleep(10);
		$this->assertFalse($b->acquire('job'));
		$this->clock->sleep(1);
		$this->assertTrue($b->acquire('job'));
		$this->assertFalse($a->release('job'));
		$this->assertCount(1, $this->rows($b->testConnection));
		$this->assertTrue($b->release('job'));
	}

	public function testForkedChildKeepsParentRow(): void
	{
		$mutex = $this->newMutex();
		$mutex->acquire('job');
		$mutex->fxRestoreAfterFork(null, ['pid' => 0]);
		$this->assertFalse($mutex->isAcquired('job'));
		$this->assertCount(1, $this->rows($mutex->testConnection));
	}

	public function testCustomTableName(): void
	{
		$mutex = $this->newMutex();
		$mutex->setMutexTableName('app_mutex');
		$this->assertSame('app_mutex', $mutex->getMutexTableName());
		$this->assertTrue($mutex->acquire('job'));
		$this->assertCount(1, $this->rows($mutex->testConnection, 'app_mutex'));
		$mutex->release('job');
	}

	public function testInvalidTableNameThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TDbTableMutex())->setMutexTableName('bad name; DROP');
	}

	public function testMissingTableWithoutAutoCreateThrows(): void
	{
		$mutex = $this->newMutex();
		$mutex->setAutoCreateMutexTable('false');
		$this->assertFalse($mutex->getAutoCreateMutexTable());
		$this->expectException(\Exception::class);
		$mutex->acquire('job');
	}

	public function testLongNameBecomesHash(): void
	{
		$mutex = $this->newMutex();
		$name = str_repeat('n', 192);
		$this->assertSame(sha1($name), $mutex->getServerLockName($name));
		$this->assertTrue($mutex->acquire($name));
		$this->assertSame(sha1($name), $this->rows($mutex->testConnection)[0]['lockname']);
		$mutex->release($name);
	}

	public function testExpire(): void
	{
		$mutex = new TDbTableMutex();
		$this->assertSame(TDbTableMutex::DEFAULT_EXPIRE, $mutex->getExpire());
		$this->expectException(TInvalidDataValueException::class);
		$mutex->setExpire(-1);
	}
}
