<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Behavior\TPhpStreamBehavior;
use Prado\IO\Stream\TBufferStream;
use Prado\IO\TStream;

class TPhpStreamBehaviorTest extends PHPUnit\Framework\TestCase
{
	private function attached(): TStream
	{
		$s = TStream::fromMemory();
		$s->attachBehavior('php', new TPhpStreamBehavior());
		return $s;
	}

	public function testDelegatesToResourceBackedStream()
	{
		$s = $this->attached();
		self::assertSame(5, $s->fwrite('abcde'));
		self::assertSame(2, $s->fputs('fg'));
		self::assertTrue($s->fseek(0));
		self::assertSame(0, $s->ftell());
		self::assertSame('abc', $s->fread(3));
		self::assertSame('defg', $s->fgets());
		self::assertIsBool($s->feof());
		$s->fseek(0);
		self::assertFalse($s->feof(), 'Not at EOF right after seeking to start.');
		self::assertSame('a', $s->fgetc());
		$s->close();
	}

	public function testFgetsLengthLimit()
	{
		$s = $this->attached();
		$s->fwrite("hello\nworld\n");
		$s->fseek(0);
		self::assertSame('hel', $s->fgets(4));   // length includes the terminator slot
		$s->close();
	}

	public function testFgetcAtEofReturnsFalse()
	{
		$s = $this->attached();
		$s->fwrite('x');
		$s->fseek(0);
		self::assertSame('x', $s->fgetc());
		self::assertFalse($s->fgetc(), 'fgetc at EOF returns false.');
		$s->close();
	}

	public function testFeofTrueAfterReadingPastEnd()
	{
		$s = $this->attached();
		$s->fwrite('ab');
		$s->fseek(0);
		$s->fread(2);
		$s->fread(1);                            // pushes the pointer past the end
		self::assertTrue($s->feof());
		$s->close();
	}

	public function testFseekNonSeekableReturnsFalse()
	{
		// A TBufferStream is a non-seekable FIFO; fseek must report failure, not throw.
		$b = new TBufferStream();
		$b->attachBehavior('php', new TPhpStreamBehavior());
		$b->fwrite('data');
		self::assertFalse($b->fseek(0));
	}

	public function testDelegatesOnNonResourceStreamButFgetsReturnsFalse()
	{
		// Owner is a StreamInterface that is not resource-backed: PSR delegation works,
		// but the raw-resource readers (fgets/fgetc) return false.
		$b = new TBufferStream();
		$b->attachBehavior('php', new TPhpStreamBehavior());
		self::assertSame(3, $b->fwrite('xyz'));
		self::assertSame('xy', $b->fread(2));
		self::assertFalse($b->fgets(), 'fgets needs a resource-backed owner.');
		self::assertFalse($b->fgetc(), 'fgetc needs a resource-backed owner.');
	}

	public function testUnattachedBehaviorThrows()
	{
		$behavior = new TPhpStreamBehavior();
		self::expectException(TIOException::class);
		$behavior->fread(1);                     // no owner -> not a stream
	}

	public function testNonStreamOwnerThrows()
	{
		$owner = new \Prado\TComponent();
		$owner->attachBehavior('php', new TPhpStreamBehavior());
		self::expectException(TIOException::class);
		$owner->fwrite('x');
	}
}
