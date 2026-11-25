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
	public function testCutEndsOnACharacterBoundary(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		// An end on a boundary stays.
		$this->assertSame('日本', TMultibyteString::cut('日本語', 0, 6));
		$this->assertSame('aé', TMultibyteString::cut('aé', 0, 3));
		// An end inside a two-, three- and four-byte character moves back.
		$this->assertSame('a', TMultibyteString::cut('aé', 0, 2));
		$this->assertSame('日', TMultibyteString::cut('日本語', 0, 4));
		$this->assertSame('日', TMultibyteString::cut('日本語', 0, 5));
		$this->assertSame('a', TMultibyteString::cut('a😀b', 0, 2));
		$this->assertSame('a', TMultibyteString::cut('a😀b', 0, 3));
		$this->assertSame('a', TMultibyteString::cut('a😀b', 0, 4));
		$this->assertSame('a😀', TMultibyteString::cut('a😀b', 0, 5));
		$this->assertSame('', TMultibyteString::cut('日本語', 0, 2));
		// A field of 256 bytes holds 85 three-byte characters.
		$cut = TMultibyteString::cut(str_repeat('日', 100), 0, 256);
		$this->assertSame(str_repeat('日', 85), $cut);
		$this->assertSame(255, strlen($cut));
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testCutStartMovesBackToItsCharacter(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame('本語', TMultibyteString::cut('日本語', 3));
		$this->assertSame('本語', TMultibyteString::cut('日本語', 4));
		$this->assertSame('本語', TMultibyteString::cut('日本語', 5));
		$this->assertSame('é', TMultibyteString::cut('aé', 2));
		$this->assertSame('😀b', TMultibyteString::cut('a😀b', 4));
		// The length counts from the moved start.
		$this->assertSame('日', TMultibyteString::cut('日本語', 1, 3));
		$this->assertSame('', TMultibyteString::cut('日本語', 2, 1));
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testCutNegativeStartAndLength(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame('語', TMultibyteString::cut('日本語', -3));
		$this->assertSame('語', TMultibyteString::cut('日本語', -1));
		$this->assertSame('本', TMultibyteString::cut('日本語', -6, 3));
		$this->assertSame('', TMultibyteString::cut('日本語', -4, 2));
		$this->assertSame('日', TMultibyteString::cut('日本語', -20, 3));
		$this->assertSame('日本', TMultibyteString::cut('日本語', 0, -3));
		$this->assertSame('日', TMultibyteString::cut('日本語', 0, -4));
		$this->assertSame('', TMultibyteString::cut('日本語', 0, -20));
		// A negative length is measured from the start as given, then counts from the moved start.
		$this->assertSame('日', TMultibyteString::cut('日本語', 1, -3));
		$this->assertSame('本', TMultibyteString::cut('日本語', -6, -3));
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testCutPastTheEndAndEmpty(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame('本語', TMultibyteString::cut('日本語', 3, 100));
		$this->assertSame('日本語', TMultibyteString::cut('日本語', 0, PHP_INT_MAX));
		$this->assertSame('日本語', TMultibyteString::cut('日本語', PHP_INT_MIN));
		$this->assertSame('', TMultibyteString::cut('日本語', 9));
		$this->assertSame('', TMultibyteString::cut('日本語', 10, 1));
		$this->assertSame('', TMultibyteString::cut('日本語', 0, 0));
		$this->assertSame('', TMultibyteString::cut('', 0));
		$this->assertSame('', TMultibyteString::cut('', 0, 2));
		$this->assertSame('', TMultibyteString::cut('', -1, -1));
	}

	/**
	 * @dataProvider pathProvider
	 * @param bool $mbstring
	 */
	public function testCutOfInvalidUtf8IsBytes(bool $mbstring)
	{
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		$this->assertSame("\x28\x41", TMultibyteString::cut("\xC3\x28\x41", 1));
		$this->assertSame("a\xE6", TMultibyteString::cut("a\xE6bc", 0, 2));
		$this->assertSame("\x97", TMultibyteString::cut("\xE6\x97", 1));
		$this->assertSame("\xE6", TMultibyteString::cut("\xE6\x97", 0, -1));
		$this->assertSame("\x97", TMultibyteString::cut("\xE6\x97", -1));
	}

	public function testCutIsTheSameOnBothPaths()
	{
		foreach (['a', 'abc', 'aé', 'éa', '日本語', 'a😀b', 'é日😀x'] as $string) {
			$reach = strlen($string) + 2;
			$lengths = array_merge([null], range(-$reach, $reach));
			foreach (range(-$reach, $reach) as $start) {
				foreach ($lengths as $length) {
					PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', true);
					$expected = TMultibyteString::cut($string, $start, $length);
					PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', false);
					$case = "cut('$string', $start, " . var_export($length, true) . ')';
					$this->assertSame($expected, TMultibyteString::cut($string, $start, $length), $case);
					if ($length !== null && $length >= 0) {
						$this->assertLessThanOrEqual($length, strlen($expected), $case);
					}
					$this->assertSame(1, preg_match('//u', $expected), $case);
				}
			}
		}
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

	public function testTransliterateUnknownRuleOrInvalidUtf8IsNull()
	{
		$this->assertNull(PradoUnit::invoke(TMultibyteString::class, 'transliterate', 'No-Such-Rule', 'abc'));
		$this->assertNull(PradoUnit::invoke(TMultibyteString::class, 'transliterate', 'Any-Lower', "\xff"));
		// A rule created on this call, then given invalid UTF-8.
		$this->assertNull(PradoUnit::invoke(TMultibyteString::class, 'transliterate', 'Any-Title', "\xff"));
		$this->assertSame('abc', PradoUnit::invoke(TMultibyteString::class, 'transliterate', 'Any-Lower', 'ABC'));
	}
}
