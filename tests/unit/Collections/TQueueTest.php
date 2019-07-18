<?php

use Prado\Collections\TQueue;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;


/**
 * @package System.Collections
 */
class TQueueTest extends PHPUnit\Framework\TestCase {

	public function setUp() {
	}

	public function tearDown() {
	}

	public function testConstruct() {
		$queue = new TQueue();
		self::assertEquals(array(), $queue->toArray());
		$queue = new TQueue(array(1, 2, 3));
		self::assertEquals(array(1, 2, 3), $queue->toArray());
	}

	public function testToArray() {
		$queue = new TQueue(array(1, 2, 3));
		self::assertEquals(array(1, 2, 3), $queue->toArray());
	}

	public function testCopyFrom() {
		$queue = new TQueue(array(1, 2, 3));
		$data = array(4, 5, 6);
		$queue->copyFrom($data);
		self::assertEquals(array(4, 5, 6), $queue->toArray());
	}

	public function testCanNotCopyFromNonTraversableTypes() {
		$queue = new TQueue();
		$data = new stdClass();
		self::expectException('Prado\\Exceptions\\TInvalidDataTypeException');
		$queue->copyFrom($data);
	}

	public function testClear() {
		$queue = new TQueue(array(1, 2, 3));
		$queue->clear();
		self::assertEquals(array(), $queue->toArray());
	}

	public function testContains() {
		$queue = new TQueue(array(1, 2, 3));
		self::assertEquals(true, $queue->contains(2));
		self::assertEquals(false, $queue->contains(4));
	}

	public function testPeek() {
		$queue = new TQueue(array(1,2,3));
		self::assertEquals(1, $queue->peek());
	}

	public function testCanNotPeekAnEmptyQueue() {
		$queue = new TQueue();
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$item = $queue->peek();
	}

	public function testDequeue() {
		$queue = new TQueue(array(1, 2, 3));
		$first = $queue->dequeue();
		self::assertEquals(1, $first);
		self::assertEquals(array(2, 3), $queue->toArray());
	}

	public function testCanNotDequeueAnEmptyQueue() {
		$queue = new TQueue();
		self::expectException('Prado\\Exceptions\\TInvalidOperationException');
		$item = $queue->dequeue();
	}

	public function testEnqueue() {
		$queue = new TQueue();
		$queue->enqueue(1);
		self::assertEquals(array(1), $queue->toArray());
	}

 	public function testGetIterator() {
		$queue = new TQueue(array(1, 2));
		self::assertInstanceOf('ArrayIterator', $queue->getIterator());
		$n = 0;
		$found = 0;
		foreach($queue as $index => $item) {
			foreach($queue as $a => $b); // test of iterator
			$n++;
			if($index === 0 && $item === 1) {
				$found++;
			}
			if($index === 1 && $item === 2) {
				$found++;
			}
		}
		self::assertTrue($n == 2 && $found == 2);
	}

	public function testGetCount() {
    	$queue = new TQueue();
		self::assertEquals(0, $queue->getCount());
		$queue = new TQueue(array(1, 2, 3));
		self::assertEquals(3, $queue->getCount());
	}

	public function testCountable() {
		$queue = new TQueue();
		self::assertEquals(0, count($queue));
		$queue = new TQueue(array(1, 2, 3));
		self::assertEquals(3, count($queue));
	}

}

