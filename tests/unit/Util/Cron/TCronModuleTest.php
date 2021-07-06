<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TComponent;
use Prado\TApplicationComponent;
use Prado\Security\TUserManager;
use Prado\Security\TUser;
use Prado\Util\Cron\TCronMethodTask;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Util\IDynamicMethods;

class MyTempModuleForCron extends TModule 
{
	public $method;
	public $data; 
	public function method1()
	{
		$this->method = 'method1';
	}
	public function method2($bool)
	{
		$this->method = 'method2';
		$this->data = $bool;
	}
	public function method3($int)
	{
		$this->method = 'method3';
		$this->data = $int;
	}
}

class TTestCronModuleTask extends TCronTask
{
	public $testunit;
	
	private $_propertyA;
	
	public function getPropertyA(){return $this->_propertyA;}
	public function setPropertyA($v){$this->_propertyA = $v;}
	
	public function execute($cron)
	{
		if($this->testunit)
			$this->testunit->subTaskTest();
	}
	
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "testunit";
	}
}



class TTestCronUserTask extends TCronTask
{
	public $executingUser;
	public function execute($cron)
	{
		if($user = $this->getApplication()->getUser())
			$this->executingUser = $user->getName();
	}
}


class TTestCronFXTest extends TApplicationComponent implements IDynamicMethods
{
	public $dyMethod;
	public $args;
	
	public function __dycall($method, $args)
	{
		$this->dyMethod = $method;
		$this->args = $args;
		if ($method == 'fxgetcrontaskinfos')
			return new TCronTaskInfo('taskName', 'taskDefinition', 'module1', 'text title', 'text description');
	}
}

class TCronModuleTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	protected $baseClass;
	
	public const SEPARATOR = '->';
	
	protected function getTestClass()
	{
		return "\\Prado\\Util\\Cron\\TCronModule";
	}
	
	protected function setUp(): void
	{
		$this->baseClass = $this->getTestClass();
		
		$this->obj = new $this->baseClass();
	}

	protected function tearDown(): void
	{
		$this->obj->unlisten();
		$this->obj = null;
		
		Prado::getApplication()->setGlobalState(TCronModule::LAST_CRON_TIME, null);
		Prado::getApplication()->setGlobalState(TCronModule::TASKS_INFO, null);
	}

	public function testConstruct()
	{
		$this->assertInstanceOf($this->getTestClass(), $this->obj);
	}
	
	public function testInitAndRawTasks()
	{
		try {
			$this->obj->init(null);
		} catch (Exception $e) {
			$this->fail(get_class($e) .' should not have been raised on init(null)');
		}
		if (!Prado::getApplication()->getModule('CMT_UserManager')) {
			self::assertNull($this->obj->getUserManager());
		}
		
		{	//Auto find
			$users = new TUserManager();
			$this->obj = new $this->baseClass();
			if (!($_users = Prado::getApplication()->getModule('CMT_UserManager'))) {
				Prado::getApplication()->setModule('CMT_UserManager', $users);
			} else {
				$users = $_users;
			}
			$this->obj->init(null);
			self::assertEquals($users, $this->obj->getUserManager());
		}
		{	// UserManager ID is proper
			$users = new TUserManager();
			$this->obj = new $this->baseClass();
			if (!($_users = Prado::getApplication()->getModule('CMT_UserManager2'))) {
				Prado::getApplication()->setModule('CMT_UserManager2', $users);
			} else {
				$users = $_users;
			}
			$this->obj->setUserManager('CMT_UserManager2');
			$this->obj->init(null);
			self::assertEquals($users, $this->obj->getUserManager());
		}
		try {	// UserManager ID has no module
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager('CMT_UserManager3');
			$this->obj->init(null);
			$this->fail('should have raised TConfigurationException, module not found');
		} catch(TConfigurationException $e) {}
		try {	// UserManager ID has module not of IUserManager
			$this->obj = new $this->baseClass();
			Prado::getApplication()->setModule('CMT_UserManager3', new MyTempModuleForCron());
			$this->obj->setUserManager('CMT_UserManager3');
			$this->obj->init(null);
			$this->fail('should have raised TConfigurationException, module not TUserManager');
		} catch(TConfigurationException $e) {}
		
		{
			$this->obj = new $this->baseClass();
			$jobs = '<module id="cron">
			<job schedule="0 * * * *" task="TTestCronModuleTask" />
			<job name="testTask1" schedule="1 * * * *" task="TTestCronModuleTask1" propertyA="value1"/>
			<job name="testTask2" schedule="2 * * * *" task="module1'.self::SEPARATOR.'method1" />
			<job name="testTask3" schedule="3 * * * *" task="module2'.self::SEPARATOR.'method2(true)" />
			<job name="testTask4" schedule="4 * * * *" task="module3'.self::SEPARATOR.'method3(86400)" />
				</module>';
			$xmldoc = new TXmlDocument('1.0', 'utf-8');
			$xmldoc->loadFromString($jobs);
			
			$this->obj->init($xmldoc);
			
			$tasks = $this->obj->getRawTasks();
			
			self::assertNotNull($tasks);
			self::assertEquals(5, count($tasks));
			self::assertEquals('0 * * * *', $tasks['277f1f7']['schedule']);
			self::assertEquals('TTestCronModuleTask', $tasks['277f1f7']['task']);
			self::assertEquals('1 * * * *', $tasks['testTask1']['schedule']);
			self::assertEquals('TTestCronModuleTask1', $tasks['testTask1']['task']);
			self::assertEquals('value1', $tasks['testTask1']['propertya']);
			self::assertEquals('2 * * * *', $tasks['testTask2']['schedule']);
			self::assertEquals('module1'.self::SEPARATOR.'method1', $tasks['testTask2']['task']);
			self::assertEquals('3 * * * *', $tasks['testTask3']['schedule']);
			self::assertEquals('module2'.self::SEPARATOR.'method2(true)', $tasks['testTask3']['task']);
			self::assertEquals('4 * * * *', $tasks['testTask4']['schedule']);
			self::assertEquals('module3'.self::SEPARATOR.'method3(86400)', $tasks['testTask4']['task']);
			
			//duplicate name via identical schedule-task pair
			try {
				$this->obj->init([
						'schedule' => '0 * * * *', 'task' => 'TTestCronModuleTask'
					]);
				$this->fail('failed to throw TConfigurationException from duplicate name for a task on init');
			} catch (TConfigurationException $e) {
			}
		}
		{
			$this->obj = new $this->baseClass();
			$jobs = ['jobs' =>[
				['schedule' => '0 * * * *', 'task' => 'TTestCronModuleTask'],
				['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask1', 'propertya' => 'value1'],
				['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'module1'.self::SEPARATOR.'method1'],
				['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
				['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
			]];
			
			$this->obj->init($jobs);
			
			$tasks = $this->obj->getRawTasks();
			
			self::assertNotNull($tasks);
			self::assertEquals(5, count($tasks));
			self::assertEquals('0 * * * *', $tasks['277f1f7']['schedule']);
			self::assertEquals('TTestCronModuleTask', $tasks['277f1f7']['task']);
			self::assertEquals('1 * * * *', $tasks['testTask1']['schedule']);
			self::assertEquals('TTestCronModuleTask1', $tasks['testTask1']['task']);
			self::assertEquals('value1', $tasks['testTask1']['propertya']);
			self::assertEquals('2 * * * *', $tasks['testTask2']['schedule']);
			self::assertEquals('module1'.self::SEPARATOR.'method1', $tasks['testTask2']['task']);
			self::assertEquals('3 * * * *', $tasks['testTask3']['schedule']);
			self::assertEquals('module2'.self::SEPARATOR.'method2(true)', $tasks['testTask3']['task']);
			self::assertEquals('4 * * * *', $tasks['testTask4']['schedule']);
			self::assertEquals('module3'.self::SEPARATOR.'method3(86400)', $tasks['testTask4']['task']);
		}
		{
			$this->obj = new $this->baseClass();
			$jobs = [
				['schedule' => '0 * * * *', 'task' => 'TTestCronModuleTask'],
				['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask1', 'propertya' => 'value1'],
				['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'module1'.self::SEPARATOR.'method1'],
				['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
				['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
			];
			
			$this->obj->init($jobs);
			
			$tasks = $this->obj->getRawTasks();
			
			self::assertNotNull($tasks);
			self::assertEquals(5, count($tasks));
			self::assertEquals('0 * * * *', $tasks['277f1f7']['schedule']);
			self::assertEquals('TTestCronModuleTask', $tasks['277f1f7']['task']);
			self::assertEquals('1 * * * *', $tasks['testTask1']['schedule']);
			self::assertEquals('TTestCronModuleTask1', $tasks['testTask1']['task']);
			self::assertEquals('value1', $tasks['testTask1']['propertya']);
			self::assertEquals('2 * * * *', $tasks['testTask2']['schedule']);
			self::assertEquals('module1'.self::SEPARATOR.'method1', $tasks['testTask2']['task']);
			self::assertEquals('3 * * * *', $tasks['testTask3']['schedule']);
			self::assertEquals('module2'.self::SEPARATOR.'method2(true)', $tasks['testTask3']['task']);
			self::assertEquals('4 * * * *', $tasks['testTask4']['schedule']);
			self::assertEquals('module3'.self::SEPARATOR.'method3(86400)', $tasks['testTask4']['task']);
		}
		{
			$this->obj = new $this->baseClass();
			$jobs = [
				['schedule' => '0 * * * *', 'task' => 'TTestCronModuleTask'],
				['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask1', 'propertya' => 'value1'],
				['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'module1'.self::SEPARATOR.'method1'],
				['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
				['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
			];
			
			$this->obj->setAdditionalCronTasks($jobs);
			$this->obj->init(null);
			
			$tasks = $this->obj->getRawTasks();
			
			self::assertNotNull($tasks);
			self::assertEquals(5, count($tasks));
			self::assertEquals('0 * * * *', $tasks['277f1f7']['schedule']);
			self::assertEquals('TTestCronModuleTask', $tasks['277f1f7']['task']);
			self::assertEquals('1 * * * *', $tasks['testTask1']['schedule']);
			self::assertEquals('TTestCronModuleTask1', $tasks['testTask1']['task']);
			self::assertEquals('value1', $tasks['testTask1']['propertya']);
			self::assertEquals('2 * * * *', $tasks['testTask2']['schedule']);
			self::assertEquals('module1'.self::SEPARATOR.'method1', $tasks['testTask2']['task']);
			self::assertEquals('3 * * * *', $tasks['testTask3']['schedule']);
			self::assertEquals('module2'.self::SEPARATOR.'method2(true)', $tasks['testTask3']['task']);
			self::assertEquals('4 * * * *', $tasks['testTask4']['schedule']);
			self::assertEquals('module3'.self::SEPARATOR.'method3(86400)', $tasks['testTask4']['task']);
		}
		
		try {// exception on not an array 
			$this->obj = new $this->baseClass();
			$jobs = [
				new TComponent()
			];
			$this->obj->init($jobs);
			$this->fail('failed to throw TConfigurationException from task not an array');
		} catch(TConfigurationException $e) {}
		
		//request cron cannot be checked on cli.
		
	}
	
	public function validationData()
	{
		return ['schedule' => '* * * * *', 'task' => 'TTestCronModuleTask'];
	}
	
	public function testValidateTask()
	{
		$properties = $this->validationData();
		$this->obj->validateTask($properties);
		
		//This test is a negative test to check things fail properly
		// and so has no usual test but fails when errors not thrown.
		try {
			$d = $properties;
			unset($d['schedule']);
			$this->obj->validateTask($d);
			self::fail('failed to throw TConfigurationException');
		} catch (TConfigurationException $e) {
			self::assertTrue(true);
		}
		
		try {
			$d = $properties;
			unset($d['task']);
			$this->obj->validateTask($d);
			self::fail('failed to throw TConfigurationException');
		} catch (TConfigurationException $e) {}
	}
	
	public function testGetTasks()
	{
		$jobs = [
			['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
			['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method1', 'username' => 'admin1'],
			['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method2(true)'],
			['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method3(86400)']
		];
		$this->obj->init($jobs);
		$tasks = $this->obj->getRawTasks();
		self::assertNotNull($tasks);
		self::assertEquals(4, count($tasks));
		self::assertEquals('1 * * * *', $tasks['testTask1']['schedule']);
		self::assertEquals('TTestCronModuleTask', $tasks['testTask1']['task']);
		self::assertEquals('value1', $tasks['testTask1']['propertya']);
		self::assertEquals('admin', $tasks['testTask1']['username']);
		self::assertEquals('GT_module', $tasks['testTask1']['moduleid']);
		self::assertEquals('2 * * * *', $tasks['testTask2']['schedule']);
		self::assertEquals('CMT_UserManager3'.self::SEPARATOR.'method1', $tasks['testTask2']['task']);
		self::assertEquals('admin1', $tasks['testTask2']['username']);
		self::assertEquals('3 * * * *', $tasks['testTask3']['schedule']);
		self::assertEquals('CMT_UserManager3'.self::SEPARATOR.'method2(true)', $tasks['testTask3']['task']);
		self::assertEquals('4 * * * *', $tasks['testTask4']['schedule']);
		self::assertEquals('CMT_UserManager3'.self::SEPARATOR.'method3(86400)', $tasks['testTask4']['task']);
		
		$tasks = $this->obj->getTasks();
		
		self::assertNotNull($tasks);
		
		self::assertEquals(4, count($tasks));
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask1']);
		self::assertEquals('1 * * * *', $tasks['testTask1']->getSchedule());
		self::assertEquals('testTask1', $tasks['testTask1']->getName());
		self::assertEquals('admin', $tasks['testTask1']->getUserName());
		self::assertEquals('GT_module', $tasks['testTask1']->getModuleId());
		self::assertEquals('value1', $tasks['testTask1']->getPropertyA());
		self::assertEquals('2 * * * *', $tasks['testTask2']->getSchedule());
		self::assertEquals('testTask2', $tasks['testTask2']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask2']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask2']->getModuleId());
		self::assertEquals('method1', $tasks['testTask2']->getMethod());
		self::assertEquals('3 * * * *', $tasks['testTask3']->getSchedule());
		self::assertEquals('testTask3', $tasks['testTask3']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask3']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask3']->getModuleId());
		self::assertEquals('method2(true)', $tasks['testTask3']->getMethod());
		self::assertEquals('4 * * * *', $tasks['testTask4']->getSchedule());
		self::assertEquals('testTask4', $tasks['testTask4']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask4']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask4']->getModuleId());
		self::assertEquals('method3(86400)', $tasks['testTask4']->getMethod());
		
		$tasks = $this->obj->getRawTasks();
		self::assertEquals(4, count($tasks));
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask1']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask2']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask3']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask4']);
		
		self::assertEquals(0, $tasks['testTask1']->getProcessCount());
		self::assertEquals(0, $tasks['testTask1']->getLastExecTime());
		self::assertEquals(0, $tasks['testTask2']->getProcessCount());
		self::assertEquals(0, $tasks['testTask2']->getLastExecTime());
		self::assertEquals(0, $tasks['testTask3']->getProcessCount());
		self::assertEquals(0, $tasks['testTask3']->getLastExecTime());
		self::assertEquals(0, $tasks['testTask4']->getProcessCount());
		self::assertEquals(0, $tasks['testTask4']->getLastExecTime());
		
		//check updateTaskInfo - test persistent data
		self::assertEquals(4, $this->obj->processPendingTasks());
		
		self::assertEquals(1, $tasks['testTask1']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask1']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask2']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask2']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask3']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask3']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask4']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask4']->getLastExecTime() < 2);
		
		// check 
		$this->checkPersistentData();
		
		// setPersistentData
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs);
		
		$tasks = $this->obj->getTasks();
		
		self::assertEquals(1, $tasks['testTask1']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask1']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask2']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask2']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask3']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask3']->getLastExecTime() < 2);
		self::assertEquals(1, $tasks['testTask4']->getProcessCount());
		self::assertTrue(microtime(true) - $tasks['testTask4']->getLastExecTime() < 2);
	}
	
	public function testGetTask()
	{
		$jobs = [
			['name' => 'testTask1', 'schedule' => '1 * * * ?', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
			['name' => 'testTask2', 'schedule' => '2 * * * ?', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method1', 'username' => 'admin1'],
			['name' => 'testTask3', 'schedule' => '3 * * * ?', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method2(true)'],
			['name' => 'testTask4', 'schedule' => '4 * * * ?', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method3(86400)']
		];
		$this->obj->init($jobs);
		
		$tasks = [];
		$tasks['testTask1'] = $this->obj->getTask('testTask1');
		$tasks['testTask2'] = $this->obj->getTask('testTask2');
		$tasks['testTask3'] = $this->obj->getTask('testTask3');
		$tasks['testTask4'] = $this->obj->getTask('testTask4');
		
		self::assertEquals(4, count($tasks));
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask1']);
		self::assertEquals('1 * * * ?', $tasks['testTask1']->getSchedule());
		self::assertEquals('testTask1', $tasks['testTask1']->getName());
		self::assertEquals('admin', $tasks['testTask1']->getUserName());
		self::assertEquals('GT_module', $tasks['testTask1']->getModuleId());
		self::assertEquals(0, $tasks['testTask1']->getProcessCount());
		self::assertEquals(0, $tasks['testTask1']->getLastExecTime());
		self::assertEquals('value1', $tasks['testTask1']->getPropertyA());
		self::assertEquals('2 * * * ?', $tasks['testTask2']->getSchedule());
		self::assertEquals('testTask2', $tasks['testTask2']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask2']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask2']->getModuleId());
		self::assertEquals('method1', $tasks['testTask2']->getMethod());
		self::assertEquals(0, $tasks['testTask2']->getProcessCount());
		self::assertEquals(0, $tasks['testTask2']->getLastExecTime());
		self::assertEquals('3 * * * ?', $tasks['testTask3']->getSchedule());
		self::assertEquals('testTask3', $tasks['testTask3']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask3']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask3']->getModuleId());
		self::assertEquals('method2(true)', $tasks['testTask3']->getMethod());
		self::assertEquals(0, $tasks['testTask3']->getProcessCount());
		self::assertEquals(0, $tasks['testTask3']->getLastExecTime());
		self::assertEquals('4 * * * ?', $tasks['testTask4']->getSchedule());
		self::assertEquals('testTask4', $tasks['testTask4']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask4']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask4']->getModuleId());
		self::assertEquals('method3(86400)', $tasks['testTask4']->getMethod());
		self::assertEquals(0, $tasks['testTask4']->getProcessCount());
		self::assertEquals(0, $tasks['testTask4']->getLastExecTime());
	}
	
	protected function checkPersistentData()
	{
		// checks the task info where it is stored.
		$tasksInfo = Prado::getApplication()->getGlobalState(TCronModule::TASKS_INFO, []);
		self::assertEquals(1, $tasksInfo['testTask1']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask1']['lastExecTime'] < 2);
		self::assertEquals(1, $tasksInfo['testTask2']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask2']['lastExecTime'] < 2);
		self::assertEquals(1, $tasksInfo['testTask3']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask3']['lastExecTime'] < 2);
		self::assertEquals(1, $tasksInfo['testTask4']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask4']['lastExecTime'] < 2);
	}
	
	public function testGetPendingTasks()
	{
		$this->obj->init([
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTask2', 'schedule' => '0 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method1'],
			['name' => 'testTask3', 'schedule' => '0 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method2(true)'],
			['name' => 'testTask4', 'schedule' => '0 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method3(86400)']
		]);
		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(4, count($pendingTasks));
		self::assertInstanceOf('TTestCronModuleTask', $pendingTasks['testTask1']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $pendingTasks['testTask2']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $pendingTasks['testTask3']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $pendingTasks['testTask4']);
		
		self::assertEquals(4, $this->obj->processPendingTasks());
		
		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(0, count($pendingTasks));
		self::assertEquals(0, $this->obj->processPendingTasks());
	}
	
	public function testGetTasksByType_InstanceTask()
	{
		$this->obj->init([
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTask2', 'schedule' => '0 0 1 2 *', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1'],
			['name' => 'testTask3', 'schedule' => '0 0 1 1 *', 'task' => 'module1'.self::SEPARATOR.'method1'],
			['name' => 'testTask4', 'schedule' => '0 0 1 1 *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
			['name' => 'testTask5', 'schedule' => '0 0 1 1 *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
		]);
		$tasks = $this->obj->getTasksByType('Prado\\Util\\Cron\\TCronMethodTask');
		
		self::assertEquals(3, count($tasks));
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask3']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask4']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask5']);
		
		self::assertEquals('module1', $tasks['testTask3']->getModuleId());
		self::assertEquals('method1', $tasks['testTask3']->getMethod());
		self::assertEquals('module2', $tasks['testTask4']->getModuleId());
		self::assertEquals('method2(true)', $tasks['testTask4']->getMethod());
		self::assertEquals('module3', $tasks['testTask5']->getModuleId());
		self::assertEquals('method3(86400)', $tasks['testTask5']->getMethod());
		
		//test InstanceTask also instanced the task class and properties.
		$tasks = $this->obj->getTasks();
		self::assertEquals(5, count($tasks));
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask1']);
		self::assertNull($tasks['testTask1']->PropertyA);
		self::assertInstanceOf('TTestCronModuleTask', $tasks['testTask2']);
		self::assertEquals('value1', $tasks['testTask2']->PropertyA);
		
		// task class not TCronTask
		$this->obj = new $this->baseClass();
		$this->obj->init([
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => '\\Prado\\Util\\Cron\\TCronModule']
		]);
		try {
			$tasks = $this->obj->getTasks();
			self::fail("did not throw TInvalidDataTypeException when task is not instance of TCronTask");
		} catch(TInvalidDataTypeException $e) {}
		
		$this->obj = new $this->baseClass();
	}
	
	protected $_countTasks = 0;
	public function subTaskTest()
	{
		$this->_countTasks++;
	}
	public function testProcessPendingTasks()
	{
		$jobs = [
			['name' => 'testTask1', 'schedule' => '0 0 1 1 ?', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTask2', 'schedule' => '0 0 1 1 ?', 'task' => 'TTestCronModuleTask']
		];
		$this->obj->init($jobs);
		$tasks = $this->obj->getTasks();
		$tasks['testTask1']->testunit = $this;
		$tasks['testTask2']->testunit = $this;
		self::assertEquals(0, $this->obj->getLastCronTime());
		
		self::assertEquals(2, $this->obj->processPendingTasks());
		
		// check pending tasks run
		self::assertEquals(2, $this->_countTasks);
		
		// logCron
		self::assertTrue(microtime(true) - $this->obj->getLastCronTime() < 2);
		
		// filterStaleTasks
		$this->obj = new $this->baseClass();
		$jobs[0]['name'] = 'testTask3';
		$this->obj->init($jobs);
		
		self::assertEquals(1, $this->obj->processPendingTasks());
		
		$this->checkFilterStaleTasks();
	}
	
	public function checkFilterStaleTasks()
	{
		$tasksInfo = Prado::getApplication()->getGlobalState(TCronModule::TASKS_INFO, []);
		self::assertFalse(isset($tasksInfo['testTask1']));
		self::assertEquals(1, $tasksInfo['testTask2']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask2']['lastExecTime'] < 2);
		self::assertEquals(1, $tasksInfo['testTask3']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask3']['lastExecTime'] < 2);
	}
	
	public function testRunTask()
	{
		{ // no UserManager
			$jobs = [['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronUserTask']];
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask1');
			self::assertTrue($task->executingUser === null || $task->executingUser === 'Guest');
		}
		
		$app = $this->obj->getApplication();
		$users = new TUserManager();
		
		$user = new TUser($users);
		$user->setName('current_user');
		$app->setUser($user);
		
		{ // without UserManager, current user is task executor, and needs to restore.
			$jobs = [['name' => 'testTask2', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronUserTask']];
			$this->obj = new $this->baseClass();
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask2');
			self::assertEquals('Guest', $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		
		$userconfig = new TXmlDocument('1.0', 'utf8');
		$userconfig->loadFromString('<users><user name="admin" password="demo"/><user name="cron" password="cron" /><user name="test" password="test" roles="Reader, User"/><role name="Administrator" users="admin,cron" /></users>');
		$users->init($userconfig);
		//with UserManager
		
		{	//app user is restored 
			//Task with no user ID, default module user
			$jobs = [['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronUserTask']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask1');
			self::assertEquals($this->obj->getDefaultUserName(), $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		{	//task with user id
			$jobs = [['name' => 'testTask2', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronUserTask', 'username' => 'admin']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask2');
			self::assertEquals('admin', $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		{	//task with bad user id
			$jobs = [['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronUserTask', 'username' => 'admin2']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask1');
			self::assertEquals($this->obj->getDefaultUserName(), $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		{	//task with bad user id, and bad default user id
			$jobs = [['name' => 'testTask2', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronUserTask', 'username' => 'admin2']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->setDefaultUserName('cron2');
			$this->obj->init($jobs);
			$task = $this->obj->getTask('testTask2');
			$task->executingUser = null;
			self::assertEquals(1, $this->obj->processPendingTasks());
			self::assertEquals('Guest',$task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		$users->switchToGuest($user);
		$app->setUser($user);
		
	}
	
	public function testLastCronTime()
	{
		self::assertEquals(0, $this->obj->getLastCronTime());
		
		$value = microtime(true);
		$this->obj->setLastCronTime($value);
		self::assertEquals($value, $this->obj->getLastCronTime());
	}
	
	public function testGetTaskInfos()
	{
		$task = new TTestCronFXTest();
		
		self::assertEquals('dyListen', $task->dyMethod);
		
		$taskInfos = $this->obj->getTaskInfos();
		
		self::assertEquals('fxgetcrontaskinfos', $task->dyMethod);
		self::assertEquals($this->obj, $task->args[0]);
		
		$taskInfos = $taskInfos[0];
		self::assertEquals('taskName', $taskInfos->getName());
		self::assertEquals('taskDefinition', $taskInfos->getTask());
		self::assertEquals('module1', $taskInfos->getModuleId());
		self::assertEquals('text title', $taskInfos->getTitle());
		self::assertEquals('text description', $taskInfos->getDescription());
	}
	
	public function testDefaultUserName()
	{
		$value = 'mytestuser';
		$this->obj->setDefaultUserName($value);
		self::assertEquals($value, $this->obj->getDefaultUserName());
		
		try {
			$this->obj->init(null);
			$this->obj->setDefaultUserName($value);
			self::fail('failed to throw TInvalidOperationException when cannot set due to being initialized');
		} catch(TInvalidOperationException $e) {}
	}
	
	public function testUserManager()
	{
		$this->obj->setUserManager(null);
		self::assertNull($this->obj->getUserManager());
		
		$this->obj->setUserManager('myUserModule');
		self::assertEquals('myUserModule', $this->obj->getUserManager());
		
		$users = new TUserManager();
		$this->obj->setUserManager($users);
		self::assertEquals($users, $this->obj->getUserManager());
		
		try {
			$this->obj->setUserManager(new TComponent());
			self::fail('failed to throw TConfigurationException on invalid usermanager');
		} catch(TConfigurationException $e) {}
		
		try {
			$this->obj->init(null);
			$this->obj->setUserManager(null);
			self::fail('failed to throw TInvalidOperationException when cannot set due to being initialized');
		} catch(TInvalidOperationException $e) {}
	}
	
	public function testEnableRequestCron()
	{
		self::assertFalse($this->obj->getEnableRequestCron());
		$this->obj->setEnableRequestCron(true);
		self::assertTrue($this->obj->getEnableRequestCron());
		$this->obj->setEnableRequestCron(false);
		self::assertFalse($this->obj->getEnableRequestCron());
		$this->obj->setEnableRequestCron('true');
		self::assertTrue($this->obj->getEnableRequestCron());
		$this->obj->setEnableRequestCron('false');
		self::assertFalse($this->obj->getEnableRequestCron());
		
		try {
			$this->obj->init(null);
			$this->obj->setEnableRequestCron(true);
			self::fail('failed to throw TInvalidOperationException when cannot set due to being initialized');
		} catch(TInvalidOperationException $e) {}
	}
	
	public function testRequestCronProbability()
	{
		$value = 10.0;
		$this->obj->setRequestCronProbability($value);
		self::assertEquals($value, $this->obj->getRequestCronProbability());
		
		try {
			$this->obj->init(null);
			$this->obj->setRequestCronProbability(2.0);
			self::fail('failed to throw TInvalidOperationException when cannot set due to being initialized');
		} catch(TInvalidOperationException $e) {}
	}

	public function testAdditionalCronTasks()
	{
		// default
		self::assertEquals([], $this->obj->getAdditionalCronTasks());
		
		//null is still zero array
		$this->obj->setAdditionalCronTasks(null);
		$this->assertEquals([], $this->obj->getAdditionalCronTasks());
		
		//invalid has an error
		try {
			$this->obj->setAdditionalCronTasks(99);
			self::fail('TInvalidDataTypeException not raised when setting an invalid value');
		} catch(TInvalidDataTypeException $e) {}
		
		// zero array is a zero array
		$this->obj->setAdditionalCronTasks([]);
		$this->assertEquals([], $this->obj->getAdditionalCronTasks());
		
		$arr = ['schedule' => '* * * * *', 'task' => 'TTestCronModuleTask'];
		//task becomes array of tasks.
		$this->obj->setAdditionalCronTasks($arr);
		self::assertEquals([$arr], $this->obj->getAdditionalCronTasks());
		
		$arr = [$arr];
		// array of tasks is an array of tasks
		$this->obj->setAdditionalCronTasks($arr);
		self::assertEquals($arr, $this->obj->getAdditionalCronTasks());
		
		// array of tasks is an array of tasks
		$this->obj->setAdditionalCronTasks(serialize($arr));
		self::assertEquals($arr, $this->obj->getAdditionalCronTasks());
		
		// array of tasks is an array of tasks
		$this->obj->setAdditionalCronTasks(json_encode($arr));
		self::assertEquals($arr, $this->obj->getAdditionalCronTasks());
		
		// serialized array of behaviors is an array of behaviors
		$this->obj->setAdditionalCronTasks('<module id="cron"><task schedule="* * * * *" task="TTestCronModuleTask" /></module>');
		$this->assertInstanceOf('\\Prado\\Xml\\TXmlDocument', $this->obj->getAdditionalCronTasks());
		
		try {
			$this->obj->init(null);
			$this->obj->setAdditionalCronTasks(null);
			self::fail('failed to throw TInvalidOperationException when cannot set due to being initialized');
		} catch(TInvalidOperationException $e) {}
	}

}
