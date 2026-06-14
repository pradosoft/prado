<?php

use Prado\IO\Filter\TStreamCodecFilter;
use Prado\IO\TStream;

class TStreamCodecFilterTest extends PHPUnit\Framework\TestCase
{
	public function testProcessTransformsEachChunk()
	{
		$s = TStream::fromMemory();
		$s->write('hello world');
		$s->seek(0);
		$s->appendFilter(TUpcaseCodecTestFilter::class, STREAM_FILTER_READ);
		self::assertSame('HELLO WORLD', $s->getContents(), 'process() runs incrementally on each chunk.');
		$s->close();
	}

	public function testFinishEmitsTrailingOutput()
	{
		$s = TStream::fromMemory();
		$s->write('abc');
		$s->seek(0);
		$s->appendFilter(TCountCodecTestFilter::class, STREAM_FILTER_READ);
		self::assertSame('abc[3]', $s->getContents(), 'finish() emits its trailing bytes at close.');
		$s->close();
	}

	public function testFinishRunsWithEmptyInput()
	{
		$s = TStream::fromMemory();
		$s->seek(0);
		$s->appendFilter(TCountCodecTestFilter::class, STREAM_FILTER_READ);
		self::assertSame('[0]', $s->getContents(), 'finish() runs at close even when no input arrived.');
		$s->close();
	}

	public function testStateCarriesAcrossBuckets()
	{
		$size = 20000;   // forces more than one bucket
		$s = TStream::fromMemory();
		$s->write(str_repeat('x', $size));
		$s->seek(0);
		$s->appendFilter(TCountCodecTestFilter::class, STREAM_FILTER_READ);
		self::assertSame(str_repeat('x', $size) . "[$size]", $s->getContents(), 'process() state accumulates across buckets.');
		$s->close();
	}
}

class TUpcaseCodecTestFilter extends TStreamCodecFilter
{
	public static function getFilterName(): string
	{
		return 'prado.test.codec.upcase';
	}

	protected function process(string $data): string
	{
		return strtoupper($data);
	}

	protected function finish(): string
	{
		return '';
	}
}

class TCountCodecTestFilter extends TStreamCodecFilter
{
	private int $_count = 0;

	public static function getFilterName(): string
	{
		return 'prado.test.codec.count';
	}

	protected function process(string $data): string
	{
		$this->_count += strlen($data);
		return $data;
	}

	protected function finish(): string
	{
		return '[' . $this->_count . ']';
	}
}
