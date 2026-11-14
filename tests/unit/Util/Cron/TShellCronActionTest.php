<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TExitException;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Prado;
use Prado\TComponent;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TShellCronAction;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Mutex\TMemoryMutex;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\Test\Unit\PradoUnit;

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
}
