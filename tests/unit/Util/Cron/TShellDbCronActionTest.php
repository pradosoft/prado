<?php

use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Prado;
use Prado\Util\Cron\TShellCronAction;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TDbCronModule;


class TShellDbCronActionTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	protected $writer;
	
	protected function getTestClass()
	{
		return TShellDbCronAction::class;
	}
	
	protected function getTestCronClass()
	{
		return TDbCronModule::class;
	}

	protected function setUp(): void
	{
		$this->obj = Prado::createComponent($this->getTestClass());
		$this->writer = new TShellWriter(new TTextWriter());
		$this->obj->setWriter($this->writer);
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		$cron = $app->getModule('DbShellCron');
		$this->obj = null;
		if ($cron) {
			$cron->removeTask('testTaskB');
			$cron->removeTask('testTaskC');
		}
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TShellDbCronAction::class, $this->obj);
	}
	
	public function testActionRun()
	{
		$app = Prado::getApplication();
		
		//  no TCronModule in application, failed 
		self::assertTrue($this->obj->actionRun(['cron']));
		self::assertEquals(1, preg_match("/TDbCronModule/", $text = $this->writer->flush()));
		
		$jobs = [['name' => 'testTaskA', 'schedule' => '1 2 3 4 ? 2020', 'task' => 'TTestCronModuleTask', 'username' => 'admin123', 'moduleid' => 'cronmodule99', 'propertya' => 'value1']];
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->setId('cronmodule88');
		$cron->init($jobs);
		
		$ttask = new TTestCronModuleTask();
		$ttask->setName('testTaskB');
		$ttask->setSchedule('2 2 2 2 * 2020');
		$ttask->setModuleId('dbcronmodule100');
		$ttask->setPropertyA('value2');
		$ttask->setUserName('admin456');
		$cron->addTask($ttask);
		$this->obj->setCronModule($cron);
		self::assertNull($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR));
		{	//cron pending tasks
			$task = $cron->getTask('testTaskA');
			self::assertEquals(0, $task->getProcessCount());
			self::assertEquals(0, $ttask->getProcessCount());
			self::assertTrue($this->obj->actionRun(['cron']));
			$ntask = $cron->getTask('testTaskA');
			self::assertTrue($ntask === $task);
			self::assertEquals(1, $task->getProcessCount());
			self::assertEquals(1, $ttask->getProcessCount());
			$this->writer->flush();
		}
		self::assertTrue(is_object($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)));
		{	//cron tasks
			self::assertTrue($this->obj->actionTasks(['cron/tasks']));
			$text = $this->writer->flush();
			
			self::assertEquals(1, preg_match("/Name/", $text));
			self::assertEquals(1, preg_match("/Schedule/", $text));
			self::assertEquals(1, preg_match("/Task/", $text));
			self::assertEquals(1, preg_match("/Last Run/", $text));
			self::assertEquals(1, preg_match("/Next Run/", $text));
			self::assertEquals(1, preg_match("/User/", $text));
			self::assertEquals(1, preg_match("/Run #/", $text));
			
			self::assertEquals(1, preg_match("/testTaskA/", $text));
			self::assertEquals(1, preg_match("/1 2 3 4 \?/", $text));
			self::assertEquals(1, preg_match("/TTestCronModuleTask/", $text));
			self::assertEquals(1, preg_match("/admin123/", $text));
			self::assertEquals(0, preg_match("/(1969|1970)/", $text));
			
			self::assertEquals(1, preg_match("/testTaskB/", $text));
			self::assertEquals(1, preg_match("/2 2 2 2 \*/", $text));
			self::assertEquals(1, preg_match("/TTestCronModuleTask/", $text));
			self::assertEquals(1, preg_match("/admin456/", $text));
		}
		{	//cron index	
			self::assertTrue($this->obj->actionIndex(['cron/index']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/cronclean/", $text));
			self::assertEquals(1, preg_match("/TDbCronCleanLogTask/", $text));
			self::assertEquals(1, preg_match("/cronmodule88/", $text));
			self::assertEquals(1, preg_match("/DbCron Clean Log Task/", $text));
			self::assertEquals(1, preg_match("/Clears the database of cron log items/i", $text));
			
			//Register & call again
			$fxtest = new TTestCronFXTest();
			$fxtest->listen();
			self::assertTrue($this->obj->actionIndex(['cron/index']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task ID/", $text));
			self::assertEquals(1, preg_match("/Task/", $text));
			self::assertEquals(1, preg_match("/Module ID/", $text));
			self::assertEquals(1, preg_match("/Title/", $text));
			
			self::assertEquals(1, preg_match("/taskName/", $text));
			self::assertEquals(1, preg_match("/taskDefinition/", $text));
			self::assertEquals(1, preg_match("/module1/", $text));
			self::assertEquals(1, preg_match("/text title/", $text));
			self::assertEquals(1, preg_match("/text description/", $text));
			$fxtest->unlisten();
		}
		{	// cron add
			self::assertTrue($this->obj->actionAdd(['cron/add']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Cannot add a task without a name/i", $text));
			
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskC']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Cannot add a task without a task id/i", $text));
			
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskC', 'cronclean']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Cannot add a task without a schedule/i", $text));
			
			//add a Config task that already exists
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskA', 'cronclean', '* * * * *']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/'testTaskA' already exists in the database/i", $text));
			
			//add a DB task that already exists
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskB', 'cronclean', '* * * * *']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/'testTaskB' already exists in the database/i", $text));
			
			//add task with bad task id
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskC', 'notAtask', '* * * * *']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task ID 'notAtask' could not be found/i", $text));
			
			//add task with bad schedule
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskC', 'cronclean', '80 25 33 13 *']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Schedule '80 25 33 13 \\*' is not a valid schedule/i", $text));
			
			//add task with improper propertyB
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskC', 'cronclean', '* * * * ?', ' PropertyB = value2 ']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task Property 'PropertyB' is not found/i", $text));
			
			//add task with proper propertyA, with extra space
			self::assertTrue($this->obj->actionAdd(['cron/add', 'testTaskC', 'cronclean', '5 0 * * ? 2000', ' TimePeriod = 864000 ', 'username=cron007', 'moduleid=mycronModule']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task 'testTaskC' was added to the database/i", $text));
			
			$task = $cron->getTask('testTaskC');
			
			self::assertTrue($cron->taskExists('testTaskC'));
			
			self::assertEquals('testTaskC', $task->getName());
			self::assertEquals('5 0 * * ? 2000', $task->getSchedule());
			self::assertEquals('cron007', $task->getUserName());
			self::assertEquals('mycronModule', $task->getModuleId());
			self::assertEquals('864000', $task->getTimePeriod());
			self::assertEquals(0, $task->getProcessCount());
			self::assertNull($task->getLastExecTime());
			
			$tasks = $cron->getTasks();
			self::assertEquals(3, count($tasks));
		}
		{	// cron update
			// no task name
			self::assertTrue($this->obj->actionUpdate(['cron/update']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Cannot update a task without a name/i", $text));
			
			// bad task name
			self::assertTrue($this->obj->actionUpdate(['cron/update', 'badTaskName']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task 'badTaskName' is not found/i", $text));
			
			// no property change
			self::assertTrue($this->obj->actionUpdate(['cron/update', 'testTaskC']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/No given properties to change/i", $text));
			
			// bad property change
			self::assertTrue($this->obj->actionUpdate(['cron/update', 'testTaskC', ' PropertyB = value']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task Property 'PropertyB' is not found/i", $text));
			
			// property change, with extra spaces
			self::assertTrue($this->obj->actionUpdate(['cron/update', 'testTaskC', 'schedule=80 25 33 13 *', ' TimePeriod = 86400 ']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Schedule '80 25 33 13 \\*' is not a valid schedule/i", $text));
			
			// property change, with extra spaces
			$time = time();
			self::assertTrue($this->obj->actionUpdate(['cron/update', 'testTaskC', 'schedule=10 1 * * ?', ' TimePeriod = 86400 ', 'username=cron001', 'moduleid=mycronModule2', 'ProcessCount=100', 'LastExecTime='.$time]));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Task 'testTaskC' was updated in the database/i", $text));
			
			$task = $cron->getTask('testTaskC');
			
			//check that it was changed.
			self::assertEquals('testTaskC', $task->getName());
			self::assertEquals('10 1 * * ?', $task->getSchedule());
			self::assertEquals('cron001', $task->getUserName());
			self::assertEquals('mycronModule2', $task->getModuleId());
			self::assertEquals('86400', $task->getTimePeriod());
			self::assertEquals(100, $task->getProcessCount());
			self::assertEquals($time, $task->getLastExecTime());
			
			// check db row
			$task = $cron->getTask('testTaskC', true, false);
			self::assertEquals('testTaskC', $task['name']);
			self::assertEquals('10 1 * * ?', $task['schedule']);
			self::assertEquals('cron001', $task['username']);
			self::assertEquals('mycronModule2', $task['moduleid']);
			self::assertEquals(100, $task['processcount']);
			self::assertEquals($time, $task['lastexectime']);
			
			// check fresh db row
			$task = $cron->getTask('testTaskC', false, false);
			self::assertEquals('testTaskC', $task['name']);
			self::assertEquals('10 1 * * ?', $task['schedule']);
			self::assertEquals('cron001', $task['username']);
			self::assertEquals('mycronModule2', $task['moduleid']);
			self::assertEquals(100, $task['processcount']);
			self::assertEquals($time, $task['lastexectime']);
		}
		{	// cron remove
			// no task name
			self::assertTrue($this->obj->actionRemove(['cron/remove']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/Cannot remove a task without a name/i", $text));
			
			// bad task name
			self::assertTrue($this->obj->actionRemove(['cron/remove', 'testTaskD']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/'testTaskD' does not exist in the database/i", $text));
			
			// remove task name
			self::assertTrue($this->obj->actionRemove(['cron/remove', 'testTaskC']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/'testTaskC' was successfully removed/i", $text));
			
			self::assertFalse($cron->taskExists('testTaskC'));
		}
		
		$cron->removeTask($ttask);
		//$app->setModule('ShellCron', null);
		$cron->unlisten();
	}
	
}
