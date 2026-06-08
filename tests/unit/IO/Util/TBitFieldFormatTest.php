<?php

use Prado\IO\Util\TBitFieldFormat;
use Prado\TEnumerable;

/**
 * Unit tests for {@see \Prado\IO\Util\TBitFieldFormat}, the bit-field interpretation enum.
 */
class TBitFieldFormatTest extends PHPUnit\Framework\TestCase
{
	public function testIsEnumerableWithExpectedValues()
	{
		self::assertInstanceOf(TEnumerable::class, new TBitFieldFormat());
		self::assertSame(1, TBitFieldFormat::Unsigned);
		self::assertSame(2, TBitFieldFormat::Signed);
		self::assertSame(3, TBitFieldFormat::Float);
	}

	public function testValuesAreDistinct()
	{
		$values = array_values((new \ReflectionClass(TBitFieldFormat::class))->getConstants());
		self::assertSame($values, array_unique($values));
		self::assertCount(3, $values);
	}

	public function testLookupHelpers()
	{
		self::assertTrue(TBitFieldFormat::hasConstant('Signed'));
		self::assertFalse(TBitFieldFormat::hasConstant('Decimal'));
		self::assertSame('Float', TBitFieldFormat::constantOfValue(3));
	}
}
