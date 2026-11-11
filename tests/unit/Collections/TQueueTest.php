<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TQueue;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;

class TQueueTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$queue = new TQueue();
		self::assertEquals([], $queue->toArray());
		$queue = new TQueue([1, 2, 3]);
		self::assertEquals([1, 2, 3], $queue->toArray());
	}

	public function testToArray()
	{
		$queue = new TQueue([1, 2, 3]);
		self::assertEquals([1, 2, 3], $queue->toArray());
	}

	public function testCopyFrom()
	{
		$queue = new TQueue([1, 2, 3]);
		$data = [4, 5, 6];
		$queue->copyFrom($data);
		self::assertEquals([4, 5, 6], $queue->toArray());
	}

	public function testCanNotCopyFromNonTraversableTypes()
	{
		$queue = new TQueue();
		$data = new \stdClass();
		self::expectException(TInvalidDataTypeException::class);
		$queue->copyFrom($data);
	}

	public function testClear()
	{
		$queue = new TQueue([1, 2, 3]);
		$queue->clear();
		self::assertEquals([], $queue->toArray());
	}

	public function testContains()
	{
		$queue = new TQueue([1, 2, 3]);
		self::assertEquals(true, $queue->contains(2));
		self::assertEquals(false, $queue->contains(4));
	}

	public function testPeek()
	{
		$queue = new TQueue([1, 2, 3]);
		self::assertEquals(1, $queue->peek());
	}

	public function testCanNotPeekAnEmptyQueue()
	{
		$queue = new TQueue();
		self::expectException(TInvalidOperationException::class);
		$item = $queue->peek();
	}

	public function testDequeue()
	{
		$queue = new TQueue([1, 2, 3]);
		$first = $queue->dequeue();
		self::assertEquals(1, $first);
		self::assertEquals([2, 3], $queue->toArray());
	}

	public function testCanNotDequeueAnEmptyQueue()
	{
		$queue = new TQueue();
		self::expectException(TInvalidOperationException::class);
		$item = $queue->dequeue();
	}

	public function testEnqueue()
	{
		$queue = new TQueue();
		$queue->enqueue(1);
		self::assertEquals([1], $queue->toArray());
	}

	public function testGetIterator()
	{
		$queue = new TQueue([1, 2]);
		self::assertInstanceOf('ArrayIterator', $queue->getIterator());
		$n = 0;
		$found = 0;
		foreach ($queue as $index => $item) {
			foreach ($queue as $a => $b); // test of iterator
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
		$queue = new TQueue();
		self::assertEquals(0, $queue->getCount());
		$queue = new TQueue([1, 2, 3]);
		self::assertEquals(3, $queue->getCount());
	}

	public function testCountable()
	{
		$queue = new TQueue();
		self::assertEquals(0, count($queue));
		$queue = new TQueue([1, 2, 3]);
		self::assertEquals(3, count($queue));
	}

	public function testCopyFromTraversableReplacesExistingItems()
	{
		$queue = new TQueue([1, 2, 3]);
		$queue->copyFrom(new \ArrayIterator([7, 8]));
		self::assertEquals([7, 8], $queue->toArray());
		self::assertEquals(2, $queue->getCount());
	}

	public function testCopyFromEmptyTraversableClearsItems()
	{
		$queue = new TQueue([1, 2, 3]);
		$queue->copyFrom(new \ArrayIterator([]));
		self::assertEquals([], $queue->toArray());
		self::assertEquals(0, $queue->getCount());
	}

	public function testCopyFromEmptyArrayClearsItems()
	{
		$queue = new TQueue([1, 2, 3]);
		$queue->copyFrom([]);
		self::assertEquals([], $queue->toArray());
		self::assertEquals(0, $queue->getCount());
	}

	public function testCopyFromNullKeepsItems()
	{
		$queue = new TQueue([1, 2, 3]);
		$queue->copyFrom(null);
		self::assertEquals([1, 2, 3], $queue->toArray());
		self::assertEquals(3, $queue->getCount());
	}

	public function testCopyFromNonTraversableErrorCode()
	{
		$queue = new TQueue([1]);
		try {
			$queue->copyFrom('string');
			self::fail('TInvalidDataTypeException was not thrown for a non-iterable.');
		} catch (TInvalidDataTypeException $e) {
			self::assertEquals('queue_data_not_iterable', $e->getErrorCode());
		}
		self::assertEquals([1], $queue->toArray());
	}
}
