<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TEmptyControlCollection;

class TEmptyControlCollectionTest extends \PHPUnit\Framework\TestCase
{
	public function testInsertAtIgnoresAString(): void
	{
		$collection = new TEmptyControlCollection(new TControl());
		$collection->insertAt(0, 'text');
		$this->assertSame(0, $collection->getCount());
		$this->assertTrue($collection->getReadOnly());
	}

	public function testInsertAtRejectsAControl(): void
	{
		$collection = new TEmptyControlCollection(new TControl());
		$this->expectException(TInvalidOperationException::class);
		$collection->insertAt(0, new TControl());
	}
}
