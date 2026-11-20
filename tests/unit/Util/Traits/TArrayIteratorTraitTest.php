<?php

/**
 * TArrayIteratorTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Traits;

use Prado\Util\Traits\TArrayIteratorTrait;

/**
 * Consumer iterating its own array by reference.
 */
class TArrayIteratorTraitConsumer implements \Iterator
{
	use TArrayIteratorTrait;

	public function __construct(private array $_data)
	{
	}

	protected function &getIteratorArray(): array
	{
		return $this->_data;
	}
}

/**
 * Unit tests for {@see \Prado\Util\Traits\TArrayIteratorTrait}.
 */
class TArrayIteratorTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testIteratesKeysAndValues()
	{
		$consumer = new TArrayIteratorTraitConsumer(['a' => 1, 'b' => 2, 3 => 'c']);

		$this->assertSame(['a' => 1, 'b' => 2, 3 => 'c'], iterator_to_array($consumer));
	}

	public function testIteratorMethodsMoveTheArrayPointer()
	{
		$consumer = new TArrayIteratorTraitConsumer(['a' => 1, 'b' => 2]);

		$consumer->rewind();
		$this->assertTrue($consumer->valid());
		$this->assertSame('a', $consumer->key());
		$this->assertSame(1, $consumer->current());

		$consumer->next();
		$this->assertTrue($consumer->valid());
		$this->assertSame('b', $consumer->key());
		$this->assertSame(2, $consumer->current());

		$consumer->next();
		$this->assertFalse($consumer->valid());
		$this->assertNull($consumer->key());
		$this->assertFalse($consumer->current());

		$consumer->rewind();
		$this->assertSame('a', $consumer->key());
	}

	public function testFalseAndNullValuesAreValid()
	{
		$consumer = new TArrayIteratorTraitConsumer([false, null]);

		$this->assertSame([false, null], iterator_to_array($consumer));
	}

	public function testEmptyArrayIsNotValid()
	{
		$consumer = new TArrayIteratorTraitConsumer([]);

		$consumer->rewind();
		$this->assertFalse($consumer->valid());
		$this->assertSame([], iterator_to_array($consumer));
	}
}
