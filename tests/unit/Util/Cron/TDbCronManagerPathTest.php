<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Cron\TDbCronManager;

/**
 * A TDbCronManager whose {@see taskExistsInternal()} reports the first name it checks as taken.
 */
class TDbCronManagerPathTestTakenName extends TDbCronManager
{
	public array $checked = [];

	protected function taskExistsInternal($name)
	{
		$this->checked[] = $name;
		return count($this->checked) === 1 || parent::taskExistsInternal($name);
	}
}

/**
 * A TDbCronManager that counts its wakes instead of sending them, on a clock the test sets.
 */
class TDbCronManagerPathTestWaking extends TDbCronManager
{
	public int $wakes = 0;

	public ?int $now = null;

	public function signalWake(): bool
	{
		$this->wakes++;
		return true;
	}

	public function time(): int
	{
		return $this->now ?? parent::time();
	}
}

/**
 * Tests of TDbCronManager branches on an in-memory SQLite table of each test's own.
 */
class TDbCronManagerPathTest extends \PHPUnit\Framework\TestCase
{
	/** @var TDbCronManager[] the managers to unlisten after the test */
	private array $_managers = [];

	protected function tearDown(): void
	{
		foreach ($this->_managers as $manager) {
			$manager->clearRuntimeTasks();
			$manager->unlisten();
		}
		$this->_managers = [];
	}

	private function newManager(?array $tasks = null, string $class = TDbCronManager::class): TDbCronManager
	{
		$manager = $this->_managers[] = new $class();
		$conn = new TDbConnection('sqlite::memory:');
		$conn->setActive(true);
		PradoUnit::setProp($manager, '_conn', $conn);
		$manager->init($tasks);
		return $manager;
	}

	private function newTask(string $name, string $schedule = '0 0 1 1 ? 2099'): TTestCronModuleTask
	{
		$task = new TTestCronModuleTask();
		$task->setName($name);
		$task->setSchedule($schedule);
		return $task;
	}

	public function testGetEarliestPendingTime()
	{
		$manager = $this->newManager();
		self::assertNull(PradoUnit::invoke($manager, 'getEarliestPendingTime'), 'no rows');
		self::assertTrue($manager->addTask($task = $this->newTask('earliest')));
		self::assertSame((int) $task->getNextTriggerTime(), PradoUnit::invoke($manager, 'getEarliestPendingTime'));
	}

	public function testAddTaskWithTheGlobalPermissionOnly()
	{
		$manager = $this->newManager();
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyAddTask']));
		self::assertTrue($manager->addTask($this->newTask('globalAdd')), 'the global permission adds a task for any user');
		self::assertFalse($manager->addTask($this->newTask('bad name')), 'an invalid name is not added');
		$manager->detachBehavior('deny');
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyAddTask', 'dyAddTaskGlobal']));
		self::assertNull($manager->addTask($this->newTask('deniedAdd')));
	}

	public function testRemoveTaskOfAConfiguredMissingOrDeniedTask()
	{
		$manager = $this->newManager([['name' => 'configured', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		self::assertFalse(PradoUnit::invoke($manager, 'removeTaskInternal', 'configured'), 'a configured task stays');
		self::assertFalse(PradoUnit::invoke($manager, 'removeTaskInternal', 'noSuchTask'));
		self::assertFalse($manager->removeTask('noSuchTask'));
		self::assertTrue($manager->addTask($this->newTask('deniedRemove')));
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyRemoveTask']));
		self::assertNull($manager->removeTask('deniedRemove'));
	}

	public function testRemoveOneOfTwoRuntimeTasks()
	{
		$manager = $this->newManager();
		$manager->addRuntimeTask($first = $this->newTask('runtimeOne'));
		$manager->addRuntimeTask($this->newTask('runtimeTwo'));
		$manager->removeRuntimeTask($first);
		self::assertSame(['runtimeTwo'], array_keys($manager->getRuntimeTasks()));
		$manager->removeRuntimeTask('runtimeTwo');
		self::assertNull($manager->getRuntimeTasks());
		$manager->removeRuntimeTask('runtimeTwo');
	}

	public function testToDateInterval()
	{
		$manager = $this->newManager();
		self::assertSame(1, PradoUnit::invoke($manager, 'toDateInterval', 'P1D')->d);
		self::assertSame(2, PradoUnit::invoke($manager, 'toDateInterval', '2 days')->d);
		self::assertSame(90, PradoUnit::invoke($manager, 'toDateInterval', 90)->s);
		$interval = new \DateInterval('PT1H');
		self::assertSame($interval, PradoUnit::invoke($manager, 'toDateInterval', $interval));
		foreach (['Pnot a period', 'not a period at all'] as $period) {
			try {
				PradoUnit::invoke($manager, 'toDateInterval', $period);
				self::fail('the period ' . $period . ' was accepted');
			} catch (TInvalidDataValueException $e) {
			}
		}
	}

	public function testAnEmptyUserNameMatchesNoDefaultUser()
	{
		$manager = $this->newManager();
		self::assertFalse(PradoUnit::invoke($manager, 'matchesDefaultUserName', null));
		self::assertFalse(PradoUnit::invoke($manager, 'matchesDefaultUserName', ''));
		self::assertTrue(PradoUnit::invoke($manager, 'matchesDefaultUserName', $manager->getDefaultUserName()));
	}

	public function testRemoveCronLogItemOfAUser()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($task = $this->newTask('loggedTask')));
		$task->setUserName('admin');
		$logId = PradoUnit::invoke($manager, 'logCronTask', $task, 'admin');
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyRemoveCronLogItem']));
		self::assertNull($manager->removeCronLogItem($logId));
		$manager->detachBehavior('deny');
		self::assertTrue($manager->removeCronLogItem($logId));
		self::assertSame(0, (int) $manager->getCronLogCount());
		self::assertTrue($manager->removeCronLogItem(999999), 'an unknown log row belongs to the default user');
	}

	public function testARowThatHoldsNoTaskIsSkipped()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($this->newTask('goodTask', '* * * * *')));
		self::assertTrue($manager->addTask($this->newTask('brokenTask', '* * * * *')));
		$conn = $manager->getDbConnection();
		$conn->createCommand("UPDATE crontabs SET nextexectime=1, lastexectime=0 WHERE active IS NOT NULL")->execute();
		$conn->createCommand("UPDATE crontabs SET options='O:15:\"NoSuchCronTask\":0:{}' WHERE name='brokenTask'")->execute();

		$fresh = function (): TDbCronManager {
			$manager = $this->_managers[] = new TDbCronManager();
			PradoUnit::setProp($manager, '_conn', $this->_managers[0]->getDbConnection());
			$manager->init(null);
			return $manager;
		};
		self::assertSame(['goodTask'], array_keys($fresh()->getPendingTasks()), 'the other tasks are pending');
		self::assertSame(1, $fresh()->processPendingTasks(), 'the other tasks run');
		self::assertSame(['goodTask'], array_keys($fresh()->getTasks()));
		$reader = $fresh();
		self::assertSame(['goodTask'], array_keys($reader->getUserTasks($reader->getDefaultUserName())));
		self::assertNull($fresh()->getTask('brokenTask'));
		$cached = $fresh();
		PradoUnit::setProp($cached, '_taskRows', ['brokenTask' => ['options' => 'not serialized']]);
		self::assertNull($cached->getTask('brokenTask'), 'a cached row that holds no task');
	}

	public function testADeferredTaskNameThatIsTakenIsDrawnAgain()
	{
		$manager = $this->newManager(null, TDbCronManagerPathTestTakenName::class);
		$name = PradoUnit::invoke($manager, 'generateDeferredTaskName');
		self::assertCount(2, $manager->checked);
		self::assertSame($manager->checked[1], $name);
		self::assertNotSame($manager->checked[0], $name);
	}

	public function testGetTaskResultsOfEachColumnState()
	{
		$manager = $this->newManager();
		self::assertSame([], $manager->getTaskResults(), 'no rows');
		self::assertTrue($manager->addTask($this->newTask('resultTask')));
		$conn = $manager->getDbConnection();
		$cases = [];
		foreach (['100', null] as $time) {
			foreach (['1.5', null] as $duration) {
				foreach (['2.5', null] as $max) {
					$cases[] = [$time, $duration, $max, [($time === null) ? null : 100, ($duration === null) ? null : 1.5, ($max === null) ? null : 2.5]];
				}
			}
		}
		foreach ($cases as [$time, $duration, $max, $expected]) {
			$cmd = $conn->createCommand('UPDATE crontabs SET lastexectime=:time, lastduration=:duration, maxduration=:max WHERE active IS NOT NULL');
			$cmd->bindValue(':time', $time);
			$cmd->bindValue(':duration', $duration);
			$cmd->bindValue(':max', $max);
			$cmd->execute();
			$result = $manager->getTaskResults()['resultTask'];
			self::assertSame($expected, [$result['time'], $result['duration'], $result['max']]);
		}
	}

	public function testAddTaskInternalOfAnInvalidScheduleAndAsARuntimeTask()
	{
		$manager = $this->newManager();
		$task = $this->newTask('badSchedule');
		$task->setSchedule('not a schedule');
		self::assertFalse(PradoUnit::invoke($manager, 'addTaskInternal', $task));
		self::assertTrue(PradoUnit::invoke($manager, 'addTaskInternal', $this->newTask('runtimeAdded'), true));
		self::assertSame(['runtimeAdded'], array_keys($manager->getRuntimeTasks()));
	}

	public function testUpdateTaskInternal()
	{
		$manager = $this->newManager();
		self::assertFalse(PradoUnit::invoke($manager, 'updateTaskInternal', $this->newTask('noSuchTask')));
		self::assertTrue($manager->addTask($this->newTask('updated')));
		self::assertFalse(PradoUnit::invoke($manager, 'updateTaskInternal', $this->newTask('updated', 'not a schedule')), 'an invalid schedule is not stored');
		$task = $manager->getTask('updated');
		$task->setLastExecTime(12345);
		self::assertTrue(PradoUnit::invoke($manager, 'updateTaskInternal', $task));
		self::assertSame(12345, (int) $task->getLastExecTime(), 'an unchanged schedule keeps the last run');
		$task->setSchedule('0 0 1 1 ? 2098');
		self::assertTrue(PradoUnit::invoke($manager, 'updateTaskInternal', $task));
		self::assertNotSame(12345, (int) $task->getLastExecTime(), 'a changed schedule resets the last run');

		$other = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($other, '_conn', $manager->getDbConnection());
		$other->init([['name' => 'updated', 'schedule' => '0 0 1 1 ? 2097', 'task' => TTestCronModuleTask::class]]);
		$configured = new TTestCronModuleTask();
		$configured->setName('updated');
		$configured->setSchedule('0 0 1 1 ? 2097');
		PradoUnit::setProp($other, '_taskRows', []);
		self::assertTrue(PradoUnit::invoke($other, 'updateTaskInternal', $configured), 'a configured task without a cached row');
		self::assertSame('0 0 1 1 ? 2097', PradoUnit::getProp($other, '_taskRows')['updated']['schedule']);
	}

	public function testTaskCountsByNameAndUser()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($this->newTask('countA')));
		$owned = $this->newTask('countB');
		$owned->setUserName('admin');
		self::assertTrue($manager->addTask($owned));
		self::assertSame(2, $manager->getTasksCount());
		self::assertSame(1, $manager->getTasksCount('countA'));
		self::assertSame(1, $manager->getUserTasksCount('admin'));
		self::assertSame(1, $manager->getUserTasksCount('admin', 'count%'));
		self::assertSame(0, $manager->getUserTasksCount('admin', 'countA'));
		self::assertSame(1, $manager->getUserTasksCount($manager->getDefaultUserName()), 'a row without a user is the default user\'s');
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyGetTasks', 'dyGetUserTasks']));
		self::assertNull($manager->getTasksCount());
		self::assertNull($manager->getUserTasksCount('admin'));
	}

	public function testRowUpdatesOfATaskWithoutACachedRow()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($task = $this->newTask('uncached')));
		PradoUnit::setProp($manager, '_taskRows', []);
		$task->setProcessCount(2);
		PradoUnit::invoke($manager, 'uncountTaskRun', $task);
		self::assertSame(1, $task->getProcessCount());
		PradoUnit::invoke($manager, 'updateTaskEnd', $task);
		self::assertSame([], PradoUnit::getProp($manager, '_taskRows'), 'no cached row is made');
		self::assertSame((int) $task->getLastExecTime(), (int) $manager->getDbConnection()->createCommand("SELECT lastexectime FROM crontabs WHERE name='uncached'")->queryScalar());
	}

	public function testSignalWakeIfEarliest()
	{
		$manager = $this->newManager(null, TDbCronManagerPathTestWaking::class);
		$now = $manager->now = (int) (floor(time() / 60) * 60 + 10);
		PradoUnit::invoke($manager, 'signalWakeIfEarliest', $this->newTask('noRow'));
		self::assertTrue($manager->addTask($this->newTask('later')), 'a trigger after this minute');
		self::assertSame(0, $manager->wakes);
		$soon = $this->newTask('soon', '* * * * *');
		self::assertTrue($manager->addTask($soon));
		$rows = PradoUnit::getProp($manager, '_taskRows');
		$rows['soon']['nextexectime'] = $now + 20;
		PradoUnit::setProp($manager, '_taskRows', $rows);
		PradoUnit::invoke($manager, 'signalWakeIfEarliest', $soon);
		self::assertSame(1, $manager->wakes, 'the earliest trigger of this minute wakes');
		$manager->getDbConnection()->createCommand("UPDATE crontabs SET nextexectime=" . ($now + 5) . " WHERE name='later'")->execute();
		PradoUnit::invoke($manager, 'signalWakeIfEarliest', $soon);
		self::assertSame(1, $manager->wakes, 'a later trigger of this minute does not wake');
		$manager->getDbConnection()->createCommand('DELETE FROM crontabs')->execute();
		PradoUnit::invoke($manager, 'signalWakeIfEarliest', $soon);
		self::assertSame(2, $manager->wakes, 'without a pending row the trigger is the earliest');
	}

	public function testAddTheNextTriggerColumnToAnEmptyOldTable()
	{
		$manager = $this->_managers[] = new TDbCronManager();
		$conn = new TDbConnection('sqlite::memory:');
		$conn->setActive(true);
		$conn->createCommand('CREATE TABLE crontabs (tabuid INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(127) NOT NULL, schedule VARCHAR(127) NOT NULL, task VARCHAR(256) NOT NULL, moduleid VARCHAR(127) NULL, username VARCHAR(127) NULL, options MEDIUMTEXT NULL, processcount INT NOT NULL DEFAULT 0, lastexectime VARCHAR(20) NULL DEFAULT NULL, active BOOLEAN NULL)')->execute();
		PradoUnit::setProp($manager, '_conn', $conn);
		self::assertSame(0, PradoUnit::invoke($manager, 'ensureNextExecTimeColumn'), 'no rows to fill');
		self::assertNull(PradoUnit::invoke($manager, 'ensureNextExecTimeColumn'), 'the column exists');
	}

	public function testNoNextTriggersForAZeroLimit()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($this->newTask('limited')));
		self::assertSame([], $manager->getNextTriggers(0));
		self::assertSame(['limited'], array_keys($manager->getNextTriggers(1)));
	}

	public function testAConfiguredNameOfADatabaseTaskConflicts()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($this->newTask('clash')));
		self::assertFalse($manager->addTask($this->newTask('clash')), 'a name is added once');
		$configured = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($configured, '_conn', $manager->getDbConnection());
		$configured->init([['name' => 'clash', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		$this->expectException(TConfigurationException::class);
		$configured->getTasks();
	}

	public function testRemoveTheLastRuntimeTaskByObject()
	{
		$manager = $this->newManager();
		$manager->addRuntimeTask($task = $this->newTask('lastRuntime'));
		$manager->removeRuntimeTask($task);
		self::assertNull($manager->getRuntimeTasks());
	}

	public function testRemoveALogRowWithoutAUser()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($task = $this->newTask('anonLogged')));
		$logId = PradoUnit::invoke($manager, 'logCronTask', $task, 'cron');
		$manager->getDbConnection()->createCommand('UPDATE crontabs SET username=NULL WHERE active IS NULL')->execute();
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyRemoveCronLogItem']));
		self::assertNull($manager->removeCronLogItem($logId), 'a log row without a user belongs to the default user');
		$manager->detachBehavior('deny');
		self::assertTrue($manager->removeCronLogItem($logId));
	}

	public function testRemoveATaskObjectWithoutPermission()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($task = $this->newTask('deniedObject')));
		$manager->attachBehavior('deny', new TTestCronDenyingBehavior(['dyRemoveTask']));
		self::assertNull($manager->removeTask($task));
	}

	public function testAPendingRowAloneThatHoldsNoTask()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($this->newTask('aloneBroken', '* * * * *')));
		$conn = $manager->getDbConnection();
		$conn->createCommand("UPDATE crontabs SET nextexectime=1, options='not serialized' WHERE active IS NOT NULL")->execute();
		$reader = $this->_managers[] = new TDbCronManager();
		PradoUnit::setProp($reader, '_conn', $conn);
		$reader->init(null);
		self::assertSame([], $reader->getPendingTasks());
	}

	public function testUserTasksCountOfTheDefaultUserByName()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($this->newTask('defaultNamed')));
		self::assertSame(1, $manager->getUserTasksCount($manager->getDefaultUserName(), 'default%'));
	}

	public function testTheLogOfTheDefaultUser()
	{
		$manager = $this->newManager();
		self::assertTrue($manager->addTask($task = $this->newTask('defaultLogged')));
		PradoUnit::invoke($manager, 'logCronTask', $task, $manager->getDefaultUserName());
		$manager->getDbConnection()->createCommand('UPDATE crontabs SET username=NULL WHERE active IS NULL')->execute();
		self::assertCount(1, $manager->getCronLog(null, 0, 0, null, $manager->getDefaultUserName()), 'a log row without a user is the default user\'s');
		self::assertSame(1, $manager->clearCronLog(null, null, $manager->getDefaultUserName()));
	}
}
