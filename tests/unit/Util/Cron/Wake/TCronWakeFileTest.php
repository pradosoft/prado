<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\Util\Cron\Wake\TCronWakeFile;

class TCronWakeFileTest extends \PHPUnit\Framework\TestCase
{
	private string $file;

	protected function setUp(): void
	{
		$this->file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-test-' . bin2hex(random_bytes(4));
	}

	protected function tearDown(): void
	{
		@unlink($this->file);
	}

	public function testPollInterval()
	{
		$t = new TCronWakeFile($this->file);
		self::assertSame(TCronWakeFile::DEFAULT_POLL_INTERVAL, $t->getPollInterval());
		self::assertSame($t, $t->setPollInterval('0.25'));
		self::assertSame(0.25, $t->getPollInterval());
		self::expectException(TInvalidDataValueException::class);
		$t->setPollInterval(0);
	}

	public function testWakeFileDefaultsToTheRuntimePath()
	{
		$t = new TCronWakeFile();
		self::assertStringEndsWith(DIRECTORY_SEPARATOR . TCronWakeFile::DEFAULT_FILE_NAME, $t->getWakeFile());
		$t->setWakeFile($this->file);
		self::assertSame($this->file, $t->getWakeFile());
	}

	public function testSignalWritesTheStampAndWaitDetectsIt()
	{
		$t = (new TCronWakeFile($this->file))->setPollInterval(0.02);
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.05, $now), 'no signal: the wait times out');

		TAsync::getLoop()->delay(0.03, static fn () => $t->signal(microtime(true)));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 2.0, $start), 'a signal during the wait wakes it');
		self::assertLessThan(1.0, microtime(true) - $start);
		self::assertFileExists($this->file);
	}

	public function testWatchCallsBackOnEachSignal()
	{
		$t = (new TCronWakeFile($this->file))->setPollInterval(0.02);
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
		$loop->delay(0.03, static fn () => $t->signal(microtime(true)));
		$timeout = $loop->delay(2.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->unwatch();
		self::assertSame(1, $wakes);
		$t->close();
	}

	public function testUnavailableWithoutTheDirectory()
	{
		$t = new TCronWakeFile(sys_get_temp_dir() . '/no-such-dir-' . bin2hex(random_bytes(4)) . '/cronwake');
		$now = microtime(true);
		self::assertFalse($t->signal($now));
		self::assertNull($t->wait($now + 1, $now));
		self::assertFalse($t->watch(TAsync::getLoop(), static fn () => null));
	}
}
