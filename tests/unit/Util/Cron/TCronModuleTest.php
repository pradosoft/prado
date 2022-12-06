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
			['name' => $tn1 = 'testTask1', 'schedule' => '1 0 1 1 *', 'task' => 'TTestCronModuleTask', 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
			['name' => $tn2 = 'testTask2', 'schedule' => '2 0 1 1 * 2000', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method1', 'username' => 'admin1'],
			['name' => $tn3 = 'testTask3', 'schedule' => '3 0 1 1 * 2099', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method2(true)'],
			['name' => $tn4 = 'testTask4', 'schedule' => '4 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method3(86400)'],
			['name' => $tn5 = 'minute2', 'schedule' => '1 0 1 1 * 2000', 'task' => 'TTestCronModuleTask', 'username' => 'cron'],
			['name' => $tn6 = 'testMessage2', 'schedule' => '1 0 1 1 * 2000', 'task' => 'TTestCronModuleTask', 'username' => 'root1'],
			['name' => $tn7 = 'minute10', 'schedule' => '1 0 1 1 * 2000', 'task' => 'TTestCronModuleTask', 'username' => 'admin']
		];
		$this->obj->init($jobs);
		$tasks = $this->obj->getRawTasks();
		self::assertNotNull($tasks);
		self::assertEquals(count($jobs), count($tasks));
			
		self::assertEquals($jobs[0]['name'], $tasks[$tn1]['name']);
		self::assertEquals($jobs[0]['schedule'], $tasks[$tn1]['schedule']);
		self::assertEquals($jobs[0]['task'], $tasks[$tn1]['task']);
		self::assertEquals($jobs[0]['propertya'], $tasks[$tn1]['propertya']);
		self::assertEquals($jobs[0]['username'], $tasks[$tn1]['username']);
		self::assertEquals($jobs[0]['moduleid'], $tasks[$tn1]['moduleid']);
		self::assertEquals($jobs[1]['name'], $tasks[$tn2]['name']);
		self::assertEquals($jobs[1]['schedule'], $tasks[$tn2]['schedule']);
		self::assertEquals($jobs[1]['task'], $tasks[$tn2]['task']);
		self::assertEquals($jobs[1]['username'], $tasks[$tn2]['username']);
		self::assertEquals($jobs[2]['name'], $tasks[$tn3]['name']);
		self::assertEquals($jobs[2]['schedule'], $tasks[$tn3]['schedule']);
		self::assertEquals($jobs[2]['task'], $tasks[$tn3]['task']);
		self::assertEquals($jobs[3]['name'], $tasks[$tn4]['name']);
		self::assertEquals($jobs[3]['schedule'], $tasks[$tn4]['schedule']);
		self::assertEquals($jobs[3]['task'], $tasks[$tn4]['task']);
		self::assertEquals($jobs[4]['name'], $tasks[$tn5]['name']);
		self::assertEquals($jobs[4]['schedule'], $tasks[$tn5]['schedule']);
		self::assertEquals($jobs[4]['task'], $tasks[$tn5]['task']);
		self::assertEquals($jobs[5]['name'], $tasks[$tn6]['name']);
		self::assertEquals($jobs[5]['schedule'], $tasks[$tn6]['schedule']);
		self::assertEquals($jobs[5]['task'], $tasks[$tn6]['task']);
		self::assertEquals($jobs[6]['name'], $tasks[$tn7]['name']);
		self::assertEquals($jobs[6]['schedule'], $tasks[$tn7]['schedule']);
		self::assertEquals($jobs[6]['task'], $tasks[$tn7]['task']);
		
		$tasks = $this->obj->getTasks();
		
		self::assertNotNull($tasks);
		
		self::assertEquals(count($jobs), count($tasks));
		self::assertInstanceOf($jobs[0]['task'], $tasks[$tn1]);
		self::assertEquals($jobs[0]['schedule'], $tasks[$tn1]->getSchedule());
		self::assertEquals($tn1, $tasks[$tn1]->getName());
		self::assertEquals($jobs[0]['username'], $tasks[$tn1]->getUserName());
		self::assertEquals($jobs[0]['moduleid'], $tasks[$tn1]->getModuleId());
		self::assertEquals($jobs[0]['propertya'], $tasks[$tn1]->getPropertyA());
		self::assertEquals($jobs[1]['schedule'], $tasks[$tn2]->getSchedule());
		self::assertEquals($jobs[1]['username'], $tasks[$tn2]->getUserName());
		self::assertEquals($tn2, $tasks[$tn2]->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks[$tn2]);
		self::assertEquals('CMT_UserManager3', $tasks[$tn2]->getModuleId());
		self::assertEquals('method1', $tasks[$tn2]->getMethod());
		self::assertEquals($jobs[2]['schedule'], $tasks[$tn3]->getSchedule());
		self::assertEquals(null, $tasks[$tn3]->getUserName());
		self::assertEquals($tn3, $tasks[$tn3]->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks[$tn3]);
		self::assertEquals('CMT_UserManager3', $tasks[$tn3]->getModuleId());
		self::assertEquals('method2(true)', $tasks[$tn3]->getMethod());
		self::assertEquals($jobs[3]['schedule'], $tasks[$tn4]->getSchedule());
		self::assertEquals(null, $tasks[$tn4]->getUserName());
		self::assertEquals($tn4, $tasks[$tn4]->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks[$tn4]);
		self::assertEquals('CMT_UserManager3', $tasks[$tn4]->getModuleId());
		self::assertEquals('method3(86400)', $tasks[$tn4]->getMethod());
		
		$tasks = $this->obj->getRawTasks();
		self::assertEquals(count($jobs), count($tasks));
		self::assertInstanceOf('TTestCronModuleTask', $tasks[$tn1]);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks[$tn2]);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks[$tn3]);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks[$tn4]);
		
		self::assertEquals(0, $tasks[$tn1]->getProcessCount());
		self::assertTrue(abs($tasks[$tn1]->getLastExecTime() - time()) < 2);
		self::assertEquals(0, $tasks[$tn2]->getProcessCount());
		self::assertNull($tasks[$tn2]->getLastExecTime());
		self::assertEquals(0, $tasks[$tn3]->getProcessCount());
		self::assertNull($tasks[$tn3]->getLastExecTime());
		self::assertEquals(0, $tasks[$tn4]->getProcessCount());
		self::assertTrue(abs($tasks[$tn4]->getLastExecTime() - time()) < 2);
		self::assertEquals(0, $tasks[$tn5]->getProcessCount());
		self::assertNull($tasks[$tn5]->getLastExecTime());
		self::assertEquals(0, $tasks[$tn6]->getProcessCount());
		self::assertNull($tasks[$tn6]->getLastExecTime());
		self::assertEquals(0, $tasks[$tn7]->getProcessCount());
		self::assertNull($tasks[$tn7]->getLastExecTime());
		
		//check updateTaskInfo - test persistent data
		self::assertEquals(4, $this->obj->processPendingTasks());
		
		self::assertEquals(0, $tasks[$tn1]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn1]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn2]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn2]->getLastExecTime() < 2);
		self::assertEquals(0, $tasks[$tn3]->getProcessCount());
		self::assertNull($tasks[$tn3]->getLastExecTime());
		self::assertEquals(0, $tasks[$tn4]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn4]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn5]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn5]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn6]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn6]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn7]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn7]->getLastExecTime() < 2);
		
		// check 
		$this->checkPersistentData();
		
		// setPersistentData
		$this->obj = new $this->baseClass();
		$this->obj->init($jobs);
		
		$tasks = $this->obj->getTasks();
		
		self::assertEquals(0, $tasks[$tn1]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn1]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn2]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn2]->getLastExecTime() < 2);
		self::assertEquals(0, $tasks[$tn3]->getProcessCount());
		self::assertEquals(0, $tasks[$tn3]->getLastExecTime());
		self::assertEquals(0, $tasks[$tn4]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn4]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn5]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn5]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn6]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn6]->getLastExecTime() < 2);
		self::assertEquals(1, $tasks[$tn7]->getProcessCount());
		self::assertTrue(microtime(true) - $tasks[$tn7]->getLastExecTime() < 2);
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
		self::assertTrue((microtime(true) - $tasks['testTask1']->getLastExecTime()) < 2);
		self::assertEquals('value1', $tasks['testTask1']->getPropertyA());
		self::assertEquals('2 * * * ?', $tasks['testTask2']->getSchedule());
		self::assertEquals('testTask2', $tasks['testTask2']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask2']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask2']->getModuleId());
		self::assertEquals('method1', $tasks['testTask2']->getMethod());
		self::assertEquals(0, $tasks['testTask2']->getProcessCount());
		self::assertTrue((microtime(true) - $tasks['testTask2']->getLastExecTime()) < 2);
		self::assertEquals('3 * * * ?', $tasks['testTask3']->getSchedule());
		self::assertEquals('testTask3', $tasks['testTask3']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask3']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask3']->getModuleId());
		self::assertEquals('method2(true)', $tasks['testTask3']->getMethod());
		self::assertEquals(0, $tasks['testTask3']->getProcessCount());
		self::assertTrue((microtime(true) - $tasks['testTask3']->getLastExecTime()) < 2);
		self::assertEquals('4 * * * ?', $tasks['testTask4']->getSchedule());
		self::assertEquals('testTask4', $tasks['testTask4']->getName());
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $tasks['testTask4']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask4']->getModuleId());
		self::assertEquals('method3(86400)', $tasks['testTask4']->getMethod());
		self::assertEquals(0, $tasks['testTask4']->getProcessCount());
		self::assertTrue((microtime(true) - $tasks['testTask4']->getLastExecTime()) < 2);
	}
	
	protected function checkPersistentData()
	{
		// checks the task info where it is stored.
		$tasksInfo = Prado::getApplication()->getGlobalState(TCronModule::TASKS_INFO, []);
		self::assertTrue(isset($tasksInfo['testTask1']));
		self::assertEquals(0, $tasksInfo['testTask1']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask1']['lastExecTime'] < 2);
		self::assertTrue(isset($tasksInfo['testTask2']));
		self::assertEquals(1, $tasksInfo['testTask2']['processCount']);
		self::assertTrue(microtime(true) - $tasksInfo['testTask2']['lastExecTime'] < 2);
		self::assertTrue(isset($tasksInfo['testTask3']));
		self::assertEquals(0, $tasksInfo['testTask3']['processCount']);
		self::assertTrue(0 - $tasksInfo['testTask3']['lastExecTime'] < 2);
		self::assertTrue(isset($tasksInfo['testTask4']));
		self::assertEquals(0, $tasksInfo['testTask4']['processCount']);
		self::assertTrue(0 - $tasksInfo['testTask4']['lastExecTime'] < 2);
	}
	
	public function testGetPendingTasks()
	{
		$this->obj->init([
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTask2', 'schedule' => '0 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method1'],
			['name' => 'testTask3', 'schedule' => '0 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method2(true)'],
			['name' => 'testTask4', 'schedule' => '0 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method3(86400)'],
			['name' => 'testTask5', 'schedule' => '0 0 1 1 * ' . TTimeScheduler::YEAR_MAX, 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)'],
			['name' => 'testTask6', 'schedule' => '0 0 1 1 * 2000', 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)'],
			['name' => 'testTask7', 'schedule' => '@'.(time()-1000), 'task' => 'CMT_UserManager3' . self::SEPARATOR . 'method3(86400)']
		]);
		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(2, count($pendingTasks));
		self::assertFalse(isset($pendingTasks['testTask1']));
		self::assertFalse(isset($pendingTasks['testTask2']));
		self::assertFalse(isset($pendingTasks['testTask3']));
		self::assertFalse(isset($pendingTasks['testTask4']));
		self::assertFalse(isset($pendingTasks['testTask5']));
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $pendingTasks['testTask6']);
		self::assertInstanceOf('Prado\\Util\\Cron\\TCronMethodTask', $pendingTasks['testTask7']);

		self::assertEquals(2, $this->obj->processPendingTasks());

		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(0, count($pendingTasks));
		self::assertEquals(0, $this->obj->processPendingTasks());
		
		$this->obj = new $this->baseClass();
		$this->obj->init([
			['name' => 'A', 'schedule' => '@4200', 'task' => 'TTestCronModuleTask'],
			['name' => 'B', 'schedule' => '@2050', 'task' => 'TTestCronModuleTask'],
			['name' => 'C', 'schedule' => '@3100', 'task' => 'TTestCronModuleTask'],
			['name' => 'CC', 'schedule' => '@3100', 'task' => 'TTestCronModuleTask'],
			['name' => 'D', 'schedule' => '@1000', 'task' => 'TTestCronModuleTask'],
			['name' => 'E', 'schedule' => '@5300', 'task' => 'TTestCronModuleTask'],
			['name' => 'F', 'schedule' => '@3800', 'task' => 'TTestCronModuleTask'],
		]);
		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(7, count($pendingTasks));
		self::assertEquals(['A', 'B', 'C', 'CC', 'D', 'E', 'F'], array_keys($pendingTasks));
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
			['name' => 'testTask1', 'schedule' => '0 0 1 1 ? 2000', 'task' => 'TTestCronModuleTask'],
			['name' => 'testTask2', 'schedule' => '0 0 1 1 ? 2020', 'task' => 'TTestCronModuleTask']
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
			$jobs = [['name' => 'testTask1', 'schedule' => '0 0 1 1 * 2020', 'task' => 'TTestCronUserTask']];
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
			$jobs = [['name' => 'testTask2', 'schedule' => '0 0 1 1 * 2020', 'task' => 'TTestCronUserTask']];
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
			$jobs = [['name' => 'testTask1', 'schedule' => '0 0 1 1 * 2020', 'task' => 'TTestCronUserTask']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask1');
			self::assertEquals($this->obj->getDefaultUserName(), $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		{	//task with user id
			$jobs = [['name' => 'testTask2', 'schedule' => '0 0 1 1 * 2020', 'task' => 'TTestCronUserTask', 'username' => 'admin']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask2');
			self::assertEquals('admin', $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		{	//task with bad user id
			$jobs = [['name' => 'testTask1', 'schedule' => '0 0 1 1 * 2020', 'task' => 'TTestCronUserTask', 'username' => 'admin2']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testTask1');
			self::assertEquals($this->obj->getDefaultUserName(), $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		{	//task with bad user id, and bad default user id
			$jobs = [['name' => 'testTask2', 'schedule' => '0 0 1 1 * 2020', 'task' => 'TTestCronUserTask', 'username' => 'admin2']];
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
		$user->setIsGuest(true);
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
	
	public function testInCronShell()
	{
		self::assertEquals(null, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell('');
		self::assertEquals(true, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell(null);
		self::assertEquals(null, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell(true);
		self::assertEquals(true, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell(false);
		self::assertEquals(false, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell(1);
		self::assertEquals(true, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell(0);
		self::assertEquals(false, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell('true');
		self::assertEquals(true, $this->obj->getInCronShell());
		
		$this->obj->setInCronShell('false');
		self::assertEquals(false, $this->obj->getInCronShell());
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
