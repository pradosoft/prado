<?php

/**
 * TDbMutexTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Mutex;

use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;
use Prado\Test\Unit\Harness\Util\Mutex\TTestLockConnection;
use Prado\Util\Mutex\TDbMutex;
use Prado\Util\Mutex\TDbTableMutex;
use Prado\Util\Mutex\TMssqlMutex;
use Prado\Util\Mutex\TMysqlMutex;
use Prado\Util\Mutex\TOracleMutex;
use Prado\Util\Mutex\TPgsqlMutex;

/**
 * Unit tests for {@see TDbMutex}: driver detection from connections and DSNs, routing
 * to each server lock, the lock-table fallback, and property forwarding.  Exclusion
 * between two PostgreSQL sessions runs against a live server (skipped without one).
 */
class TDbMutexTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	private ?string $file = null;

	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
		if ($this->file !== null && is_file($this->file)) {
			@unlink($this->file);
		}
	}

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupPgsqlConnection';
	}

	protected function getTestTables(): array
	{
		return [];
	}

	private function newSqliteConnection(): TDbConnection
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$this->file ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-dbmutex-' . bin2hex(random_bytes(6)) . '.db';
		$connection = new TDbConnection('sqlite:' . $this->file);
		$connection->setActive(true);
		return $connection;
	}

	public static function driverProvider(): array
	{
		return [
			'mysql driver' => ['mysql', TMysqlMutex::class],
			'mysql dsn' => ['mysql:host=localhost;dbname=app', TMysqlMutex::class],
			'pgsql dsn' => ['pgsql:host=localhost', TPgsqlMutex::class],
			'oci dsn' => ['oci:dbname=//localhost/XE', TOracleMutex::class],
			'sqlsrv dsn' => ['sqlsrv:Server=localhost', TMssqlMutex::class],
			'dblib dsn' => ['dblib:host=localhost', TMssqlMutex::class],
			'mssql driver' => ['MSSQL', TMssqlMutex::class],
			'sqlite dsn' => ['sqlite:/tmp/app.db', null],
			'firebird dsn' => ['firebird:dbname=localhost:/db.fdb', null],
			'ibm driver' => ['ibm', null],
		];
	}

	/**
	 * @dataProvider driverProvider
	 * @param string $source
	 * @param ?string $class
	 */
	public function testServerMutexClass(string $source, ?string $class): void
	{
		$this->assertSame($class, TDbMutex::getServerMutexClass($source));
		$this->assertSame($class !== null, TDbMutex::getIsServerLockAvailable($source));
	}

	public function testDriverName(): void
	{
		$this->assertSame('mysql', TDbMutex::getDriverName('MySQL:host=x'));
		$this->assertSame('pgsql', TDbMutex::getDriverName('pgsql'));
		$this->assertSame('oci', TDbMutex::getDriverName(new TTestLockConnection('oci')));
		$this->assertSame(TOracleMutex::class, TDbMutex::getServerMutexClass(new TTestLockConnection('oci')));
	}

	public function testIsDriverSupported(): void
	{
		$this->assertTrue(TMysqlMutex::isDriverSupported('MYSQL'));
		$this->assertFalse(TMysqlMutex::isDriverSupported('pgsql'));
		$this->assertTrue(TDbTableMutex::isDriverSupported('anything'));
		$this->assertTrue(TDbMutex::isDriverSupported('anything'));
	}

	public function testRoutesToServerLock(): void
	{
		$connection = new TTestLockConnection('mysql');
		$connection->results = [[1], [1]];
		$mutex = (new TDbMutex())->setDbConnection($connection)->setKeyPrefix('app:');
		$this->assertTrue($mutex->acquire('job'));
		$driverMutex = $mutex->getDriverMutex();
		$this->assertInstanceOf(TMysqlMutex::class, $driverMutex);
		$this->assertSame($connection, $driverMutex->getDbConnection());
		$this->assertFalse($driverMutex->getAutoRelease());
		$this->assertSame('SELECT GET_LOCK(:name, 0)', $connection->commands[0]->sql);
		$this->assertSame('app:job', $connection->commands[0]->bound[':name']);
		$this->assertTrue($mutex->isAcquired('job'));
		$this->assertTrue($driverMutex->isAcquired('job'));
		$this->assertTrue($mutex->release('job'));
		$this->assertSame('SELECT RELEASE_LOCK(:name)', $connection->commands[1]->sql);
		$this->assertFalse($driverMutex->isAcquired('job'));
	}

	public function testRoutesEachServerDriver(): void
	{
		foreach (['pgsql' => TPgsqlMutex::class, 'oci' => TOracleMutex::class, 'dblib' => TMssqlMutex::class] as $driver => $class) {
			$mutex = (new TDbMutex())->setDbConnection(new TTestLockConnection($driver));
			$this->assertInstanceOf($class, $mutex->getDriverMutex());
		}
	}

	public function testKeyPrefixSetLaterPassesToDriverMutex(): void
	{
		$mutex = (new TDbMutex())->setDbConnection(new TTestLockConnection('pgsql'));
		$driverMutex = $mutex->getDriverMutex();
		$mutex->setKeyPrefix('late:');
		$this->assertSame('late:', $driverMutex->getKeyPrefix());
	}

	public function testNewConnectionChoosesAgain(): void
	{
		$mutex = (new TDbMutex())->setDbConnection(new TTestLockConnection('pgsql'));
		$this->assertInstanceOf(TPgsqlMutex::class, $mutex->getDriverMutex());
		$mutex->setDbConnection(new TTestLockConnection('mysql'));
		$this->assertInstanceOf(TMysqlMutex::class, $mutex->getDriverMutex());
	}

	public function testTableFallbackForSqlite(): void
	{
		$a = (new TDbMutex())->setDbConnection($this->newSqliteConnection())->setRetryDelay(1);
		$b = (new TDbMutex())->setDbConnection($this->newSqliteConnection())->setRetryDelay(1);
		$this->assertTrue($a->getTableFallback());
		$this->assertTrue($a->acquire('job'));
		$this->assertInstanceOf(TDbTableMutex::class, $a->getDriverMutex());
		$this->assertFalse($b->acquire('job'));
		$this->assertFalse($b->acquire('job', 0.02));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$b->releaseAll();
	}

	public function testTablePropertiesPassToFallback(): void
	{
		$mutex = (new TDbMutex())->setDbConnection($this->newSqliteConnection());
		$this->assertNull($mutex->getExpire());
		$this->assertNull($mutex->getMutexTableName());
		$this->assertNull($mutex->getAutoCreateMutexTable());
		$mutex->setExpire('90')->setMutexTableName('app_mutex')->setAutoCreateMutexTable('true');
		$table = $mutex->getDriverMutex();
		$this->assertSame(90, $table->getExpire());
		$this->assertSame('app_mutex', $table->getMutexTableName());
		$this->assertTrue($table->getAutoCreateMutexTable());
		$mutex->setExpire(15);
		$this->assertSame(15, $table->getExpire());
		$this->assertTrue($mutex->acquire('job'));
		$this->assertTrue($mutex->release('job'));
	}

	public function testNoTableFallbackThrows(): void
	{
		$mutex = (new TDbMutex())->setDbConnection(new TTestLockConnection('sqlite'));
		$mutex->setTableFallback('false');
		$this->assertFalse($mutex->getTableFallback());
		try {
			$mutex->acquire('job');
			$this->fail('TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertStringContainsString("'sqlite'", $e->getMessage());
			$this->assertStringContainsString('mysql, pgsql, oci, sqlsrv, dblib, mssql', $e->getMessage());
		}
	}

	public function testDestructReleasesThroughDriverMutex(): void
	{
		$connection = new TTestLockConnection('mysql');
		$connection->results = [[1], [1]];
		$mutex = (new TDbMutex())->setDbConnection($connection);
		$mutex->acquire('job');
		unset($mutex);
		$this->assertCount(2, $connection->commands);
		$this->assertSame('SELECT RELEASE_LOCK(:name)', $connection->commands[1]->sql);
	}

	public function testPgsqlSessionsExcludeEachOther(): void
	{
		$first = $this->setUpConnection();
		$second = $this->setUpConnection();
		if ($first === null || $second === null) {
			$this->markTestSkipped('No PostgreSQL connection.');
		}
		$prefix = 'prado-unit-' . bin2hex(random_bytes(4)) . ':';
		$a = (new TDbMutex())->setDbConnection($first)->setKeyPrefix($prefix)->setRetryDelay(1);
		$b = (new TDbMutex())->setDbConnection($second)->setKeyPrefix($prefix)->setRetryDelay(1);
		$this->assertTrue($a->acquire('job'));
		$this->assertInstanceOf(TPgsqlMutex::class, $a->getDriverMutex());
		$this->assertFalse($b->acquire('job', 0.05));
		$this->assertTrue($a->release('job'));
		$this->assertTrue($b->acquire('job'));
		$b->releaseAll();
	}
}
