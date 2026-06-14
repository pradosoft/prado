<?php

use Prado\IO\Filter\TBufferedStreamFilter;
use Prado\IO\TStream;

class TBufferedStreamFilterTest extends PHPUnit\Framework\TestCase
{
	public function testProcessRunsOverTheWholeBuffer()
	{
		$s = TStream::fromMemory();
		$s->write('abcdef');
		$s->seek(0);
		$s->appendFilter(TReverseBufferedTestFilter::class, STREAM_FILTER_READ);
		self::assertSame('fedcba', $s->getContents(), 'process() transforms the whole buffer at close.');
		$s->close();
	}

	public function testAccumulatesEveryBucketExactlyOnce()
	{
		// 20 KB forces more than one stream bucket (PHP reads ~8 KB at a time); a length-reporting
		// filter proves the buffer holds the input once, not doubled per bucket.
		$size = 20000;
		$s = TStream::fromMemory();
		$s->write(str_repeat('x', $size));
		$s->seek(0);
		$s->appendFilter(TLengthBufferedTestFilter::class, STREAM_FILTER_READ);
		self::assertSame((string) $size, $s->getContents(), 'The whole input is buffered exactly once across buckets.');
		$s->close();
	}

	public function testEmptyInputEmitsNothing()
	{
		$s = TStream::fromMemory();
		$s->seek(0);
		$s->appendFilter(TReverseBufferedTestFilter::class, STREAM_FILTER_READ);
		self::assertSame('', $s->getContents(), 'An empty buffer emits no bucket.');
		$s->close();
	}
}

class TReverseBufferedTestFilter extends TBufferedStreamFilter
{
	public static function getFilterName(): string
	{
		return 'prado.test.buffered.reverse';
	}

	protected function process(string $data): string
	{
		return strrev($data);
	}
}

class TLengthBufferedTestFilter extends TBufferedStreamFilter
{
	public static function getFilterName(): string
	{
		return 'prado.test.buffered.length';
	}

	protected function process(string $data): string
	{
		return (string) strlen($data);
	}
}
