<?php

use Prado\IO\Process\TPipeStream;
use Prado\IO\Process\TProcess;
use Psr\Http\Message\StreamInterface;

class TPipeStreamTest extends PHPUnit\Framework\TestCase
{
	public function testPopenReadCapabilitiesAndOutput()
	{
		$p = TPipeStream::popen(PHP_BINARY . ' -r "echo \"output\";"', 'r');
		self::assertInstanceOf(StreamInterface::class, $p);
		self::assertTrue($p->getIsCommandPipe());
		self::assertTrue($p->isReadable());
		self::assertFalse($p->isWritable());
		self::assertFalse($p->isSeekable(), 'Pipes are not seekable.');
		self::assertSame('output', $p->getContents());
		$p->close();
		self::assertSame(0, $p->getExitCode());
	}

	public function testPopenWriteFeedsCommandInput()
	{
		// 'w' pipe: the test writes to the command's STDIN; the command swallows it.
		$p = TPipeStream::popen(PHP_BINARY . ' -r "stream_get_contents(STDIN);"', 'w');
		self::assertTrue($p->getIsCommandPipe());
		self::assertTrue($p->isWritable());
		self::assertFalse($p->isReadable());
		self::assertSame(5, $p->write('hello'));
		$p->close();
		self::assertSame(0, $p->getExitCode());
	}

	public function testExitCodeNullUntilCloseThenCaptured()
	{
		$p = TPipeStream::popen(PHP_BINARY . ' -r "exit(3);"', 'r');
		$p->getContents();
		self::assertNull($p->getExitCode(), 'Exit code is null until the pipe is closed.');
		$p->close();
		self::assertSame(3, $p->getExitCode(), 'pclose() captures the command exit code.');
	}

	public function testSeekThrows()
	{
		$p = TPipeStream::popen(PHP_BINARY . ' -r "echo 1;"', 'r');
		try {
			$p->seek(0);
			self::fail('A non-seekable pipe must throw on seek.');
		} catch (\RuntimeException $e) {
			self::assertTrue(true);
		}
		$p->getContents();
		$p->close();
	}

	public function testSerializationKeepsExitCodeAndZapsHandle()
	{
		$p = TPipeStream::popen(PHP_BINARY . ' -r "exit(0);"', 'r');
		$p->getContents();
		$p->close();
		$revived = unserialize(serialize($p));
		self::assertInstanceOf(TPipeStream::class, $revived);
		self::assertFalse($revived->isOpen(), 'Revived pipe holds no handle.');
		self::assertSame(0, $revived->getExitCode(), 'Captured exit code survives serialization.');
	}

	public function testDoubleCloseIsSafe()
	{
		$p = TPipeStream::popen(PHP_BINARY . ' -r "echo 1;"', 'r');
		$p->getContents();
		self::assertTrue($p->closeStream());
		self::assertNull($p->closeStream(), 'Second close returns null.');
	}

	public function testDescriptorPipeIsNotACommandPipe()
	{
		// A proc_open descriptor pipe closes with fclose (not pclose) and captures no exit
		// code of its own; its owning TProcess reports the process exit code.
		$proc = TProcess::open([PHP_BINARY, '-r', 'echo "x";']);
		$stdout = $proc->getStdout();
		self::assertInstanceOf(TPipeStream::class, $stdout);
		self::assertFalse($stdout->getIsCommandPipe(), 'proc_open pipes are not command pipes.');
		self::assertNull($stdout->getExitCode());
		self::assertSame('x', $stdout->getContents());
		$proc->close();
	}
}
