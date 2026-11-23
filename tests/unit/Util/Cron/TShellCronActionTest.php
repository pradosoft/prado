<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TExitException;
use Prado\IO\Async\TAsync;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Prado;
use Prado\TComponent;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TShellCronAction;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTaskMode;
use Prado\Util\Cron\TShellCronLogBehavior;
use Prado\Util\Mutex\TMemoryMutex;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\TUser;
use Prado\Security\TUserManager;

/**
 * Throws from its run.
 */
class TTestCronShellFailingTask extends TCronTask
{
	public function execute($cron)
	{
		throw new \RuntimeException('shell task failed');
	}
}

/**
 * Shows cron/top as a terminal or not, and records its waits.
 */
class TTestShellCronTopAction extends TShellCronAction
{
	public bool $interactive = false;
	public array $sleeps = [];

	protected function isInteractive(): bool
	{
		return $this->interactive;
	}

	protected function sleep(float $seconds): void
	{
		$this->sleeps[] = $seconds;
	}

	protected function flushView(): void
	{
	}
}

/**
 * Answers the daemon retire and stop requests with {@see $answer}.
 */
class TTestShellCronRequestModule extends TCronModule
{
	public ?bool $answer = null;

	public function requestDaemonRetire(?float $timeout = null): ?bool
	{
		return $this->answer;
	}

	public function requestDaemonStop(?float $timeout = null): ?bool
	{
		return $this->answer;
	}
}


class TShellCronActionTest extends \PHPUnit\Framework\TestCase
{
	protected $obj, $writer;
	
	protected function getTestClass()
	{
		return TShellCronAction::class;
	}
	
	protected function getTestCronClass()
	{
		return TCronModule::class;
	}

	protected function setUp(): void
	{
		$this->obj = Prado::createComponent($this->getTestClass());
		$this->writer = new TShellWriter(new TTextWriter());
		$this->obj->setWriter($this->writer);
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TShellCronAction::class, $this->obj);
	}

	public function testCronModule()
	{	
		$this->obj->setCronModule($v = new TComponent());
		$this->assertEquals($v, $this->obj->getCronModule());
	}
	
	public function testActionDaemonRetireStop()
	{
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->setID('shellDaemon' . substr(md5(static::class), 0, 6));
		$cron->setDaemonMutex(new TMemoryMutex());
		$cron->init(null);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionDaemon(['cron/daemon']));
			self::assertStringContainsString('No cron daemon holds the lease', $this->writer->flush());
			self::assertTrue($this->obj->actionRetire(['cron/retire']));
			self::assertStringContainsString('No cron daemon is running', $this->writer->flush());
			self::assertTrue($this->obj->actionStop(['cron/stop', '0.1']));
			self::assertStringContainsString('No cron daemon is running', $this->writer->flush());

			// another process holds the lease
			$other = new TMemoryMutex();
			self::assertTrue($other->acquire($cron->getDaemonLockName()));
			self::assertTrue($this->obj->actionDaemon(['cron/daemon']));
			self::assertStringContainsString('is running', $this->writer->flush());
			self::assertTrue($this->obj->actionStop(['cron/stop', '0.1']));
			self::assertStringContainsString('did not stop', $this->writer->flush(), 'the holder never acknowledges');
			self::assertTrue($other->acquire($cron->getDaemonRetiringLockName()));
			self::assertTrue($this->obj->actionDaemon(['cron/daemon']));
			self::assertStringContainsString('is retiring', $this->writer->flush());
			self::assertTrue($this->obj->actionRetire(['cron/retire', '0.1']));
			self::assertStringContainsString('is retiring', $this->writer->flush(), 'a retiring daemon acknowledges at once');
			$other->releaseAll();
		} finally {
			$cron->unlisten();
		}
	}

	public function testPerformAction()
	{
		
		//  no TCronModule in application, failed 
		self::assertTrue($this->obj->actionRun(['cron']));
		self::assertEquals(1, preg_match("/TCronModule/", $text = $this->writer->flush()));
		
		$jobs = [['name' => 'testTaskA', 'schedule' => '1 2 3 4 ? 2020', 'task' => TTestCronModuleTask::class, 'username' => 'admin123', 'moduleid' => 'cronmodule99', 'propertya' => 'value1']];
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$statusDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$cron->setStatusPath($statusDir);
		$cron->init($jobs);
		$this->obj->setCronModule($cron);
		self::assertNull($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR));
		{	//cron pending tasks
			$task = $cron->getTask('testTaskA');
			self::assertEquals(0, $task->getProcessCount());
			self::assertNull($cron->getEnableStatus());
			self::assertTrue($this->obj->actionRun(['cron/run']));
			$text = $this->writer->flush();
			self::assertEquals(1, $task->getProcessCount());
			self::assertTrue($cron->getEnableStatus(), 'cron/run publishes its status');
			self::assertSame([], glob($statusDir . DIRECTORY_SEPARATOR . '*'), 'the sweep removed its status file');
			rmdir($statusDir);
				
			$ntask = $cron->getTask('testTaskA');
			self::assertTrue($ntask === $task);
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
			self::assertEquals(1, preg_match("/Mode/", $text));
			self::assertEquals(1, preg_match("/Result/", $text));
			self::assertEquals(1, preg_match("/State/", $text));
			self::assertEquals(1, preg_match("/Serial/", $text));
			self::assertEquals(1, preg_match("/ok/", $text));
			
			self::assertEquals(1, preg_match("/testTaskA/", $text));
			self::assertEquals(1, preg_match("/1 2 3 4 \?/", $text));
			self::assertEquals(1, preg_match("/TTestCronModuleTask/", $text));
			self::assertEquals(1, preg_match("/admin123/", $text));
			self::assertEquals(0, preg_match("/(1969|1970)/", $text));
		}
		{	//cron index	
			// Hide the modules other tests leave listening to the global event, such
			// as the application's session, so the catalog starts empty.
			$globalEvents = PradoUnit::getStaticProp(TComponent::class, '_ue');
			$isolatedEvents = $globalEvents;
			unset($isolatedEvents['fxgetcrontaskinfos']);
			PradoUnit::setStaticProp(TComponent::class, '_ue', $isolatedEvents);
			try {
				self::assertTrue($this->obj->actionIndex(['cron/index']));
				$text = $this->writer->flush();
				self::assertEquals(1, preg_match("/No registered application tasks/", $text));
			} finally {
				PradoUnit::setStaticProp(TComponent::class, '_ue', $globalEvents);
			}
			
			//Register & call again
			$fxtest = new TTestCronFXComponent();
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
		
		$cron->unlisten();
	}
	

	public function testActionExec()
	{
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->setID('shellExec' . substr(md5(static::class), 0, 6));
		$cron->setDaemonMutex(new TMemoryMutex());
		$cron->init([
			['name' => 'execGood', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
			['name' => 'execBad', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronShellFailingTask::class],
		]);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionExec(['cron/exec', 'execGood']));
			self::assertSame(0, $cron->getTask('execGood')->getProcessCount(), 'the parent process claims the run');
			foreach ([['execBad', TCronModule::EXIT_ERROR], ['noSuchTask', TCronModule::EXIT_NO_TASK]] as [$name, $code]) {
				try {
					$this->obj->actionExec(['cron/exec', $name]);
					self::fail('cron/exec ' . $name . ' did not exit');
				} catch (TExitException $e) {
					self::assertSame($code, $e->getExitCode());
				}
			}
			self::assertStringContainsString("Cron task 'noSuchTask' is not found", $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionTop()
	{
		$action = new TTestShellCronTopAction();
		$action->setWriter($this->writer);
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$id = 'shellTop' . substr(md5(static::class), 0, 6);
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$cron->setID($id);
		$cron->setDaemonMutex(new TMemoryMutex());
		$cron->setStatusPath($dir);
		$cron->init([
			['name' => 'topDone', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class],
			['name' => 'topChild', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'mode' => 'Process'],
		]);
		$action->setCronModule($cron);
		try {
			self::assertTrue($action->actionTop(['cron/top']));
			$text = $this->writer->flush();
			self::assertStringContainsString('Cron ' . $id, $text);
			self::assertStringContainsString('No cron process is running', $text);
			self::assertStringContainsString('Next', $text);
			self::assertStringContainsString('topChild', $text);
			self::assertStringNotContainsString('Last results', $text, 'no task has run');
			self::assertStringNotContainsString("\033[H", $text, 'outside a terminal the view is shown once');
			self::assertSame([], $action->sleeps);

			self::assertTrue($cron->runTask($cron->getTask('topDone')));
			mkdir($dir);
			$now = microtime(true);
			file_put_contents($dir . DIRECTORY_SEPARATOR . 'daemon.json', json_encode([
				'module' => $id, 'role' => 'daemon', 'pid' => getmypid(), 'host' => (string) gethostname(), 'started' => $now - 75, 'updated' => $now,
				'concurrency' => 1, 'recent' => [],
				'daemon' => ['state' => 'running', 'transport' => 'Prado\\Util\\Cron\\Wake\\TCronWakeSocket', 'watching' => true, 'sweeps' => 4, 'tasks' => 2, 'wake' => $now + 30],
				'running' => [['name' => 'topChild', 'mode' => 'Process', 'user' => 'cron', 'started' => $now - 3, 'deadline' => null, 'pid' => 4321]],
			]));
			$action->interactive = true;
			self::assertTrue($action->actionTop(['cron/top', '0.5', '2']));
			$text = $this->writer->flush();
			self::assertSame(2, substr_count($text, "\033[H\033[2J"), 'a terminal redraws each view');
			self::assertSame([0.5], $action->sleeps);
			self::assertStringContainsString('Daemon', $text);
			self::assertStringContainsString('up 1m 15s', $text);
			self::assertStringContainsString('TCronWakeSocket watching', $text);
			self::assertStringContainsString('4 sweeps, 2 tasks', $text);
			self::assertStringContainsString('Running', $text);
			self::assertStringContainsString('4321', $text);
			self::assertStringContainsString('1 child process(es)', $text);
			self::assertStringContainsString('Last results', $text);
			self::assertStringContainsString('topDone', $text);

			self::assertTrue($action->actionTasks(['cron/tasks']));
			self::assertStringContainsString('pid 4321', $this->writer->flush(), 'cron/tasks shows the running child');
		} finally {
			$cron->unlisten();
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}


	public function testActionNow()
	{
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->setID('shellNow' . substr(md5(static::class), 0, 6));
		$statusDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$cron->setStatusPath($statusDir);
		$mutex = new TMemoryMutex();
		$cron->setDaemonMutex($mutex);
		$cron->init([
			['name' => 'nowGood', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
			['name' => 'nowBad', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronShellFailingTask::class],
		]);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionNow(['cron/now', 'nowGood']));
			self::assertStringContainsString("Cron task 'nowGood' ran: ok", $this->writer->flush());
			self::assertSame(1, $cron->getTask('nowGood')->getProcessCount(), 'a manual run is claimed and counted');
			self::assertTrue($this->obj->actionNow(['cron/now', 'nowBad']));
			self::assertStringContainsString("Cron task 'nowBad' ran: error", $this->writer->flush());

			$other = new TMemoryMutex();
			self::assertTrue($other->acquire($cron->getTaskLockName('nowGood')));
			self::assertTrue($this->obj->actionNow(['cron/now', 'nowGood']));
			$other->releaseAll();
			self::assertStringContainsString("Cron task 'nowGood' did not run: running", $this->writer->flush());

			self::assertTrue($this->obj->actionNow(['cron/now', 'noSuchTask']));
			self::assertStringContainsString("Cron task 'noSuchTask' is not found", $this->writer->flush());
			self::assertTrue($cron->getEnableStatus(), 'cron/now publishes its status');
			self::assertSame([], glob($statusDir . DIRECTORY_SEPARATOR . '*'), 'the manual run removed its status file');
		} finally {
			$cron->unlisten();
			if (is_dir($statusDir)) {
				rmdir($statusDir);
			}
		}
	}

	public function testActionNext()
	{
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->setID('shellNext' . substr(md5(static::class), 0, 6));
		$cron->init([
			['name' => 'nextYearly', 'schedule' => '0 0 1 1 ? 2098', 'task' => TTestCronModuleTask::class, 'description' => 'Runs in 2098'],
			['name' => 'nextTokyo', 'schedule' => '0 9 * * *', 'task' => TTestCronModuleTask::class, 'timezone' => 'Asia/Tokyo', 'mode' => 'Parallel'],
		]);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionNext(['cron/next']));
			$text = $this->writer->flush();
			self::assertStringContainsString('Trigger', $text);
			self::assertStringContainsString('JST', $text, 'a time is shown in the task time zone');
			self::assertStringContainsString('Parallel', $text);
			self::assertLessThan(strpos($text, 'nextYearly'), strpos($text, 'nextTokyo'), 'the soonest trigger is first');
			self::assertSame(1, substr_count($text, ' 09:00:00 JST'));

			self::assertTrue($this->obj->actionNext(['cron/next', 'nextTok%', '3']));
			$text = $this->writer->flush();
			self::assertSame(3, substr_count($text, ' 09:00:00 JST'), 'a count shows that many triggers');
			self::assertStringNotContainsString('nextYearly', $text);

			self::assertTrue($this->obj->actionNext(['cron/next', 'nextYearly']));
			self::assertSame(1, substr_count($this->writer->flush(), '2098-01-01'), 'a one-year schedule has one trigger left');

			self::assertTrue($this->obj->actionTasks(['cron/tasks']));
			self::assertStringContainsString('Runs in 2098', $this->writer->flush(), 'cron/tasks shows the description');
		} finally {
			$cron->unlisten();
		}
	}


	public function testActionPauseResume()
	{
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$cron->setID('shellPause' . bin2hex(random_bytes(3)));
		$cron->init([['name' => 'pauseMe', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		$this->obj->setCronModule($cron);
		$file = \Prado\Test\Unit\PradoUnit::invoke($cron, 'getPausedFile');
		try {
			self::assertTrue($this->obj->actionPause(['cron/pause', 'pauseMe']));
			self::assertStringContainsString("Cron task 'pauseMe' is paused", $this->writer->flush());
			self::assertTrue($cron->getIsTaskPaused('pauseMe'));
			self::assertTrue($this->obj->actionTasks(['cron/tasks']));
			self::assertStringContainsString('paused', $this->writer->flush());
			self::assertTrue($this->obj->actionResume(['cron/resume', 'pauseMe']));
			self::assertStringContainsString("Cron task 'pauseMe' is resumed", $this->writer->flush());
			self::assertFalse($cron->getIsTaskPaused('pauseMe'));
			self::assertTrue($this->obj->actionPause(['cron/pause', 'noSuchTask']));
			self::assertStringContainsString("Cron task 'noSuchTask' is not found", $this->writer->flush());
		} finally {
			$cron->unlisten();
			if (is_file($file)) {
				unlink($file);
			}
		}
	}


	public function testActionReport()
	{
		$cronClass = $this->getTestCronClass();
		$cron = new $cronClass();
		$id = 'shellReport' . bin2hex(random_bytes(3));
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$cron->setID($id);
		$cron->setStatusPath($dir);
		$cron->init([['name' => 'reported', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'description' => 'In the report']]);
		$this->obj->setCronModule($cron);
		try {
			mkdir($dir);
			$now = microtime(true);
			file_put_contents($dir . DIRECTORY_SEPARATOR . 'sweep.json', json_encode([
				'module' => $id, 'role' => 'sweep', 'pid' => getmypid(), 'host' => (string) gethostname(), 'started' => $now - 5, 'updated' => $now,
				'concurrency' => 1, 'recent' => [], 'daemon' => null,
				'running' => [['name' => 'reported', 'mode' => 'Process', 'user' => 'cron', 'started' => $now - 2, 'deadline' => null, 'pid' => 4321]],
			]));
			self::assertTrue($this->obj->actionReport(['cron/report']));
			$report = json_decode($this->writer->flush(), true);
			self::assertIsArray($report);
			self::assertSame($id, $report['module']);
			self::assertNull($report['daemon']);
			self::assertCount(1, $report['processes']);
			self::assertSame('In the report', $report['tasks']['reported']['description']);
			self::assertSame([4321], $report['tasks']['reported']['running']);
			self::assertSame(['reported', 'Process', 4321, getmypid()], [$report['runs'][0]['name'], $report['runs'][0]['mode'], $report['runs'][0]['pid'], $report['runs'][0]['owner']]);

			self::assertTrue($this->obj->actionReport(['cron/report', 'Prometheus']));
			$metrics = $this->writer->flush();
			self::assertStringContainsString('prado_cron_task_running{module="' . $id . '",task="reported"} 1', $metrics);
			self::assertStringContainsString('prado_cron_processes{module="' . $id . '"} 1', $metrics);

			self::assertTrue($this->obj->actionReport(['cron/report', 'xml']));
			self::assertStringContainsString("The report format 'xml' is not json or prometheus", $this->writer->flush());
		} finally {
			$cron->unlisten();
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}

	public function testWithoutCronModule()
	{
		foreach (['actionTasks', 'actionIndex', 'actionNext', 'actionTop', 'actionReport', 'actionDaemon', 'actionRetire', 'actionStop', 'actionPause', 'actionResume', 'actionNow'] as $action) {
			self::assertTrue($this->obj->$action(['cron/x', 'name']), $action);
			self::assertStringContainsString('TCronModule is not found', $this->writer->flush(), $action);
		}
	}

	public function testFindsTheCronModuleOfTheApplication()
	{
		$app = Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_modules']);
		$cron = new TCronModule();
		try {
			$app->setModule('shellFound' . bin2hex(random_bytes(3)), $cron);
			$action = new TShellCronAction();
			$action->setWriter($this->writer);
			self::assertSame($cron, $action->getCronModule());
			self::assertNotNull($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR), 'the shell attaches its log behavior');
		} finally {
			PradoUnit::restore($app, $snapshot);
			$cron->unlisten();
		}
	}

	public function testRestrictedUser()
	{
		$app = Prado::getApplication();
		$prior = $app->getUser();
		$user = new TUser(new TUserManager());
		$user->setName('alice');
		if ($user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR)) {
			$user->detachBehavior(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR);
		}
		$user->attachBehavior(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR, $permissions = new TTestCronUserPermissions());
		$cron = new TCronModule();
		$cron->setID('shellRestricted' . bin2hex(random_bytes(3)));
		$cron->init([
			['name' => 'aliceTask', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'username' => 'alice'],
			['name' => 'bobTask', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'username' => 'bob'],
		]);
		$app->setUser($user);
		try {
			$action = new TShellCronAction();
			$action->setWriter($this->writer);
			$action->setCronModule($cron);
			$methods = PradoUnit::getProp($action, 'methods');
			foreach (['run', 'exec', 'now', 'pause', 'resume', 'retire', 'stop'] as $method) {
				self::assertNotContains($method, $methods, 'a user without the run permission has no ' . $method);
			}
			self::assertContains('tasks', $methods);
			self::assertCount(count($methods), PradoUnit::getProp($action, 'parameters'));
			self::assertCount(count($methods), PradoUnit::getProp($action, 'optional'));
			self::assertCount(count($methods) + 1, PradoUnit::getProp($action, 'description'));
			PradoUnit::invoke($action, 'removeShellMethod', 'run');
			self::assertSame($methods, PradoUnit::getProp($action, 'methods'), 'removing an absent method changes nothing');

			self::assertTrue($action->actionTasks(['cron/tasks']));
			$text = $this->writer->flush();
			self::assertStringContainsString('filtered by @alice', $text);
			self::assertStringContainsString('aliceTask', $text);
			self::assertStringNotContainsString('bobTask', $text, 'a user without the read permission sees only their tasks');
			self::assertTrue($action->actionReport(['cron/report']));
			self::assertSame(['aliceTask'], array_keys(json_decode($this->writer->flush(), true)['tasks']));

			$cron->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetUserTasks']));
			foreach (['actionTasks', 'actionNext', 'actionTop', 'actionReport'] as $method) {
				self::assertTrue($action->$method(['cron/x']), $method);
			}
			self::assertStringNotContainsString('aliceTask', $this->writer->flush(), 'a denied read shows no task');
			$cron->detachBehavior('denies');

			$permissions->allowed = [TCronModule::PERM_CRON_RUN_PENDING, TCronModule::PERM_CRON_READ_TASKS];
			$action = new TShellCronAction();
			self::assertContains('run', PradoUnit::getProp($action, 'methods'));
		} finally {
			PradoUnit::setProp($app, '_user', $prior);
			$cron->unlisten();
		}
	}

	public function testActionTasksFilters()
	{
		$cron = new TCronModule();
		$cron->setID('shellFilters' . bin2hex(random_bytes(3)));
		$now = time();
		$cron->init([
			['name' => 'filterSoon', 'schedule' => '@' . ($now + 3600), 'task' => TTestCronModuleTask::class, 'username' => 'carol'],
			['name' => 'filterLater', 'schedule' => '@' . ($now + 5 * 86400), 'task' => TTestCronModuleTask::class],
			['name' => 'filterDue', 'schedule' => '* * * * *', 'task' => TTestCronModuleTask::class],
			['name' => 'filterNoRun', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
		]);
		$cron->getTask('filterDue')->setLastExecTime($now - 3 * 86400);
		$cron->getTask('filterDue')->setProcessCount(3);
		$cron->getTask('filterNoRun')->setLastExecTime($now - 60);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionTasks(['cron/tasks', 'filter%', '@carol']));
			$text = $this->writer->flush();
			self::assertStringContainsString("filtered by task name 'filter%' and @carol", $text);
			self::assertStringContainsString('filterSoon', $text);
			self::assertStringNotContainsString('filterLater', $text);
			self::assertStringContainsString('@(' . date('H:i:s', $now + 3600) . ')', $text, 'a one-off within a day shows its time');

			self::assertTrue($this->obj->actionTasks(['cron/tasks', '@carol']));
			self::assertStringContainsString('filtered by @carol', $this->writer->flush());

			self::assertTrue($this->obj->actionTasks(['cron/tasks', 'filter%']));
			$text = $this->writer->flush();
			self::assertStringContainsString("filtered by task name 'filter%'", $text);
			self::assertStringContainsString('@(' . date('Y-m-d H:i:s', $now + 5 * 86400) . ')', $text, 'a one-off past a day shows its date');
			self::assertStringContainsString(date('Y-m-d H:i:s', $now - 3 * 86400), $text, 'a run past a day shows its date');
			self::assertStringContainsString('*', $text, 'a pending task is marked');
			self::assertStringContainsString('- (' . date('H:i:s', $now - 60) . ')', $text, 'a time without runs is in parentheses');

			self::assertTrue($this->obj->actionTasks(['cron/tasks', 'nothing%']));
			self::assertStringContainsString('There are no configured Cron Tasks', $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionNextWithoutTriggers()
	{
		$cron = new TCronModule();
		$cron->setID('shellNever' . bin2hex(random_bytes(3)));
		$cron->init([['name' => 'neverRuns', 'schedule' => '@never', 'task' => TTestCronModuleTask::class]]);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionNext(['cron/next']));
			self::assertStringContainsString('No task has a next trigger', $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionExecDenied()
	{
		$cron = new TCronModule();
		$cron->setID('shellDenied' . bin2hex(random_bytes(3)));
		$cron->init([['name' => 'execDenied', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		$cron->attachBehavior('denies', new TTestCronDenyingBehavior(['dyRunTask']));
		$this->obj->setCronModule($cron);
		try {
			$this->obj->actionExec(['cron/exec', 'execDenied']);
			self::fail('cron/exec of a denied task did not exit');
		} catch (TExitException $e) {
			self::assertSame(TCronModule::EXIT_DENIED, $e->getExitCode());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionRetireStopAnswers()
	{
		$cron = new TTestShellCronRequestModule();
		$cron->setID('shellAnswer' . bin2hex(random_bytes(3)));
		$cron->init(null);
		$this->obj->setCronModule($cron);
		try {
			$cron->answer = true;
			self::assertTrue($this->obj->actionRetire(['cron/retire']));
			self::assertStringContainsString('The cron daemon is retiring', $this->writer->flush());
			self::assertTrue($this->obj->actionStop(['cron/stop']));
			self::assertStringContainsString('The cron daemon stopped', $this->writer->flush());
			$cron->answer = false;
			self::assertTrue($this->obj->actionRetire(['cron/retire', '0.1']));
			self::assertStringContainsString('did not acknowledge the retire request', $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionTopEndedAndOverdue()
	{
		$action = new TTestShellCronTopAction();
		$action->setWriter($this->writer);
		$cron = new TCronModule();
		$id = 'shellEnded' . bin2hex(random_bytes(3));
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronstatus-' . bin2hex(random_bytes(4));
		$cron->setID($id);
		$cron->setStatusPath($dir);
		$cron->init([['name' => 'slowTask', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		$action->setCronModule($cron);
		try {
			mkdir($dir);
			$now = microtime(true);
			file_put_contents($dir . DIRECTORY_SEPARATOR . 'manual.json', json_encode([
				'module' => $id, 'role' => 'manual', 'pid' => getmypid(), 'host' => (string) gethostname(), 'started' => $now - 5, 'updated' => $now,
				'concurrency' => 1, 'recent' => [], 'daemon' => null,
				'running' => [
					['name' => 'slowTask', 'mode' => 'Serial', 'user' => 'cron', 'started' => $now - 4, 'deadline' => $now + 60, 'warn' => $now - 1, 'overdue' => false],
					['name' => 'unreadTask', 'mode' => 'Serial', 'user' => 'cron', 'started' => $now - 4, 'deadline' => null],
				],
			]));
			file_put_contents($dir . DIRECTORY_SEPARATOR . 'gone.json', json_encode([
				'module' => $id, 'role' => 'sweep', 'pid' => 12345, 'host' => 'another-host.invalid', 'started' => $now - 2000, 'updated' => $now - 1000,
				'concurrency' => 1, 'recent' => [], 'daemon' => null,
				'running' => [['name' => 'slowTask', 'mode' => 'Serial', 'user' => 'cron', 'started' => $now - 1500, 'deadline' => null]],
			]));
			$this->writer->setColorSupported(true);
			self::assertTrue($action->actionTop(['cron/top']));
			$this->writer->setColorSupported(false);
			$text = $this->writer->flush();
			self::assertStringContainsString('Manual', $text);
			self::assertStringContainsString('ended without removing its status', $text);
			self::assertMatchesRegularExpression('/\033\[31;1m4\.\d\ds/', $text, 'a run past its warning is red');
			self::assertStringNotContainsString('unreadTask', $text, 'a run of a task not reported is left out');
			self::assertStringContainsString('1 running in process', $text, 'the run of an ended process is not running');

			self::assertTrue($action->actionTasks(['cron/tasks']));
			self::assertSame(1, substr_count($this->writer->flush(), 'running'), 'an ended process runs nothing');
		} finally {
			$cron->unlisten();
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}

	public function testRenderTopFormatsEachKindOfRow()
	{
		$now = time();
		$cron = new class () extends TCronModule {
			public array $report = [];

			public function getMonitorReport(?array $tasks = null): array
			{
				return $this->report;
			}
		};
		$cron->setID('shellTopRows');
		$cron->init(null);
		$stats = static fn (?float $rate, ?float $avg) => ['successRate' => $rate, 'avgDuration' => $avg];
		$task = static fn (array $values) => $values + ['mode' => 'Serial', 'next' => null, 'paused' => false, 'result' => null, 'last' => null, 'duration' => null, 'count' => 0, 'stats' => $stats(null, null)];
		$cron->report = [
			'module' => 'shellTopRows', 'daemonMode' => true, 'concurrency' => 1, 'processes' => [], 'ended' => [], 'runs' => [],
			'tasks' => [
				'farTask' => $task(['next' => $now + 3 * 86400]),
				'dueTask' => $task(['next' => $now - 5]),
				'pausedTask' => $task(['next' => $now + 60, 'paused' => true]),
				'neverTask' => $task(['result' => 'ok']),
				'oldTask' => $task(['result' => 'error', 'last' => $now - 3 * 86400, 'duration' => 1.5, 'count' => 4, 'stats' => $stats(0.75, 2.0)]),
			],
		];
		$action = new TShellCronAction();
		$action->setWriter($this->writer);
		try {
			$view = $action->renderTop($cron);
			self::assertStringContainsString('No cron process is running; the next cron/run tick starts the daemon.', $view);
			self::assertStringContainsString(date('Y-m-d', $now + 3 * 86400), $view, 'a trigger more than a day away shows its date');
			self::assertStringContainsString('due', $view);
			self::assertStringContainsString('paused', $view);
			self::assertStringContainsString(date('Y-m-d', $now - 3 * 86400), $view, 'a run more than a day ago shows its date');
			self::assertStringContainsString('75%', $view);
			self::assertMatchesRegularExpression('/neverTask\s+Serial\s+-\s+-/', $view, 'a result without a run time or seconds');

			$cron->report['daemonMode'] = false;
			$cron->report['tasks'] = [];
			$view = $action->renderTop($cron);
			self::assertStringContainsString('No cron process is running.', $view);
			self::assertStringNotContainsString('Next', $view, 'no triggers, no table');
		} finally {
			$cron->unlisten();
		}
	}

	public function testShellSeams()
	{
		$action = new TShellCronAction();
		$action->setWriter($this->writer);
		self::assertSame('-', PradoUnit::invoke($action, 'formatResult', null));
		self::assertIsBool(PradoUnit::invoke($action, 'isInteractive'));
		// Windows times sleeps to its clock tick (about 15.6 ms): the margin is wide.
		$start = microtime(true);
		PradoUnit::invoke($action, 'sleep', 0.05);
		self::assertGreaterThanOrEqual(0.03, microtime(true) - $start);
		$this->writer->write('flushed');
		PradoUnit::invoke($action, 'flushView');
		self::assertSame('', $this->writer->flush(), 'the view flushes the writer');
	}

	public function testSleepYieldsToTheEventLoop()
	{
		$action = new TShellCronAction();
		$ran = false;
		TAsync::getLoop()->defer(static function () use (&$ran) {
			$ran = true;
		});
		PradoUnit::invoke($action, 'sleep', 0.01);
		self::assertTrue($ran, 'the main fiber runs the loop while it waits');

		$order = [];
		$sleeper = TAsync::run(static function () use ($action, &$order) {
			PradoUnit::invoke($action, 'sleep', 0.01);
			$order[] = 'woke';
		});
		$order[] = 'yielded';
		TAsync::await($sleeper);
		self::assertSame(['yielded', 'woke'], $order, 'a worker fiber yields while it waits');
	}

	public function testTagArguments()
	{
		$action = new TTestShellCronTopAction();
		$action->setWriter($this->writer);
		$cron = new TCronModule();
		$cron->setID('shellTags' . bin2hex(random_bytes(3)));
		$cron->init([
			['name' => 'tagWeb', 'schedule' => '0 9 * * *', 'task' => TTestCronModuleTask::class, 'tags' => 'web', 'description' => 'Web task'],
			['name' => 'tagWebToo', 'schedule' => '0 10 * * *', 'task' => TTestCronModuleTask::class, 'tags' => 'web, mail'],
			['name' => 'tagOther', 'schedule' => '0 11 * * *', 'task' => TTestCronModuleTask::class],
		]);
		$action->setCronModule($cron);
		$file = PradoUnit::invoke($cron, 'getPausedFile');
		try {
			self::assertTrue($action->actionTasks(['cron/tasks', '#web']));
			$text = $this->writer->flush();
			self::assertStringContainsString('2 tasks found, filtered by #web', $text);
			self::assertStringContainsString('#web Web task', $text);
			self::assertStringContainsString('#web #mail', $text);
			self::assertStringNotContainsString('tagOther', $text);
			self::assertTrue($action->actionTasks(['cron/tasks', 'tagWeb%', '#mail']));
			self::assertStringContainsString("filtered by task name 'tagWeb%' and #mail", $this->writer->flush());

			self::assertTrue($action->actionNext(['cron/next', '#mail', '2']));
			$text = $this->writer->flush();
			self::assertStringContainsString('tagWebToo', $text);
			self::assertStringNotContainsString('tagOther', $text);

			self::assertTrue($action->actionTop(['cron/top', '#web']));
			$text = $this->writer->flush();
			self::assertStringContainsString('tagWeb', $text);
			self::assertStringNotContainsString('tagOther', $text);

			self::assertTrue($action->actionReport(['cron/report', '#mail']));
			self::assertSame(['tagWebToo'], array_keys(json_decode($this->writer->flush(), true)['tasks']));
			self::assertTrue($action->actionReport(['cron/report', 'prometheus', '#mail']));
			self::assertStringNotContainsString('task="tagWeb"', $this->writer->flush());

			self::assertTrue($action->actionPause(['cron/pause', '#web']));
			self::assertStringContainsString('Cron tasks tagWeb, tagWebToo are paused', $this->writer->flush());
			self::assertSame(['tagWeb', 'tagWebToo'], $cron->getPausedTasks());
			self::assertTrue($action->actionResume(['cron/resume', '#WEB']));
			self::assertStringContainsString('are resumed', $this->writer->flush());
			self::assertSame([], $cron->getPausedTasks());
			self::assertTrue($action->actionPause(['cron/pause', '#nothing']));
			self::assertStringContainsString("No cron task has the tag 'nothing'", $this->writer->flush());
		} finally {
			$cron->unlisten();
			if (is_file($file)) {
				unlink($file);
			}
		}
	}

	/**
	 * Sets the application user for the shell actions constructed in $test.
	 * @param ?TUser $user the user, or null for none
	 * @param callable $test runs with the user
	 */
	protected function withUser(?TUser $user, callable $test): void
	{
		$app = Prado::getApplication();
		$prior = $app->getUser();
		PradoUnit::setProp($app, '_user', $user);
		try {
			$test();
		} finally {
			PradoUnit::setProp($app, '_user', $prior);
		}
	}

	/**
	 * @param ?array $allowed the permissions of the user, or null for a user without the permissions behavior
	 * @return TUser the user 'alice'
	 */
	protected function createUser(?array $allowed): TUser
	{
		$user = new TUser(new TUserManager());
		$user->setName('alice');
		if ($user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR)) {
			$user->detachBehavior(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR);
		}
		if ($allowed !== null) {
			$permissions = new TTestCronUserPermissions();
			$permissions->allowed = $allowed;
			$user->attachBehavior(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR, $permissions);
		}
		return $user;
	}

	public function testReadableTasksByUserAndTag()
	{
		$cron = new TCronModule();
		$cron->setID('shellReadable' . bin2hex(random_bytes(3)));
		$cron->init([
			['name' => 'aliceWeb', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'username' => 'alice', 'tags' => 'web'],
			['name' => 'aliceMail', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'username' => 'alice'],
			['name' => 'bobWeb', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'username' => 'bob', 'tags' => 'web'],
		]);
		$report = function (TShellCronAction $action, array $args): ?array {
			self::assertTrue($action->actionReport($args));
			$text = $this->writer->flush();
			return ($text === '') ? null : array_keys(json_decode($text, true)['tasks']);
		};
		$action = function () use ($cron): TShellCronAction {
			$action = new TShellCronAction();
			$action->setWriter($this->writer);
			$action->setCronModule($cron);
			return $action;
		};
		try {
			$this->withUser($this->createUser([]), function () use ($action, $report) {
				self::assertSame(['aliceWeb'], $report($action(), ['cron/report', '#web']), 'a restricted user sees their tasks of the tag');
			});
			$this->withUser($this->createUser([TCronModule::PERM_CRON_READ_TASKS]), function () use ($cron, $action, $report) {
				$reader = $action();
				self::assertEqualsCanonicalizing(['aliceWeb', 'aliceMail', 'bobWeb'], $report($reader, ['cron/report']), 'a reader sees every task');
				self::assertEqualsCanonicalizing(['aliceWeb', 'bobWeb'], $report($reader, ['cron/report', '#web']), 'a reader sees every task of the tag');
				$cron->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetTasks']));
				self::assertNull($report($reader, ['cron/report']), 'a denied read shows nothing');
				$cron->detachBehavior('denies');
			});
			$this->withUser(null, function () use ($cron, $action, $report) {
				$cron->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetTasks']));
				self::assertNull($report($action(), ['cron/report']), 'a denied read without a user shows nothing');
				$cron->detachBehavior('denies');
			});
		} finally {
			$cron->unlisten();
		}
	}

	public function testConstructWithAUserWithoutPermissions()
	{
		$this->withUser($this->createUser(null), function () {
			$methods = PradoUnit::getProp(new TShellCronAction(), 'methods');
			self::assertContains('run', $methods, 'a user without the permissions behavior keeps every method');
			self::assertContains('stop', $methods);
		});
	}

	public function testRunningStatesOfEachStatus()
	{
		$module = new class () {
			public array $statuses = [];

			public function getStatuses(): array
			{
				return $this->statuses;
			}
		};
		$action = new TShellCronAction();
		$states = static function (array $statuses) use ($module, $action): array {
			$module->statuses = $statuses;
			return PradoUnit::invoke($action, 'getRunningStates', $module);
		};
		self::assertSame([], $states([['alive' => false, 'running' => [['name' => 'gone', 'mode' => 'Serial']]]]), 'an ended process runs nothing');
		self::assertSame([], $states([['alive' => true, 'running' => []]]));
		self::assertSame(['inProcess' => 'running'], $states([['alive' => true, 'running' => [['name' => 'inProcess', 'mode' => 'Serial']]]]));
		self::assertSame(['child' => 'pid 41'], $states([['alive' => true, 'running' => [['name' => 'child', 'mode' => TCronTaskMode::Process, 'pid' => 41]]]]));
		self::assertSame(['child' => 'pid 41, pid 42'], $states([['alive' => true, 'running' => [
			['name' => 'child', 'mode' => TCronTaskMode::Process, 'pid' => 41],
			['name' => 'child', 'mode' => TCronTaskMode::Process, 'pid' => 42],
		]]]), 'the runs of one task are listed together');
	}

	public function testCronModuleOfTheApplicationWithItsShellLog()
	{
		$app = Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_modules']);
		$cron = new TCronModule();
		$behavior = new TShellCronLogBehavior($this->writer);
		$cron->attachBehavior(TCronModule::SHELL_LOG_BEHAVIOR, $behavior);
		try {
			$app->setModule('shellLogged' . bin2hex(random_bytes(3)), $cron);
			$action = new TShellCronAction();
			$action->setWriter(new TShellWriter(new TTextWriter()));
			self::assertSame($cron, $action->getCronModule());
			self::assertSame($behavior, $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR), 'the shell keeps the log behavior of the module');
		} finally {
			PradoUnit::restore($app, $snapshot);
			$cron->unlisten();
		}
	}

	public function testActionExecWithoutATaskName()
	{
		try {
			$this->obj->actionExec(['cron/exec']);
			self::fail('cron/exec without a task name did not exit');
		} catch (TExitException $e) {
			self::assertSame(TCronModule::EXIT_NO_TASK, $e->getExitCode());
		}
		self::assertStringContainsString("Cron task '' is not found", $this->writer->flush());
	}

	public function testActionDaemonInDaemonModeWithoutADaemon()
	{
		$cron = new TCronModule();
		$cron->setID('shellDaemonMode' . bin2hex(random_bytes(3)));
		$cron->setDaemon(true);
		$cron->setDaemonMutex(new TMemoryMutex());
		$cron->init(null);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionDaemon(['cron/daemon']));
			self::assertStringContainsString('the next cron/run tick starts one', $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testRequestTimeoutOfANonNumericArgument()
	{
		$cron = new TTestShellCronRequestModule();
		$cron->setID('shellTimeoutArg' . bin2hex(random_bytes(3)));
		$cron->init(null);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionRetire(['cron/retire', 'soon']));
			self::assertStringContainsString('No cron daemon is running', $this->writer->flush());
			self::assertNull(PradoUnit::invoke($this->obj, 'requestTimeout', ['cron/retire', 'soon']));
			self::assertSame(0.5, PradoUnit::invoke($this->obj, 'requestTimeout', ['cron/retire', '0.5']));
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionReportArguments()
	{
		$cron = new TCronModule();
		$cron->setID('shellReportArgs' . bin2hex(random_bytes(3)));
		$cron->init([['name' => 'argTask', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'tags' => 'web']]);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionReport([]));
			self::assertArrayHasKey('argTask', json_decode($this->writer->flush(), true)['tasks'], 'no arguments is the JSON report');
			self::assertTrue($this->obj->actionReport([1 => '#web']));
			self::assertArrayHasKey('argTask', json_decode($this->writer->flush(), true)['tasks'], 'a tag as the only argument');
			self::assertTrue($this->obj->actionReport([1 => 'json']));
			self::assertArrayHasKey('argTask', json_decode($this->writer->flush(), true)['tasks']);
			self::assertTrue($this->obj->actionReport([1 => 5]));
			self::assertStringContainsString("The report format '5' is not json or prometheus", $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionTopInATerminalRunsUntilTheTasksCannotBeRead()
	{
		$cron = new TCronModule();
		$cron->setID('shellTopLive' . bin2hex(random_bytes(3)));
		$cron->init([['name' => 'liveTask', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		$action = new class () extends TTestShellCronTopAction {
			public ?TCronModule $deny = null;

			protected function sleep(float $seconds): void
			{
				parent::sleep($seconds);
				if (count($this->sleeps) === 2) {
					$this->deny->attachBehavior('denies', new TTestCronDenyingBehavior(['dyGetTasks']));
				}
			}
		};
		$action->deny = $cron;
		$action->interactive = true;
		$action->setWriter($this->writer);
		$action->setCronModule($cron);
		try {
			self::assertTrue($action->actionTop(['cron/top']));
			self::assertSame([1.0, 1.0], $action->sleeps, 'a terminal without a count redraws until the view ends');
			self::assertSame(2, substr_count($this->writer->flush(), "\033[H\033[2J"));
		} finally {
			$cron->unlisten();
		}
	}

	public function testRenderTopOfADaemonNotWatching()
	{
		$now = microtime(true);
		$cron = new class () extends TCronModule {
			public array $report = [];

			public function getMonitorReport(?array $tasks = null): array
			{
				return $this->report;
			}
		};
		$cron->setID('shellTopDaemon');
		$cron->init(null);
		$cron->report = [
			'module' => 'shellTopDaemon', 'daemonMode' => true, 'concurrency' => 1, 'ended' => [], 'runs' => [], 'tasks' => [],
			'processes' => [['role' => TCronModule::STATUS_ROLE_DAEMON, 'pid' => 7, 'host' => 'here', 'alive' => true, 'started' => $now - 30,
				'daemon' => ['state' => 'running', 'transport' => 'Prado\\Util\\Cron\\Wake\\TCronWakeFile', 'watching' => false, 'wake' => null]]],
		];
		$action = new TShellCronAction();
		$action->setWriter($this->writer);
		try {
			$view = $action->renderTop($cron);
			self::assertStringContainsString('TCronWakeFile not watching', $view);
			self::assertStringNotContainsString('next sweep', $view, 'without a wake time no next sweep is shown');
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionRunWithTheStatusSet()
	{
		$cron = new TCronModule();
		$cron->setID('shellRunStatus' . bin2hex(random_bytes(3)));
		$cron->init(null);
		$cron->setEnableStatus(false);
		$this->obj->setCronModule($cron);
		$prior = $cron->getLastCronTime();
		try {
			$cron->setLastCronTime(0);
			self::assertTrue($this->obj->actionRun(['cron/run']));
			self::assertStringContainsString('Last cron run time was never', $this->writer->flush());
			$cron->setLastCronTime(1700000000);
			self::assertTrue($this->obj->actionRun(['cron/run']));
			self::assertStringContainsString('Last cron run time was ' . date('Y-m-d H:i:s', 1700000000), $this->writer->flush());
			self::assertFalse($cron->getEnableStatus(), 'cron/run keeps a status that is set');
		} finally {
			$cron->setLastCronTime($prior);
			$cron->unlisten();
		}
	}

	public function testActionNextShowsADueTrigger()
	{
		$cron = new TCronModule();
		$cron->setID('shellNextDue' . bin2hex(random_bytes(3)));
		$cron->init([['name' => 'dueNow', 'schedule' => '* * * * *', 'task' => TTestCronModuleTask::class]]);
		$cron->getTask('dueNow')->setLastExecTime(time() - 3600);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionNext(['cron/next', 'dueNow', '2']));
			self::assertStringContainsString('due', $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionNowReportsTheTaskAndNotItsChain()
	{
		$cron = new TCronModule();
		$cron->setID('shellNowChain' . bin2hex(random_bytes(3)));
		$cron->setDaemonMutex(new TMemoryMutex());
		$cron->init([
			['name' => 'nowFirst', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'then' => 'nowSecond'],
			['name' => 'nowSecond', 'schedule' => '@never', 'task' => TTestCronShellFailingTask::class],
		]);
		$cron->setEnableStatus(false);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionNow(['cron/now', 'nowFirst']));
			$text = $this->writer->flush();
			self::assertStringContainsString("Cron task 'nowFirst' ran: ok", $text, 'the failed chained task is not the result');
			self::assertSame(1, $cron->getTask('nowSecond')->getProcessCount(), 'the chained task ran');
		} finally {
			$cron->unlisten();
		}
	}

	public function testActionTasksStatesAndResults()
	{
		$cron = new class () extends TCronModule {
			public float $lastCronTime = 0;

			public function getLastCronTime()
			{
				return $this->lastCronTime;
			}

			public function getPausedTasks(): array
			{
				return ['pausedRunning', 'pausedOnly'];
			}

			public function getStatuses(): array
			{
				return [['alive' => true, 'running' => [['name' => 'pausedRunning', 'mode' => 'Serial']]]];
			}

			public function getTaskResults(bool $fresh = false): array
			{
				return ['pausedRunning' => ['result' => 'ok', 'duration' => null], 'pausedOnly' => ['result' => 'error', 'duration' => 12.0]];
			}
		};
		$cron->setID('shellTaskStates');
		$cron->init([
			['name' => 'pausedRunning', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
			['name' => 'pausedOnly', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
		]);
		$this->obj->setCronModule($cron);
		try {
			self::assertTrue($this->obj->actionTasks(['cron/tasks']));
			$text = $this->writer->flush();
			self::assertStringContainsString('Last cron run was never', $text);
			self::assertStringContainsString('paused, running', $text, 'a paused task that runs shows both');
			self::assertMatchesRegularExpression('/pausedRunning.*\bok\b/', $text, 'a result without a duration');
			self::assertStringContainsString('error 12s', $text);
			$cron->lastCronTime = 1700000000;
			self::assertTrue($this->obj->actionTasks(['cron/tasks']));
			self::assertStringContainsString('Last cron run was ' . date('Y-m-d H:i:s', 1700000000), $this->writer->flush());
		} finally {
			$cron->unlisten();
		}
	}
}
