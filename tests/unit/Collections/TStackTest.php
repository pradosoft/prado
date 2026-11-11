<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TStack;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;

class TStackTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$stack = new TStack();
		self::assertEquals([], $stack->toArray());
		$stack = new TStack([1, 2, 3]);
		self::assertEquals([1, 2, 3], $stack->toArray());
	}

	public function testToArray()
	{
		$stack = new TStack([1, 2, 3]);
		self::assertEquals([1, 2, 3], $stack->toArray());
	}

	public function testCopyFrom()
	{
		$stack = new TStack([1, 2, 3]);
		$data = [4, 5, 6];
		$stack->copyFrom($data);
		self::assertEquals([4, 5, 6], $stack->toArray());
	}

	public function testCanNotCopyFromNonTraversableTypes()
	{
		$stack = new TStack();
		$data = new \stdClass();
		self::expectException(TInvalidDataTypeException::class);
		$stack->copyFrom($data);
	}

	public function testClear()
	{
		$stack = new TStack([1, 2, 3]);
		$stack->clear();
		self::assertEquals([], $stack->toArray());
	}

	public function testContains()
	{
		$stack = new TStack([1, 2, 3]);
		self::assertEquals(true, $stack->contains(2));
		self::assertEquals(false, $stack->contains(4));
	}

	public function testPeek()
	{
		$stack = new TStack([1]);
		self::assertEquals(1, $stack->peek());
	}

	public function testCanNotPeekAnEmptyStack()
	{
		$stack = new TStack();
		self::expectException(TInvalidOperationException::class);
		$item = $stack->peek();
	}

	public function testPop()
	{
		$stack = new TStack([1, 2, 3]);
		$last = $stack->pop();
		self::assertEquals(3, $last);
		self::assertEquals([1, 2], $stack->toArray());
	}

	public function testCanNotPopAnEmptyStack()
	{
		$stack = new TStack();
		self::expectException(TInvalidOperationException::class);
		$item = $stack->pop();
	}

	public function testPush()
	{
		$stack = new TStack();
		$stack->push(1);
		self::assertEquals([1], $stack->toArray());
	}

	public function testGetIterator()
	{
		$stack = new TStack([1, 2]);
		self::assertInstanceOf('ArrayIterator', $stack->getIterator());
		$n = 0;
		$found = 0;
		foreach ($stack as $index => $item) {
			foreach ($stack as $a => $b); // test of iterator
			$n++;
			if ($index === 0 && $item === 1) {
				$found++;
			}
			if ($index === 1 && $item === 2) {
				$found++;
			}
		}
		self::assertTrue($n == 2 && $found == 2);
	}

	public function testGetCount()
	{
		$stack = new TStack();
		self::assertEquals(0, $stack->getCount());
		$stack = new TStack([1, 2, 3]);
		self::assertEquals(3, $stack->getCount());
	}

	public function testCount()
	{
		$stack = new TStack();
		self::assertEquals(0, count($stack));
		$stack = new TStack([1, 2, 3]);
		self::assertEquals(3, count($stack));
	}

	public function testCopyFromTraversableReplacesExistingItems()
	{
		$stack = new TStack([1, 2, 3]);
		$stack->copyFrom(new \ArrayIterator([7, 8]));
		self::assertEquals([7, 8], $stack->toArray());
		self::assertEquals(2, $stack->getCount());
	}

	public function testCopyFromEmptyTraversableClearsItems()
	{
		$stack = new TStack([1, 2, 3]);
		$stack->copyFrom(new \ArrayIterator([]));
		self::assertEquals([], $stack->toArray());
		self::assertEquals(0, $stack->getCount());
	}

	public function testCopyFromEmptyArrayClearsItems()
	{
		$stack = new TStack([1, 2, 3]);
		$stack->copyFrom([]);
		self::assertEquals([], $stack->toArray());
		self::assertEquals(0, $stack->getCount());
	}

	public function testCopyFromNullKeepsItems()
	{
		$stack = new TStack([1, 2, 3]);
		$stack->copyFrom(null);
		self::assertEquals([1, 2, 3], $stack->toArray());
		self::assertEquals(3, $stack->getCount());
	}

	public function testCopyFromNonTraversableErrorCode()
	{
		$stack = new TStack([1]);
		try {
			$stack->copyFrom('string');
			self::fail('TInvalidDataTypeException was not thrown for a non-iterable.');
		} catch (TInvalidDataTypeException $e) {
			self::assertEquals('stack_data_not_iterable', $e->getErrorCode());
		}
		self::assertEquals([1], $stack->toArray());
	}
}
