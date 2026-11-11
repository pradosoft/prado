<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TMap;
use Prado\Collections\TPagedMapIterator;

class TPagedMapIteratorTest extends \PHPUnit\Framework\TestCase
{
	public function testIteratesWindowWithMapKeys()
	{
		$iterator = new TPagedMapIterator(new TMap(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]), 1, 2);
		self::assertSame(['b' => 2, 'c' => 3], iterator_to_array($iterator));
	}

	public function testFirstPageStartsAtFirstKey()
	{
		$iterator = new TPagedMapIterator(new TMap(['a' => 1, 'b' => 2, 'c' => 3]), 0, 2);
		self::assertSame(['a' => 1, 'b' => 2], iterator_to_array($iterator));
	}

	public function testIteratorProtocol()
	{
		$iterator = new TPagedMapIterator(new TMap(['a' => 1, 'b' => 2, 'c' => 3]), 1, 1);
		$iterator->rewind();
		self::assertTrue($iterator->valid());
		self::assertSame('b', $iterator->key());
		self::assertSame(2, $iterator->current());
		$iterator->next();
		self::assertFalse($iterator->valid());
		$iterator->rewind();
		self::assertTrue($iterator->valid());
		self::assertSame('b', $iterator->key());
	}

	public function testCountIsClampedToMapEnd()
	{
		$iterator = new TPagedMapIterator(new TMap(['a' => 1, 'b' => 2, 'c' => 3]), 1, 10);
		self::assertSame(['b' => 2, 'c' => 3], iterator_to_array($iterator));
	}

	public function testStartIndexPastMapEndIteratesNothing()
	{
		$iterator = new TPagedMapIterator(new TMap(['a' => 1]), 3, 2);
		self::assertSame([], iterator_to_array($iterator));
	}
}
