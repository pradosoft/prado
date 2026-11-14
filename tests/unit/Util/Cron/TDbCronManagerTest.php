<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\Cron\TCronMethodTask;
use Prado\Util\Cron\TDbCronManager;
use Prado\Test\Unit\PradoUnit;
use Prado\Test\Unit\Harness\Util\Cron\TTestDbCronManager;
use Prado\Prado;
use Prado\Util\Cron\TClosureCronTask;
use Prado\Data\IDataConnection;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TShellCronLogBehavior;
use Prado\Shell\TShellWriter;
use Prado\IO\TTextWriter;

/**
 * SQLite connection that reports the PostgreSQL driver and records each command's SQL.
 */
class TDbCronManagerTestPgsqlConnection extends TDbConnection
{
	public array $sql = [];

	public string $driver = 'pgsql';

	public function getDriverName()
	{
		return $this->driver;
	}

	public function createCommand($sql)
	{
		$this->sql[] = $sql;
		throw new TDbException('dbconnection_connection_inactive');
	}
}

/**
 * TDbCronManager that uses a recording connection and skips table creation.
 */
class TDbCronManagerTestPgsqlManager extends TDbCronManager
{
	public ?TDbCronManagerTestPgsqlConnection $recorder = null;

	public function getDbConnection(): IDataConnection
	{
		return $this->recorder;
	}

	protected function ensureTable()
	{
	}
}

class TDbCronManagerTest extends TCronModuleTest
{
	protected function getTestClass()
	{
		return TTestDbCronManager::class;
	}
	
	protected function setUp(): void
	{
		parent::setUp();
		
		// The cron table persists in a sqlite file across tests and runs; each test starts empty.
		$cleaner = new TDbCronManager();
		PradoUnit::invoke($cleaner, 'ensureTable');
		$cleaner->getDbConnection()->createCommand('DELETE FROM ' . $cleaner->getTableName())->execute();
	}
	
	protected function tearDown(): void
	{
		//In case these fail, clean these up
		$this->obj->removeTask('crudTask');
		$this->obj->removeTask('crudTaskRemove');
		$this->obj->clearCronLog(0);
		$this->obj->clearRuntimeTasks();
		
		parent::tearDown();
	}
	
	/**
	 * This overrides the parent because there are two now.
	 */
	public function testGetTaskInfos()
	{
		$task = new TTestCronFXComponent();
		
		self::assertEquals('dyListen', $task->dyMethod);
		$this->obj->setId('testCronModule100');
		
		$taskInfos = $this->obj->getTaskInfos();
		
		self::assertEquals('fxgetcrontaskinfos', $task->dyMethod);
		self::assertEquals($this->obj, $task->args[0]);
		
		// Other modules listening to the global event add their own task infos.
		$cleanInfos = array_values(array_filter($taskInfos, fn ($info) => $info->getName() === 'cronclean' && $info->getModuleId() === 'testCronModule100'));
		self::assertCount(1, $cleanInfos);
		$taskInfo = $cleanInfos[0];
		self::assertInstanceOf(\Prado\Util\Cron\TCronTaskInfo::class, $taskInfo);
		self::assertEquals(\Prado\Util\Cron\TDbCronCleanLogTask::class, $taskInfo->getTask());
		
		$testInfos = array_values(array_filter($taskInfos, fn ($info) => $info->getName() === 'taskName'));
		self::assertCount(1, $testInfos);
		$taskInfos = $testInfos[0];
		self::assertEquals('taskDefinition', $taskInfos->getTask());
		self::assertEquals('module1', $taskInfos->getModuleId());
		self::assertEquals('text title', $taskInfos->getTitle());
		self::assertEquals('text description', $taskInfos->getDescription());
	}
	
	public function validationData()
	{
		return ['name' => 'testTask1', 'schedule' => '* * * * *', 'task' => TTestCronModuleTask::class];
	}
	
	public function testValidateTask()
	{
		parent::testValidateTask();
		
		$properties = $this->validationData();
		
		try {
			$d = $properties;
			unset($d['name']);
			$this->obj->validateTask($d);
			self::fail('failed to throw TConfigurationException');
		} catch (TConfigurationException $e) {}
	}
	
	
	protected function checkPersistentData()
	{
		// checks the task  where it is stored.
		
		self::assertTrue($this->obj->taskExists('testTask1'));
		self::assertTrue($this->obj->taskExists('testTask2'));
		self::assertTrue($this->obj->taskExists('testTask3'));
		self::assertTrue($this->obj->taskExists('testTask4'));
		self::assertFalse($this->obj->taskExists('testTask5'));
		$tasksInfo = []; //no check for existinc, direct to DB for data, return array from db
		$tasksInfo['testTask1'] = $this->obj->getTask('testTask1', false, false);
		$tasksInfo['testTask2'] = $this->obj->getTask('testTask2', false, false);
		$tasksInfo['testTask3'] = $this->obj->getTask('testTask3', false, false);
		$tasksInfo['testTask4'] = $this->obj->getTask('testTask4', false, false);
		
		$testTask1 = $this->obj->getTask('testTask1');
		$testTask2 = $this->obj->getTask('testTask2');
		$testTask3 = $this->obj->getTask('testTask3');
		$testTask4 = $this->obj->getTask('testTask4');
		self::assertEquals(0, $tasksInfo['testTask1']['processcount']);
		self::assertTrue((microtime(true) - $tasksInfo['testTask1']['lastexectime']) < 2);
		self::assertEquals(serialize($testTask1), $tasksInfo['testTask1']['options']);
		self::assertEquals(1, $tasksInfo['testTask2']['processcount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask2']['lastexectime'] < 2);
		self::assertEquals(serialize($testTask2), $tasksInfo['testTask2']['options']);
		self::assertEquals(0, $tasksInfo['testTask3']['processcount']);
		self::assertNull($tasksInfo['testTask3']['lastexectime']);
		self::assertEquals(serialize($testTask3), $tasksInfo['testTask3']['options']);
		self::assertEquals(0, $tasksInfo['testTask4']['processcount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask4']['lastexectime'] < 2);
		self::assertEquals(serialize($testTask4), $tasksInfo['testTask4']['options']);
		
		//reload, make sure the reload goes smoothly.
		$testTask1 = $this->obj->getTask('testTask1', false);
		$testTask2 = $this->obj->getTask('testTask2', false);
		$testTask3 = $this->obj->getTask('testTask3', false);
		$testTask4 = $this->obj->getTask('testTask4', false);
		self::assertEquals(serialize($testTask1), $tasksInfo['testTask1']['options']);
		self::assertEquals(serialize($testTask2), $tasksInfo['testTask2']['options']);
		self::assertEquals(serialize($testTask3), $tasksInfo['testTask3']['options']);
		self::assertEquals(serialize($testTask4), $tasksInfo['testTask4']['options']);
	}
	
	public function testGetTasks()
	{
		parent::testGetTasks();
		
		$jobs = [
			['name' => 'testTask1', 'schedule' => '1 * 1 1 *', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
			['name' => 'testTask2', 'schedule' => '2 * 1 1 *', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1', 'username' => 'admin1']
		];
		
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs);
		try {
			$this->obj->setConnectionId('anotherModule');
			self::fail("failed to throw TInvalidOperationException when changing ConnectionId after initialization");
		} catch (TInvalidOperationException $e) {}
		try {
			$this->obj->setTableName('newTableName');
			self::fail("failed to throw TInvalidOperationException when changing TableName after initialization");
		} catch (TInvalidOperationException $e) {}
		try {
			$this->obj->setAutoCreateCronTable(false);
			self::fail("failed to throw TInvalidOperationException when changing AutoCreateCronTable after initialization");
		} catch (TInvalidOperationException $e) {}
		$this->obj->processPendingTasks(); //remove stale
		
		self::assertTrue($this->obj->taskExists('testTask1'));
		self::assertTrue($this->obj->taskExists('testTask2'));
		self::assertFalse($this->obj->taskExists('testTask3'));
		self::assertFalse($this->obj->taskExists('testTask4'));
		
		$task = new TTestCronModuleTask();
		$task->setName('testTask3');
		$task->setSchedule('3 * * * * 2020'); // Testing out the 6 star pattern here too, normal is 5.
		$this->obj->addTask($task);
		
		$task = new TCronMethodTask('CMT_UserManager3', 'method3(86400)');
		$task->setName('testTask4');
		$task->setSchedule('4 * * * * 2020');
		$this->obj->addTask($task);
		
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs);
		self::assertTrue($this->obj->taskExists('testTask3'));
		self::assertTrue($this->obj->taskExists('testTask4'));
		
		$tasks = $this->obj->getTasks();
		
		self::assertEquals(4, count($tasks));
		self::assertInstanceOf(TTestCronModuleTask::class, $tasks['testTask1']);
		self::assertEquals('1 * 1 1 *', $tasks['testTask1']->getSchedule());
		self::assertEquals('testTask1', $tasks['testTask1']->getName());
		self::assertEquals('admin', $tasks['testTask1']->getUserName());
		self::assertEquals('GT_module', $tasks['testTask1']->getModuleId());
		self::assertEquals('value1', $tasks['testTask1']->getPropertyA());
		self::assertEquals('2 * 1 1 *', $tasks['testTask2']->getSchedule());
		self::assertEquals('testTask2', $tasks['testTask2']->getName());
		self::assertInstanceOf(\Prado\Util\Cron\TCronMethodTask::class, $tasks['testTask2']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask2']->getModuleId());
		self::assertEquals('method1', $tasks['testTask2']->getMethod());
		
		// GetTasks returns DB tasks
		self::assertEquals('3 * * * * 2020', $tasks['testTask3']->getSchedule());
		self::assertEquals('testTask3', $tasks['testTask3']->getName());
		self::assertInstanceOf(TTestCronModuleTask::class, $tasks['testTask3']);
		self::assertEquals('4 * * * * 2020', $tasks['testTask4']->getSchedule());
		self::assertEquals('testTask4', $tasks['testTask4']->getName());
		self::assertInstanceOf(\Prado\Util\Cron\TCronMethodTask::class, $tasks['testTask4']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask4']->getModuleId());
		self::assertEquals('method3(86400)', $tasks['testTask4']->getMethod());
		//Yay, the sum of what needs to happen just happened.
		
		$tasks = $this->obj->getTasks();
		
		self::assertEquals(2, $this->obj->processPendingTasks());
		
		// check updateTaskInfo - test persistent data
		self::assertEquals(0, $tasks['testTask1']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask1']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask2']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask2']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask3']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask3']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask4']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask4']->getLastExecTime() < 2);
		
		$jobs2 = [
			['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
			['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1', 'username' => 'admin1'],
			['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method2(true)'],
			['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs2);
		
		try {
			$this->obj->getTasks();
			self::fail("failed to throw TConfigurationException when colliding configuration with DB tasks");
		} catch (TConfigurationException $e) {
		}
		
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs);
		$this->obj->removeTask('testTask3');
		$this->obj->removeTask('testTask4');
	}
	
	public function testLogCronTask()
	{
		$this->obj->init(null);
		$jobs = [
			['name' => 'testTaskAA', 'schedule' => '* * * * * 2020', 'task' => TTestCronModuleTask::class],
			['name' => 'testTaskBB', 'schedule' => '* * * * * 2020', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1'],
			['name' => 'testTaskCC', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1'],
			['name' => 'testTaskDD', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method2(true)'],
			['name' => 'testTaskEE', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		
		$this->obj->setLogCronTasks(true);
		$this->obj->init($jobs);
		
		self::assertEquals(0, $this->obj->getCronLogCount());
		self::assertEquals(5, $this->obj->processPendingTasks()); // 5 logs
		
		self::assertEquals(5, $this->obj->getCronLogCount());
		
		$log = $this->obj->getCronLog(null, false, false, false);
		self::assertEquals(5, count($log));
		
		self::assertEquals('testTaskAA', $log[0]['name']);
		$task = $this->obj->getTask('testTaskAA');
		self::assertEquals(serialize($task), $log[0]['options']);
		
		self::assertEquals('testTaskBB', $log[1]['name']);
		$task = $this->obj->getTask('testTaskBB');
		self::assertEquals(serialize($task), $log[1]['options']);
		
		self::assertEquals('testTaskCC', $log[2]['name']);
		$task = $this->obj->getTask('testTaskCC');
		self::assertEquals(serialize($task), $log[2]['options']);
		
		self::assertEquals('testTaskDD', $log[3]['name']);
		$task = $this->obj->getTask('testTaskDD');
		self::assertEquals(serialize($task), $log[3]['options']);
		
		self::assertEquals('testTaskEE', $log[4]['name']);
		$task = $this->obj->getTask('testTaskEE');
		self::assertEquals(serialize($task), $log[4]['options']);
		
		// Test when db logging is off
		$jobs = [
			['name' => 'testTask1', 'schedule' => '* * * * * 2020', 'task' => TTestCronModuleTask::class],
			['name' => 'testTask2', 'schedule' => '* * * * * 2020', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1'],
			['name' => 'testTask3', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1'],
			['name' => 'testTask4', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method2(true)'],
			['name' => 'testTask5', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		
		$this->obj = new $this->baseClass();
		$this->obj->setLogCronTasks(false);
		$this->obj->init($jobs);
		
		self::assertEquals(5, $this->obj->getCronLogCount());
		
		self::assertEquals(5, $this->obj->processPendingTasks());
		
		self::assertEquals(5, $this->obj->getCronLogCount());
		$log = $this->obj->getCronLog(null, false, false, true);
		self::assertEquals(5, count($log));
		
	}
	
	public function checkFilterStaleTasks()
	{
		self::assertFalse($this->obj->taskExists('testTask1'));
		self::assertTrue($this->obj->taskExists('testTask2'));
		self::assertTrue($this->obj->taskExists('testTask3'));
		self::assertFalse($this->obj->taskExists('testTask4'));
		self::assertFalse($this->obj->taskExists('testTask5'));
	}
	
	// TODO
	// executeRuntimeTasks
	public function testExecuteRuntimeTasks()
	{
		$jobs = [
			['name' => 'testTaskA', 'schedule' => '@' . ($this->obj->fakeNow + 86400), 'task' => TTestCronModuleTask::class]
		];
		$this->obj->init($jobs);
		$task1 = $this->obj->getTask('testTaskA');
		
		$task2 = new TTestCronModuleTask();
		$task2->setName('testTaskB');
		$task2->setSchedule('* * * * *');
		
		$this->obj->addTask($task2, true);
		
		$task3 = new TTestCronModuleTask();
		$task3->setName('testTaskC');
		$task3->setSchedule('* * * * *');
		
		$this->obj->addRuntimeTask($task1);
		$this->obj->addRuntimeTask($task3);
		
		self::assertNull($this->obj->getTask('testTaskC'));
		
		self::assertEquals(0, $this->obj->getCronLogCount());
		self::assertEquals(3, $this->obj->executeRuntimeTasks());
		self::assertEquals(3, $this->obj->getCronLogCount());
		
		$this->obj->removeTask($task2);
		
	}
	
	public function testAddGetRemoveRuntimeTasks()
	{
		// addRuntimeTask
		// getRuntimeTasks
		// removeRuntimeTask
		// clearRuntimeTasks
		
		$app = \Prado::getApplication();
		
		$task = new TTestCronModuleTask();
		$task->setName('runtimeTask1');
		$task->setSchedule('@' . ($this->obj->fakeNow + 86400));
		$task2 = new TTestCronModuleTask();
		$task2->setName('runtimeTask2');
		$task2->setSchedule('* * * * *');
		
		// Other components (e.g. the logger) may already listen on onEndRequest.
		$handlers = count($app->onEndRequest);
		self::assertNull($this->obj->getRuntimeTasks());
		
		$this->obj->addRuntimeTask($task);
		self::assertEquals($handlers + 1, count($app->onEndRequest));
		$this->obj->addRuntimeTask($task2);
		self::assertEquals($handlers + 1, count($app->onEndRequest));
		
		$tasks = $this->obj->getRuntimeTasks();
		self::assertEquals(2, count($tasks));
		self::assertEquals($task, $tasks['runtimeTask1']);
		self::assertEquals($task2, $tasks['runtimeTask2']);
		
		$this->obj->removeRuntimeTask($task);
		
		$tasks = $this->obj->getRuntimeTasks();
		self::assertEquals($handlers + 1, count($app->onEndRequest));
		self::assertEquals(1, count($tasks));
		self::assertEquals($task2, $tasks['runtimeTask2']);
		
		$this->obj->removeRuntimeTask('runtimeTask2');
		
		self::assertEquals($handlers, count($app->onEndRequest));
		self::assertNull($this->obj->getRuntimeTasks());
		
		$this->obj->addRuntimeTask($task);
		$this->obj->addRuntimeTask($task2);
		self::assertEquals($handlers + 1, count($app->onEndRequest));
		self::assertEquals(2, count($this->obj->getRuntimeTasks()));
		
		$this->obj->clearRuntimeTasks();
		self::assertEquals($handlers, count($app->onEndRequest));
		self::assertNull($this->obj->getRuntimeTasks());
		
		//tell the object to filterStaleTasks.  This is for testing purposes only
		$this->obj->processPendingTasks();
	}
	
	public function testGetTask()
	{
		parent::testGetTask();
		
		$task = new TTestCronModuleTask();
		$task->setName('testTask5');
		$task->setSchedule('5 * * * *');
		$this->obj->addTask($task);
		
		$tasks = [];
		$tasks['testTask1'] = $this->obj->getTask('testTask1', true, false);
		$tasks['testTask2'] = $this->obj->getTask('testTask2', true, false);
		$tasks['testTask3'] = $this->obj->getTask('testTask3', true, false);
		$tasks['testTask4'] = $this->obj->getTask('testTask4', true, false);
		
		self::assertEquals(4, count($tasks));
		self::assertEquals(TTestCronModuleTask::class, $tasks['testTask1']['task']);
		self::assertEquals('1 * * * ?', $tasks['testTask1']['schedule']);
		self::assertEquals('testTask1', $tasks['testTask1']['name']);
		self::assertEquals('admin', $tasks['testTask1']['username']);
		self::assertEquals('GT_module', $tasks['testTask1']['moduleid']);
		self::assertEquals(0, $tasks['testTask1']['processcount']);
		self::assertTrue(abs(microtime(true) - $tasks['testTask1']['lastexectime']) < 2);
		self::assertEquals('2 * * * ?', $tasks['testTask2']['schedule']);
		self::assertEquals('testTask2', $tasks['testTask2']['name']);
		self::assertEquals('CMT_UserManager3' . self::SEPARATOR . 'method1', $tasks['testTask2']['task']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask2']['moduleid']);
		self::assertEquals(0, $tasks['testTask2']['processcount']);
		self::assertTrue(abs(microtime(true) - $tasks['testTask2']['lastexectime']) < 2);
		self::assertEquals('3 * * * ?', $tasks['testTask3']['schedule']);
		self::assertEquals('testTask3', $tasks['testTask3']['name']);
		self::assertEquals('CMT_UserManager3' . self::SEPARATOR . 'method2(true)', $tasks['testTask3']['task']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask3']['moduleid']);
		self::assertEquals(0, $tasks['testTask3']['processcount']);
		self::assertTrue(abs(microtime(true) - $tasks['testTask3']['lastexectime']) < 2);
		self::assertEquals('4 * * * ?', $tasks['testTask4']['schedule']);
		self::assertEquals('testTask4', $tasks['testTask4']['name']);
		self::assertEquals('CMT_UserManager3' . self::SEPARATOR . 'method3(86400)', $tasks['testTask4']['task']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask4']['moduleid']);
		self::assertEquals(0, $tasks['testTask4']['processcount']);
		self::assertTrue(abs(microtime(true) - $tasks['testTask4']['lastexectime']) < 2);
		
		self::assertNull($this->obj->getTask('non_testTask', false, false));
		self::assertNull($this->obj->getTask('non_testTask', false, true));
		self::assertNull($this->obj->getTask('non_testTask', true, false));
		self::assertNull($this->obj->getTask('non_testTask', true, true));
		
		$jobs = [
			['name' => 'testTaskAB', 'schedule' => '4 * * * *', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs);
		self::assertNull($this->obj->getTask('testTaskAB', false, false));
		self::assertNull($this->obj->getTask('testTaskAB', false, true));
		
		$task = $this->obj->getTask('testTaskAB', true, false);
		self::assertTrue(is_array($task));
		self::assertTrue(is_numeric($task['tabuid']));
		self::assertEquals('testTaskAB', $task['name']);
		self::assertEquals('4 * * * *', $task['schedule']);
		self::assertEquals('CMT_UserManager3' . self::SEPARATOR . 'method3(86400)', $task['task']);
		self::assertNull($task['username']);
		self::assertEquals(0, $task['processcount']);
		self::assertTrue(abs(microtime(true) - $task['lastexectime']) < 2);
		self::assertInstanceOf(\Prado\Util\Cron\TCronMethodTask::class, $this->obj->getTask('testTaskAB', true, true));
		
		// Test DB task for GetTask
		$task = $this->obj->getTask('testTask5', true, false);
		self::assertTrue(is_array($task));
		self::assertTrue(is_numeric($task['tabuid']));
		self::assertEquals('testTask5', $task['name']);
		self::assertEquals('5 * * * *', $task['schedule']);
		self::assertEquals(TTestCronModuleTask::class, $task['task']);
		self::assertNull($task['username']);
		self::assertEquals(0, $task['processcount']);
		self::assertTrue(abs(microtime(true) - $task['lastexectime']) < 2);
		
		$task = $this->obj->getTask('testTask5', true, true);
		self::assertInstanceOf(TTestCronModuleTask::class, $task);
		self::assertEquals('testTask5', $task->getName());
		self::assertEquals('5 * * * *', $task->getSchedule());
		self::assertEquals(TTestCronModuleTask::class, $task->getTask());
		self::assertNull($task->getUserName());
		self::assertEquals(0, $task->getProcessCount());
		self::assertTrue(abs(microtime(true) - $task->getLastExecTime()) < 2);
		
		$this->obj->removeTask($task);
	}
	
	public function testAddTask()
	{
		self::assertFalse($this->obj->taskExists('crudTask'));
		
		$task = new TTestCronModuleTask();
		$task->setName('*'); // Invalid name, does not add
		self::assertFalse($this->obj->addTask($task));
		
		$count = rand();
		$time = floor(microtime(true)) + 0.6;
		
		$task = new TTestCronModuleTask();
		$task->setName('crudTask');
		$task->setSchedule('test * * * *');
		$task->setModuleId('crudModule');
		$task->setUserName('cronCrudUser');
		$task->setProcessCount($count);
		$task->setLastExecTime($time);
		$task->setPropertyA('myCrudValue');
		
		self::assertFalse($this->obj->addTask($task));
		$task->setSchedule('* * * * *');
		self::assertTrue($this->obj->addTask($task));
		
		self::assertNotNull($this->obj->getTasks()['crudTask']);
		
		self::assertTrue($this->obj->taskExists('crudTask'));
		self::assertEquals(serialize($task), serialize($this->obj->getTask('crudTask', false, true)));
		$taskInfo = $this->obj->getTask('crudTask', false, false);
		self::assertEquals('crudTask', $taskInfo['name']);
		self::assertEquals('* * * * *', $taskInfo['schedule']);
		self::assertEquals('crudModule', $taskInfo['moduleid']);
		self::assertEquals('cronCrudUser', $taskInfo['username']);
		self::assertEquals('1', $taskInfo['active']);
		self::assertEquals($count, $taskInfo['processcount']);
		self::assertEquals(floor($time), $taskInfo['lastexectime']);
		
		$this->obj->removeTask($task);
		self::assertFalse($this->obj->taskExists('crudTask'));
	}
	
	public function testUpdateTask()
	{
		$this->obj->init(null);
		self::assertFalse($this->obj->taskExists('crudTask'));
		
		$count = rand();
		$time = microtime(true) + 0.5;
		
		$task = new TTestCronModuleTask();
		$task->setName('crudTask');
		$task->setSchedule('* * * * *');
		
		self::assertTrue($this->obj->addTask($task));
		
		$task = new TTestCronModuleTask();
		$task->setName('crudTask');
		$task->setSchedule('test * * * *');
		$task->setModuleId('crudModule');
		$task->setUserName('cronCrudUser');
		$task->setProcessCount($count);
		$task->setLastExecTime($time);
		
		self::assertFalse($this->obj->updateTask($task));
		
		$task->setSchedule('1 * * * *');
		self::assertTrue($this->obj->updateTask($task));
		
		// was the row updated
		$taskInfo = $this->obj->getTask('crudTask', true, false);
		self::assertEquals('crudTask', $taskInfo['name']);
		self::assertEquals('1 * * * *', $taskInfo['schedule']);
		self::assertEquals('crudModule', $taskInfo['moduleid']);
		self::assertEquals('cronCrudUser', $taskInfo['username']);
		self::assertEquals('1', $taskInfo['active']);
		self::assertEquals($count, $taskInfo['processcount']);
		self::assertTrue(abs($time - $taskInfo['lastexectime']) < 2);
		
		//were the tasks updated
		self::assertTrue(isset($this->obj->getTasks()['crudTask']));
		
		self::assertTrue($this->obj->taskExists('crudTask'));
		
		// *******
		self::assertEquals(serialize($task), serialize($this->obj->getTask('crudTask', false, true)));
		$taskInfo = $this->obj->getTask('crudTask', false, false);
		self::assertEquals('crudTask', $taskInfo['name']);
		self::assertEquals('1 * * * *', $taskInfo['schedule']);
		self::assertEquals('crudModule', $taskInfo['moduleid']);
		self::assertEquals('cronCrudUser', $taskInfo['username']);
		self::assertEquals('1', $taskInfo['active']);
		self::assertEquals($count, $taskInfo['processcount']);
		self::assertTrue(abs($time - $taskInfo['lastexectime']) < 2);
		
		$this->obj->removeTask($task);
		self::assertFalse($this->obj->taskExists('crudTask'));
	}
	
	public function testRemoveTask_taskExists()
	{
		self::assertFalse($this->obj->taskExists('crudTaskRemove'));
		self::assertFalse(isset($this->obj->getTasks()['crudTaskRemove']));
		
		$task = new TTestCronModuleTask();
		$task->setName('crudTaskRemove');
		$task->setSchedule('* * * * *');
		
		$this->obj->addTask($task);
		
		self::assertNotNull($this->obj->getTasks()['crudTaskRemove']);
		self::assertTrue($this->obj->taskExists('crudTaskRemove'));
		
		$this->obj->removeTask($task->getName());
		
		self::assertFalse(isset($this->obj->getTasks()['crudTaskRemove']));
		self::assertFalse($this->obj->taskExists('crudTaskRemove'));
		
		
		$this->obj->addTask($task);
		
		self::assertNotNull($this->obj->getTasks()['crudTaskRemove']);
		self::assertTrue($this->obj->taskExists('crudTaskRemove'));
		
		$this->obj->removeTask($task);
		
		self::assertFalse(isset($this->obj->getTasks()['crudTaskRemove']));
		self::assertFalse($this->obj->taskExists('crudTaskRemove'));
	}
	
	public function testClearCronLog()
	{
		//$pageSize, $page
		$db = $this->obj->getDbConnection();
		$time = time();
		$cmd = $db->createCommand(
			"INSERT INTO {$this->obj->getTableName()} " .
				"(name, schedule, task, moduleid, username, processcount, lastexectime, active)" .
				" VALUES ('testTask1', '* * * * *', 'TTestCronModuleTask', 'module1', 'cron', '3', " . ($time - 60) . ", NULL)" .
				",('testTask2', '* * * * *', 'TTestCronModuleTask', 'module2', 'cron', '14', " . ($time - 60) . ", NULL)" .
				",('testTask3', '* * * * *', 'CMT_UserManager3" . self::SEPARATOR . "method1', 'CMT_UserManager3', 'cron', '15', " . ($time - 60) . ", NULL)" .
				",('testTask4', '* * * * *', 'CMT_UserManager3" . self::SEPARATOR . "method2(true)', 'CMT_UserManager3', 'cron', '16', " . ($time - 60) . ", NULL)" .
				",('testTask5', '5 * * * *', 'CMT_UserManager3" . self::SEPARATOR . "method3(86400)', 'CMT_UserManager3', 'cron', '17', " . ($time - 60) . ", NULL)" .
				", ('testTask1', '1 * * * *', 'TTestCronModuleTask', 'module1', 'cron', '18', " . ($time - 120) . ", NULL)" .
				",('testTask2', '* * * * *', 'TTestCronModuleTask', 'module2', 'cron', '20', " . ($time - 120) . ", NULL)" .
				",('testTask3', '* * * * *', 'CMT_UserManager3" . self::SEPARATOR . "method1', 'CMT_UserManager3', 'cron', '21', " . ($time - 120) . ", NULL)" .
				",('testTask4', '* * * * *', 'CMT_UserManager3" . self::SEPARATOR . "method2(true)', 'CMT_UserManager3', 'cron', '23', " . ($time - 120) . ", NULL)" .
				",('testTask5', '* * * * *', 'CMT_UserManager3" . self::SEPARATOR . "method3(86400)', 'CMT_UserManager3', 'cron', '24', " . ($time - 120) . ", NULL)"
		);
		
		$cmd->execute();
		
		self::assertEquals(10, $this->obj->getCronLogCount());
		
		$this->obj->clearCronLog(90);
		
		self::assertEquals(5, $this->obj->getCronLogCount());
		
		$log = $this->obj->getCronLog(null, false, false, true);
		self::assertEquals(5, count($log));
		self::assertEquals('testTask5', $log[0]['name']);
		self::assertEquals($time - 60, $log[0]['lastexectime']);
		self::assertEquals($time - 60, $log[1]['lastexectime']);
		self::assertEquals($time - 60, $log[2]['lastexectime']);
		self::assertEquals($time - 60, $log[3]['lastexectime']);
		self::assertEquals($time - 60, $log[4]['lastexectime']);
		
		// test removeCronLogItem
		$this->obj->removeCronLogItem($log[2]['tabuid']);
		$log = $this->obj->getCronLog(null, false, false, true);
		self::assertEquals(4, count($log));
	}
	
	
	public function testGetCronLogCount()
	{
		$jobs = [
			['name' => 'testTaskV', 'schedule' => '* * * * * 2020', 'task' => TTestCronModuleTask::class],
			['name' => 'testTaskW', 'schedule' => '* * * * * 2020', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1'],
			['name' => 'testTaskX', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1'],
			['name' => 'testTaskY', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method2(true)'],
			['name' => 'testTaskZ', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		
		$this->obj->setLogCronTasks(true);
		$this->obj->setInCronShell(false); // prevent minute-boundary cutoff under CI environments where TERM is unset
		$this->obj->init($jobs);

		self::assertEquals(0, $this->obj->getCronLogCount());
		self::assertEquals(5, $this->obj->processPendingTasks(false)); // 5 logs
		
		self::assertEquals(5, $this->obj->getCronLogCount());
		
		$this->obj->clearCronLog(0);
		self::assertEquals(0, $this->obj->getCronLogCount());
	}
	
	public function testGetCronLog()
	{
		//$pageSize, $page
		$db = $this->obj->getDbConnection();
		$time = time();
		$cmd = $db->createCommand(
			"INSERT INTO {$this->obj->getTableName()} " .
				"(name, schedule, task, moduleid, username, processcount, lastexectime, active)" .
				" VALUES ('testTask1', '* * * * *', 'TTestCronModuleTask', 'module1', 'cron', '3', " . ($time - 60) .  ", NULL)".
				",('testTask2', '* * * * *', 'TTestCronModuleTask', 'module2', 'cron', '14', " . ($time - 60) .  ", NULL)".
				",('testTask3', '* * * * *', 'CMT_UserManager3".self::SEPARATOR."method1', 'CMT_UserManager3', 'cron', '15', " . ($time - 60) .  ", NULL)".
				",('testTask4', '* * * * *', 'CMT_UserManager3".self::SEPARATOR."method2(true)', 'CMT_UserManager3', 'cron', '16', " . ($time - 60) .  ", NULL)".
				",('testTask5', '5 * * * *', 'CMT_UserManager3".self::SEPARATOR."method3(86400)', 'CMT_UserManager3', 'cron', '17', " . ($time - 60) .  ", NULL)".
				", ('testTask1', '1 * * * *', 'TTestCronModuleTask', 'module1', 'cron', '18', " . ($time - 120) .  ", NULL)".
				",('testTask2', '* * * * *', 'TTestCronModuleTask', 'module2', 'cron', '20', " . ($time - 120) .  ", NULL)".
				",('testTask3', '* * * * *', 'CMT_UserManager3".self::SEPARATOR."method1', 'CMT_UserManager3', 'cron', '21', " . ($time - 120) .  ", NULL)".
				",('testTask4', '* * * * *', 'CMT_UserManager3".self::SEPARATOR."method2(true)', 'CMT_UserManager3', 'cron', '23', " . ($time - 120) .  ", NULL)".
				",('testTask5', '* * * * *', 'CMT_UserManager3".self::SEPARATOR."method3(86400)', 'CMT_UserManager3', 'cron', '24', " . ($time - 120) .  ", NULL)");
		
		$cmd->execute();
		
		self::assertEquals(10, $this->obj->getCronLogCount());
		$log = $this->obj->getCronLog(null, false, false, true);
		self::assertEquals(10, count($log));
		self::assertEquals('testTask5', $log[0]['name']);
		self::assertEquals('5 * * * *', $log[0]['schedule']);
		self::assertEquals('CMT_UserManager3'.self::SEPARATOR.'method3(86400)', $log[0]['task']);
		self::assertEquals('CMT_UserManager3', $log[0]['moduleid']);
		self::assertEquals($time - 60, $log[0]['lastexectime']);
		self::assertEquals('cron', $log[0]['username']);
		self::assertEquals(null, $log[0]['active']);
		self::assertEquals('testTask4', $log[1]['name']);
		self::assertEquals('testTask3', $log[2]['name']);
		self::assertEquals('testTask2', $log[3]['name']);
		self::assertEquals('testTask1', $log[4]['name']);
		self::assertEquals('testTask5', $log[5]['name']);
		self::assertEquals('testTask4', $log[6]['name']);
		self::assertEquals('testTask3', $log[7]['name']);
		self::assertEquals('testTask2', $log[8]['name']);
		self::assertEquals('testTask1', $log[9]['name']);
		
		$log = $this->obj->getCronLog(null, false, false, false);
		self::assertEquals(10, count($log));
		self::assertEquals('testTask1', $log[0]['name']);
		self::assertEquals('testTask2', $log[1]['name']);
		self::assertEquals('testTask3', $log[2]['name']);
		self::assertEquals('testTask4', $log[3]['name']);
		self::assertEquals('testTask5', $log[4]['name']);
		self::assertEquals('testTask1', $log[5]['name']);
		self::assertEquals('testTask2', $log[6]['name']);
		self::assertEquals('testTask3', $log[7]['name']);
		self::assertEquals('testTask4', $log[8]['name']);
		self::assertEquals('testTask5', $log[9]['name']);
		
		$log = $this->obj->getCronLog(null, 2, 2, true);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask3', $log[0]['name']);
		self::assertEquals('testTask2', $log[1]['name']);
		
		$log = $this->obj->getCronLog(null, 2, 2, false);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask3', $log[0]['name']);
		self::assertEquals('testTask4', $log[1]['name']);
		
		$log = $this->obj->getCronLog('testTask1', false, false, false);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask1', $log[0]['name']);
		self::assertEquals('testTask1', $log[1]['name']);
		
		$log = $this->obj->getCronLog('testTask2', false, false, false);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask2', $log[0]['name']);
		self::assertEquals('testTask2', $log[1]['name']);
		
		$log = $this->obj->getCronLog('testTask3', false, false, false);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask3', $log[0]['name']);
		self::assertEquals('testTask3', $log[1]['name']);
		
		$log = $this->obj->getCronLog('testTask4', false, false, false);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask4', $log[0]['name']);
		self::assertEquals('testTask4', $log[1]['name']);
		
		$log = $this->obj->getCronLog('testTask5', false, false, false);
		self::assertEquals(2, count($log));
		self::assertEquals('testTask5', $log[0]['name']);
		self::assertEquals('testTask5', $log[1]['name']);
		
	}
	
	public function testGetCronLogPagesWithLimitOffset()
	{
		$manager = new TDbCronManagerTestPgsqlManager();
		$manager->recorder = new TDbCronManagerTestPgsqlConnection('sqlite::memory:');
		foreach ([[2, 4], [3, 0]] as [$pageSize, $offset]) {
			try {
				$manager->getCronLog(null, $pageSize, $offset, true);
			} catch (TDbException $e) {
			}
		}
		self::assertStringEndsWith(' LIMIT 2 OFFSET 4', $manager->recorder->sql[0]);
		self::assertStringEndsWith(' LIMIT 3', $manager->recorder->sql[1]);
		// Xdebug retains the objects on the stack of a thrown exception, so the manager unlistens itself rather than in its destructor.
		$manager->unlisten();
	}

	public function testDeferTask()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}
		$app = Prado::getApplication();
		$this->obj->fakeNow = (int) (floor(time() / 60) * 60 + 10);
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;

		$base = count($app->onEndRequest);
		self::assertNull($this->obj->getRuntimeTasks());

		$task = $this->obj->deferTask(function ($t, $cron) {
			return 'deferred-ran';
		});

		// Queued as a runtime task (the onEndRequest fast path), auto-removing.
		self::assertInstanceOf(TClosureCronTask::class, $task);
		self::assertEquals($base + 1, count($app->onEndRequest));
		$runtime = $this->obj->getRuntimeTasks();
		self::assertEquals(1, count($runtime));
		self::assertArrayHasKey($task->getName(), $runtime);
		self::assertTrue($task->getAutoRemove());

		// Persisted as an active task due now (the cron fallback).
		self::assertTrue($this->obj->taskExists($task->getName()));
		$row = $this->obj->getTask($task->getName(), false, false);
		self::assertEquals('1', $row['active']);
		self::assertLessThanOrEqual($this->obj->fakeNow, (int) $row['nextexectime']);

		// The persisted closure round-trips and runs.
		$reloaded = $this->obj->getTask($task->getName(), false, true);
		self::assertEquals('deferred-ran', $reloaded->execute($this->obj));

		$this->obj->clearRuntimeTasks();
		$this->obj->removeTask($task->getName());
		self::assertFalse($this->obj->taskExists($task->getName()));
	}

	public function testAddTaskSignalsWakeOnlyWhenEarliestThisMinute()
	{
		$this->obj->fakeNow = (int) (floor(time() / 60) * 60 + 10); // 10s into the current minute
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;

		// A one-shot due at the start of this minute is the earliest pending task → kicks cron.
		$now = new TTestCronModuleTask();
		$now->setName('wakeNow');
		$now->setSchedule('@' . (int) (floor($this->obj->fakeNow / 60) * 60));
		$this->obj->wakeSignals = [];
		self::assertTrue($this->obj->addTask($now));
		self::assertNotEmpty($this->obj->wakeSignals, 'an earliest due-this-minute task should signal wake');

		// A task due tomorrow is beyond this minute → no kick.
		$later = new TTestCronModuleTask();
		$later->setName('wakeLater');
		$later->setSchedule('@' . ($this->obj->fakeNow + 86400));
		$this->obj->wakeSignals = [];
		self::assertTrue($this->obj->addTask($later));
		self::assertEmpty($this->obj->wakeSignals, 'a far-future task should not signal wake');

		$this->obj->removeTask($now);
		$this->obj->removeTask($later);
	}

	public function testGetCronLogFiltersByUsernameAndClearsByPeriod()
	{
		$db = $this->obj->getDbConnection();
		PradoUnit::invoke($this->obj, 'ensureTable');
		$time = time();
		$db->createCommand(
			"INSERT INTO {$this->obj->getTableName()} " .
				"(name, schedule, task, moduleid, username, processcount, lastexectime, active)" .
				" VALUES ('logA', '* * * * *', 'TTestCronModuleTask', 'module1', 'alice', '1', " . ($time - 60) . ", NULL)" .
				",('logB', '* * * * *', 'TTestCronModuleTask', 'module1', 'bob', '2', " . ($time - 60) . ", NULL)" .
				",('logC', '* * * * *', 'TTestCronModuleTask', 'module1', NULL, '3', " . ($time - 7200) . ", NULL)"
		)->execute();

		self::assertEquals(3, $this->obj->getCronLogCount());
		self::assertEquals(1, $this->obj->getCronLogCount(null, 'alice'));
		self::assertEquals(3, $this->obj->getCronLogCount('log_'), 'the _ wild card matches one character');
		self::assertEquals(1, $this->obj->getCronLogCount('logA'));
		self::assertEquals(1, $this->obj->getCronLogCount(null, $this->obj->getDefaultUserName()), 'rows without a user belong to the default user');

		$log = $this->obj->getCronLog(null, 0, 0, null, 'bob');
		self::assertEquals(1, count($log));
		self::assertEquals('logB', $log[0]['name']);
		$log = $this->obj->getCronLog('log%', 1, 1, true);
		self::assertEquals(1, count($log));

		self::assertEquals(1, $this->obj->clearCronLog('1 hour'));
		self::assertEquals(2, $this->obj->getCronLogCount());
		self::assertEquals(1, $this->obj->clearCronLog(null, null, 'alice'));
		self::assertEquals(1, $this->obj->getCronLogCount());
		self::assertEquals(1, $this->obj->clearCronLog(0));
		self::assertEquals(0, $this->obj->getCronLogCount());

		self::assertEquals(0, $this->obj->clearCronLog('P7D'), 'an ISO 8601 duration is a period');
		self::expectException(TInvalidDataValueException::class);
		$this->obj->clearCronLog('not a period');
	}

	public function testGetUserTasks()
	{
		$this->obj->init([
			['name' => 'userTaskA', 'schedule' => '0 0 1 1 * 2099', 'task' => TTestCronModuleTask::class, 'username' => 'alice'],
			['name' => 'userTaskB', 'schedule' => '0 0 1 1 * 2099', 'task' => TTestCronModuleTask::class],
		]);
		$task = new TTestCronModuleTask();
		$task->setName('userTaskC');
		$task->setSchedule('0 0 1 1 * 2099');
		$task->setUserName('alice');
		self::assertTrue($this->obj->addTask($task));

		self::assertEquals(3, $this->obj->getTasksCount());
		self::assertEquals(3, $this->obj->getTasksCount('userTask_'));
		self::assertEquals(1, $this->obj->getTasksCount('userTaskA'));
		self::assertEquals(['userTaskA', 'userTaskC'], array_keys($this->obj->getUserTasks('alice')));
		self::assertEquals(2, $this->obj->getUserTasksCount('alice'));
		self::assertEquals(['userTaskB'], array_keys($this->obj->getUserTasks($this->obj->getDefaultUserName())), 'a task without a user belongs to the default user');
		self::assertEquals(['userTaskC'], array_keys($this->obj->getUserTasks('alice', 'userTaskC')));
		self::assertEquals(['userTaskC', 'userTaskA'], array_keys($this->obj->getUserTasks('al%', null, 0, 0, false)));
		self::assertEquals(['userTaskB'], array_keys($this->obj->getTasks(null, 1, 1)));

		$this->obj->removeTask($task);
	}

	public function testProcessPendingTasks_ACrontabTickRunsAtTheTriggerSecond()
	{
		$minute = (int) (floor(time() / 60) * 60);
		$this->obj->fakeNow = $minute + 1;
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
		$this->obj->setInCronShell(true);
		$this->obj->init(null);

		$early = new TTestCronModuleTask();
		$early->setName('secondEarly');
		$early->setSchedule('@' . ($minute + 5));
		$early->testunit = $this;
		$late = new TTestCronModuleTask();
		$late->setName('secondLate');
		$late->setSchedule('@' . ($minute + 20));
		$late->testunit = $this;
		$next = new TTestCronModuleTask();
		$next->setName('secondNextMinute');
		$next->setSchedule('@' . ($minute + 65));
		self::assertTrue($this->obj->addTask($late));
		self::assertTrue($this->obj->addTask($early));
		self::assertTrue($this->obj->addTask($next));

		self::assertEquals(2, $this->obj->processPendingTasks(), 'the tasks due within the minute are pending');
		self::assertEquals([$minute + 5, $minute + 20], $this->obj->waitedFor, 'waits until each trigger second, earliest first');
		self::assertEquals(2, $this->_countTasks);
		self::assertEquals(1, $this->obj->getTask('secondEarly')->getProcessCount());
		self::assertEquals(1, $this->obj->getTask('secondLate')->getProcessCount());
		self::assertEquals(0, $this->obj->getTask('secondNextMinute')->getProcessCount());
		self::assertEquals($minute + 20, $this->obj->fakeNow, 'the pinned clock advanced to the last trigger');

		$this->obj->removeTask($early);
		$this->obj->removeTask($late);
		$this->obj->removeTask($next);
	}

	public function testUpdateTaskInfoClaimsTheRunAgainstTheRow()
	{
		$task = new TTestCronModuleTask();
		$task->setName('claimTask');
		$task->setSchedule('@' . ($this->obj->fakeNow - 10));
		$task->testunit = $this;
		self::assertTrue($this->obj->addTask($task));

		// Another process advanced the row since this process read it: the claim fails and the task is skipped.
		$rows = PradoUnit::getProp($this->obj, '_taskRows');
		$rows['claimTask']['nextexectime'] = 12345;
		PradoUnit::setProp($this->obj, '_taskRows', $rows);
		self::assertFalse($this->obj->runTask($task));
		self::assertEquals(0, $task->getProcessCount());
		self::assertEquals(0, $this->_countTasks);
		self::assertEquals(0, $this->obj->getCronLogCount('claimTask'));

		// A fresh read of the row claims and runs.
		$pending = $this->obj->getPendingTasks();
		self::assertArrayHasKey('claimTask', $pending);
		self::assertTrue($this->obj->runTask($pending['claimTask']));
		self::assertEquals(1, $task->getProcessCount());
		self::assertEquals(1, $this->_countTasks);
		self::assertEquals(1, $this->obj->getCronLogCount('claimTask'));
		$log = $this->obj->getCronLog('claimTask');
		self::assertEquals(1, $log[0]['processcount'], 'the log row records the claimed run');

		$this->obj->removeTask($task);
	}

	public function testGetDbConnection()
	{
		self::assertInstanceOf(\Prado\Data\TDbConnection::class, $this->obj->getDbConnection());
	}
	
	public function testConnectionId()
	{
		self::assertEquals('', $this->obj->getConnectionId());
		$value = 'mycronDBConnection';
		$this->obj->setConnectionId($value);
		self::assertEquals($value, $this->obj->getConnectionId());
		$this->obj->setConnectionId('');
	}
	
	public function testLogCronTasks()
	{
		self::assertTrue($this->obj->getLogCronTasks());
		$this->obj->setLogCronTasks(false);
		self::assertFalse($this->obj->getLogCronTasks());
		$this->obj->setLogCronTasks(true);
		self::assertTrue($this->obj->getLogCronTasks());
		$this->obj->setLogCronTasks('false');
		self::assertFalse($this->obj->getLogCronTasks());
		$this->obj->setLogCronTasks('true');
		self::assertTrue($this->obj->getLogCronTasks());
	}
	
	public function testTableName()
	{
		$restoreValue = $this->obj->getTableName();
		self::assertTrue(is_string($this->obj->getTableName()));
		self::assertTrue(strlen($this->obj->getTableName()) > 0);
		$value = 'mycrontablename';
		$this->obj->setTableName($value);
		self::assertEquals($value, $this->obj->getTableName());
		$this->obj->setTableName($restoreValue);
	}

	public function testTableNameRejectsSql()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->obj->setTableName('crontabs; DROP TABLE users');
	}
	
	public function testAutoCreateCronTable()
	{
		self::assertTrue($this->obj->getAutoCreateCronTable());
		$this->obj->setAutoCreateCronTable(false);
		self::assertFalse($this->obj->getAutoCreateCronTable());
		$this->obj->setAutoCreateCronTable(true);
		self::assertTrue($this->obj->getAutoCreateCronTable());
		$this->obj->setAutoCreateCronTable('false');
		self::assertFalse($this->obj->getAutoCreateCronTable());
		$this->obj->setAutoCreateCronTable('true');
		self::assertTrue($this->obj->getAutoCreateCronTable());
	}

	public function testMigrateTable_CreatesTheTableAndAddsThe440Columns()
	{
		$manager = new TDbCronManager();
		$manager->setTableName('migratetable');
		$db = $manager->getDbConnection();
		$db->createCommand('DROP TABLE IF EXISTS migratetable')->execute();
		try {
			// the table of a 4.3 installation: no nextexectime column, one active task row
			$db->createCommand('CREATE TABLE migratetable (tabuid INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(127) NOT NULL, schedule VARCHAR(127) NOT NULL, task VARCHAR(256) NOT NULL, moduleid VARCHAR(127) NULL, username VARCHAR(127) NULL, options MEDIUMTEXT NULL, processcount INT NOT NULL DEFAULT 0, lastexectime VARCHAR(20) NULL DEFAULT NULL, active BOOLEAN NULL)')->execute();
			$task = new TTestCronModuleTask();
			$task->setName('migrated');
			$task->setSchedule('0 * * * *');
			$last = 1700000000;
			$cmd = $db->createCommand("INSERT INTO migratetable (name, schedule, task, options, processcount, lastexectime, active) VALUES ('migrated', '0 * * * *', :task, :options, 3, :last, 1)");
			$cmd->bindValue(':task', TTestCronModuleTask::class);
			$cmd->bindValue(':options', serialize($task));
			$cmd->bindValue(':last', (string) $last);
			$cmd->execute();

			self::assertSame(['created' => false, 'column' => 1, 'run' => true], $manager->migrateTable());
			self::assertSame(['lastresult' => null, 'lastduration' => null, 'paused' => null], $db->createCommand("SELECT lastresult, lastduration, paused FROM migratetable WHERE name='migrated'")->queryRow(), 'the run columns are added');
			self::assertEquals($task->getNextTriggerTime($last), $db->createCommand("SELECT nextexectime FROM migratetable WHERE name='migrated'")->queryScalar());
			self::assertSame(['created' => false, 'column' => null, 'run' => false], $manager->migrateTable(), 'a second run finds the columns');

			$db->createCommand('DROP TABLE migratetable')->execute();
			$manager->setAutoCreateCronTable(false);
			self::assertSame(['created' => true, 'column' => null, 'run' => false], $manager->migrateTable(), 'a missing table is created with the columns, whatever AutoCreateCronTable says');
			self::assertSame(0, (int) $db->createCommand('SELECT COUNT(*) FROM migratetable')->queryScalar());
		} finally {
			$db->createCommand('DROP TABLE IF EXISTS migratetable')->execute();
			$manager->unlisten();
		}
	}

	public function testRunTask_RecordsTheResultOnTheLogRow()
	{
		$this->obj->setDaemonMutex(new \Prado\Util\Mutex\TMemoryMutex());
		$this->obj->setLogCronTasks(true);
		$this->obj->init([['name' => 'logged', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class]]);
		self::assertTrue($this->obj->runTask($this->obj->getTask('logged')));
		$log = $this->obj->getCronLog('logged');
		self::assertCount(1, $log);
		self::assertSame(\Prado\Util\Cron\TCronModule::RESULT_OK, $log[0]['lastresult']);
		self::assertIsNumeric($log[0]['lastduration']);
		$this->obj->clearCronLog(null, 'logged');
	}


	public function testGetPendingTasks_TakesAnotherProcessRunFromTheRow()
	{
		$task = new TTestCronModuleTask();
		$task->setName('pendingSync');
		$task->setSchedule('* * * * *');
		self::assertTrue($this->obj->addTask($task));
		$loaded = $this->obj->getTask('pendingSync');
		$now = $this->obj->fakeNow;
		// another process ran the task: its row moved on while this process holds the loaded object
		$db = $this->obj->getDbConnection();
		$cmd = $db->createCommand("UPDATE {$this->obj->getTableName()} SET lastexectime=:last, nextexectime=:next, processcount=7 WHERE name='pendingSync' AND active IS NOT NULL");
		$cmd->bindValue(':last', (string) ($now - 70));
		$cmd->bindValue(':next', (string) ($now - 10));
		$cmd->execute();
		$pending = $this->obj->getPendingTasks();
		self::assertArrayHasKey('pendingSync', $pending);
		self::assertSame($loaded, $pending['pendingSync']);
		self::assertEquals($now - 70, $loaded->getLastExecTime(), 'the task takes the row\'s last run');
		self::assertSame(7, $loaded->getProcessCount());
		$this->obj->removeTask($loaded);
	}

	public function testDbFilters_ByNameAndUser()
	{
		$this->obj->init(null);
		foreach (['dbFilterOne' => 'hal', 'dbFilterTwo' => 'ivy', 'other' => null] as $name => $user) {
			$task = new TTestCronModuleTask();
			$task->setName($name);
			$task->setSchedule('0 0 1 1 ? 2099');
			$task->setUserName($user);
			self::assertTrue($this->obj->addTask($task));
		}
		self::assertSame(['dbFilterOne', 'dbFilterTwo'], array_keys($this->obj->getTasks('dbFilter%')));
		self::assertSame(2, $this->obj->getTasksCount('dbFilter%'));
		self::assertSame(1, $this->obj->getUserTasksCount('ivy', 'dbFilter%'));
		self::assertSame(0, $this->obj->getUserTasksCount('ivy', 'other'));
		self::assertSame(['other'], array_keys($this->obj->getUserTasks('')), 'an empty user name matches the tasks without a user, as in TCronModule');

		// another manager on the table reads the rows it has not loaded
		$other = new TTestDbCronManager();
		$other->init(null);
		self::assertSame(['dbFilterOne'], array_keys($other->getUserTasks('hal')));
		$row = $other->getTask('dbFilterTwo', true, false);
		self::assertSame('ivy', $row['username']);
		PradoUnit::setProp($other, '_tasks', []);
		self::assertSame('dbFilterTwo', $other->getTask('dbFilterTwo')->getName(), 'a loaded row is made a task');

		$task = $this->obj->getTask('other');
		$fresh = new TTestDbCronManager();
		$fresh->init(null);
		$task->setSchedule('0 0 2 1 ? 2099');
		self::assertTrue($fresh->updateTask($task), 'a manager updates a row it has not loaded');
		self::assertSame('0 0 2 1 ? 2099', $fresh->getTask('other', false, false)['schedule']);
		$missing = new TTestCronModuleTask();
		$missing->setName('noSuchRow');
		$missing->setSchedule('0 0 1 1 ? 2099');
		self::assertFalse($this->obj->updateTask($missing));

		$never = new TTestCronModuleTask();
		$never->setName('dbNever');
		$never->setSchedule('@never');
		self::assertTrue($this->obj->addTask($never), 'a task without a next trigger wakes no cron');
		self::assertSame([], $this->obj->wakeSignals);

		foreach (['dbFilterOne', 'dbFilterTwo', 'other', 'dbNever'] as $name) {
			$this->obj->removeTask($name);
		}
		$other->unlisten();
		$fresh->unlisten();
	}

	public function testDbPermissions_DenyTheRowsAndTheLog()
	{
		$this->obj->init(null);
		$task = new TTestCronModuleTask();
		$task->setName('dbDenied');
		$task->setSchedule('0 0 1 1 ? 2000');
		self::assertTrue($this->obj->addTask($task));
		self::assertTrue($this->obj->runTask($this->obj->getTask('dbDenied')));
		$uid = $this->obj->getCronLog()[0]['tabuid'];

		$this->obj->attachBehavior('denies', new TTestCronDenyingBehavior(['dyAddTask', 'dyAddTaskGlobal', 'dyUpdateTask', 'dyGetUserTasks', 'dyRemoveCronLogItem', 'dyGetCronLog']));
		$another = new TTestCronModuleTask();
		$another->setName('dbDeniedToo');
		$another->setSchedule('0 0 1 1 ? 2099');
		self::assertNull($this->obj->addTask($another));
		self::assertNull($this->obj->updateTask($task));
		self::assertNull($this->obj->getTask('dbDenied', false));
		self::assertNull($this->obj->taskExists('dbDenied'));
		self::assertNull($this->obj->removeCronLogItem($uid));
		self::assertNull($this->obj->removeCronLogItem(999999), 'an unknown item is the default user');
		self::assertNull($this->obj->getCronLog());
		$this->obj->detachBehavior('denies');
		self::assertSame(1, $this->obj->getCronLogCount(), 'a denied removal removes nothing');
		$this->obj->removeTask('dbDenied');
	}

	public function testDbTable_MissingWithoutAutoCreate()
	{
		$manager = new TDbCronManager();
		$manager->setTableName('nocrontable');
		$manager->setAutoCreateCronTable(false);
		$manager->getDbConnection()->createCommand('DROP TABLE IF EXISTS nocrontable')->execute();
		try {
			self::expectException(TConfigurationException::class);
			$manager->getTasks();
		} finally {
			$manager->unlisten();
		}
	}

	public function testMigrateTable_SkipsARowThatIsNoTask()
	{
		$manager = new TDbCronManager();
		$manager->setTableName('migratebadrow');
		$db = $manager->getDbConnection();
		$db->createCommand('DROP TABLE IF EXISTS migratebadrow')->execute();
		$db->createCommand('CREATE TABLE migratebadrow (tabuid INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(127) NOT NULL, schedule VARCHAR(127) NOT NULL, task VARCHAR(256) NOT NULL, moduleid VARCHAR(127) NULL, username VARCHAR(127) NULL, options MEDIUMTEXT NULL, processcount INT NOT NULL DEFAULT 0, lastexectime VARCHAR(20) NULL DEFAULT NULL, active BOOLEAN NULL)')->execute();
		$db->createCommand("INSERT INTO migratebadrow (name, schedule, task, options, active) VALUES ('broken', '* * * * *', 'x', 'not serialized', 1)")->execute();
		try {
			self::assertSame(['created' => false, 'column' => 0, 'run' => true], $manager->migrateTable());
		} finally {
			$db->createCommand('DROP TABLE IF EXISTS migratebadrow')->execute();
			$manager->unlisten();
		}
	}

	public function testCreateDbTable_TheAutoIncrementOfEachDriver()
	{
		$manager = new TDbCronManagerTestPgsqlManager();
		$manager->recorder = new TDbCronManagerTestPgsqlConnection('sqlite::memory:');
		foreach (['mysql', 'pgsql'] as $driver) {
			$manager->recorder->driver = $driver;
			try {
				PradoUnit::invoke($manager, 'createDbTable');
			} catch (TDbException $e) {
			}
		}
		self::assertStringContainsString('`tabuid` INTEGER PRIMARY KEY AUTO_INCREMENT', $manager->recorder->sql[0]);
		self::assertStringContainsString('`tabuid` SERIAL PRIMARY KEY', $manager->recorder->sql[1]);
		$manager->unlisten();
	}

	public function testExecuteRuntimeTasks_NoneAndTheShellLog()
	{
		$this->obj->init(null);
		self::assertSame(0, $this->obj->executeRuntimeTasks());
		$writer = new TShellWriter(new TTextWriter());
		$this->obj->attachBehavior(TCronModule::SHELL_LOG_BEHAVIOR, new TShellCronLogBehavior($writer));
		$task = new TTestCronModuleTask();
		$task->setName('runtimeLogged');
		$task->setSchedule('* * * * *');
		$this->obj->addRuntimeTask($task);
		self::assertSame(1, $this->obj->executeRuntimeTasks());
		self::assertTrue($this->obj->asa(TCronModule::SHELL_LOG_BEHAVIOR)->getEnabled(), 'the shell log is enabled again after');
		self::assertSame('', $writer->flush(), 'the runtime tasks do not write to the shell');
		$this->obj->detachBehavior(TCronModule::SHELL_LOG_BEHAVIOR);
	}

	public function testClearCronLog_AnInvalidPeriodThrows()
	{
		$this->obj->init(null);
		self::expectException(TInvalidDataValueException::class);
		$this->obj->clearCronLog('Pnot a period');
	}

	public function testConnectionID_Invalid()
	{
		$manager = new TDbCronManager();
		$manager->setConnectionID('noSuchDbModule');
		try {
			self::expectException(TConfigurationException::class);
			self::expectExceptionMessage('noSuchDbModule');
			$manager->getDbConnection();
		} finally {
			$manager->unlisten();
		}
	}
}
