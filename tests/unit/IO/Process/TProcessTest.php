<?php

use Prado\IO\Process\TPipeStream;
use Prado\IO\Process\TProcess;
use Prado\IO\Process\TProcessStatus;
use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

class TProcessTest extends PHPUnit\Framework\TestCase
{
	public function testProcessReadStdout()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "hello stdout";']);
		self::assertInstanceOf(TPipeStream::class, $p->getStdout());
		self::assertSame('hello stdout', $p->getStdout()->getContents());
		$p->close();
		self::assertSame(0, $p->getExitCode());
	}

	public function testProcessWriteStdinReadStdout()
	{
		// cat-like: read stdin, echo it back.
		$p = TProcess::open([PHP_BINARY, '-r', 'echo strtoupper(stream_get_contents(STDIN));']);
		$p->getStdin()->write('shout');
		$p->getStdin()->close();   // EOF to the child
		self::assertSame('SHOUT', $p->getStdout()->getContents());
		$p->close();
	}

	public function testProcessStderr()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'fwrite(STDERR, "oops");']);
		self::assertSame('oops', $p->getStderr()->getContents());
		$p->close();
	}

	public function testDefaultDescriptorConstants()
	{
		self::assertSame(['pipe', 'r'], TProcess::DEFAULT_STDIN);
		self::assertSame(['pipe', 'w'], TProcess::DEFAULT_STDOUT);
		self::assertSame(['pipe', 'w'], TProcess::DEFAULT_STDERR);
		self::assertSame(
			[0 => TProcess::DEFAULT_STDIN, 1 => TProcess::DEFAULT_STDOUT, 2 => TProcess::DEFAULT_STDERR],
			TProcess::defaultDescriptors(),
		);
	}

	public function testSubclassOverridesDefaultsAndOpenReturnsSubclass()
	{
		// Late static binding: a subclass's overridden constant flows through
		// defaultDescriptors(), and open() returns the subclass type.
		self::assertSame(['file', '/dev/null', 'w'], TCustomProcess::defaultDescriptors()[1]);
		$p = TCustomProcess::open([PHP_BINARY, '-r', 'fwrite(STDERR, "e");'], TProcess::defaultDescriptors());
		self::assertInstanceOf(TCustomProcess::class, $p);
		$p->getStderr()->getContents();
		$p->close();
	}

	public function testGetPidAndCommandWhileRunning()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'usleep(200000);']);
		self::assertGreaterThan(0, $p->getPid());
		self::assertStringContainsString('-r', $p->getCommand());
		self::assertTrue($p->getIsRunning());
		$p->close();
		self::assertNull($p->getPid(), 'pid is null once closed.');
	}

	public function testExitCodeStableAcrossRepeatedStatusCalls()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'exit(7);']);
		$p->wait();
		// Repeated reads must keep reporting the captured exit code (proc_get_status
		// only returns it on the first call after exit).
		self::assertSame(7, $p->getStatus()->getExitCode());
		self::assertSame(7, $p->getStatus()->getExitCode());
		self::assertSame(7, $p->getExitCode());
		$p->close();
	}

	public function testTerminate()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'sleep(30);']);
		self::assertTrue($p->getIsRunning());
		self::assertTrue($p->terminate());
		$p->wait();
		self::assertFalse($p->getIsRunning());
		$p->close();
	}

	public function testPipeIsCommandVsDescriptor()
	{
		$cmd = TPipeStream::popen(PHP_BINARY . ' -r "echo 1;"', 'r');
		self::assertTrue($cmd->getIsCommandPipe());
		$cmd->close();

		$p = TProcess::open([PHP_BINARY, '-r', 'echo "x";']);
		self::assertFalse($p->getStdout()->getIsCommandPipe(), 'proc_open pipes are not command pipes.');
		self::assertNull($p->getStdout()->getExitCode());
		$p->getStdout()->getContents();
		$p->close();
	}

	public function testCloseStreamReturnsBoolAndIdempotent()
	{
		// close() is PSR-shaped (void); closeStream() carries the boolean result.
		$p = TProcess::open([PHP_BINARY, '-r', 'exit(0);']);
		$p->wait();
		self::assertIsBool($p->closeStream());
		self::assertNull($p->closeStream(), 'second close returns null.');
	}

	public function testStatusAndExitCode()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'exit(3);']);
		$code = $p->wait();
		self::assertSame(3, $code);
		$status = $p->getStatus();
		self::assertInstanceOf(TProcessStatus::class, $status);
		self::assertFalse($status->getRunning());
		self::assertSame(3, $p->getExitCode());
		$p->close();
	}

	public function testPipesWorkTogetherWithOtherProcessPipes()
	{
		// Producer writes three lines to stdout.
		$producer = TProcess::open([PHP_BINARY, '-r', 'echo "alpha\ntxtfile\nbeta\n";']);

		// Consumer's STDIN is wired DIRECTLY to the producer's stdout pipe.
		$consumerCode = 'foreach (explode("\n", stream_get_contents(STDIN)) as $l) { if (strpos($l, "txt") !== false) { echo $l . "\n"; } }';
		$consumer = TProcess::open(
			[PHP_BINARY, '-r', $consumerCode],
			[
				0 => $producer->getStdout(),   // <-- another process's pipe as a descriptor
				1 => TProcess::DEFAULT_STDOUT,
				2 => TProcess::DEFAULT_STDERR,
			]
		);

		$filtered = $consumer->getStdout()->getContents();
		self::assertSame("txtfile\n", $filtered, 'Consumer received the producer pipe and filtered it.');
		$consumer->close();
		$producer->close();
	}

	public function testDescriptorAcceptsPsrStream()
	{
		// A non-pipe StreamInterface can be used as a descriptor (via asResource bridge).
		$infile = TStream::fromString('from-a-stream');
		$p = TProcess::open(
			[PHP_BINARY, '-r', 'echo stream_get_contents(STDIN);'],
			[0 => $infile, 1 => TProcess::DEFAULT_STDOUT, 2 => TProcess::DEFAULT_STDERR]
		);
		self::assertSame('from-a-stream', $p->getStdout()->getContents());
		$p->close();
		$infile->close();
	}

	public function testArrayAccessReadsPipes()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "via-offset";']);
		self::assertTrue(isset($p[1]));
		self::assertFalse(isset($p[5]));
		self::assertInstanceOf(TPipeStream::class, $p[1]);
		self::assertSame($p->getStdout(), $p[1]);
		self::assertSame('via-offset', $p[1]->getContents());
		$p->close();
	}

	public function testArrayAccessWriteStdin()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo strtoupper(stream_get_contents(STDIN));']);
		$p[0] = 'whisper';            // write to stdin via offset
		$p[0]->close();               // EOF to the child
		self::assertSame('WHISPER', $p[1]->getContents());
		$p->close();
	}

	public function testArrayAccessUnsetClosesPipe()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "x";']);
		self::assertTrue(isset($p[0]));
		unset($p[0]);
		self::assertFalse(isset($p[0]));
		$p->close();
	}

	public function testCountableAndIterable()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "y";']);
		self::assertCount(3, $p);                 // stdin, stdout, stderr
		$fds = [];
		foreach ($p as $fd => $pipe) {
			self::assertInstanceOf(TPipeStream::class, $pipe);
			$fds[] = $fd;
		}
		self::assertSame([0, 1, 2], $fds);
		$p->close();
	}

	public function testArrayAccessInvalidOffsetThrows()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "z";']);
		try {
			self::expectException(\Prado\Exceptions\TInvalidDataValueException::class);
			$p['bogus'];
		} finally {
			$p->close();
		}
	}

	public function testTerminateRecordsSignalExitCode()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'sleep(30);']);
		self::assertTrue($p->terminate());   // SIGTERM (15)
		$p->wait();
		$status = $p->getStatus();
		self::assertTrue($status->getSignaled(), 'A killed process reports signaled.');
		self::assertSame(15, $status->getTermSig());
		self::assertSame(143, $p->getExitCode(), 'Signal exit is 128 + signal number.');
		$p->close();
	}

	public function testExitCodeNullWhileRunning()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'usleep(300000);']);
		self::assertNull($p->getExitCode(), 'No exit code while running.');
		$p->terminate();
		$p->wait();
		$p->close();
	}

	public function testGetStatusAfterClose()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'exit(0);']);
		$p->wait();
		$p->close();
		$status = $p->getStatus();
		self::assertInstanceOf(TProcessStatus::class, $status);
		self::assertFalse($status->getRunning());
		self::assertSame(0, $status->getExitCode());
	}

	public function testSerializationPreservesExitCodeAndZapsHandle()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'exit(5);']);
		$p->wait();
		$revived = unserialize(serialize($p));
		self::assertInstanceOf(TProcess::class, $revived);
		self::assertSame(5, $revived->getExitCode(), 'Captured exit code survives serialization.');
		self::assertFalse($revived->isOpen(), 'Revived process holds no handle.');
		self::assertCount(0, $revived, 'Revived process holds no pipes.');
		$p->close();
	}

	public function testWaitTimeoutReturnsMinusOne()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'usleep(500000);']);
		self::assertSame(-1, $p->wait(1000, 20000), 'wait() returns -1 when the timeout elapses.');
		self::assertTrue($p->getIsRunning(), 'Process still running after the timeout.');
		$p->terminate();
		$p->wait();
		$p->close();
	}

	public function testCloneHasNoPipesAndDoesNotCloseOriginal()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "orig";']);
		$copy = clone $p;
		self::assertCount(0, $copy, 'Clone owns no pipes.');
		self::assertNull($copy->getStdout());
		self::assertFalse($copy->closeStream(), 'Clone is non-owning; closing it is a no-op for the handle.');
		self::assertSame('orig', $p->getStdout()->getContents(), 'Original pipes are untouched by the clone close.');
		$p->close();
	}

	public function testCountAndIterationAfterUnset()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "u";']);
		self::assertCount(3, $p);
		unset($p[TProcess::STDIN]);
		self::assertCount(2, $p);
		self::assertSame([1, 2], array_keys(iterator_to_array($p)));
		$p->getStdout()->getContents();
		$p->close();
	}

	public function testRawResourceDescriptor()
	{
		$res = fopen('php://temp', 'r+b');
		fwrite($res, 'raw-fd');
		rewind($res);
		$p = TProcess::open(
			[PHP_BINARY, '-r', 'echo stream_get_contents(STDIN);'],
			[0 => $res, 1 => TProcess::DEFAULT_STDOUT, 2 => TProcess::DEFAULT_STDERR]
		);
		self::assertSame('raw-fd', $p->getStdout()->getContents());
		$p->close();
		if (is_resource($res)) {
			fclose($res);
		}
	}

	public function testClosedResourceDescriptorThrows()
	{
		$closed = TStream::fromString('x');
		$closed->close();   // IResource with no open handle
		self::expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		TProcess::open(
			[PHP_BINARY, '-r', 'echo "no";'],
			[0 => $closed, 1 => TProcess::DEFAULT_STDOUT, 2 => TProcess::DEFAULT_STDERR]
		);
	}

	public function testOffsetGetAbsentNumericReturnsNull()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "a";']);
		self::assertNull($p[5], 'An absent numeric offset returns null.');
		$p->getStdout()->getContents();
		$p->close();
	}

	public function testWriteToReadOnlyPipeThrows()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "ro";']);
		try {
			self::expectException(\RuntimeException::class);
			$p[TProcess::STDOUT] = 'nope';   // stdout is read-only on the parent side
		} finally {
			$p->getStdout()->getContents();
			$p->close();
		}
	}

	public function testOffsetSetNonNumericThrows()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "s";']);
		try {
			self::expectException(\Prado\Exceptions\TInvalidDataValueException::class);
			$p['bad'] = 'x';
		} finally {
			$p->getStdout()->getContents();
			$p->close();
		}
	}

	public function testOffsetUnsetAbsentThrows()
	{
		$p = TProcess::open([PHP_BINARY, '-r', 'echo "x";']);
		try {
			self::expectException(\Prado\Exceptions\TInvalidDataValueException::class);
			unset($p[9]);
		} finally {
			$p->getStdout()->getContents();
			$p->close();
		}
	}
}

/**
 * Subclass that overrides a default descriptor, to exercise late static binding.
 */
class TCustomProcess extends TProcess
{
	public const DEFAULT_STDOUT = ['file', '/dev/null', 'w'];
}
