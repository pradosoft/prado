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
