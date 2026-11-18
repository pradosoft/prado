<?php

namespace Prado\Test\Unit\Util\Helpers;

use Prado\Exceptions\TNotSupportedException;
use Prado\IO\Async\IEventLoop;
use Prado\Prado;
use Prado\TEventSubscription;
use Prado\Util\Behaviors\TCaptureForkLog;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\TSignalsDispatcher;
use Prado\Test\Unit\Util\TTestSignalsDispatcher;

/**
 * A TProcessHelper that reports Windows, to run the Windows branches that start no command.
 */
class TProcessHelperTestWindows extends TProcessHelper
{
	public static function isSystemWindows(): bool
	{
		return true;
	}
}

/**
 * A TProcessHelper that cannot fork.
 */
class TProcessHelperTestUnforkable extends TProcessHelper
{
	public static function isForkable(): bool
	{
		return false;
	}
}

class TProcessHelperTest extends \PHPUnit\Framework\TestCase
{
	public $dispatcher = null;
	protected function setUp(): void
	{
	}
	
	protected function tearDown(): void
	{
		if ($this->dispatcher) {
			$this->dispatcher->detach();
			$this->dispatcher = null;
		}
		TTestSignalsDispatcher::setPriorHandlerPriority(null);
	}
	
	public function testIsSystemWindows()
	{
		self::assertEquals(strncasecmp(php_uname('s'), 'win', 3) === 0, TProcessHelper::isSystemWindows());
	}
	
	public function testIsSystemMacOS()
	{
		self::assertEquals(strncasecmp(php_uname('s'), 'darwin', 6) === 0, TProcessHelper::isSystemMacOS());
	}
	
	public function testIsSystemLinux()
	{
		self::assertEquals(strncasecmp(php_uname('s'), 'linux', 5) === 0, TProcessHelper::isSystemLinux());
		self::assertEquals(strncasecmp(php_uname('s'), 'linux', 5) === 0, TProcessHelper::isSystemLinux());
	}
	
	public function testIsForkable()
	{
		self::assertEquals(function_exists('pcntl_fork'), TProcessHelper::isForkable());
	}
	
	public function testFork()
	{
		if (!TProcessHelper::isForkable()) {
			//$this->markTestSkipped("skipping " . TProcessHelper::class . "::sendSignal on Windows.");
			self::expectException(TNotSupportedException::class);
			TProcessHelper::fork();
			return;
		}
		
		$dispatcher = TSignalsDispatcher::singleton();
		$restoreCalled = $prepareCalled = false;
		
		
		$prepareSub = new TEventSubscription($dispatcher, TProcessHelper::FX_PREPARE_FOR_FORK, 
			function($sender, $param) use (&$prepareCalled) {
				$prepareCalled = true;
				return ['datakey' => 'value'];
			}
		);
		$restoreParam = null;
		$restoreSub = new TEventSubscription($dispatcher, TProcessHelper::FX_RESTORE_AFTER_FORK, 
			function($sender, $param) use (&$restoreCalled, &$restoreParam) {
				$restoreCalled = true;
				$restoreParam = $param;
			}
		);
		
		$pid = TProcessHelper::fork();
			
		if ($pid === 0) {
			// The child always exits; it must never return into PHPUnit.
			try {
				usleep(50_000); // time for isRunning by parent.  delay of start is usually enough.
				exit();
			} catch (\Throwable $e) {
				exit(1);
			}
		} else if ($pid === -1) {
			self::fail('Failed to fork with pid = -1');
			return;
		}
		
		self::assertTrue(TProcessHelper::isRunning($pid));
		self::assertTrue($prepareCalled);
		self::assertTrue($restoreCalled);
		self::assertEquals($pid, $restoreParam['pid']);
		self::assertEquals('value', $restoreParam['datakey']);
		
		$dispatcher->detach();
	}
	
	public function testSendSignal()
	{
		if (TProcessHelper::isSystemWindows()) {
			self::expectException(TNotSupportedException::class);
			TProcessHelper::sendSignal(0);
			return;
		}
		$app = Prado::getApplication();
		$this->dispatcher = TSignalsDispatcher::singleton();
		
		self::assertTrue($this->dispatcher->getAsyncSignals());
		
		$done = false;
		$signal = SIGUSR1;
		$this->dispatcher->attachEventHandler($signal, $handler = function($sender, $param) use (&$done) {$done = true;});
		self::assertTrue(TProcessHelper::sendSignal($signal));
		self::assertTrue($done, 'did not send the signal');
		self::assertTrue($this->dispatcher->getEventHandlers('fxSignalUser1')->contains($handler));
		self::assertTrue($this->dispatcher->getEventHandlers($signal)->contains($handler));
		self::assertEquals($this->dispatcher, pcntl_signal_get_handler($signal));
		
		$this->dispatcher->detachEventHandler($signal, $handler);
		$this->dispatcher->detach();
	}
	
	public function testIsRunning_Kill_Priority()
	{
		self::assertTrue(TProcessHelper::isRunning(getmypid()));
		
		// The child outlives the priority calls; on Windows each one starts PowerShell, which can take seconds.
		$command = TProcessHelper::filterCommand(['@php', '-r', 'sleep(60);']);
		
		$descriptorspec = [];
		
		$process = proc_open($command, $descriptorspec, $pipes);
		$info = proc_get_status($process);
		$pid = $info['pid'];
		try {
			// A loaded Windows runner can take well over 50ms to list the child in TASKLIST.
			$deadline = microtime(true) + 10;
			while (!($running = TProcessHelper::isRunning($pid)) && microtime(true) < $deadline) {
				usleep(50_000);
			}
			self::assertTrue($running, "Child process $pid did not appear in the OS process list.");
			
			self::assertTrue(is_int($processPriority = TProcessHelper::getProcessPriority($pid)));
			self::assertTrue(TProcessHelper::setProcessPriority(TProcessHelper::WINDOWS_BELOW_NORMAL_PRIORITY, $pid));
			self::assertEquals(TProcessHelper::WINDOWS_BELOW_NORMAL_PRIORITY, TProcessHelper::getProcessPriority($pid));
			
			self::assertTrue(TProcessHelper::setProcessPriority(TProcessHelper::WINDOWS_IDLE_PRIORITY, $pid));
			self::assertEquals(TProcessHelper::WINDOWS_IDLE_PRIORITY, TProcessHelper::getProcessPriority($pid));
			
			self::assertTrue(TProcessHelper::kill($pid)); // kill pid despite sleeping.
			proc_close($process);
			$process = null;
			self::assertFalse(TProcessHelper::isRunning($pid));
		} finally {
			if ($process !== null) {
				proc_terminate($process, 9);
				proc_close($process);
			}
		}
	}
	
	public function testEscapeShellArg()
	{
		if (TProcessHelper::isSystemWindows()) {
			$argument = '';
			$expected = '""';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test';
			$expected = '"test"';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test argument';
			$expected = '"test argument"';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = '"quoted"';
			// 'quoted' is non-quote content so $addQuote=true; result is outer double-quotes wrapping the escaped inner quotes.
			$expected = '"\"quoted\""';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = '%ENVIRONMENT%';
			$expected = '^%"ENVIRONMENT"^%';
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));

		} else {
			
			$argument = '';
			$expected = "''";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test';
			$expected = "'test'";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = 'test argument';
			$expected = "'test argument'";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));
			
			$argument = "'quoted'";
			$expected = "''\\''quoted'\\'''";
			$this->assertEquals($expected, TProcessHelper::escapeShellArg($argument));

		}
	}
	
	public function testisSurroundedBy()
	{
		self::assertFalse(TProcessHelper::isSurroundedBy('text', '-'));
		self::assertTrue(TProcessHelper::isSurroundedBy('-text-', '-'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-text', '-'));
		self::assertTrue(TProcessHelper::isSurroundedBy('-text-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-ext-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-tex-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-ex-', '-t'));
		self::assertFalse(TProcessHelper::isSurroundedBy('-', '-'));
	}

	/**
	 * Starts a PHP child that sleeps, and returns [process, pid].
	 */
	private function startSleepingChild(): array
	{
		$process = proc_open(TProcessHelper::filterCommand(['@php', '-r', 'sleep(60);']), [], $pipes);
		$pid = proc_get_status($process)['pid'];
		$deadline = microtime(true) + 10;
		while (!TProcessHelper::isRunning($pid) && microtime(true) < $deadline) {
			usleep(10_000);
		}
		return [$process, $pid];
	}

	/**
	 * Returns the id of a process that has ended.
	 */
	private function endedPid(): int
	{
		[$process, $pid] = $this->startSleepingChild();
		proc_terminate($process, 9);
		proc_close($process);
		return $pid;
	}

	public function testForkNotForkable()
	{
		self::expectException(TNotSupportedException::class);
		TProcessHelperTestUnforkable::fork();
	}

	public function testForkCaptureForkLog()
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl_fork is not available on this platform.');
		}
		$app = Prado::getApplication();
		$attached = (bool) $app->asa(TCaptureForkLog::class);
		try {
			foreach ([1, 2] as $round) {
				$pid = TProcessHelper::fork(true);
				if ($pid === 0) {
					exit(0);
				}
				self::assertGreaterThan(0, $pid);
				self::assertNotNull($app->asa(TCaptureForkLog::class), "The fork log is not captured in round $round.");
				pcntl_waitpid($pid, $status);
				$app->receiveLogsFromChildren($pid);
			}
		} finally {
			if (!$attached) {
				$app->detachBehavior(TCaptureForkLog::BEHAVIOR_NAME);
			}
		}
	}

	public function testWatchChildSignalWithDispatcher()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		$dispatcher = TSignalsDispatcher::singleton(false);
		if ($dispatcher === null) {
			$dispatcher = $this->dispatcher = TSignalsDispatcher::singleton();
		}
		$deferred = null;
		$loop = $this->createMock(IEventLoop::class);
		$loop->expects(self::once())->method('defer')->willReturnCallback(function ($callback) use (&$deferred) {
			$deferred = $callback;
			return 'deferred';
		});
		$loop->expects(self::never())->method('onSignal');
		$before = $dispatcher->getEventHandlers(TSignalsDispatcher::FX_SIGNAL_CHILD)->toArray();
		$called = 0;
		$unwatch = TProcessHelper::watchChildSignal($loop, function () use (&$called) {
			$called++;
		});
		try {
			$added = array_values(array_filter(
				$dispatcher->getEventHandlers(TSignalsDispatcher::FX_SIGNAL_CHILD)->toArray(),
				fn ($handler) => !in_array($handler, $before, true)
			));
			self::assertCount(1, $added);
			($added[0])($dispatcher, null);
			self::assertSame(0, $called, 'The callback runs from the loop, not the signal handler.');
			$deferred();
			self::assertSame(1, $called);
		} finally {
			$unwatch();
		}
		self::assertSame($before, $dispatcher->getEventHandlers(TSignalsDispatcher::FX_SIGNAL_CHILD)->toArray());
	}

	public function testWatchChildSignalWithoutDispatcher()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped('pcntl signals are not available on this platform.');
		}
		if (TSignalsDispatcher::singleton(false) !== null) {
			$this->markTestSkipped('A signals dispatcher is attached.');
		}
		$signalCallback = null;
		$loop = $this->createMock(IEventLoop::class);
		$loop->expects(self::once())->method('onSignal')->willReturnCallback(function ($signal, $callback) use (&$signalCallback) {
			self::assertSame(SIGCHLD, $signal);
			$signalCallback = $callback;
			return 'signal-id';
		});
		$loop->expects(self::once())->method('cancel')->with('signal-id');
		$called = 0;
		$unwatch = TProcessHelper::watchChildSignal($loop, function () use (&$called) {
			$called++;
		});
		$signalCallback();
		self::assertSame(1, $called);
		$unwatch();
	}

	public function testExitStatus()
	{
		if (!function_exists('pcntl_wifexited')) {
			self::assertSame(3, TProcessHelper::exitStatus(3));
			return;
		}
		$pid = pcntl_fork();
		if ($pid === 0) {
			exit(3);
		}
		pcntl_waitpid($pid, $exited);
		self::assertSame(3, TProcessHelper::exitStatus($exited));

		$pid = pcntl_fork();
		if ($pid === 0) {
			posix_kill(getmypid(), SIGKILL);
			exit(0);
		}
		pcntl_waitpid($pid, $signaled);
		self::assertFalse(pcntl_wifexited($signaled));
		self::assertSame($signaled, TProcessHelper::exitStatus($signaled));
	}

	public function testFilterCommand()
	{
		$expected = PHP_BINARY . ' -v';
		self::assertSame(TProcessHelper::isSystemWindows() ? '"' . $expected . '"' : $expected, TProcessHelper::filterCommand('@php -v'));
		self::assertSame([PHP_BINARY, '-v'], TProcessHelper::filterCommand(['@php', '-v']));
	}

	public function testFilterCommandWindows()
	{
		self::assertSame('"' . PHP_BINARY . ' -v"', TProcessHelperTestWindows::filterCommand('@php -v'));
		self::assertSame([PHP_BINARY, '-v'], TProcessHelperTestWindows::filterCommand(['@php', '-v']));
	}

	public function testSendSignalWindows()
	{
		self::expectException(TNotSupportedException::class);
		TProcessHelperTestWindows::sendSignal(0);
	}

	public function testEscapeShellArgWindows()
	{
		self::assertSame('""', TProcessHelperTestWindows::escapeShellArg(''));
		self::assertSame('"a b"', TProcessHelperTestWindows::escapeShellArg('a b'));
		self::assertSame('"\"quoted\""', TProcessHelperTestWindows::escapeShellArg('"quoted"'));
		self::assertSame('\"', TProcessHelperTestWindows::escapeShellArg('"'));
		self::assertSame('^%"PATH"^%', TProcessHelperTestWindows::escapeShellArg('%PATH%'));
		self::assertSame('"dir\\\\"', TProcessHelperTestWindows::escapeShellArg('dir\\'));
		self::assertSame('"a\"^%"X"^%"', TProcessHelperTestWindows::escapeShellArg('a"%X%'));
	}

	public function testProcessPriorityOfEndedProcess()
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('Windows reads the priority through PowerShell.');
		}
		$pid = $this->endedPid();
		self::assertNull(TProcessHelper::getProcessPriority($pid));
		self::assertFalse(TProcessHelper::setProcessPriority(5, $pid));
	}

	public function testSetProcessPriorityDenied()
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('Windows sets the priority through PowerShell.');
		}
		if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
			$this->markTestSkipped('The superuser may raise a priority.');
		}
		[$process, $pid] = $this->startSleepingChild();
		try {
			$priority = TProcessHelper::getProcessPriority($pid);
			self::assertFalse(TProcessHelper::setProcessPriority($priority - 5, $pid));
			self::assertSame($priority, TProcessHelper::getProcessPriority($pid));
		} finally {
			proc_terminate($process, 9);
			proc_close($process);
		}
	}

	public function testProcessPriorityCurrentProcess()
	{
		$priority = TProcessHelper::getProcessPriority(getmypid());
		self::assertSame($priority, TProcessHelper::getProcessPriority());
		self::assertTrue(TProcessHelper::setProcessPriority($priority));
		self::assertSame($priority, TProcessHelper::getProcessPriority());
	}
}
