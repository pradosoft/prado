<?php

namespace Prado\Test\Unit\Util;

use Prado\Test\Unit\PradoUnit;
use Prado\Util\TUtf8Converter;

class TUtf8ConverterTest extends \PHPUnit\Framework\TestCase
{
	private const UTF8 = "caf\u{e9}";
	private const LATIN1 = "caf\xe9";

	private string $_locale;
	private array $_static;

	protected function setUp(): void
	{
		$this->_locale = setlocale(LC_CTYPE, '0');
		$this->_static = PradoUnit::snapshotStatic(TUtf8Converter::class, ['_iconv']);
	}

	protected function tearDown(): void
	{
		setlocale(LC_CTYPE, $this->_locale);
		PradoUnit::restoreStatic(TUtf8Converter::class, $this->_static);
	}

	private function withoutIconv(): void
	{
		PradoUnit::setStaticProp(TUtf8Converter::class, '_iconv', false);
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

	public function testHasIconv()
	{
		PradoUnit::setStaticProp(TUtf8Converter::class, '_iconv', null);
		self::assertSame(extension_loaded('iconv'), TUtf8Converter::hasIconv());
	}

	public static function pathProvider(): array
	{
		return [
			'iconv' => [true],
			'UConverter' => [false],
		];
	}

	/**
	 * @dataProvider pathProvider
	 */
	public function testConvertsBothWays(bool $iconv)
	{
		if ($iconv && !extension_loaded('iconv')) {
			self::markTestSkipped('The iconv extension is not loaded.');
		}
		if (!$iconv) {
			$this->withoutIconv();
		}
		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::LATIN1, 'ISO-8859-1'));
		self::assertSame(self::LATIN1, TUtf8Converter::fromUTF8(self::UTF8, 'ISO-8859-1'));
		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::LATIN1, 'ISO-8859-1.C'));
		self::assertSame($this->_locale, setlocale(LC_CTYPE, '0'));
	}

	/**
	 * @dataProvider pathProvider
	 */
	public function testUnknownEncodingReturnsTheString(bool $iconv)
	{
		if ($iconv && !extension_loaded('iconv')) {
			self::markTestSkipped('The iconv extension is not loaded.');
		}
		if (!$iconv) {
			$this->withoutIconv();
		}
		self::assertSame(self::LATIN1, TUtf8Converter::toUTF8(self::LATIN1, 'NO-SUCH-ENCODING'));
		self::assertSame(self::UTF8, TUtf8Converter::fromUTF8(self::UTF8, 'NO-SUCH-ENCODING.C'));
		self::assertSame($this->_locale, setlocale(LC_CTYPE, '0'), 'The locale is restored after a failed conversion.');
	}

	public function testIconvInvalidInputReturnsTheString()
	{
		if (!extension_loaded('iconv')) {
			self::markTestSkipped('The iconv extension is not loaded.');
		}
		// "\xff" is not UTF-8; iconv() warns and fails.
		self::assertSame("caf\xff", TUtf8Converter::fromUTF8("caf\xff", 'ISO-8859-1'));
	}

	public function testUConverterSubstitutesAndIgnoresIconvSuffixes()
	{
		$this->withoutIconv();
		// ICU substitutes SUB (0x1A) for a character ASCII cannot hold.
		self::assertSame("caf\x1a", TUtf8Converter::fromUTF8("caf\u{2603}", 'ASCII//TRANSLIT'));
		self::assertSame(self::UTF8, TUtf8Converter::toUTF8(self::LATIN1, 'ISO-8859-1//IGNORE'));
	}
}
