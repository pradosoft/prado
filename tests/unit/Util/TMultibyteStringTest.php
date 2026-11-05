<?php

namespace Prado\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TMultibyteString;

class TMultibyteStringTest extends TestCase
{
	private mixed $_mbstring;

	protected function setUp(): void
	{
		$this->_mbstring = PradoUnit::getStaticProp(TMultibyteString::class, '_mbstring');
	}

	protected function tearDown(): void
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $this->_mbstring);
	}

	public static function pathProvider(): array
	{
		return ['mbstring' => [true], 'fallback' => [false]];
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testLength(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame($mbstring, TMultibyteString::hasMbstring());
		$this->assertSame(0, TMultibyteString::length(''));
		$this->assertSame(5, TMultibyteString::length('héllo'));
		$this->assertSame(2, TMultibyteString::length("a\n"));
		$this->assertSame(7, TMultibyteString::length('こんにちは世界'));
	}

	public function testLengthOfInvalidUtf8WithoutMbstringIsBytes()
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', false);
		$this->assertSame(3, TMultibyteString::length("\xC3\x28\x41"));
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testSubstr(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame('éll', TMultibyteString::substr('héllo', 1, 3));
		$this->assertSame('éllo', TMultibyteString::substr('héllo', 1));
		$this->assertSame('lo', TMultibyteString::substr('héllo', -2));
		$this->assertSame('éll', TMultibyteString::substr('héllo', 1, -1));
		$this->assertSame('', TMultibyteString::substr('héllo', 10));
		$this->assertSame('', TMultibyteString::substr('', 0, 2));
		$this->assertSame('にち', TMultibyteString::substr('こんにちは', 2, 2));
	}

	public function testSubstrOfInvalidUtf8WithoutMbstringIsBytes()
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', false);
		$this->assertSame("\x28\x41", TMultibyteString::substr("\xC3\x28\x41", 1));
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testUpperAndLower(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame('', TMultibyteString::upper(''));
		$this->assertSame('CAFÉ', TMultibyteString::upper('café'));
		$this->assertSame('STRASSE', TMultibyteString::upper('straße'));
		$this->assertSame('É', TMultibyteString::upper('é'));
		$this->assertSame('café au lait', TMultibyteString::lower("CAFÉ au Lait"));
		$this->assertSame('ß', TMultibyteString::lower('ß'));
		$this->assertSame('i̇', TMultibyteString::lower('İ'));
		$this->assertSame('abc', TMultibyteString::lower('ABC'));
	}
}
