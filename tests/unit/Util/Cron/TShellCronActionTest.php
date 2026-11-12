<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TExitException;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Prado;
use Prado\TComponent;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TShellCronAction;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Mutex\TMemoryMutex;

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
			self::assertTrue($this->obj->actionIndex(['cron/index']));
			$text = $this->writer->flush();
			self::assertEquals(1, preg_match("/No registered application tasks/", $text));
			
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
		} finally {
			$cron->unlisten();
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

}
