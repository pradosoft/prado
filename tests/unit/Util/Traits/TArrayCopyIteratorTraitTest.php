<?php

/**
 * TArrayCopyIteratorTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Traits;

use Prado\Util\Traits\TArrayCopyIteratorTrait;

/**
 * Consumer that counts the copies the trait takes of its source array.
 */
class TArrayCopyIteratorTraitConsumer implements \Iterator
{
	use TArrayCopyIteratorTrait;

	public int $copies = 0;

	public function __construct(public array $source)
	{
	}

	protected function getIteratorArrayCopy(): array
	{
		$this->copies++;
		return $this->source;
	}

	public function ensure(): void
	{
		$this->ensureIteratorArray();
	}

	public function getDirect(): ?array
	{
		return $this->getIteratorArrayDirect();
	}

	public function setDirect(?array $array): void
	{
		$this->setIteratorArrayDirect($array);
	}
}

/**
 * Unit tests for {@see \Prado\Util\Traits\TArrayCopyIteratorTrait}.
 */
class TArrayCopyIteratorTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testIterationCopiesTheSourceOnce()
	{
		$consumer = new TArrayCopyIteratorTraitConsumer(['a' => 1, 'b' => 2]);
		$this->assertNull($consumer->getDirect());

		$this->assertSame(['a' => 1, 'b' => 2], iterator_to_array($consumer));
		$this->assertSame(1, $consumer->copies);
		$this->assertSame(['a' => 1, 'b' => 2], $consumer->getDirect());

		$consumer->source = ['c' => 3];
		$this->assertSame(['a' => 1, 'b' => 2], iterator_to_array($consumer));
		$this->assertSame(1, $consumer->copies);
	}

	public function testEnsureIteratorArrayCopiesOnlyWithoutACopy()
	{
		$consumer = new TArrayCopyIteratorTraitConsumer(['a' => 1]);

		$consumer->ensure();
		$this->assertSame(1, $consumer->copies);
		$this->assertSame(['a' => 1], $consumer->getDirect());

		$consumer->ensure();
		$this->assertSame(1, $consumer->copies);
	}

	public function testSetIteratorArrayDirectReplacesAndClearsTheCopy()
	{
		$consumer = new TArrayCopyIteratorTraitConsumer(['a' => 1]);

		$consumer->setDirect(['x' => 9]);
		$this->assertSame(['x' => 9], iterator_to_array($consumer));
		$this->assertSame(0, $consumer->copies);

		$consumer->setDirect(null);
		$consumer->source = ['b' => 2];
		$this->assertSame(['b' => 2], iterator_to_array($consumer));
		$this->assertSame(1, $consumer->copies);
	}
}
