<?php

use Prado\IO\Stream\TCachingStream;
use Psr\Http\Message\StreamInterface;

/**
 * A minimal forward-only, non-seekable source over an in-memory string.
 */
class TNonSeekableSource implements StreamInterface
{
	private string $_data;
	private int $_pos = 0;

	public function __construct(string $data)
	{
		$this->_data = $data;
	}

	public function read(int $length): string
	{
		$chunk = substr($this->_data, $this->_pos, $length);
		$this->_pos += strlen($chunk);
		return $chunk;
	}

	public function eof(): bool
	{
		return $this->_pos >= strlen($this->_data);
	}

	public function getSize(): ?int
	{
		return strlen($this->_data);
	}

	public function tell(): int
	{
		return $this->_pos;
	}

	public function isSeekable(): bool
	{
		return false;
	}

	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('not seekable');
	}

	public function rewind(): void
	{
		throw new \RuntimeException('not seekable');
	}

	public function isReadable(): bool
	{
		return true;
	}

	public function isWritable(): bool
	{
		return false;
	}

	public function write(string $string): int
	{
		throw new \RuntimeException('not writable');
	}

	public function getContents(): string
	{
		$rest = substr($this->_data, $this->_pos);
		$this->_pos = strlen($this->_data);
		return $rest;
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

	public function __toString(): string
	{
		return $this->_data;
	}
}

class TCachingStreamTest extends PHPUnit\Framework\TestCase
{
	private function caching(string $data = 'abcdefghij'): TCachingStream
	{
		return new TCachingStream(new TNonSeekableSource($data));
	}

	public function testReadThenRewind()
	{
		$s = $this->caching();
		self::assertSame('abcde', $s->read(5));
		$s->rewind();
		self::assertSame('abc', $s->read(3));
	}

	public function testSeekBackwardServedFromCache()
	{
		$s = $this->caching();
		$s->read(8);
		$s->seek(2);
		self::assertSame('cdef', $s->read(4));
	}

	public function testSeekForwardPastCacheFills()
	{
		$s = $this->caching();
		self::assertSame('ab', $s->read(2));
		$s->seek(6);                      // jump ahead of the cache
		self::assertSame('ghij', $s->read(10));
		self::assertTrue($s->eof());
	}

	public function testSeekEndUsesSourceSize()
	{
		$s = $this->caching();
		$s->seek(-3, SEEK_END);
		self::assertSame('hij', $s->read(3));
	}

	public function testIsSeekableTrueOverNonSeekableSource()
	{
		$s = $this->caching();
		self::assertTrue($s->isSeekable());
		self::assertFalse($s->getRemote()->isSeekable());
	}

	public function testToStringReadsAll()
	{
		$s = $this->caching('hello world');
		self::assertSame('hello world', (string) $s);
	}

	public function testIsReadOnly()
	{
		$s = $this->caching();
		self::assertFalse($s->isWritable(), 'A write would desynchronize the cache from the source.');
		self::expectException(\RuntimeException::class);
		$s->write('x');
	}

	public function testCloseReleasesTheSource()
	{
		$remote = new class ('abc') extends TNonSeekableSource {
			public bool $closed = false;

			public function close(): void
			{
				$this->closed = true;
			}
		};
		$s = new TCachingStream($remote);
		$s->close();
		self::assertTrue($remote->closed, 'Closing the caching stream closes the source too.');
	}

	public function testSeekPastTheEndClampsToTheCachedEnd()
	{
		$s = $this->caching('abcdefghij');
		$s->seek(50);
		self::assertSame(10, $s->tell(), 'A seek past the source clamps to the last byte cached.');
		self::assertTrue($s->eof());
	}

	public function testGetSizeReflectsSource()
	{
		$s = $this->caching('abcdefghij');
		self::assertSame(10, $s->getSize());
	}
}
