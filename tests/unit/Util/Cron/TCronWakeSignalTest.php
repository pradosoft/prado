<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\IO\Async\TAsync;
use Prado\Util\Cron\TCronWakeSignal;

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
}
