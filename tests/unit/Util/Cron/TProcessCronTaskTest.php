<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TProcessCronTask;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;

/**
 * Test double: stubs the OS launch so no real process is spawned.
 */
class TTestProcessCronTask extends TProcessCronTask
{
	public int $launchCalls = 0;
	public ?string $launchedCommand = null;
	public ?int $nextPid = 4242;
	public bool $launchedAlive = true;   // post-launch liveness of the just-launched fake pid
	public ?string $fakeStart = null;    // when set, processStartTime() returns this for any pid; the fake pid otherwise has none

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
		// The fake pid has no real start time; a real process may hold that pid on the host.
		if ($this->fakeStart !== null || $pid === $this->nextPid) {
			return $this->fakeStart;
		}
		return parent::processStartTime($pid);
	}
}

class TProcessCronTaskTest extends \PHPUnit\Framework\TestCase
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

	public function testLaunchesARealDetachedProcess()
	{
		if (TProcessHelper::isSystemWindows()) {
			self::markTestSkipped('the POSIX launch');
		}
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-proccron-' . bin2hex(random_bytes(4));
		$log = $dir . '.log';
		$task = new TProcessCronTask();
		$task->setName('realproc');
		$task->setPidFile($dir . DIRECTORY_SEPARATOR . 'real.pid');
		$task->setCommand('@php -r "echo getcwd(); sleep(3);"');
		$task->setDirectory(sys_get_temp_dir());
		$task->setLogFile($log);
		self::assertSame(sys_get_temp_dir(), $task->getDirectory());
		self::assertSame($log, $task->getLogFile());
		$pid = null;
		try {
			$pid = $task->execute(null);
			self::assertIsInt($pid);
			self::assertTrue($task->isProcessRunning(), 'the launched process runs with its recorded start time');
			self::assertNotNull(PradoUnit::invoke($task, 'getRecordedStart'));
			self::assertFalse($task->execute(null), 'a running process is not launched again');
			for ($wait = 0; $wait < 40 && (string) @file_get_contents($log) === ''; $wait++) {
				usleep(50000);
			}
			self::assertStringContainsString(basename(realpath(sys_get_temp_dir())), (string) file_get_contents($log), 'the command runs in its Directory');

			$missing = new TProcessCronTask();
			$missing->setName('nodir');
			$missing->setPidFile($dir . DIRECTORY_SEPARATOR . 'nodir.pid');
			$missing->setCommand('@php -r "sleep(3);"');
			$missing->setDirectory($dir . DIRECTORY_SEPARATOR . 'missing');
			self::assertFalse($missing->execute(null), 'a Directory that cannot be entered launches nothing');
		} finally {
			if ($pid) {
				posix_kill($pid, SIGTERM);
			}
			foreach ([$dir . DIRECTORY_SEPARATOR . 'real.pid', $dir . DIRECTORY_SEPARATOR . 'real.pid.lock', $dir . DIRECTORY_SEPARATOR . 'nodir.pid.lock', $log] as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}

	public function testAPidFileThatCannotBeWrittenIsALaunchFailure()
	{
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-proccron-file-' . bin2hex(random_bytes(4));
		file_put_contents($file, 'a file, not a directory');
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-proccron-ro-' . bin2hex(random_bytes(4));
		mkdir($dir);
		chmod($dir, 0o500);
		try {
			$task = $this->makeTask();
			$task->setPidFile($file . DIRECTORY_SEPARATOR . 'x.pid');
			self::assertFalse($task->execute(null), 'a pid directory that cannot be made fails the launch');
			self::assertNull(PradoUnit::invoke($task, 'getRecordedStart'));
			$task->setPidFile($dir . DIRECTORY_SEPARATOR . 'x.pid');
			// Windows ignores the mode of a directory: probe it with a write.
			if (@file_put_contents($dir . DIRECTORY_SEPARATOR . 'probe', '') !== false) {
				self::markTestSkipped('the process can write to a read-only directory');
			}
			self::assertFalse($task->execute(null), 'a pid file that cannot be written fails the launch');
			self::assertSame([], glob($dir . DIRECTORY_SEPARATOR . '*'));
		} finally {
			chmod($dir, 0o700);
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $left) {
				unlink($left);
			}
			rmdir($dir);
			unlink($file);
		}
	}

	public function testSerializeLeavesOutTheDefaults()
	{
		$text = serialize(new TProcessCronTask());
		foreach (['_command', '_pidFile', '_match', '_directory', '_logFile'] as $prop) {
			self::assertStringNotContainsString($prop, $text);
		}
		$copy = unserialize(serialize($this->makeTask()->setMatch('websocket')));
		self::assertSame('websocket', $copy->getMatch());
		self::assertSame($this->pidFile, $copy->getPidFile());
	}
}
