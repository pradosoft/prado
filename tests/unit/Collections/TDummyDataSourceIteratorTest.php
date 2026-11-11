<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TDummyDataSourceIterator;

class TDummyDataSourceIteratorTest extends \PHPUnit\Framework\TestCase
{
	public function testIteratorProtocol()
	{
		$iterator = new TDummyDataSourceIterator(2);
		self::assertTrue($iterator->valid());
		self::assertSame(0, $iterator->key());
		self::assertNull($iterator->current());
		$iterator->next();
		self::assertTrue($iterator->valid());
		self::assertSame(1, $iterator->key());
		$iterator->next();
		self::assertFalse($iterator->valid());
		self::assertSame(2, $iterator->key());
		$iterator->rewind();
		self::assertTrue($iterator->valid());
		self::assertSame(0, $iterator->key());
	}

	public function testZeroCountIsNeverValid()
	{
		$iterator = new TDummyDataSourceIterator(0);
		self::assertFalse($iterator->valid());
	}
}
