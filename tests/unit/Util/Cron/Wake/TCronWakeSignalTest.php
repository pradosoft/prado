<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\IO\Async\TAsync;
use Prado\Util\Cron\Wake\TCronWakeSignal;

class TCronWakeSignalTest extends \PHPUnit\Framework\TestCase
{
	private string $pidFile;

	protected function setUp(): void
	{
		if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
			self::markTestSkipped('pcntl and posix are required');
		}
		$this->pidFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-test-' . bin2hex(random_bytes(4)) . '.pid';
	}

	protected function tearDown(): void
	{
		if (isset($this->pidFile)) {
			@unlink($this->pidFile);
		}
	}

	public function testProperties()
	{
		$t = new TCronWakeSignal($this->pidFile);
		self::assertSame($this->pidFile, $t->getPidFile());
		self::assertSame(SIGUSR1, $t->getSignal());
		self::assertSame($t, $t->setSignal((string) SIGUSR2));
		self::assertSame(SIGUSR2, $t->getSignal());
		self::assertNull($t->getPid(), 'no pid file yet');
		$t = new TCronWakeSignal();
		self::assertStringEndsWith(DIRECTORY_SEPARATOR . TCronWakeSignal::DEFAULT_PID_FILE_NAME, $t->getPidFile());
		$t->setPidFile($this->pidFile);
		self::assertSame($this->pidFile, $t->getPidFile());
	}

	public function testSignalWithoutAWaitingProcessIsANoOp()
	{
		$t = new TCronWakeSignal($this->pidFile);
		self::assertFalse($t->signal(microtime(true)), 'no pid file');
		file_put_contents($this->pidFile, '999999999');
		self::assertFalse($t->signal(microtime(true)), 'no such process');
	}

	public function testWaitRecordsThePidAndASignalBetweenWaitsIsCounted()
	{
		$t = new TCronWakeSignal($this->pidFile);
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.01, $now), 'no signal: the wait times out');
		self::assertSame(getmypid(), $t->getPid());

		// A signal arrives while the process is busy between waits; the next wait consumes it at once.
		self::assertTrue($t->signal($now));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start));
		self::assertLessThan(1.0, microtime(true) - $start);

		// A signal during the wait ends it.
		TAsync::getLoop()->delay(0.03, static fn () => $t->signal(microtime(true)));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start));
		self::assertLessThan(1.0, microtime(true) - $start);

		$t->close();
		self::assertFileDoesNotExist($this->pidFile, 'close removes this process\'s pid file');
	}

	public function testWaitInstallsTheHandlerBeforeItRecordsThePid()
	{
		$t = new class ($this->pidFile) extends TCronWakeSignal {
			public mixed $handlerAtWrite = null;

			protected function writePid(): bool
			{
				$this->handlerAtWrite = pcntl_signal_get_handler($this->getSignal());
				return parent::writePid();
			}
		};
		$now = microtime(true);
		try {
			self::assertFalse($t->wait($now, $now));
			self::assertInstanceOf(\Closure::class, $t->handlerAtWrite, 'a signal sent once the pid file names this process is handled');
		} finally {
			$t->close();
		}
	}

	public function testWatchDeliversTheSignalOnTheLoop()
	{
		$t = new TCronWakeSignal($this->pidFile);
		$loop = TAsync::getLoop();
		$wakes = 0;
		$suspension = $loop->suspension();
		$resumed = false;
		$resume = static function () use ($suspension, &$resumed) {
			if (!$resumed) {
				$resumed = true;
				$suspension->resume();
			}
		};
		self::assertTrue($t->watch($loop, static function () use (&$wakes, $resume) {
			$wakes++;
			$resume();
		}));
		self::assertSame(getmypid(), $t->getPid());
		$loop->delay(0.02, static fn () => $t->signal(microtime(true)));
		$timeout = $loop->delay(2.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->close();
		self::assertSame(1, $wakes);
	}

	public function testUnavailableAndALoopWithoutSignals()
	{
		$t = new class ($this->pidFile) extends TCronWakeSignal {
			protected function getIsAvailable(): bool
			{
				return false;
			}
		};
		$now = microtime(true);
		file_put_contents($this->pidFile, (string) getmypid());
		self::assertFalse($t->signal($now), 'an unavailable transport does not signal the pid');
		self::assertNull($t->wait($now + 1, $now));
		self::assertFalse($t->watch(TAsync::getLoop(), static fn () => null));
		$t->close();
		self::assertFileExists($this->pidFile, 'an unavailable transport leaves the pid file');

		$loop = $this->createMock(\Prado\IO\Async\IEventLoop::class);
		$loop->method('onSignal')->willThrowException(new \Prado\Exceptions\TNotSupportedException('eventloop_signals_unsupported'));
		$t = new TCronWakeSignal($this->pidFile);
		self::assertFalse($t->watch($loop, static fn () => null), 'a loop without signals cannot watch');
	}

	public function testAPidFileWithoutAPid()
	{
		$t = new TCronWakeSignal($this->pidFile);
		file_put_contents($this->pidFile, 'not a pid');
		self::assertNull($t->getPid());
		file_put_contents($this->pidFile, '0');
		self::assertNull($t->getPid());
	}

	public function testCloseKeepsThePidFileOfAnotherProcess()
	{
		$t = new TCronWakeSignal($this->pidFile);
		file_put_contents($this->pidFile, '999999999');
		$t->close();
		self::assertSame(999999999, $t->getPid(), 'the pid file of another process stays');
	}
}
