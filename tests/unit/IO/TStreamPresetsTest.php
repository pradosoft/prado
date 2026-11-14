<?php

namespace Prado\Test\Unit\IO;

use Prado\Exceptions\TIOException;
use Prado\IO\TFileStream;
use Prado\IO\TInputStream;
use Prado\IO\TMemoryStream;
use Prado\IO\TOutputStream;
use Prado\IO\TStdErrStream;
use Prado\IO\TStdInStream;
use Prado\IO\TStdOutStream;
use Prado\IO\TStream;
use Prado\IO\TTempStream;
use Prado\Test\Unit\Harness\IO\TTestIOHelper;

class TStreamPresetsTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testFileStream()
	{
		$path = TTestIOHelper::tempFile('file-data', 'prado-tfs');
		$s = new TFileStream($path, 'r+b');
		self::assertInstanceOf(TStream::class, $s);
		self::assertTrue($s->getOwnsResource());
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		self::assertSame('file-data', $s->getContents());
		self::assertSame($path, $s->getURI());
		$s->close();
	}

	public function testFileStreamMissingThrows()
	{
		self::expectException(TIOException::class);
		new TFileStream('/no/such/dir/missing.bin', 'rb');
	}

	public function testMemoryStream()
	{
		$s = new TMemoryStream();
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		self::assertTrue($s->getOwnsResource());
		$s->write('mem');
		$s->seek(0);
		self::assertSame('mem', $s->getContents());
		$s->close();
	}

	public function testTempStream()
	{
		$s = new TTempStream(1024);
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		$s->write('temp-data');
		$s->seek(0);
		self::assertSame('temp-data', $s->read(9));
		$s->close();
	}

	/**
	 * The writability of STDIN follows the descriptor the run was started with, so
	 * {@see testStdStreamCapabilitiesFollowTheDescriptor()} checks it in a child process.
	 */
	public function testStdInStream()
	{
		$s = new TStdInStream();
		self::assertFalse($s->getOwnsResource(), 'STDIN must be non-owning.');
		self::assertTrue($s->isReadable());
		self::assertSame(TStdInStream::URI, $s->getURI());
		// Do not read (would block); just verify closing leaves the fd intact.
		$resource = $s->getResource();
		$s->close();
		self::assertTrue(is_resource($resource), 'Non-owning close must leave STDIN open.');
	}

	public function testStdOutStreamIsNonOwningWritableFlushable()
	{
		$s = new TStdOutStream();
		self::assertFalse($s->getOwnsResource(), 'STDOUT must be non-owning.');
		self::assertTrue($s->isWritable());
		self::assertTrue(method_exists($s, 'flush'));
		$resource = $s->getResource();
		$s->close();
		self::assertTrue(is_resource($resource), 'Non-owning close must leave STDOUT open.');
	}

	public function testStdErrStreamIsNonOwningWriteOnly()
	{
		$s = new TStdErrStream();
		self::assertFalse($s->getOwnsResource(), 'STDERR must be non-owning.');
		self::assertTrue($s->isWritable());
		self::assertSame(TStdErrStream::URI, $s->getURI());
		$resource = $s->getResource();
		$s->close();
		self::assertTrue(is_resource($resource), 'Non-owning close must leave STDERR open.');
	}

	/**
	 * The standard streams take their capabilities from the process descriptors.  A
	 * child process with pipes for its descriptors has a read-only STDIN and write-only
	 * STDOUT and STDERR.  A socket STDIN, as a terminal application may attach, is
	 * read-write.
	 */
	public function testStdStreamCapabilitiesFollowTheDescriptor()
	{
		$pipes = ['pipe', 'r'];
		self::assertSame(
			[[true, false], [false, true], [false, true]],
			$this->childStdStreamCapabilities($pipes),
			'Pipe descriptors: STDIN is read-only; STDOUT and STDERR are write-only.'
		);
		if (PHP_OS_FAMILY === 'Windows') {
			return;
		}
		$pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
		try {
			self::assertSame(
				[true, true],
				$this->childStdStreamCapabilities($pair[1])[0],
				'A socket STDIN is read-write.'
			);
		} finally {
			fclose($pair[0]);
			fclose($pair[1]);
		}
	}

	/**
	 * Runs a child PHP process with the given STDIN descriptor and pipes for STDOUT and
	 * STDERR, and returns the readable and writable flags of TStdInStream,
	 * TStdOutStream, and TStdErrStream in the child.
	 * @param array|resource $stdin the proc_open() descriptor of the child's STDIN.
	 * @return array<int, array{bool, bool}> the readable and writable flags of each stream.
	 */
	private function childStdStreamCapabilities($stdin): array
	{
		$code = 'require ' . var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true) . ';'
			. '$flags = [];'
			. 'foreach ([new \\Prado\\IO\\TStdInStream(), new \\Prado\\IO\\TStdOutStream(), new \\Prado\\IO\\TStdErrStream()] as $s) {'
			. '$flags[] = [$s->isReadable(), $s->isWritable()];'
			. '}'
			. 'echo json_encode($flags);';
		$proc = proc_open([PHP_BINARY, '-r', $code], [0 => $stdin, 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		if (!is_resource($proc)) {
			self::markTestSkipped('proc_open unavailable.');
		}
		if (isset($pipes[0])) {
			fclose($pipes[0]);
		}
		$output = stream_get_contents($pipes[1]);
		$errors = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($proc);
		$flags = json_decode($output, true);
		self::assertIsArray($flags, 'The child process reports its flags. ' . $output . $errors);
		return $flags;
	}

	public function testInputStreamReadOnlyOwned()
	{
		$s = new TInputStream();
		self::assertTrue($s->getOwnsResource());
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isWritable());
		self::assertSame(TInputStream::URI, $s->getURI());
		$s->close();
	}

	public function testOutputStreamWritesThroughOutputBuffer()
	{
		$s = new TOutputStream();
		self::assertTrue($s->getOwnsResource());
		self::assertTrue($s->isWritable());
		self::assertFalse($s->isReadable());
		self::assertTrue(method_exists($s, 'flush'));

		ob_start();
		$s->write('echoed');
		$captured = ob_get_clean();
		self::assertSame('echoed', $captured, 'php://output writes go through the output buffer.');
		$s->close();
	}

	public function testOutputStreamFlushReturnsBool()
	{
		// Flush with nothing buffered so no output leaks out of the test.
		ob_start();
		$s = new TOutputStream();
		$result = $s->flush();
		ob_end_clean();
		self::assertIsBool($result);
		$s->close();
	}

	public function testStdOutStreamFlushReturnsBool()
	{
		$s = new TStdOutStream();          // non-owning; flush() must not error
		self::assertIsBool($s->flush());
		$s->close();
	}

	public function testStdErrStreamIsNotFlushable()
	{
		// By design only stdout/output carry TFlushableStreamTrait; stderr is unbuffered.
		self::assertFalse(method_exists(new TStdErrStream(), 'flush'));
	}
}
