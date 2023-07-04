<?php

use Prado\Util\Helpers\TArrayHelper;

class TArrayHelperTest extends PHPUnit\Framework\TestCase
{
	public function testArray_is_list()
	{
		self::assertTrue(array_is_list([]));
		self::assertTrue(array_is_list(['apple', 2, 3]));
		self::assertTrue(array_is_list([0 => 'apple', 'orange']));
		
		// The array does not start at 0
		self::assertFalse(array_is_list([1 => 'apple', 'orange']));
		
		// The keys are not in the correct order
		self::assertFalse(array_is_list([1 => 'apple', 0 => 'orange']));
		
		// Non-integer keys
		self::assertFalse(array_is_list([0 => 'apple', 'foo' => 'bar']));
		
		// Non-consecutive keys
		self::assertFalse(array_is_list([0 => 'apple', 2 => 'bar']));
	}
}