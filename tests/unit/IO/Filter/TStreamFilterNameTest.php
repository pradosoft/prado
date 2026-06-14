<?php

use Prado\IO\Filter\TStreamFilterName;
use Prado\IO\TStream;

class TStreamFilterNameTest extends PHPUnit\Framework\TestCase
{
	public function testConstantsMapToFilterNames()
	{
		self::assertSame('string.rot13', TStreamFilterName::ROT13);
		self::assertSame('string.toupper', TStreamFilterName::TOUPPER);
		self::assertSame('convert.base64-encode', TStreamFilterName::BASE64_ENCODE);
		self::assertSame('zlib.deflate', TStreamFilterName::DEFLATE);
		self::assertSame('bzip2.compress', TStreamFilterName::BZIP2_COMPRESS);
		self::assertSame('dechunk', TStreamFilterName::DECHUNK);
	}

	public function testConstantUsableAsFilterName()
	{
		$s = TStream::fromMemory();
		$s->appendFilter(TStreamFilterName::ROT13, STREAM_FILTER_WRITE);
		$s->write('hello');
		$s->seek(0);
		self::assertSame('uryyb', $s->getContents(), 'A constant passes straight through as a filter name.');
		$s->close();
	}

	public function testBuiltinStringFiltersAreRegistered()
	{
		// The string.* and dechunk filters ship with PHP regardless of optional extensions.
		self::assertTrue(TStream::filterExists(TStreamFilterName::ROT13));
		self::assertTrue(TStream::filterExists(TStreamFilterName::TOUPPER));
		self::assertTrue(TStream::filterExists(TStreamFilterName::TOLOWER));
		self::assertTrue(TStream::filterExists(TStreamFilterName::DECHUNK));
	}
}
