<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\IO\Async\TAsync;
use Prado\Prado;
use Prado\Test\Unit\Harness\Util\Cron\TTestCronModule;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTaskMode;
use Prado\Util\Mutex\TMemoryMutex;

/**
 * Runs a Process task's child as a PHP one-liner, with a short timeout grace.
 */
class TTestCronProcessModule extends TTestCronModule
{
	public const PROCESS_TIMEOUT_GRACE = 0.2;

	/** @var string[] the child command; empty for the module's own */
	public array $command = [];

	public function getTaskProcessCommand($task): array
	{
		return $this->command ?: parent::getTaskProcessCommand($task);
	}

	public function callRunTasks(array $tasks): array
	{
		return $this->runTasks($tasks);
	}
}

class TCronModuleProcessTest extends \PHPUnit\Framework\TestCase
{
	private TTestCronProcessModule $module;

	private string $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronprocess-' . bin2hex(random_bytes(4));
		$this->module = new TTestCronProcessModule();
		$this->module->setID('cronProcess');
		$this->module->fakeNow = time();
		$this->module->fakeMicrotime = (float) $this->module->fakeNow;
		$this->module->setDaemonMutex(new TMemoryMutex());
		$this->module->setStatusPath($this->dir);
		$this->module->init([['name' => 'child', 'schedule' => '0 0 1 1 ? 2000', 'task' => TTestCronModuleTask::class, 'mode' => 'Process']]);
	}

	protected function tearDown(): void
	{
		$this->module->unlisten();
		foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
			unlink($file);
		}
		if (is_dir($this->dir)) {
			rmdir($this->dir);
		}
		Prado::getApplication()->setGlobalState(TCronModule::LAST_CRON_TIME, null);
		Prado::getApplication()->setGlobalState(TCronModule::TASKS_INFO, null);
	}

	private function runChild(string $code): bool
	{
		$this->module->command = [PHP_BINARY, '-r', $code];
		$task = $this->module->getTask('child');
		$task->setLastExecTime(null);
		return $this->module->runTask($task);
	}

	private function childResult(): ?string
	{
		return $this->module->getTaskResults()['child']['result'];
	}

	public function testTaskProcessCommand()
	{
		$command = (new TCronModule())->getTaskProcessCommand($this->module->getTask('child'));
		self::assertSame(PHP_BINARY, $command[0]);
		self::assertFileExists($command[1]);
		self::assertSame('prado-cli.php', basename($command[1]));
		self::assertSame('-d=' . Prado::getApplication()->getBasePath(), $command[2]);
		self::assertSame(['--quiet=1', 'cron/exec', 'child'], array_slice($command, 3));
	}

	public function testRunTask_TheChildExitCodeIsTheResult()
	{
		self::assertTrue($this->runChild('exit(0);'));
		self::assertSame(TCronModule::RESULT_OK, $this->childResult());
		self::assertSame(1, $this->module->getTask('child')->getProcessCount(), 'the parent claims the run');

		self::assertFalse($this->runChild('exit(' . TCronModule::EXIT_ERROR . ');'));
		self::assertSame(TCronModule::RESULT_ERROR, $this->childResult());

		self::assertFalse($this->runChild('exit(' . TCronModule::EXIT_DENIED . ');'));
		self::assertSame(TCronModule::RESULT_DENIED, $this->childResult());

		$this->module->command = [__DIR__ . DIRECTORY_SEPARATOR . 'no-such-binary-' . bin2hex(random_bytes(4))];
		$task = $this->module->getTask('child');
		$task->setLastExecTime(null);
		self::assertFalse($this->module->runTask($task), 'a child that cannot start is an error');
		self::assertSame(TCronModule::RESULT_ERROR, $this->childResult());
	}

	public function testRunTask_AChildPastItsTimeoutIsTerminated()
	{
		$this->module->getTask('child')->setTimeout(0.1);
		$start = microtime(true);
		self::assertFalse($this->runChild('sleep(30);'));
		self::assertLessThan(10.0, microtime(true) - $start, 'the child is terminated after the timeout and its grace');
		self::assertSame(TCronModule::RESULT_TIMEOUT, $this->childResult());
	}

	public function testRunTasks_DoesNotWaitForAProcessTask()
	{
		$this->module->command = [PHP_BINARY, '-r', 'usleep(300000);'];
		$task = $this->module->getTask('child');
		self::assertSame(TCronTaskMode::Process, $task->getMode());
		self::assertSame([], $this->module->callRunTasks([$task]), 'a Process task has no result when the sweep ends');
		self::assertSame(1, $this->module->getProcessRunCount());
		$this->module->awaitProcessRuns();
		self::assertSame(0, $this->module->getProcessRunCount());
		self::assertSame(TCronModule::RESULT_OK, $this->childResult());
	}

	public function testStatus_ShowsTheChildProcess()
	{
		$this->module->setEnableStatus(true);
		$this->module->command = [PHP_BINARY, '-r', 'usleep(500000);'];
		$future = TAsync::run([$this->module, 'processPendingTasks']);
		$run = null;
		$deadline = microtime(true) + 5;
		while ($run === null && microtime(true) < $deadline) {
			TAsync::sleep(0.02);
			foreach ($this->module->getStatuses() as $status) {
				foreach ($status['running'] as $entry) {
					if (($entry['pid'] ?? getmypid()) !== getmypid()) {
						$run = $entry;
					}
				}
			}
		}
		self::assertSame(1, $future->await(), 'the sweep returns after its Process task finishes');
		self::assertNotNull($run, 'the status lists the running child');
		self::assertSame('child', $run['name']);
		self::assertSame(TCronTaskMode::Process, $run['mode']);
		self::assertIsInt($run['pid']);
		self::assertSame([], $this->module->getStatuses());
		self::assertSame(TCronModule::RESULT_OK, $this->childResult());
	}
}
