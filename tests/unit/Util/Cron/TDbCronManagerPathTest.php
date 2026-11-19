<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Data\TDbConnection;
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
}
