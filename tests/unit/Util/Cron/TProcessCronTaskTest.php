<?php

use Prado\Util\Cron\TProcessCronTask;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Exceptions\TConfigurationException;

/**
 * Test double: stubs the OS launch so no real process is spawned.
 */
class TTestProcessCronTask extends TProcessCronTask
{
	public int $launchCalls = 0;
	public ?string $launchedCommand = null;
	public ?int $nextPid = 4242;
	public bool $launchedAlive = true;   // post-launch liveness of the just-launched fake pid
	public ?string $fakeStart = null;    // when set, processStartTime() returns this for any pid

	protected function launchProcess(string $command): ?int
	{
		$this->launchCalls++;
		$this->launchedCommand = $command;
		return $this->nextPid;
	}

	protected function isRunning(int $pid): bool
	{
		// Treat the just-launched fake pid as alive (controllable); delegate everything else to the real check.
		return $pid === $this->nextPid ? $this->launchedAlive : parent::isRunning($pid);
	}

	protected function processStartTime(int $pid): ?string
	{
		return $this->fakeStart ?? parent::processStartTime($pid);
	}
}

class TProcessCronTaskTest extends PHPUnit\Framework\TestCase
{
	private string $pidFile;

	protected function setUp(): void
	{
		$this->pidFile = sys_get_temp_dir() . '/prado_proccron_' . getmypid() . '.pid';
		@unlink($this->pidFile);
	}

	protected function tearDown(): void
	{
		@unlink($this->pidFile);
		@unlink($this->pidFile . '.lock');
		foreach (glob($this->pidFile . '.*.tmp') ?: [] as $tmp) {
			@unlink($tmp);
		}
	}

	private function makeTask(): TTestProcessCronTask
	{
		$task = new TTestProcessCronTask();
		$task->setName('proctest');
		$task->setPidFile($this->pidFile);
		$task->setCommand('@php prado-cli websocket-server');
		return $task;
	}

	public function testLaunchesWhenNotRunning()
	{
		$task = $this->makeTask();
		self::assertFalse($task->isProcessRunning());
		$pid = $task->execute(null);
		self::assertSame(4242, $pid);
		self::assertSame(1, $task->launchCalls);
		self::assertSame('4242', trim(file_get_contents($this->pidFile)));
		self::assertSame(4242, $task->getPid());
	}

	public function testAtPhpIsReplacedWithPhpBinary()
	{
		$task = $this->makeTask();
		$task->execute(null);
		self::assertStringNotContainsString('@php', $task->launchedCommand);
		self::assertStringStartsWith(PHP_BINARY . ' ', $task->launchedCommand);
	}

	public function testSkipsLaunchWhenAlreadyRunning()
	{
		// Point the pid file at this live PHP process.
		file_put_contents($this->pidFile, (string) getmypid());
		$task = $this->makeTask();
		self::assertTrue($task->isProcessRunning());
		self::assertFalse($task->execute(null)); // already running
		self::assertSame(0, $task->launchCalls); // no relaunch
	}

	public function testRelaunchesWhenPidIsDead()
	{
		// A pid that is almost certainly not running.
		file_put_contents($this->pidFile, '2147483646');
		$task = $this->makeTask();
		self::assertFalse($task->isProcessRunning());
		self::assertSame(4242, $task->execute(null));
		self::assertSame(1, $task->launchCalls);
	}

	public function testNoCommandThrows()
	{
		$task = new TTestProcessCronTask();
		$task->setName('nocmd');
		$task->setPidFile($this->pidFile);
		self::expectException(TConfigurationException::class);
		$task->execute(null);
	}

	public function testLaunchFailureThrows()
	{
		$task = $this->makeTask();
		$task->nextPid = null; // simulate a failed launch
		self::expectException(TConfigurationException::class);
		$task->startProcess();
	}

	public function testGetPidIsNullWithoutFile()
	{
		$task = $this->makeTask();
		self::assertNull($task->getPid());
	}

	public function testDefaultPidFileUsesBgFolderAndName()
	{
		$task = new TProcessCronTask('@php prado-cli x');
		$task->setName('mysvc');
		$expected = Prado::getApplication()->getRuntimePath()
			. DIRECTORY_SEPARATOR . TProcessCronTask::PID_DIRECTORY
			. DIRECTORY_SEPARATOR . 'mysvc.pid';
		self::assertSame($expected, $task->getPidFile());
	}

	public function testMatchGuardsPidReuse()
	{
		if (TProcessHelper::isSystemWindows()) {
			self::markTestSkipped('command-line match is POSIX-only');
		}
		file_put_contents($this->pidFile, (string) getmypid());
		$task = $this->makeTask();
		// The live process command line contains "php" but not this random token.
		$task->setMatch('php');
		self::assertTrue($task->isProcessRunning());
		$task->setMatch('zzz_not_in_cmdline_zzz');
		self::assertFalse($task->isProcessRunning());
	}

	public function testSettersAreChainable()
	{
		$task = new TProcessCronTask();
		self::assertSame($task, $task->setCommand('x'));
		self::assertSame($task, $task->setPidFile('/tmp/x.pid'));
		self::assertSame($task, $task->setMatch('x'));
		self::assertSame($task, $task->setDirectory('/tmp'));
		self::assertSame($task, $task->setLogFile('/tmp/x.log'));
	}

	public function testAdvisoryLockPreventsConcurrentLaunch()
	{
		// While another worker holds the lock, execute() skips (no duplicate launch). [PROC-1]
		$lock = fopen($this->pidFile . '.lock', 'c');
		self::assertTrue(flock($lock, LOCK_EX | LOCK_NB));
		try {
			$task = $this->makeTask();
			self::assertFalse($task->execute(null));
			self::assertSame(0, $task->launchCalls);
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	public function testPidReuseGuardWithStartTime()
	{
		// A live pid whose recorded start time no longer matches is treated as a reused (foreign) pid. [PROC-2]
		file_put_contents($this->pidFile, "4242\nMon Jan  1 00:00:00 2020");
		$task = $this->makeTask();          // nextPid 4242, launchedAlive true => isRunning(4242)=true
		$task->fakeStart = 'Tue Feb  2 00:00:00 2021'; // live start != recorded => reused
		self::assertFalse($task->isProcessRunning());
		$task->fakeStart = 'Mon Jan  1 00:00:00 2020'; // live start == recorded => ours
		self::assertTrue($task->isProcessRunning());
	}

	public function testImmediateDeathIsNotReportedAsStarted()
	{
		// A command that exits immediately must not be logged as "started" or abort the sweep. [PROC-3]
		$task = $this->makeTask();
		$task->launchedAlive = false;       // the launched pid is gone after the grace window
		self::assertFalse($task->execute(null)); // sweep-safe: returns false, does not throw
		self::assertSame(1, $task->launchCalls);

		// startProcess() itself surfaces the failure as a typed exception (caught by execute()).
		$task2 = $this->makeTask();
		$task2->launchedAlive = false;
		self::expectException(TConfigurationException::class);
		$task2->startProcess();
	}

	public function testWritePidRecordsStartTime()
	{
		$task = $this->makeTask();
		$task->fakeStart = 'START-MARKER';
		$task->execute(null);
		self::assertSame("4242\nSTART-MARKER", file_get_contents($this->pidFile));
		self::assertSame(4242, $task->getPid()); // getPid reads only the first line
	}

	public function testAtPhpOnlyReplacesWholeToken()
	{
		// '@php' is resolved only as a whole token, never inside an argument value. [PROC-6]
		$task = $this->makeTask();
		$task->setCommand('@php run --note=keep@php');
		$task->execute(null);
		self::assertStringStartsWith(PHP_BINARY . ' run', $task->launchedCommand);
		self::assertStringContainsString('--note=keep@php', $task->launchedCommand); // untouched
	}

	public function testCronTaskErrorMessagesAreResolved()
	{
		// The new error codes must resolve to real text (not render as the raw key). [CLOS-1]
		foreach ([
			'processcrontask_write_failed',
			'closurecrontask_no_closure',
			'closurecrontask_closure_required',
			'closurecrontask_invalid_securitymanager',
			'closurecrontask_decrypt_failed',
		] as $code) {
			$message = (new TConfigurationException($code, 'CTX'))->getMessage();
			self::assertStringNotContainsString($code, $message, "message code '$code' is missing from messages.txt");
		}
	}
}
