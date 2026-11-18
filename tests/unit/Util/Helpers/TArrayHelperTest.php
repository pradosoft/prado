<?php

namespace Prado\Test\Unit\Util\Helpers;

use Prado\Util\Helpers\TArrayHelper;

class TArrayHelperTest extends \PHPUnit\Framework\TestCase
{
	public function testArray_is_list()
	{
		self::assertTrue(TArrayHelper::array_is_list([]));
		self::assertTrue(TArrayHelper::array_is_list(['apple', 2, 3]));
		self::assertTrue(TArrayHelper::array_is_list([0 => 'apple', 'orange']));

		// The array does not start at 0
		self::assertFalse(TArrayHelper::array_is_list([1 => 'apple', 'orange']));

		// The keys are not in the correct order
		self::assertFalse(TArrayHelper::array_is_list([1 => 'apple', 0 => 'orange']));

		// Non-integer keys
		self::assertFalse(TArrayHelper::array_is_list([0 => 'apple', 'foo' => 'bar']));

		// Non-consecutive keys
		self::assertFalse(TArrayHelper::array_is_list([0 => 'apple', 2 => 'bar']));
	}

	public function testArray_is_listOfTraversable()
	{
		self::assertTrue(TArrayHelper::array_is_list(new \ArrayIterator([])));
		self::assertTrue(TArrayHelper::array_is_list(new \ArrayIterator(['apple'])));
		self::assertTrue(TArrayHelper::array_is_list(new \ArrayIterator(['apple', 'orange'])));
		self::assertFalse(TArrayHelper::array_is_list(new \ArrayIterator([1 => 'apple'])));
		self::assertFalse(TArrayHelper::array_is_list(new \ArrayIterator([0 => 'apple', 'foo' => 'bar'])));
		self::assertFalse(TArrayHelper::array_is_list((function () {
			yield 'a' => 1;
		})()));
	}
}
