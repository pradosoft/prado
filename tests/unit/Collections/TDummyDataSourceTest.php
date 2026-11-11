<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TDummyDataSource;
use Prado\Collections\TDummyDataSourceIterator;

class TDummyDataSourceTest extends \PHPUnit\Framework\TestCase
{
	public function testCount()
	{
		$ds = new TDummyDataSource(3);
		self::assertSame(3, $ds->getCount());
		self::assertSame(3, $ds->Count);
		self::assertCount(3, $ds);
	}

	public function testIteratorYieldsNullItems()
	{
		$ds = new TDummyDataSource(3);
		self::assertInstanceOf(TDummyDataSourceIterator::class, $ds->getIterator());
		self::assertSame([0 => null, 1 => null, 2 => null], iterator_to_array($ds));
	}

	public function testZeroCountIteratesNothing()
	{
		$ds = new TDummyDataSource(0);
		self::assertCount(0, $ds);
		self::assertSame([], iterator_to_array($ds));
	}
}
