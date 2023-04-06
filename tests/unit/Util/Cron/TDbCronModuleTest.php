<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\Cron\TDbCronModule;
use Prado\Util\Cron\TCronMethodTask;

class TDbCronModuleTest extends TCronModuleTest
{
	protected function getTestClass()
	{
		return TDbCronModule::class;
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
		$task = new TTestCronFXTest();
		
		self::assertEquals('dyListen', $task->dyMethod);
		$this->obj->setId('testCronModule100');
		
		$taskInfos = $this->obj->getTaskInfos();
		
		self::assertEquals('fxgetcrontaskinfos', $task->dyMethod);
		self::assertEquals($this->obj, $task->args[0]);
		
		$taskInfo = $taskInfos[0];
		self::assertInstanceOf(\Prado\Util\Cron\TCronTaskInfo::class, $taskInfo);
		self::assertEquals('cronclean', $taskInfo->getName());
		self::assertEquals(\Prado\Util\Cron\TDbCronCleanLogTask::class, $taskInfo->getTask());
		self::assertEquals('testCronModule100', $taskInfo->getModuleId());
		
		$taskInfos = $taskInfos[1];
		self::assertEquals('taskName', $taskInfos->getName());
		self::assertEquals('taskDefinition', $taskInfos->getTask());
		self::assertEquals('module1', $taskInfos->getModuleId());
		self::assertEquals('text title', $taskInfos->getTitle());
		self::assertEquals('text description', $taskInfos->getDescription());
	}
	
	public function validationData()
	{
		return ['name' => 'testTask1', 'schedule' => '* * * * *', 'task' => 'TTestCronModuleTask'];
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
			['name' => 'testTask1', 'schedule' => '1 * 1 1 *', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
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
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask1']);
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
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask3']);
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
			['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
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
			['name' => 'testTaskAA', 'schedule' => '* * * * * 2020', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTaskBB', 'schedule' => '* * * * * 2020', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1'],
			['name' => 'testTaskCC', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1'],
			['name' => 'testTaskDD', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method2(true)'],
			['name' => 'testTaskEE', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		
		$this->obj->setLogCronTasks(true);
		$this->obj->init($jobs);
		
		self::assertEquals(0, $this->obj->getCronLogCount());
		self::assertEquals(5, $this->obj->processPendingTasks()); // 5 logs
		
		self::assertEquals(5, $this->obj->getCronLogCount());
		
		$log = $this->obj->getCronLog(null, false, false, true);
		self::assertEquals(5, count($log));
		
		self::assertEquals('testTaskAA', $log[0]['name']);
		$task = $this->obj->getTask('testTaskAA');
		$task->setProcessCount(0);
		$task->setLastExecTime(null);
		self::assertEquals(serialize($task), $log[0]['options']);
		
		self::assertEquals('testTaskBB', $log[1]['name']);
		$task = $this->obj->getTask('testTaskBB');
		$task->setProcessCount(0);
		$task->setLastExecTime(null);
		self::assertEquals(serialize($task), $log[1]['options']);
		
		self::assertEquals('testTaskCC', $log[2]['name']);
		$task = $this->obj->getTask('testTaskCC');
		$task->setProcessCount(0);
		$task->setLastExecTime(null);
		self::assertEquals(serialize($task), $log[2]['options']);
		
		self::assertEquals('testTaskDD', $log[3]['name']);
		$task = $this->obj->getTask('testTaskDD');
		$task->setProcessCount(0);
		$task->setLastExecTime(null);
		self::assertEquals(serialize($task), $log[3]['options']);
		
		self::assertEquals('testTaskEE', $log[4]['name']);
		$task = $this->obj->getTask('testTaskEE');
		$task->setProcessCount(0);
		$task->setLastExecTime(null);
		self::assertEquals(serialize($task), $log[4]['options']);
		
		// Test when db logging is off
		$jobs = [
			['name' => 'testTask1', 'schedule' => '* * * * * 2020', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTask2', 'schedule' => '* * * * * 2020', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1'],
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
			['name' => 'testTaskA', 'schedule' => '0 0 1 1 * 2099', 'task' => 'TTestCronModuleTask']
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
		
		$app = Prado::getApplication();
		
		$task = new TTestCronModuleTask();
		$task->setName('runtimeTask1');
		$task->setSchedule('0 0 1 0 ? 2099');
		$task2 = new TTestCronModuleTask();
		$task2->setName('runtimeTask2');
		$task2->setSchedule('* * * * *');
		
		self::assertEquals(0, count($app->onEndRequest));
		self::assertNull($this->obj->getRuntimeTasks());
		
		$this->obj->addRuntimeTask($task);
		self::assertEquals(1, count($app->onEndRequest));
		$this->obj->addRuntimeTask($task2);
		self::assertEquals(1, count($app->onEndRequest));
		
		$tasks = $this->obj->getRuntimeTasks();
		self::assertEquals(2, count($tasks));
		self::assertEquals($task, $tasks['runtimeTask1']);
		self::assertEquals($task2, $tasks['runtimeTask2']);
		
		$this->obj->removeRuntimeTask($task);
		
		$tasks = $this->obj->getRuntimeTasks();
		self::assertEquals(1, count($app->onEndRequest));
		self::assertEquals(1, count($tasks));
		self::assertEquals($task2, $tasks['runtimeTask2']);
		
		$this->obj->removeRuntimeTask('runtimeTask2');
		
		self::assertEquals(0, count($app->onEndRequest));
		self::assertNull($this->obj->getRuntimeTasks());
		
		$this->obj->addRuntimeTask($task);
		$this->obj->addRuntimeTask($task2);
		self::assertEquals(1, count($app->onEndRequest));
		self::assertEquals(2, count($this->obj->getRuntimeTasks()));
		
		$this->obj->clearRuntimeTasks();
		self::assertEquals(0, count($app->onEndRequest));
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
		self::assertEquals('TTestCronModuleTask', $tasks['testTask1']['task']);
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
		self::assertEquals('TTestCronModuleTask', $task['task']);
		self::assertNull($task['username']);
		self::assertEquals(0, $task['processcount']);
		self::assertTrue(abs(microtime(true) - $task['lastexectime']) < 2);
		
		$task = $this->obj->getTask('testTask5', true, true);
		self::assertInstanceOf('TTestCronModuleTask', $task);
		self::assertEquals('testTask5', $task->getName());
		self::assertEquals('5 * * * *', $task->getSchedule());
		self::assertEquals('TTestCronModuleTask', $task->getTask());
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
			['name' => 'testTaskV', 'schedule' => '* * * * * 2020', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTaskW', 'schedule' => '* * * * * 2020', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1'],
			['name' => 'testTaskX', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method1'],
			['name' => 'testTaskY', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method2(true)'],
			['name' => 'testTaskZ', 'schedule' => '* * * * * 2020', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		];
		
		$this->obj->setLogCronTasks(true);
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
}
