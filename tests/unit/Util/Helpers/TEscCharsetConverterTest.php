<?php

namespace Prado\Test\Unit\Util\Helpers;

use Prado\Util\Helpers\TEscCharsetConverter;

class TEscCharsetConverterTest extends \PHPUnit\Framework\TestCase
{
	public function testDecodeEscapeCharset()
	{
		self::assertSame('UTF-8', TEscCharsetConverter::decodeEscapeCharset("\x1B%G"));
		self::assertSame('ISO-8859-1', TEscCharsetConverter::decodeEscapeCharset("\x1B-A"));
		self::assertSame('JIS0208', TEscCharsetConverter::decodeEscapeCharset("\x1B\$(B"));
	}

	public function testDecodeEscapeCharsetFirstKnownSequence()
	{
		self::assertSame('ISO-8859-2', TEscCharsetConverter::decodeEscapeCharset("\x1B??\x1B-B\x1B-A"));
	}

	public function testDecodeEscapeCharsetUnknown()
	{
		self::assertNull(TEscCharsetConverter::decodeEscapeCharset(''));
		self::assertNull(TEscCharsetConverter::decodeEscapeCharset('UTF-8'));
		self::assertNull(TEscCharsetConverter::decodeEscapeCharset("\x1B??"));
	}

	public function testEncodeEscapeCharset()
	{
		self::assertSame("\x1B%G", TEscCharsetConverter::encodeEscapeCharset('UTF-8'));
		self::assertSame("\x1B,A", TEscCharsetConverter::encodeEscapeCharset('ISO-8859-1'));
		self::assertNull(TEscCharsetConverter::encodeEscapeCharset('UTF-16'));
	}

	public function testRoundTrip()
	{
		foreach (array_unique(TEscCharsetConverter::ESC_CHAR_ENCODINGS_MAP) as $charset) {
			$sequence = TEscCharsetConverter::encodeEscapeCharset($charset);
			self::assertSame($charset, TEscCharsetConverter::decodeEscapeCharset($sequence));
		}
	}
}
