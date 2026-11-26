<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Cron\TDbCronManager;

/**
 * Runs TDbCronManager against each database driver: its table, task rows, run log, and claim.
 */
class TDbCronManagerDriverTest extends \PHPUnit\Framework\TestCase
{
	/** @var array{0: TDbConnection, 1: string}[] the tables to drop after the test */
	private array $_tables = [];

	/** @var TDbCronManager[] the managers to unlisten after the test */
	private array $_managers = [];

	protected function tearDown(): void
	{
		foreach ($this->_tables as [$conn, $table]) {
			$conn->setActive(true);
			$conn->createCommand('DROP TABLE IF EXISTS ' . $table)->execute();
		}
		foreach ($this->_managers as $manager) {
			$manager->unlisten();
		}
		$this->_tables = [];
		$this->_managers = [];
	}

	public static function driverProvider(): array
	{
		return [
			'sqlite' => [TDbDriver::DRIVER_SQLITE],
			'mysql' => [TDbDriver::DRIVER_MYSQL],
			'pgsql' => [TDbDriver::DRIVER_PGSQL],
		];
	}

	private function driverConnection(string $driver): TDbConnection
	{
		$conn = match ($driver) {
			TDbDriver::DRIVER_SQLITE => new TDbConnection('sqlite::memory:'),
			TDbDriver::DRIVER_MYSQL => PradoUnit::setupMysqlConnection('prado_unitest'),
			TDbDriver::DRIVER_PGSQL => PradoUnit::setupPgsqlConnection('prado_unitest'),
		};
		if (is_array($conn)) {
			PradoUnit::failFirstThenSkip($this, $conn[0], null, $conn[1]);
		} elseif ($conn instanceof \Exception) {
			throw $conn;
		}
		$conn->setActive(true);
		return $conn;
	}

	private function driverManager(TDbConnection $conn, string $table, array $tasks = []): TDbCronManager
	{
		$conn->createCommand('DROP TABLE IF EXISTS ' . $table)->execute();
		$this->_tables[] = [$conn, $table];
		$manager = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($manager, '_conn', $conn);
		$manager->setTableName($table);
		$manager->init($tasks);
		return $manager;
	}

	private function newTask(string $name): TTestCronModuleTask
	{
		$task = new TTestCronModuleTask();
		$task->setName($name);
		$task->setSchedule('* * * * *');
		return $task;
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testTwoTablesInOneDatabaseRunAndLogTheirTasks(string $driver)
	{
		$conn = $this->driverConnection($driver);
		$first = $this->driverManager($conn, 'crondrv_first');
		$second = $this->driverManager($conn, 'crondrv_second');

		self::assertTrue($first->addTask($this->newTask('drvtask')));
		self::assertTrue($second->addTask($this->newTask('drvtask')));
		self::assertTrue($first->taskExists('drvtask'), 'the count and the user name are one aggregate row');
		self::assertFalse($first->taskExists('drvnone'));
		self::assertTrue($first->runTaskNow($first->getTask('drvtask')));

		$results = $first->getTaskResults();
		self::assertSame(TDbCronManager::RESULT_OK, $results['drvtask']['result']);
		self::assertSame(1, $results['drvtask']['count']);
		self::assertSame(1, $results['drvtask']['ok']);
		self::assertNull($second->getTaskResults()['drvtask']['result'], 'the other table is untouched');

		$log = $first->getCronLog('drvtask');
		self::assertCount(1, $log);
		self::assertSame(TDbCronManager::RESULT_OK, $log[0]['lastresult'], 'the run log row has the result');

		self::assertTrue($first->removeTask('drvtask'));
		self::assertNull($first->getTask('drvtask'));
		self::assertNotNull($second->getTask('drvtask'));
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testAFreshManagerReadsTheStoredTaskBack(string $driver)
	{
		$conn = $this->driverConnection($driver);
		$manager = $this->driverManager($conn, 'crondrv_readback');
		$task = $this->newTask('drvread');
		$task->setPropertyA("a value with a \0 NUL byte");
		self::assertStringContainsString("\0", serialize($task), 'a serialized task holds NUL bytes');
		self::assertTrue($manager->addTask($task));

		$reader = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($reader, '_conn', $conn);
		$reader->setTableName('crondrv_readback');
		$reader->init(null);
		$read = $reader->getTask('drvread');
		self::assertInstanceOf(TTestCronModuleTask::class, $read);
		self::assertSame("a value with a \0 NUL byte", $read->getPropertyA());
		self::assertSame($task->getSchedule(), $read->getSchedule());
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testATriggerAfter2038IsStored(string $driver)
	{
		$conn = $this->driverConnection($driver);
		$manager = $this->driverManager($conn, 'crondrv_y2038');
		$task = $this->newTask('drvfar');
		$task->setSchedule('0 0 1 1 ? 2099');
		self::assertTrue($manager->addTask($task));
		$stored = (int) $conn->createCommand("SELECT nextexectime FROM crondrv_y2038 WHERE name='drvfar'")->queryScalar();
		self::assertGreaterThan(2 ** 31, $stored);
		self::assertSame((int) $task->getNextTriggerTime(), $stored);
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testAConfiguredTaskKeepsItsRowAndTakesItsNewConfiguration(string $driver)
	{
		$conn = $this->driverConnection($driver);
		$job = ['name' => 'drvkeep', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class];
		$first = $this->driverManager($conn, 'crondrv_keep', [$job]);
		$first->getTasks();
		$tabuid = $conn->createCommand("SELECT tabuid FROM crondrv_keep WHERE name='drvkeep'")->queryScalar();

		$same = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($same, '_conn', $conn);
		$same->setTableName('crondrv_keep');
		$same->init([$job]);
		self::assertTrue(PradoUnit::invoke($same, 'setPersistentData', 'drvkeep', $same->getTask('drvkeep')), 'an unchanged configuration keeps its row');

		$changed = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($changed, '_conn', $conn);
		$changed->setTableName('crondrv_keep');
		$changed->init([['schedule' => '0 0 1 1 ? 2098'] + $job]);
		$changed->getTasks();
		$row = $conn->createCommand("SELECT tabuid, schedule FROM crondrv_keep WHERE name='drvkeep'")->queryRow();
		self::assertSame([(string) $tabuid, '0 0 1 1 ? 2098'], [(string) $row['tabuid'], $row['schedule']], 'a changed configuration updates its row');
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testAConfiguredTaskNoLongerConfiguredLosesItsRow(string $driver)
	{
		$conn = $this->driverConnection($driver);
		$configured = $this->driverManager($conn, 'crondrv_config', [
			['name' => 'drvstale', 'schedule' => '* * * * *', 'task' => TTestCronModuleTask::class],
		]);
		$configured->getTasks();
		self::assertSame(1, (int) $conn->createCommand("SELECT COUNT(*) FROM crondrv_config WHERE name='drvstale'")->queryScalar());

		$manager = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($manager, '_conn', $conn);
		$manager->setTableName('crondrv_config');
		$manager->init([['name' => 'drvother', 'schedule' => '* * * * *', 'task' => TTestCronModuleTask::class]]);
		$manager->getTasks();
		self::assertSame(0, (int) $conn->createCommand("SELECT COUNT(*) FROM crondrv_config WHERE name='drvstale'")->queryScalar());
		self::assertSame(1, (int) $conn->createCommand("SELECT COUNT(*) FROM crondrv_config WHERE name='drvother'")->queryScalar());
	}
}
