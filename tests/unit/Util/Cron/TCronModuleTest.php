<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Caching\TApplicationStateCacheDependency;
use Prado\Caching\TChainedCacheDependency;
use Prado\Caching\TDirectoryCacheDependency;
use Prado\Caching\TFileCacheDependency;
use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TDeferred;
use Prado\IO\Async\TFiberContext;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpClientResponse;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Test\Unit\Harness\Util\Cron\TTestCronModule;
use Prado\Util\Cron\TCronDaemonEventParameter;
use Prado\Util\Cron\TCronSweepEventParameter;
use Prado\Util\Cron\TCronTaskEventParameter;
use Prado\Util\Cron\Wake\TCronWakeFile;
use Prado\Util\Cron\Wake\TCronWakeSocket;
use Prado\Util\Mutex\TFileMutex;
use Prado\Util\Mutex\TMemoryMutex;
use Prado\Util\TBehavior;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TApplicationComponent;
use Prado\Security\TUserManager;
use Prado\Security\TUser;
use Prado\Util\Cron\TCronMethodTask;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Util\IDynamicMethods;
use Prado\TModule;
use Prado\Util\Cron\TTimeScheduler;
use Prado\Xml\TXmlDocument;

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

class TTestCronUserTask extends TCronTask
{
	public $executingUser;
	public function execute($cron)
	{
		if($user = $this->getApplication()->getUser())
			$this->executingUser = $user->getName();
	}
}

/**
 * Records its start and end, the user it sees before and after a pause on the loop, and can throw.
 */
class TTestCronRecordingTask extends TCronTask
{
	public static array $events = [];
	public float $pause = 0;
	public float $block = 0;
	public bool $throw = false;
	public array $users = [];

	public array $triggers = [];

	public function execute($cron)
	{
		$this->triggers[] = $this->getRunTrigger();
		self::$events[] = $this->getName() . ' start';
		$this->users[] = $this->getApplication()->getUser()?->getName();
		if ($this->pause > 0) {
			TAsync::sleep($this->pause);
			$this->users[] = $this->getApplication()->getUser()?->getName();
		}
		if ($this->block > 0) {
			usleep((int) ($this->block * 1_000_000));
		}
		if ($this->throw) {
			throw new \RuntimeException('task failed: ' . $this->getName());
		}
		self::$events[] = $this->getName() . ' end';
	}
}

/**
 * Waits on a future that never resolves, observing its cancellation token.
 */
class TTestCronWaitingTask extends TCronTask
{
	public ?\Throwable $error = null;
	public ?float $tokenTimeout = null;

	public function execute($cron)
	{
		$token = $this->getCancellationToken();
		$this->tokenTimeout = $token?->getTimeout();
		try {
			(new TDeferred())->getFuture()->await($token);
		} catch (\Throwable $e) {
			$this->error = $e;
			throw $e;
		}
	}

	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = 'error';
	}
}

/**
 * Collects the dyLogCronTaskError calls of a cron module.
 */
class TTestCronErrorLogBehavior extends TBehavior
{
	public array $errors = [];

	public function dyLogCronTaskError($task, $exception, $callchain)
	{
		$this->errors[] = [$task->getName(), $exception];
		return $callchain->dyLogCronTaskError($task, $exception);
	}
}


/**
 * Records the cron statuses its run sees.
 */
class TTestCronStatusTask extends TCronTask
{
	public array $seen = [];

	public function execute($cron)
	{
		$this->seen = $cron->getStatuses();
	}
}


/**
 * Records the requests a cron module's pings make.
 */
class TTestCronPingHttpClient extends THttpClient
{
	public array $requests = [];
	public int $status = 200;
	public bool $fail = false;

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		if ($this->fail) {
			throw new THttpClientException('httpclient_transport_error', 0, "Failed to open '{$url}'.");
		}
		$this->requests[] = [$method, $url];
		return new THttpClientResponse($this->status);
	}
}

/**
 * Declines every cron task run through `dyShouldRunTask`.
 */
class TTestCronDecliningBehavior extends TBehavior
{
	public function dyShouldRunTask($run, $task, $callchain)
	{
		return $callchain->dyShouldRunTask(false, $task);
	}
}

/**
 * Moves the fake clock of a cron test module 30 seconds forward when a run is logged, between its claim and its end.
 */
class TTestCronAdvancingBehavior extends TBehavior
{
	public function dyLogCronTask($task, $username, $callchain)
	{
		$this->getOwner()->fakeNow += 30;
		$this->getOwner()->fakeMicrotime += 30;
		return $callchain->dyLogCronTask($task, $username);
	}
}

class TCronModuleTest extends \PHPUnit\Framework\TestCase
{
	protected $obj;
	protected $baseClass;

	/** @var false|string the TERM environment variable before the test. */
	private $_term;

	/** @var string[] the wake files the test made */
	private array $_wakeFiles = [];
	
	public const SEPARATOR = '->';
	
	protected function getTestClass()
	{
		return TTestCronModule::class;
	}
	
	protected function setUp(): void
	{
		// An unset TERM (CI) detects a crontab shell, where processPendingTasks stops at a minute boundary.
		$this->_term = getenv('TERM');
		putenv('TERM=xterm');

		$this->baseClass = $this->getTestClass();
		
		$this->obj = new $this->baseClass();
		// Pin the clock to the real "now" so the minute boundary cannot roll over mid-test while the
		// "ran recently" assertions (microtime(true) - lastExecTime < 2) continue to hold.
		$this->obj->fakeNow = time();
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
		
		// Method tasks ('CMT_UserManager3' . SEPARATOR . 'methodN') execute against this module.
		if (!Prado::getApplication()->getModule('CMT_UserManager3')) {
			Prado::getApplication()->setModule('CMT_UserManager3', new MyTempModuleForCron());
		}
	}

	protected function tearDown(): void
	{
		$this->obj->unlisten();
		$this->obj = null;
		
		Prado::getApplication()->setGlobalState(TCronModule::LAST_CRON_TIME, null);
		Prado::getApplication()->setGlobalState(TCronModule::TASKS_INFO, null);

		putenv($this->_term === false ? 'TERM' : 'TERM=' . $this->_term);
		foreach ($this->_wakeFiles as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		$this->_wakeFiles = [];
	}

	/**
	 * Makes a file wake transport on a temporary file the test removes at its end.
	 * @param ?float $pollInterval the seconds between polls, or null for the default
	 * @return TCronWakeFile the transport
	 */
	protected function wakeFile(?float $pollInterval = null): TCronWakeFile
	{
		$this->_wakeFiles[] = $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-' . bin2hex(random_bytes(4));
		$transport = new TCronWakeFile($file);
		return ($pollInterval === null) ? $transport : $transport->setPollInterval($pollInterval);
	}

	public function testConstruct()
	{
		$this->assertInstanceOf($this->getTestClass(), $this->obj);
	}
	
	public function testInitAndRawTasks()
	{
		try {
			$this->obj->init(null);
		} catch (\Exception $e) {
			$this->fail($e::class .' should not have been raised on init(null)');
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
			$this->obj->setUserManager('CMT_UserManagerMissing');
			$this->obj->init(null);
			$this->fail('should have raised TConfigurationException, module not found');
		} catch(TConfigurationException $e) {}
		try {	// UserManager ID has module not of IUserManager
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager('CMT_UserManager3');
			$this->obj->init(null);
			$this->fail('should have raised TConfigurationException, module not TUserManager');
		} catch(TConfigurationException $e) {}
		
		{
			$this->obj = new $this->baseClass();
			$jobs = '<module id="cron">
			<job schedule="0 * * * *" task="Prado\Test\Unit\Util\Cron\TTestCronModuleTask" />
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
			self::assertEquals('0 * * * *', $tasks['046d0b1']['schedule']);
			self::assertEquals(TTestCronModuleTask::class, $tasks['046d0b1']['task']);
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
						'schedule' => '0 * * * *', 'task' => TTestCronModuleTask::class
					]);
				$this->fail('failed to throw TConfigurationException from duplicate name for a task on init');
			} catch (TConfigurationException $e) {
			}
		}
		{
			$this->obj = new $this->baseClass();
			$jobs = ['jobs' =>[
				['schedule' => '0 * * * *', 'task' => TTestCronModuleTask::class],
				['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask1', 'propertya' => 'value1'],
				['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'module1'.self::SEPARATOR.'method1'],
				['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
				['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
			]];
			
			$this->obj->init($jobs);
			
			$tasks = $this->obj->getRawTasks();
			
			self::assertNotNull($tasks);
			self::assertEquals(5, count($tasks));
			self::assertEquals('0 * * * *', $tasks['046d0b1']['schedule']);
			self::assertEquals(TTestCronModuleTask::class, $tasks['046d0b1']['task']);
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
				['schedule' => '0 * * * *', 'task' => TTestCronModuleTask::class],
				['name' => 'testTask1', 'schedule' => '1 * * * *', 'task' => 'TTestCronModuleTask1', 'propertya' => 'value1'],
				['name' => 'testTask2', 'schedule' => '2 * * * *', 'task' => 'module1'.self::SEPARATOR.'method1'],
				['name' => 'testTask3', 'schedule' => '3 * * * *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
				['name' => 'testTask4', 'schedule' => '4 * * * *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
			];
			
			$this->obj->init($jobs);
			
			$tasks = $this->obj->getRawTasks();
			
			self::assertNotNull($tasks);
			self::assertEquals(5, count($tasks));
			self::assertEquals('0 * * * *', $tasks['046d0b1']['schedule']);
			self::assertEquals(TTestCronModuleTask::class, $tasks['046d0b1']['task']);
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
				['schedule' => '0 * * * *', 'task' => TTestCronModuleTask::class],
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
			self::assertEquals('0 * * * *', $tasks['046d0b1']['schedule']);
			self::assertEquals(TTestCronModuleTask::class, $tasks['046d0b1']['task']);
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
		return ['schedule' => '* * * * *', 'task' => TTestCronModuleTask::class];
	}
	
	public function testInit_PhpJobKeysCaseInsensitive()
	{
		$this->obj->init(['jobs' => [
			['Name' => 'mixedCase', 'Schedule' => '5 * * * *', 'Task' => TTestCronModuleTask::class, 'PropertyA' => 'value1'],
		]]);

		$tasks = $this->obj->getRawTasks();
		self::assertEquals(['name' => 'mixedCase', 'schedule' => '5 * * * *', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1'], $tasks['mixedCase']);
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
			['name' => $tn1 = 'testTask1', 'schedule' => '1 0 1 1 *', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
			['name' => $tn2 = 'testTask2', 'schedule' => '2 0 1 1 * 2000', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method1', 'username' => 'admin1'],
			['name' => $tn3 = 'testTask3', 'schedule' => '3 0 1 1 * 2099', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method2(true)'],
			['name' => $tn4 = 'testTask4', 'schedule' => '4 0 1 1 *', 'task' => 'CMT_UserManager3'.self::SEPARATOR.'method3(86400)'],
			['name' => $tn5 = 'minute2', 'schedule' => '1 0 1 1 * 2000', 'task' => TTestCronModuleTask::class, 'username' => 'cron'],
			['name' => $tn6 = 'testMessage2', 'schedule' => '1 0 1 1 * 2000', 'task' => TTestCronModuleTask::class, 'username' => 'root1'],
			['name' => $tn7 = 'minute10', 'schedule' => '1 0 1 1 * 2000', 'task' => TTestCronModuleTask::class, 'username' => 'admin']
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
		self::assertInstanceOf(TCronMethodTask::class, $tasks[$tn2]);
		self::assertEquals('CMT_UserManager3', $tasks[$tn2]->getModuleId());
		self::assertEquals('method1', $tasks[$tn2]->getMethod());
		self::assertEquals($jobs[2]['schedule'], $tasks[$tn3]->getSchedule());
		self::assertEquals(null, $tasks[$tn3]->getUserName());
		self::assertEquals($tn3, $tasks[$tn3]->getName());
		self::assertInstanceOf(TCronMethodTask::class, $tasks[$tn3]);
		self::assertEquals('CMT_UserManager3', $tasks[$tn3]->getModuleId());
		self::assertEquals('method2(true)', $tasks[$tn3]->getMethod());
		self::assertEquals($jobs[3]['schedule'], $tasks[$tn4]->getSchedule());
		self::assertEquals(null, $tasks[$tn4]->getUserName());
		self::assertEquals($tn4, $tasks[$tn4]->getName());
		self::assertInstanceOf(TCronMethodTask::class, $tasks[$tn4]);
		self::assertEquals('CMT_UserManager3', $tasks[$tn4]->getModuleId());
		self::assertEquals('method3(86400)', $tasks[$tn4]->getMethod());
		
		$tasks = $this->obj->getRawTasks();
		self::assertEquals(count($jobs), count($tasks));
		self::assertInstanceOf(TTestCronModuleTask::class, $tasks[$tn1]);
		self::assertInstanceOf(TCronMethodTask::class, $tasks[$tn2]);
		self::assertInstanceOf(TCronMethodTask::class, $tasks[$tn3]);
		self::assertInstanceOf(TCronMethodTask::class, $tasks[$tn4]);
		
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
			['name' => 'testTask1', 'schedule' => '1 * * * ?', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1', 'username' => 'admin', 'moduleid' => 'GT_module'],
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
		self::assertInstanceOf(TTestCronModuleTask::class, $tasks['testTask1']);
		self::assertEquals('1 * * * ?', $tasks['testTask1']->getSchedule());
		self::assertEquals('testTask1', $tasks['testTask1']->getName());
		self::assertEquals('admin', $tasks['testTask1']->getUserName());
		self::assertEquals('GT_module', $tasks['testTask1']->getModuleId());
		self::assertEquals(0, $tasks['testTask1']->getProcessCount());
		self::assertTrue((microtime(true) - $tasks['testTask1']->getLastExecTime()) < 2);
		self::assertEquals('value1', $tasks['testTask1']->getPropertyA());
		self::assertEquals('2 * * * ?', $tasks['testTask2']->getSchedule());
		self::assertEquals('testTask2', $tasks['testTask2']->getName());
		self::assertInstanceOf(TCronMethodTask::class, $tasks['testTask2']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask2']->getModuleId());
		self::assertEquals('method1', $tasks['testTask2']->getMethod());
		self::assertEquals(0, $tasks['testTask2']->getProcessCount());
		self::assertTrue((microtime(true) - $tasks['testTask2']->getLastExecTime()) < 2);
		self::assertEquals('3 * * * ?', $tasks['testTask3']->getSchedule());
		self::assertEquals('testTask3', $tasks['testTask3']->getName());
		self::assertInstanceOf(TCronMethodTask::class, $tasks['testTask3']);
		self::assertEquals('CMT_UserManager3', $tasks['testTask3']->getModuleId());
		self::assertEquals('method2(true)', $tasks['testTask3']->getMethod());
		self::assertEquals(0, $tasks['testTask3']->getProcessCount());
		self::assertTrue((microtime(true) - $tasks['testTask3']->getLastExecTime()) < 2);
		self::assertEquals('4 * * * ?', $tasks['testTask4']->getSchedule());
		self::assertEquals('testTask4', $tasks['testTask4']->getName());
		self::assertInstanceOf(TCronMethodTask::class, $tasks['testTask4']);
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
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => TTestCronModuleTask::class],
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
		self::assertInstanceOf(TCronMethodTask::class, $pendingTasks['testTask6']);
		self::assertInstanceOf(TCronMethodTask::class, $pendingTasks['testTask7']);

		self::assertEquals(2, $this->obj->processPendingTasks());

		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(0, count($pendingTasks));
		self::assertEquals(0, $this->obj->processPendingTasks());
		
		$this->obj = new $this->baseClass();
		$this->obj->init([
			['name' => 'A', 'schedule' => '@4200', 'task' => TTestCronModuleTask::class],
			['name' => 'B', 'schedule' => '@2050', 'task' => TTestCronModuleTask::class],
			['name' => 'C', 'schedule' => '@3100', 'task' => TTestCronModuleTask::class],
			['name' => 'CC', 'schedule' => '@3100', 'task' => TTestCronModuleTask::class],
			['name' => 'D', 'schedule' => '@1000', 'task' => TTestCronModuleTask::class],
			['name' => 'E', 'schedule' => '@5300', 'task' => TTestCronModuleTask::class],
			['name' => 'F', 'schedule' => '@3800', 'task' => TTestCronModuleTask::class],
		]);
		$pendingTasks = $this->obj->getPendingTasks();
		self::assertEquals(7, count($pendingTasks));
		self::assertEquals(['A', 'B', 'C', 'CC', 'D', 'E', 'F'], array_keys($pendingTasks));
	}
	
	public function testGetTasksByType_InstanceTask()
	{
		$this->obj->init([
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => TTestCronModuleTask::class],
			['name' => 'testTask2', 'schedule' => '0 0 1 2 *', 'task' => TTestCronModuleTask::class, 'propertya' => 'value1'],
			['name' => 'testTask3', 'schedule' => '0 0 1 1 *', 'task' => 'module1'.self::SEPARATOR.'method1'],
			['name' => 'testTask4', 'schedule' => '0 0 1 1 *', 'task' => 'module2'.self::SEPARATOR.'method2(true)'],
			['name' => 'testTask5', 'schedule' => '0 0 1 1 *', 'task' => 'module3'.self::SEPARATOR.'method3(86400)']
		]);
		$tasks = $this->obj->getTasksByType(TCronMethodTask::class);
		
		self::assertEquals(3, count($tasks));
		self::assertInstanceOf(TCronMethodTask::class, $tasks['testTask3']);
		self::assertInstanceOf(TCronMethodTask::class, $tasks['testTask4']);
		self::assertInstanceOf(TCronMethodTask::class, $tasks['testTask5']);
		
		self::assertEquals('module1', $tasks['testTask3']->getModuleId());
		self::assertEquals('method1', $tasks['testTask3']->getMethod());
		self::assertEquals('module2', $tasks['testTask4']->getModuleId());
		self::assertEquals('method2(true)', $tasks['testTask4']->getMethod());
		self::assertEquals('module3', $tasks['testTask5']->getModuleId());
		self::assertEquals('method3(86400)', $tasks['testTask5']->getMethod());
		
		//test InstanceTask also instanced the task class and properties.
		$tasks = $this->obj->getTasks();
		self::assertEquals(5, count($tasks));
		self::assertInstanceOf(TTestCronModuleTask::class, $tasks['testTask1']);
		self::assertNull($tasks['testTask1']->PropertyA);
		self::assertInstanceOf(TTestCronModuleTask::class, $tasks['testTask2']);
		self::assertEquals('value1', $tasks['testTask2']->PropertyA);
		
		// task class not TCronTask
		$this->obj = new $this->baseClass();
		$this->obj->init([
			['name' => 'testTask1', 'schedule' => '0 0 1 1 *', 'task' => TCronModule::class]
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
			['name' => 'testTask1', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class],
			['name' => 'testTask2', 'schedule' => '0 0 1 1 ? 2020', 'task' => TTestCronModuleTask::class]
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
			$jobs = [['name' => 'testRunTask1', 'schedule' => '0 0 1 1 * 2020', 'task' => TTestCronUserTask::class]];
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testRunTask1');
			self::assertTrue($task->executingUser === null || $task->executingUser === 'Guest');
		}
		
		$app = $this->obj->getApplication();
		$users = new TUserManager();
		
		$user = new TUser($users);
		$user->setName('current_user');
		$app->setUser($user);
		
		{ // without UserManager, current user is task executor, and needs to restore.
			$jobs = [['name' => 'testRunTask2', 'schedule' => '0 0 1 1 * 2020', 'task' => TTestCronUserTask::class]];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testRunTask2');
			self::assertEquals('Guest', $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		
		$userconfig = new TXmlDocument('1.0', 'utf8');
		$userconfig->loadFromString('<users><user name="admin" password="demo"/><user name="cron" password="cron" /><user name="test" password="test" roles="Reader, User"/><role name="Administrator" users="admin,cron" /></users>');
		$priorConfigType = $app->getConfigurationType();
		$app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		try {
			$users->init($userconfig);
		} finally {
			$app->setConfigurationType($priorConfigType);
		}
		//with UserManager
		
		{	//app user is restored 
			//Task with no user ID, default module user
			$jobs = [['name' => 'testRunTask1', 'schedule' => '0 0 1 1 * 2020', 'task' => TTestCronUserTask::class]];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testRunTask1');
			self::assertEquals($this->obj->getDefaultUserName(), $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		{	//task with user id
			$jobs = [['name' => 'testRunTask2', 'schedule' => '0 0 1 1 * 2020', 'task' => TTestCronUserTask::class, 'username' => 'admin']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testRunTask2');
			self::assertEquals('admin', $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		{	//task with bad user id
			$jobs = [['name' => 'testRunTask1', 'schedule' => '0 0 1 1 * 2020', 'task' => TTestCronUserTask::class, 'username' => 'admin2']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->init($jobs);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$task = $this->obj->getTask('testRunTask1');
			self::assertEquals($this->obj->getDefaultUserName(), $task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		
		{	//task with bad user id, and bad default user id
			$jobs = [['name' => 'testRunTask2', 'schedule' => '0 0 1 1 * 2020', 'task' => TTestCronUserTask::class, 'username' => 'admin2']];
			$this->obj = new $this->baseClass();
			$this->obj->setUserManager($users);
			$this->obj->setDefaultUserName('cron2');
			$this->obj->init($jobs);
			$task = $this->obj->getTask('testRunTask2');
			$task->executingUser = null;
			self::assertEquals(1, $this->obj->processPendingTasks());
			self::assertEquals('Guest',$task->executingUser);
			self::assertEquals($user, $app->getUser());
		}
		$user->setIsGuest(true);
		$app->setUser($user);
		
	}
	
	public function testConcurrency()
	{
		self::assertSame(1, $this->obj->getConcurrency());
		$this->obj->setConcurrency('3');
		self::assertSame(3, $this->obj->getConcurrency());
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setConcurrency(0);
	}

	public function testTaskTimeout()
	{
		self::assertNull($this->obj->getTaskTimeout());
		$this->obj->setTaskTimeout('2.5');
		self::assertSame(2.5, $this->obj->getTaskTimeout());
		$this->obj->setTaskTimeout('');
		self::assertNull($this->obj->getTaskTimeout());
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setTaskTimeout(0);
	}

	public function testProcessPendingTasks_ConcurrencyRunsTasksInFibers()
	{
		TTestCronRecordingTask::$events = [];
		$this->obj->init([
			['name' => 'slowA', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
			['name' => 'slowB', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
		]);
		$tasks = $this->obj->getTasks();
		$tasks['slowA']->pause = 0.02;
		$tasks['slowB']->pause = 0.02;
		self::assertEquals(2, $this->obj->processPendingTasks());
		self::assertEquals(['slowA start', 'slowA end', 'slowB start', 'slowB end'], TTestCronRecordingTask::$events, 'Concurrency 1 runs the tasks in order');

		TTestCronRecordingTask::$events = [];
		$this->obj->unlisten();
		$this->obj = new $this->baseClass();
		$this->obj->setConcurrency(2);
		$this->obj->init([
			['name' => 'slowC', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'mode' => 'Parallel'],
			['name' => 'slowD', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'mode' => 'parallel'],
			['name' => 'slowE', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'mode' => 'Parallel'],
		]);
		$tasks = $this->obj->getTasks();
		$tasks['slowC']->pause = 0.02;
		$tasks['slowD']->pause = 0.02;
		$tasks['slowE']->pause = 0.02;
		self::assertEquals(3, $this->obj->processPendingTasks());
		$order = array_flip(TTestCronRecordingTask::$events);
		self::assertCount(6, $order);
		self::assertLessThan($order['slowC end'], $order['slowD start'], 'Concurrency 2 starts the second task while the first waits');
		self::assertGreaterThan($order['slowC end'], $order['slowE start'], 'The third task waits for a slot');
		self::assertGreaterThan($order['slowE start'], $order['slowE end']);

		TTestCronRecordingTask::$events = [];
		$this->obj->unlisten();
		$this->obj = new $this->baseClass();
		$this->obj->setConcurrency(2);
		$this->obj->init([
			['name' => 'slowF', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'mode' => 'Parallel'],
			['name' => 'slowG', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
			['name' => 'slowH', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'mode' => 'Parallel'],
		]);
		foreach ($this->obj->getTasks() as $task) {
			$task->pause = 0.02;
		}
		self::assertEquals(3, $this->obj->processPendingTasks());
		self::assertEquals(['slowF start', 'slowF end', 'slowG start', 'slowG end', 'slowH start', 'slowH end'], TTestCronRecordingTask::$events, 'a Serial task waits for the running tasks, and runs alone');
	}

	public function testRunTask_UserIsTheFiberUser()
	{
		$app = $this->obj->getApplication();
		$priorUser = $app->getUser();
		$users = new TUserManager();
		$userconfig = new TXmlDocument('1.0', 'utf8');
		$userconfig->loadFromString('<users><user name="admin" password="demo"/><user name="cron" password="cron" /></users>');
		$priorConfigType = $app->getConfigurationType();
		$app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		try {
			$users->init($userconfig);
		} finally {
			$app->setConfigurationType($priorConfigType);
		}
		$current = new TUser($users);
		$current->setName('current_user');
		$app->setUser($current);
		try {
			$this->obj->setConcurrency(2);
			$this->obj->setUserManager($users);
			$this->obj->init([
				['name' => 'userA', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'username' => 'admin'],
				['name' => 'userB', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'username' => 'cron'],
			]);
			$tasks = $this->obj->getTasks();
			$tasks['userA']->pause = 0.02;
			$tasks['userB']->pause = 0.02;
			self::assertEquals(2, $this->obj->processPendingTasks());
			self::assertEquals(['admin', 'admin'], $tasks['userA']->users, 'A task sees its own user before and after yielding to the other task');
			self::assertEquals(['cron', 'cron'], $tasks['userB']->users);
			self::assertSame($current, $app->getUser(), 'The application user is unchanged');
			self::assertFalse(TFiberContext::has(TApplication::USER_CONTEXT_KEY));
		} finally {
			PradoUnit::setProp($app, '_user', $priorUser);
		}
	}

	public function testRunTask_ExceptionIsLoggedAndTheSweepContinues()
	{
		TTestCronRecordingTask::$events = [];
		$behavior = new TTestCronErrorLogBehavior();
		$this->obj->attachBehavior('errorLog', $behavior);
		$this->obj->init([
			['name' => 'failing', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
			['name' => 'after', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
		]);
		$tasks = $this->obj->getTasks();
		$tasks['failing']->throw = true;
		self::assertEquals(2, $this->obj->processPendingTasks());
		self::assertEquals(['failing start', 'after start', 'after end'], TTestCronRecordingTask::$events);
		self::assertCount(1, $behavior->errors);
		self::assertEquals('failing', $behavior->errors[0][0]);
		self::assertInstanceOf(\RuntimeException::class, $behavior->errors[0][1]);
		self::assertEquals('task failed: failing', $behavior->errors[0][1]->getMessage());

		self::assertFalse($this->obj->runTask($tasks['failing']));
		self::assertTrue($this->obj->runTask($tasks['after']));
		self::assertCount(2, $behavior->errors);
	}

	public function testRunTask_TimeoutCancelsTheTask()
	{
		$behavior = new TTestCronErrorLogBehavior();
		$this->obj->attachBehavior('errorLog', $behavior);
		$this->obj->init([
			['name' => 'waiting', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronWaitingTask::class, 'timeout' => '0.05'],
		]);
		$task = $this->obj->getTask('waiting');
		self::assertSame(0.05, $task->getTimeout());
		$start = microtime(true);
		self::assertEquals(1, $this->obj->processPendingTasks());
		self::assertLessThan(2, microtime(true) - $start);
		self::assertSame(0.05, $task->tokenTimeout, 'The task Timeout is the token Timeout');
		self::assertInstanceOf(TCancelledException::class, $task->error);
		self::assertNull($task->getCancellationToken(), 'The token is cleared after the run');
		self::assertCount(1, $behavior->errors);
		self::assertInstanceOf(TCancelledException::class, $behavior->errors[0][1]);

		// The module TaskTimeout applies to a task without its own Timeout.
		$this->obj->unlisten();
		$this->obj = new $this->baseClass();
		$this->obj->setTaskTimeout(0.05);
		$this->obj->init([
			['name' => 'waiting2', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronWaitingTask::class],
		]);
		$task = $this->obj->getTask('waiting2');
		self::assertNull($task->getTimeout());
		self::assertEquals(1, $this->obj->processPendingTasks());
		self::assertSame(0.05, $task->tokenTimeout);
		self::assertInstanceOf(TCancelledException::class, $task->error);

		// A task that finishes before its deadline leaves no timer on the loop.
		$this->obj->unlisten();
		$this->obj = new $this->baseClass();
		$this->obj->setTaskTimeout(10);
		$this->obj->init([
			['name' => 'quick', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
		]);
		$before = count(TAsync::getLoop()->getIdentifiers());
		self::assertEquals(1, $this->obj->processPendingTasks());
		self::assertCount($before, TAsync::getLoop()->getIdentifiers());
	}

	public function testDaemonProperties()
	{
		$suffix = substr(md5(static::class), 0, 6);
		self::assertFalse($this->obj->getDaemon());
		self::assertEquals(TCronModule::DEFAULT_DAEMON_LIFETIME, $this->obj->getDaemonLifetime());
		self::assertEquals(TCronModule::DEFAULT_DAEMON_IDLE, $this->obj->getDaemonIdle());
		self::assertInstanceOf(TFileMutex::class, $this->obj->getDaemonMutex());
		self::assertInstanceOf(TCronWakeSocket::class, $this->obj->getWakeTransport());
		self::assertEquals('cron:daemon:cron', $this->obj->getDaemonLockName());
		$this->obj->setID('cronDaemon' . $suffix);
		self::assertEquals('cron:daemon:cronDaemon' . $suffix, $this->obj->getDaemonLockName());

		$this->obj->setDaemon('true');
		self::assertTrue($this->obj->getDaemon());
		$this->obj->setDaemonLifetime('0');
		self::assertSame(0.0, $this->obj->getDaemonLifetime());
		$this->obj->setDaemonIdle('2.5');
		self::assertSame(2.5, $this->obj->getDaemonIdle());
		self::assertEquals(TCronModule::DEFAULT_DAEMON_HANDOFF_TIMEOUT, $this->obj->getDaemonHandoffTimeout());
		$this->obj->setDaemonHandoffTimeout('3');
		self::assertSame(3.0, $this->obj->getDaemonHandoffTimeout());
		self::assertSame($this->obj->getDaemonLockName() . ':retiring', $this->obj->getDaemonRetiringLockName());
		self::assertTrue($this->obj->getDaemonWatchConfig());
		$appConfig = ($file = Prado::getApplication()->getConfigurationFile()) ? [$file] : [];
		self::assertSame($appConfig, $this->obj->getDaemonConfigFiles(), 'the application configuration file is watched by default');
		$this->obj->setDaemonWatchConfig('false');
		self::assertFalse($this->obj->getDaemonWatchConfig());
		$this->obj->setDaemonConfigFiles(' /tmp/a.xml , /tmp/b.php ');
		self::assertSame(['/tmp/a.xml', '/tmp/b.php'], $this->obj->getDaemonConfigFiles());
		$this->obj->setDaemonConfigFiles(['/tmp/c.xml']);
		self::assertSame(['/tmp/c.xml'], $this->obj->getDaemonConfigFiles());
		$fingerprint = $this->obj->getDaemonConfigFingerprint();
		self::assertSame(64, strlen($fingerprint));
		self::assertSame($fingerprint, $this->obj->getDaemonConfigFingerprint(), 'the fingerprint is stable');
		$this->obj->setDaemonConfigFiles(null);
		self::assertSame($appConfig, $this->obj->getDaemonConfigFiles());
		self::assertNotSame($fingerprint, $this->obj->getDaemonConfigFingerprint());
		self::assertSame($this->obj->getDaemonLockName() . ':handoff', $this->obj->getDaemonHandoffLockName());

		$mutex = new TMemoryMutex();
		$this->obj->setDaemonMutex($mutex);
		self::assertSame($mutex, $this->obj->getDaemonMutex());
		$this->obj->setDaemonMutex(null);
		self::assertInstanceOf(TFileMutex::class, $this->obj->getDaemonMutex());
		$file = new TCronWakeFile(sys_get_temp_dir() . '/cronwake-props-' . $suffix);
		$this->obj->setWakeTransport($file);
		self::assertSame($file, $this->obj->getWakeTransport());

		$app = Prado::getApplication();
		$app->setModule('cronTestDaemonMutex' . $suffix, $mutex);
		$app->setModule('cronTestWakeFile' . $suffix, $file);
		$this->obj->setDaemonMutex('cronTestDaemonMutex' . $suffix);
		$this->obj->setWakeTransport('cronTestWakeFile' . $suffix);
		self::assertSame($mutex, $this->obj->getDaemonMutex());
		self::assertSame($file, $this->obj->getWakeTransport());

		try {
			$this->obj->setDaemonLifetime(-1);
			self::fail('a negative DaemonLifetime is invalid');
		} catch (TInvalidDataValueException $e) {
		}
		try {
			$this->obj->setDaemonIdle(0);
			self::fail('a zero DaemonIdle is invalid');
		} catch (TInvalidDataValueException $e) {
		}
		try {
			$this->obj->setDaemonHandoffTimeout(-1);
			self::fail('a negative DaemonHandoffTimeout is invalid');
		} catch (TInvalidDataValueException $e) {
		}
		try {
			$this->obj->setDaemonMutex(42);
			self::fail('DaemonMutex takes a mutex, a module ID, or null');
		} catch (TConfigurationException $e) {
		}
		$this->obj->setDaemonMutex('cronTestNoSuchModule');
		try {
			$this->obj->getDaemonMutex();
			self::fail('an unknown DaemonMutex module ID is a configuration error');
		} catch (TConfigurationException $e) {
		}
		$this->obj->setWakeTransport('cronTestNoSuchModule');
		try {
			$this->obj->getWakeTransport();
			self::fail('an unknown WakeTransport module ID is a configuration error');
		} catch (TConfigurationException $e) {
		}
	}

	public function testInit_WakeAndMutexElements()
	{
		$suffix = substr(md5(static::class), 0, 6);
		$wakeFile = sys_get_temp_dir() . '/cronwake-element-' . $suffix;
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		$xmldoc->loadFromString('<module id="cron">
			<wake class="Prado\\Util\\Cron\\Wake\\TCronWakeFile" WakeFile="' . $wakeFile . '" PollInterval="0.25" />
			<mutex class="Prado\\Util\\Mutex\\TMemoryMutex" />
			<job name="elementTask" schedule="0 0 1 1 ? 2000" task="' . TTestCronModuleTask::class . '" />
		</module>');
		$this->obj->init($xmldoc);
		$wake = $this->obj->getWakeTransport();
		self::assertInstanceOf(TCronWakeFile::class, $wake);
		self::assertSame($wakeFile, $wake->getWakeFile());
		self::assertSame(0.25, $wake->getPollInterval());
		self::assertNull($wake->getID(), 'the element component is not a registered module');
		self::assertInstanceOf(TMemoryMutex::class, $this->obj->getDaemonMutex());
		self::assertArrayHasKey('elementTask', $this->obj->getRawTasks(), 'the elements do not disturb the jobs');

		$php = new $this->baseClass();
		$php->init([
			'wake' => ['class' => TCronWakeFile::class, 'properties' => ['WakeFile' => $wakeFile, 'PollInterval' => 0.5]],
			'mutex' => TMemoryMutex::class,
		]);
		$wake = $php->getWakeTransport();
		self::assertInstanceOf(TCronWakeFile::class, $wake);
		self::assertSame($wakeFile, $wake->getWakeFile());
		self::assertSame(0.5, $wake->getPollInterval());
		self::assertInstanceOf(TMemoryMutex::class, $php->getDaemonMutex());
		self::assertSame([], $php->getRawTasks(), 'the keys are not jobs');

		$cases = [
			'<module id="cron"><wake PollInterval="1" /></module>' => 'an element without a class',
			'<module id="cron"><wake class="Prado\\Util\\Mutex\\TMemoryMutex" /></module>' => 'a wake that is not a transport',
			'<module id="cron"><mutex class="Prado\\Util\\Cron\\Wake\\TCronWakeFile" /></module>' => 'a mutex that is not a mutex',
		];
		foreach ($cases as $xml => $message) {
			$xmldoc = new TXmlDocument('1.0', 'utf-8');
			$xmldoc->loadFromString($xml);
			try {
				(new $this->baseClass())->init($xmldoc);
				self::fail($message . ' is a configuration error');
			} catch (TConfigurationException $e) {
			}
		}
		try {
			(new $this->baseClass())->init(['mutex' => 42]);
			self::fail('a PHP mutex that is neither an array nor a class is a configuration error');
		} catch (TConfigurationException $e) {
		}
	}

	public function testInit_DependencyElements()
	{
		$dir = sys_get_temp_dir() . '/cron-dependency-' . bin2hex(random_bytes(4));
		mkdir($dir);
		$file = $dir . '/watched';
		file_put_contents($file, 'v1');
		try {
			$xmldoc = new TXmlDocument('1.0', 'utf-8');
			$xmldoc->loadFromString('<module id="cron">
				<dependency class="Prado\\Caching\\TFileCacheDependency" FileName="' . $file . '" />
				<job name="elementTask" schedule="0 0 1 1 ? 2000" task="' . TTestCronModuleTask::class . '" />
			</module>');
			$this->obj->init($xmldoc);
			$dependency = $this->obj->getDaemonDependency();
			self::assertInstanceOf(TFileCacheDependency::class, $dependency);
			self::assertSame($file, $dependency->getFileName());
			self::assertArrayHasKey('elementTask', $this->obj->getRawTasks(), 'the element does not disturb the jobs');

			$xmldoc = new TXmlDocument('1.0', 'utf-8');
			$xmldoc->loadFromString('<module id="cron">
				<dependency class="Prado\\Caching\\TFileCacheDependency" FileName="' . $file . '" />
				<dependency class="Prado\\Caching\\TDirectoryCacheDependency" Directory="' . $dir . '" />
			</module>');
			$chained = new $this->baseClass();
			$chained->init($xmldoc);
			$dependency = $chained->getDaemonDependency();
			self::assertInstanceOf(TChainedCacheDependency::class, $dependency, 'several dependencies are chained');
			self::assertCount(2, $dependency->getDependencies());
			self::assertInstanceOf(TFileCacheDependency::class, $dependency->getDependencies()[0]);
			self::assertInstanceOf(TDirectoryCacheDependency::class, $dependency->getDependencies()[1]);

			$php = new $this->baseClass();
			$php->init(['dependency' => ['class' => TFileCacheDependency::class, 'properties' => ['FileName' => $file]]]);
			self::assertSame($file, $php->getDaemonDependency()->getFileName());
			self::assertSame([], $php->getRawTasks(), 'the key is not a job');

			$php = new $this->baseClass();
			$php->init(['dependency' => [
				['class' => TFileCacheDependency::class, 'properties' => ['FileName' => $file]],
				TApplicationStateCacheDependency::class,
			]]);
			self::assertInstanceOf(TChainedCacheDependency::class, $php->getDaemonDependency());
			self::assertCount(2, $php->getDaemonDependency()->getDependencies());

			$php = new $this->baseClass();
			$php->init(['dependency' => TApplicationStateCacheDependency::class]);
			self::assertInstanceOf(TApplicationStateCacheDependency::class, $php->getDaemonDependency());
		} finally {
			@unlink($file);
			@rmdir($dir);
		}

		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		$xmldoc->loadFromString('<module id="cron"><dependency class="Prado\\Util\\Mutex\\TMemoryMutex" /></module>');
		try {
			(new $this->baseClass())->init($xmldoc);
			self::fail('a dependency that is not an ICacheDependency is a configuration error');
		} catch (TConfigurationException $e) {
			self::assertStringContainsString('ICacheDependency', $e->getMessage());
		}
		try {
			(new $this->baseClass())->init(['dependency' => [42]]);
			self::fail('a PHP dependency that is neither an array nor a class is a configuration error');
		} catch (TConfigurationException $e) {
		}
	}

	public function testRunDaemon_RunsTheDueTasksAndRetiresAfterTheLifetime()
	{
		$now = $this->obj->fakeNow;
		$mutex = new TMemoryMutex();
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0.3);
		$this->obj->setDaemonIdle(0.05);
		$this->obj->setDaemonMutex($mutex);
		$this->obj->setWakeTransport($this->wakeFile());
		$this->obj->init([
			['name' => 'daemonDue', 'schedule' => '@' . ($now - 10), 'task' => TTestCronModuleTask::class],
			['name' => 'daemonLater', 'schedule' => '@' . ($now + 86400), 'task' => TTestCronModuleTask::class],
		]);
		$this->obj->getTask('daemonDue')->testunit = $this;
		$events = [];
		foreach (['OnDaemonStart', 'OnDaemonIdle', 'OnDaemonEnd'] as $event) {
			$this->obj->attachEventHandler($event, function ($sender, $param) use (&$events, $event) {
				self::assertInstanceOf(TCronDaemonEventParameter::class, $param);
				$events[$event][] = $param;
				if ($event === 'OnDaemonIdle') {
					$param->setWait(0.02); // a handler shortens the wait
				}
			});
		}
		// Without a tick to hand over to, a daemon past its lifetime keeps serving while holding the retiring lock.
		$loop = TAsync::getLoop();
		$retiringHeld = null;
		$loop->delay(0.45, function () use (&$retiringHeld) {
			$probe = new TMemoryMutex();
			$retiringHeld = !$probe->acquire($this->obj->getDaemonRetiringLockName());
			$probe->releaseAll();
			$this->obj->stopDaemon();
		});
		$start = microtime(true);
		self::assertEquals(1, $this->obj->processPendingTasks());
		$elapsed = microtime(true) - $start;
		self::assertGreaterThanOrEqual(0.4, $elapsed, 'the daemon served past its lifetime until stopped');
		self::assertLessThan(3.0, $elapsed);
		self::assertTrue($retiringHeld, 'the retiring lock is held after the lifetime');
		self::assertEquals(1, $this->_countTasks);
		self::assertEquals(1, $this->obj->getTask('daemonDue')->getProcessCount());
		self::assertEquals(0, $this->obj->getTask('daemonLater')->getProcessCount());
		self::assertFalse($mutex->isAcquired($this->obj->getDaemonLockName()), 'the lease is released');
		self::assertFalse($mutex->isAcquired($this->obj->getDaemonRetiringLockName()), 'the retiring lock is released');
		self::assertCount(1, $events['OnDaemonStart']);
		self::assertSame($this->obj->getDaemonLockName(), $events['OnDaemonStart'][0]->getLockName());
		self::assertTrue($events['OnDaemonStart'][0]->getWatching());
		self::assertGreaterThanOrEqual(3, count($events['OnDaemonIdle']), 'the daemon idles repeatedly through the shortened waits (a loaded runner yields few)');
		self::assertSame(0.02, $events['OnDaemonIdle'][0]->getWait(), 'the handler shortened the wait');
		self::assertSame(1, $events['OnDaemonIdle'][0]->getTasks(), 'the first idle follows the sweep that ran the task');
		self::assertSame(0, $events['OnDaemonIdle'][1]->getTasks());
		self::assertCount(1, $events['OnDaemonEnd']);
		self::assertSame(1, $events['OnDaemonEnd'][0]->getTasks());
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $events['OnDaemonEnd'][0]->getReason());
		self::assertGreaterThanOrEqual(0.4, $events['OnDaemonEnd'][0]->getElapsed());
	}

	public function testRunDaemon_ExitsWhenAnotherProcessHoldsTheLease()
	{
		$now = $this->obj->fakeNow;
		$other = new TMemoryMutex();
		$this->obj->setDaemon(true);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'daemonHeld', 'schedule' => '@' . ($now - 10), 'task' => TTestCronModuleTask::class]]);
		self::assertTrue($other->acquire($this->obj->getDaemonLockName()));
		try {
			self::assertEquals(0, $this->obj->processPendingTasks());
			self::assertEquals(0, $this->obj->getTask('daemonHeld')->getProcessCount());
		} finally {
			$other->release($this->obj->getDaemonLockName());
		}
	}

	public function testRunDaemon_RetiresAndHandsTheLeaseToTheNextTick()
	{
		$now = $this->obj->fakeNow;
		$retiring = $this->obj;
		$retiring->setDaemon(true);
		$retiring->setDaemonLifetime(0.05);
		$retiring->setDaemonIdle(0.5); // the tick's wake ends the wait sooner; this bounds the test without one
		$retiring->setDaemonMutex(new TMemoryMutex());
		$retiring->setWakeTransport($this->wakeFile(0.02));
		$retiring->init([['name' => 'handoffTask', 'schedule' => '@' . ($now - 10), 'task' => TTestCronModuleTask::class]]);

		$tick = new $this->baseClass();
		$tick->fakeNow = $now;
		$tick->fakeMicrotime = (float) $now;
		$tick->setDaemon(true);
		$tick->setDaemonLifetime(0);
		$tick->setDaemonIdle(10);
		$tick->setDaemonHandoffTimeout(5);
		$tick->realWake = true; // the harness records wakes; this tick must really wake the retiring daemon
		$tick->setDaemonMutex(new TMemoryMutex());
		$tick->setWakeTransport($retiring->getWakeTransport());
		$tick->init(null);
		self::assertSame($retiring->getDaemonLockName(), $tick->getDaemonLockName());

		$events = [];
		foreach ([$retiring, $tick] as $i => $module) {
			foreach (['OnDaemonStart', 'OnDaemonEnd'] as $event) {
				$module->attachEventHandler($event, function ($sender, $param) use (&$events, $event, $i) {
					$events[] = [$i, $event, $param->getReason(), microtime(true)];
				});
			}
		}
		$loop = TAsync::getLoop();
		// The tick arrives after the daemon's lifetime passed and takes the lease over in a fiber of its own.
		$tickResult = null;
		$loop->delay(0.2, static function () use ($tick, &$tickResult) {
			TAsync::run(static function () use ($tick, &$tickResult) {
				$tickResult = $tick->processPendingTasks();
			});
		});
		$loop->delay(0.6, static fn () => $tick->stopDaemon());

		$start = microtime(true);
		self::assertEquals(1, $retiring->processPendingTasks(), 'the retiring daemon ran its task and returned on the handoff');
		$elapsed = microtime(true) - $start;
		self::assertGreaterThanOrEqual(0.15, $elapsed, 'the retired daemon served past its lifetime until the tick arrived');
		self::assertLessThan(3.0, $elapsed);

		// Let the successor run until its stop.
		$suspension = $loop->suspension();
		$timer = $loop->delay(2.0, static fn () => $suspension->resume());
		$tick->attachEventHandler('OnDaemonEnd', static function () use ($suspension, $loop, $timer) {
			$loop->cancel($timer);
			$loop->defer(static fn () => $suspension->resume());
		});
		if ($tickResult === null) {
			$suspension->suspend();
		}
		self::assertSame(0, $tickResult, 'the successor had nothing left to run');

		$names = array_map(static fn ($e) => $e[0] . ':' . $e[1], $events);
		self::assertSame(['0:OnDaemonStart', '0:OnDaemonEnd', '1:OnDaemonStart', '1:OnDaemonEnd'], $names, 'the successor starts after the retiring daemon ends');
		self::assertSame(TCronDaemonEventParameter::REASON_HANDOFF, $events[1][2]);
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $events[3][2]);
		self::assertFalse($tick->getDaemonMutex()->isAcquired($tick->getDaemonLockName()));
		self::assertTrue((new TMemoryMutex())->acquire($tick->getDaemonRetiringLockName()), 'the retiring lock is released');
		(new TMemoryMutex())->release($tick->getDaemonRetiringLockName());
		$tick->unlisten();
	}

	public function testRunDaemon_TickLeavesAHealthyDaemonAlone()
	{
		$now = $this->obj->fakeNow;
		$other = new TMemoryMutex();
		$this->obj->setDaemon(true);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setDaemonHandoffTimeout(5);
		$this->obj->init([['name' => 'daemonHealthy', 'schedule' => '@' . ($now - 10), 'task' => TTestCronModuleTask::class]]);
		self::assertTrue($other->acquire($this->obj->getDaemonLockName()));
		try {
			$start = microtime(true);
			self::assertEquals(0, $this->obj->processPendingTasks());
			self::assertLessThan(0.5, microtime(true) - $start, 'a tick exits at once when the daemon is not retiring');
			// A retiring daemon that does not release within the handoff timeout is left as well.
			self::assertTrue($other->acquire($this->obj->getDaemonRetiringLockName()));
			$this->obj->setDaemonHandoffTimeout(0.1);
			$start = microtime(true);
			self::assertEquals(0, $this->obj->processPendingTasks());
			self::assertGreaterThanOrEqual(0.09, microtime(true) - $start, 'the tick waited for the lease');
			self::assertTrue((new TMemoryMutex())->acquire($this->obj->getDaemonHandoffLockName()), 'the handoff lock is released after the timeout');
			(new TMemoryMutex())->release($this->obj->getDaemonHandoffLockName());
			self::assertEquals(0, $this->obj->getTask('daemonHealthy')->getProcessCount());
		} finally {
			$other->releaseAll();
		}
	}

	public function testRunDaemon_RetiresWhenTheConfigurationChanges()
	{
		$config = sys_get_temp_dir() . '/cron-config-' . bin2hex(random_bytes(4)) . '.xml';
		file_put_contents($config, '<application><module id="cron" /></application>');
		try {
			$this->obj->setDaemon(true);
			$this->obj->setDaemonLifetime(0);
			$this->obj->setDaemonIdle(0.05);
			$this->obj->setDaemonMutex(new TMemoryMutex());
			$this->obj->setDaemonConfigFiles($config);
			$this->obj->init(null);
			$loop = TAsync::getLoop();
			$retiringAt = [];
			$probe = function () use (&$retiringAt) {
				$mutex = new TMemoryMutex();
				$retiringAt[] = !$mutex->acquire($this->obj->getDaemonRetiringLockName());
				$mutex->releaseAll();
			};
			$loop->delay(0.1, $probe);                      // unchanged: not retiring
			$loop->delay(0.15, static function () use ($config) {
				file_put_contents($config, '<application><module id="cron" DaemonIdle="30" /></application>');
			});
			$loop->delay(0.4, $probe);                      // changed: retiring, still serving
			$loop->delay(0.45, fn () => $this->obj->stopDaemon());
			self::assertEquals(0, $this->obj->processPendingTasks());
			self::assertSame([false, true], $retiringAt, 'the daemon retires once the configuration file changes');
		} finally {
			@unlink($config);
		}
	}

	public function testRunDaemon_RetiresWhenTheDependencyChanges()
	{
		$file = sys_get_temp_dir() . '/cron-dependency-' . bin2hex(random_bytes(4));
		file_put_contents($file, 'v1');
		try {
			$this->obj->setDaemon(true);
			$this->obj->setDaemonLifetime(0);
			$this->obj->setDaemonIdle(0.05);
			$this->obj->setDaemonMutex(new TMemoryMutex());
			$this->obj->setDaemonWatchConfig(false);
			$this->obj->setDaemonDependency(new TFileCacheDependency($file));
			$this->obj->init(null);
			$loop = TAsync::getLoop();
			$retiringAt = [];
			$probe = function () use (&$retiringAt) {
				$mutex = new TMemoryMutex();
				$retiringAt[] = !$mutex->acquire($this->obj->getDaemonRetiringLockName());
				$mutex->releaseAll();
			};
			$loop->delay(0.1, $probe);                      // unchanged: not retiring
			$loop->delay(0.15, static function () use ($file) {
				touch($file, time() + 10);
			});
			$loop->delay(0.4, $probe);                      // changed: retiring, still serving
			$loop->delay(0.45, fn () => $this->obj->stopDaemon());
			self::assertEquals(0, $this->obj->processPendingTasks());
			self::assertSame([false, true], $retiringAt, 'the daemon retires once the dependency changes');
		} finally {
			@unlink($file);
		}
	}

	public function testDaemonDependency()
	{
		self::assertNull($this->obj->getDaemonDependency());
		$dependency = new TApplicationStateCacheDependency();
		$this->obj->setDaemonDependency($dependency);
		self::assertSame($dependency, $this->obj->getDaemonDependency());
		$this->obj->setDaemonDependency(null);
		self::assertNull($this->obj->getDaemonDependency());
	}

	public function testRunDaemon_RetireDaemonHandsOffAtTheNextTick()
	{
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(0.05);
		$this->obj->setDaemonWatchConfig(false);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init(null);
		$loop = TAsync::getLoop();
		$loop->delay(0.1, fn () => $this->obj->retireDaemon());
		$retiring = null;
		$tick = new TMemoryMutex();
		$loop->delay(0.25, function () use (&$retiring, $tick) {
			$retiring = !$tick->acquire($this->obj->getDaemonRetiringLockName());
			$tick->releaseAll();
			// a tick arrives and waits for the lease
			$tick->acquire($this->obj->getDaemonHandoffLockName());
		});
		$end = null;
		$this->obj->attachEventHandler('OnDaemonEnd', function ($sender, $param) use (&$end) {
			$end = $param;
		});
		$start = microtime(true);
		self::assertEquals(0, $this->obj->processPendingTasks());
		$tick->releaseAll();
		self::assertLessThan(2.0, microtime(true) - $start);
		self::assertTrue($retiring, 'retireDaemon() makes the daemon hold the retiring lock');
		self::assertSame(TCronDaemonEventParameter::REASON_HANDOFF, $end->getReason());
	}

	public function testSweepEvents_HandlersSeeAndChangeTheTasksAndTheResults()
	{
		$now = $this->obj->fakeNow;
		TTestCronRecordingTask::$events = [];
		$this->obj->init([
			['name' => 'sweepKept', 'schedule' => '@' . ($now - 10), 'task' => TTestCronModuleTask::class],
			['name' => 'sweepDropped', 'schedule' => '@' . ($now - 10), 'task' => TTestCronModuleTask::class],
			['name' => 'sweepFailing', 'schedule' => '@' . ($now - 10), 'task' => TTestCronRecordingTask::class],
		]);
		$this->obj->getTask('sweepKept')->testunit = $this;
		$this->obj->getTask('sweepDropped')->testunit = $this;
		$this->obj->getTask('sweepFailing')->throw = true;
		$fx = new TTestCronFXComponent();
		$seen = [];
		$this->obj->attachEventHandler('OnSweepStart', function ($sender, $param) use (&$seen) {
			self::assertInstanceOf(TCronSweepEventParameter::class, $param);
			$seen['start'] = $param;
			self::assertFalse($param->getDaemon());
			self::assertSame(['sweepKept', 'sweepDropped', 'sweepFailing'], array_keys($param->getTasks()));
			self::assertSame(3, $param->getCount());
			$tasks = $param->getTasks();
			unset($tasks['sweepDropped']);
			$param->setTasks($tasks);
		});
		$this->obj->attachEventHandler('OnSweepEnd', function ($sender, $param) use (&$seen) {
			$seen['end'] = $param;
		});
		self::assertEquals(2, $this->obj->processPendingTasks(), 'the handler dropped one task from the sweep');
		self::assertSame('fxcronsweepend', $fx->dyMethod, 'the on events are broadcast as fx events');
		self::assertSame($seen['end'], $fx->args[1]);
		self::assertEquals(1, $this->_countTasks);
		self::assertEquals(1, $this->obj->getTask('sweepKept')->getProcessCount());
		self::assertEquals(0, $this->obj->getTask('sweepDropped')->getProcessCount(), 'a dropped task stays pending');
		self::assertEquals(1, $this->obj->getTask('sweepFailing')->getProcessCount());
		self::assertSame($seen['start'], $seen['end']);
		self::assertSame(['sweepKept' => true, 'sweepFailing' => false], $seen['end']->getResults());
		self::assertSame(2, $seen['end']->getCount());
		self::assertSame(1, $seen['end']->getFailed());
		$fx->unlisten();
	}

	public function testDaemonRequests_RetireAndStopFromAnotherProcess()
	{
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(0.05);
		$this->obj->setDaemonWatchConfig(false);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init(null);
		self::assertNull($this->obj->getDaemonState(), 'no daemon yet');
		self::assertNull($this->obj->requestDaemonRetire(0.1));
		self::assertNull($this->obj->requestDaemonStop(0.1));

		$loop = TAsync::getLoop();
		$states = [];
		$acks = [];
		// Another process (its own mutex instance) asks the running daemon to retire, then to stop.
		$loop->delay(0.1, function () use (&$states, &$acks) {
			$states[] = $this->obj->getDaemonState();
			$other = new $this->baseClass();
			$other->setDaemonMutex(new TMemoryMutex());
			$other->setID($this->obj->getID());
			TAsync::run(function () use ($other, &$acks, &$states) {
				$acks['retire'] = $other->requestDaemonRetire(2);
				$states[] = $other->getDaemonState();
				$acks['stop'] = $other->requestDaemonStop(2);
				$states[] = $other->getDaemonState();
				$other->unlisten();
			});
		});
		$end = null;
		$this->obj->attachEventHandler('OnDaemonEnd', function ($sender, $param) use (&$end) {
			$end = $param;
		});
		$start = microtime(true);
		self::assertEquals(0, $this->obj->processPendingTasks());
		self::assertLessThan(3.0, microtime(true) - $start);
		// let the requester's fiber finish its last probe
		$suspension = $loop->suspension();
		$loop->delay(0.1, static fn () => $suspension->resume());
		$suspension->suspend();
		self::assertSame([TCronModule::DAEMON_STATE_RUNNING, TCronModule::DAEMON_STATE_RETIRING, null], $states);
		self::assertSame(['retire' => true, 'stop' => true], $acks);
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $end->getReason());
	}

	public function testFxRequests_RetireAndStopTheDaemonFromAComponent()
	{
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(0.05);
		$this->obj->setDaemonWatchConfig(false);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init(null);
		$loop = TAsync::getLoop();
		$raiser = new TComponent();
		$retiring = null;
		$loop->delay(0.1, static fn () => $raiser->raiseEvent('fxRetireCronDaemon', $raiser, null));
		$loop->delay(0.25, function () use (&$retiring) {
			$retiring = $this->obj->getDaemonState();
		});
		$loop->delay(0.3, static fn () => $raiser->raiseEvent('fxStopCronDaemon', $raiser, null));
		$end = null;
		$this->obj->attachEventHandler('OnDaemonEnd', function ($sender, $param) use (&$end) {
			$end = $param;
		});
		self::assertEquals(0, $this->obj->processPendingTasks());
		self::assertSame(TCronModule::DAEMON_STATE_RETIRING, $retiring, 'fxRetireCronDaemon retires the daemon');
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $end->getReason(), 'fxStopCronDaemon stops it');
	}

	public function testRunDaemon_AWakeSweepsAndStopDaemonEndsTheWait()
	{
		$now = $this->obj->fakeNow;
		$transport = $this->wakeFile(0.02);
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(10);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setWakeTransport($transport);
		$this->obj->init([['name' => 'daemonWoken', 'schedule' => '@' . ($now + 100), 'task' => TTestCronModuleTask::class]]);
		$this->obj->getTask('daemonWoken')->testunit = $this;
		$loop = TAsync::getLoop();
		// The task becomes due while the daemon waits; the wake makes it sweep instead of waiting out DaemonIdle.
		$loop->delay(0.05, function () use ($transport) {
			$this->obj->fakeNow += 200;
			$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
			$transport->signal(microtime(true));
		});
		$loop->delay(0.3, fn () => $this->obj->stopDaemon());
		$end = null;
		$this->obj->attachEventHandler('OnDaemonEnd', function ($sender, $param) use (&$end) {
			$end = $param;
		});
		$start = microtime(true);
		self::assertEquals(1, $this->obj->processPendingTasks());
		self::assertLessThan(3.0, microtime(true) - $start, 'stopDaemon ends the wait before DaemonIdle');
		self::assertSame(TCronDaemonEventParameter::REASON_STOP, $end->getReason());
		self::assertSame(1, $end->getTasks());
		self::assertEquals(1, $this->_countTasks);
		self::assertEquals(1, $this->obj->getTask('daemonWoken')->getProcessCount());
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
		$task = new TTestCronFXComponent();
		
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
		
		$arr = ['schedule' => '* * * * *', 'task' => TTestCronModuleTask::class];
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
		$this->obj->setAdditionalCronTasks('<module id="cron"><task schedule="* * * * *" task="Prado\Test\Unit\Util\Cron\TTestCronModuleTask" /></module>');
		$this->assertInstanceOf(\Prado\Xml\TXmlDocument::class, $this->obj->getAdditionalCronTasks());
		
		try {
			$this->obj->init(null);
			$this->obj->setAdditionalCronTasks(null);
			self::fail('failed to throw TInvalidOperationException when cannot set due to being initialized');
		} catch(TInvalidOperationException $e) {}
	}


	public function testRunTask_SkipsATriggerWhileTheRunLockIsHeld()
	{
		$mutex = new TMemoryMutex();
		$this->obj->setDaemonMutex($mutex);
		TTestCronRecordingTask::$events = [];
		$this->obj->init([['name' => 'locked', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$task = $this->obj->getTask('locked');
		$lock = $this->obj->getTaskLockName('locked');
		self::assertSame($this->obj->getDaemonLockName() . ':task:locked', $lock);
		$other = new TMemoryMutex();
		self::assertTrue($other->acquire($lock));
		try {
			self::assertFalse($this->obj->runTask($task), 'the trigger is skipped while another run holds the lock');
			self::assertSame([], TTestCronRecordingTask::$events);
			self::assertSame(0, $task->getProcessCount(), 'a skipped trigger is not a run');
			self::assertFalse($task->getIsPending(), 'a skipped trigger advances the task');

			$task->setOverlap(true);
			$task->setLastExecTime(null);
			self::assertTrue($this->obj->runTask($task), 'with Overlap the run takes no lock');
			self::assertSame(['locked start', 'locked end'], TTestCronRecordingTask::$events);
		} finally {
			$other->releaseAll();
		}
		$task->setOverlap(false);
		$task->setLastExecTime(null);
		self::assertTrue($this->obj->runTask($task));
		self::assertSame(2, $task->getProcessCount());
		self::assertFalse($mutex->isAcquired($lock), 'the run releases its lock');
	}

	public function testRunTask_RecordsTheResultAndDuration()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([
			['name' => 'resultGood', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
			['name' => 'resultBad', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
		]);
		$results = $this->obj->getTaskResults();
		self::assertNull($results['resultGood']['result'], 'a task never run has no result');
		self::assertNull($results['resultGood']['duration']);
		self::assertSame(0, $results['resultGood']['count']);

		$this->obj->getTask('resultBad')->throw = true;
		self::assertTrue($this->obj->runTask($this->obj->getTask('resultGood')));
		self::assertFalse($this->obj->runTask($this->obj->getTask('resultBad')));
		foreach ([false, true] as $fresh) {
			$results = $this->obj->getTaskResults($fresh);
			self::assertSame(TCronModule::RESULT_OK, $results['resultGood']['result']);
			self::assertIsFloat($results['resultGood']['duration']);
			self::assertGreaterThanOrEqual(0.0, $results['resultGood']['duration']);
			self::assertSame(1, $results['resultGood']['count']);
			self::assertSame($this->obj->fakeNow, $results['resultGood']['time']);
			self::assertSame(TCronModule::RESULT_ERROR, $results['resultBad']['result']);
		}
	}

	public function testExecuteTask_RunsWithoutAClaim()
	{
		TTestCronRecordingTask::$events = [];
		$this->obj->init([['name' => 'executed', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$task = $this->obj->getTask('executed');
		$last = $task->getLastExecTime();
		self::assertSame(TCronModule::RESULT_OK, $this->obj->executeTask($task));
		self::assertSame(['executed start', 'executed end'], TTestCronRecordingTask::$events);
		self::assertSame(0, $task->getProcessCount(), 'the parent process claimed the run');
		self::assertSame($last, $task->getLastExecTime());
		$task->throw = true;
		self::assertSame(TCronModule::RESULT_ERROR, $this->obj->executeTask($task));
	}

	public function testGetNextTriggers()
	{
		$this->obj->init([
			['name' => 'nextLast', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
			['name' => 'nextSecond', 'schedule' => '0 0 1 1 ? 2098', 'task' => TTestCronModuleTask::class],
			['name' => 'nextDue', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class],
		]);
		$due = $this->obj->getTask('nextDue')->getNextTriggerTime();
		$expected = ['nextDue' => (int) $due, 'nextSecond' => (int) mktime(0, 0, 0, 1, 1, 2098), 'nextLast' => (int) mktime(0, 0, 0, 1, 1, 2099)];
		self::assertSame($expected, $this->obj->getNextTriggers());
		self::assertSame(array_slice($expected, 0, 2, true), $this->obj->getNextTriggers(2, true));
		self::assertSame([], $this->obj->getNextTriggers(0));
	}

	public function testStatusProperties()
	{
		self::assertNull($this->obj->getEnableStatus(), 'cron/run turns the status on unless configured');
		$this->obj->setEnableStatus('false');
		self::assertFalse($this->obj->getEnableStatus());
		$this->obj->setEnableStatus(null);
		self::assertNull($this->obj->getEnableStatus());
		self::assertSame(Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . TCronModule::STATUS_DIRECTORY, $this->obj->getStatusPath());
		$this->obj->setStatusPath('/srv/status');
		self::assertSame('/srv/status', $this->obj->getStatusPath());
		$this->obj->setStatusPath('');
		self::assertSame(Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . TCronModule::STATUS_DIRECTORY, $this->obj->getStatusPath());
	}

	public function testStatus_PublishesTheRunsOfASweep()
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$this->obj->setID('cronStatusSweep');
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setStatusPath($dir);
		$this->obj->init([['name' => 'watched', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronStatusTask::class, 'username' => 'cron']]);
		try {
			self::assertEquals(1, $this->obj->processPendingTasks());
			self::assertSame([], $this->obj->getTask('watched')->seen, 'without EnableStatus no status is published');

			$this->obj->unlisten();
			$this->obj = new $this->baseClass();
			$this->obj->setID('cronStatusSweep');
			$this->obj->setDaemonMutex(new TMemoryMutex());
			$this->obj->setStatusPath($dir);
			$this->obj->setEnableStatus(true);
			$this->obj->init([['name' => 'watchedOn', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronStatusTask::class, 'username' => 'cron']]);
			self::assertEquals(1, $this->obj->processPendingTasks());
			$seen = $this->obj->getTask('watchedOn')->seen;
			self::assertCount(1, $seen);
			$status = $seen[0];
			self::assertSame(TCronModule::STATUS_ROLE_SWEEP, $status['role']);
			self::assertSame(getmypid(), $status['pid']);
			self::assertSame((string) gethostname(), $status['host']);
			self::assertTrue($status['alive']);
			self::assertNull($status['daemon']);
			self::assertSame(1, $status['concurrency']);
			self::assertCount(1, $status['running']);
			self::assertSame('watchedOn', $status['running'][0]['name']);
			self::assertSame('Serial', $status['running'][0]['mode']);
			self::assertSame(getmypid(), $status['running'][0]['pid']);
			self::assertSame([], $this->obj->getStatuses(), 'the sweep removes its status when it ends');
			self::assertSame([], glob($dir . DIRECTORY_SEPARATOR . '*'));
		} finally {
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}

	public function testStatus_ReadsTheProcessesAndRemovesTheEndedOnesOfThisHost()
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		mkdir($dir);
		$this->obj->setID('cronStatusRead');
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setStatusPath($dir);
		$now = $this->obj->fakeMicrotime;
		$write = function (string $file, array $status) use ($dir) {
			file_put_contents($dir . DIRECTORY_SEPARATOR . $file, json_encode($status + ['module' => 'cronStatusRead', 'started' => 1.0, 'running' => [], 'recent' => []]));
		};
		$write('ended.json', ['role' => 'sweep', 'host' => (string) gethostname(), 'pid' => 2147483000, 'updated' => $now]);
		$write('daemon.json', ['role' => 'daemon', 'host' => 'elsewhere.invalid', 'pid' => 10, 'updated' => $now - 30, 'daemon' => ['wake' => $now + 30]]);
		$write('stale.json', ['role' => 'sweep', 'host' => 'elsewhere.invalid', 'pid' => 11, 'updated' => $now - TCronModule::STATUS_STALE - 10]);
		$write('other.json', ['module' => 'otherCron', 'role' => 'sweep', 'host' => (string) gethostname(), 'pid' => 2147483001, 'updated' => $now]);
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'broken.json', '{');
		try {
			$statuses = $this->obj->getStatuses();
			self::assertSame(['daemon', 'sweep', 'sweep'], array_column($statuses, 'role'), 'the daemon is first');
			$alive = array_column($statuses, 'alive', 'pid');
			self::assertSame([10 => true, 2147483000 => false, 11 => false], $alive, 'another host is alive until its expected update is stale');
			self::assertSame($dir . DIRECTORY_SEPARATOR . 'daemon.json', $statuses[0]['file']);

			$this->obj->setEnableStatus(true);
			$this->obj->init([]);
			$this->obj->processPendingTasks();
			self::assertFileDoesNotExist($dir . DIRECTORY_SEPARATOR . 'ended.json', 'the status of an ended process of this host is removed');
			self::assertFileExists($dir . DIRECTORY_SEPARATOR . 'stale.json', 'the status of another host is kept');
			self::assertFileExists($dir . DIRECTORY_SEPARATOR . 'other.json', 'the status of another module is kept');
		} finally {
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			rmdir($dir);
		}
	}

	public function testRunDaemon_PublishesItsStatus()
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$this->obj->setID('cronStatusDaemon');
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(0.05);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setWakeTransport(new TCronWakeFile($dir . '-wake'));
		$this->obj->setStatusPath($dir);
		$this->obj->setEnableStatus(true);
		$this->obj->init([
			['name' => 'daemonStatus', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class],
			['name' => 'daemonStatusLater', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
		]);
		$this->obj->getTask('daemonStatus')->testunit = $this;
		$seen = [];
		$this->obj->attachEventHandler('OnDaemonIdle', function ($sender, $param) use (&$seen) {
			$seen[] = $this->obj->getStatuses();
			if (count($seen) >= 2) {
				$this->obj->stopDaemon();
			}
		});
		try {
			self::assertEquals(1, $this->obj->processPendingTasks());
			self::assertCount(2, $seen);
			$daemon = $seen[1][0];
			self::assertSame(TCronModule::STATUS_ROLE_DAEMON, $daemon['role']);
			self::assertSame(TCronModule::DAEMON_STATE_RUNNING, $daemon['daemon']['state']);
			self::assertSame(TCronWakeFile::class, $daemon['daemon']['transport']);
			self::assertSame($this->obj->getDaemonLockName(), $daemon['daemon']['lock']);
			self::assertSame(1, $daemon['daemon']['sweeps']);
			self::assertSame(1, $daemon['daemon']['tasks']);
			self::assertIsFloat($daemon['daemon']['wake']);
			self::assertSame(['daemonStatusLater'], array_keys($daemon['daemon']['next']));
			self::assertSame([], $this->obj->getStatuses(), 'the daemon removes its status when it ends');
		} finally {
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
			if (is_file($dir . '-wake')) {
				unlink($dir . '-wake');
			}
		}
	}


	public function testRunTask_StartingDeadlineSkipsALateTrigger()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([['name' => 'late', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'startingdeadline' => '60']]);
		$task = $this->obj->getTask('late');
		$skipped = [];
		$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$skipped) {
			$skipped[] = $param->getReason();
		});
		self::assertTrue($task->getIsPending());
		self::assertFalse($this->obj->runTask($task), 'a trigger 26 years late is past its 60-second deadline');
		self::assertSame([], TTestCronRecordingTask::$events);
		self::assertSame([TCronTaskEventParameter::REASON_DEADLINE], $skipped);
		self::assertSame(0, $task->getProcessCount());
		self::assertFalse($task->getIsPending(), 'the missed trigger is claimed');
		self::assertSame(TCronModule::RESULT_MISSED, $this->obj->getTaskResults()['late']['result']);

		self::assertTrue($this->obj->runTask($task, true), 'a manual run has no deadline');
		self::assertSame(['late start', 'late end'], TTestCronRecordingTask::$events);
	}

	public function testRunTask_RaisesTheTaskEvents()
	{
		$mutex = new TMemoryMutex();
		$this->obj->setDaemonMutex($mutex);
		$this->obj->init([['name' => 'evented', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$task = $this->obj->getTask('evented');
		$events = [];
		$cancel = false;
		foreach (['OnTaskStart', 'OnTaskEnd', 'OnTaskFailure', 'OnTaskSkipped'] as $event) {
			$this->obj->attachEventHandler($event, function ($sender, $param) use (&$events, &$cancel, $event) {
				self::assertInstanceOf(TCronTaskEventParameter::class, $param);
				$events[] = [$event, $param->getReason(), $param->getResult(), $param->getException() ? $param->getException()->getMessage() : null];
				if ($event === 'OnTaskStart' && $cancel) {
					$param->setCancel(true);
				}
			});
		}
		self::assertTrue($this->obj->runTask($task));
		self::assertSame([['OnTaskStart', null, null, null], ['OnTaskEnd', null, TCronModule::RESULT_OK, null]], $events);

		$events = [];
		$task->throw = true;
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task));
		self::assertSame(['OnTaskStart', 'OnTaskEnd', 'OnTaskFailure'], array_column($events, 0));
		self::assertSame([TCronModule::RESULT_ERROR, 'task failed: evented'], array_slice($events[2], 2));

		$events = [];
		$cancel = true;
		$count = $task->getProcessCount();
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task));
		self::assertSame([['OnTaskStart', null, null, null], ['OnTaskSkipped', TCronTaskEventParameter::REASON_CANCELLED, null, null]], $events);
		self::assertSame($count, $task->getProcessCount(), 'a cancelled run is not counted');
		self::assertFalse($task->getIsPending(), 'a cancelled run claims its trigger');

		$events = [];
		$cancel = false;
		$other = new TMemoryMutex();
		self::assertTrue($other->acquire($this->obj->getTaskLockName('evented')));
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task));
		$other->releaseAll();
		self::assertSame([['OnTaskStart', null, null, null], ['OnTaskSkipped', TCronTaskEventParameter::REASON_RUNNING, null, null]], $events, 'the claimer finds the run lock held and skips');
		self::assertFalse($task->getIsPending(), 'the skipped trigger is claimed');
	}

	public function testRunTask_SendsThePings()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$client = new TTestCronPingHttpClient();
		$this->obj->setHttpClient($client);
		self::assertSame($client, $this->obj->getHttpClient());
		$this->obj->init([['name' => 'pinged', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class,
			'pingbeforeurl' => 'https://hc.test/u/start', 'pingafterurl' => 'https://hc.test/u/after',
			'pingsuccessurl' => 'https://hc.test/u', 'pingfailureurl' => 'https://hc.test/u/fail']]);
		$task = $this->obj->getTask('pinged');
		self::assertTrue($this->obj->runTask($task));
		self::assertSame([['GET', 'https://hc.test/u/start'], ['GET', 'https://hc.test/u/after'], ['GET', 'https://hc.test/u']], $client->requests);

		$client->requests = [];
		$task->throw = true;
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task));
		self::assertSame(['https://hc.test/u/start', 'https://hc.test/u/after', 'https://hc.test/u/fail'], array_column($client->requests, 1));

		// a handler delivers a ping itself: the module does not send it
		$client->requests = [];
		$pings = [];
		$this->obj->attachEventHandler('OnTaskPing', function ($sender, $param) use (&$pings) {
			$pings[] = [$param->getPing(), $param->getUrl(), $param->getResult()];
			if ($param->getPing() === TCronTask::PING_FAILURE) {
				$param->setHandled(true);
			}
		});
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task));
		self::assertSame([[TCronTask::PING_BEFORE, 'https://hc.test/u/start', null], [TCronTask::PING_AFTER, 'https://hc.test/u/after', TCronModule::RESULT_ERROR], [TCronTask::PING_FAILURE, 'https://hc.test/u/fail', TCronModule::RESULT_ERROR]], $pings);
		self::assertSame(['https://hc.test/u/start', 'https://hc.test/u/after'], array_column($client->requests, 1), 'the handled ping is not sent');

		// a failed ping does not affect the run
		$client->fail = true;
		$task->throw = false;
		$task->setLastExecTime(null);
		self::assertTrue($this->obj->runTask($task));
		self::assertSame(TCronModule::RESULT_OK, $this->obj->getTaskResults()['pinged']['result']);
	}


	public function testTimeZone_IsTheDefaultOfTheTasks()
	{
		self::assertNull($this->obj->getTimeZone());
		$this->obj->setTimeZone('Asia/Tokyo');
		$this->obj->init([
			['name' => 'zoneDefault', 'schedule' => '0 9 * * *', 'task' => TTestCronModuleTask::class],
			['name' => 'zoneOwn', 'schedule' => '0 9 * * *', 'task' => TTestCronModuleTask::class, 'timezone' => 'UTC'],
		]);
		self::assertSame('Asia/Tokyo', $this->obj->getTask('zoneDefault')->getScheduleTimeZone());
		self::assertNull($this->obj->getTask('zoneDefault')->getTimeZone());
		self::assertSame('UTC', $this->obj->getTask('zoneOwn')->getScheduleTimeZone());
		$this->obj->setTimeZone('Europe/Paris');
		self::assertSame('Europe/Paris', $this->obj->getTask('zoneDefault')->getScheduleTimeZone(), 'a change reaches the loaded tasks');
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setTimeZone('Nowhere/Never');
	}

	public function testPauseAndResume()
	{
		$this->obj->setID('cronPause' . bin2hex(random_bytes(3)));
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([['name' => 'pausable', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$task = $this->obj->getTask('pausable');
		$skipped = [];
		$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$skipped) {
			$skipped[] = $param->getReason();
		});
		$file = PradoUnit::invoke($this->obj, 'getPausedFile');
		try {
			self::assertSame([], $this->obj->getPausedTasks());
			self::assertFalse($this->obj->pauseTask('noSuchTask'));
			self::assertTrue($this->obj->pauseTask('pausable'));
			self::assertTrue($this->obj->getIsTaskPaused('pausable'));
			self::assertSame(['pausable'], $this->obj->getPausedTasks());
			self::assertFalse($this->obj->runTask($task));
			self::assertSame([TCronTaskEventParameter::REASON_PAUSED], $skipped);
			self::assertSame([], TTestCronRecordingTask::$events);
			self::assertFalse($task->getIsPending(), 'the paused trigger passes');

			$task->setLastExecTime(null);
			self::assertTrue($this->obj->runTaskNow($task), 'a manual run runs a paused task');
			self::assertSame(['pausable start', 'pausable end'], TTestCronRecordingTask::$events);

			self::assertTrue($this->obj->resumeTask('pausable'));
			self::assertFalse($this->obj->getIsTaskPaused('pausable'));
			$task->setLastExecTime(null);
			self::assertTrue($this->obj->runTask($task));
		} finally {
			if (is_file($file)) {
				unlink($file);
			}
		}
	}

	public function testRunTask_SkipsOutsideTheWindowOrOnACondition()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$hour = (int) gmdate('G', $this->obj->fakeNow);
		$this->obj->init([['name' => 'windowed', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'timezone' => 'UTC',
			'between' => sprintf('%02d:00-%02d:59', ($hour + 2) % 24, ($hour + 3) % 24)]]);
		$task = $this->obj->getTask('windowed');
		$skipped = [];
		$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$skipped) {
			$skipped[] = $param->getReason();
		});
		self::assertFalse($this->obj->runTask($task));
		self::assertSame([TCronTaskEventParameter::REASON_WINDOW], $skipped);

		$task->setBetween(null);
		$task->setLastExecTime(null);
		$this->obj->attachBehavior('declines', new TTestCronDecliningBehavior());
		self::assertFalse($this->obj->runTask($task));
		self::assertSame([TCronTaskEventParameter::REASON_WINDOW, TCronTaskEventParameter::REASON_CONDITION], $skipped);
		$this->obj->detachBehavior('declines');
		$task->setLastExecTime(null);
		self::assertTrue($this->obj->runTask($task));
	}

	public function testRunTask_RetriesAFailedRun()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([['name' => 'retried', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'retries' => 2, 'retrydelay' => 0.01]]);
		$task = $this->obj->getTask('retried');
		$task->throw = true;
		$retries = [];
		$end = null;
		$this->obj->attachEventHandler('OnTaskRetry', function ($sender, $param) use (&$retries) {
			$retries[] = [$param->getAttempt(), $param->getResult()];
		});
		$this->obj->attachEventHandler('OnTaskEnd', function ($sender, $param) use (&$end) {
			$end = $param->getAttempt();
		});
		self::assertFalse($this->obj->runTask($task));
		self::assertSame([[1, TCronModule::RESULT_ERROR], [2, TCronModule::RESULT_ERROR]], $retries);
		self::assertSame(3, $end, 'the run ends after the last retry');
		self::assertSame(3, count(array_filter(TTestCronRecordingTask::$events, fn ($e) => $e === 'retried start')));
		self::assertSame(1, $task->getProcessCount(), 'the retries are one run');
	}


	public function testRunTask_ReplaceCancelsTheRunInThisProcess()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'replaced', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronWaitingTask::class, 'overlap' => 'Replace', 'timeout' => 0.3]]);
		$task = $this->obj->getTask('replaced');
		$results = [];
		$this->obj->attachEventHandler('OnTaskEnd', function ($sender, $param) use (&$results) {
			$results[] = $param->getResult();
		});
		$first = TAsync::run([$this->obj, 'runTask'], $task);
		TAsync::sleep(0.05);
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task), 'the replacing run waits on its own token until its timeout');
		self::assertFalse($first->await());
		self::assertSame([TCronModule::RESULT_REPLACED, TCronModule::RESULT_ERROR], $results, 'the first run is replaced, the second times out');
	}

	public function testRunTask_WarnDurationMarksAnOverdueRunOnce()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([
			['name' => 'slowWaits', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'warnduration' => 0.02],
			['name' => 'fast', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'warnduration' => 5],
		]);
		$this->obj->getTask('slowWaits')->pause = 0.1;
		$overdue = [];
		$ended = [];
		$this->obj->attachEventHandler('OnTaskOverdue', function ($sender, $param) use (&$overdue, &$ended) {
			$overdue[] = [$param->getTask()->getName(), $param->getDuration(), isset($ended[$param->getTask()->getName()])];
		});
		$this->obj->attachEventHandler('OnTaskEnd', function ($sender, $param) use (&$ended) {
			$ended[$param->getTask()->getName()] = true;
		});
		self::assertTrue($this->obj->runTask($this->obj->getTask('slowWaits')));
		self::assertTrue($this->obj->runTask($this->obj->getTask('fast')));
		self::assertCount(1, $overdue, 'one warning, for the slow run');
		self::assertSame('slowWaits', $overdue[0][0]);
		self::assertGreaterThanOrEqual(0.02, $overdue[0][1]);
		self::assertFalse($overdue[0][2], 'a run waiting on the loop is overdue before it ends');
	}


	public function testRunTask_AfterEndCountsTheNextTriggerFromTheEnd()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'delayed', 'schedule' => 'every 1 hour after end from @' . ($this->obj->fakeNow - 10), 'task' => TTestCronModuleTask::class]]);
		$task = $this->obj->getTask('delayed');
		$task->setLastExecTime(null);
		self::assertTrue($task->getIsPending());
		$start = $this->obj->fakeNow;
		$this->obj->attachBehavior('advance', new TTestCronAdvancingBehavior());
		try {
			self::assertTrue($this->obj->runTask($task));
		} finally {
			$this->obj->detachBehavior('advance');
		}
		self::assertSame($start + 30, $task->getLastExecTime(), 'the last run time is the end of the run');
		self::assertSame($start + 30 + 3600, $task->getNextTriggerTime());
		if ($this->obj instanceof \Prado\Util\Cron\TDbCronManager) {
			self::assertEquals($start + 30 + 3600, $this->obj->getTask('delayed', true, false)['nextexectime'], 'the row holds the next trigger from the end');
		}
		$this->obj->fakeNow = $start;
		$this->obj->fakeMicrotime = (float) $start;
	}


	public function testRunTasks_RunsByPriority()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([
			['name' => 'prioA', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
			['name' => 'prioB', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'priority' => 1],
			['name' => 'prioC', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'priority' => 20],
			['name' => 'prioD', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class],
		]);
		self::assertEquals(4, $this->obj->processPendingTasks());
		self::assertSame(['prioB', 'prioA', 'prioD', 'prioC'], array_values(array_unique(array_map(fn ($e) => explode(' ', $e)[0], TTestCronRecordingTask::$events))), 'lower first; equal priorities keep their order');
	}

	public function testRunTask_RunsTheChain()
	{
		$this->obj->setID('cronChain' . bin2hex(random_bytes(3)));
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([
			['name' => 'chainHead', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'then' => 'chainNext, chainMissing', 'thenonfailure' => 'chainAlert'],
			['name' => 'chainNext', 'schedule' => '@never', 'task' => TTestCronRecordingTask::class, 'then' => 'chainHead, chainLast'],
			['name' => 'chainLast', 'schedule' => '@never', 'task' => TTestCronRecordingTask::class],
			['name' => 'chainAlert', 'schedule' => '@never', 'task' => TTestCronRecordingTask::class],
		]);
		$chains = [];
		$this->obj->attachEventHandler('OnTaskStart', function ($sender, $param) use (&$chains) {
			$chains[$param->getTask()->getName()] = $param->getChain();
		});
		$head = $this->obj->getTask('chainHead');
		$debug = [];
		$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$debug) {
			$debug[] = [$param->getTask()->getName(), $param->getReason()];
		});
		self::assertFalse($this->obj->getTask('chainNext')->getIsPending(), '@never is never due');
		self::assertTrue($this->obj->runTask($head));
		self::assertSame([], $debug, 'no chained run is skipped');
		self::assertSame(['chainHead start', 'chainHead end', 'chainNext start', 'chainNext end', 'chainLast start', 'chainLast end'], TTestCronRecordingTask::$events, 'a task already in the chain is not run again, an unknown one is skipped');
		self::assertSame(['chainHead', 'chainNext'], $chains['chainLast']);
		self::assertSame(1, $this->obj->getTask('chainNext')->getProcessCount(), 'a chained run is claimed');

		TTestCronRecordingTask::$events = [];
		$head->throw = true;
		$head->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($head));
		self::assertSame(['chainHead start', 'chainAlert start', 'chainAlert end'], TTestCronRecordingTask::$events, 'a failure runs ThenOnFailure');

		$file = PradoUnit::invoke($this->obj, 'getPausedFile');
		try {
			TTestCronRecordingTask::$events = [];
			$skipped = [];
			$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$skipped) {
				$skipped[] = [$param->getTask()->getName(), $param->getReason()];
			});
			$this->obj->pauseTask('chainAlert');
			$head->setLastExecTime(null);
			self::assertFalse($this->obj->runTask($head));
			self::assertSame([['chainAlert', TCronTaskEventParameter::REASON_PAUSED]], $skipped, 'a paused chained task is skipped');
		} finally {
			if (is_file($file)) {
				unlink($file);
			}
		}
	}

	public function testMonitorReportAndMetrics()
	{
		$this->obj->setID('cronMonitor');
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([
			['name' => 'monitored', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'description' => 'Watched "here"', 'priority' => 3],
			['name' => 'idle', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronRecordingTask::class],
		]);
		self::assertTrue($this->obj->runTask($this->obj->getTask('monitored')));
		$report = $this->obj->getMonitorReport();
		self::assertSame('cronMonitor', $report['module']);
		self::assertNull($report['daemon']);
		self::assertSame([], $report['processes']);
		self::assertSame([], $report['ended']);
		self::assertSame([], $report['runs']);
		self::assertFalse($report['daemonMode']);
		self::assertSame(1, $report['concurrency']);
		self::assertSame(['idle'], array_keys($this->obj->getMonitorReport(['idle' => $this->obj->getTask('idle')])['tasks']), 'the report has the given tasks');
		self::assertSame(['idle', 'monitored'], array_keys($report['tasks']), 'the tasks are by name');
		$task = $report['tasks']['monitored'];
		self::assertSame('Serial', $task['mode']);
		self::assertSame(3.0, $task['priority']);
		self::assertSame('Watched "here"', $task['description']);
		self::assertFalse($task['paused']);
		self::assertSame([], $task['running']);
		self::assertSame(1, $task['count']);
		self::assertSame(TCronModule::RESULT_OK, $task['result']);
		self::assertSame((int) mktime(0, 0, 0, 1, 1, 2099), $report['tasks']['idle']['next']);

		$metrics = $this->obj->getMonitorMetrics();
		self::assertStringContainsString("# TYPE prado_cron_daemon_up gauge\nprado_cron_daemon_up{module=\"cronMonitor\"} 0\n", $metrics);
		self::assertStringContainsString('prado_cron_task_runs_total{module="cronMonitor",task="monitored"} 1', $metrics);
		self::assertStringContainsString('prado_cron_task_last_success{module="cronMonitor",task="monitored"} 1', $metrics);
		self::assertStringNotContainsString('prado_cron_task_last_success{module="cronMonitor",task="idle"}', $metrics, 'a task never run has no result');
		self::assertStringEndsWith("\n", $metrics);
	}


	public function testProcessPendingTasks_ACrontabTickRunsTheMinuteAtItsSeconds()
	{
		$minute = (int) (floor(time() / 60) * 60);
		$this->obj->fakeNow = $minute + 1;
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
		$this->obj->setInCronShell(true);
		$this->obj->setConcurrency(2);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([
			['name' => 'tickNowA', 'schedule' => '@' . $minute, 'task' => TTestCronRecordingTask::class, 'mode' => 'Parallel'],
			['name' => 'tickNowB', 'schedule' => '@' . $minute, 'task' => TTestCronRecordingTask::class, 'mode' => 'Parallel'],
			['name' => 'tickLater', 'schedule' => '@' . ($minute + 30), 'task' => TTestCronRecordingTask::class],
			['name' => 'tickNextMinute', 'schedule' => '@' . ($minute + 70), 'task' => TTestCronRecordingTask::class],
		]);
		foreach (['tickNowA', 'tickNowB'] as $name) {
			$this->obj->getTask($name)->pause = 0.02;
		}
		self::assertEquals(3, $this->obj->processPendingTasks(), 'the tasks due within the minute are pending');
		self::assertEquals([$minute + 30], $this->obj->waitedFor, 'the tick waits only for the later trigger');
		$order = array_flip(TTestCronRecordingTask::$events);
		self::assertLessThan($order['tickNowA end'], $order['tickNowB start'], 'the tasks due together run together, by their Mode');
		self::assertGreaterThan($order['tickNowB end'], $order['tickLater start']);
		self::assertArrayNotHasKey('tickNextMinute start', $order, 'a trigger of the next minute is the next tick\'s');
	}


	public function testProcessPendingTasks_ACrontabTickRepeatsATaskWithinTheMinute()
	{
		$minute = (int) (floor(time() / 60) * 60);
		$this->obj->fakeNow = $minute + 1;
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
		$this->obj->setInCronShell(true);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		TTestCronRecordingTask::$events = [];
		$this->obj->init([['name' => 'tickRepeat', 'schedule' => '*:*/20 * * * *', 'task' => TTestCronRecordingTask::class]]);
		$task = $this->obj->getTask('tickRepeat');
		$task->setLastExecTime($minute);
		if ($this->obj instanceof \Prado\Util\Cron\TDbCronManager) {
			self::assertTrue($this->obj->updateTask($task), 'the row holds the next trigger the pending query reads');
		}
		self::assertEquals($minute + 20, $task->getNextTriggerTime());
		self::assertEquals(1, $this->obj->processPendingTasks(), 'one task is pending in the minute');
		self::assertEquals([$minute + 20, $minute + 40], $this->obj->waitedFor, 'the tick waits for each trigger of the minute');
		self::assertSame(['tickRepeat start', 'tickRepeat end', 'tickRepeat start', 'tickRepeat end'], TTestCronRecordingTask::$events, 'the task runs at :20 and :40');
		self::assertSame(2, $task->getProcessCount());
		self::assertEquals($minute + 60, $task->getNextTriggerTime(), 'the next trigger is the next tick\'s');
	}

	public function testPermissions_DenyTheReadsAndRuns()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([
			['name' => 'permA', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'username' => 'ann'],
			['name' => 'permB', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'username' => 'ben'],
		]);
		$permissions = $this->obj->getPermissions(null);
		self::assertContainsOnlyInstancesOf(\Prado\Security\Permissions\TPermissionEvent::class, $permissions);
		$names = array_map(fn ($permission) => $permission->getName(), $permissions);
		self::assertContains(TCronModule::PERM_CRON_RUN_TASK, $names);
		self::assertContains(TCronModule::PERM_CRON_READ_TASKS, $names);

		self::assertSame(['permB', 'permA'], array_keys($this->obj->getTasks(null, 0, 0, false)), 'descending by name');
		self::assertSame(2, $this->obj->getTasksCount());
		self::assertSame(['permA'], array_keys($this->obj->getUserTasks('ann')));
		self::assertSame(['permB', 'permA'], array_keys($this->obj->getUserTasks('%', 'perm_', 0, 0, false)));
		self::assertSame(1, $this->obj->getUserTasksCount('ben'));
		self::assertSame(['permB', 'permA'], array_keys($this->obj->getTasksByType(TTestCronRecordingTask::class, null, 0, 0, false)));
		self::assertTrue($this->obj->taskExists('permA'));
		self::assertFalse($this->obj->taskExists('permZ'));

		$this->obj->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetTasks', 'dyGetUserTasks', 'dyGetPendingTasks']));
		self::assertNull($this->obj->getTasks());
		self::assertNull($this->obj->getTasksCount());
		self::assertNull($this->obj->getUserTasks('ann'));
		self::assertNull($this->obj->getUserTasksCount('ann'));
		self::assertNull($this->obj->getTask('permA'));
		self::assertNull($this->obj->getPendingTasks());
		self::assertSame(0, $this->obj->processPendingTasks(), 'a denied sweep runs nothing');
		$this->obj->detachBehavior('denies');
		$task = $this->obj->getTask('permA');

		$skipped = [];
		$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$skipped) {
			$skipped[] = $param->getReason();
		});
		$this->obj->attachBehavior('denies', new TTestCronDenyingBehavior(['dyRunTask']));
		self::assertSame(TCronModule::RESULT_DENIED, $this->obj->executeTask($task));
		self::assertFalse($this->obj->runTask($task));
		self::assertSame([TCronTaskEventParameter::REASON_DENIED], $skipped);
		self::assertSame(TCronModule::RESULT_DENIED, $this->obj->getTaskResults(true)['permA']['result']);
		self::assertSame([], TTestCronRecordingTask::$events = array_values(array_filter(TTestCronRecordingTask::$events, fn ($e) => str_starts_with($e, 'perm'))), 'a denied task does not run');
		$this->obj->detachBehavior('denies');
	}

	public function testInit_RejectsADuplicateTaskName()
	{
		$this->expectException(TConfigurationException::class);
		$this->obj->init([
			['name' => 'twice', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class],
			['name' => 'twice', 'schedule' => '0 0 1 1 ? 2001', 'task' => TTestCronModuleTask::class],
		]);
	}

	public function testInit_RequestCronRunsAtTheEndOfARequest()
	{
		$app = Prado::getApplication();
		$this->obj->fakeSapi = 'apache2handler';
		$this->obj->fakeRand = 0;
		$this->obj->setEnableRequestCron(true);
		$this->obj->init(null);
		$handler = [$this->obj, 'processPendingTasks'];
		try {
			self::assertTrue($app->getEventHandlers('OnEndRequest')->contains($handler));
		} finally {
			$app->detachEventHandler('OnEndRequest', $handler);
		}
		$other = new $this->baseClass();
		$other->fakeSapi = 'apache2handler';
		$other->fakeRand = mt_getrandmax();
		$other->setEnableRequestCron(true);
		$other->setRequestCronProbability(50);
		$other->init(null);
		self::assertFalse($app->getEventHandlers('OnEndRequest')->contains([$other, 'processPendingTasks']), 'a request past the probability does not run cron');
		$other->unlisten();
	}

	public function testShell_RegistersTheOptionAndTheAction()
	{
		$shell = $this->createMock(\Prado\Shell\TShellApplication::class);
		$shell->expects(self::once())->method('addShellActionClass');
		$module = new class () extends TTestCronModule {
			public $shell;
			public function getApplication()
			{
				return $this->shell;
			}
		};
		$module->shell = $shell;
		$shell->expects(self::once())->method('registerOption')->with('cron', [$module, 'setInCronShell']);
		$shell->expects(self::once())->method('registerOptionAlias')->with('c', 'cron');
		$shell->method('getModulesByType')->willReturn([]);
		$module->init(null);
		$module->registerShellAction(null, null);
		$module->attachBehavior('denies', new TTestCronDenyingBehavior(['dyRegisterShellAction']));
		$module->registerShellAction(null, null);
	}

	public function testRunTasks_StopsAtTheMinuteAndSkipsRemovedTasks()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'inSweep', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$removed = new TTestCronRecordingTask();
		$removed->setName('notInModule');
		$removed->setSchedule('0 0 1 1 ? 2000');
		$task = $this->obj->getTask('inSweep');
		$minute = (int) floor($this->obj->fakeNow / 60);
		self::assertSame([], PradoUnit::invoke($this->obj, 'runTasks', [$task], $minute - 1), 'a sweep of a past minute starts nothing');
		self::assertSame(['inSweep' => true], PradoUnit::invoke($this->obj, 'runTasks', [$removed, $task], $minute), 'a task not in the module is skipped');
		self::assertSame(0, $removed->getProcessCount());
	}

	public function testRunAsTaskUser_RestoresThePriorFiberUser()
	{
		$users = new TUserManager();
		$prior = new TUser($users);
		$prior->setName('prior');
		$this->obj->setUserManager($users);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'asUser', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$result = TAsync::run(function () use ($prior) {
			TFiberContext::set(TApplication::USER_CONTEXT_KEY, $prior);
			$this->obj->runTask($this->obj->getTask('asUser'));
			return TFiberContext::get(TApplication::USER_CONTEXT_KEY);
		})->await();
		self::assertSame($prior, $result, 'the fiber user before the run is restored');
	}

	public function testRunTask_ABlockingRunPastWarnDurationIsOverdueWhenItEnds()
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$this->obj->setStatusPath($dir);
		$this->obj->setEnableStatus(true);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'blocks', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'warnduration' => 0.02]]);
		$this->obj->getTask('blocks')->block = 0.06;
		$overdue = [];
		$this->obj->attachEventHandler('OnTaskOverdue', function ($sender, $param) use (&$overdue) {
			$overdue[] = $param->getDuration();
		});
		$marked = null;
		$this->obj->attachEventHandler('OnTaskOverdue', function ($sender, $param) use (&$marked) {
			foreach ($sender->getStatuses() as $status) {
				foreach ($status['running'] as $run) {
					$marked = $run['overdue'];
				}
			}
		});
		try {
			PradoUnit::invoke($this->obj, 'beginStatus', TCronModule::STATUS_ROLE_MANUAL);
			self::assertTrue($this->obj->runTask($this->obj->getTask('blocks')));
			self::assertCount(1, $overdue, 'a run that never waits on the loop is overdue when it ends');
			self::assertGreaterThanOrEqual(0.02, $overdue[0]);
			self::assertTrue($marked, 'the status of the run is marked overdue');
		} finally {
			PradoUnit::invoke($this->obj, 'endStatus');
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}

	public function testRunTask_ReplaceOfARunInAnotherProcessSkips()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'replaces', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'overlap' => 'Replace']]);
		$other = new TMemoryMutex();
		self::assertTrue($other->acquire($this->obj->getTaskLockName('replaces')));
		try {
			self::assertFalse($this->obj->runTask($this->obj->getTask('replaces')), 'a run of another process cannot be replaced from here');
			self::assertSame(0, $this->obj->getTask('replaces')->getProcessCount(), 'the skipped trigger is uncounted');
		} finally {
			$other->releaseAll();
		}
	}

	public function testPauseTask_UnknownAndUnwritable()
	{
		$this->obj->init([['name' => 'pausable', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		self::assertFalse($this->obj->resumeTask('noSuchTask'));
		self::assertFalse($this->obj->pauseTask('noSuchTask'));
		if (!($this->obj instanceof \Prado\Util\Cron\TDbCronManager)) {
			$module = new class () extends TTestCronModule {
				protected function getPausedFile(): string
				{
					return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-missing-' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR . 'paused.json';
				}
			};
			$module->init([['name' => 'pausable', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
			self::assertTrue($module->pauseTask('pausable'), 'the task exists');
			self::assertFalse($module->getIsTaskPaused('pausable'), 'the pause could not be stored');
			$module->unlisten();
		}
	}

	public function testRunTask_APingAnsweredWithAnErrorStatusDoesNotFailTheRun()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$client = new TTestCronPingHttpClient();
		$client->status = 503;
		$this->obj->setHttpClient($client);
		$this->obj->init([['name' => 'pingedDown', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class, 'pingafterurl' => 'https://hc.test/down']]);
		self::assertTrue($this->obj->runTask($this->obj->getTask('pingedDown')));
		self::assertSame([['GET', 'https://hc.test/down']], $client->requests);
	}

	public function testWaitSeams_SleepAndWaitForWake()
	{
		$module = new TCronModule();
		// Windows times sleeps to its clock tick (about 15.6 ms): the margins are wide.
		$start = microtime(true);
		self::assertTrue(PradoUnit::invoke($module, 'time_sleep_until', microtime(true) + 0.05));
		self::assertGreaterThanOrEqual(0.03, microtime(true) - $start);
		self::assertTrue(PradoUnit::invoke($module, 'time_sleep_until', microtime(true) - 1), 'a past time returns at once');

		$transport = $this->wakeFile(0.01);
		$module->setWakeTransport($transport);
		TAsync::getLoop()->delay(0.03, static fn () => $transport->signal(microtime(true)));
		self::assertTrue(PradoUnit::invoke($module, 'waitForWake', microtime(true) + 2), 'a signal wakes the wait');
		self::assertFalse(PradoUnit::invoke($module, 'waitForWake', microtime(true) + 0.03), 'no signal waits until the time');
		$transport->close();

		$module = new class () extends TCronModule {
			public function getWakeTransport(): ?\Prado\Util\Cron\Wake\ICronWakeTransport
			{
				return null;
			}
		};
		$start = microtime(true);
		self::assertFalse(PradoUnit::invoke($module, 'waitForWake', microtime(true) + 0.05), 'without a transport the wait sleeps');
		self::assertGreaterThanOrEqual(0.03, microtime(true) - $start);
	}

	public function testWakeTransport_InvalidAndWithoutApplication()
	{
		try {
			$this->obj->setWakeTransport(new TComponent());
			self::fail('a component that is no transport is accepted');
		} catch (TConfigurationException $e) {
		}
		$module = new class () extends TCronModule {
			public function getApplication()
			{
				return null;
			}
		};
		self::assertNull($module->getWakeTransport(), 'without an application there is no runtime path for the socket');
	}

	public function testRunDaemon_AWakeDuringASweepSweepsAgain()
	{
		$now = $this->obj->fakeNow;
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(10);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setWakeTransport($this->wakeFile(0.02));
		$this->obj->init([['name' => 'wakes', 'schedule' => '@' . ($now - 10), 'task' => TTestCronRecordingTask::class]]);
		$sweeps = 0;
		$this->obj->attachEventHandler('OnSweepStart', function () use (&$sweeps) {
			$sweeps++;
		});
		$this->obj->attachEventHandler('OnTaskStart', fn () => PradoUnit::invoke($this->obj, 'wakeDaemon'));
		$this->obj->attachEventHandler('OnDaemonIdle', fn () => $this->obj->stopDaemon());
		self::assertEquals(1, $this->obj->processPendingTasks());
		self::assertSame(2, $sweeps, 'the wake during the first sweep sweeps again before idling');
	}

	public function testRunDaemon_AnErrorEndsTheDaemon()
	{
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setWakeTransport($this->wakeFile(0.02));
		$this->obj->init(null);
		$this->obj->attachEventHandler('OnSweepStart', function () {
			throw new \RuntimeException('sweep failed');
		});
		$end = null;
		$this->obj->attachEventHandler('OnDaemonEnd', function ($sender, $param) use (&$end) {
			$end = $param->getReason();
		});
		try {
			$this->obj->processPendingTasks();
			self::fail('the error of a sweep is lost');
		} catch (\RuntimeException $e) {
			self::assertSame('sweep failed', $e->getMessage());
		}
		self::assertSame(TCronDaemonEventParameter::REASON_ERROR, $end);
		self::assertFalse($this->obj->getDaemonMutex()->isAcquired($this->obj->getDaemonLockName()), 'the lease is released');
	}

	public function testRunDaemon_ASignalStopsTheDaemon()
	{
		if (!extension_loaded('pcntl') || !function_exists('posix_kill')) {
			self::markTestSkipped('signals need pcntl and posix');
		}
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonIdle(10);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setWakeTransport($this->wakeFile(0.02));
		$this->obj->init(null);
		$end = null;
		$this->obj->attachEventHandler('OnDaemonEnd', function ($sender, $param) use (&$end) {
			$end = $param->getReason();
		});
		TAsync::getLoop()->delay(0.1, static fn () => posix_kill(getmypid(), SIGTERM));
		$start = microtime(true);
		self::assertEquals(0, $this->obj->processPendingTasks());
		self::assertLessThan(5.0, microtime(true) - $start, 'the signal ends the wait');
		self::assertSame(TCronDaemonEventParameter::REASON_SIGNAL, $end);
	}

	public function testRunDaemon_ATickExitsWhenAnotherWaitsForTheHandoff()
	{
		$this->obj->setDaemon(true);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init(null);
		$other = new TMemoryMutex();
		foreach ([$this->obj->getDaemonLockName(), $this->obj->getDaemonRetiringLockName(), $this->obj->getDaemonHandoffLockName()] as $lock) {
			self::assertTrue($other->acquire($lock));
		}
		try {
			$start = microtime(true);
			self::assertSame(0, $this->obj->processPendingTasks());
			self::assertLessThan(1.0, microtime(true) - $start, 'the tick does not wait for the lease');
		} finally {
			$other->releaseAll();
		}
	}

	public function testRequestDaemon_AnotherRequestHoldsTheLock()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init(null);
		$other = new TMemoryMutex();
		self::assertTrue($other->acquire($this->obj->getDaemonLockName()));
		self::assertTrue($other->acquire($this->obj->getDaemonRetireLockName()));
		try {
			self::assertFalse($this->obj->requestDaemonRetire(0.1), 'another request waits for the daemon');
		} finally {
			$other->releaseAll();
		}
	}

	public function testStatus_WriteFailures()
	{
		PradoUnit::invoke($this->obj, 'writeStatus');
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-file-' . bin2hex(random_bytes(4));
		file_put_contents($file, 'a file, not a directory');
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-ro-' . bin2hex(random_bytes(4));
		mkdir($dir);
		chmod($dir, 0o500);
		try {
			$this->obj->setStatusPath($file . DIRECTORY_SEPARATOR . 'status');
			$this->obj->setEnableStatus(true);
			PradoUnit::invoke($this->obj, 'beginStatus', TCronModule::STATUS_ROLE_MANUAL);
			self::assertSame([], $this->obj->getStatuses(), 'a status directory that cannot be made has no status');
			PradoUnit::invoke($this->obj, 'endStatus');

			// Windows ignores the mode of a directory: probe it with a write.
			if (@file_put_contents($dir . DIRECTORY_SEPARATOR . 'probe', '') !== false) {
				self::markTestSkipped('the process can write to a read-only directory');
			}
			$this->obj->setStatusPath($dir);
			PradoUnit::invoke($this->obj, 'beginStatus', TCronModule::STATUS_ROLE_MANUAL);
			self::assertSame([], glob($dir . DIRECTORY_SEPARATOR . '*'), 'a status that cannot be written leaves no file');
			PradoUnit::invoke($this->obj, 'endStatus');
		} finally {
			chmod($dir, 0o700);
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $left) {
				unlink($left);
			}
			rmdir($dir);
			unlink($file);
		}
	}

	public function testSweepEventParameter_Daemon()
	{
		$param = new TCronSweepEventParameter([], false);
		self::assertFalse($param->getDaemon());
		self::assertTrue($param->setDaemon('true')->getDaemon());
	}

	public function testStatus_ARenameFailureRemovesTheTemporaryFile()
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$this->obj->setStatusPath($dir);
		$this->obj->setEnableStatus(true);
		$target = PradoUnit::invoke($this->obj, 'getStatusFile');
		mkdir($target, 0o777, true);
		try {
			PradoUnit::invoke($this->obj, 'beginStatus', TCronModule::STATUS_ROLE_MANUAL);
			self::assertSame([basename($target)], array_map('basename', glob($dir . DIRECTORY_SEPARATOR . '*')), 'the temporary file is removed');
			PradoUnit::invoke($this->obj, 'endStatus');
		} finally {
			rmdir($target);
			rmdir($dir);
		}
	}

	public function testWatchDaemonSignals_ALoopWithoutSignals()
	{
		$loop = $this->createMock(\Prado\IO\Async\IEventLoop::class);
		$loop->method('onSignal')->willThrowException(new \Prado\Exceptions\TNotSupportedException('eventloop_signals_unsupported'));
		self::assertSame([], PradoUnit::invoke($this->obj, 'watchDaemonSignals', $loop));
	}

	public function testRunDaemon_ADeniedSweepRunsNothing()
	{
		$this->obj->setDaemon(true);
		$this->obj->setDaemonLifetime(0);
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->setWakeTransport($this->wakeFile(0.02));
		$this->obj->init([['name' => 'deniedSweep', 'schedule' => '@' . ($this->obj->fakeNow - 10), 'task' => TTestCronModuleTask::class]]);
		$this->obj->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetPendingTasks']));
		$this->obj->attachEventHandler('OnDaemonIdle', fn () => $this->obj->stopDaemon());
		self::assertSame(0, $this->obj->processPendingTasks());
		$this->obj->detachBehavior('denies');
		self::assertSame(0, $this->obj->getTask('deniedSweep')->getProcessCount());
	}

	public function testTags_SelectPauseAndResumeTogether()
	{
		$this->obj->init([
			['name' => 'tagA', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'tags' => 'nightly, reports'],
			['name' => 'tagB', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'tags' => 'nightly'],
			['name' => 'tagC', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
		]);
		try {
			self::assertSame(['tagA', 'tagB'], array_keys($this->obj->getTasksByTag('#Nightly')));
			self::assertSame(['tagA'], array_keys($this->obj->getTasksByTag('reports')));
			self::assertSame([], $this->obj->getTasksByTag('none'));
			self::assertSame(['tagA', 'tagB'], $this->obj->pauseTag('nightly'));
			self::assertSame(['tagA', 'tagB'], $this->obj->getPausedTasks());
			self::assertSame(['nightly', 'reports'], $this->obj->getMonitorReport()['tasks']['tagA']['tags']);
			self::assertSame([], $this->obj->getMonitorReport()['tasks']['tagC']['tags']);
			self::assertSame(['tagA'], $this->obj->resumeTag('reports'));
			self::assertSame(['tagB'], $this->obj->getPausedTasks());
			self::assertSame(['tagA', 'tagB'], $this->obj->resumeTag('nightly'));
			self::assertSame([], $this->obj->getPausedTasks());
			$this->obj->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetTasks']));
			self::assertNull($this->obj->getTasksByTag('nightly'));
			self::assertSame([], $this->obj->pauseTag('nightly'), 'a denied read pauses nothing');
			$this->obj->detachBehavior('denies');
		} finally {
			foreach (['tagA', 'tagB'] as $name) {
				$this->obj->resumeTask($name);
			}
			$file = $this->obj instanceof \Prado\Util\Cron\TDbCronManager ? null : PradoUnit::invoke($this->obj, 'getPausedFile');
			if ($file !== null && is_file($file)) {
				unlink($file);
			}
		}
	}

	public function testRunStats_CountTheFinishedRuns()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'counted', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronRecordingTask::class]]);
		$task = $this->obj->getTask('counted');
		self::assertSame(['ok' => 0, 'failed' => 0, 'successRate' => null, 'avgDuration' => null, 'maxDuration' => null, 'totalDuration' => 0.0],
			$this->obj->getMonitorReport()['tasks']['counted']['stats'], 'no finished run');
		foreach ([false, false, true] as $throw) {
			$task->throw = $throw;
			$task->setLastExecTime(null);
			$this->obj->runTask($task);
		}
		$this->obj->attachBehavior('denies', new TTestCronDenyingBehavior(['dyRunTask']));
		$task->setLastExecTime(null);
		self::assertFalse($this->obj->runTask($task));
		$this->obj->detachBehavior('denies');
		$stats = $this->obj->getMonitorReport()['tasks']['counted']['stats'];
		self::assertSame(2, $stats['ok']);
		self::assertSame(1, $stats['failed'], 'a denied run is no finished run');
		self::assertSame(0.6667, $stats['successRate']);
		self::assertGreaterThanOrEqual(0.0, $stats['avgDuration']);
		self::assertGreaterThanOrEqual($stats['avgDuration'], $stats['maxDuration']);
		self::assertEqualsWithDelta($stats['avgDuration'] * 3, $stats['totalDuration'], 0.00001);

		$metrics = $this->obj->getMonitorMetrics();
		self::assertStringContainsString('prado_cron_task_finished_total{module="' . $this->obj->getID() . '",task="counted",outcome="ok"} 2', $metrics);
		self::assertStringContainsString('prado_cron_task_finished_total{module="' . $this->obj->getID() . '",task="counted",outcome="failed"} 1', $metrics);
		self::assertStringContainsString('prado_cron_task_run_seconds_count{module="' . $this->obj->getID() . '",task="counted"} 3', $metrics);
		self::assertStringContainsString('prado_cron_task_run_seconds_max{', $metrics);
	}

	public function testResultOutcome()
	{
		self::assertSame('ok', TCronModule::getResultOutcome(TCronModule::RESULT_OK));
		self::assertSame('failed', TCronModule::getResultOutcome(TCronModule::RESULT_ERROR));
		self::assertSame('failed', TCronModule::getResultOutcome(TCronModule::RESULT_TIMEOUT));
		foreach ([TCronModule::RESULT_DENIED, TCronModule::RESULT_MISSED, TCronModule::RESULT_REPLACED] as $result) {
			self::assertNull(TCronModule::getResultOutcome($result));
		}
	}

	public function testCatchUp_RunsTheLatestMissedTriggersOldestFirst()
	{
		$hour = (int) (floor($this->obj->fakeNow / 3600) * 3600);
		$this->obj->fakeNow = $hour + 600;
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([
			['name' => 'catchy', 'schedule' => '0 * * * *', 'task' => TTestCronRecordingTask::class, 'catchup' => 3],
			['name' => 'coalesced', 'schedule' => '0 * * * *', 'task' => TTestCronRecordingTask::class],
		]);
		$triggers = [];
		$this->obj->attachEventHandler('OnTaskStart', function ($sender, $param) use (&$triggers) {
			$triggers[] = [$param->getTask()->getName(), $param->getTrigger()];
		});
		foreach (['catchy', 'coalesced'] as $name) {
			$task = $this->obj->getTask($name);
			$task->setLastExecTime($hour - 5 * 3600);
			if ($this->obj instanceof \Prado\Util\Cron\TDbCronManager) {
				$this->obj->updateTask($task);
			}
		}
		for ($sweep = 0; $sweep < 5; $sweep++) {
			$this->obj->processPendingTasks();
		}
		self::assertSame([['catchy', $hour - 2 * 3600], ['coalesced', $hour - 4 * 3600], ['catchy', $hour - 3600], ['catchy', $hour]], $triggers,
			'the latest three missed triggers run one per sweep, oldest first; CatchUp 1 runs once for all');
		$task = $this->obj->getTask('catchy');
		self::assertSame(3, $task->getProcessCount());
		self::assertSame($this->obj->fakeNow, $task->getLastExecTime(), 'the last caught-up run is on time');
		self::assertFalse($task->getIsPending($this->obj->fakeNow));
		self::assertSame($hour, $task->getRunTrigger());
	}

	public function testCatchUp_KeepsTheTriggersWithinTheStartingDeadline()
	{
		$hour = (int) (floor($this->obj->fakeNow / 3600) * 3600);
		$this->obj->fakeNow = $hour + 600;
		$this->obj->fakeMicrotime = (float) $this->obj->fakeNow;
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([
			['name' => 'withinDeadline', 'schedule' => '0 * * * *', 'task' => TTestCronRecordingTask::class, 'catchup' => 5, 'startingdeadline' => 4000],
			['name' => 'pastDeadline', 'schedule' => '0 * * * *', 'task' => TTestCronRecordingTask::class, 'catchup' => 5, 'startingdeadline' => 300],
		]);
		$skipped = [];
		$this->obj->attachEventHandler('OnTaskSkipped', function ($sender, $param) use (&$skipped) {
			$skipped[] = [$param->getTask()->getName(), $param->getReason()];
		});
		foreach (['withinDeadline', 'pastDeadline'] as $name) {
			$task = $this->obj->getTask($name);
			$task->setLastExecTime($hour - 3 * 3600);
			if ($this->obj instanceof \Prado\Util\Cron\TDbCronManager) {
				$this->obj->updateTask($task);
			}
		}
		$this->obj->processPendingTasks();
		$this->obj->processPendingTasks();
		self::assertSame([$hour], $this->obj->getTask('withinDeadline')->triggers, 'only the trigger within the deadline runs, on time');
		self::assertSame([], $this->obj->getTask('pastDeadline')->triggers);
		self::assertSame([['pastDeadline', TCronTaskEventParameter::REASON_DEADLINE]], $skipped);
	}

	public function testCatchUp_ATaskNeverRunOrRunManuallyDoesNotCatchUp()
	{
		$this->obj->setDaemonMutex(new TMemoryMutex());
		$this->obj->init([['name' => 'fresh', 'schedule' => '0 * * * *', 'task' => TTestCronRecordingTask::class, 'catchup' => 10]]);
		$task = $this->obj->getTask('fresh');
		self::assertNull(PradoUnit::invoke($this->obj, 'getCatchUpTriggers', $task), 'a task never run');
		self::assertTrue($this->obj->runTask($task, true));
		self::assertSame([null], $task->triggers, 'a manual run serves no trigger');
		$task->setCatchUp(1);
		$task->setLastExecTime($this->obj->fakeNow - 86400);
		self::assertNull(PradoUnit::invoke($this->obj, 'getCatchUpTriggers', $task), 'a CatchUp of 1');
	}
}
