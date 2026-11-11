<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TQueueIterator;

class TQueueIteratorTest extends \PHPUnit\Framework\TestCase
{
	public function testIteratesItemsInOrderWithIntegerKeys()
	{
		$data = ['a', 'b', 'c'];
		$iterator = new TQueueIterator($data);
		self::assertInstanceOf(\Iterator::class, $iterator);

		$items = [];
		foreach ($iterator as $key => $item) {
			$items[$key] = $item;
		}
		self::assertSame([0 => 'a', 1 => 'b', 2 => 'c'], $items);
	}

	public function testManualStepping()
	{
		$data = [10, 20];
		$iterator = new TQueueIterator($data);

		self::assertTrue($iterator->valid());
		self::assertSame(0, $iterator->key());
		self::assertSame(10, $iterator->current());

		$iterator->next();
		self::assertTrue($iterator->valid());
		self::assertSame(1, $iterator->key());
		self::assertSame(20, $iterator->current());

		$iterator->next();
		self::assertFalse($iterator->valid());
		self::assertSame(2, $iterator->key());
	}

	public function testRewindReturnsToFirstItem()
	{
		$data = ['x', 'y'];
		$iterator = new TQueueIterator($data);
		$iterator->next();
		$iterator->next();
		self::assertFalse($iterator->valid());

		$iterator->rewind();
		self::assertTrue($iterator->valid());
		self::assertSame(0, $iterator->key());
		self::assertSame('x', $iterator->current());
	}

	public function testEmptyDataIsNeverValid()
	{
		$data = [];
		$iterator = new TQueueIterator($data);
		self::assertFalse($iterator->valid());
		self::assertSame([], iterator_to_array($iterator));
	}

	public function testDataIsReferencedAndCountIsFixedAtConstruction()
	{
		$data = ['a', 'b'];
		$iterator = new TQueueIterator($data);

		$data[0] = 'changed';
		$data[] = 'appended';

		self::assertSame('changed', $iterator->current());
		self::assertSame(['changed', 'b'], iterator_to_array($iterator));
	}
}
