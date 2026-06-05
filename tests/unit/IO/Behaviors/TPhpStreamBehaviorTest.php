<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Behaviors\TPhpStreamBehavior;
use Prado\IO\TStream;
use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

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

	public function testFseekWhences()
	{
		$s = $this->attached();
		$s->fwrite('0123456789');
		$s->fseek(3);                            // SEEK_SET
		self::assertSame(3, $s->ftell());
		self::assertSame('3', $s->fread(1));
		$s->fseek(2, SEEK_CUR);
		self::assertSame('6', $s->fread(1));
		$s->fseek(-1, SEEK_END);
		self::assertSame('9', $s->fread(1));
		$s->close();
	}

	public function testFgetsAtEofReturnsFalse()
	{
		$s = $this->attached();
		$s->fwrite("only\n");
		$s->fseek(0);
		self::assertSame("only\n", $s->fgets());
		self::assertFalse($s->fgets(), 'fgets at EOF returns false.');
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
		// A non-seekable owner: fseek must report failure, not throw.
		$b = new TNonSeekableStreamDouble();
		$b->attachBehavior('php', new TPhpStreamBehavior());
		$b->fwrite('data');
		self::assertFalse($b->fseek(0));
	}

	public function testDelegatesOnNonResourceStreamButFgetsReturnsFalse()
	{
		// Owner is a StreamInterface that is not resource-backed: PSR delegation works,
		// but the raw-resource readers (fgets/fgetc) return false.
		$b = new TNonSeekableStreamDouble();
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
		$owner = new TComponent();
		$owner->attachBehavior('php', new TPhpStreamBehavior());
		self::expectException(TIOException::class);
		$owner->fwrite('x');
	}
}

/**
 * Minimal non-seekable, non-resource StreamInterface owner for the behavior tests, so the
 * suite stays self-contained (no dependency on the Stream/ batch).
 */
class TNonSeekableStreamDouble extends TComponent implements StreamInterface
{
	private string $_buffer = '';

	private int $_pos = 0;

	public function write(string $string): int
	{
		$this->_buffer .= $string;
		return strlen($string);
	}

	public function read(int $length): string
	{
		$data = substr($this->_buffer, $this->_pos, $length);
		$this->_pos += strlen($data);
		return $data;
	}

	public function isSeekable(): bool
	{
		return false;
	}

	public function isReadable(): bool
	{
		return true;
	}

	public function isWritable(): bool
	{
		return true;
	}

	public function eof(): bool
	{
		return $this->_pos >= strlen($this->_buffer);
	}

	public function tell(): int
	{
		return $this->_pos;
	}

	public function getSize(): ?int
	{
		return strlen($this->_buffer);
	}

	public function getContents(): string
	{
		$data = substr($this->_buffer, $this->_pos);
		$this->_pos = strlen($this->_buffer);
		return $data;
	}

	public function __toString(): string
	{
		return $this->_buffer;
	}

	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('not seekable');
	}

	public function rewind(): void
	{
		throw new \RuntimeException('not seekable');
	}

	public function close(): void
	{
	}

	public function detach()
	{
		return null;
	}

	public function getMetadata(?string $key = null): mixed
	{
		return $key === null ? [] : null;
	}
}
