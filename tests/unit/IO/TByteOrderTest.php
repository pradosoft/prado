<?php

use Prado\IO\TByteOrder;
use Prado\TEnumerable;

class TByteOrderTest extends PHPUnit\Framework\TestCase
{
	public function testConstantsAndEnumerable()
	{
		self::assertInstanceOf(TEnumerable::class, new TByteOrder());
		self::assertSame(0, TByteOrder::LittleEndian);
		self::assertSame(1, TByteOrder::BigEndian);
	}

	public function testNativeIsKnown()
	{
		self::assertContains(TByteOrder::native(), [TByteOrder::LittleEndian, TByteOrder::BigEndian]);
	}

	public function testResolve()
	{
		self::assertSame(TByteOrder::native(), TByteOrder::resolve(null));
		self::assertSame(TByteOrder::BigEndian, TByteOrder::resolve(TByteOrder::BigEndian));
		self::assertSame(TByteOrder::LittleEndian, TByteOrder::resolve(TByteOrder::LittleEndian));
	}

	public function testIsBigEndian()
	{
		self::assertTrue(TByteOrder::isBigEndian(TByteOrder::BigEndian));
		self::assertFalse(TByteOrder::isBigEndian(TByteOrder::LittleEndian));
		self::assertSame(TByteOrder::native() === TByteOrder::BigEndian, TByteOrder::isBigEndian(null));
	}
}
