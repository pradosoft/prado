<?php

/**
 * TDbCronManagerDriverTestTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;
use Prado\Test\Unit\Util\Cron\TTestCronModuleTask;
use Prado\Util\Cron\TDbCronManager;

/**
 * TDbCronManagerDriverTestTrait runs {@see TDbCronManager} against a live database server of a
 * driver with column definitions ({@see TDbCronManager::getColumnDefinitions()}): it creates, indexes,
 * and migrates its table, and stores, runs, logs, and removes its tasks.  A test class in a
 * `DbSpecific` suite uses it and names its driver through
 * {@see PradoUnitDataConnectionTrait::getDbDriver()}.  The tables are named for the process and
 * dropped after each test, on a connection of their own.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TDbCronManagerDriverTestTrait
{
	use PradoUnitDataConnectionTrait;

	/** @var string[] the tables to drop after the test */
	private array $_cronTables = [];

	/** @var TDbCronManager[] the managers to unlisten after the test */
	private array $_cronManagers = [];

	private ?TDbConnection $_cronConnection = null;

	protected function getTestTables(): array
	{
		return [];
	}

	protected function setUp(): void
	{
		if (($connection = $this->setUpConnection()) === null) {
			$this->markTestSkipped('No ' . $this->getDbDriver() . ' connection.');
		}
		$this->_cronConnection = $connection;
	}

	protected function tearDown(): void
	{
		foreach ($this->_cronManagers as $manager) {
			$manager->unlisten();
		}
		$this->_cronManagers = [];
		$this->_cronConnection?->setActive(false);
		$this->_cronConnection = null;
		foreach ($this->_cronTables as $table) {
			$this->dropCronTable($table);
		}
		$this->_cronTables = [];
	}

	/**
	 * Drops a table when it exists, on a connection of its own.
	 * @param string $table the table
	 */
	private function dropCronTable(string $table): void
	{
		$conn = $this->setUpConnection();
		try {
			$conn->createCommand('DROP TABLE ' . $table)->execute();
		} catch (TDbException $e) {
		}
		$conn->setActive(false);
	}

	/**
	 * @param string $name the table name, without the process suffix
	 * @return string the table name for this process, which is dropped after the test
	 */
	private function cronTable(string $name): string
	{
		$table = $name . '_' . getmypid();
		$this->dropCronTable($table);
		$this->_cronTables[] = $table;
		return $table;
	}

	private function cronManager(string $table): TDbCronManager
	{
		$manager = $this->_cronManagers[] = new TDbCronManager();
		PradoUnit::setProp($manager, '_conn', $this->_cronConnection);
		$manager->setTableName($table);
		$manager->init(null);
		return $manager;
	}

	private function cronTask(string $name, string $schedule = '* * * * *'): TTestCronModuleTask
	{
		$task = new TTestCronModuleTask();
		$task->setName($name);
		$task->setSchedule($schedule);
		return $task;
	}

	public function testCreatesTheTableAndItsIndexes(): void
	{
		$manager = $this->cronManager($this->cronTable('crondrv_create'));
		self::assertSame(['created' => true, 'column' => null, 'run' => false], $manager->migrateTable());
		self::assertSame(['created' => false, 'column' => null, 'run' => false], $manager->migrateTable(), 'a second migration changes nothing');
		$definitions = PradoUnit::invoke($manager, 'getColumnDefinitions');
		self::assertIsArray($definitions);
		foreach (array_keys($definitions) as $column) {
			self::assertTrue(PradoUnit::invoke($manager, 'columnExists', $column), $column);
		}
	}

	public function testMigratesATableCreatedBefore440(): void
	{
		$table = $this->cronTable('crondrv_old');
		$manager = $this->cronManager($table);
		$definitions = PradoUnit::invoke($manager, 'getColumnDefinitions');
		$columns = [];
		foreach ($definitions as $column => $definition) {
			if ($column !== 'nextexectime' && !isset(TDbCronManager::RUN_COLUMNS[$column])) {
				$columns[] = $column . ' ' . $definition;
			}
		}
		$this->_cronConnection->createCommand('CREATE TABLE ' . $table . ' (' . implode(', ', $columns) . ')')->execute();
		$cmd = $this->_cronConnection->createCommand("INSERT INTO {$table} (name, schedule, task, options, active) VALUES ('drvold', '0 0 1 1 ? 2099', 'x', :options, 1)");
		$cmd->bindValue(':options', base64_encode(serialize($this->cronTask('drvold', '0 0 1 1 ? 2099'))));
		$cmd->execute();

		self::assertSame(['created' => false, 'column' => 1, 'run' => true], $manager->migrateTable());
		foreach (array_keys($definitions) as $column) {
			self::assertTrue(PradoUnit::invoke($manager, 'columnExists', $column), $column);
		}
		$stored = (int) $this->_cronConnection->createCommand("SELECT nextexectime FROM {$table} WHERE name='drvold'")->queryScalar();
		self::assertGreaterThan(2 ** 31, $stored, 'the trigger after 2038 is filled');
	}

	public function testStoresRunsLogsAndRemovesTasks(): void
	{
		$table = $this->cronTable('crondrv_tasks');
		$manager = $this->cronManager($table);
		$task = $this->cronTask('drvtask');
		$task->setPropertyA("a value with a \0 NUL byte");
		self::assertTrue($manager->addTask($task));
		self::assertFalse($manager->addTask($this->cronTask('drvtask')), 'the name is taken');
		self::assertTrue($manager->taskExists('drvtask'));
		self::assertTrue($manager->addTask($this->cronTask('drvfar', '0 0 1 1 ? 2099')));
		self::assertNotEmpty($manager->getTask('drvtask', true, false)['tabuid'], 'the inserted row id');

		$reader = $this->cronManager($table);
		$read = $reader->getTask('drvtask');
		self::assertInstanceOf(TTestCronModuleTask::class, $read);
		self::assertSame("a value with a \0 NUL byte", $read->getPropertyA());
		self::assertSame(['drvfar', 'drvtask'], array_keys($reader->getTasks()));
		self::assertSame(['drvtask'], array_keys($reader->getTasks(null, 1, 1)), 'the second page of one task');
		self::assertCount(1, $reader->getTasks(null, 1, 0, null), 'an unordered page');
		self::assertSame(2, $reader->getTasksCount());

		self::assertTrue($manager->runTaskNow($manager->getTask('drvtask')));
		$results = $manager->getTaskResults();
		self::assertSame(TDbCronManager::RESULT_OK, $results['drvtask']['result']);
		self::assertSame(1, $results['drvtask']['count']);
		self::assertSame(1, $results['drvtask']['ok']);
		self::assertNotNull($results['drvtask']['max']);

		$log = $manager->getCronLog('drvtask');
		self::assertCount(1, $log);
		self::assertSame(TDbCronManager::RESULT_OK, $log[0]['lastresult'], 'the run log row has the result');
		self::assertSame(1, $manager->getCronLogCount('drvtask'));

		self::assertSame(['drvtask'], array_keys($manager->getNextTriggers(1)));
		$far = (int) $this->_cronConnection->createCommand("SELECT nextexectime FROM {$table} WHERE name='drvfar' AND active IS NOT NULL")->queryScalar();
		self::assertGreaterThan(2 ** 31, $far);

		self::assertSame(1, $manager->clearCronLog());
		self::assertTrue($manager->removeTask('drvtask'));
		self::assertNull($manager->getTask('drvtask'));
		self::assertFalse($manager->taskExists('drvtask'));
	}
}
