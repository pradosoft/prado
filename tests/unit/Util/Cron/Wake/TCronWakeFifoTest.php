<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\IO\Async\TAsync;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Cron\Wake\TCronWakeFifo;

/**
 * A stream whose reads return the scripted {@see $reads} in order, then false, a read error.
 */
class TCronWakeFifoTestStream
{
	public const PROTOCOL = 'prado-cronwake-fifo-test';

	/** @var array<int, false|string> the results of the next reads */
	public static array $reads = [];

	/** @var resource the stream context */
	public $context;

	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		return true;
	}

	public function stream_read(int $count): false|string
	{
		return array_shift(self::$reads) ?? false;
	}

	public function stream_eof(): bool
	{
		return false;
	}
}

class TCronWakeFifoTest extends \PHPUnit\Framework\TestCase
{
	private string $fifo;

	protected function setUp(): void
	{
		if (!function_exists('posix_mkfifo')) {
			self::markTestSkipped('posix_mkfifo is required');
		}
		$this->fifo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-test-' . bin2hex(random_bytes(4)) . '.fifo';
	}

	protected function tearDown(): void
	{
		if (isset($this->fifo)) {
			@unlink($this->fifo);
		}
	}

	public function testProperties()
	{
		$t = new TCronWakeFifo($this->fifo);
		self::assertSame($this->fifo, $t->getFifoFile());
		$t = new TCronWakeFifo();
		self::assertStringEndsWith(DIRECTORY_SEPARATOR . TCronWakeFifo::DEFAULT_FIFO_NAME, $t->getFifoFile());
		$t->setFifoFile($this->fifo);
		self::assertSame($this->fifo, $t->getFifoFile());
	}

	public function testSignalCreatesThePipeAndWaitReadsIt()
	{
		$t = new TCronWakeFifo($this->fifo);
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.01, $now), 'no signal: the wait times out');
		self::assertSame('fifo', filetype($this->fifo));

		// A byte written while the waiting process is busy stays in the pipe it holds open.
		self::assertTrue($t->signal($now));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start));
		self::assertLessThan(1.0, microtime(true) - $start);

		// A byte written by another handle (another process's transport) is read by the held pipe.
		$other = new TCronWakeFifo($this->fifo);
		self::assertTrue($other->signal($start));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start));
		self::assertLessThan(1.0, microtime(true) - $start);
		$t->close();
	}

	public function testWatchDeliversWakesOnTheLoop()
	{
		$t = new TCronWakeFifo($this->fifo);
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
		$other = new TCronWakeFifo($this->fifo);
		$loop->delay(0.02, static fn () => $other->signal(microtime(true)));
		$timeout = $loop->delay(2.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->close();
		self::assertSame(1, $wakes);
	}

	public function testUnavailableWithoutTheDirectory()
	{
		$t = new TCronWakeFifo(sys_get_temp_dir() . '/no-such-dir-' . bin2hex(random_bytes(4)) . '/cronwake.fifo');
		$now = microtime(true);
		self::assertFalse($t->signal($now));
		self::assertNull($t->wait($now + 1, $now));
		self::assertFalse($t->watch(TAsync::getLoop(), static fn () => null));
	}

	public function testAPlainFileIsNoPipe()
	{
		file_put_contents($this->fifo, 'not a pipe');
		$t = new TCronWakeFifo($this->fifo);
		$now = microtime(true);
		self::assertFalse($t->signal($now));
		self::assertNull($t->wait($now + 1, $now));
	}

	public function testAPipeThatCannotBeMadeOrOpened()
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-ro-' . bin2hex(random_bytes(4));
		mkdir($dir);
		chmod($dir, 0o500);
		try {
			$t = new TCronWakeFifo($dir . DIRECTORY_SEPARATOR . 'cronwake.fifo');
			if (@file_put_contents($dir . DIRECTORY_SEPARATOR . 'probe', '') !== false) {
				self::markTestSkipped('the process can write to a read-only directory');
			}
			self::assertFalse($t->signal(microtime(true)), 'a pipe that cannot be made');
		} finally {
			chmod($dir, 0o700);
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $left) {
				unlink($left);
			}
			rmdir($dir);
		}
		posix_mkfifo($this->fifo, 0o000);
		$t = new TCronWakeFifo($this->fifo);
		self::assertFalse($t->signal(microtime(true)), 'a pipe that cannot be opened');
	}

	public function testAWaitAlreadyPastDrainsTheQueuedWakes()
	{
		$t = new TCronWakeFifo($this->fifo);
		$now = microtime(true);
		self::assertFalse($t->wait($now - 1, $now), 'nothing queued');
		self::assertTrue($t->signal($now));
		self::assertTrue($t->wait($now - 1, $now), 'a queued wake is consumed at once');
		$t->close();
	}

	public function testCloseWithoutAnOpenPipe()
	{
		$t = new TCronWakeFifo($this->fifo);
		$t->close();
		self::assertNull(PradoUnit::getProp($t, '_pipe'));
		self::assertFileDoesNotExist($this->fifo, 'nothing was opened or made');
	}

	public function testWatchOnAPlainFile()
	{
		file_put_contents($this->fifo, 'not a pipe');
		$t = new TCronWakeFifo($this->fifo);
		self::assertFalse($t->watch(TAsync::getLoop(), static fn () => null), 'a plain file is no pipe to watch');
	}

	public function testANewPipeThatCannotBeOpened()
	{
		if (posix_geteuid() === 0) {
			self::markTestSkipped('root opens a read-only pipe for writing');
		}
		$umask = umask(0o277);
		try {
			$t = new TCronWakeFifo($this->fifo);
			self::assertFalse($t->signal(microtime(true)), 'the umask makes the new pipe read-only, so it cannot be opened for writing');
			self::assertSame('fifo', filetype($this->fifo));
		} finally {
			umask($umask);
		}
	}

	public function testDrainStopsAtAReadError()
	{
		stream_wrapper_register(TCronWakeFifoTestStream::PROTOCOL, TCronWakeFifoTestStream::class);
		try {
			$t = new TCronWakeFifo($this->fifo);
			TCronWakeFifoTestStream::$reads = ['1', false];
			PradoUnit::setProp($t, '_pipe', fopen(TCronWakeFifoTestStream::PROTOCOL . '://pipe', 'r+'));
			self::assertTrue(PradoUnit::invoke($t, 'drain'), 'the bytes before the error are read');
			TCronWakeFifoTestStream::$reads = [false];
			self::assertFalse(PradoUnit::invoke($t, 'drain'), 'an error at once reads nothing');
			$t->close();
		} finally {
			TCronWakeFifoTestStream::$reads = [];
			stream_wrapper_unregister(TCronWakeFifoTestStream::PROTOCOL);
		}
	}
}
