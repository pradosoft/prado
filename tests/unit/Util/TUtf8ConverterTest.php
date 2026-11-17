<?php

namespace Prado\Test\Unit\Util;

use Prado\Util\TUtf8Converter;

class TUtf8ConverterTest extends \PHPUnit\Framework\TestCase
{
	private const UTF8 = "caf\u{e9}";
	private const LATIN1 = "caf\xe9";

	private string $_locale;

	protected function setUp(): void
	{
		$this->_locale = setlocale(LC_CTYPE, '0');
	}

	protected function tearDown(): void
	{
		setlocale(LC_CTYPE, $this->_locale);
	}

	public function testToUtf8()
	{
		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::UTF8, 'UTF-8'));
		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::LATIN1, 'ISO-8859-1'));
	}

	public function testToUtf8WithLanguage()
	{
		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::LATIN1, 'ISO-8859-1.C'));
		self::assertSame($this->_locale, setlocale(LC_CTYPE, '0'));

		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::LATIN1, 'ISO-8859-1', 'C'));
		self::assertSame($this->_locale, setlocale(LC_CTYPE, '0'));
	}

	public function testFromUtf8()
	{
		self::assertSame(self::UTF8, TUtf8Converter::fromUTF8(self::UTF8, 'UTF-8'));
		self::assertSame(self::LATIN1, TUtf8Converter::fromUTF8(self::UTF8, 'ISO-8859-1'));
	}

	public function testFromUtf8WithLanguage()
	{
		self::assertSame(self::LATIN1, TUtf8Converter::fromUTF8(self::UTF8, 'ISO-8859-1.C'));
		self::assertSame($this->_locale, setlocale(LC_CTYPE, '0'));

		self::assertSame(self::LATIN1, TUtf8Converter::fromUTF8(self::UTF8, 'ISO-8859-1', 'C'));
		self::assertSame($this->_locale, setlocale(LC_CTYPE, '0'));
	}

	public function testParseEncodingLanguage()
	{
		$encoding = 'ISO-8859-1.fr_FR';
		$lang = null;
		TUtf8Converter::parseEncodingLanguage($encoding, $lang);
		self::assertSame('ISO-8859-1', $encoding);
		self::assertSame('fr_FR', $lang);

		$encoding = 'ISO-8859-1';
		$lang = null;
		TUtf8Converter::parseEncodingLanguage($encoding, $lang);
		self::assertSame('ISO-8859-1', $encoding);
		self::assertNull($lang);
	}
}
