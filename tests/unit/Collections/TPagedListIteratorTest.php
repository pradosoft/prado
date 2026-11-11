<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TList;
use Prado\Collections\TPagedListIterator;

class TPagedListIteratorTest extends \PHPUnit\Framework\TestCase
{
	public function testIteratesWindowWithPageRelativeKeys()
	{
		$iterator = new TPagedListIterator(new TList(['a', 'b', 'c', 'd', 'e']), 1, 3);
		self::assertSame([0 => 'b', 1 => 'c', 2 => 'd'], iterator_to_array($iterator));
	}

	public function testIteratorProtocol()
	{
		$iterator = new TPagedListIterator(new TList(['a', 'b', 'c']), 1, 2);
		self::assertTrue($iterator->valid());
		self::assertSame(0, $iterator->key());
		self::assertSame('b', $iterator->current());
		$iterator->next();
		self::assertSame(1, $iterator->key());
		self::assertSame('c', $iterator->current());
		$iterator->next();
		self::assertFalse($iterator->valid());
		$iterator->rewind();
		self::assertTrue($iterator->valid());
		self::assertSame(0, $iterator->key());
		self::assertSame('b', $iterator->current());
	}

	public function testCountIsClampedToListEnd()
	{
		$iterator = new TPagedListIterator(new TList(['a', 'b', 'c', 'd']), 2, 10);
		self::assertSame([0 => 'c', 1 => 'd'], iterator_to_array($iterator));
	}

	public function testStartIndexPastListEndIteratesNothing()
	{
		$iterator = new TPagedListIterator(new TList(['a', 'b']), 4, 2);
		self::assertFalse($iterator->valid());
		self::assertSame([], iterator_to_array($iterator));
	}
}
